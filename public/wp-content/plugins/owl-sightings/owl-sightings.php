<?php

/**
 * Plugin Name: Owl Sightings
 * Plugin URI: https://github.com/darylbehrens/papertrail/tree/main/public/wp/wp-content/plugins/owl-sightings
 * Description: Adds a custom post type for recording owl sightings.
 * Version: 1.0
 * Author: Daryl Behrens
 * License: GPL2
 */

 // Grab hardcoded data for select boxes. In future would be in a DB or grabbed from an API
require_once plugin_dir_path(__FILE__) . 'includes/owl-data.php';
require_once plugin_dir_path(__FILE__) . 'includes/county-utils.php';

// Basic security: prevents this file from being run outside of WordPress
defined('ABSPATH') or die('No script kiddies please!');

// Enqueue our owl info side panel script only on the submission page
add_action('wp_enqueue_scripts', function () {
    if (is_page('submit-sightings')) {
        wp_enqueue_script(
            'owl-side-panel-script',
            plugin_dir_url(__FILE__) . 'assets/js/owl-side-panel.js',
            [],
            false,
            true // Load in footer
        );
    }
});

// This filter ensures curl is allowed for remote requests (like Wikipedia API calls)
add_filter('use_curl_transport', '__return_true');

add_shortcode('owl_sightings', function () {
    // Prevents the shortcode from running multiple times on the same page
    static $has_run = false;
    if ($has_run)
        return '';
    $has_run = true;

    // Show "Submit New Sighting" button if the user is logged in
    if (is_user_logged_in()) {
        echo '<p><a href="' . esc_url(home_url('/submit-sighting')) . '" class="button">➕ Submit New Sighting</a></p>';
    }

    // Capture species filter from URL query string if present
    $species_filter = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';

    // Build meta query based on filter
    $meta_query = [];
    if (!empty($species_filter)) {
        $meta_query[] = [
            'key' => 'owl_species',
            'value' => $species_filter,
            'compare' => '='
        ];
    }

    // Query all owl_sighting posts, optionally filtered by species
    $args = [
        'post_type' => 'owl_sighting',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => $meta_query,
    ];
    $query = new WP_Query($args);

    ob_start(); // Start output buffering so we can return the HTML
?>
    <!-- Filter dropdown -->
    <form method="GET" style="margin-bottom:1em;">
        <label for="species">Filter by Species:</label>
        <select name="species" id="species" onchange="this.form.submit()">
            <option value="">-- All Species --</option>
            <?php
            // Get all unique species from existing sightings
            $species_seen = get_posts([
                'post_type' => 'owl_sighting',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            $species_values = [];
            foreach ($species_seen as $post_id) {
                $species = get_post_meta($post_id, 'owl_species', true);
                if ($species && !in_array($species, $species_values)) {
                    $species_values[] = $species;
                }
            }
            sort($species_values); // Alphabetize
            foreach ($species_values as $species) {
                // Render each species as a <select> option
                echo '<option value="' . esc_attr($species) . '" ' . selected($species_filter, $species, false) . '>' . esc_html($species) . '</option>';
            }
            ?>
        </select>
    </form>
    <?php

    // If no sightings found, show message
    if (!$query->have_posts()) {
        echo '<p>No sightings found.</p>';
    } else {
        echo '<div class="owl-sightings-list">';
        // Loop through all matching sightings
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $species = get_post_meta($id, 'owl_species', true);
            $date_raw = get_post_meta($id, 'owl_date_spotted', true);
            $date = date('m-d-Y', strtotime($date_raw));
            $notes = get_post_meta($id, 'owl_notes', true);
            $image_url = get_the_post_thumbnail_url($id, 'owl-thumb'); // Could also use 'medium'

            // Render sighting card
            echo '<div class="owl-sighting">';
            echo '<h3>' . esc_html($species) . '</h3>';
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($species) . '" style="max-width:200px;">';
            }
            echo '<ul>';
            echo '<li><strong>Date:</strong> ' . esc_html($date) . '</li>';
            echo '</ul>';
            echo '<p><strong>Notes:</strong><br>' . nl2br(esc_html($notes)) . '</p>';
            echo '</div><hr>';
        }
        echo '</div>';
    }

    wp_reset_postdata(); // Reset global post data after custom query
    return ob_get_clean(); // Return the captured output
});


