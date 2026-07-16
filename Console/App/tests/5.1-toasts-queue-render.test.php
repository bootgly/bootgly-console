<?php

namespace Console\App;


use function array_keys;
use function assert;
use function count;
use function preg_replace;
use function str_contains;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal;
use Bootgly\CLI\Terminal\Output;
use Bootgly\CLI\UI\Components\Alert\Type;
use Bootgly\CLI\UX\Components\Toasts;


return new Specification(
   description: 'It should compose the core Toasts stack as App overlay rows',
   test: function () {
      // ! Deterministic terminal size
      Terminal::$columns = 40;
      Terminal::$lines = 24;

      // @ The core UX Toasts replaces the platform queue — overlay() is the
      // App seam: absolute 1-based rows, pure (no screen bookkeeping)
      $Toasts = new Toasts(new Output('php://memory'));

      yield assert(
         assertion: $Toasts->overlay(at: 10.0) === [],
         description: 'Empty queue overlays no rows'
      );

      // @ Queue with the injected clock
      $Toasts->add('Saved', at: 10.0);
      $Toasts->add('Boom', Type::Failure, TTL: 10.0, at: 10.0);

      $rows = $Toasts->overlay(at: 11.0);
      $strip = static fn (string $row): string
         => (string) preg_replace('/\e\[[0-9;]*m/', '', $row);
      $body = '';
      foreach ($rows as $row) {
         $body .= $strip($row) . "\n";
      }

      yield assert(
         assertion: str_contains($body, 'Saved') && str_contains($body, 'Boom'),
         description: 'Alive toasts compose overlay rows'
      );

      // @ TTL expiry (default 3s: `Saved` dies, `Boom` lives)
      $rows = $Toasts->overlay(at: 14.0);
      $body = '';
      foreach ($rows as $row) {
         $body .= $strip($row) . "\n";
      }

      yield assert(
         assertion: str_contains($body, 'Saved') === false
            && str_contains($body, 'Boom') === true,
         description: 'Expired toasts are dropped from the overlay'
      );

      // @ Limit: only the latest N boxes compose (3 boxes × 3 rows)
      $Toasts->add('1', at: 15.0);
      $Toasts->add('2', at: 15.0);
      $Toasts->add('3', at: 15.0);
      $Toasts->add('4', at: 15.0);
      $rows = $Toasts->overlay(at: 15.5);

      yield assert(
         assertion: count($rows) === 9
            && array_keys($rows)[0] === 1,
         description: 'Overlay is capped at the visible limit: ' . count($rows)
      );
   }
);
