<?php
$context = Timber::context();
$context['post'] = Timber::get_post();

Timber::render('templates/single-owl_sighting.twig', $context);
