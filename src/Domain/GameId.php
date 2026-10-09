<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidGameId;

/**
 * Opaque identifier of a game, handed out by the board when a game starts.
 */
final readonly class GameId implements \Stringable
{
    private function __construct(
        public string $value,
    ) {
        if (preg_match('/^[0-9a-f]{32}$/', $value) !== 1) {
            throw InvalidGameId::malformed($value);
        }
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    /**
     * Rebuilds an id from the string form produced by __toString().
     *
     * @throws InvalidGameId
     */
    public static function fromString(string $value): self
    {
        return new self($value);
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
