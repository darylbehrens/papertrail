<?php
// Load Composer's autoloader and Timber
require_once __DIR__ . '/vendor/autoload.php';

use Timber\Timber;

// Initialize Timber
Timber::init();

add_action('init', function () {
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
                update_post_meta($post_id, '_owl_species', $species[$i]);
                update_post_meta($post_id, '_owl_location', $locations[$i]);
                update_post_meta($post_id, '_owl_date_spotted', date('Y-m-d', strtotime("-$i days")));
                update_post_meta($post_id, '_owl_notes', 'Spotted near sunset. Very quiet and elusive.');
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