// Renders the content of the meta box in the editor
function owl_sighting_meta_box_html($post)
{
    // Load saved values (if they exist) from post meta
    $selected_species = get_post_meta($post->ID, 'owl_species', true);
    $location = get_post_meta($post->ID, 'owl_location', true);
    $date_spotted = get_post_meta($post->ID, 'owl_date_spotted', true);
    $notes = get_post_meta($post->ID, 'owl_notes', true);

    // Add a nonce field for security
    wp_nonce_field('owl_sighting_nonce_action', 'owl_sighting_nonce');
    ?>
    <!-- Species dropdown -->
    <p>
        <label for="owl_species">Species:</label><br>
        <select name="owl_species" id="owl_species" class="regular-text"></select>
    </p>

    <!-- More Info button to open side panel with Wikipedia info -->
    <p>
        <button type="button" id="more_owl_info" class="button">More Info</button>
    </p>

    <!-- Side panel for Wikipedia owl info -->
    <div id="owl_side_panel" aria-hidden="true">
        <div id="owl_side_panel_inner">
            <button id="close_owl_panel" aria-label="Close panel">&times;</button>
            <div id="owl_panel_spinner">🌀 Loading owl facts...</div>
            <h2 id="owl_panel_title"></h2>
            <img id="owl_panel_img" src="" alt="Owl photo" />
            <p id="owl_panel_summary"></p>
            <p><a id="owl_panel_link" href="#" target="_blank" rel="noopener noreferrer">Read more on Wikipedia →</a></p>
        </div>
    </div>

    <!-- Location input -->
    <p>
        <label for="owl_location">Location:</label><br>
        <input type="text" name="owl_location" id="owl_location" value="<?php echo esc_attr($location); ?>"
            class="regular-text">
    </p>

    <!-- Date spotted input -->
    <p>
        <label for="owl_date_spotted">Date Spotted:</label><br>
        <input type="date" name="owl_date_spotted" id="owl_date_spotted" value="<?php echo esc_attr($date_spotted); ?>">
    </p>

    <!-- Notes textarea -->
    <p>
        <label for="owl_notes">Notes:</label><br>
        <textarea name="owl_notes" id="owl_notes" rows="5" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
    </p>
<?php
}

// Registers REST API endpoints under /wp-json/owl/v1/
add_action('rest_api_init', function () {
    // Endpoint to get the list of owl species
    register_rest_route('owl/v1', '/species', [
        'methods' => 'GET',
        'callback' => 'get_fake_owl_species'
    ]);

    // Endpoint to get Wikipedia info for a selected owl
    register_rest_route('owl/v1', '/info', [
        'methods' => 'GET',
        'callback' => 'get_fake_owl_info',
        'permission_callback' => '__return_true' // Makes the route public
    ]);
});

// Returns a list of PNW owls with protection status
function get_fake_owl_species()
{
    $pnw_owls = get_pnw_owls();

    // Sort the array alphabetically by name
    usort($pnw_owls, fn($a, $b) => strcmp($a['name'], $b['name']));

    // Append " (Protected)" to protected species
    return array_map(function ($owl) {
        return $owl['name'] . ($owl['protected'] ? ' (Protected)' : '');
    }, $pnw_owls);
}

// Retrieves Wikipedia info for a given owl species via REST
function get_fake_owl_info($request)
{
    $species = $request->get_param('species');
    return fetch_wikipedia_owl_info($species);
}

