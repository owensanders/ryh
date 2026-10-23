<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/** Why a meal plan (AI or from the recipe library) couldn't be suggested. */
final class MealPlanUnavailableException extends UserFacingException
{
    public static function notConfigured(): self
    {
        return new self('AI meal planning is not set up yet.');
    }

    public static function paused(): self
    {
        return new self('AI meal planning is taking a break — you can still plan meals by hand.');
    }

    public static function outOfCredit(Throwable $previous): self
    {
        return new self('AI meal planning is taking a break — you can still plan meals by hand. Your weekly plan hasn\'t been used.', previous: $previous);
    }

    public static function weeklyLimitReached(): self
    {
        return new self('Your household can generate one AI meal plan per week — check back soon.');
    }

    public static function dailyCapReached(): self
    {
        return new self('AI meal planning has hit its limit for today — try again tomorrow.');
    }

    public static function alreadyGenerating(): self
    {
        return new self('A meal plan is already being generated for your household.');
    }

    public static function providerFailed(Throwable $previous): self
    {
        return new self('We couldn\'t generate a meal plan right now — please try again in a few minutes.', previous: $previous);
    }

    /** The AI answered, but nothing it suggested passed MealSuggestionCheckService. */
    public static function unusableAnswer(): self
    {
        return new self('We couldn\'t generate a meal plan right now — please try again in a few minutes.');
    }

    public static function noMatchingRecipes(): self
    {
        return new self('None of your saved recipes match — try loosening the filters or add more recipes.');
    }

    public function errorKey(): string
    {
        return 'ai';
    }
}
