<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests;

use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\ScoreBoard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The worked example from the exercise, end to end.
 */
#[CoversClass(ScoreBoard::class)]
final class SummaryExampleTest extends TestCase
{
    public function testSummaryMatchesTheExampleFromTheExercise(): void
    {
        $board = new ScoreBoard(new InMemoryGameRepository());

        foreach ([
            ['Mexico', 'Canada', 0, 5],
            ['Spain', 'Brazil', 10, 2],
            ['Germany', 'France', 2, 2],
            ['Uruguay', 'Italy', 6, 6],
            ['Argentina', 'Australia', 3, 1],
        ] as [$home, $away, $homeGoals, $awayGoals]) {
            $board->updateScore($board->startGame($home, $away), $homeGoals, $awayGoals);
        }

        self::assertSame([
            'Uruguay 6 - Italy 6',
            'Spain 10 - Brazil 2',
            'Mexico 0 - Canada 5',
            'Argentina 3 - Australia 1',
            'Germany 2 - France 2',
        ], array_map(self::line(...), $board->summary()));
    }

    private static function line(Game $game): string
    {
        return sprintf('%s %d - %s %d', $game->homeTeam->name, $game->score->home, $game->awayTeam->name, $game->score->away);
    }
}
