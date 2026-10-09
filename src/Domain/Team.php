<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Domain;

use Nazymko\ScoreBoard\Domain\Exception\InvalidTeamName;

/**
 * A team identified by its name: surrounding whitespace is ignored and names
 * are compared case-insensitively, while the given spelling is kept.
 */
final readonly class Team
{
    public string $name;

    public function __construct(string $name)
    {
        $name = mb_trim($name);

        if ($name === '') {
            throw InvalidTeamName::blank();
        }

        $this->name = $name;
    }

    public function equals(self $other): bool
    {
        return mb_strtolower($this->name) === mb_strtolower($other->name);
    }
}
