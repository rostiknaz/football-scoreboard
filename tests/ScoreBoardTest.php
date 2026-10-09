<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\InvalidScore;
use Nazymko\ScoreBoard\Domain\Exception\InvalidTeamName;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;
use Nazymko\ScoreBoard\Domain\Exception\TeamCannotPlayItself;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\ScoreBoard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScoreBoard::class)]
final class ScoreBoardTest extends TestCase
{
    private ScoreBoard $board;

    #[\Override]
    protected function setUp(): void
    {
        $this->board = new ScoreBoard(new InMemoryGameRepository());
    }

    public function testStartedGameIsOnTheBoardWithNilNilScore(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');

        $game = $this->onlyGame();
        self::assertTrue($game->id->equals($id));
        self::assertSame('Mexico', $game->homeTeam->name);
        self::assertSame('Canada', $game->awayTeam->name);
        self::assertEquals(Score::initial(), $game->score);
    }

    public function testEachStartedGameGetsItsOwnId(): void
    {
        $first = $this->board->startGame('Mexico', 'Canada');
        $second = $this->board->startGame('Spain', 'Brazil');

        self::assertFalse($first->equals($second));
        self::assertCount(2, $this->board->summary());
    }

    public function testRejectsBlankTeamNames(): void
    {
        $this->expectException(InvalidTeamName::class);

        $this->board->startGame('Mexico', '   ');
    }

    public function testATeamCannotPlayAgainstItself(): void
    {
        $this->expectException(TeamCannotPlayItself::class);

        $this->board->startGame('Spain', 'spain');
    }

    public function testATeamCannotBeInTwoLiveGamesAtOnce(): void
    {
        $this->board->startGame('Spain', 'Brazil');

        $this->expectException(TeamAlreadyPlaying::class);

        $this->board->startGame('Spain', 'Italy');
    }

    public function testFinishedGameIsRemovedFromTheBoard(): void
    {
        $mexicoCanada = $this->board->startGame('Mexico', 'Canada');
        $this->board->startGame('Spain', 'Brazil');

        $this->board->finishGame($mexicoCanada);

        self::assertSame('Spain', $this->onlyGame()->homeTeam->name);
    }

    public function testFinishingAnUnknownGameFails(): void
    {
        $this->expectException(GameNotFound::class);

        $this->board->finishGame(GameId::generate());
    }

    public function testFinishingAGameTwiceFails(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');
        $this->board->finishGame($id);

        $this->expectException(GameNotFound::class);

        $this->board->finishGame($id);
    }

    public function testUpdatesTheScoreOfALiveGame(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');

        $this->board->updateScore($id, 0, 5);

        self::assertEquals(new Score(0, 5), $this->onlyGame()->score);
    }

    public function testUpdatesOnlyTheAddressedGame(): void
    {
        $mexicoCanada = $this->board->startGame('Mexico', 'Canada');
        $spainBrazil = $this->board->startGame('Spain', 'Brazil');

        $this->board->updateScore($spainBrazil, 10, 2);

        self::assertSame(0, $this->game($mexicoCanada)->score->total());
        self::assertSame(12, $this->game($spainBrazil)->score->total());
    }

    public function testScoreMayBeCorrectedDownwards(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');
        $this->board->updateScore($id, 2, 1);

        $this->board->updateScore($id, 1, 1);

        self::assertEquals(new Score(1, 1), $this->onlyGame()->score);
    }

    public function testUpdatingToTheCurrentScoreChangesNothing(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');
        $this->board->updateScore($id, 3, 1);

        $this->board->updateScore($id, 3, 1);

        self::assertEquals(new Score(3, 1), $this->onlyGame()->score);
    }

    public function testRejectsNegativeScores(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');

        $this->expectException(InvalidScore::class);

        $this->board->updateScore($id, -1, 0);
    }

    public function testUpdatingAnUnknownGameFails(): void
    {
        $this->expectException(GameNotFound::class);

        $this->board->updateScore(GameId::generate(), 1, 0);
    }

    public function testUpdatingAFinishedGameFails(): void
    {
        $id = $this->board->startGame('Mexico', 'Canada');
        $this->board->finishGame($id);

        $this->expectException(GameNotFound::class);

        $this->board->updateScore($id, 1, 0);
    }

    public function testEmptyBoardHasAnEmptySummary(): void
    {
        self::assertSame([], $this->board->summary());
    }

    public function testSummaryOrdersGamesByTotalScoreHighestFirst(): void
    {
        $this->board->updateScore($this->board->startGame('Mexico', 'Canada'), 0, 5);
        $this->board->updateScore($this->board->startGame('Spain', 'Brazil'), 10, 2);
        $this->board->updateScore($this->board->startGame('Germany', 'France'), 2, 2);

        self::assertSame(['Spain', 'Mexico', 'Germany'], $this->homeTeamsInSummary());
    }

    public function testGamesWithTheSameTotalAreOrderedMostRecentlyStartedFirst(): void
    {
        $this->board->updateScore($this->board->startGame('Germany', 'France'), 2, 2);
        $this->board->updateScore($this->board->startGame('Argentina', 'Australia'), 3, 1);

        self::assertSame(['Argentina', 'Germany'], $this->homeTeamsInSummary());
    }

    public function testUpdatingAScoreDoesNotMakeAGameMoreRecent(): void
    {
        $germanyFrance = $this->board->startGame('Germany', 'France');
        $argentinaAustralia = $this->board->startGame('Argentina', 'Australia');

        $this->board->updateScore($argentinaAustralia, 2, 0);
        $this->board->updateScore($germanyFrance, 1, 1);

        self::assertSame(['Argentina', 'Germany'], $this->homeTeamsInSummary());
    }

    public function testFinishingAGameKeepsTheOrderOfTheOthers(): void
    {
        $this->board->startGame('Mexico', 'Canada');
        $spainBrazil = $this->board->startGame('Spain', 'Brazil');
        $this->board->startGame('Germany', 'France');

        $this->board->finishGame($spainBrazil);

        self::assertSame(['Germany', 'Mexico'], $this->homeTeamsInSummary());
    }

    /**
     * @return list<string>
     */
    private function homeTeamsInSummary(): array
    {
        return array_map(static fn(Game $game): string => $game->homeTeam->name, $this->board->summary());
    }

    private function game(GameId $id): Game
    {
        return array_find($this->board->summary(), static fn(Game $game): bool => $game->id->equals($id))
            ?? self::fail(sprintf('Game %s is not on the board.', $id));
    }

    private function onlyGame(): Game
    {
        $games = $this->board->summary();
        self::assertCount(1, $games);

        return $games[0];
    }
}
