<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Domain\Team;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Behaviour every GameRepository implementation has to provide.
 */
abstract class GameRepositoryContract extends TestCase
{
    abstract protected function createRepository(): GameRepository;

    public function testStoredGameCanBeRetrievedById(): void
    {
        $repository = $this->createRepository();
        $game = $this->game('Mexico', 'Canada');

        $repository->save($game);

        self::assertEquals($game, $repository->get($game->id));
    }

    public function testRetrievingAnUnknownGameFails(): void
    {
        $this->expectException(GameNotFound::class);

        $this->createRepository()->get(GameId::generate());
    }

    public function testRemovedGameIsGone(): void
    {
        $repository = $this->createRepository();
        $game = $this->game('Mexico', 'Canada');
        $repository->save($game);

        $repository->remove($game->id);

        self::assertSame([], $repository->all());
    }

    public function testRemovingAnUnknownGameFails(): void
    {
        $this->expectException(GameNotFound::class);

        $this->createRepository()->remove(GameId::generate());
    }

    public function testEmptyRepositoryListsNothing(): void
    {
        self::assertSame([], $this->createRepository()->all());
    }

    public function testListsGamesInTheOrderTheyWereFirstSaved(): void
    {
        $repository = $this->createRepository();
        $first = $this->game('Mexico', 'Canada');
        $second = $this->game('Spain', 'Brazil');
        $third = $this->game('Germany', 'France');

        $repository->save($first);
        $repository->save($second);
        $repository->save($third);

        self::assertEquals([$first, $second, $third], $repository->all());
    }

    public function testSavingAnExistingGameReplacesItWithoutChangingItsPosition(): void
    {
        $repository = $this->createRepository();
        $first = $this->game('Mexico', 'Canada');
        $second = $this->game('Spain', 'Brazil');
        $repository->save($first);
        $repository->save($second);

        $updated = $first->withScore(new Score(0, 5));
        $repository->save($updated);

        self::assertEquals([$updated, $second], $repository->all());
        self::assertEquals($updated, $repository->get($first->id));
    }

    public function testRemovedGameCanNoLongerBeRetrieved(): void
    {
        $repository = $this->createRepository();
        $game = $this->game('Mexico', 'Canada');
        $repository->save($game);
        $repository->remove($game->id);

        $this->expectException(GameNotFound::class);

        $repository->get($game->id);
    }

    public function testRemovingAGameKeepsTheOrderOfTheOthers(): void
    {
        $repository = $this->createRepository();
        $first = $this->game('Mexico', 'Canada');
        $second = $this->game('Spain', 'Brazil');
        $third = $this->game('Germany', 'France');
        $repository->save($first);
        $repository->save($second);
        $repository->save($third);

        $repository->remove($second->id);

        self::assertEquals([$first, $third], $repository->all());
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
    public function testRejectsANewGameInvolvingATeamThatIsAlreadyPlaying(string $home, string $away): void
    {
        $repository = $this->createRepository();
        $repository->save($this->game('Spain', 'Brazil'));

        $this->expectException(TeamAlreadyPlaying::class);

        $repository->save($this->game($home, $away));
    }

    public function testARejectedGameIsNotStored(): void
    {
        $repository = $this->createRepository();
        $spainBrazil = $this->game('Spain', 'Brazil');
        $repository->save($spainBrazil);

        try {
            $repository->save($this->game('Spain', 'Italy'));
            self::fail('Expected the second game to be rejected.');
        } catch (TeamAlreadyPlaying) {
            self::assertEquals([$spainBrazil], $repository->all());
        }
    }

    public function testReSavingAGameDoesNotConflictWithItself(): void
    {
        $repository = $this->createRepository();
        $game = $this->game('Spain', 'Brazil');
        $repository->save($game);

        $repository->save($game->withScore(new Score(1, 0)));

        self::assertCount(1, $repository->all());
    }

    public function testATeamIsFreeAgainOnceItsGameIsRemoved(): void
    {
        $repository = $this->createRepository();
        $spainBrazil = $this->game('Spain', 'Brazil');
        $repository->save($spainBrazil);
        $repository->remove($spainBrazil->id);

        $spainItaly = $this->game('Spain', 'Italy');
        $repository->save($spainItaly);

        self::assertEquals([$spainItaly], $repository->all());
    }

    private function game(string $home, string $away): Game
    {
        return Game::start(GameId::generate(), new Team($home), new Team($away));
    }
}
