<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\InvalidTeamName;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;
use Nazymko\ScoreBoard\Domain\Exception\TeamCannotPlayItself;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\ScoreBoard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        self::assertSame(0, $game->score->home);
        self::assertSame(0, $game->score->away);
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function gamesInvolvingSpain(): iterable
    {
        yield 'as home team' => ['Spain', 'Italy'];
        yield 'as away team' => ['Italy', 'Spain'];
        yield 'spelled differently' => [' SPAIN ', 'Italy'];
    }

    #[DataProvider('gamesInvolvingSpain')]
    public function testATeamCannotBeInTwoLiveGamesAtOnce(string $home, string $away): void
    {
        $this->board->startGame('Spain', 'Brazil');

        $this->expectException(TeamAlreadyPlaying::class);

        $this->board->startGame($home, $away);
    }

    public function testARejectedStartLeavesTheBoardUnchanged(): void
    {
        $this->board->startGame('Spain', 'Brazil');

        try {
            $this->board->startGame('Spain', 'Italy');
            self::fail('Expected the second game to be rejected.');
        } catch (TeamAlreadyPlaying) {
            self::assertSame('Brazil', $this->onlyGame()->awayTeam->name);
        }
    }

    public function testATeamMayPlayAgainOnceItsGameIsFinished(): void
    {
        $id = $this->board->startGame('Spain', 'Brazil');
        $this->board->finishGame($id);

        $this->board->startGame('Spain', 'Italy');

        self::assertSame('Italy', $this->onlyGame()->awayTeam->name);
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

    private function onlyGame(): Game
    {
        $games = $this->board->summary();
        self::assertCount(1, $games);

        return $games[0];
    }
}