// Fetches summary and image from Wikipedia REST API
function fetch_wikipedia_owl_info($species)
{
    $primaryQuery = urlencode($species);
    $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$primaryQuery";

    $result = wikipedia_request($url);

    // If no valid result, retry with a lowercase, underscored fallback version
    if (!$result || empty($result['fact']) || $result['fact'] === 'No summary available.') {
        $fallback = str_replace(' ', '_', strtolower($species));
        $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$fallback";
        error_log("🔁 Retrying with fallback: $url");
        $result = wikipedia_request($url);
    }

    // Return either the fetched result or a default message
    return $result ?? [
        'fact' => "No Wikipedia summary available for '$species'.",
        'image' => null,
    ];
}

// Makes a GET request to Wikipedia and parses the result
function wikipedia_request($url)
{
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => false, // Allows self-signed certs; not ideal in production
    ]);

    // If there's an error, log it and return null
    if (is_wp_error($response)) {
        error_log("🛑 Wikipedia request error: " . $response->get_error_message());
        return null;
    }

    // Decode the response JSON
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Check for a valid article and extract summary and image
    if (isset($data['type']) && $data['type'] === 'standard') {
        return [
            'fact' => $data['extract'] ?? 'No summary available.',
            'image' => $data['thumbnail']['source'] ?? null,
        ];
    }

    return null; // Not a standard article
}

// Ensures custom post type slugs like /sightings work after plugin activation
register_activation_hook(__FILE__, 'flush_owl_rewrites');
function flush_owl_rewrites()
{
    owl_sightings_register_post_type();
    flush_rewrite_rules();
}

// Shortcode to render the frontend owl sighting submission form
add_shortcode('owl_sighting_form', function () {
    // Force login before form can be used
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }

    // Static list of PNW owls for form dropdown
    $pnw_owls = get_pnw_owls();

    // 🔥 Handle form submission
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['owl_sighting_nonce']) &&
        wp_verify_nonce($_POST['owl_sighting_nonce'], 'submit_owl_sighting')
    ) {
        $title = sanitize_text_field($_POST['owl_species']);
        $location = sanitize_text_field($_POST['owl_location']);
        $raw_date = sanitize_text_field($_POST['owl_date_spotted']);
        $date_only = substr($raw_date, 0, 10); // Remove time if present

        // Prevent future dates
        if (strtotime($date_only) > strtotime(date('Y-m-d'))) {
            wp_die('❌ The date cannot be in the future.');
        }

        $date_spotted = $date_only;
        $notes = sanitize_textarea_field($_POST['owl_notes']);
        $protected = isset($_POST['owl_protected']) ? sanitize_text_field($_POST['owl_protected']) : '0';

        error_log('🔍 Form POST: ' . print_r($_POST, true));

        // Insert the sighting as a new post
        $post_id = wp_insert_post([
            'post_title' => $title . ' sighting',
            'post_type' => 'owl_sighting',
            'post_status' => 'publish',
            'post_content' => $notes,
        ]);

        // Save meta fields and image if post was created
        if ($post_id) {
            update_post_meta($post_id, 'owl_species', $title);
            update_post_meta($post_id, 'owl_date_spotted', $date_spotted);
            update_post_meta($post_id, 'owl_notes', $notes);
            update_post_meta($post_id, 'owl_protected', $protected);
            update_post_meta($post_id, 'owl_location', $location);

            // Handle file upload and attach it as a featured image
            if (!empty($_FILES['owl_photo']) && !empty($_FILES['owl_photo']['name'])) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $attachment_id = media_handle_upload('owl_photo', $post_id);

                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);

                    // ✅ Regenerate metadata
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id));
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                }
            }

            // Redirect to sightings page after successful submission
            wp_redirect(site_url('/sightings'));
            exit;
        }
    }

    // 🔁 Output the HTML form
    ob_start();
    ?>
    
    <?php
    return ob_get_clean(); // Return form HTML
});

// Load the plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-owl-sightings-plugin.php';

// Instantiate the plugin class on plugins_loaded
add_action('plugins_loaded', function () {
    new OwlSightingsPlugin();
});