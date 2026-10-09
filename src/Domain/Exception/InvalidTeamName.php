<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain\Exception;

final class InvalidTeamName extends \InvalidArgumentException implements ScoreBoardException
{
    public static function blank(): self
    {
        return new self('A team name must not be blank.');
    }
}
