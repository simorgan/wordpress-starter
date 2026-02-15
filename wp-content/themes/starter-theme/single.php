<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();
$timber_post = Timber::get_post();
$context['post'] = $timber_post;

if ($timber_post->post_type === 'post') {
	$template = 'templates/single.twig';
} else {
	$template = ['templates/single-' . $timber_post->post_type . '.twig'];
}

Timber::render($template, $context);