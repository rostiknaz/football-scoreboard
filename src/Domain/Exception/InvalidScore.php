<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain\Exception;

final class InvalidScore extends \InvalidArgumentException implements ScoreBoardException
{
    public static function negative(int $home, int $away): self
    {
        return new self(sprintf('Goals must not be negative, got %d - %d.', $home, $away));
    }
}
