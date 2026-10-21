<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\DiscoversClassesTrait;

/** One operation per Action, run by handle() (CLAUDE.md, "Layers"). */
class ActionsShapeTest extends TestCase
{
    use DiscoversClassesTrait;

    #[Test]
    public function everyActionRunsThroughHandle(): void
    {
        $wrong = [];

        foreach ($this->classesIn('App\\Actions\\', 'app/Actions') as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasMethod('handle') || ! $reflection->getMethod('handle')->isPublic() || $reflection->hasMethod('execute')) {
                $wrong[] = $class;
            }
        }

        $this->assertSame([], $wrong, 'Give each Action one public handle() method, and no execute().');
    }
}
