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

add_filter('use_curl_transport', '__return_true');

add_shortcode('list_owl_sightings', function () {
    $query = new WP_Query([
        'post_type' => 'owl_sighting',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    if (!$query->have_posts())
        return '<p>No sightings found.</p>';

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

function owl_sightings_register_post_type()
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
        'has_archive' => true,
        'rewrite' => ['slug' => 'owl-sightings'],
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
    ];

    register_post_type('owl_sighting', $args);
}

add_action('init', 'owl_sightings_register_post_type');

add_action('add_meta_boxes', 'owl_sighting_add_meta_boxes');

function owl_sighting_add_meta_boxes()
{
    add_meta_box(
        'owl_sighting_details',
        'Owl Sighting Details',
        'owl_sighting_meta_box_html',
        'owl_sighting',
        'normal',
        'high'
    );
}

function owl_sighting_meta_box_html($post)
{
    $selected_species = get_post_meta($post->ID, '_owl_species', true);
    $location = get_post_meta($post->ID, '_owl_location', true);
    $date_spotted = get_post_meta($post->ID, '_owl_date_spotted', true);
    $notes = get_post_meta($post->ID, '_owl_notes', true);
    wp_nonce_field('owl_sighting_nonce_action', 'owl_sighting_nonce');
    ?>
    <p>
        <label for="owl_species">Species:</label><br>
        <select name="owl_species" id="owl_species" class="regular-text"></select>
    </p>

    <p>
        <button type="button" id="more_owl_info" class="button">More Info</button>
    </p>

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



    <p>
        <label for="owl_location">Location:</label><br>
        <input type="text" name="owl_location" id="owl_location" value="<?php echo esc_attr($location); ?>"
            class="regular-text">
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
        document.addEventListener('DOMContentLoaded', function () {
            const speciesDropdown = document.getElementById('owl_species');
            const moreInfoBtn = document.getElementById('more_owl_info');

            const sidePanel = document.getElementById('owl_side_panel');
            const panelTitle = document.getElementById('owl_panel_title');
            const panelImg = document.getElementById('owl_panel_img');
            const panelSummary = document.getElementById('owl_panel_summary');
            const panelLink = document.getElementById('owl_panel_link');
            const closePanel = document.getElementById('close_owl_panel');
            const spinner = document.getElementById('owl_panel_spinner');

            if (speciesDropdown && moreInfoBtn && sidePanel && closePanel) {
                moreInfoBtn.addEventListener('click', () => {
                    const species = speciesDropdown.value;

                    // Clear previous content and show spinner
                    panelTitle.textContent = '';
                    panelImg.src = '';
                    panelSummary.textContent = '';
                    panelLink.href = '#';
                    spinner.style.display = 'block';

                    sidePanel.classList.add('open');



                    fetch(`/wp-json/owl/v1/info?species=${encodeURIComponent(species)}`)
                        .then(res => res.json())
                        .then(data => {
                            spinner.style.display = 'none';
                            panelTitle.textContent = species;
                            panelImg.src = data.image || '';
                            panelSummary.textContent = (data.fact || '').slice(0, 500);
                            panelLink.href = `https://en.wikipedia.org/wiki/${encodeURIComponent(species.replace(/ /g, '_'))}`;
                        });
                });

                closePanel.addEventListener('click', () => {
                    sidePanel.classList.remove('open');
                });
            } else {
                console.warn('Some owl UI elements were not found in the DOM.');
            }

            fetch('<?php echo esc_url_raw(rest_url('owl/v1/species')); ?>')
                .then(res => res.json())
                .then(speciesList => {
                    speciesDropdown.innerHTML = speciesList.map(species =>
                        `<option value="${species}" ${species === '<?php echo esc_js($selected_species); ?>' ? 'selected' : ''}>${species}</option>`
                    ).join('');
                });

        });



    </script>
    <style>
        #owl_side_panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 350px;
            height: 100%;
            background: #f8f9fa;
            /* soft gray */
            box-shadow: -4px 0 12px rgba(0, 0, 0, 0.2);
            padding: 0;
            transition: right 0.3s ease-in-out;
            overflow-y: auto;
            z-index: 10000;
            border-left: 1px solid #ddd;
        }

        #owl_side_panel.open {
            right: 0;
        }

        #owl_side_panel_inner {
            padding: 1.5rem;
            font-family: system-ui, sans-serif;
        }

        #close_owl_panel {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.8rem;
            font-weight: bold;
            color: #444;
            cursor: pointer;
        }

        #owl_panel_spinner {
            text-align: center;
            padding: 2rem;
            font-size: 1.1rem;
            color: #666;
            display: none;
        }

        #owl_panel_img {
            max-width: 100%;
            height: auto;
            margin: 1rem 0;
            border-radius: 4px;
        }

        #owl_panel_summary {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #333;
        }

        #owl_panel_link {
            display: inline-block;
            margin-top: 1rem;
            font-weight: bold;
            color: #0066cc;
            text-decoration: none;
        }

        #owl_panel_link:hover {
            text-decoration: underline;
        }

        #owl_side_panel_inner {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
    </style>


    <?php
}

