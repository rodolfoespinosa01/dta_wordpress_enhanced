<?php

// Enqueue parent and child theme styles
function astra_child_enqueue_styles() {
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('astra-child-style', get_stylesheet_directory_uri() . '/style.css', array('astra-parent-style'));
}
add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');

add_action('rest_api_init', function() {
    error_log('REST API Initialized');
});

add_filter('rest_pre_echo_response', function($result, $server, $request) {
    error_log('REST API Response: ' . print_r($result, true));
    return $result;
}, 10, 3);

