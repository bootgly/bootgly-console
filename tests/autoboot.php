<?php

use Bootgly\ACI\Tests\Suites;

return new Suites(
   directories: [
      // ! Console platform
      // ? Bootable + autoloader
      'Console/',
      // ? App shell (Keymaps, Router, Screens, widgets)
      'Console/App/',
      // ? Game module (Canvas, Keyboard, Loop, Scenes)
      'Console/Game/',
      // ! Game projects — example signature suites (kit import guide)
      'projects/Invaders/tests/project/',
      'projects/Pong/tests/project/',
      'projects/Snake/tests/project/',
   ]
);
