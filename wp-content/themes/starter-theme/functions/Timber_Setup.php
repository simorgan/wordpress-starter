<?php

namespace SIWP\WPT;

use Timber\Timber;

class Timber_Setup
{
	public function __construct()
	{
		Timber::init();
		Timber::$dirname = ['templates', 'views'];

		add_filter('timber/post/content', [$this, 'add_to_context'], 10, 2);
	}

	public function add_to_context($context)
	{
		// Process content for the main post
		if (isset($context['post']) && $context['post']) {
			$context['post']->content = apply_filters('the_content', $context['post']->post_content);
		}

		return $context;
	}
}