<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\Support\DiscoversClassesTrait;
use Tests\TestCase;

/**
 * Every query goes through a repository (CLAUDE.md, "Repositories"). Route
 * model binding's lookup by id is the one exception: those models arrive
 * ready, and their attributes are fine to read.
 */
class RepositoryBoundaryTest extends TestCase
{
    use DiscoversClassesTrait;

    /** Where queries must not appear. */
    private const array LAYERS = ['app/Actions', 'app/ViewModels', 'app/Http', 'app/Policies', 'app/Listeners'];

    /** Static entry points into a query, on a model (filled in below) or the query builder. */
    private const string STATIC_QUERY = '/\b(%s)::(query|where\w*|find\w*|first\w*|create|firstOrCreate|updateOrCreate|all|count|exists|destroy|insert)\(|\bDB::(table|select|statement|insert|update|delete)\(/';

    /** Writes or reloads called on a model held in a variable (not on `$this->repository`). */
    private const string MODEL_WRITE = '/\$(?!this\b)\w+(?:\?)?->(save|update|delete|forceFill|forceDelete|refresh|fresh|load|loadMissing|increment|decrement|touch|push|markAsRead|markAsUnread)\(/';

    #[Test]
    public function queriesOnlyHappenInRepositories(): void
    {
        $models = array_map(fn (string $model) => class_basename($model), array_values($this->classesIn('App\\Models\\', 'app/Models')));
        $staticQuery = sprintf(self::STATIC_QUERY, implode('|', [...$models, 'DatabaseNotification']));
        $relations = $this->relationMethodNames();
        // On a model in a variable, or on one a call returned (`$this->user($request)->notifications()`).
        $relationCall = '/(\$(?!this\b)\w+\??|\))->('.implode('|', $relations).')\(\)/';
        $found = [];

        foreach (self::LAYERS as $layer) {
            foreach ($this->classesIn('App\\'.str_replace('/', '\\', substr($layer, 4)).'\\', $layer) as $path => $class) {
                $file = base_path("{$layer}/{$path}.php");

                foreach (file($file) ?: [] as $number => $line) {
                    if (preg_match('/^\s*(\*|\/\/|\/\*)/', $line)) {
                        continue;
                    }

                    foreach ([$staticQuery, self::MODEL_WRITE, $relationCall] as $pattern) {
                        if (preg_match($pattern, $line, $match)) {
                            $found[] = "{$layer}/{$path}.php:".($number + 1).'  '.trim($match[0]);
                        }
                    }
                }
            }
        }

        $this->assertSame([], $found, 'Move these into a repository in app/Repositories.');
    }

    /** @return list<string> Every relation method on the app's models, found by return type. */
    private function relationMethodNames(): array
    {
        $names = [];

        foreach ($this->classesIn('App\\Models\\', 'app/Models') as $model) {
            foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $type = $method->getReturnType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && is_a($type->getName(), Relation::class, true)) {
                    $names[] = $method->getName();
                }
            }
        }

        // Laravel's own: the notifications a user has, and their push devices.
        return array_values(array_unique([...$names, 'notifications', 'readNotifications', 'unreadNotifications', 'pushSubscriptions', 'subscriptions']));
    }
}
