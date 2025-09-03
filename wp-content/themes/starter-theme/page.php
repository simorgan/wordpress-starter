<?php
/**
 * The template for displaying all pages.
 *
 */

$context = Timber::context();
$context['post'] = Timber::get_post();
$templates = array('templates/page.twig');

Timber::render($templates, $context);