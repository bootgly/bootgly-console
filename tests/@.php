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
   ]
);
