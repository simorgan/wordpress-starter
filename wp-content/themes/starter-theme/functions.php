<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

add_filter('timber/context', function ($context) {
	// Get WordPress body classes
	$body_classes = get_body_class();
	$context['body_class'] = implode(' ', $body_classes);

	return $context;
});