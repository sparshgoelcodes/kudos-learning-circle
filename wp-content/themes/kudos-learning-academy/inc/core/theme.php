<?php

/* =========================================
   KUDOS THEME ASSETS
   ========================================= */

function kudos_assets() {

    wp_enqueue_style(
        'kudos-style',
        get_stylesheet_uri(),
        array(),
        '1.0'
    );
}

add_action('wp_enqueue_scripts', 'kudos_assets');


/* =========================================
   THEME SUPPORT
   ========================================= */

add_theme_support('title-tag');


/* =========================================
   HIDE WORDPRESS ADMIN BAR ON FRONTEND
   ========================================= */

add_filter('show_admin_bar', '__return_false');


