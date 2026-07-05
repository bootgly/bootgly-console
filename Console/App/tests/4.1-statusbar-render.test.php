<?php

namespace Console\App;


use function assert;
use function mb_strlen;
use function preg_replace;
use function str_contains;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal;


return new Specification(
   description: 'It should render the status bar with left segments and right-aligned segments',
   test: function () {
      // ! Deterministic terminal width
      $width = Terminal::$width;
      Terminal::$width = 40;

      $Statusbar = new Statusbar;
      $Statusbar->left = ['Snake', 'Score: 3'];
      $Statusbar->right = ['[?] help'];

      // @
      $row = (string) $Statusbar->render();
      $plain = (string) preg_replace('/\x1b\[[0-9;?]*[ -\/]*[@-~]/', '', $row);

      // @ Valid
      yield assert(
         assertion: str_contains($plain, 'Snake  ▏ Score: 3'),
         description: 'Left segments joined with the ▏ separator'
      );
      yield assert(
         assertion: str_contains($plain, '[?] help '),
         description: 'Right segments present'
      );
      yield assert(
         assertion: str_contains($row, "\e["),
         description: 'Row is styled (background wrap)'
      );

      // @ Right side is aligned to the edge (plain width = terminal width)
      yield assert(
         assertion: mb_strlen($plain) === 40,
         description: 'Row fits the terminal width exactly: ' . mb_strlen($plain)
      );

      // @ Restore
      Terminal::$width = $width;
   }
);
