<?php
$context = Timber::context();
$context['posts'] = Timber::get_posts();
$context['title'] = '🦉 Owl Sightings Archive';

Timber::render('templates/archive-owl_sighting.twig', $context);
