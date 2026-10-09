<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Infrastructure;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;

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
        foreach ([$game->homeTeam, $game->awayTeam] as $team) {
            $occupant = $this->gameIdsByTeam[$team->normalizedName] ?? null;

            if ($occupant !== null && $occupant !== $game->id->value) {
                throw TeamAlreadyPlaying::named($team);
            }
        }

        if (isset($this->games[$game->id->value])) {
            $this->unindex($this->games[$game->id->value]);
        }

        $this->games[$game->id->value] = $game;
        $this->index($game);
    }

    #[\Override]
    public function get(GameId $id): Game
    {
        return $this->games[$id->value] ?? throw GameNotFound::withId($id);
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
        $this->gameIdsByTeam[$game->homeTeam->normalizedName] = $game->id->value;
        $this->gameIdsByTeam[$game->awayTeam->normalizedName] = $game->id->value;
    }

    private function unindex(Game $game): void
    {
        unset($this->gameIdsByTeam[$game->homeTeam->normalizedName], $this->gameIdsByTeam[$game->awayTeam->normalizedName]);
    }
}
