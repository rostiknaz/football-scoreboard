<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\GameId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GameId::class)]
final class GameIdTest extends TestCase
{
    public function testGeneratedIdsAreUnique(): void
    {
        self::assertFalse(GameId::generate()->equals(GameId::generate()));
    }

    public function testEqualsItself(): void
    {
        $id = GameId::generate();

        self::assertTrue($id->equals($id));
    }

    public function testStringFormIsTheOpaqueValue(): void
    {
        $id = GameId::generate();

        self::assertSame($id->value, (string) $id);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $id);
    }
}
