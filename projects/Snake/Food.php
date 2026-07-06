<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Snake;


use function in_array;
use function random_int;


class Food
{
   // * Config
   // ...

   // * Data
   // Position
   public int $x = 0;
   public int $y = 0;

   // * Metadata
   // ...


   /**
    * Respawn the food inside the borders, avoiding the occupied cells.
    *
    * @param int $columns The board columns (border inclusive).
    * @param int $rows The board rows (border inclusive).
    * @param array<int,array{0: int, 1: int}> $occupied Cells to avoid (the snake body).
    */
   public function spawn (int $columns, int $rows, array $occupied = []): void
   {
      // @@
      do {
         $this->x = random_int(1, $columns - 2);
         $this->y = random_int(1, $rows - 2);
      } while (in_array([$this->x, $this->y], $occupied, true) === true);
   }
}
