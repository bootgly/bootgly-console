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


use function count;
use function implode;
use function max;
use function min;
use function ord;
use function stripos;
use Closure;

use Bootgly\ABI\Data\__String\Escapeable\Text\Formattable;
use Bootgly\API\Component;
use Bootgly\CLI\Terminal\Input\Keystrokes;
use Bootgly\CLI\Terminal\Input\Line;


/**
 * Command palette: incremental search over the registered Keymaps bindings.
 *
 * While active it captures every keystroke — type to filter, ↑/↓ to select,
 * Enter to run the selected action, Esc to dismiss.
 */
class Palette extends Component
{
   use Formattable;


   // * Config
   /** Visible entries */
   public int $limit = 8;

   // * Data
   public Keymaps $Keymaps;
   /** Query editor (cursor, Home/End, Ctrl-W/U — the core Line engine) */
   public Line $Line;

   // * Metadata
   public private(set) bool $active = false;
   public private(set) int $cursor = 0;
   public string $query {
      get => $this->Line->value;
   }


   public function __construct (Keymaps &$Keymaps)
   {
      // * Data
      $this->Keymaps = $Keymaps;
      $this->Line = new Line;
   }

   /**
    * Toggle the palette (opening resets the query and selection).
    */
   public function toggle (): void
   {
      $this->active = $this->active === false;

      // * Data
      $this->Line->reset();
      // * Metadata
      $this->cursor = 0;
   }

   /**
    * Filter the bindings by the current query (matched against the labels).
    *
    * @return array<int,array{keys: string, label: string, handler: Closure}>
    */
   public function filter (): array
   {
      // ?
      if ($this->query === '') {
         return $this->Keymaps->list();
      }

      $matches = [];
      foreach ($this->Keymaps->list() as $binding) {
         if (stripos($binding['label'], $this->query) !== false) {
            $matches[] = $binding;
         }
      }

      // :
      return $matches;
   }

   /**
    * Handle a keystroke while the palette is active.
    *
    * @param string $key Raw bytes read from the terminal.
    *
    * @return bool Always true — the palette captures all input while active.
    */
   public function control (string $key): bool
   {
      switch ($key) {
         // @ Dismiss
         case Keystrokes::ESCAPE->value:
            $this->toggle();
            break;
         // @ Run the selected action
         case Keystrokes::ENTER->value:
         case "\r": // raw mode (-icrnl) delivers Enter as CR
            $bindings = $this->filter();
            $selected = $bindings[$this->cursor] ?? null;

            $this->toggle();

            if ($selected !== null) {
               ($selected['handler'])();
            }
            break;
         // @ Select
         case Keystrokes::UP->value:
            $this->cursor = max(0, $this->cursor - 1);
            break;
         case Keystrokes::DOWN->value:
            $this->cursor = min(max(0, count($this->filter()) - 1), $this->cursor + 1);
            break;
         // @ Edit the query (delegated to the Line engine)
         default:
            $before = $this->Line->value;

            $byte = ord($key[0]);
            $byte === 27 || $byte < 32 || $byte === 127
               ? $this->Line->control($key)
               : $this->Line->feed($key);

            // ? A changed query resets the selection
            if ($this->Line->value !== $before) {
               $this->cursor = 0;
            }
      }

      // :
      return true;
   }

   /**
    * Render the palette frame content.
    *
    * @param int $mode Only RETURN_OUTPUT is supported — the App composes the frame.
    *
    * @return null|string The palette lines.
    */
   public function render (int $mode = self::RETURN_OUTPUT): null|string
   {
      // !
      $bindings = $this->filter();
      $total = count($bindings);

      // @ Header (query prompt — the Line renders its own cursor)
      // 256-color dark gray background + bright white text — bright-black
      // (SGR 100) is theme-dependent and renders LIGHT gray in some themes
      $lines = [];
      $lines[] = self::wrap(self::_EXTENDED_BACKGROUND, '5', '236', self::_WHITE_BRIGHT_FOREGROUND)
         . ' ⌘ ' . $this->Line->render() . self::_RESET_FORMAT;
      $lines[] = '';

      // @ Window the entries around the selection
      $this->cursor = max(0, min($this->cursor, max(0, $total - 1)));
      $top = max(0, min($this->cursor - ($this->limit - 1), $total - $this->limit));
      for ($index = max(0, $top); $index < $total && $index < $top + $this->limit; $index++) {
         $binding = $bindings[$index];
         $selected = $index === $this->cursor;

         $gutter = $selected
            ? self::wrap(self::_CYAN_BOLD) . '›' . self::_RESET_FORMAT
            : ' ';

         $lines[] = "$gutter "
            . self::wrap(self::_CYAN_FOREGROUND) . $binding['keys'] . self::_RESET_FORMAT
            . '  ' . $binding['label'];
      }

      // ?
      if ($total === 0) {
         $lines[] = self::wrap(self::_BLACK_BRIGHT_FOREGROUND) . ' (no matching actions)' . self::_RESET_FORMAT;
      }

      $lines[] = '';
      $lines[] = self::wrap(self::_BLACK_BRIGHT_FOREGROUND)
         . ' [type to filter]  [↑↓] select  [Enter] run  [Esc] dismiss' . self::_RESET_FORMAT;

      // :
      return implode("\n", $lines);
   }
}
