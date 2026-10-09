<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\TeamCannotPlayItself;

/**
 * A game in progress. Instances are immutable: a score change yields a new
 * instance that keeps the same identity.
 */
final readonly class Game
{
    private function __construct(
        public GameId $id,
        public Team $homeTeam,
        public Team $awayTeam,
        public Score $score,
    ) {}

    /**
     * @throws TeamCannotPlayItself
     */
    public static function start(GameId $id, Team $homeTeam, Team $awayTeam): self
    {
        if ($homeTeam->equals($awayTeam)) {
            throw TeamCannotPlayItself::named($homeTeam);
        }

        return new self($id, $homeTeam, $awayTeam, Score::initial());
    }

    public function withScore(Score $score): self
    {
        return new self($this->id, $this->homeTeam, $this->awayTeam, $score);
    }

    /**
     * @return array{Team, Team} home team first
     */
    public function teams(): array
    {
        return [$this->homeTeam, $this->awayTeam];
    }
}
