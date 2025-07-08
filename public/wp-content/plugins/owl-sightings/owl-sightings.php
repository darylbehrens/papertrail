<?php
/**
 * Plugin Name: Owl Sightings
 * Plugin URI: https://github.com/darylbehrens/papertrail/tree/main/public/wp/wp-content/plugins/owl-sightings
 * Description: Adds a custom post type for recording owl sightings.
 * Version: 1.0
 * Author: Daryl Behrens
 * License: GPL2
 */

defined('ABSPATH') or die('No script kiddies please!');

add_shortcode('list_owl_sightings', function () {
    $query = new WP_Query([
        'post_type' => 'owl_sighting',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    if (!$query->have_posts()) return '<p>No sightings found.</p>';

    ob_start();
    echo '<ul>';
    while ($query->have_posts()) {
        $query->the_post();
        echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }
    echo '</ul>';
    wp_reset_postdata();
    return ob_get_clean();
});

function owl_sightings_register_post_type() {
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
        'has_archive' => true,
        'rewrite' => ['slug' => 'owl-sightings'],
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
    ];

    register_post_type('owl_sighting', $args);
}

add_action('init', 'owl_sightings_register_post_type');

add_action('add_meta_boxes', 'owl_sighting_add_meta_boxes');

function owl_sighting_add_meta_boxes() {
    add_meta_box(
        'owl_sighting_details',
        'Owl Sighting Details',
        'owl_sighting_meta_box_html',
        'owl_sighting',
        'normal',
        'high'
    );
}

function owl_sighting_meta_box_html($post) {
    $selected_species = get_post_meta($post->ID, '_owl_species', true);
    $location = get_post_meta($post->ID, '_owl_location', true);
    $date_spotted = get_post_meta($post->ID, '_owl_date_spotted', true);
    $notes = get_post_meta($post->ID, '_owl_notes', true);
    wp_nonce_field('owl_sighting_nonce_action', 'owl_sighting_nonce');
    ?>
    <p>
        <label for="owl_species">Species:</label><br>
        <select name="owl_species" id="owl_species" class="regular-text"></select>
        <button type="button" id="check_sightings_btn" class="button">Check Sightings</button>
        <span id="sighting_count"></span>
    </p>
    <p>
        <label for="owl_location">Location:</label><br>
        <input type="text" name="owl_location" id="owl_location" value="<?php echo esc_attr($location); ?>" class="regular-text">
    </p>
    <p>
        <label for="owl_date_spotted">Date Spotted:</label><br>
        <input type="date" name="owl_date_spotted" id="owl_date_spotted" value="<?php echo esc_attr($date_spotted); ?>">
    </p>
    <p>
        <label for="owl_notes">Notes:</label><br>
        <textarea name="owl_notes" id="owl_notes" rows="5" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
    </p>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const speciesDropdown = document.getElementById('owl_species');
    const checkBtn = document.getElementById('check_sightings_btn');
    const sightingCount = document.getElementById('sighting_count');

    // Fetch species list from API
    fetch('<?php echo esc_url_raw(rest_url('owl/v1/species')); ?>')
        .then(response => response.json())
        .then(speciesList => {
            speciesDropdown.innerHTML = speciesList.map(species => 
                `<option value="${species}" ${species === '<?php echo esc_js($selected_species); ?>' ? 'selected' : ''}>${species}</option>`
            ).join('');
        })
        .catch(err => console.error('Error fetching species:', err));

    // Check sightings count when button is clicked
    checkBtn.addEventListener('click', function() {
        const selectedSpecies = speciesDropdown.value;
        const url = '<?php echo esc_url_raw(rest_url('owl/v1/sightings')); ?>?species=' + encodeURIComponent(selectedSpecies);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                sightingCount.textContent = `Seen ${data.count} times within 30 miles in the last month.`;
            })
            .catch(err => console.error('Error fetching sighting count:', err));
    });
});
</script>
    <?php
}

add_action('save_post', 'owl_sighting_save_meta_boxes');

function owl_sighting_save_meta_boxes($post_id) {
    // Verify nonce
    if (!isset($_POST['owl_sighting_nonce']) || !wp_verify_nonce($_POST['owl_sighting_nonce'], 'owl_sighting_nonce_action')) {
        return;
    }

    // Prevent autosaves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Permissions check
    if ('owl_sighting' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save data
    if (isset($_POST['owl_species'])) {
        update_post_meta($post_id, '_owl_species', sanitize_text_field($_POST['owl_species']));
    }

    if (isset($_POST['owl_location'])) {
        update_post_meta($post_id, '_owl_location', sanitize_text_field($_POST['owl_location']));
    }

    if (isset($_POST['owl_date_spotted'])) {
        update_post_meta($post_id, '_owl_date_spotted', sanitize_text_field($_POST['owl_date_spotted']));
    }

    if (isset($_POST['owl_notes'])) {
        update_post_meta($post_id, '_owl_notes', sanitize_textarea_field($_POST['owl_notes']));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('owl/v1', '/species', [
        'methods' => 'GET',
        'callback' => 'get_fake_owl_species'
    ]);

    register_rest_route('owl/v1', '/sightings', [
        'methods' => 'GET',
        'callback' => 'get_fake_sighting_count'
    ]);
});

function get_fake_owl_species() {
    return ['Great Horned Owl', 'Northern Saw-whet Owl', 'Barn Owl', 'Barred Owl', 'Snowy Owl', 'Great Gray Owl', 'Spotted Owl', 'Western Screech Owl'];
}

function get_fake_sighting_count($request) {
    $species = $request->get_param('species');

    $fake_counts = [
        'Great Horned Owl' => 12,
        'Northern Saw-whet Owl' => 4,
        'Barn Owl' => 7,
        'Barred Owl' => 9,
        'Snowy Owl' => 2,
        'Great Gray Owl' => 1,
        'Spotted Owl' => 3,
        'Western Screech Owl' => 5
    ];

    return ['species' => $species, 'count' => $fake_counts[$species] ?? 0];
}

register_activation_hook(__FILE__, 'flush_owl_rewrites');
function flush_owl_rewrites() {
    // Ensure CPT is registered first
    owl_sightings_register_post_type(); // or whatever your function is
    flush_rewrite_rules();
}

