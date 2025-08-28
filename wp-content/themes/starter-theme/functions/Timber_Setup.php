<?php

namespace SIWP\WPT;

use Timber\Timber;

class Timber_Setup
{
	public function __construct()
	{
		Timber::init();
		Timber::$dirname = ['templates', 'views'];
	}
}