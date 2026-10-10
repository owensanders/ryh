<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

class TypedConstantsTest extends TestCase
{
    /** PSR-4 roots (see composer.json) whose classes must type their constants. */
    private const array ROOTS = ['App\\' => 'app', 'Tests\\' => 'tests'];

    #[Test]
    public function everyClassConstantDeclaresItsType(): void
    {
        $untyped = [];

        foreach (self::ROOTS as $namespace => $directory) {
            foreach ($this->classesIn($namespace, dirname(__DIR__, 2).'/'.$directory) as $class) {
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

    /** @return list<class-string> */
    private function classesIn(string $namespace, string $directory): array
    {
        $classes = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $class = $namespace.str_replace('/', '\\', $relative);

            if (class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
