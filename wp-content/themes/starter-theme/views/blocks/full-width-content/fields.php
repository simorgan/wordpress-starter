<?php

namespace SIWP\WPT\Blocks;


use SIWP\WPT\Theme_ACF;

$banner = new Theme_ACF('banner');


$banner
	->addText('title')
	->addWysiwyg('content')
	->addLink('link')
	->setLocation('block', '==', 'acf/full-width-content')
	->build_fields();


