<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Infrastructure;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;
use Nazymko\ScoreBoard\Domain\Team;

final class InMemoryGameRepository implements GameRepository
{
    /**
     * @var array<string, Game> keyed by game id, in insertion order
     */
    private array $games = [];

    /**
     * @var array<string, string> game id keyed by normalised team name
     */
    private array $gameIdsByTeam = [];

    #[\Override]
    public function save(Game $game): void
    {
        $id = $game->id->value;

        if (isset($this->games[$id])) {
            $this->unindex($this->games[$id]);
        }

        $this->games[$id] = $game;
        $this->index($game);
    }

    #[\Override]
    public function get(GameId $id): Game
    {
        return $this->games[$id->value] ?? throw GameNotFound::withId($id);
    }

    #[\Override]
    public function findByTeam(Team $team): ?Game
    {
        $id = $this->gameIdsByTeam[$team->normalizedName] ?? null;

        return $id === null ? null : $this->games[$id];
    }

    #[\Override]
    public function remove(GameId $id): void
    {
        $game = $this->get($id);

        unset($this->games[$id->value]);
        $this->unindex($game);
    }

    #[\Override]
    public function all(): array
    {
        return array_values($this->games);
    }

    private function index(Game $game): void
    {
        foreach ($game->teams() as $team) {
            $this->gameIdsByTeam[$team->normalizedName] = $game->id->value;
        }
    }

    private function unindex(Game $game): void
    {
        foreach ($game->teams() as $team) {
            if (($this->gameIdsByTeam[$team->normalizedName] ?? null) === $game->id->value) {
                unset($this->gameIdsByTeam[$team->normalizedName]);
            }
        }
    }
}
