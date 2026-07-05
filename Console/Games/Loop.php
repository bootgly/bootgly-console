<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games;


use function explode;
use function is_string;
use function max;
use function microtime;
use Closure;


/**
 * Fixed-timestep game loop over the Terminal Client/Server input channel.
 *
 * The Server side consumes the channel generator (`Input->reading()` SAPI):
 * keystroke tokens feed the Keyboard, the channel timeout paces the frames —
 * no busy wait — and simulation ticks catch up to real time in fixed steps,
 * so game speed is independent of render jitter.
 */
class Loop
{
   // * Config
   /** Simulation ticks per second */
   public int $tps = 20;
   /** Stop after N ticks (deterministic tests) — null = run until stopped */
   public null|int $limit = null;

   // * Data
   public Keyboard $Keyboard;

   // * Metadata
   public private(set) int $ticks = 0;
   public private(set) bool $running = false;


   public function __construct (Keyboard &$Keyboard)
   {
      // * Data
      $this->Keyboard = $Keyboard;
   }

   /**
    * Run the loop until `stop()`, the tick limit or a closed channel.
    *
    * @param callable $reading The channel generator factory — function (int $length, null|int $timeout): Generator.
    *                          Yields: string data, null on timeout, false when the channel closes.
    * @param Closure $update Simulation tick — function (float $delta): void.
    * @param Closure $render Frame render — called once per burst, after the catch-up ticks.
    */
   public function run (callable $reading, Closure $update, Closure $render): void
   {
      // * Metadata
      $this->ticks = 0;
      $this->running = true;

      // !
      $step = 1.0 / $this->tps;
      $deadline = microtime(true) + $step;

      // @@ Main loop — the channel timeout is the frame pacing
      while ($this->running === true) {
         $timeout = (int) max(0.0, ($deadline - microtime(true)) * 1000000);

         // @@ Drain the channel until the next tick deadline
         foreach ($reading(512, $timeout) as $data) {
            // ? Channel closed (Client died)
            if ($data === false) {
               $this->stop();

               break;
            }
            // ? Timeout — tick time
            if ($data === null) {
               break;
            }

            // @ Feed newline-framed keystroke tokens
            if (is_string($data) === true) {
               foreach (explode("\n", $data) as $token) {
                  if ($token !== '') {
                     $this->Keyboard->press($token);
                  }
               }
            }

            // ? Deadline reached while data was still flowing
            if (microtime(true) >= $deadline) {
               break;
            }
         }

         // ?
         if ($this->running === false) {
            break;
         }

         // @ Catch-up ticks (fixed timestep)
         $now = microtime(true);
         while ($now >= $deadline) {
            $this->Keyboard->expire($now);

            $update($step);

            $this->ticks++;
            $deadline += $step;

            // ? Tick limit (deterministic tests)
            if ($this->limit !== null && $this->ticks >= $this->limit) {
               $this->stop();

               break;
            }
         }

         // @ Render once per burst
         $render();
      }
   }

   /**
    * Stop the loop.
    */
   public function stop (): void
   {
      // * Metadata
      $this->running = false;
   }
}
