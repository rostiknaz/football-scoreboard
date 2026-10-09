<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\TeamCannotPlayItself;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Domain\Team;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Game::class)]
final class GameTest extends TestCase
{
    public function testStartsWithNilNilScore(): void
    {
        $game = Game::start(GameId::generate(), new Team('Mexico'), new Team('Canada'));

        self::assertSame('Mexico', $game->homeTeam->name);
        self::assertSame('Canada', $game->awayTeam->name);
        self::assertEquals(Score::initial(), $game->score);
    }

    public function testKeepsTheGivenId(): void
    {
        $id = GameId::generate();

        self::assertTrue(Game::start($id, new Team('Mexico'), new Team('Canada'))->id->equals($id));
    }

    public function testCannotBeStartedWithTheSameTeamOnBothSides(): void
    {
        $this->expectException(TeamCannotPlayItself::class);

        Game::start(GameId::generate(), new Team('Spain'), new Team(' spain '));
    }

    public function testChangingTheScoreYieldsANewGameWithTheSameIdentity(): void
    {
        $game = Game::start(GameId::generate(), new Team('Spain'), new Team('Brazil'));

        $updated = $game->withScore(new Score(10, 2));

        self::assertTrue($updated->id->equals($game->id));
        self::assertEquals(new Score(10, 2), $updated->score);
        self::assertEquals(Score::initial(), $game->score, 'the original game is left untouched');
    }

    public function testListsItsTeamsHomeFirst(): void
    {
        $game = Game::start(GameId::generate(), new Team('Germany'), new Team('France'));

        self::assertEquals([new Team('Germany'), new Team('France')], $game->teams());
    }
}
