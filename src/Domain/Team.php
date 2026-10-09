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

    /**
     * Lower-cased name used for comparison and for indexing games by team.
     */
    public string $normalizedName;

    public function __construct(string $name)
    {
        $name = mb_trim($name);

        if ($name === '') {
            throw InvalidTeamName::blank();
        }

        $this->name = $name;
        $this->normalizedName = mb_strtolower($name);
    }

    public function equals(self $other): bool
    {
        return $this->normalizedName === $other->normalizedName;
    }
}
