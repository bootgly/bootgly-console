---
name: bootgly-build-console
description: "Build Console platform features in a CLI project of a bootgly.kit, on the Console package: one-shot commands, the Console\\App full-screen shell (screens, keymaps, status bar, toasts, help overlay, palette, CLI UI widgets) and the Console\\Game shell (fixed-timestep loop, Canvas, Keyboard, Scenes, Vector, Zone, Timer, sprites), the headless BOOTGLY_TTY=0 smoke test and tests that drive views without a terminal. Extends bootgly-build. Use when adding or changing a command, screen, keymap, widget, dashboard or game in a projects/<Name>/ CLI project, when switching the CLI scaffold to Console\\App or Console\\Game, or when copying a Console example (Demo/Snake, Demo/Pong, Demo/Invaders). Tables, migrations and models: bootgly-build (references/database.md). Creating or running the project: bootgly-project. Tests: bootgly-test. Not for changing the Console package itself."
---
<!-- Machine-managed by `bootgly kit boot`: it rewrites this skill, and removes it once no longer shipped. Do not edit. -->

# Bootgly build: the Console platform

Commands are written `bootgly …`. Without the global wrapper, run `php ../bootgly …` from `projects/` and `php ../../bootgly …` from `projects/<Name>/` (one more `../` per nested segment).

The rules live in `<kit>/projects/.agents/rules/`; this skill links them as `../../../.agents/rules/<Section>.md`.

This skill extends [bootgly-build](../bootgly-build/SKILL.md): its §1 (the ground), §3 (what every feature
shares) and its Definition of done apply here unchanged. It is shipped by the Console platform package
(`<kit>/Console/`) and laid down by `kit boot` while that package is set up in the kit.

The **CLI** interface gives rise to the **Console** platform. A CLI project is a `<Name>.Project.php` whose `boot` closure runs when you type `bootgly project <Name> start`. The `Console/` package adds two shells: **`Console\App`**, a full-screen TUI with screens, keymaps, a status bar, toasts, a `?` help overlay and a `Ctrl+P` palette, and **`Console\Game`**, which adds a fixed-timestep loop, a diff-rendered `Canvas`, a `Keyboard` and `Scenes`. Pinned source: `<kit>/Console/Console/`, and the widgets in `<kit>/Bootgly/Bootgly/CLI/UI/`.

## 1. Pick the shape

- **One-shot command** (prints and returns): keep the plain scaffold. Its `boot` prints with `CLI->Terminal->Output->render('@#green:text@;@.;')` (`use const Bootgly\CLI;`). Words and options typed after `start` arrive as `boot(array $arguments, array $options)`.
- **Interactive tool or terminal dashboard**: `Console\App` (see 2a). The E2E-tested reference is the Monitor cookbook page.
- **Real-time or animated**: `Console\Game` (see 2b). Shipped references: `projects/Demo/Snake`, `Demo/Pong` and `Demo/Invaders` (sprites). The Breakout cookbook page walks through one. To build on one, copy it (`bootgly projects create MyGame --from=Demo/Snake --yes`) and rename every `Demo\Snake` reference first — the bootgly-project skill, §3.

**Data** (any shape): build `$Database` in `boot` from the `database` scope ([database.md](../bootgly-build/references/database.md) §2) and hand it to your classes.

The scaffold of `projects create Monitor --interfaces=CLI` holds `Monitor.Project.php`, `schedule.php` and `tests/`. Classes live under the project namespace: `projects/Monitor/System.php` is `namespace Monitor;` → `Monitor\System`.

## 2a. App shell

In `Monitor.Project.php`, keep the scaffolded metadata, add `use Console\App;` and replace `boot`:

```php
   boot: function (array $arguments = [], array $options = []): void
   {
      $App = new App;
      // @ Screens: screens/screens.index.php returns ['Overview', …]; one <Name>.php per screen
      $App->Screens->load(__DIR__ . '/screens');
      // @ Global keymaps (boot() adds q quit, ? help, Ctrl+P palette)
      $App->Keymaps->bind('1', 'Overview', fn () => $App->Screens->switch('Overview'));
      $App->Statusbar->left = ['Monitor'];
      $App->Statusbar->right = ['1 Overview', '? help · q quit'];
      $App->boot();
      $App->run('Overview');
   }
```

