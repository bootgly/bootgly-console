<?php

namespace Console\App;


use function array_shift;
use function assert;
use function count;
use function fopen;
use function json_encode;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;


return new Specification(
   description: 'It should follow a pull source and drain it into the log buffer',
   test: function () {
      // ! Tail with in-memory streams
      $stream = fopen('php://memory', 'r+');
      $Input = new Input($stream); // @phpstan-ignore-line
      $Output = new Output('php://memory');
      $Tail = new Tail($Input, $Output);

      // @ No source: pull is a no-op
      $Tail->pull();
      yield assert(
         assertion: count($Tail->Records) === 0,
         description: 'pull() without a source is a no-op'
      );

      // @ Follow a source with two records, then drained
      $chunks = [
         json_encode(['level' => 200, 'channel' => 'app', 'message' => 'hello', 'timestamp' => 1.0]) . "\n",
         json_encode(['level' => 400, 'channel' => 'app', 'message' => 'boom', 'timestamp' => 2.0]) . "\n",
      ];
      $Tail->follow(function () use (&$chunks): string|false {
         return array_shift($chunks) ?? false;
      });

      $Tail->pull();
      yield assert(
         assertion: count($Tail->Records) === 2,
         description: 'pull() drains the source into records: ' . count($Tail->Records)
      );

      // @ Unbind
      $Tail->follow(null);
      $Tail->pull();
      yield assert(
         assertion: count($Tail->Records) === 2,
         description: 'follow(null) unbinds the source'
      );
   }
);
