<?php

declare(strict_types=1);

namespace Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/** Finds the classes, interfaces, traits and enums under a PSR-4 root, for architecture tests. */
trait DiscoversClassesTrait
{
    /**
     * @return array<string, class-string> Path relative to the root (without `.php`) => class name.
     */
    private function classesIn(string $namespace, string $directory): array
    {
        $directory = dirname(__DIR__, 2).'/'.$directory;
        $classes = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $class = $namespace.str_replace('/', '\\', $relative);

            if (class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class)) {
                $classes[$relative] = $class;
            }
        }

        ksort($classes);

        return $classes;
    }
}