A screen file returns a view that builds the frame as a string, one line per row. The App trims each line to the terminal width and puts the status bar on the last row. `screens/Overview.php`:

```php
<?php

use Bootgly\API\Component;
use Bootgly\CLI\Terminal\Input\Keystrokes;
use Bootgly\CLI\UI\Components\Charts\Meter;
use Console\App;
use Console\App\Screens\Screen;


return static function (App $App, Screen $Screen): string {
   // ! Screen keymaps: bound once per visit, checked before the global ones
   if ($Screen->Keymaps->list() === []) {
      $Screen->Keymaps->bind(Keystrokes::UP, 'Raise level', function () use ($Screen): void {
         $Screen->state['level'] = min(100, ($Screen->state['level'] ?? 50) + 5);
      });
   }
   $level = $Screen->state['level'] ?? 50;
   $Meter = new Meter($App->Output);
   $Meter->summary = "{$level}%";
   $Meter->value = (float) $level;
   // :
   return implode("\n", ['', (string) $Meter->render(Component::RETURN_OUTPUT), ' ↑ raises the level']);
};
```

- **Shared state.** Subclass the shell (`namespace Monitor; class Monitor extends App`). Give it a `// * Data` property such as `public System $System;`, set after `parent::__construct();`, and type the views as `Monitor $App`. Keep I/O and business rules in plain classes, never in views. Views run up to `$App->FPS` (30) times a second, so throttle expensive reads.
- **Navigation.** `$App->Screens->switch('Name', ['id' => 42])` replaces the current screen. `push()` stacks a screen on top and `pop()` goes back. Popping the last screen ends `run()`. A view reads navigation data from `$Screen->state`.
- **Keys and chrome.** `bind()` takes a character, a `Keystrokes` case (`UP`, `ENTER`, `CTRL_P`…) or an array for a chord (`['g', 'g']`). Every label appears in the `?` overlay and the palette. `$App->Toasts->add('Saved')` shows a toast. `Statusbar->left` and `right` take arrays of strings.
- **Widgets.** `Charts\Meter`, `Charts\Sparkline` (`->series`), `Charts\Bars`, `Markdown` (`->source`) and `Atoms\Dumper` render into a view with `render(Component::RETURN_OUTPUT)`. `Bootgly\CLI\UI\Components\Chart\Gradient` is not a widget: it colours a chart (`$Meter->Gradient = new Gradient(['#00ff00', '#ff0000']);`). `Table` always writes to the terminal, so render tables as a Markdown table. Never run `Select`, `Form` or `Textbox` inside a view, because they run their own read loops. Put actions in keymaps instead.

## 2b. Game shell

`Dodge.php` (namespaced, so import global functions). `update()` and `draw()` are the shell's contract:

```php
<?php

namespace Dodge;


use function max;
use function round;

use Console\Game;
use Console\Game\Scenes\Scene;
use Console\Game\Vector;


class Dodge extends Game
{
   // * Data
   public Vector $Player;


   public function __construct ()
   {
      // ! 40×20 square pixels (aspect 2), capped to the real terminal
      parent::__construct(columns: 40, rows: 20, aspect: 2);
      $this->Player = new Vector($this->Canvas->columns / 2.0, (float) ($this->Canvas->rows - 2));
      $this->Scenes->add(new Scene(
         'Play',
         update: function (float $delta): void {
            // @ 20 cells per second while ← is held (add RIGHT, Menu and Over scenes the same way)
            if ($this->Keyboard->check('LEFT') === true) {
               $this->Player->x -= 20.0 * $delta;
            }
            $this->Player->x = max(0.0, $this->Player->x);
         },
         render: function (): void {
            $this->Canvas->clear();
            $this->Canvas->plot((int) round($this->Player->x), (int) $this->Player->y, '█', "\e[1;32m");
         }
      ));
      $this->Scenes->switch('Play');
   }

   protected function update (float $delta): void
   {
      // ? `q` quits from any scene (the input pump stops after it)
      if ($this->Keyboard->pop('q') === true) {
         $this->Loop->stop();
         return;
      }
      $Scene = $this->Scenes->Current;
      if ($Scene !== null && $Scene->update !== null) {
         ($Scene->update)($delta, $Scene);
      }
   }

   protected function draw (): void
   {
      $Scene = $this->Scenes->Current;
      if ($Scene !== null && $Scene->render !== null) {
         ($Scene->render)($Scene);
      }
      $this->Statusbar->left = ['Dodge', '[q] quit'];
   }
}
```

