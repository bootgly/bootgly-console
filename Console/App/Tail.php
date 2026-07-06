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


use Closure;

use Bootgly\CLI\UI\Components\Logs;


/**
 * Log viewer bound to a pull source (tail/follow).
 *
 * Extends the core Logs pager (filters, pause/scroll, detail view, geometry)
 * with a bound source: `follow()` a Closure that returns the next chunk of
 * newline-delimited JSON records, and `pull()` each frame to drain it.
 */
class Tail extends Logs
{
   // * Data
   /** Pull source — function (): string|false (false/empty = drained) */
   private null|Closure $source = null;


   /**
    * Bind (or unbind) the pull source.
    *
    * @param null|Closure $source function (): string|false — the next raw chunk.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function follow (null|Closure $source): self
   {
      // * Data
      $this->source = $source;

      // :
      return $this;
   }

   /**
    * Drain the bound source into the log buffer.
    */
   public function pull (): void
   {
      // ?
      if ($this->source === null) {
         return;
      }

      // @@ Drain until the source reports empty
      while (true) {
         $chunk = ($this->source)();

         if ($chunk === false || $chunk === '') {
            break;
         }

         $this->feed($chunk);
      }
   }
}
