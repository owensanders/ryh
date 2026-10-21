<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\DiscoversClassesTrait;

/** Actions run one operation through handle(); view models build one page through toData() (CLAUDE.md, "Layers"). */
class LayerShapeTest extends TestCase
{
    use DiscoversClassesTrait;

    #[Test]
    public function everyActionRunsThroughHandle(): void
    {
        $wrong = [];

        $classes = $this->classesIn('App\\Actions\\', 'app/Actions');
        $this->assertNotEmpty($classes, 'Found nothing to check in app/Actions.');

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasMethod('handle') || ! $reflection->getMethod('handle')->isPublic() || $reflection->hasMethod('execute')) {
                $wrong[] = $class;
            }
        }

        $this->assertSame([], $wrong, 'Give each Action one public handle() method, and no execute().');
    }

    #[Test]
    public function everyViewModelBuildsItsPageThroughToData(): void
    {
        $wrong = [];

        $classes = $this->classesIn('App\\ViewModels\\', 'app/ViewModels');
        $this->assertNotEmpty($classes, 'Found nothing to check in app/ViewModels.');

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasMethod('toData') || ! $reflection->getMethod('toData')->isPublic() || $reflection->hasMethod('handle') || $reflection->hasMethod('execute')) {
                $wrong[] = $class;
            }
        }

        $this->assertSame([], $wrong, 'Give each view model one public toData() method; operations belong in an Action.');
    }
}
