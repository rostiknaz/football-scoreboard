<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidScore;
use Nazymko\ScoreBoard\Domain\Score;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Score::class)]
final class ScoreTest extends TestCase
{
    public function testInitialScoreIsNilNil(): void
    {
        $score = Score::initial();

        self::assertSame(0, $score->home);
        self::assertSame(0, $score->away);
    }

    public function testKeepsHomeAndAwayGoals(): void
    {
        $score = new Score(10, 2);

        self::assertSame(10, $score->home);
        self::assertSame(2, $score->away);
    }

    public function testTotalIsTheSumOfBothSides(): void
    {
        self::assertSame(12, new Score(10, 2)->total());
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function negativeScores(): iterable
    {
        yield 'home negative' => [-1, 0];
        yield 'away negative' => [0, -1];
        yield 'both negative' => [-3, -2];
    }

    #[DataProvider('negativeScores')]
    public function testRejectsNegativeGoals(int $home, int $away): void
    {
        $this->expectException(InvalidScore::class);

        new Score($home, $away);
    }
}
