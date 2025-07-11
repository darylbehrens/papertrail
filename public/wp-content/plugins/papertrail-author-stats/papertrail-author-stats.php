<?php

/**
 * Plugin Name: Papertrail Author Stats
 * Plugin URI: https://github.com/darylbehrens/papertrail/tree/main/public/wp/wp-content/plugins/papertrail-author-stats
 * Description: Adds feature to show post counts per author.
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

/**
 * Adds [author_stats] shortcode to display a list of authors and their post counts.
 */
add_shortcode('author_stats', function () {
    $authors = get_users(['who' => 'authors']);

    if (empty($authors)) {
        return '<p>No authors found.</p>';
    }

    ob_start();
    echo '<ul class="author-stats">';
    foreach ($authors as $author) {
        $count = count_user_posts($author->ID);
        echo "<li><strong>{$author->display_name}</strong>: {$count} posts</li>";
    }
    echo '</ul>';
    return ob_get_clean();
});

class Author_Stats_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'author_stats_widget',
            'Author Stats',
            ['description' => 'Displays a list of authors and their post counts.']
        );
    }

    public function widget($args, $instance)
    {
        $authors = get_users(['who' => 'authors']);

        echo $args['before_widget'];
        echo $args['before_title'] . 'Author Stats' . $args['after_title'];

        echo '<ul>';
        foreach ($authors as $author) {
            $count = count_user_posts($author->ID);
            echo "<li><strong>{$author->display_name}</strong>: {$count}</li>";
        }
        echo '</ul>';

        echo $args['after_widget'];
    }
}

function register_author_stats_widget()
{
    register_widget('Author_Stats_Widget');
}
add_action('widgets_init', 'register_author_stats_widget');

function papertrail_widget_styles()
{
    echo '<style>
        .widget_author_stats ul {
            list-style-type: none;
            padding: 0;
        }
        .widget_author_stats li {
            padding: 2px 0;
            font-weight: 500;
        }
    </style>';
}
add_action('wp_head', 'papertrail_widget_styles');
