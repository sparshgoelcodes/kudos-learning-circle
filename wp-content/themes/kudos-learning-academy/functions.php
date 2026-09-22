<?php
/**
 * Kudos Learning Academy
 * Modular Theme Functions
 */

require_once get_template_directory() . '/inc/core/theme.php';

require_once get_template_directory() . '/inc/activities/activities.php';
require_once get_template_directory() . '/inc/students/students.php';
require_once get_template_directory() . '/inc/performance/performance.php';

require_once get_template_directory() . '/inc/teachers/teachers.php';
require_once get_template_directory() . '/inc/teachers/teacher-tools.php';
require_once get_template_directory() . '/inc/teachers/teacher-performance.php';
require_once get_template_directory() . '/inc/teachers/teacher-attendance.php';
require_once get_template_directory() . '/inc/teachers/teacher-my-students.php';

require_once get_template_directory() . '/inc/parents/parents.php';
require_once get_template_directory() . '/inc/parents/parent-dashboard-compact.php';
require_once get_template_directory() . '/inc/parents/parent-learning-snapshot.php';

require_once get_template_directory() . '/inc/rewards/rewards.php';

require_once get_template_directory() . '/inc/fees/fees.php';
require_once get_template_directory() . '/inc/enrollments/enrollments.php';

require_once get_template_directory() . '/inc/notifications/notifications.php';

require_once get_template_directory() . '/inc/admin/admin-dashboard.php';

require_once get_template_directory() . '/inc/core/enquiries.php';




/**
 * Load main theme stylesheet
 */
function kudos_enqueue_theme_assets() {

    $style_file = get_stylesheet_directory() . '/style.css';

    wp_enqueue_style(
        'kudos-main-style',
        get_stylesheet_directory_uri() . '/style.css',
        array(),
        file_exists( $style_file ) ? filemtime( $style_file ) : time()
    );
}

add_action(
    'wp_enqueue_scripts',
    'kudos_enqueue_theme_assets',
    999
);


/**
 * Hide WordPress admin bar on frontend
 */
add_filter(
    'show_admin_bar',
    '__return_false'
);