add_action('save_post', 'owl_sighting_save_meta_boxes');

function owl_sighting_save_meta_boxes($post_id)
{
    if (!isset($_POST['owl_sighting_nonce']) || !wp_verify_nonce($_POST['owl_sighting_nonce'], 'owl_sighting_nonce_action'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if ('owl_sighting' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id))
        return;

    if (isset($_POST['owl_species']))
        update_post_meta($post_id, '_owl_species', sanitize_text_field($_POST['owl_species']));
    if (isset($_POST['owl_location']))
        update_post_meta($post_id, '_owl_location', sanitize_text_field($_POST['owl_location']));
    if (isset($_POST['owl_date_spotted']))
        update_post_meta($post_id, '_owl_date_spotted', sanitize_text_field($_POST['owl_date_spotted']));
    if (isset($_POST['owl_notes']))
        update_post_meta($post_id, '_owl_notes', sanitize_textarea_field($_POST['owl_notes']));
}

add_action('rest_api_init', function () {
    register_rest_route('owl/v1', '/species', [
        'methods' => 'GET',
        'callback' => 'get_fake_owl_species'
    ]);

    register_rest_route('owl/v1', '/info', [
        'methods' => 'GET',
        'callback' => 'get_fake_owl_info',
        'permission_callback' => '__return_true'
    ]);
});

function get_fake_owl_species()
{
    return ['Great Horned Owl', 'Northern Saw-whet Owl', 'Barn Owl', 'Barred Owl', 'Snowy Owl', 'Great Gray Owl', 'Spotted Owl', 'Western Screech Owl'];
}

function get_fake_owl_info($request)
{
    $species = $request->get_param('species');
    return fetch_wikipedia_owl_info($species);
}




function fetch_wikipedia_owl_info($species)
{
    $primaryQuery = urlencode($species);
    $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$primaryQuery";

    $result = wikipedia_request($url);

    // If no extract, retry with fallback format (underscored, lowercased)
    if (!$result || empty($result['fact']) || $result['fact'] === 'No summary available.') {
        $fallback = str_replace(' ', '_', strtolower($species));
        $url = "https://en.wikipedia.org/api/rest_v1/page/summary/$fallback";
        error_log("🔁 Retrying with fallback: $url");
        $result = wikipedia_request($url);
    }

    return $result ?? [
        'fact' => "No Wikipedia summary available for '$species'.",
        'image' => null,
    ];
}

function wikipedia_request($url)
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

    // Detect if it's a valid article
    if (isset($data['type']) && $data['type'] === 'standard') {
        return [
            'fact' => $data['extract'] ?? 'No summary available.',
            'image' => $data['thumbnail']['source'] ?? null,
        ];
    }

    return null;
}



register_activation_hook(__FILE__, 'flush_owl_rewrites');
function flush_owl_rewrites()
{
    owl_sightings_register_post_type();
    flush_rewrite_rules();
}
