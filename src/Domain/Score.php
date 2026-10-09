<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidScore;

/**
 * The goals scored by the home and the away team.
 */
final readonly class Score
{
    public function __construct(
        public int $home,
        public int $away,
    ) {
        if ($home < 0 || $away < 0) {
            throw InvalidScore::negative($home, $away);
        }
    }

    public static function initial(): self
    {
        return new self(0, 0);
    }

    public function total(): int
    {
        return $this->home + $this->away;
    }
}
