<?php

namespace Console\App;

use function assert;
use function fopen;
use function rewind;
use function str_contains;
use function stream_get_contents;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal;
use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;
use Console\App;

return new Specification(
   description: 'It should compose frames: screen view + status bar + overlays (help, palette, toasts)',
   test: function () {
      // ! Deterministic terminal size
      $width = Terminal::$width;
      $height = Terminal::$height;
      Terminal::$width = 60;
      Terminal::$height = 12;

      // ! App with in-memory streams (control() exposed for direct dispatch)
      $stream = fopen('php://memory', 'r+');
      $Input = new Input($stream); // @phpstan-ignore-line
      $Output = new Output('php://memory');

      $App = new class ($Input, $Output) extends App {
         public function control (string $key): bool
         {
            return parent::control($key);
         }
      };

      $App->boot();
      $App->Statusbar->left = ['Fixture'];
      $App->Screens->Router->route('Home', static function (App $App, $Screen): string {
         return "Home content\nline two";
      });

      // @ run() on a non-TTY renders a single frame and returns
      $App->run('Home');
      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, "\e[H") && str_contains($frame, "\e[K"),
         description: 'Frame is anchored (\e[H) with per-line clears (\e[K)'
      );
      yield assert(
         assertion: str_contains($frame, 'Home content') && str_contains($frame, 'line two'),
         description: 'Frame contains the screen view content'
      );
      yield assert(
         assertion: str_contains($frame, 'Fixture'),
         description: 'Frame contains the status bar'
      );

      // @ Help overlay replaces the content
      $App->control('?');
      $App->render();
      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);
      yield assert(
         assertion: str_contains($frame, 'Keymaps') && str_contains($frame, 'Quit'),
         description: 'Help overlay lists the keymaps'
      );

      // @ Any key dismisses the help overlay
      $App->control('x');
      yield assert(
         assertion: $App->help === false,
         description: 'Any key dismisses the help overlay'
      );

      // @ Palette captures input while active
      $App->control("\x10"); // Ctrl+P
      yield assert(
         assertion: $App->Palette->active === true && $App->control('h') === true,
         description: 'Ctrl+P opens the palette and it captures input'
      );
      $App->control("\e"); // Esc dismisses

      // @ Toasts — on non-TTY the core stack streams a plain classified line
      // at add() (interactive runs overlay the composed boxes instead)
      $App->Toasts->add('Saved!', TTL: 3600.0);
      $App->render();
      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);
      yield assert(
         assertion: str_contains($frame, 'Saved!'),
         description: 'Toast output reaches the App stream'
      );

      // @ quit() stops the loop flag
      $App->quit();
      yield assert(
         assertion: $App->running === false,
         description: 'quit() stops the main loop'
      );

      // @ Restore
      Terminal::$width = $width;
      Terminal::$height = $height;
   }
);
