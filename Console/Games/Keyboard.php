<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games;


use function microtime;


/**
 * Keyboard state with pressed / held heuristics.
 *
 * Terminals report no key-up: a held key arrives as auto-repeats — one byte,
 * a ~250–500 ms initial delay, then repeats every ~30–50 ms. A key is
 * considered *held* while its repeats keep arriving inside a window: the
 * `grace` window right after the first press (covering the initial delay),
 * then the tighter `window` between subsequent repeats.
 *
 * `pop()` consumes edge-triggered presses (one event per keystroke);
 * `check()` answers "is it held right now?".
 */
class Keyboard
{
   // * Config
   /** Seconds from the first press to the first auto-repeat */
   public float $grace = 0.6;
   /** Seconds between auto-repeats once repeating */
   public float $window = 0.15;

   // * Data
   /** @var array<string,array{first: float, last: float, repeats: int}> */
   protected array $keys = [];
   /** @var array<string,int> Edge-triggered press queue (consumed by pop) */
   protected array $pressed = [];

   // * Metadata
   // ...


   /**
    * Feed a keystroke token (a raw press or an auto-repeat).
    *
    * @param string $key The keystroke token (e.g. `UP`, `q`).
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    */
   public function press (string $key, null|float $at = null): void
   {
      // !
      $now = $at ?? microtime(true);

      // @ Track hold state
      if (isSet($this->keys[$key]) === true) {
         $this->keys[$key]['last'] = $now;
         $this->keys[$key]['repeats']++;
      }
      else {
         $this->keys[$key] = [
            'first' => $now,
            'last' => $now,
            'repeats' => 1
         ];
      }

      // @ Queue the edge-triggered press
      $this->pressed[$key] = ($this->pressed[$key] ?? 0) + 1;
   }

   /**
    * Check whether a key is currently held.
    *
    * @param string $key The keystroke token.
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    */
   public function check (string $key, null|float $at = null): bool
   {
      // ?
      if (isSet($this->keys[$key]) === false) {
         return false;
      }

      // !
      $now = $at ?? microtime(true);
      $state = $this->keys[$key];

      // ?: Inside the grace window after the first press, or between repeats
      $window = $state['repeats'] > 1 ? $this->window : $this->grace;

      return ($now - $state['last']) < $window;
   }

   /**
    * Consume one queued press of a key (edge-triggered).
    *
    * @param string $key The keystroke token.
    *
    * @return bool Whether a press was pending.
    */
   public function pop (string $key): bool
   {
      // ?
      if (($this->pressed[$key] ?? 0) < 1) {
         return false;
      }

      // @
      $this->pressed[$key]--;
      if ($this->pressed[$key] === 0) {
         unset($this->pressed[$key]);
      }

      // :
      return true;
   }

   /**
    * Drop stale hold states (call once per tick).
    *
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    */
   public function expire (null|float $at = null): void
   {
      // !
      $now = $at ?? microtime(true);

      // @
      foreach ($this->keys as $key => $state) {
         $window = $state['repeats'] > 1 ? $this->window : $this->grace;

         if (($now - $state['last']) >= $window) {
            unset($this->keys[$key]);
         }
      }
   }

   /**
    * Reset all keyboard state.
    */
   public function reset (): void
   {
      $this->keys = [];
      $this->pressed = [];
   }
}
