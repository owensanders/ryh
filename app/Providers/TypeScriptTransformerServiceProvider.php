<?php

declare(strict_types=1);

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;

/** Generates resources/js/types/generated.ts from the Data classes and enums: `artisan typescript:transform`. */
class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->extension(new LaravelDataTypeScriptTransformerExtension())
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path('Data'), app_path('Enums'))
            ->outputDirectory(resource_path('js/types'))
            ->writer(new FlatModuleWriter('generated.ts'))
            // Its hashes would skip rewriting a hand-edited generated.ts, hiding it from CI's diff check.
            ->withoutManifest()
            ->formatter(PrettierFormatter::class);
    }
}
