<?php
$context = Timber::context();
$post = Timber::get_post();

$context['post'] = $post;
$context['post_content'] = apply_filters('the_content', $post->post_content);

// Optional: If you ever want to show related sightings or more metadata
// $context['related_sightings'] = Timber::get_posts([...]);

Timber::render(['single-owl_sighting.twig', 'single.twig'], $context);
