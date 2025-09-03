<?php
$context = Timber::context();
$context['post'] = Timber::get_post();
$context['posts'] = Timber::get_posts();

Timber::render('archive.twig', $context);