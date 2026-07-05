<?php

namespace Console\Games;


use function assert;
use function rewind;
use function str_contains;
use function stream_get_contents;
use function strlen;
use function substr;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Output;


return new Specification(
   description: 'It should diff-render frames: full first flush, zero writes when unchanged, one run per change',
   test: function () {
      // ! Canvas 6×3 (Block) with an in-memory Output
      $Output = new Output('php://memory');
      $Canvas = new Canvas($Output, 6, 3);

      // @ First flush writes the painted cells
      $Canvas->draw(0, 0, 'ABC');
      $Canvas->plot(5, 2, 'Z');
      $Canvas->flush();

      rewind($Output->stream);
      $first = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($first, 'ABC') && str_contains($first, 'Z'),
         description: 'First flush writes the painted cells'
      );
      // The first flush paints the WHOLE region (front buffer starts unknown),
      // so every row is one full run anchored at column 1
      yield assert(
         assertion: str_contains($first, "\e[1;1H") && str_contains($first, "\e[3;1H"),
         description: 'Runs are addressed with anchored cursor moves'
      );

      // @ Unchanged frame writes nothing
      $size = strlen($first);
      $Canvas->clear();
      $Canvas->draw(0, 0, 'ABC');
      $Canvas->plot(5, 2, 'Z');
      $Canvas->flush();

      rewind($Output->stream);
      $unchanged = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: strlen($unchanged) === $size,
         description: 'Unchanged frame costs zero writes'
      );

      // @ Single-cell change emits a single run
      $Canvas->clear();
      $Canvas->draw(0, 0, 'ABC');
      $Canvas->plot(5, 2, 'X'); // Z → X
      $Canvas->flush();

      rewind($Output->stream);
      $delta = substr($unchanged = (string) stream_get_contents($Output->stream), $size);

      yield assert(
         assertion: $delta === "\e[3;6HX",
         description: 'Single-cell change emits exactly one addressed run'
      );

      // @ Erasing a cell writes a space over it
      $Canvas->clear();
      $Canvas->draw(0, 0, 'ABC'); // Z/X pixel gone
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: substr($frame, -strlen("\e[3;6H ")) === "\e[3;6H ",
         description: 'A cleared cell is repainted with a space'
      );

      // @ Anchor offsets the addressing
      $Canvas->reset();
      $Canvas->row = 5;
      $Canvas->column = 10;
      $Canvas->plot(0, 0, '#');
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, "\e[5;10H#"),
         description: 'Anchor row/column offset the cursor addressing'
      );

      // @ Styled cells wrap with the style and reset
      $Canvas->reset();
      $Canvas->row = 1;
      $Canvas->column = 1;
      $Canvas->plot(1, 1, 'S', "\e[32m");
      $Canvas->flush();

      rewind($Output->stream);
      $frame = (string) stream_get_contents($Output->stream);

      yield assert(
         assertion: str_contains($frame, "\e[32mS\e[0m"),
         description: 'Styled cells carry their style + reset'
      );
   }
);
