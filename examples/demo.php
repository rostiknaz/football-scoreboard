<?php

declare(strict_types=1);

/*
 * Console demo: an afternoon and an evening of World Cup games on the score
 * board. Prints the board as games kick off, goals go in and games finish.
 *
 *     php examples/demo.php
 */

use Nazymko\ScoreBoard\Domain\Exception\ScoreBoardException;
use Nazymko\ScoreBoard\Domain\Game;
use Nazymko\ScoreBoard\Domain\GameId;
use Nazymko\ScoreBoard\Domain\Score;
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\ScoreBoard;

require __DIR__ . '/../vendor/autoload.php';

$board = new ScoreBoard(new InMemoryGameRepository());

$line = static fn(Game $game): string => sprintf('%s %d - %s %d', $game->homeTeam->name, $game->score->home, $game->awayTeam->name, $game->score->away);

$find = static fn(GameId $gameId): Game => array_find($board->summary(), static fn(Game $game): bool => $game->id->equals($gameId))
    ?? throw new LogicException("Game $gameId is not on the board.");

$summary = static function (string $heading) use ($board, $line): void {
    printf("\n%s\n", $heading);

    $games = $board->summary();
    if ($games === []) {
        echo "  (no games in progress)\n";
    }
    foreach ($games as $position => $game) {
        printf("  %d. %s\n", $position + 1, $line($game));
    }
    echo "\n";
};

$kickOff = static function (string $time, string $home, string $away) use ($board, $line, $find): GameId {
    $gameId = $board->startGame($home, $away);
    printf("%s  Kick-off     %s\n", $time, $line($find($gameId)));

    return $gameId;
};

$goal = static function (int $minute, GameId $gameId, string $scorer) use ($board, $line, $find): void {
    $game = $find($gameId);
    $scored = $game->withScore(new Score(
        $game->score->home + ($game->homeTeam->name === $scorer ? 1 : 0),
        $game->score->away + ($game->awayTeam->name === $scorer ? 1 : 0),
    ));
    $board->updateScore($gameId, $scored->score->home, $scored->score->away);
    printf("%6d'  Goal %-9s %s\n", $minute, $scorer, $line($scored));
};

$finalWhistle = static function (string $time, GameId $gameId) use ($board, $line, $find): void {
    $game = $find($gameId);
    $board->finishGame($gameId);
    printf("%s  Full-time    %s\n", $time, $line($game));
};

echo "Afternoon games\n";
$mexicoCanada = $kickOff('15:00', 'Mexico', 'Canada');
$spainBrazil = $kickOff('15:00', 'Spain', 'Brazil');
$summary('Board at kick-off');

$goal(12, $mexicoCanada, 'Canada');
$goal(23, $spainBrazil, 'Spain');
$goal(31, $spainBrazil, 'Spain');
$goal(45, $mexicoCanada, 'Canada');
$summary('Board at half-time (equal totals: the game that kicked off last comes first)');

try {
    $board->startGame('Spain', 'Italy');
} catch (ScoreBoardException $exception) {
    printf("15:50  Start of Spain - Italy refused: %s\n", $exception->getMessage());
}

$goal(58, $spainBrazil, 'Brazil');
$goal(67, $mexicoCanada, 'Mexico');
$goal(88, $spainBrazil, 'Spain');
$summary('Board before the final whistle');

$finalWhistle('16:50', $mexicoCanada);
$finalWhistle('16:52', $spainBrazil);
$summary('Board after the afternoon games');

echo "Evening games\n";
$germanyFrance = $kickOff('18:00', 'Germany', 'France');
$uruguayItaly = $kickOff('18:00', 'Uruguay', 'Italy');
$argentinaAustralia = $kickOff('18:00', 'Argentina', 'Australia');

$goal(9, $uruguayItaly, 'Italy');
$goal(27, $argentinaAustralia, 'Argentina');
$goal(40, $germanyFrance, 'France');
$goal(44, $uruguayItaly, 'Uruguay');
$summary('Board at half-time');

$goal(52, $argentinaAustralia, 'Argentina');
$goal(70, $uruguayItaly, 'Italy');
$goal(79, $germanyFrance, 'Germany');
$goal(90, $argentinaAustralia, 'Australia');
$summary('Board at full-time');

$finalWhistle('19:50', $germanyFrance);
$finalWhistle('19:51', $uruguayItaly);
$finalWhistle('19:53', $argentinaAustralia);
$summary('Board at the end of the day');
