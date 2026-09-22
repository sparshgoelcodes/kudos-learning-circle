<?php
/**
 * Kudos Learning Academy
 * Parent Notifications Module
 *
 * Standalone notification center for parent accounts.
 * Creates notifications from:
 * - Kudos awards
 * - Kudos redemptions
 * - Performance updates
 * - Attendance updates
 *
 * Does not replace parents.php or the existing teacher modules.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * Notification CPT
 * ------------------------------------------------------------------------- */

function kla_register_parent_notifications() {
    register_post_type(
        'kla_notification',
        array(
            'labels' => array(
                'name'          => 'Notifications',
                'singular_name' => 'Notification',
                'menu_name'     => 'Notifications',
                'add_new'       => 'Add Notification',
                'edit_item'     => 'Edit Notification',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 27,
            'menu_icon'           => 'dashicons-bell',
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'supports'            => array( 'title', 'editor' ),
            'show_in_rest'        => false,
        )
    );
}
add_action( 'init', 'kla_register_parent_notifications' );

/* -------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */

function kla_notification_student_parent_id( $student_id ) {
    $student_id = absint( $student_id );

    if ( ! $student_id || 'kudos_student' !== get_post_type( $student_id ) ) {
        return 0;
    }

    return absint( get_post_meta( $student_id, '_kudos_student_parent', true ) );
}

function kla_notification_student_name( $student_id ) {
    $name = get_post_meta( $student_id, '_kudos_student_name', true );

    return $name ? $name : get_the_title( $student_id );
}

function kla_notification_teacher_name( $teacher_id = 0 ) {
    $teacher_id = absint( $teacher_id );

    if ( $teacher_id ) {
        $teacher = get_user_by( 'id', $teacher_id );

        if ( $teacher ) {
            return $teacher->display_name ? $teacher->display_name : $teacher->user_login;
        }
    }

    $current = wp_get_current_user();

    if ( $current && $current->ID ) {
        return $current->display_name ? $current->display_name : $current->user_login;
    }

    return 'Teacher';
}

/**
 * Create one parent notification.
 *
 * Duplicate protection is based on source post + source event key.
 */
function kla_create_parent_notification( $args = array() ) {
    $defaults = array(
        'parent_id'    => 0,
        'student_id'   => 0,
        'type'         => 'general',
        'title'        => '',
        'message'      => '',
        'source_id'    => 0,
        'source_key'   => '',
        'icon'         => '🔔',
    );

    $args = wp_parse_args( $args, $defaults );

    $parent_id  = absint( $args['parent_id'] );
    $student_id = absint( $args['student_id'] );
    $source_id  = absint( $args['source_id'] );

    if ( ! $parent_id || ! $student_id || ! $args['title'] || ! $args['message'] ) {
        return 0;
    }

    if ( ! $args['source_key'] ) {
        $args['source_key'] = $args['type'] . '_' . $source_id;
    }

    $existing = new WP_Query(
        array(
            'post_type'      => 'kla_notification',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_kla_notification_parent_id',
                    'value'   => $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kla_notification_source_key',
                    'value'   => sanitize_text_field( $args['source_key'] ),
                    'compare' => '=',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    if ( ! empty( $existing->posts ) ) {
        return absint( $existing->posts[0] );
    }

    $notification_id = wp_insert_post(
        array(
            'post_title'   => wp_strip_all_tags( $args['title'] ),
            'post_content' => wp_kses_post( $args['message'] ),
            'post_status'  => 'publish',
            'post_type'    => 'kla_notification',
        ),
        true
    );

    if ( is_wp_error( $notification_id ) ) {
        return 0;
    }

    update_post_meta( $notification_id, '_kla_notification_parent_id', $parent_id );
    update_post_meta( $notification_id, '_kla_notification_student_id', $student_id );
    update_post_meta( $notification_id, '_kla_notification_type', sanitize_key( $args['type'] ) );
    update_post_meta( $notification_id, '_kla_notification_source_id', $source_id );
    update_post_meta( $notification_id, '_kla_notification_source_key', sanitize_text_field( $args['source_key'] ) );
    update_post_meta( $notification_id, '_kla_notification_icon', sanitize_text_field( $args['icon'] ) );
    update_post_meta( $notification_id, '_kla_notification_read', 0 );
    update_post_meta( $notification_id, '_kla_notification_created_at', current_time( 'mysql' ) );

    return absint( $notification_id );
}

/* -------------------------------------------------------------------------
 * Notification: Kudos Award / Redemption
 * ------------------------------------------------------------------------- */

function kla_notification_from_transaction_legacy( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || 'kudos_transaction' !== $post->post_type ) {
        return;
    }

    if ( 'publish' !== $post->post_status ) {
        return;
    }

    $student_id = absint( get_post_meta( $post_id, '_kla_transaction_student_id', true ) );
    $parent_id  = absint( get_post_meta( $post_id, '_kla_transaction_parent_id', true ) );

    if ( ! $student_id ) {
        return;
    }

    if ( ! $parent_id ) {
        $parent_id = kla_notification_student_parent_id( $student_id );
    }

    if ( ! $parent_id ) {
        return;
    }

    $type       = strtolower( get_post_meta( $post_id, '_kla_transaction_type', true ) );
    $points     = absint( get_post_meta( $post_id, '_kla_transaction_points', true ) );
    $reason     = get_post_meta( $post_id, '_kla_transaction_reason', true );
    $new_balance = get_post_meta( $post_id, '_kla_transaction_new_balance', true );
    $activity_id = absint( get_post_meta( $post_id, '_kla_transaction_activity_id', true ) );

    $student_name = kla_notification_student_name( $student_id );

    if ( in_array( $type, array( 'redemption', 'redeem' ), true ) ) {
        $activity_name = $activity_id ? get_the_title( $activity_id ) : '';

        $title = $student_name . ' redeemed ' . $points . ' Kudos';

        $message = 'Redeemed';
        if ( $activity_name ) {
            $message .= ' for ' . $activity_name;
        }
        if ( $new_balance !== '' ) {
            $message .= '. Remaining Kudos: ' . absint( $new_balance ) . '.';
        }

        kla_create_parent_notification(
            array(
                'parent_id'  => $parent_id,
                'student_id' => $student_id,
                'type'       => 'kudos_redemption',
                'title'      => $title,
                'message'    => $message,
                'source_id'  => $post_id,
                'source_key' => 'transaction_' . $post_id,
                'icon'       => '🎁',
            )
        );

        return;
    }

    $title = $student_name . ' earned ' . $points . ' Kudos';

    $message = $reason ? $reason : 'A new Kudos reward was added.';
    if ( $new_balance !== '' ) {
        $message .= ' Current Kudos balance: ' . absint( $new_balance ) . '.';
    }

    kla_create_parent_notification(
        array(
            'parent_id'  => $parent_id,
            'student_id' => $student_id,
            'type'       => 'kudos_award',
            'title'      => $title,
            'message'    => $message,
            'source_id'  => $post_id,
            'source_key' => 'transaction_' . $post_id,
            'icon'       => '⭐',
        )
    );
}


/* -------------------------------------------------------------------------
 * Notification: Performance update
 * ------------------------------------------------------------------------- */

function kla_notification_from_performance_legacy( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || 'kudos_performance' !== $post->post_type ) {
        return;
    }

    if ( 'publish' !== $post->post_status ) {
        return;
    }

    $student_id = absint( get_post_meta( $post_id, '_kudos_performance_student', true ) );

    if ( ! $student_id ) {
        $student_name_meta = get_post_meta( $post_id, '_kudos_performance_student_name', true );

        if ( $student_name_meta ) {
            $matches = get_posts(
                array(
                    'post_type'      => 'kudos_student',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'     => '_kudos_student_name',
                            'value'   => $student_name_meta,
                            'compare' => '=',
                        ),
                    ),
                    'no_found_rows'  => true,
                )
            );

            if ( ! empty( $matches ) ) {
                $student_id = absint( $matches[0] );
            }
        }
    }

    if ( ! $student_id ) {
        return;
    }

    $parent_id = kla_notification_student_parent_id( $student_id );

    if ( ! $parent_id ) {
        return;
    }

    /*
     * Use modified timestamp so repeated autosaves/technical saves
     * do not create multiple notifications for the same update.
     */
    $modified = get_post_modified_time( 'U', true, $post_id );

    if ( ! $modified ) {
        return;
    }

    $source_key = 'performance_' . $post_id . '_' . $modified;

    $student_name = kla_notification_student_name( $student_id );
    $teacher_name = get_post_meta( $post_id, '_kudos_performance_updated_by_name', true );

    if ( ! $teacher_name ) {
        $teacher_id = absint( get_post_meta( $post_id, '_kudos_performance_updated_by', true ) );
        $teacher_name = kla_notification_teacher_name( $teacher_id );
    }

    $message = 'Performance was updated by ' . $teacher_name . '.';

    kla_create_parent_notification(
        array(
            'parent_id'  => $parent_id,
            'student_id' => $student_id,
            'type'       => 'performance',
            'title'      => $student_name . '\'s performance was updated',
            'message'    => $message,
            'source_id'  => $post_id,
            'source_key' => $source_key,
            'icon'       => '📊',
        )
    );
}


