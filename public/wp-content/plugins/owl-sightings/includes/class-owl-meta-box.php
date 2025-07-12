<?php

class OwlSightingMetaBox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post', [$this, 'save_meta_box']);
    }

    public function register_meta_box()
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

    public function render_meta_box($post)
    {
        // Prepare variables
        $selected_species = get_post_meta($post->ID, 'owl_species', true);
        $location = get_post_meta($post->ID, 'owl_location', true);
        $date_spotted = get_post_meta($post->ID, 'owl_date_spotted', true);
        $notes = get_post_meta($post->ID, 'owl_notes', true);

        // Make them available in included file
        $meta_vars = compact('selected_species', 'location', 'date_spotted', 'notes');
        extract($meta_vars);

        // Render
        wp_nonce_field('owl_sighting_nonce_action', 'owl_sighting_nonce');
        include plugin_dir_path(__FILE__) . '/../partials/meta-box-fields.php';
    }

    public function save_meta_box($post_id)
    {
        if (!isset($_POST['owl_sighting_nonce']) || !wp_verify_nonce($_POST['owl_sighting_nonce'], 'owl_sighting_nonce_action')) {
            return;
        }

        $fields = ['owl_species', 'owl_location', 'owl_date_spotted', 'owl_notes'];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}
