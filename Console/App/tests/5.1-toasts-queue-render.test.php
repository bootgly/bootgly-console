<?php

namespace Console\App;

use function assert;
use function count;
use function explode;
use function str_contains;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal;

return new Specification(
   description: 'It should queue toasts, expire them by TTL and render right-aligned rows',
   test: function () {
      // ! Deterministic terminal width
      $width = Terminal::$width;
      Terminal::$width = 40;

      $Toasts = new Toasts;

      // @ Empty queue renders nothing
      yield assert(
         assertion: $Toasts->render() === '',
         description: 'Empty queue renders an empty string'
      );

      // @ Queue with injected clock
      $Toasts->add('Saved', at: 10.0);
      $Toasts->add('Boom', level: 'error', ttl: 10.0, at: 10.0);

      $Toasts->expire(at: 11.0);
      $rendered = (string) $Toasts->render();
      yield assert(
         assertion: str_contains($rendered, 'Saved') && str_contains($rendered, 'Boom'),
         description: 'Alive toasts render'
      );

      // @ TTL expiry (default 3s: `Saved` dies, `Boom` lives)
      $Toasts->expire(at: 14.0);
      $rendered = (string) $Toasts->render();
      yield assert(
         assertion: str_contains($rendered, 'Saved') === false && str_contains($rendered, 'Boom') === true,
         description: 'Expired toasts are dropped'
      );

      // @ Limit: only the latest N render
      $Toasts->add('1', at: 15.0);
      $Toasts->add('2', at: 15.0);
      $Toasts->add('3', at: 15.0);
      $Toasts->add('4', at: 15.0);
      $lines = explode("\n", (string) $Toasts->render());
      yield assert(
         assertion: count($lines) === 3,
         description: 'Render is capped at the visible limit: ' . count($lines)
      );

      // @ Restore
      Terminal::$width = $width;
   }
);