/* -------------------------------------------------------------------------
 * Notification: Attendance update
 * ------------------------------------------------------------------------- */

function kla_notification_from_attendance_legacy( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || 'kudos_attendance' !== $post->post_type ) {
        return;
    }

    if ( 'publish' !== $post->post_status ) {
        return;
    }

    $student_id = absint( get_post_meta( $post_id, '_kudos_attendance_student', true ) );

    if ( ! $student_id ) {
        return;
    }

    $parent_id = kla_notification_student_parent_id( $student_id );

    if ( ! $parent_id ) {
        return;
    }

    $modified = get_post_modified_time( 'U', true, $post_id );

    if ( ! $modified ) {
        return;
    }

    $source_key = 'attendance_' . $post_id . '_' . $modified;

    $student_name = kla_notification_student_name( $student_id );
    $status       = get_post_meta( $post_id, '_kudos_attendance_status', true );
    $date         = get_post_meta( $post_id, '_kudos_attendance_date', true );

    if ( ! $status ) {
        return;
    }

    $status_text = strtolower( $status );

    kla_create_parent_notification(
        array(
            'parent_id'  => $parent_id,
            'student_id' => $student_id,
            'type'       => 'attendance',
            'title'      => $student_name . ' was marked ' . $status,
            'message'    => 'Attendance recorded as ' . $status_text . ( $date ? ' for ' . $date . '.' : '.' ),
            'source_id'  => $post_id,
            'source_key' => $source_key,
            'icon'       => '📅',
        )
    );
}



