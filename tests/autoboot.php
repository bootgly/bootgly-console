<?php

use Bootgly\ACI\Tests\Suites;

return new Suites(
   directories: [
      // ! Console platform
      // ? Bootable + autoloader
      'Console/',
      // ? App shell (Keymaps, Router, Screens, widgets)
      'Console/App/',
      // ? Games module (Canvas, Keyboard, Loop, Scenes)
      'Console/Games/',
      // ! Game projects — example signature suites (kit import guide)
      'projects/Invaders/',
      'projects/Pong/',
      'projects/Snake/',
   ]
);
