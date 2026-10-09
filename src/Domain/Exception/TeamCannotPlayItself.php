<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain\Exception;

use Nazymko\ScoreBoard\Domain\Team;

final class TeamCannotPlayItself extends \DomainException implements ScoreBoardException
{
    public static function named(Team $team): self
    {
        return new self(sprintf('"%s" cannot play against itself.', $team->name));
    }
}
