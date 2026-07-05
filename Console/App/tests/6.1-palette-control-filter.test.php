<?php

namespace Console\App;

use function assert;
use function count;
use function str_contains;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Input\Keystrokes;

return new Specification(
   description: 'It should filter bindings by query and run the selected action',
   test: function () {
      // ! Keymaps + Palette
      $Keymaps = new Keymaps;

      $ran = [];
      $Keymaps->bind('s', 'Save file', function () use (&$ran): void {
         $ran[] = 'save';
      });
      $Keymaps->bind('o', 'Open file', function () use (&$ran): void {
         $ran[] = 'open';
      });
      $Keymaps->bind('q', 'Quit', function () use (&$ran): void {
         $ran[] = 'quit';
      });

      $Palette = new Palette($Keymaps);

      // @ Toggle opens
      $Palette->toggle();
      yield assert(
         assertion: $Palette->active === true,
         description: 'toggle() activates the palette'
      );

      // @ Empty query lists all bindings
      yield assert(
         assertion: count($Palette->filter()) === 3,
         description: 'Empty query lists every binding'
      );

      // @ Typing filters by label (case-insensitive)
      $Palette->control('f');
      $Palette->control('i');
      $Palette->control('l');
      yield assert(
         assertion: $Palette->query === 'fil' && count($Palette->filter()) === 2,
         description: 'Query filters bindings by label'
      );

      // @ Selection + Enter runs the action and closes
      $Palette->control(Keystrokes::DOWN->value);
      $Palette->control(Keystrokes::ENTER->value);
      yield assert(
         assertion: $ran === ['open'] && $Palette->active === false,
         description: 'Enter runs the selected action and dismisses'
      );

      // @ Esc dismisses without running
      $Palette->toggle();
      $Palette->control(Keystrokes::ESCAPE->value);
      yield assert(
         assertion: $Palette->active === false && count($ran) === 1,
         description: 'Esc dismisses without running an action'
      );

      // @ Render exposes query + entries
      $Palette->toggle();
      $Palette->control('q');
      $rendered = (string) $Palette->render();
      yield assert(
         assertion: str_contains($rendered, '⌘ q') && str_contains($rendered, 'Quit'),
         description: 'Render shows the query prompt and matching entries'
      );

      // @ Query editing is a full Line editor (cursor movement + kill keys)
      $Palette->control(Keystrokes::LEFT->value);
      $Palette->control('s');
      yield assert(
         assertion: $Palette->query === 'sq',
         description: 'Left arrow moves the query cursor (insert before): ' . $Palette->query
      );

      $Palette->control(Keystrokes::CTRL_U->value);
      yield assert(
         assertion: $Palette->query === 'q',
         description: 'Ctrl+U kills to the start of the query: ' . $Palette->query
      );

      $Palette->control(Keystrokes::END->value);
      $Palette->control(Keystrokes::BACKSPACE->value);
      yield assert(
         assertion: $Palette->query === '',
         description: 'Backspace erases the query (after End)'
      );
   }
);
