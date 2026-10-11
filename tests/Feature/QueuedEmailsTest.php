<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Notifications\AiCreditsExhaustedNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Traits\RetriesEmailTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/** Email goes through the queue, so a slow or down mail provider can't fail a request. */
class QueuedEmailsTest extends TestCase
{
    use RefreshDatabase;

    private const int MAX_QUEUE_DELAY_SECONDS = 900;

    #[Test]
    public function everyEmailIsQueuedAndRetriesWithABackoff(): void
    {
        $emails = collect([...File::files(app_path('Mail')), ...File::files(app_path('Notifications'))])
            ->map(fn (SplFileInfo $file) => 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($file->getPathname(), app_path().'/')))
            ->filter(fn (string $class) => is_subclass_of($class, Mailable::class) || method_exists($class, 'toMail'))
            ->values();

        $this->assertContains(VerifyEmailNotification::class, $emails);
        $this->assertContains(ResetPasswordNotification::class, $emails);
        $this->assertContains(AiCreditsExhaustedNotification::class, $emails);

        foreach ($emails as $email) {
            $this->assertTrue(is_subclass_of($email, ShouldQueue::class), "{$email} should implement ShouldQueue.");
            $this->assertContains(RetriesEmailTrait::class, class_uses_recursive($email), "{$email} should use RetriesEmailTrait.");
        }

        // Laravel Cloud's managed queue can't delay a job by more than 15 minutes.
        foreach ((new class
        {
            use RetriesEmailTrait;
        })->backoff() as $seconds) {
            $this->assertLessThanOrEqual(self::MAX_QUEUE_DELAY_SECONDS, $seconds);
        }
    }

    #[Test]
    public function signingUpQueuesTheVerificationEmailRatherThanSendingIt(): void
    {
        Queue::fake();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ])->assertRedirect(route('household.setup', absolute: false));

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof VerifyEmailNotification);
    }
}
