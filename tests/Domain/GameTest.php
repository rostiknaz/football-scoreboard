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
        self::assertSame(0, $game->score->home);
        self::assertSame(0, $game->score->away);
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
        self::assertSame(10, $updated->score->home);
        self::assertSame(2, $updated->score->away);
        self::assertSame(0, $game->score->total(), 'the original game is left untouched');
    }

    public function testKnowsWhichTeamsAreInvolved(): void
    {
        $game = Game::start(GameId::generate(), new Team('Germany'), new Team('France'));

        self::assertTrue($game->involves(new Team('germany')));
        self::assertTrue($game->involves(new Team('France')));
        self::assertFalse($game->involves(new Team('Italy')));
    }
}
