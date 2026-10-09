<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain\Exception;

use Nazymko\ScoreBoard\Domain\GameId;

final class GameNotFound extends \RuntimeException implements ScoreBoardException
{
    public static function withId(GameId $id): self
    {
        return new self(sprintf('There is no live game with id "%s".', $id));
    }
}
