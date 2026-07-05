<?php

namespace Console;


use function assert;
use function fopen;
use function ftruncate;
use function rewind;
use function stream_get_contents;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Output;

use Console\Games\Canvas;
use Console\Games\Canvas\Modes;


return new Specification(
   description: 'It should render square pixels (aspect), diff frames and repaint the region after reset',
   test: function () {
      // ! Canvas 4×2 logical pixels, aspect 2 → 8×2 terminal cells
      $stream = fopen('php://memory', 'r+');
      $Output = new Output($stream);
      $Canvas = new Canvas($Output, 4, 2, Modes::Block, aspect: 2);

      $grab = function () use ($stream): string {
         rewind($stream);
         $data = (string) stream_get_contents($stream);
         ftruncate($stream, 0);
         rewind($stream);

         return $data;
      };

      // @ Frame 1: single-char pixel doubles; text runs one char per cell;
      // the first flush paints EVERY cell (blanks included)
      $Canvas->plot(1, 0, '█');
      $Canvas->draw(0, 1, 'AB');
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;1H  ██    \e[2;1HAB      ",
         description: 'First flush paints the full region: pixel doubled, text 1:1'
      );

      // @ Frame 2: identical content → zero writes
      $Canvas->clear();
      $Canvas->plot(1, 0, '█');
      $Canvas->draw(0, 1, 'AB');
      $Canvas->flush();

      yield assert(
         assertion: $grab() === '',
         description: 'Unchanged frame costs zero writes'
      );

      // @ Frame 3: cleared content → blanks overwrite the stale cells
      $Canvas->clear();
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;3H  \e[2;1H  ",
         description: 'Cleared cells are erased with spaces (diff still tracks them)'
      );

      // @ Frame 4: reset() forgets the screen → next flush repaints everything
      $Canvas->reset();
      $Canvas->plot(0, 0, '● ');
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;1H●       \e[2;1H        ",
         description: 'After reset() the whole region repaints — leftovers are wiped'
      );

      // @ Frame 5: center() anchors text in terminal cells (aspect-independent)
      $Canvas->center(0, 'AB');
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;4HAB",
         description: 'center() writes at the terminal-cell midpoint'
      );
   }
);
