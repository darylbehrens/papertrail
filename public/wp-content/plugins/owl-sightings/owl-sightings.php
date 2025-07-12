<?php

/**
 * Plugin Name: Owl Sightings
 * Plugin URI: https://github.com/darylbehrens/papertrail/tree/main/public/wp/wp-content/plugins/owl-sightings
 * Description: Adds a custom post type for recording owl sightings.
 * Version: 1.0
 * Author: Daryl Behrens
 * License: GPL2
 */

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

// Helper function that returns a list of all Washington counties (used in county dropdown)
function get_washington_counties()
{
    return [
        'Adams',
        'Asotin',
        'Benton',
        'Chelan',
        'Clallam',
        'Clark',
        'Columbia',
        'Cowlitz',
        'Douglas',
        'Ferry',
        'Franklin',
        'Garfield',
        'Grant',
        'Grays Harbor',
        'Island',
        'Jefferson',
        'King',
        'Kitsap',
        'Kittitas',
        'Klickitat',
        'Lewis',
        'Lincoln',
        'Mason',
        'Okanogan',
        'Pacific',
        'Pend Oreille',
        'Pierce',
        'San Juan',
        'Skagit',
        'Skamania',
        'Snohomish',
        'Spokane',
        'Stevens',
        'Thurston',
        'Wahkiakum',
        'Walla Walla',
        'Whatcom',
        'Whitman',
        'Yakima',
    ];
}

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


// Registers the custom post type 'owl_sighting'
function owl_sightings_register_post_type()
{
    // Set up labels for the WordPress admin UI
    $labels = [
        'name' => 'Owl Sightings',
        'singular_name' => 'Owl Sighting',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Owl Sighting',
        'edit_item' => 'Edit Owl Sighting',
        'new_item' => 'New Owl Sighting',
        'view_item' => 'View Owl Sighting',
        'search_items' => 'Search Owl Sightings',
        'not_found' => 'No owl sightings found',
        'not_found_in_trash' => 'No owl sightings found in Trash',
    ];

    // Define arguments for the post type behavior
    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => 'sightings', // creates /sightings archive
        'rewrite' => ['slug' => 'sightings'],
        'supports' => ['title', 'editor', 'custom-fields'], // enables title, description, and metadata
        'show_in_rest' => true, // enables Gutenberg + REST API access
    ];

    register_post_type('owl_sighting', $args);
}

// Hook into WordPress init to register the post type
add_action('init', 'owl_sightings_register_post_type');


// Register a meta box for owl sighting details
add_action('add_meta_boxes', 'owl_sighting_add_meta_boxes');

function owl_sighting_add_meta_boxes()
{
    add_meta_box(
        'owl_sighting_details',       // HTML ID
        'Owl Sighting Details',       // Title in UI
        'owl_sighting_meta_box_html', // Callback function to render box
        'owl_sighting',               // Post type it's for
        'normal',                     // Context
        'high'                        // Priority
    );
}

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

// Hook that will eventually save data from the meta box (logic not shown here)
add_action('save_post', 'owl_sighting_save_meta_boxes');


