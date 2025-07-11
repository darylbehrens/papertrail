<?php
$context = Timber::context();
$post = Timber::get_post();

if ($post->thumbnail) {
    $post->thumbnail = Timber::get_image($post->thumbnail->ID);
} else {
    $post->thumbnail = Timber::get_image('2025\07\Owl-Dead-on-SharpenAI-Focus-150x150.jpg');
}



$context['post'] = $post;
$context['post_content'] = apply_filters('the_content', $post->post_content);

Timber::render(['single-owl_sighting.twig', 'single.twig'], $context);
