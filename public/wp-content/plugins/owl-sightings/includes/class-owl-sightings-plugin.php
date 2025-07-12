<?php

/**
 * Class OwlSightingsPlugin
 * Handles custom post type, meta boxes, REST routes, and shortcode rendering for owl sightings.
 */

require_once plugin_dir_path(__FILE__) . 'owl-data.php';

class OwlSightingsPlugin
{
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        add_shortcode('owl_sighting_form', [$this, 'render_form']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('init', [$this, 'handle_form_submission']);
    }

    // Register the "Owl Sighting" custom post type
    public function register_post_type()
    {
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

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => 'sightings',
            'rewrite' => ['slug' => 'sightings'],
            'supports' => ['title', 'editor', 'custom-fields'],
            'show_in_rest' => true,
        ];

        register_post_type('owl_sighting', $args);
    }

    // Add custom meta boxes for owl sighting details
    public function add_meta_boxes()
    {
        add_meta_box(
            'owl_sighting_details',
            'Owl Sighting Details',
            [$this, 'render_meta_box'],
            'owl_sighting',
            'normal',
            'high'
        );
    }

    // HTML for the meta box fields
    public function save_meta_boxes($post_id)
    {
        require_once plugin_dir_path(__FILE__) . 'partials/save-meta-box.php';
    }

    // Render the front-end submission form via shortcode
    public function render_form()
    {
        ob_start();
        require plugin_dir_path(__FILE__) . 'partials/form.php';
        return ob_get_clean();
    }

    public function handle_form_submission()
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['owl_sighting_nonce']) &&
            wp_verify_nonce($_POST['owl_sighting_nonce'], 'submit_owl_sighting')
        ) {
            $title = sanitize_text_field($_POST['owl_species']);
            $location = sanitize_text_field($_POST['owl_location']);
            $raw_date = sanitize_text_field($_POST['owl_date_spotted']);
            $date_only = substr($raw_date, 0, 10); // Trim time
            $protected = isset($_POST['owl_protected']) ? sanitize_text_field($_POST['owl_protected']) : '0';
            $notes = sanitize_textarea_field($_POST['owl_notes']);

            if (strtotime($date_only) > strtotime(date('Y-m-d'))) {
                wp_die('❌ The date cannot be in the future.');
            }

            $post_id = wp_insert_post([
                'post_title' => $title . ' sighting',
                'post_type' => 'owl_sighting',
                'post_status' => 'publish',
                'post_content' => $notes,
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'owl_species', $title);
                update_post_meta($post_id, 'owl_date_spotted', $date_only);
                update_post_meta($post_id, 'owl_notes', $notes);
                update_post_meta($post_id, 'owl_protected', $protected);
                update_post_meta($post_id, 'owl_location', $location);

                if (!empty($_FILES['owl_photo']) && !empty($_FILES['owl_photo']['name'])) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';

                    $attachment_id = media_handle_upload('owl_photo', $post_id);

                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                        $meta = wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id));
                        wp_update_attachment_metadata($attachment_id, $meta);
                    }
                }

                wp_redirect(site_url('/sightings'));
                exit;
            }
        }
    }

    // Register REST API endpoints for owl species and info
    public function register_rest_routes()
    {
        register_rest_route('owl/v1', '/species', [
            'methods' => 'GET',
            'callback' => [$this, 'get_species']
        ]);

        register_rest_route('owl/v1', '/info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_species_info'],
            'permission_callback' => '__return_true'
        ]);
    }

    // Return owl species list
    public function get_species()
    {
        return get_pnw_owls();
    }

    // Return owl info using Wikipedia
    public function get_species_info($request)
    {
        return $this->fetch_wikipedia_owl_info($request->get_param('species'));
    }

    private function fetch_wikipedia_owl_info($species)
    {
        $primaryQuery = urlencode($species);
        $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$primaryQuery";

        $result = $this->wikipedia_request($url);

        if (!$result || empty($result['fact']) || $result['fact'] === 'No summary available.') {
            $fallback = str_replace(' ', '_', strtolower($species));
            $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$fallback";
            error_log("🔁 Retrying with fallback: $url");
            $result = $this->wikipedia_request($url);
        }

        return $result ?? [
            'fact' => "No Wikipedia summary available for '$species'.",
            'image' => null,
        ];
    }

    private function wikipedia_request($url)
    {
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            error_log("🛑 Wikipedia request error: " . $response->get_error_message());
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['type']) && $data['type'] === 'standard') {
            return [
                'fact' => $data['extract'] ?? 'No summary available.',
                'image' => $data['thumbnail']['source'] ?? null,
            ];
        }

        return null;
    }
}