/* -------------------------------------------------------------------------
 * Request-level notification queue
 *
 * We process notifications at shutdown so all transaction/performance/
 * attendance metadata written by the same request is available first.
 * ------------------------------------------------------------------------- */

$GLOBALS['kla_notification_queue'] = array();

function kla_queue_notification_source( $type, $post_id ) {
    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return;
    }

    if ( ! isset( $GLOBALS['kla_notification_queue'][ $type ] ) ) {
        $GLOBALS['kla_notification_queue'][ $type ] = array();
    }

    $GLOBALS['kla_notification_queue'][ $type ][ $post_id ] = true;
}

function kla_process_notification_queue() {
    $queue = isset( $GLOBALS['kla_notification_queue'] ) ? $GLOBALS['kla_notification_queue'] : array();

    /* Transaction notifications */
    if ( ! empty( $queue['transaction'] ) ) {
        foreach ( array_keys( $queue['transaction'] ) as $post_id ) {
            $student_id = absint( get_post_meta( $post_id, '_kla_transaction_student_id', true ) );

            if ( ! $student_id ) {
                continue;
            }

            $parent_id = absint( get_post_meta( $post_id, '_kla_transaction_parent_id', true ) );

            if ( ! $parent_id ) {
                $parent_id = kla_notification_student_parent_id( $student_id );
            }

            if ( ! $parent_id ) {
                continue;
            }

            $type         = strtolower( get_post_meta( $post_id, '_kla_transaction_type', true ) );
            $points       = absint( get_post_meta( $post_id, '_kla_transaction_points', true ) );
            $reason       = get_post_meta( $post_id, '_kla_transaction_reason', true );
            $new_balance  = get_post_meta( $post_id, '_kla_transaction_new_balance', true );
            $activity_id  = absint( get_post_meta( $post_id, '_kla_transaction_activity_id', true ) );
            $student_name = kla_notification_student_name( $student_id );

            if ( in_array( $type, array( 'redemption', 'redeem' ), true ) ) {
                $activity_name = $activity_id ? get_the_title( $activity_id ) : '';

                $message = 'Redeemed';
                if ( $activity_name ) {
                    $message .= ' for ' . $activity_name;
                }
                if ( $new_balance !== '' ) {
                    $message .= '. Remaining Kudos: ' . absint( $new_balance ) . '.';
                }

                kla_create_parent_notification(
                    array(
                        'parent_id'  => $parent_id,
                        'student_id' => $student_id,
                        'type'       => 'kudos_redemption',
                        'title'      => $student_name . ' redeemed ' . $points . ' Kudos',
                        'message'    => $message,
                        'source_id'  => $post_id,
                        'source_key' => 'transaction_' . $post_id,
                        'icon'       => '🎁',
                    )
                );
            } else {
                $message = $reason ? $reason : 'A new Kudos reward was added.';
                if ( $new_balance !== '' ) {
                    $message .= ' Current Kudos balance: ' . absint( $new_balance ) . '.';
                }

                kla_create_parent_notification(
                    array(
                        'parent_id'  => $parent_id,
                        'student_id' => $student_id,
                        'type'       => 'kudos_award',
                        'title'      => $student_name . ' earned ' . $points . ' Kudos',
                        'message'    => $message,
                        'source_id'  => $post_id,
                        'source_key' => 'transaction_' . $post_id,
                        'icon'       => '⭐',
                    )
                );
            }
        }
    }

    /* Performance notifications */
    if ( ! empty( $queue['performance'] ) ) {
        foreach ( array_keys( $queue['performance'] ) as $post_id ) {
            $student_id = absint( get_post_meta( $post_id, '_kudos_performance_student', true ) );

            if ( ! $student_id ) {
                $student_name_meta = get_post_meta( $post_id, '_kudos_performance_student_name', true );

                if ( $student_name_meta ) {
                    $matches = get_posts(
                        array(
                            'post_type'      => 'kudos_student',
                            'post_status'    => 'publish',
                            'posts_per_page' => 1,
                            'fields'         => 'ids',
                            'meta_query'     => array(
                                array(
                                    'key'     => '_kudos_student_name',
                                    'value'   => $student_name_meta,
                                    'compare' => '=',
                                ),
                            ),
                            'no_found_rows'  => true,
                        )
                    );

                    if ( ! empty( $matches ) ) {
                        $student_id = absint( $matches[0] );
                    }
                }
            }

            if ( ! $student_id ) {
                continue;
            }

            $parent_id = kla_notification_student_parent_id( $student_id );

            if ( ! $parent_id ) {
                continue;
            }

            $updated_at = (string) get_post_meta( $post_id, '_kudos_performance_updated_at', true );
            $source_key = 'performance_' . $post_id . '_' . md5( $updated_at );

            $teacher_name = get_post_meta( $post_id, '_kudos_performance_updated_by_name', true );

            if ( ! $teacher_name ) {
                $teacher_id   = absint( get_post_meta( $post_id, '_kudos_performance_updated_by', true ) );
                $teacher_name = kla_notification_teacher_name( $teacher_id );
            }

            kla_create_parent_notification(
                array(
                    'parent_id'  => $parent_id,
                    'student_id' => $student_id,
                    'type'       => 'performance',
                    'title'      => kla_notification_student_name( $student_id ) . '\'s performance was updated',
                    'message'    => 'Performance was updated by ' . $teacher_name . '.',
                    'source_id'  => $post_id,
                    'source_key' => $source_key,
                    'icon'       => '📊',
                )
            );
        }
    }

    /* Attendance notifications */
    if ( ! empty( $queue['attendance'] ) ) {
        foreach ( array_keys( $queue['attendance'] ) as $post_id ) {
            $student_id = absint( get_post_meta( $post_id, '_kudos_attendance_student', true ) );

            if ( ! $student_id ) {
                continue;
            }

            $parent_id = kla_notification_student_parent_id( $student_id );

            if ( ! $parent_id ) {
                continue;
            }

            $updated_at = (string) get_post_meta( $post_id, '_kudos_attendance_updated_at', true );
            $source_key = 'attendance_' . $post_id . '_' . md5( $updated_at );

            $status = get_post_meta( $post_id, '_kudos_attendance_status', true );
            $date   = get_post_meta( $post_id, '_kudos_attendance_date', true );

            if ( ! $status ) {
                continue;
            }

            kla_create_parent_notification(
                array(
                    'parent_id'  => $parent_id,
                    'student_id' => $student_id,
                    'type'       => 'attendance',
                    'title'      => kla_notification_student_name( $student_id ) . ' was marked ' . $status,
                    'message'    => 'Attendance recorded as ' . strtolower( $status ) . ( $date ? ' for ' . $date . '.' : '.' ),
                    'source_id'  => $post_id,
                    'source_key' => $source_key,
                    'icon'       => '📅',
                )
            );
        }
    }
}

