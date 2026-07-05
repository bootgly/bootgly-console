<?php

// Screen view fixture
use Console\App;
use Console\App\Screens\Screen;

return static function (App $App, Screen $Screen): string {
   return "Dashboard fixture — state: " . ($Screen->state['tag'] ?? 'none');
};
