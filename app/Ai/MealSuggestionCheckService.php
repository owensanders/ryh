<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * What the AI suggests is checked before anyone sees it: each meal must be
 * exactly the shape MealPlanAgent's schema asks for, within the lengths a
 * saved recipe allows (PlannedMealRequest), and free of things a dinner
 * never needs: links, email addresses, markup, or text aimed at the AI.
 * A meal that fails is dropped, not repaired.
 */
final class MealSuggestionCheckService
{
    /** Field => longest it may be, matching what accepting it saves. */
    private const array MAX_LENGTHS = ['name' => 120, 'description' => 2000, 'duration_label' => 40];

    /** MealPlanAgent's schema offers only these. */
    public const array DIFFICULTIES = ['Easy', 'Medium', 'Hard'];

    private const int MAX_TAGS = 8;

    private const int MAX_TAG_LENGTH = 30;

    private const int MAX_INGREDIENTS = 40;

    private const int MAX_INGREDIENT_NAME_LENGTH = 120;

    private const int MAX_QUANTITY_LENGTH = 40;

    /** More than any dinner's ingredients cost in pounds: past this, the number is nonsense. */
    private const int MAX_COST = 1000;

    /** Never in a recipe: a link, an email address, markup, a code block, or talk of instructions. */
    private const array BLOCKED = [
        '~https?://|www\.~i',
        '~[\w.+-]+@[\w-]+\.[\w.]+~',
        '~<\s*/?\s*[a-z!][^>]*>~i',
        '~```~',
        '~\b(ignore|disregard|forget)\b.{0,40}\b(instructions?|prompt|rules)\b~i',
        '~\b(system prompt|as an ai|language model)\b~i',
    ];

    /**
     * The meals that pass, in order.
     *
     * @param  array<mixed>  $meals  The AI's answer's `meals`.
     *
     * @return list<array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: float, tags: list<string>, ingredients: list<array{name: string, quantity: string}>}>
     */
    public function usable(array $meals): array
    {
        $usable = [];

        foreach ($meals as $meal) {
            if (is_array($meal) && $this->isUsable($meal)) {
                // isUsable() has checked every field's type, which PHPStan can't follow through it.
                /** @var array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: int|float, tags: list<string>, ingredients: list<array{name: string, quantity: string}>} $meal */
                $usable[] = [...$meal, 'estimated_cost' => (float) $meal['estimated_cost']];
            }
        }

        return $usable;
    }

    /** @param  array<mixed>  $meal */
    private function isUsable(array $meal): bool
    {
        $keys = ['name', 'description', 'duration_label', 'difficulty', 'estimated_cost', 'tags', 'ingredients'];

        if (array_diff(array_keys($meal), $keys) !== [] || array_diff($keys, array_keys($meal)) !== []) {
            return false;
        }

        foreach (self::MAX_LENGTHS as $field => $max) {
            if (! $this->isText($meal[$field], $max)) {
                return false;
            }
        }

        if (! in_array($meal['difficulty'], self::DIFFICULTIES, true)) {
            return false;
        }

        if (! is_int($meal['estimated_cost']) && ! is_float($meal['estimated_cost']) || $meal['estimated_cost'] < 0 || $meal['estimated_cost'] > self::MAX_COST) {
            return false;
        }

        if (! is_array($meal['tags']) || ! array_is_list($meal['tags']) || count($meal['tags']) > self::MAX_TAGS) {
            return false;
        }

        foreach ($meal['tags'] as $tag) {
            if (! $this->isText($tag, self::MAX_TAG_LENGTH)) {
                return false;
            }
        }

        if (! is_array($meal['ingredients']) || ! array_is_list($meal['ingredients']) || $meal['ingredients'] === [] || count($meal['ingredients']) > self::MAX_INGREDIENTS) {
            return false;
        }

        foreach ($meal['ingredients'] as $ingredient) {
            if (! is_array($ingredient) || array_keys($ingredient) !== ['name', 'quantity']
                || ! $this->isText($ingredient['name'], self::MAX_INGREDIENT_NAME_LENGTH)
                || ! $this->isText($ingredient['quantity'], self::MAX_QUANTITY_LENGTH, allowEmpty: true)) {
                return false;
            }
        }

        return true;
    }

    /** Text within `$max` (and not blank, unless `$allowEmpty`), with nothing blocked in it. */
    private function isText(mixed $value, int $max, bool $allowEmpty = false): bool
    {
        if (! is_string($value) || (! $allowEmpty && trim($value) === '') || mb_strlen($value) > $max) {
            return false;
        }

        foreach (self::BLOCKED as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return false;
            }
        }

        return true;
    }
}
