<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidGameId;
use Nazymko\ScoreBoard\Domain\GameId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testCanBeRebuiltFromItsStringForm(): void
    {
        $id = GameId::generate();

        self::assertTrue(GameId::fromString((string) $id)->equals($id));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedIds(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['abc123'];
        yield 'non-hex characters' => [str_repeat('g', 32)];
        yield 'uppercase hex' => [str_repeat('A', 32)];
    }

    #[DataProvider('malformedIds')]
    public function testRejectsMalformedStrings(string $value): void
    {
        $this->expectException(InvalidGameId::class);

        GameId::fromString($value);
    }

    public function testStringFormIsTheOpaqueValue(): void
    {
        $id = GameId::generate();

        self::assertSame($id->value, (string) $id);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $id);
    }
}
