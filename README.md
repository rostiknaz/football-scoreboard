# Live Football World Cup Score Board

A small PHP 8.4 library that keeps the football games currently in progress
and produces a summary ordered by total score. It is a plain library: no
framework, no HTTP layer, no database. Storage is in memory behind a
repository interface, so a persistent store can be added without touching
the model.

## Getting started

Requirements: PHP 8.4 or newer with the `mbstring` extension, and Composer.

```
composer install
composer check      # coding standard, static analysis and tests
composer test       # tests only
```

`composer check` runs php-cs-fixer (PER-CS 3.0), PHPStan at its maximum
level and PHPUnit. The same command runs in CI on PHP 8.4 and 8.5.

## Usage

```php
use Nazymko\ScoreBoard\Infrastructure\InMemoryGameRepository;
use Nazymko\ScoreBoard\ScoreBoard;

$board = new ScoreBoard(new InMemoryGameRepository());

$gameId = $board->startGame('Mexico', 'Canada');   // starts at 0 - 0
$board->updateScore($gameId, 0, 5);                // absolute values
$games  = $board->summary();                       // list<Game>, ordered
$board->finishGame($gameId);                       // removes the game
```

`summary()` returns immutable `Game` objects, not formatted strings.
Rendering is left to the caller; for the data from the exercise it gives:

```
Uruguay 6 - Italy 6
Spain 10 - Brazil 2
Mexico 0 - Canada 5
Argentina 3 - Australia 1
Germany 2 - France 2
```

`tests/SummaryExampleTest.php` reproduces exactly this example.

## How the code is organised

```
src/
├── ScoreBoard.php                     public entry point: the four operations
├── Domain/                            model, rules and the storage port
│   ├── Game.php                       a game in progress: identity, teams, score
│   ├── GameId.php                     opaque identifier handed out by the board
│   ├── Team.php                       validated team name
│   ├── Score.php                      validated pair of goals
│   ├── GameRepository.php             port: how live games are stored
│   └── Exception/                     one exception per rule that can be violated
└── Infrastructure/
    └── InMemoryGameRepository.php     adapter: in-memory implementation of the port

tests/
├── ScoreBoardTest.php                 behaviour of the public API
├── SummaryExampleTest.php             the worked example from the exercise
├── Domain/                            one test per domain object
│   └── GameRepositoryContract.php     abstract test every repository adapter must pass
└── Infrastructure/
    └── InMemoryGameRepositoryTest.php runs the contract against the adapter
```

Suggested reading order: `ScoreBoard.php`, then `Domain/Game.php` and the
value objects, then `GameRepository.php` with its in-memory adapter, then
the tests.

* `ScoreBoard` is the only class a client talks to. It accepts raw input
  (strings and integers) at the boundary, turns it into domain objects and
  orchestrates the repository.
* `Domain` holds the rules. The value objects are always valid: they throw
  on construction if the input is unacceptable, so nothing downstream
  re-validates. `Game` owns the rules about a single game.
* `GameRepository` is the seam between the model and storage. It also
  carries the one rule that spans several games (a team is in at most one
  live game), because only the store can guarantee it atomically.

Dependencies point inwards only: `Infrastructure` and `ScoreBoard` depend
on `Domain`; `Domain` depends on nothing.

## Design decisions

### Games are identified by a generated `GameId`, not by the team pair

`startGame()` returns a `GameId`; `updateScore()` and `finishGame()` take
it. Identifying a game by `(home, away)` reads closer to the exercise text,
but ties identity to descriptive data: swapping the two arguments by mistake
would silently address a different game, and a real data feed carries an id
anyway. The property a composite key gives for free, one live game per team,
is enforced explicitly instead.

The id is 128 random bits rendered as 32 hex characters; no UUID library is
needed for an opaque handle. `GameId::fromString()` rebuilds an id after a
round trip through a URL or a database row and rejects anything else.

### `Game` is immutable

`Game` is a `readonly` class; updating the score produces a new instance
(`withScore()`) that the board saves back. The summary can therefore hand
out domain objects directly: nothing can change a score behind the board's
back, and no snapshot or DTO layer is needed. The cost is one `save()` per
update, which a persistent store would need anyway.

### Summary ordering

