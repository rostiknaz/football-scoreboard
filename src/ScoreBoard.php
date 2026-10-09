<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard;

use Nazymko\ScoreBoard\Domain\Exception\GameNotFound;
use Nazymko\ScoreBoard\Domain\Exception\InvalidScore;
use Nazymko\ScoreBoard\Domain\Exception\InvalidTeamName;
use Nazymko\ScoreBoard\Domain\Exception\TeamAlreadyPlaying;
use Nazymko\ScoreBoard\Domain\Exception\TeamCannotPlayItself;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\GameRepository;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Domain\Team;

/**
 * Live score board: the games in progress and their current scores.
 *
 * Raw input is validated here at the boundary and turned into domain
 * objects; games are addressed by the GameId that startGame() hands out.
 */
final readonly class ScoreBoard
{
    public function __construct(
        private GameRepository $games,
    ) {}

    /**
     * Starts a game with an initial score of 0 - 0.
     *
     * @throws InvalidTeamName
     * @throws TeamCannotPlayItself
     * @throws TeamAlreadyPlaying when either team is already in a live game
     */
    public function startGame(string $homeTeam, string $awayTeam): GameId
    {
        $game = Game::start(GameId::generate(), new Team($homeTeam), new Team($awayTeam));

        foreach ($game->teams() as $team) {
            if ($this->games->findByTeam($team) !== null) {
                throw TeamAlreadyPlaying::named($team);
            }
        }

        $this->games->save($game);

        return $game->id;
    }

    /**
     * Replaces the score of a live game with the given absolute values.
     *
     * @throws InvalidScore
     * @throws GameNotFound
     */
    public function updateScore(GameId $gameId, int $homeScore, int $awayScore): void
    {
        $score = new Score($homeScore, $awayScore);
        $game = $this->games->get($gameId);

        $this->games->save($game->withScore($score));
    }

    /**
     * Removes a game from the board.
     *
     * @throws GameNotFound
     */
    public function finishGame(GameId $gameId): void
    {
        $this->games->remove($gameId);
    }

    /**
     * Live games ordered by total score, highest first; games with the same
     * total are ordered by the most recently started first.
     *
     * @return list<Game>
     */
    public function summary(): array
    {
        $games = array_reverse($this->games->all());

        // usort() is stable since PHP 8.0, so equal totals keep the most recent first.
        usort($games, static fn(Game $a, Game $b): int => $b->score->total() <=> $a->score->total());

        return $games;
    }
}
