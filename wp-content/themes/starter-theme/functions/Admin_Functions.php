<?php

namespace SIWP\WPT;

class Admin_Functions
{
	public function __construct()
	{
		add_filter('use_block_editor_for_post_type', [$this, 'block_editor_for_post_type'], 10, 2);
	}

	public function block_editor_for_post_type($can_edit, $post_type)
	{
		$gutenberg_post_types = [
			'post',
			'page',
		];

		if (in_array($post_type, $gutenberg_post_types)) {
			return true;
		}

		return $can_edit;
	}

}