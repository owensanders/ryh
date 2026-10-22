<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use RuntimeException;

/**
 * Actions and view models say what went wrong with an exception, never with
 * HTTP: a UserFacingException subclass for a message people should read, and
 * ModelNotFoundException for "not in this household" (CLAUDE.md, "Errors").
 * And nothing catches RuntimeException, because a database error is one too
 * and its message isn't for people.
 *
 * @implements Rule<Node>
 */
final class UserFacingErrorsRule implements Rule
{
    private const array NO_ABORT = ['App\\Actions\\', 'App\\ViewModels\\'];

    private const array ABORTS = ['abort', 'abort_if', 'abort_unless'];

    /**
     * @param  list<string>  $noAbort  Namespaces that throw rather than abort.
     * @param  string  $root  Where catching RuntimeException is checked. The rule tests point both at their fixtures.
     */
    public function __construct(
        private readonly array $noAbort = self::NO_ABORT,
        private readonly string $root = 'App\\',
    ) {}

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $namespace = ($scope->getNamespace() ?? '').'\\';

        if ($node instanceof FuncCall && $node->name instanceof Name && in_array($node->name->toLowerString(), self::ABORTS, true)
            && array_filter($this->noAbort, fn (string $layer): bool => str_starts_with($namespace, $layer)) !== []) {
            return [
                RuleErrorBuilder::message("{$node->name->toString()}() in an action or view model. Throw a UserFacingException or ModelNotFoundException instead.")
                    ->identifier('ryh.userFacingErrors')
                    ->build(),
            ];
        }

        if ($node instanceof Catch_ && str_starts_with($namespace, $this->root)) {
            foreach ($node->types as $type) {
                if (ltrim($scope->resolveName($type), '\\') === RuntimeException::class) {
                    return [
                        RuleErrorBuilder::message('Catches RuntimeException, which includes database errors. Catch the UserFacingException subclass you mean instead.')
                            ->identifier('ryh.userFacingErrors')
                            ->build(),
                    ];
                }
            }
        }

        return [];
    }
}
