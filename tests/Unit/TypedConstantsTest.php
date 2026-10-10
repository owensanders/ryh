<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\DiscoversClassesTrait;

class TypedConstantsTest extends TestCase
{
    use DiscoversClassesTrait;

    /** PSR-4 roots (see composer.json) whose classes must type their constants. */
    private const array ROOTS = ['App\\' => 'app', 'Tests\\' => 'tests'];

    #[Test]
    public function everyClassConstantDeclaresItsType(): void
    {
        $untyped = [];

        foreach (self::ROOTS as $namespace => $directory) {
            foreach ($this->classesIn($namespace, $directory) as $class) {
                foreach ((new ReflectionClass($class))->getReflectionConstants() as $constant) {
                    // Enum cases are constants too, but can't take a type.
                    if ($constant->getDeclaringClass()->getName() === $class && ! $constant->isEnumCase() && ! $constant->hasType()) {
                        $untyped[] = "{$class}::{$constant->getName()}";
                    }
                }
            }
        }

        $this->assertSame([], $untyped, 'Give these constants a type, e.g. `private const int LIMIT = 10;`');
    }
}
