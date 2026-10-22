<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Queries belong in actions, view models and models (CLAUDE.md, "Where
 * queries live"), never in the HTTP layer, policies, listeners, mail or
 * notifications: nothing there starts a query, runs one, or writes a model.
 * Judged by type, so a query is caught however it's written, split over
 * lines or held in a variable. Asking a model a question
 * (`$user->membershipIn($id)`) is the allowed way, and so is reading a model
 * you were handed, its relations included (N+1 loads are caught at runtime
 * by preventLazyLoading). Validation rules (`Rule::exists()`) are lookups the
 * validator runs, not calls here.
 *
 * @implements Rule<Expr>
 */
final class QueryBoundaryRule implements Rule
{
    /** Where queries must not appear. */
    private const array LAYERS = ['App\\Http\\', 'App\\Policies\\', 'App\\Listeners\\', 'App\\Mail\\', 'App\\Notifications\\'];

    /** Model methods that write or reload. */
    private const array MODEL_WRITES = [
        'save', 'saveQuietly', 'update', 'updateQuietly', 'delete', 'deleteQuietly', 'forceDelete', 'refresh', 'fresh',
        'load', 'loadMissing', 'loadCount', 'increment', 'decrement', 'touch', 'push', 'markAsRead', 'markAsUnread',
    ];

    private const string ADVICE = 'Move it into an action or view model, or ask the model (a method on it, a #[Scope] for reuse).';

    /** @param  list<string>  $layers  Namespaces where queries must not appear; the rule tests point it at their fixtures. */
    public function __construct(private readonly array $layers = self::LAYERS) {}

    public function getNodeType(): string
    {
        return Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->inGuardedLayer($scope)) {
            return [];
        }

        $message = match (true) {
            $node instanceof StaticCall => $this->staticCall($node, $scope),
            $node instanceof MethodCall, $node instanceof NullsafeMethodCall => $this->methodCall($node, $scope),
            default => null,
        };

        return $message === null ? [] : [$this->error($message)];
    }

    private function staticCall(StaticCall $node, Scope $scope): ?string
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier) {
            return null;
        }

        $class = $scope->resolveName($node->class);
        $short = substr((string) strrchr('\\'.$class, '\\'), 1);
        $call = "{$short}::{$node->name->toString()}()";

        if ($this->isModel(new ObjectType($class))) {
            return "Queries the {$short} model: {$call}.";
        }

        return $class === DB::class ? "Queries the database: {$call}." : null;
    }

    private function methodCall(MethodCall|NullsafeMethodCall $node, Scope $scope): ?string
    {
        if (! $node->name instanceof Identifier) {
            return null;
        }

        $method = $node->name->toString();
        $callee = TypeCombinator::removeNull($scope->getType($node->var));

        // The start of a chain: something that isn't a query hands one back
        // (a relation method, or a call that returns a builder).
        if (! $this->isQuery($callee) && $this->isQuery(TypeCombinator::removeNull($scope->getType($node)))) {
            return "Starts a query: ->{$method}().";
        }

        // A query someone else started, held in a variable or property.
        if ($this->isQuery($callee) && ! $this->isCall($node->var)) {
            return "Runs a query: ->{$method}().";
        }

        if (in_array($method, self::MODEL_WRITES, true) && $this->isModel($callee)) {
            return "Writes to or reloads a model: ->{$method}().";
        }

        return null;
    }

    private function inGuardedLayer(Scope $scope): bool
    {
        $namespace = ($scope->getNamespace() ?? '').'\\';

        foreach ($this->layers as $layer) {
            if (str_starts_with($namespace, $layer)) {
                return true;
            }
        }

        return false;
    }

    private function isQuery(Type $type): bool
    {
        // Only objects are queries. A call with no value (void, or null once
        // null is taken out) leaves `never`, which would otherwise match.
        if (! $type->isObject()->yes()) {
            return false;
        }

        foreach ([EloquentBuilder::class, QueryBuilder::class, Relation::class] as $query) {
            if ((new ObjectType($query))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }

    private function isModel(Type $type): bool
    {
        return $type->isObject()->yes() && (new ObjectType(Model::class))->isSuperTypeOf($type)->yes();
    }

    private function isCall(Expr $expr): bool
    {
        return $expr instanceof MethodCall || $expr instanceof NullsafeMethodCall || $expr instanceof StaticCall;
    }

    private function error(string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message("{$message} ".self::ADVICE)->identifier('ryh.queryBoundary')->build();
    }
}
