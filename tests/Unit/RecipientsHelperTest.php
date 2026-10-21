<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Support\RecipientsHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RecipientsHelperTest extends TestCase
{
    #[Test]
    public function everyoneInvolvedHearsOnceButNeverThePersonWhoActed(): void
    {
        [$sarah, $james, $mia] = [$this->user(1), $this->user(2), $this->user(3)];

        $recipients = RecipientsHelper::except($sarah, [$james, null, $sarah, $mia, $james]);

        $this->assertSame([2, 3], array_map(fn (User $user) => $user->id, $recipients));
    }

    private function user(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }
}
