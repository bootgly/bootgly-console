<?php

namespace Console\App;


use function assert;
use function mb_strlen;
use function preg_replace;
use function str_contains;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\API\Component;
use Bootgly\CLI\Terminal;
use Bootgly\CLI\Terminal\Output;
use Bootgly\CLI\UI\Atoms\Statusbar;


return new Specification(
   description: 'It should compose the core Statusbar Atom as the App status row',
   test: function () {
      // ! Deterministic terminal width
      $width = Terminal::$width;
      Terminal::$width = 40;

      // @ The core Atom replaces the platform Statusbar (same left/right API)
      $Statusbar = new Statusbar(new Output('php://memory'));
      $Statusbar->decoration = true;
      $Statusbar->left = ['Snake', 'Score: 3'];
      $Statusbar->right = ['[?] help'];

      $row = (string) $Statusbar->render(Component::RETURN_OUTPUT);
      $plain = (string) preg_replace('/\x1b\[[0-9;?]*[ -\/]*[@-~]/', '', $row);

      // @ Valid
      yield assert(
         assertion: str_contains($plain, 'Snake  ▏ Score: 3'),
         description: 'Left segments joined with the ▏ divider'
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
