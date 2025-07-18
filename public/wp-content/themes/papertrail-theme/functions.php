<?php

add_theme_support('post-thumbnails'); // ✅ ADDED
// Load Composer's autoloader and Timber
require_once __DIR__ . '/vendor/autoload.php';

use Timber\Timber;

// Initialize Timber
Timber::init();

add_theme_support('post-thumbnails'); // ✅ ADDED
add_image_size('owl-thumb', 300, 300, true); // ✅ Optional custom size
add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, ['owl-thumb' => 'Owl Thumbnail']);
}); // ✅ Optional display in WP Media dropdown

add_filter('timber/loader/paths', function ($paths) {
    $paths[0][] = __DIR__ . '/templates';
    return $paths;
});
add_filter('timber/loader/paths', function ($paths) {
    $paths[0][] = __DIR__ . '/templates';
    return $paths;
});

add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if (is_a($user, 'WP_User') && $user->has_cap('read')) {
        return home_url('/sightings'); // Adjust if your slug is different
    }
    return $redirect_to;
}, 10, 3);
add_theme_support('menus');

add_action('after_setup_theme', function () {
    register_nav_menus([
        'main_menu' => 'Main Menu'
    ]);
});
add_filter('nav_menu_item_args', function ($args) {
    $args->link_before = '';
    $args->link_after = '';
    return $args;
});
add_action('init', function () {

    $file = get_attached_file(85); // Replace 999 with a real attachment ID
    $size = getimagesize($file);
    error_log('🧪 Image size test: ' . print_r($size, true));
    if (!get_option('demo_owl_sightings_created')) {
        $species = ['Great Horned Owl', 'Barn Owl', 'Northern Saw-whet Owl', 'Spotted Owl'];
        $locations = ['Forest Park, OR', 'Ridgefield NWR', 'Mount Tabor', 'Sauvie Island'];

        for ($i = 0; $i < 4; $i++) {
            $post_id = wp_insert_post([
                'post_type' => 'owl_sighting',
                'post_title' => $species[$i],
                'post_status' => 'publish',
                'post_date' => date('Y-m-d H:i:s', strtotime("-$i days")),
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'owl_species', $species[$i]);
                update_post_meta($post_id, 'owl_location', $locations[$i]);
                update_post_meta($post_id, 'owl_date_spotted', date('Y-m-d', strtotime("-$i days")));
                update_post_meta($post_id, 'owl_notes', 'Spotted near sunset. Very quiet and elusive.');
            }
        }

        update_option('demo_owl_sightings_created', true);
    }
});

error_log('🦉 functions.php is loading');

// add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_style('papertrail-style', get_stylesheet_uri());
// });

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('papertrail-style', get_template_directory_uri() . '/style.css');
});

// Redirect homepage to /sightings
add_action('template_redirect', function () {
    if (is_front_page() && !is_admin()) {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $url    = "{$scheme}://{$host}/sightings";

        wp_redirect($url, 302);
        exit;
    }
});

// Require login for sightings and submit page
add_action('template_redirect', function () {
    if (!is_user_logged_in()) {
        if (is_post_type_archive('owl_sighting') || is_singular('owl_sighting')) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        if (is_page('submit-sighting')) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
});
add_action('template_redirect', function () {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/owl-sightings') !== false) {
        error_log("🦉 Hit owl-sightings URL");

        if (is_page()) {
            error_log("📄 WordPress thinks this is a PAGE: " . get_the_title());
        }

        if (is_post_type_archive('owl_sighting')) {
            error_log("📦 WordPress thinks this is the archive for owl_sighting");
        }

        if (is_singular('owl_sighting')) {
            error_log("📌 WordPress thinks this is a single owl_sighting");
        }

        if (is_404()) {
            error_log("❌ This is a 404 page");
        }
    }
});

add_action('template_redirect', function () {
    // Check if the current path is exactly "owl-sightings"
    if (trim($_SERVER['REQUEST_URI'], '/') === 'owl-sightings') {
        wp_redirect(home_url('/sightings'), 301); // permanent redirect
        exit;
    }
});
