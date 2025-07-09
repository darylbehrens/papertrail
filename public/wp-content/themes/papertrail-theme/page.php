<?php
// page.php — Timber v2+ and shortcode-ready

if ( ! class_exists( 'Timber' ) ) {
    echo '❌ Timber not activated.';
    return;
}

try {
    $context = Timber::context();
    $post = Timber::get_post();

    // Also apply the_content filters (for Gutenberg/shortcodes/etc.)
    $context['post'] = $post;
    $context['post_content'] = apply_filters('the_content', $post->post_content);

    Timber::render('page.twig', $context);
} catch (Throwable $e) {
    echo '❌ Timber error: ' . $e->getMessage();
}