add_action( 'shutdown', 'kla_process_notification_queue', 999 );

/* -------------------------------------------------------------------------
 * Reliable notification triggers
 *
 * WordPress fires save_post immediately after the CPT post is created/updated,
 * but our award/performance/attendance handlers add the important meta fields
 * immediately afterwards. The original save_post-only approach could therefore
 * run too early and miss the parent/student relation.
 *
 * These hooks fire after the relevant meta is actually written.
 * ------------------------------------------------------------------------- */

function kla_notification_transaction_meta_saved( $meta_id, $object_id, $meta_key, $meta_value ) {
    if ( 'kudos_transaction' !== get_post_type( $object_id ) ) {
        return;
    }

    if ( 0 !== strpos( (string) $meta_key, '_kla_transaction_' ) ) {
        return;
    }

    kla_queue_notification_source( 'transaction', $object_id );
}

add_action( 'added_post_meta', 'kla_notification_transaction_meta_saved', 20, 4 );
add_action( 'updated_post_meta', 'kla_notification_transaction_meta_saved', 20, 4 );

function kla_notification_performance_meta_saved( $meta_id, $object_id, $meta_key, $meta_value ) {
    if ( 'kudos_performance' !== get_post_type( $object_id ) ) {
        return;
    }

    if ( 0 !== strpos( (string) $meta_key, '_kudos_performance_' ) ) {
        return;
    }

    kla_queue_notification_source( 'performance', $object_id );
}

