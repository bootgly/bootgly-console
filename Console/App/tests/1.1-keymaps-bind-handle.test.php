<?php

namespace Console\App;

use function assert;
use function count;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Input\Keystrokes;

return new Specification(
   description: 'It should bind and handle single-key bindings',
   test: function () {
      // ! Keymaps
      $Keymaps = new Keymaps;

      $ran = [];
      $Keymaps->bind('q', 'Quit', function () use (&$ran): void {
         $ran[] = 'q';
      });
      $Keymaps->bind(Keystrokes::CTRL_P, 'Command palette', function () use (&$ran): void {
         $ran[] = 'palette';
      });

      // @ Exact match runs the handler
      yield assert(
         assertion: $Keymaps->handle('q') === true && $ran === ['q'],
         description: 'Single key runs its handler'
      );

      // @ Keystrokes enum binding matches its raw value
      yield assert(
         assertion: $Keymaps->handle(Keystrokes::CTRL_P->value) === true && $ran === ['q', 'palette'],
         description: 'Keystrokes binding matches the raw byte sequence'
      );

      // @ Unbound key is not consumed
      yield assert(
         assertion: $Keymaps->handle('x') === false,
         description: 'Unbound key is not consumed'
      );

      // @ Empty key is ignored
      yield assert(
         assertion: $Keymaps->handle('') === false,
         description: 'Empty key is not consumed'
      );

      // @ list() exposes display keys + labels
      $bindings = $Keymaps->list();
      yield assert(
         assertion: count($bindings) === 2
            && $bindings[0]['keys'] === 'q'
            && $bindings[1]['keys'] === 'Ctrl+P'
            && $bindings[1]['label'] === 'Command palette',
         description: 'list() exposes displayable keys and labels'
      );
   }
);
