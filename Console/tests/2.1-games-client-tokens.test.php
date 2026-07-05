<?php

namespace Console;


use function array_map;
use function array_shift;
use function assert;
use function fopen;
use function implode;
use function strlen;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;


return new Specification(
   description: 'It should pump keystrokes as newline-framed tokens (chunked, escape-aware)',
   test: function () {
      // ! Game with in-memory streams (client() exposed)
      $stream = fopen('php://memory', 'r+');
      $Input = new Input($stream); // @phpstan-ignore-line
      $Output = new Output('php://memory');

      $Game = new class ($Input, $Output) extends Games {
         public function pump (callable $read, callable $write): void
         {
            $this->client($read, $write);
         }

         protected function update (float $delta): void
         {
         }

         protected function draw (): void
         {
         }
      };

      // ! Scripted terminal reads: coalesced burst, split escape, lone ESC, quit
      $chunks = [
         "\e[A\e[A",   // two UP keystrokes in one chunk
         'a',          // plain character
         "\r",         // Enter as CR (raw mode / xterm)
         "\e[1;5",     // CTRL_UP split across reads...
         'A',          // ...tail arrives
         "\e",         // lone ESC (waits for a tail...)
         '',           // ...idle poll flushes it as ESCAPE
         ' ',          // space → SPACE token
         'q',          // quit → ends the pump
         false,        // never reached
      ];
      $read = function (int $length) use (&$chunks): string|false {
         return array_shift($chunks) ?? false;
      };

      $tokens = [];
      $write = function (string $data) use (&$tokens): int {
         $tokens[] = $data;

         return strlen($data);
      };

      // @
      $Game->pump($read, $write);

      // @ Valid
      yield assert(
         assertion: $tokens === [
            "UP\n", "UP\n", "a\n", "ENTER\n", "CTRL_UP\n", "ESCAPE\n", "SPACE\n", "q\n"
         ],
         description: 'Tokens: ' . implode(' ', array_map('trim', $tokens))
      );
      yield assert(
         assertion: $chunks === [false],
         description: 'Pump ended on the quit token (before the channel closed)'
      );
   }
);
