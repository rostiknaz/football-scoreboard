<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;

/**
 * Storage of the games currently in progress.
 */
interface GameRepository
{
    /**
     * Stores a game. A game with the same id is replaced in place, keeping
     * its position in the order games were first saved.
     */
    public function save(Game $game): void;

    /**
     * @throws GameNotFound
     */
    public function get(GameId $id): Game;

    /**
     * @throws GameNotFound
     */
    public function remove(GameId $id): void;

    /**
     * All stored games in the order they were first saved, oldest first.
     *
     * @return list<Game>
     */
    public function all(): array;
}
