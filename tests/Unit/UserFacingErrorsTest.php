<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Actions say what went wrong with an exception, never with HTTP: a
 * UserFacingException subclass for a message people should read, and
 * ModelNotFoundException for "not in this household" (CLAUDE.md, "Errors").
 * And nothing catches RuntimeException to show its message, because a
 * database error is a RuntimeException too.
 */
class UserFacingErrorsTest extends TestCase
{
    #[Test]
    public function actionsAndUseCasesNeverAbort(): void
    {
        foreach (['app/Actions', 'app/UseCases'] as $directory) {
            $this->assertSame([], $this->linesMatching($directory, '/\babort(_if|_unless)?\(/'), 'Throw a UserFacingException or ModelNotFoundException instead.');
        }
    }

    #[Test]
    public function nothingShowsARuntimeExceptionsMessage(): void
    {
        $this->assertSame([], $this->linesMatching('app', '/catch \(([\w\\\\|]*\|)?\\\\?RuntimeException\b/'), 'Catch the UserFacingException subclass you mean instead.');
    }

    /** @return list<string> */
    private function linesMatching(string $directory, string $pattern): array
    {
        $root = dirname(__DIR__, 2);
        $found = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}")) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach (file($file->getPathname()) ?: [] as $number => $line) {
                if (preg_match($pattern, $line)) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1).':'.($number + 1).'  '.trim($line);
                }
            }
        }

        return $found;
    }
}
