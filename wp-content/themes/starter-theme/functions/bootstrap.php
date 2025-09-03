<?php

use SIWP\WPT\Admin_Functions;
use SIWP\WPT\Assets_Loader;
use SIWP\WPT\Theme_ACF;
use SIWP\WPT\Theme_Blocks;
use SIWP\WPT\Theme_Debugger;
use SIWP\WPT\Timber_Setup;

new Assets_Loader();
new Timber_Setup();
new Theme_Blocks();
new Admin_Functions();
new Theme_Debugger();
