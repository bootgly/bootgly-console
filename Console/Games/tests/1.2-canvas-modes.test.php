<?php

namespace Console\Games;

use function assert;
use function rewind;
use function str_contains;
use function stream_get_contents;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Output;
use Console\Games\Canvas\Modes;

return new Specification(
   description: 'It should pack pixels into half-block and Braille cells',
   test: function () {
      // ! Half mode: 1 cell = 1×2 pixels
      $Output = new Output('php://memory');
      $Canvas = new Canvas($Output, 4, 4, Modes::Half);

      $Canvas->plot(0, 0); // top only         → ▀ at cell (0,0)
      $Canvas->plot(1, 1); // bottom only      → ▄ at cell (1,0)
      $Canvas->plot(2, 0); // both             → █ at cell (2,0)
      $Canvas->plot(2, 1);
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, '▀') && str_contains($frame, '▄') && str_contains($frame, '█'),
         description: 'Half mode packs top/bottom/full cells'
      );

      // ! Braille mode: 1 cell = 2×4 pixels
      $Output = new Output('php://memory');
      $Canvas = new Canvas($Output, 4, 4, Modes::Braille);

      // @ Dot 1 (top-left) → U+2801
      $Canvas->plot(0, 0);
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, '⠁'),
         description: 'Braille mode packs the top-left dot (U+2801)'
      );

      // @ Full 2×4 cell → U+28FF
      $Output = new Output('php://memory');
      $Canvas = new Canvas($Output, 2, 4, Modes::Braille);
      for ($y = 0; $y < 4; $y++) {
         for ($x = 0; $x < 2; $x++) {
            $Canvas->plot($x, $y);
         }
      }
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, '⣿'),
         description: 'Braille mode packs the full cell (U+28FF)'
      );

      // @ resize() forces a full redraw
      $Canvas->resize(2, 4);
      $Canvas->plot(0, 0);
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, '⠁'),
         description: 'resize() resets the buffers (full redraw)'
      );
   }
);
