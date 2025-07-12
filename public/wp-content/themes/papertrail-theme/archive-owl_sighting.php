<?php
$context = Timber::context();
$context['title'] = '🦉 Owl Sightings Archive';

$filters = [];
$meta_query = [];

error_log('🧪 GET params: ' . print_r($_GET, true));

// 🦉 Filter by species
if (!empty($_GET['species'])) {
    $meta_query[] = [
        'key'     => 'owl_species',
        'value'   => sanitize_text_field($_GET['species']),
        'compare' => '='
    ];
}
$context['selected_species'] = isset($_GET['species']) ? sanitize_text_field($_GET['species']) : '';

// 🏞️ Filter by location
if (!empty($_GET['location'])) {
    $meta_query[] = [
        'key'     => 'owl_location',
        'value'   => sanitize_text_field($_GET['location']),
        'compare' => '='
    ];
}

// 📅 Filter by date range
if (!empty($_GET['range']) && $_GET['range'] !== 'all') {
    $range = $_GET['range'];
    $date_query = [];

    switch ($range) {
        case 'day':
            $date_query['after'] = '1 day ago';
            break;
        case 'week':
            $date_query['after'] = '1 week ago';
            break;
        case 'month':
            $date_query['after'] = '1 month ago';
            break;
        case 'year':
            $date_query['after'] = '1 year ago';
            break;
    }

    if (!empty($date_query)) {
        $filters['date_query'][] = $date_query;
    }
}

$filters['post_type'] = 'owl_sighting';
$filters['posts_per_page'] = -1;
if (!empty($meta_query)) {
    $filters['meta_query'] = $meta_query;
}

$context['sightings'] = Timber::get_posts($filters);

// 🛡️ List of protected owl species
$protected_species = [
    'Northern Spotted Owl',
    'Long-eared Owl',
    'Short-eared Owl',
    'Burrowing Owl',
    'Great Gray Owl',
    'Flammulated Owl'
];

// 🔍 Build all_species list
$raw_species = array_unique(array_filter(array_map(function ($p) {
    return get_post_meta($p->ID, 'owl_species', true);
}, get_posts(['post_type' => 'owl_sighting', 'numberposts' => -1]))));

// 🔤 Sort alphabetically
sort($raw_species);

// 🛡️ Add (Protected) to display name
$context['all_species'] = array_map(function ($species) use ($protected_species) {
    return in_array($species, $protected_species)
        ? $species . ' (Protected)'
        : $species;
}, $raw_species);

$context['all_locations'] = array_unique(array_filter(array_map(function ($p) {
    return get_post_meta($p->ID, 'owl_location', true);
}, get_posts(['post_type' => 'owl_sighting', 'numberposts' => -1]))));

$context['species'] = $context['all_species'];

Timber::render('archive-owl_sighting.twig', $context);
