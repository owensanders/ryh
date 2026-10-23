<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Who's in a household, and in what role, can't change this way: joining,
 * inviting, changing a role, removing someone, leaving, deleting an account.
 * Each message shows under the field it's about.
 */
final class MembershipException extends UserFacingException
{
    private const string DATA_LOST = '(chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.';

    private function __construct(string $message, private readonly string $field)
    {
        parent::__construct($message);
    }

    public static function alreadyMember(): self
    {
        return new self('This person is already part of the household.', 'email');
    }

    public static function fullForInvites(int $maxMembers): self
    {
        return new self("Your household is full — it can have up to {$maxMembers} members, including pending invites.", 'email');
    }

    public static function unknownJoinCode(): self
    {
        return new self('That code doesn\'t match a household.', 'code');
    }

    public static function notTakingJoinRequests(): self
    {
        return new self('This household isn\'t accepting join requests right now.', 'code');
    }

    public static function fullForJoining(): self
    {
        return new self('That household is full, so it can\'t take any more members.', 'code');
    }

    public static function soleOwnerCantStepDown(): self
    {
        return new self("You're the only owner — promote someone else first.", 'role');
    }

    public static function soleOwnerCantBeRemoved(): self
    {
        return new self("You're the only owner — promote someone else first.", 'member');
    }

    public static function soleOwnerLeaving(): self
    {
        return new self("You're the sole owner of this household. Leaving will permanently delete it and all its data ".self::DATA_LOST, 'household');
    }

    /** @param  list<string>  $householdNames  The households others would lose with the account. */
    public static function soleOwnerDeletingAccount(array $householdNames): self
    {
        [$which, $them] = count($householdNames) === 1
            ? ['this household', 'the household and all its data']
            : ['these households ('.implode(', ', $householdNames).')', 'them and all their data'];

        return new self("You're the sole owner of {$which}. Deleting your account will permanently delete {$them} ".self::DATA_LOST, 'household');
    }

    public function errorKey(): string
    {
        return $this->field;
    }
}
