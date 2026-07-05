<?php

namespace Console\App;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Closure;
use InvalidArgumentException;

return new Specification(
   description: 'It should load the screens manifest and resolve screen views',
   test: function () {
      // ! Router with the fixture manifest
      $Router = new Router;
      $Router->load(__DIR__ . '/screens');

      // @ check()
      yield assert(
         assertion: $Router->check('Dashboard') === true && $Router->check('Nope') === false,
         description: 'check() reports routable screens'
      );

      // @ resolve() lazily requires the screen file
      $View = $Router->resolve('Dashboard');
      yield assert(
         assertion: $View instanceof Closure,
         description: 'resolve() returns the view Closure'
      );

      // @ resolve() caches (same instance)
      yield assert(
         assertion: $Router->resolve('Dashboard') === $View,
         description: 'resolve() caches the resolved view'
      );

      // @ Inline route()
      $Router->route('Inline', static fn (): string => 'inline');
      yield assert(
         assertion: $Router->check('Inline') === true,
         description: 'route() registers an inline view'
      );

      // @ Unknown screen throws
      $caught = false;
      try {
         $Router->resolve('Nope');
      }
      catch (InvalidArgumentException) {
         $caught = true;
      }
      yield assert(
         assertion: $caught === true,
         description: 'resolve() throws on an unknown screen'
      );

      // @ Missing manifest throws
      $caught = false;
      try {
         (new Router)->load(__DIR__ . '/missing');
      }
      catch (InvalidArgumentException) {
         $caught = true;
      }
      yield assert(
         assertion: $caught === true,
         description: 'load() throws on a missing manifest'
      );
   }
);
