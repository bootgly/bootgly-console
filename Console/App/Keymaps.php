<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\App;


use function array_map;
use function array_values;
use function explode;
use function implode;
use function is_array;
use function microtime;
use function str_starts_with;
use function strlen;
use function strtolower;
use function ucfirst;
use Closure;

use Bootgly\CLI\Terminal\Input\Keystrokes;


/**
 * Shortcut registry with chord support.
 *
 * A binding maps a key — or a sequence of keys (a chord, e.g. `g g`) — to a
 * labeled handler. `handle()` consumes raw keystrokes: exact matches run the
 * handler, chord prefixes buffer until the next key or the chord timeout.
 * `list()` exposes the bindings for help overlays and command palettes.
 */
class Keymaps
{
   // ! Chord buffer separator (never a terminal byte)
   private const string SEPARATOR = "\x1F";


   // * Config
   /** Chord window in milliseconds — a pending chord expires after it */
   public int $timeout = 800;

   // * Data
   /** @var array<string,array{keys: string, label: string, handler: Closure}> */
   protected array $bindings = [];

   // * Metadata
   /** Pending chord buffer (normalized key ids) */
   private string $pending = '';
   /** Timestamp of the last buffered key */
   private float $last = 0.0;


   /**
    * Register a binding.
    *
    * @param string|array<int,string|Keystrokes>|Keystrokes $keys A key, or a sequence of keys (chord).
    * @param string $label Human-readable action label (help overlay / palette).
    * @param Closure $handler Action to run when the binding matches.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function bind (string|array|Keystrokes $keys, string $label, Closure $handler): self
   {
      // !
      $sequence = is_array($keys) ? $keys : [$keys];

      $ids = [];
      $displays = [];
      foreach ($sequence as $key) {
         if ($key instanceof Keystrokes) {
            $ids[] = $key->value;
            $displays[] = implode(
               '+',
               array_map(
                  static fn (string $word): string => ucfirst(strtolower($word)),
                  explode('_', $key->name)
               )
            );

            continue;
         }

         $ids[] = $key;
         $displays[] = $key;
      }

      // @
      $this->bindings[implode(self::SEPARATOR, $ids)] = [
         'keys' => implode(' ', $displays),
         'label' => $label,
         'handler' => $handler
      ];

      // :
      return $this;
   }

   /**
    * Handle a raw keystroke against the registered bindings.
    *
    * @param string $key Raw bytes read from the terminal (escape sequences arrive whole).
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    *
    * @return bool Whether the key was consumed (a binding ran or a chord is pending).
    */
   public function handle (string $key, null|float $at = null): bool
   {
      // ?
      if (strlen($key) === 0) {
         return false;
      }

      // !
      $now = $at ?? microtime(true);

      // ? Pending chord expired
      if ($this->pending !== '' && ($now - $this->last) * 1000 > $this->timeout) {
         $this->reset();
      }

      $candidate = $this->pending === ''
         ? $key
         : $this->pending . self::SEPARATOR . $key;

      // ? Exact match — run the handler
      if (isSet($this->bindings[$candidate]) === true) {
         $this->reset();

         ($this->bindings[$candidate]['handler'])();

         return true;
      }

      // ? Chord prefix — buffer and wait for the next key
      foreach ($this->bindings as $id => $binding) {
         if (str_starts_with($id, $candidate . self::SEPARATOR) === true) {
            $this->pending = $candidate;
            $this->last = $now;

            return true;
         }
      }

      // ? A broken chord: retry the key as a fresh sequence
      if ($this->pending !== '') {
         $this->reset();

         return $this->handle($key, $now);
      }

      // :
      return false;
   }

   /**
    * Reset the pending chord buffer.
    */
   public function reset (): void
   {
      $this->pending = '';
      $this->last = 0.0;
   }

   /**
    * List the registered bindings (help overlay / command palette data).
    *
    * @return array<int,array{keys: string, label: string, handler: Closure}>
    */
   public function list (): array
   {
      return array_values($this->bindings);
   }
}
