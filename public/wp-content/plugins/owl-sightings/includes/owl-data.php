<?php

// Returns an array of PNW owls with protection info
function get_pnw_owls() {
    $owls = [
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

    usort($owls, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $owls;
}