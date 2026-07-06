<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\App;


use function array_slice;
use function count;
use function implode;
use function max;
use function mb_strlen;
use function microtime;
use function str_repeat;

use Bootgly\ABI\Data\__String\Escapeable\Text\Formattable;
use Bootgly\API\Component;
use Bootgly\CLI\Terminal;


/**
 * Toast notification queue: short-lived messages overlaid on the top-right
 * rows of the frame, expiring after their TTL.
 */
class Toasts extends Component
{
   use Formattable;


   // * Config
   /** Default toast lifetime in seconds */
   public float $ttl = 3.0;
   /** Visible toasts */
   public int $limit = 3;

   // * Data
   /** @var array<int,array{message: string, level: string, until: float}> */
   protected array $queue = [];

   // * Metadata
   // ...


   /**
    * Queue a toast.
    *
    * @param string $message The toast message (plain text).
    * @param string $level info | warning | error.
    * @param null|float $ttl Lifetime in seconds — defaults to the configured TTL.
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function add (
      string $message,
      string $level = 'info',
      null|float $ttl = null,
      null|float $at = null
   ): self
   {
      // !
      $now = $at ?? microtime(true);

      // @
      $this->queue[] = [
         'message' => $message,
         'level' => $level,
         'until' => $now + ($ttl ?? $this->ttl)
      ];

      // :
      return $this;
   }

   /**
    * Drop expired toasts.
    *
    * @param null|float $at Injectable clock (seconds) — defaults to the current time.
    */
   public function expire (null|float $at = null): void
   {
      // !
      $now = $at ?? microtime(true);

      // @
      $alive = [];
      foreach ($this->queue as $toast) {
         if ($toast['until'] > $now) {
            $alive[] = $toast;
         }
      }

      $this->queue = $alive;
   }

   /**
    * Render the visible toasts as right-aligned overlay rows.
    *
    * @param int $mode Only RETURN_OUTPUT is supported — the App composes the frame.
    *
    * @return null|string The overlay rows — empty string when no toast is alive.
    */
   public function render (int $mode = self::RETURN_OUTPUT): null|string
   {
      // ?
      if ($this->queue === []) {
         return '';
      }

      // !
      $width = Terminal::$width;

      // @ Latest toasts first
      $visible = array_slice($this->queue, - $this->limit);

      $lines = [];
      foreach ($visible as $toast) {
         $color = match ($toast['level']) {
            'warning' => self::_YELLOW_BOLD,
            'error' => self::_RED_BOLD,
            default => self::_GREEN_BOLD
         };

         $body = "▐ {$toast['message']} ";
         $gap = max(0, $width - mb_strlen($body));

         $lines[] = str_repeat(' ', $gap)
            . self::wrap($color) . $body . self::_RESET_FORMAT;
      }

      // :
      return implode("\n", $lines);
   }
}