// Saves custom meta box data when a post is saved
function owl_sighting_save_meta_boxes($post_id)
{
    // Verify the security nonce
    if (!isset($_POST['owl_sighting_nonce']) || !wp_verify_nonce($_POST['owl_sighting_nonce'], 'owl_sighting_nonce_action'))
        return;

    // Prevent autosaves from triggering this logic
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Ensure it's the right post type and the user has permission
    if ('owl_sighting' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id))
        return;

    // Save each field if it's present
    if (isset($_POST['owl_species']))
        update_post_meta($post_id, 'owl_species', sanitize_text_field($_POST['owl_species']));

    if (isset($_POST['owl_location']))
        update_post_meta($post_id, 'owl_location', sanitize_text_field($_POST['owl_location']));

    if (isset($_POST['owl_date_spotted']))
        update_post_meta($post_id, 'owl_date_spotted', sanitize_text_field($_POST['owl_date_spotted']));

    // Optionally update the actual post date to match the "date spotted"
    if (!empty($_POST['owl_date_spotted'])) {
        $date = sanitize_text_field($_POST['owl_date_spotted']);
        wp_update_post([
            'ID' => $post_id,
            'post_date' => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ]);
    }

    if (isset($_POST['owl_notes']))
        update_post_meta($post_id, 'owl_notes', sanitize_textarea_field($_POST['owl_notes']));
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
    $pnw_owls = [
        ['name' => 'Barn Owl', 'protected' => false],
        ['name' => 'Barred Owl', 'protected' => false],
        ['name' => 'Burrowing Owl', 'protected' => true],
        ['name' => 'Flammulated Owl', 'protected' => true],
        ['name' => 'Great Gray Owl', 'protected' => true],
        ['name' => 'Great Horned Owl', 'protected' => false],
        ['name' => 'Long-eared Owl', 'protected' => true],
        ['name' => 'Northern Pygmy-Owl', 'protected' => false],
        ['name' => 'Northern Saw-whet Owl', 'protected' => false],
        ['name' => 'Northern Spotted Owl', 'protected' => true],
        ['name' => 'Short-eared Owl', 'protected' => true],
        ['name' => 'Snowy Owl', 'protected' => false],
        ['name' => 'Western Screech-Owl', 'protected' => false],
    ];

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
    $pnw_owls = [
        ['name' => 'Barn Owl', 'protected' => false],
        ['name' => 'Barred Owl', 'protected' => false],
        ['name' => 'Burrowing Owl', 'protected' => true],
        ['name' => 'Flammulated Owl', 'protected' => true],
        ['name' => 'Great Gray Owl', 'protected' => true],
        ['name' => 'Great Horned Owl', 'protected' => false],
        ['name' => 'Long-eared Owl', 'protected' => true],
        ['name' => 'Northern Pygmy-Owl', 'protected' => false],
        ['name' => 'Northern Saw-whet Owl', 'protected' => false],
        ['name' => 'Northern Spotted Owl', 'protected' => true],
        ['name' => 'Short-eared Owl', 'protected' => true],
        ['name' => 'Snowy Owl', 'protected' => false],
        ['name' => 'Western Screech-Owl', 'protected' => false],
    ];

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
    <!-- HTML form for owl sighting submission -->
    <form method="post" enctype="multipart/form-data" id="owl-sighting-form" class="owl-form">
        <?php wp_nonce_field('submit_owl_sighting', 'owl_sighting_nonce'); ?>

        <!-- Owl species dropdown with More Info button -->
        <div class="form-group">
            <label for="owl_species">Species</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <select name="owl_species" id="owl_species" class="form-control" required style="flex: 1;">
                    <option value="">-- Select an Owl --</option>
                    <?php foreach ($pnw_owls as $owl): ?>
                        <option value="<?php echo esc_attr($owl['name']); ?>"
                            data-protected="<?php echo $owl['protected'] ? '1' : '0'; ?>">
                            <?php echo esc_html($owl['name']); ?>
                            <?php if ($owl['protected']) echo ' (protected)'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="lookup-owl" class="btn">🔍 More Info</button>
            </div>
        </div>

        <!-- Wikipedia preview container -->
        <div id="wiki-result" style="margin-top: 1rem; border: 1px solid #ccc; padding: 1em; display: none;"></div>
        <input type="hidden" name="owl_protected" id="protected_species" value="0">

        <!-- Location field (county dropdown) -->
        <div class="form-group">
            <label for="owl_location">County (Washington only)</label>
            <select name="owl_location" id="owl_location" class="form-control" required>
                <option value="">-- Select a County --</option>
                <?php
                $washington_counties = get_washington_counties();
                foreach ($washington_counties as $county) {
                    echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . ' County</option>';
                }
                ?>
            </select>
        </div>

        <!-- Date field -->
        <div class="form-group">
            <label for="owl_date_spotted">Date Spotted</label>
            <input type="date" name="owl_date_spotted" id="owl_date_spotted" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
        </div>

        <!-- Notes field -->
        <div class="form-group">
            <label for="owl_notes">Notes</label>
            <textarea name="owl_notes" id="owl_notes" class="form-control" rows="4"></textarea>
        </div>

        <!-- Photo upload field -->
        <div class="form-group">
            <label for="owl_photo">Photo</label>
            <input type="file" name="owl_photo" id="owl_photo" class="form-control">
        </div>

        <!-- Submit button -->
        <div class="form-actions">
            <button type="submit" class="btn">Submit Sighting</button>
        </div>

        <!-- Side panel (JavaScript-driven Wikipedia display) -->
        <div id="owl_side_panel">
            <div id="owl_side_panel_inner">
                <button id="close_owl_panel">&times;</button>
                <div id="owl_panel_spinner">Loading info...</div>
                <div id="owl_panel_content" style="display: none;">
                    <img id="owl_panel_img" src="" alt="" />
                    <div id="owl_panel_summary"></div>
                    <a id="owl_panel_link" href="#" target="_blank">Read more on Wikipedia</a>
                </div>
            </div>
        </div>
    </form>
    <?php
    return ob_get_clean(); // Return form HTML
});
