<?php

// enqueue styles for child theme
function example_enqueue_styles() {

    // enqueue child styles
    wp_enqueue_style('child-theme-style', get_stylesheet_directory_uri() . '/style.css', array());
    wp_enqueue_script('theme_js', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '1.0', true);
}

add_action('wp_enqueue_scripts', 'example_enqueue_styles');
?>