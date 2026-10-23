<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * An action's handle() takes what it needs as types, never a keyed array
 * (`array<string, mixed> $data`): a write's form input arrives as a typed
 * input from app/Inputs (CLAUDE.md, "Layers"). A list of values is fine.
 * Judged by the parameter's resolved type, PHPDoc included.
 *
 * @implements Rule<InClassMethodNode>
 */
final class TypedActionInputRule implements Rule
{
    private const array ACTIONS = ['App\\Actions\\'];

    /** Class::$parameter => why it may stay a keyed array. */
    private const array ALLOWED = [
        'App\\Actions\\Billing\\SendPaymentFailedNoticeAction::$invoice' => "Stripe's webhook payload, as Cashier hands it over.",
        'App\\Actions\\Billing\\SendRenewalReminderAction::$invoice' => "Stripe's webhook payload, as Cashier hands it over.",
    ];

    /**
     * @param  list<string>  $actions  Namespaces whose handle() is checked; the rule tests point it at their fixtures.
     * @param  array<string, string>  $allowed
     */
    public function __construct(
        private readonly array $actions = self::ACTIONS,
        private readonly array $allowed = self::ALLOWED,
    ) {}

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getMethodReflection();
        $class = $method->getDeclaringClass()->getName();

        if ($method->getName() !== 'handle' || ! $this->isAction($class)) {
            return [];
        }

        $errors = [];

        foreach ($method->getVariants()[0]->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = "{$class}::\${$parameter->getName()}";

            if (! $type->isArray()->yes() || $type->isList()->yes() || array_key_exists($name, $this->allowed)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'handle() takes $%s as a keyed array (%s). Give it a typed input from app/Inputs, built by the form request\'s toInput().',
                $parameter->getName(),
                $type->describe(VerbosityLevel::typeOnly()),
            ))->identifier('ryh.typedActionInput')->build();
        }

        return $errors;
    }

    private function isAction(string $class): bool
    {
        foreach ($this->actions as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