In `Dodge.Project.php`, add `use Dodge\Dodge;` below the other imports and write `$Dodge = new Dodge; $Dodge->run();` in `boot`. Games do not call `boot()`.

- **Keys.** A key arrives as a token: a `Keystrokes` case name (`LEFT`, `ENTER`, `SPACE`, `ESCAPE`) or the raw character. `pop()` consumes one press. `check()` tells whether a key is held down right now.
- **Canvas.** `plot()` sets one pixel, `draw()` writes a text run and `center()` writes a centered line, each with an optional ANSI style. The shell calls `flush()`, which writes only the cells that changed. Scale gameplay from `Canvas->columns` and `Canvas->rows`.
- **Helpers** — instance methods of `Console\Game\Vector`, `Zone` and `Timer`. `$this->Ball->add($this->Velocity, $delta)` moves a `Vector` in place. `$Zone->contain($Vector)` tests a point and `$Zone->check($Other)` an overlap between two zones. A `Timer` drives a cadence: `$Timer = new Timer(0.5)` (`repeat: false` for a one-shot), then, every tick, `if ($Timer->tick($delta) === true)` — true on the tick it fires. `$this->Loop->tps` sets the tick rate (default 20). `$this->Sprites->load(__DIR__ . '/<Name>.sprites.php')` loads art.

## 3. Run it

- **Humans:** `bootgly project <Name> start` takes over the terminal until `q`, then restores it. Ask the user to run it in their own terminal, not in yours.
- **Agents:** `BOOTGLY_TTY=0 bootgly project <Name> start | head -40` makes an App render one frame, or a Game run one tick and one frame, and then exit. This is your smoke test. `BOOTGLY_TTY=0` forces the single frame: interactivity is read from STDIN, so a stdout pipe alone does not stop a TUI.
- **Long-running** (a plain CLI loop, or an interactive run on a PTY): start it as a background task. Find it with `bootgly project <Name> show` and stop it with `bootgly project <Name> stop [PID]`. **Errors:** `bootgly project <Name> logs` prints the backlog; `-f` follows it and never returns, so run that as a background task too.

## 4. Test it

In `tests/autoboot.php`, replace `'tests/example/'` with your suite directories. Give each suite an `autoboot.php` like `tests/example/autoboot.php` that lists its cases (the bootgly-test skill). Tests create the shells with no terminal: never call `boot()` or `run()`. Call views and public members directly:

```php
<?php

use Bootgly\ACI\Tests\Suite\Test;
use Console\App;


return new Test(
   description: 'Overview renders the level gauge',
   test: function () {
      $App = new App;
      $App->Screens->load(__DIR__ . '/../../screens');
      $Screen = $App->Screens->switch('Overview');
      $frame = ($Screen->View)($App, $Screen);
      yield assert(assertion: str_contains($frame, 'raises the level'), description: 'the view returns the frame');
   }
);
```

For a game, expose the gameplay as public state and methods (Breakout tests `reset()`, `Bricks` and `Velocity`) or move it into plain classes. Run `AI_AGENT=1 bootgly test` from `projects/<Name>/` and read `result` and `failures`.

## Checks

On top of the Definition of done in [bootgly-build](../bootgly-build/SKILL.md):
- [ ] `BOOTGLY_TTY=0 bootgly project <Name> start | head -40` shows the expected frame and exits.
- [ ] You told the user how to run it (`bootgly project <Name> start`) and which keys it has.

## Go deeper

- Cookbook (step by step, tests included): https://docs.bootgly.com/cookbook/console/monitor/overview.md (App) · https://docs.bootgly.com/cookbook/console/breakout/overview.md (Game)
- Platform, widgets, input, tests: https://docs.bootgly.com/guide/console-platform/overview.md · https://docs.bootgly.com/manual/Console/App/overview.md · https://docs.bootgly.com/manual/Console/Game/overview.md · https://docs.bootgly.com/manual/CLI/UI/Components/Charts/overview.md · https://docs.bootgly.com/manual/CLI/UI/Components/Markdown/overview.md · https://docs.bootgly.com/manual/CLI/Terminal/Input/Keystrokes/overview.md · https://docs.bootgly.com/testing/about/testing/overview.md
