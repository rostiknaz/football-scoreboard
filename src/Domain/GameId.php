<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

/**
 * Opaque identifier of a game, handed out by the board when a game starts.
 */
final readonly class GameId implements \Stringable
{
    private function __construct(
        public string $value,
    ) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
