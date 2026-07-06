<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games\Canvas;


/**
 * Canvas pixel-packing modes: how logical pixels map to terminal cells.
 */
enum Modes
{
   /** 1 pixel = 1 terminal cell (pixels carry their own character) */
   case Block;
   /** 1 terminal cell = 1×2 pixels (▀ / ▄ / █) */
   case Half;
   /** 1 terminal cell = 2×4 pixels (Braille dots U+2800..U+28FF) */
   case Braille;
}
