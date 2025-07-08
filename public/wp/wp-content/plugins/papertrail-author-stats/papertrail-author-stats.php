<?php
/**
 * Plugin Name: Papertrail Author Stats
 * Plugin URI: https://github.com/darylbehrens/papertrail/tree/main/public/wp/wp-content/plugins/papertrail-author-stats
 * Description: Adds a dashboard widget showing post counts per author.
 * Version: 1.0
 * Author: Daryl Behrens
 * License: GPL2
 */

add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('author_stats_widget', 'Author Stats', function () {
        $authors = get_users(['who' => 'authors']);
        echo '<ul>';
        foreach ($authors as $author) {
            $count = count_user_posts($author->ID);
            echo "<li><strong>{$author->display_name}</strong>: {$count} posts</li>";
        }
        echo '</ul>';
    });
});
