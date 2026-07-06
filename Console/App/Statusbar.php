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


use function implode;
use function max;
use function mb_strlen;
use function str_repeat;

use Bootgly\ABI\Data\__String\Escapeable\Text\Formattable;
use Bootgly\API\Component;
use Bootgly\CLI\Terminal;


/**
 * Fixed status bar (the last frame row): left segments ▏-separated,
 * right segments right-aligned — both plain user strings.
 */
class Statusbar extends Component
{
   use Formattable;


   // * Config
   /** @var array<int,string> */
   public array $left = [];
   /** @var array<int,string> */
   public array $right = [];

   // * Data
   // ...

   // * Metadata
   // ...


   /**
    * Render the status bar row.
    *
    * @param int $mode Only RETURN_OUTPUT is supported — the App composes the frame.
    *
    * @return null|string The status bar row.
    */
   public function render (int $mode = self::RETURN_OUTPUT): null|string
   {
      // !
      $width = Terminal::$width;

      $left = ' ' . implode('  ▏ ', $this->left);
      $right = $this->right === [] ? '' : implode('  ', $this->right) . ' ';

      // @ Pad the gap so the right segments align to the edge
      $gap = max(1, $width - mb_strlen($left) - mb_strlen($right));

      // : 256-color dark gray background + bright white text — bright-black
      // (SGR 100) is theme-dependent and renders LIGHT gray in some themes
      return self::wrap(self::_EXTENDED_BACKGROUND, '5', '236', self::_WHITE_BRIGHT_FOREGROUND)
         . $left . str_repeat(' ', $gap) . $right
         . self::_RESET_FORMAT;
   }
}
