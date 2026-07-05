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


/**
 * Interval countdown for game cadences — march steps, fire cooldowns, TTLs.
 *
 * Repeating timers fire once per cycle and carry the remainder, so cadence
 * stays exact across ticks; one-shot timers fire once and stay expired until
 * `reset()`. The interval is public and mutable — cadences may accelerate
 * mid-game (e.g. a formation marching faster as it shrinks).
 */
class Timer
{
   // * Config
   /** Seconds per cycle (mutable — cadences may accelerate mid-game) */
   public float $interval;
   /** Restart automatically after firing (false = one-shot) */
   public bool $repeat = true;

   // * Metadata
   /** Seconds accumulated into the current cycle */
   public private(set) float $elapsed = 0.0;
   /** One-shot completed */
   public private(set) bool $expired = false;


   public function __construct (float $interval, bool $repeat = true)
   {
      // * Config
      $this->interval = $interval;
      $this->repeat = $repeat;
   }

   /**
    * Advance the timer; whether it fired this tick.
    *
    * Repeating: fires and carries the remainder (`elapsed -= interval`).
    * One-shot: fires once, then stays expired until `reset()`.
    *
    * @param float $delta Seconds since the last tick.
    *
    * @return bool Whether the timer fired this tick.
    */
   public function tick (float $delta): bool
   {
      // ? One-shot already completed
      if ($this->expired === true) {
         return false;
      }

      // @
      $this->elapsed += $delta;

      // ? Cycle not completed yet
      if ($this->elapsed < $this->interval) {
         return false;
      }

      // ?: Fired — repeating carries the remainder, one-shot expires
      if ($this->repeat === true) {
         $this->elapsed -= $this->interval;
      }
      else {
         $this->expired = true;
      }

      return true;
   }

   /**
    * Rearm the timer.
    *
    * @return self
    */
   public function reset (): self
   {
      // * Metadata
      $this->elapsed = 0.0;
      $this->expired = false;

      // :
      return $this;
   }
}
