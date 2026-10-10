<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MoneyHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function itFormatsPenceAsWholePoundsWithThousandSeparators(): void
    {
        $this->assertSame('£0', MoneyHelper::format(0));
        $this->assertSame('£1', MoneyHelper::format(199));
        $this->assertSame('£1,284', MoneyHelper::format(128_400));
    }
}
