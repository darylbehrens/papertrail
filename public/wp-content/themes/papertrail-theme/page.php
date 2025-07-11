<?php
// page.php — Timber v2+ and shortcode-ready

if (!class_exists('Timber')) {
    echo '❌ Timber not activated.';
    return;
}

try {
    $context = Timber::context();
    $post = Timber::get_post();

    $context['post'] = $post;
    $context['post_content'] = apply_filters('the_content', $post->post_content);

    // ✅ Only add sightings if we're on the sightings page
    if ($post->post_name === 'sightings') {
        $context['sightings'] = Timber::get_posts([
            'post_type' => 'owl_sighting',
            'posts_per_page' => -1,
        ]);
    }

    // 👇 Add support for page-specific templates
    $template_slug = 'page-' . $post->post_name . '.twig';
    $templates = [$template_slug, 'page.twig'];

    Timber::render($templates, $context);
} catch (Throwable $e) {
    echo '❌ Timber error: ' . $e->getMessage();
}
