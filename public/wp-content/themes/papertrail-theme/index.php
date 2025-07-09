<?php
$context = Timber::context();
$context['posts'] = Timber::get_posts([
    'post_type' => 'owl_sighting',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
]);
Timber::render('templates/index.twig', $context);
