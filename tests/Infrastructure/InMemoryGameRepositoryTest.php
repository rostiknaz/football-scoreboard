<?php

declare(strict_types=1);

namespace Nazymko\ScoreBoard\Tests\Infrastructure;

use Nazymko\ScoreBoard\Domain\GameRepository;
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\Tests\Domain\GameRepositoryContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryGameRepository::class)]
final class InMemoryGameRepositoryTest extends GameRepositoryContract
{
    #[\Override]
    protected function createRepository(): GameRepository
    {
        return new InMemoryGameRepository();
    }
}
