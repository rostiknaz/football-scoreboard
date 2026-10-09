<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Infrastructure;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;

final class InMemoryGameRepository implements GameRepository
{
    /**
     * @var array<string, Game> keyed by game id, in insertion order
     */
    private array $games = [];

    #[\Override]
    public function save(Game $game): void
    {
        $this->games[$game->id->value] = $game;
    }

    #[\Override]
    public function get(GameId $id): Game
    {
        return $this->games[$id->value] ?? throw GameNotFound::withId($id);
    }

    #[\Override]
    public function remove(GameId $id): void
    {
        if (!isset($this->games[$id->value])) {
            throw GameNotFound::withId($id);
        }

        unset($this->games[$id->value]);
    }

    #[\Override]
    public function all(): array
    {
        return array_values($this->games);
    }
}
