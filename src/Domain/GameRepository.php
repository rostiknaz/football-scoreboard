<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;

/**
 * Storage of the games currently in progress.
 */
interface GameRepository
{
    /**
     * Stores a game. A game with the same id is replaced in place, keeping
     * its position in the order games were first saved. A new game is
     * rejected when one of its teams is already in another stored game.
     *
     * @throws TeamAlreadyPlaying
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
