<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Controllers take a request's values already typed, from its form request:
 * `toInput()` for a form whose fields go to an action together, or a named
 * getter for one value (`$request->status()`). They never read, cast or
 * parse raw input (CLAUDE.md, "Form requests"). Judged by type, so any
 * request object counts, however it's held.
 *
 * @implements Rule<MethodCall>
 */
final class TypedRequestValuesRule implements Rule
{
    private const array CONTROLLERS = ['App\\Http\\Controllers\\'];

    /** Request methods that read what the browser sent. */
    private const array RAW_READS = [
        'validated', 'safe', 'input', 'query', 'post', 'get', 'all', 'only', 'except', 'collect', 'keys',
        'string', 'str', 'integer', 'boolean', 'float', 'date', 'enum', 'enums', 'array', 'interval',
        'has', 'hasAny', 'filled', 'anyFilled', 'missing', 'exists', 'file', 'allFiles', 'json', 'route',
    ];

    /** Controller => why it reads the request itself. */
    private const array ALLOWED = [
        'App\\Http\\Controllers\\StripeWebhookController' => "Stripe's signed webhook payload, read the way Cashier's controller does.",
    ];

    /**
     * @param  list<string>  $controllers  Namespaces checked; the rule tests point it at their fixtures.
     * @param  array<string, string>  $allowed
     */
    public function __construct(
        private readonly array $controllers = self::CONTROLLERS,
        private readonly array $allowed = self::ALLOWED,
    ) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $namespace = ($scope->getNamespace() ?? '').'\\';

        if (! $node->name instanceof Identifier || ! in_array($node->name->toString(), self::RAW_READS, true)
            || array_filter($this->controllers, fn (string $layer): bool => str_starts_with($namespace, $layer)) === []) {
            return [];
        }

        if (array_key_exists($scope->getClassReflection()?->getName() ?? '', $this->allowed)
            || ! (new ObjectType(Request::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Reads the request in a controller: ->{$node->name->toString()}(). Give the form request a typed getter, or toInput() for a form.")
                ->identifier('ryh.typedRequestValues')
                ->build(),
        ];
    }
}
