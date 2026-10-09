<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidTeamName;
use Nazymko\ScoreBoard\Domain\Team;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Team::class)]
final class TeamTest extends TestCase
{
    public function testKeepsTheNameAsGiven(): void
    {
        self::assertSame("Côte d'Ivoire", new Team("Côte d'Ivoire")->name);
    }

    public function testTrimsSurroundingWhitespace(): void
    {
        self::assertSame('Mexico', new Team("\u{00A0} Mexico\t\n")->name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blankNames(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
        yield 'tabs and newlines' => ["\t\n"];
        yield 'non-breaking space' => ["\u{00A0}"];
    }

    #[DataProvider('blankNames')]
    public function testRejectsBlankNames(string $name): void
    {
        $this->expectException(InvalidTeamName::class);

        new Team($name);
    }

    public function testNamesAreComparedIgnoringCaseAndSurroundingWhitespace(): void
    {
        self::assertTrue(new Team('Spain')->equals(new Team(' SPAIN ')));
    }

    public function testNonAsciiNamesAreComparedIgnoringCase(): void
    {
        self::assertTrue(new Team("CÔTE D'IVOIRE")->equals(new Team("côte d'ivoire")));
    }

    public function testDifferentNamesAreNotEqual(): void
    {
        self::assertFalse(new Team('Spain')->equals(new Team('Brazil')));
    }
}
