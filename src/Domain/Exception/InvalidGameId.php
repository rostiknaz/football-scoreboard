<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain\Exception;

final class InvalidGameId extends \InvalidArgumentException implements ScoreBoardException
{
    public static function malformed(string $value): self
    {
        return new self(sprintf('"%s" is not a valid game id.', $value));
    }
}