add_action( 'added_post_meta', 'kla_notification_performance_meta_saved', 20, 4 );
add_action( 'updated_post_meta', 'kla_notification_performance_meta_saved', 20, 4 );

function kla_notification_attendance_meta_saved( $meta_id, $object_id, $meta_key, $meta_value ) {
    if ( 'kudos_attendance' !== get_post_type( $object_id ) ) {
        return;
    }

    if ( 0 !== strpos( (string) $meta_key, '_kudos_attendance_' ) ) {
        return;
    }

    kla_queue_notification_source( 'attendance', $object_id );
}

add_action( 'added_post_meta', 'kla_notification_attendance_meta_saved', 20, 4 );

add_action( 'updated_post_meta', 'kla_notification_attendance_meta_saved', 20, 4 );

/* -------------------------------------------------------------------------
 * AJAX: Get parent notifications
 * ------------------------------------------------------------------------- */

function kla_parent_notifications_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please log in again.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Parent access required.' ), 403 );
    }

    check_ajax_referer( 'kla_parent_notifications_nonce', 'nonce' );

    $parent_id = function_exists( 'kla_get_parent_record_id' )
        ? absint( kla_get_parent_record_id() )
        : absint( get_user_meta( $user->ID, '_kla_parent_record_id', true ) );

    if ( ! $parent_id ) {
        wp_send_json_error( array( 'message' => 'Parent account is not linked.' ), 400 );
    }

    $notifications = new WP_Query(
        array(
            'post_type'      => 'kla_notification',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_notification_parent_id',
                    'value'   => $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    $items = array();
    $unread = 0;

    if ( $notifications->have_posts() ) {
        while ( $notifications->have_posts() ) {
            $notifications->the_post();

            $id = get_the_ID();
            $read = (bool) absint( get_post_meta( $id, '_kla_notification_read', true ) );

            if ( ! $read ) {
                $unread++;
            }

            $items[] = array(
                'id'      => $id,
                'title'   => get_the_title(),
                'message' => wp_strip_all_tags( get_post_field( 'post_content', $id ) ),
                'icon'    => get_post_meta( $id, '_kla_notification_icon', true ),
                'type'    => get_post_meta( $id, '_kla_notification_type', true ),
                'date'    => get_post_meta( $id, '_kla_notification_created_at', true ),
                'read'    => $read,
            );
        }

        wp_reset_postdata();
    }

    wp_send_json_success(
        array(
            'items'  => $items,
            'unread' => $unread,
        )
    );
}

add_action( 'wp_ajax_kla_parent_notifications', 'kla_parent_notifications_ajax' );

/* -------------------------------------------------------------------------
 * AJAX: Mark all notifications read
 * ------------------------------------------------------------------------- */

function kla_parent_notifications_mark_read_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please log in again.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Parent access required.' ), 403 );
    }

    check_ajax_referer( 'kla_parent_notifications_nonce', 'nonce' );

    $parent_id = function_exists( 'kla_get_parent_record_id' )
        ? absint( kla_get_parent_record_id() )
        : absint( get_user_meta( $user->ID, '_kla_parent_record_id', true ) );

    if ( ! $parent_id ) {
        wp_send_json_error( array( 'message' => 'Parent account is not linked.' ), 400 );
    }

    $ids = get_posts(
        array(
            'post_type'      => 'kla_notification',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_notification_parent_id',
                    'value'   => $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kla_notification_read',
                    'value'   => 0,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    foreach ( $ids as $id ) {
        update_post_meta( $id, '_kla_notification_read', 1 );
    }

    wp_send_json_success( array( 'message' => 'Notifications marked as read.' ) );
}

add_action( 'wp_ajax_kla_parent_notifications_mark_read', 'kla_parent_notifications_mark_read_ajax' );

/* -------------------------------------------------------------------------
 * Parent Dashboard UI
 * ------------------------------------------------------------------------- */

function kla_parent_notifications_render() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        return;
    }

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'kla_parent_notifications_nonce' );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dashboard =
            document.querySelector('.kla-parent-dashboard') ||
            document.querySelector('.parent-dashboard') ||
            document.querySelector('[data-parent-dashboard]');

        if (!dashboard || document.getElementById('kla-parent-notifications')) {
            return;
        }

        const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;

        const section = document.createElement('section');
        section.id = 'kla-parent-notifications';

        const anchor =
            dashboard.querySelector('.kla-parent-dashboard-content') ||
            dashboard.querySelector('.kla-parent-main') ||
            dashboard;

        anchor.prepend(section);

        function esc(value) {
            return String(value === undefined || value === null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDate(value) {
            if (!value) return '';
            const date = new Date(String(value).replace(' ', 'T'));
            if (isNaN(date.getTime())) return value;

            return date.toLocaleDateString(undefined, {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function typeClass(type) {
            if (type === 'kudos_award') return 'kla-notif-award';
            if (type === 'kudos_redemption') return 'kla-notif-redeem';
            if (type === 'performance') return 'kla-notif-performance';
            if (type === 'attendance') return 'kla-notif-attendance';
            return '';
        }

        function render(items, unread) {
            section.innerHTML = `
                <div class="kla-notif-header">
                    <div>
                        <span class="kla-notif-eyebrow">Parent Updates</span>
                        <h2>Notifications <span class="kla-notif-count">${esc(unread)}</span></h2>
                    </div>
                    <div class="kla-notif-actions">
                        <button type="button" id="kla-notif-refresh">↻ Refresh</button>
                        ${unread ? '<button type="button" id="kla-notif-read">Mark all read</button>' : ''}
                    </div>
                </div>

                <div class="kla-notif-list">
                    ${
                        items.length
                        ? items.map(function(item) {
                            return `
                                <article class="kla-notif-item ${item.read ? 'kla-notif-read' : 'kla-notif-unread'}">
                                    <div class="kla-notif-icon ${typeClass(item.type)}">${esc(item.icon || '🔔')}</div>
                                    <div class="kla-notif-body">
                                        <div class="kla-notif-title-row">
                                            <h3>${esc(item.title)}</h3>
                                            ${item.read ? '' : '<span class="kla-notif-new">NEW</span>'}
                                        </div>
                                        <p>${esc(item.message)}</p>
                                        <small>${esc(formatDate(item.date))}</small>
                                    </div>
                                </article>
                            `;
                        }).join('')
                        : '<div class="kla-notif-empty">No notifications yet.</div>'
                    }
                </div>
            `;

            document.getElementById('kla-notif-refresh').addEventListener('click', load);

            const readButton = document.getElementById('kla-notif-read');

            if (readButton) {
                readButton.addEventListener('click', function() {
                    const data = new FormData();
                    data.append('action', 'kla_parent_notifications_mark_read');
                    data.append('nonce', nonce);

                    readButton.disabled = true;

                    fetch(ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    })
                    .then(function(response){ return response.json(); })
                    .then(function(result){
                        if (!result.success) {
                            throw new Error(result.data && result.data.message ? result.data.message : 'Unable to update notifications.');
                        }

                        load();
                    })
                    .catch(function(){
                        readButton.disabled = false;
                    });
                });
            }
        }

        function load() {
            section.innerHTML = `
                <div class="kla-notif-loading">
                    Loading notifications...
                </div>
            `;

            const data = new FormData();
            data.append('action', 'kla_parent_notifications');
            data.append('nonce', nonce);

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(function(response){ return response.json(); })
            .then(function(result){
                if (!result.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : 'Unable to load notifications.');
                }

                render(result.data.items || [], result.data.unread || 0);
            })
            .catch(function(error){
                section.innerHTML = '<div class="kla-notif-error">' + esc(error.message) + '</div>';
            });
        }

        const style = document.createElement('style');

        style.textContent = `
            #kla-parent-notifications{width:100%;margin:0 0 22px}
            .kla-notif-header{display:flex;align-items:flex-end;justify-content:space-between;gap:15px;margin-bottom:12px}
            .kla-notif-eyebrow{display:inline-block;color:#2d7b3c;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:4px}
            .kla-notif-header h2{margin:0;color:#30205a;font-size:23px}
            .kla-notif-count{display:inline-flex;min-width:20px;height:20px;align-items:center;justify-content:center;border-radius:999px;background:#2d7b3c;color:#fff;font-size:10px;vertical-align:middle;margin-left:4px;padding:0 5px}
            .kla-notif-actions{display:flex;gap:7px;flex-wrap:wrap}
            .kla-notif-actions button{border:1px solid #ddd7ca;background:#fff;color:#50495c;border-radius:8px;padding:8px 11px;font-size:10px;font-weight:800;cursor:pointer}
            .kla-notif-actions button:first-child{background:#30205a;color:#fff;border-color:#30205a}
            .kla-notif-list{display:grid;gap:7px}
            .kla-notif-item{display:flex;align-items:flex-start;gap:11px;background:#fff;border:1px solid #ece7d9;border-radius:12px;padding:12px 13px;box-shadow:0 4px 14px rgba(48,32,90,.04)}
            .kla-notif-unread{border-left:3px solid #2d7b3c}
            .kla-notif-read{opacity:.82}
            .kla-notif-icon{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:#f7f4eb;flex:none;font-size:17px}
            .kla-notif-award{background:#eef6dd}
            .kla-notif-redeem{background:#fff3d6}
            .kla-notif-performance{background:#eeeafa}
            .kla-notif-attendance{background:#eaf4f7}
            .kla-notif-body{min-width:0;flex:1}
            .kla-notif-title-row{display:flex;align-items:center;gap:7px}
            .kla-notif-title-row h3{margin:0;color:#30205a;font-size:12px;line-height:1.35}
            .kla-notif-new{background:#2d7b3c;color:#fff;border-radius:999px;padding:3px 6px;font-size:7px;font-weight:800;letter-spacing:.04em}
            .kla-notif-body p{margin:4px 0;color:#5d5867;font-size:11px;line-height:1.45}
            .kla-notif-body small{color:#8a8491;font-size:9px}
            .kla-notif-empty,.kla-notif-loading,.kla-notif-error{padding:17px;border:1px dashed #ddd7ca;border-radius:11px;background:#fff;text-align:center;color:#8a8491;font-size:11px}
            .kla-notif-error{color:#b42318}
            @media(max-width:600px){
                .kla-notif-header{align-items:flex-start;flex-direction:column}
                .kla-notif-actions{width:100%}
                .kla-notif-actions button{flex:1}
            }
        `;

        document.head.appendChild(style);
        load();
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'kla_parent_notifications_render', 45 );
