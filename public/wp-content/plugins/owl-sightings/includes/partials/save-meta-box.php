<?php
/**
 * File: save-meta-box.php
 * Description: Handles saving of custom meta fields for owl sightings.
 * Loaded by OwlSightingsPlugin::save_meta_boxes()
 */

$post_id = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;

if (!$post_id) {
    return; // Nothing to do
}

// Verify the nonce to ensure the request is legitimate
if (!isset($_POST['owl_sighting_nonce']) || !wp_verify_nonce($_POST['owl_sighting_nonce'], 'submit_owl_sighting')) {
    return;
}

// Prevent auto-saves from triggering this logic
if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
}

// Check that this is the correct post type and user has permission to edit
if ('owl_sighting' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
    return;
}

// Save owl species (dropdown)
if (isset($_POST['owl_species'])) {
    update_post_meta($post_id, 'owl_species', sanitize_text_field($_POST['owl_species']));
}

// Save location (text input)
if (isset($_POST['owl_location'])) {
    update_post_meta($post_id, 'owl_location', sanitize_text_field($_POST['owl_location']));
}

// Save sighting date and also set it as the post publish date
if (isset($_POST['owl_date_spotted'])) {
    $date = sanitize_text_field($_POST['owl_date_spotted']);
    update_post_meta($post_id, 'owl_date_spotted', $date);

    if (!empty($date)) {
        wp_update_post([
            'ID' => $post_id,
            'post_date' => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ]);
    }
}

// Save notes (textarea)
if (isset($_POST['owl_notes'])) {
    update_post_meta($post_id, 'owl_notes', sanitize_textarea_field($_POST['owl_notes']));
}

// Save uploaded owl photo if provided
if (
    isset($_FILES['owl_photo']) &&
    is_array($_FILES['owl_photo']) &&
    $_FILES['owl_photo']['size'] > 0
) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('owl_photo', $post_id);

    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
        update_post_meta($post_id, 'owl_photo_id', $attachment_id);
    }
}
