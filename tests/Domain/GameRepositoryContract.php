<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Domain;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Domain\Team;
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

    private function game(string $home, string $away): Game
    {
        return Game::start(GameId::generate(), new Team($home), new Team($away));
    }
}