Games are sorted by total score, highest first; games with the same total
are ordered by the most recently started first. "Most recently added" is
implemented as insertion order, which the repository contract guarantees
(`all()` returns games oldest first). The summary reverses that list and
applies a stable sort by total (PHP sorts are stable since 8.0), so ties keep
the most recent first. Updating a score does not change a game's position.

Alternatives considered: a `startedAt` timestamp from a clock abstraction
(timestamps collide and are not monotonic) or a sequence number stored on
`Game` (a storage concern leaking into the model).

### One live game per team is guaranteed by the repository

`GameRepository::save()` rejects a new game whose team is already in another
stored game. A check in `ScoreBoard` before saving would be check-then-act
and would race once the store is shared between processes. The rule is still
declared in the domain, through the port's contract and the
`TeamAlreadyPlaying` exception, and the contract test pins it for every
adapter. In memory it is a hash index keyed by the normalised team name
(O(1)); in a database it would be a unique index.

### Errors are exceptions, one class per rule

Every exception implements the `ScoreBoardException` marker interface, so a
caller can catch everything from the library at once or react to one rule.
They extend the SPL exception that fits: `InvalidArgumentException` for
malformed input, `DomainException` for a rule the input itself breaks, and
`RuntimeException` for refusals that depend on current state (a missing
game, a team that is already playing).

### Deliberately not included

* No DI container, event dispatcher or framework:
  `new ScoreBoard(new InMemoryGameRepository())` is the whole wiring.
* No `Summary` wrapper type: a `list<Game>` is sufficient today.
* No history of finished games: the exercise says finishing removes the game.
* No concurrency control: one board per process, single thread.

## Rules and where they are enforced

| Rule | Enforced by |
|---|---|
| Team name must not be blank after trimming | `Team` constructor |
| Scores must be non-negative integers | `Score` constructor |
| A game id must have the generated format | `GameId` constructor |
| A team cannot play against itself | `Game::start()` |
| A team can be in only one live game at a time | `GameRepository::save()` |
| Updating or finishing an unknown game fails | `GameRepository::get()` and `remove()` |

## Assumptions where the exercise is silent

1. "Game" and "match" mean the same thing; the class is `Game` because
   `match` is a reserved word in PHP 8.
2. A team is identified by its name. Names are trimmed and compared
   case-insensitively (`spain` and `Spain` are the same team); the original
   spelling is kept for display.
3. A team takes part in at most one live game at a time.
4. The home and away teams of a game must differ.
5. A score update carries absolute values, not increments. The latest update
   wins and a score may go down: the board mirrors its data source, and a
   disallowed goal is a legitimate correction. Updating to the current score
   is a harmless no-op.
6. Scores are non-negative integers with no domain upper bound.
7. "Most recently added to our system" means the order in which games were
   started on this board, not kick-off time.
8. Operating on a game that does not exist, or has already been finished,
   throws `GameNotFound`. Ignoring it silently would hide client bugs.
9. The board is used from a single process; the in-memory store is not
   shared across requests or threads.
10. An empty board produces an empty summary.

## Edge cases

| Call | Result |
|---|---|
| `startGame('', 'Brazil')` or `startGame('   ', 'Brazil')` | `InvalidTeamName` |
| `startGame('Spain', ' spain ')` | `TeamCannotPlayItself` |
| `startGame('Spain', 'Brazil')` while Spain is already playing | `TeamAlreadyPlaying` |
| `updateScore($id, -1, 0)` | `InvalidScore` |
| `updateScore($id, 2, 1)` then `updateScore($id, 1, 1)` | allowed, score is 1 - 1 |
| `updateScore($id, ...)` after `finishGame($id)` | `GameNotFound` |
| `finishGame($id)` twice | second call throws `GameNotFound` |
| `GameId::fromString('not-an-id')` | `InvalidGameId` |
| `summary()` with equal totals | most recently started game first |
| `summary()` on an empty board | `[]` |

## Tests

* One unit test per value object and for `Game`, covering the rules above.
* `GameRepositoryContract` is an abstract test that any repository adapter
  extends; the in-memory adapter is its only implementation here.
* `ScoreBoard` is tested through its public API with the real in-memory
  repository. There are no mocks: the collaborator is a pure in-memory
  object, and mocking it would couple the tests to the implementation.
* `SummaryExampleTest` is the acceptance test for the exercise's example.

Run a single file with `vendor/bin/phpunit tests/ScoreBoardTest.php`.
