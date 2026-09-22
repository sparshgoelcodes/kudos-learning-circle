<?php
/**
 * Template Name: Teacher Dashboard
 * Template Post Type: page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>

    <style>
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-width: 0 !important;
            background: #fffaf0 !important;
            overflow-x: hidden !important;
        }

        body.kudos-teacher-dashboard-page .site-header,
        body.kudos-teacher-dashboard-page .site-footer,
        body.kudos-teacher-dashboard-page .page-title-area,
        body.kudos-teacher-dashboard-page .breadcrumb-area,
        body.kudos-teacher-dashboard-page .page-header {
            display: none !important;
        }

        body.kudos-teacher-dashboard-page .site,
        body.kudos-teacher-dashboard-page .site-main,
        body.kudos-teacher-dashboard-page main,
        body.kudos-teacher-dashboard-page .site-content,
        body.kudos-teacher-dashboard-page .content-area,
        body.kudos-teacher-dashboard-page .page-wrapper,
        body.kudos-teacher-dashboard-page .site-page,
        body.kudos-teacher-dashboard-page .page-content,
        body.kudos-teacher-dashboard-page .page-body,
        body.kudos-teacher-dashboard-page .entry-content,
        body.kudos-teacher-dashboard-page article,
        body.kudos-teacher-dashboard-page article > .container {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
        }

        body.kudos-teacher-dashboard-page .kudos-teacher-dashboard {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
            background: #fffaf0 !important;
        }

        body.kudos-teacher-dashboard-page .teacher-dashboard-header {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        /*
         * IMPORTANT:
         * The real Teacher Tools module is injected through WordPress
         * the_content filter. Its own compact-layout JavaScript/CSS
         * handles the sidebar, Performance and Attendance panels.
         *
         * Do NOT recreate those panels here.
         */
        body.kudos-teacher-dashboard-page .kla-teacher-tools {
            width: 100% !important;
            max-width: 1180px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 760px) {
            body.kudos-teacher-dashboard-page .kla-teacher-tools {
                width: calc(100% - 28px) !important;
            }
        }
    </style>
</head>

<body <?php body_class( 'kudos-teacher-dashboard-page' ); ?>>

<?php

if (
    is_user_logged_in()
    && in_array(
        'kudos_teacher',
        (array) wp_get_current_user()->roles,
        true
    )
) {

    /*
     * DO NOT call do_shortcode() directly here.
     *
     * teacher-tools.php attaches itself to the_content filter.
     * Calling the shortcode directly bypasses that filter, which is
     * why Performance and Attendance were showing "Coming Next".
     */
    $teacher_dashboard_html = do_shortcode(
        '[kudos_teacher_dashboard]'
    );

    echo apply_filters(
        'the_content',
        $teacher_dashboard_html
    );

} else {

    echo do_shortcode(
        '[kudos_teacher_login]'
    );
}

?>

<?php wp_footer(); ?>

</body>
</html>
