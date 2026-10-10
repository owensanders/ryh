<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\DiscoversClassesTrait;

/**
 * A class's name ends with what it is (CLAUDE.md, "Naming"). Models are the
 * one exception: their class names are stored in the database.
 */
class NamingConventionsTest extends TestCase
{
    use DiscoversClassesTrait;

    /**
     * Folder under app/ => the suffix every class in it takes, most specific
     * folder first; null means no suffix. A new folder needs a rule here.
     */
    private const array SUFFIXES = [
        'Ai/Agents' => 'Agent',
        'Ai/Fakes' => 'Fake',
        'Ai' => 'Service',
        'Contracts/Repositories' => 'RepositoryInterface',
        'Data' => 'Data',
        'Enums' => 'Enum',
        'Events' => 'Event',
        'Http/Controllers' => 'Controller',
        'Http/Middleware' => 'Middleware',
        'Http/Requests' => 'Request',
        'Jobs' => 'Job',
        'Listeners' => 'Listener',
        'Mail' => 'Mail',
        'Models' => null,
        'Notifications/Channels' => 'Channel',
        'Notifications/Messages' => 'Message',
        'Notifications' => 'Notification',
        'Policies' => 'Policy',
        'Providers' => 'ServiceProvider',
        'Repositories' => 'Repository',
        'Status' => 'Service',
        'Support' => 'Helper',
        'Traits' => 'Trait',
        'UseCases' => 'UseCase',
    ];

    #[Test]
    public function everyAppClassEndsWithWhatItIs(): void
    {
        $wrong = [];

        foreach ($this->classesIn('App\\', 'app') as $path => $class) {
            $folder = $this->folderRuleFor($path);
            $name = (new ReflectionClass($class))->getShortName();

            if ($folder === false) {
                $wrong[] = "{$class}: no naming rule for its folder; add one to ".self::class.'::SUFFIXES';

                continue;
            }

            $suffix = self::SUFFIXES[$folder];

            if ($suffix !== null && ! str_ends_with($name, $suffix)) {
                $wrong[] = "{$class}: should end with {$suffix}";
            }
        }

        $this->assertSame([], $wrong);
    }

    #[Test]
    public function everyTraitInterfaceAndEnumSaysSo(): void
    {
        $wrong = [];

        foreach (['App\\' => 'app', 'Tests\\' => 'tests'] as $namespace => $directory) {
            foreach ($this->classesIn($namespace, $directory) as $class) {
                $reflection = new ReflectionClass($class);
                $kind = match (true) {
                    $reflection->isTrait() => 'Trait',
                    $reflection->isInterface() => 'Interface',
                    $reflection->isEnum() => 'Enum',
                    default => null,
                };

                if ($kind !== null && ! str_ends_with($reflection->getShortName(), $kind)) {
                    $wrong[] = "{$class}: should end with {$kind}";
                }
            }
        }

        $this->assertSame([], $wrong);
    }

    /** The most specific folder rule covering this path, or false if none does. */
    private function folderRuleFor(string $path): string|false
    {
        foreach (array_keys(self::SUFFIXES) as $folder) {
            if (str_starts_with($path, $folder.'/')) {
                return $folder;
            }
        }

        return false;
    }
}
