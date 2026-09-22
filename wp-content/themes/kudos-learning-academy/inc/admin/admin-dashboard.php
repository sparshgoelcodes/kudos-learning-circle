<?php
/**
 * Kudos Learning Academy
 * Admin Dashboard Module
 *
 * Central administration dashboard for:
 * - Students
 * - Parents
 * - Activities
 * - Teachers
 * - Performance
 * - Attendance
 * - Kudos / Wallet
 * - Redemptions
 * - Notifications
 * - Activity Enrollment & Fee Management
 *
 * Usage:
 * 1. Add this file to:
 *    inc/admin/admin-dashboard.php
 * 2. Add to functions.php:
 *    require_once get_template_directory() . '/inc/admin/admin-dashboard.php';
 * 3. Create a WordPress page named "Admin Dashboard" with:
 *    [kla_admin_dashboard]
 *
 * Only WordPress administrators can access the dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



/* -------------------------------------------------------------------------
 * Fee Payment History / Installments
 * ------------------------------------------------------------------------- */

function kla_register_fee_payment_cpt() {
    register_post_type(
        'kudos_fee_payment',
        array(
            'labels' => array(
                'name'          => 'Fee Payments',
                'singular_name' => 'Fee Payment',
                'menu_name'     => 'Fee Payments',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'show_in_rest' => false,
            'supports'     => array( 'title' ),
            'menu_icon'    => 'dashicons-money-alt',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        )
    );
}
add_action( 'init', 'kla_register_fee_payment_cpt', 21 );

function kla_fee_payment_modes() {
    return array(
        'cash'          => 'Cash',
        'upi'           => 'UPI',
        'bank_transfer' => 'Bank Transfer',
        'card'          => 'Card',
        'cheque'        => 'Cheque',
        'other'         => 'Other',
    );
}

function kla_fee_payment_mode_label( $mode ) {
    $modes = kla_fee_payment_modes();
    return isset( $modes[ $mode ] ) ? $modes[ $mode ] : ucfirst( str_replace( '_', ' ', $mode ) );
}

function kla_admin_enrollment_totals( $enrollment_id ) {
    $fee = (float) get_post_meta( $enrollment_id, '_kla_enrollment_fee', true );
    $activity_id = absint( get_post_meta( $enrollment_id, '_kla_enrollment_activity_id', true ) );

    /*
     * Older enrollment records may have been created before the activity
     * fee was copied into the enrollment. Never let a valid activity fee
     * appear as ₹0 on reports/parent screens.
     */
    if ( $fee <= 0 && $activity_id ) {
        $activity_fee = kla_enrollment_activity_fee( $activity_id );
        if ( $activity_fee > 0 ) {
            $fee = $activity_fee;
            update_post_meta( $enrollment_id, '_kla_enrollment_fee', $fee );
        }
    }

    $discount = max( 0, (float) get_post_meta( $enrollment_id, '_kla_enrollment_kudos_discount', true ) );
    $stored_payable = (float) get_post_meta( $enrollment_id, '_kla_enrollment_net_payable', true );
    $payable = $stored_payable > 0 ? $stored_payable : max( 0, $fee - $discount );

    if ( $payable <= 0 && $fee > 0 ) {
        $payable = max( 0, $fee - $discount );
    }

    if ( $payable > 0 ) {
        update_post_meta( $enrollment_id, '_kla_enrollment_net_payable', $payable );
    }

    /*
     * Do NOT cap paid at payable. If an admin recorded ₹1,500 against a
     * ₹1,480 payable enrollment, the extra ₹20 is a real credit/advance.
     */
    $paid = max( 0, (float) get_post_meta( $enrollment_id, '_kla_enrollment_amount_paid', true ) );
    $due = max( 0, $payable - $paid );
    $credit = max( 0, $paid - $payable );

    if ( $payable > 0 && $paid >= $payable ) {
        $status = 'paid';
    } elseif ( $paid > 0 ) {
        $status = 'partial';
    } else {
        $status = 'pending';
    }

    update_post_meta( $enrollment_id, '_kla_enrollment_amount_due', $due );
    update_post_meta( $enrollment_id, '_kla_enrollment_credit_balance', $credit );
    update_post_meta( $enrollment_id, '_kla_enrollment_payment_status', $status );

    return array(
        'fee'      => $fee,
        'discount' => $discount,
        'payable'  => $payable,
        'paid'     => $paid,
        'due'      => $due,
        'credit'   => $credit,
        'status'   => $status,
    );
}

function kla_admin_record_fee_payment(
    $enrollment_id,
    $amount,
    $payment_date,
    $payment_mode,
    $payment_reference = '',
    $payment_notes = '',
    $source = 'manual'
) {
    $amount = max( 0, (float) $amount );

    if ( ! $enrollment_id || 'kudos_enrollment' !== get_post_type( $enrollment_id ) || $amount <= 0 ) {
        return new WP_Error( 'invalid_payment', 'Invalid enrollment or payment amount.' );
    }

    $totals = kla_admin_enrollment_totals( $enrollment_id );

    /*
     * Do not reject an additional payment merely because the enrollment is
     * already paid. If the payment exceeds the remaining due, the excess is
     * stored as an enrollment credit/advance.
     */
    if ( $totals['payable'] <= 0 ) {
        return new WP_Error( 'invalid_payable', 'This enrollment has no payable amount.' );
    }

    $payment_date = sanitize_text_field( $payment_date ?: current_time( 'Y-m-d' ) );
    $payment_mode = sanitize_key( $payment_mode );
    $modes = kla_fee_payment_modes();

    if ( ! isset( $modes[ $payment_mode ] ) ) {
        $payment_mode = 'other';
    }

    $payment_reference = sanitize_text_field( $payment_reference );
    $payment_notes = sanitize_textarea_field( $payment_notes );

    $sid = absint( get_post_meta( $enrollment_id, '_kla_enrollment_student_id', true ) );
    $aid = absint( get_post_meta( $enrollment_id, '_kla_enrollment_activity_id', true ) );
    $student_name = $sid ? kla_enrollment_student_name( $sid ) : 'Student';
    $activity_name = $aid ? get_the_title( $aid ) : 'Activity';

    $payment_id = wp_insert_post(
        array(
            'post_title'  => $student_name . ' - ' . $activity_name . ' - Payment ₹' . number_format( $amount, 2, '.', '' ),
            'post_status' => 'publish',
            'post_type'   => 'kudos_fee_payment',
        ),
        true
    );

    if ( is_wp_error( $payment_id ) ) {
        return $payment_id;
    }

    update_post_meta( $payment_id, '_kla_fee_payment_enrollment_id', $enrollment_id );
    update_post_meta( $payment_id, '_kla_fee_payment_student_id', $sid );
    update_post_meta( $payment_id, '_kla_fee_payment_activity_id', $aid );
    update_post_meta( $payment_id, '_kla_fee_payment_amount', $amount );
    update_post_meta( $payment_id, '_kla_fee_payment_date', $payment_date );
    update_post_meta( $payment_id, '_kla_fee_payment_mode', $payment_mode );
    update_post_meta( $payment_id, '_kla_fee_payment_reference', $payment_reference );
    update_post_meta( $payment_id, '_kla_fee_payment_notes', $payment_notes );
    update_post_meta( $payment_id, '_kla_fee_payment_source', $source );
    update_post_meta( $payment_id, '_kla_fee_payment_recorded_by', get_current_user_id() );
    update_post_meta( $payment_id, '_kla_fee_payment_recorded_at', current_time( 'mysql' ) );
    update_post_meta( $payment_id, '_kla_fee_payment_previous_paid', $totals['paid'] );

    $new_paid = max( 0, $totals['paid'] + $amount );
    $new_due  = max( 0, $totals['payable'] - $new_paid );
    $new_credit = max( 0, $new_paid - $totals['payable'] );
    $new_status = $new_paid >= $totals['payable'] && $totals['payable'] > 0
        ? 'paid'
        : ( $new_paid > 0 ? 'partial' : 'pending' );

    update_post_meta( $enrollment_id, '_kla_enrollment_amount_paid', $new_paid );
    update_post_meta( $enrollment_id, '_kla_enrollment_amount_due', $new_due );
    update_post_meta( $enrollment_id, '_kla_enrollment_credit_balance', $new_credit );
    update_post_meta( $enrollment_id, '_kla_enrollment_payment_status', $new_status );
    update_post_meta( $payment_id, '_kla_fee_payment_balance_after', $new_paid );
    update_post_meta( $payment_id, '_kla_fee_payment_due_after', $new_due );
    update_post_meta( $payment_id, '_kla_fee_payment_credit_after', $new_credit );

    return $payment_id;
}

function kla_admin_add_fee_payment() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_fee_payment', 'kla_admin_nonce' );

    $enrollment_id = absint( $_POST['fee_payment_enrollment_id'] ?? 0 );
    $amount = max( 0, (float) ( $_POST['fee_payment_amount'] ?? 0 ) );
    $payment_date = sanitize_text_field( wp_unslash( $_POST['fee_payment_date'] ?? current_time( 'Y-m-d' ) ) );
    $payment_mode = sanitize_key( $_POST['fee_payment_mode'] ?? 'other' );
    $reference = sanitize_text_field( wp_unslash( $_POST['fee_payment_reference'] ?? '' ) );
    $notes = sanitize_textarea_field( wp_unslash( $_POST['fee_payment_notes'] ?? '' ) );

    $result = kla_admin_record_fee_payment(
        $enrollment_id,
        $amount,
        $payment_date,
        $payment_mode,
        $reference,
        $notes,
        'manual'
    );

    if ( is_wp_error( $result ) ) {
        kla_admin_dashboard_redirect( $result->get_error_message(), 'error', 'enrollments' );
    }

    kla_admin_dashboard_redirect(
        'Payment of ₹' . number_format_i18n( $amount, 2 ) . ' recorded successfully.',
        'success',
        'enrollments'
    );
}
add_action( 'admin_post_kla_admin_add_fee_payment', 'kla_admin_add_fee_payment' );

function kla_admin_get_fee_payments( $enrollment_id ) {
    return get_posts(
        array(
            'post_type'      => 'kudos_fee_payment',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => 'meta_value',
            'meta_key'       => '_kla_fee_payment_date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_fee_payment_enrollment_id',
                    'value'   => $enrollment_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );
}

/* -------------------------------------------------------------------------
 * Activity Enrollment + Fee Management
 * ------------------------------------------------------------------------- */

function kla_register_enrollment_cpt() {
    register_post_type(
        'kudos_enrollment',
        array(
            'labels' => array(
                'name'          => 'Enrollments',
                'singular_name' => 'Enrollment',
                'menu_name'     => 'Enrollments',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'show_in_rest' => false,
            'supports'     => array( 'title' ),
            'menu_icon'    => 'dashicons-welcome-learn-more',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        )
    );
}
add_action( 'init', 'kla_register_enrollment_cpt', 20 );

function kla_enrollment_student_name( $student_id ) {
    $name = get_post_meta( $student_id, '_kudos_student_name', true );
    return $name ? $name : get_the_title( $student_id );
}

function kla_enrollment_activity_fee( $activity_id ) {
    $fee = get_post_meta( $activity_id, '_kudos_activity_fee', true );
    return is_numeric( $fee ) ? (float) $fee : 0;
}

function kla_admin_find_latest_redemption( $student_id, $activity_id ) {
    $records = get_posts(
        array(
            'post_type'      => 'kudos_redemption',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_kla_student_id',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kla_activity_id',
                    'value'   => $activity_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kla_status',
                    'value'   => 'Approved',
                    'compare' => '=',
                ),
            ),
        )
    );

    return ! empty( $records ) ? $records[0] : null;
}

function kla_admin_redemption_snapshot( $redemption_id ) {
    if ( ! $redemption_id || 'kudos_redemption' !== get_post_type( $redemption_id ) ) {
        return array(
            'id' => 0,
            'points' => 0,
            'discount' => 0,
            'payable' => 0,
            'paid' => 0,
            'due' => 0,
            'payment_status' => 'pending',
        );
    }

    $activity_fee = (float) get_post_meta( $redemption_id, '_kla_activity_fee', true );
    $discount     = (float) get_post_meta( $redemption_id, '_kla_discount', true );
    $payable      = (float) get_post_meta( $redemption_id, '_kla_payable_fee', true );
    $points       = (int) get_post_meta( $redemption_id, '_kla_points_used', true );
    $paid         = (float) get_post_meta( $redemption_id, '_kla_amount_paid', true );
    $status       = get_post_meta( $redemption_id, '_kla_payment_status', true );

    if ( $payable <= 0 && $activity_fee > 0 ) {
        $payable = max( 0, $activity_fee - $discount );
    }

    $paid = min( max( 0, $paid ), $payable );
    $due  = max( 0, $payable - $paid );

    if ( ! in_array( $status, array( 'unpaid', 'partial', 'paid' ), true ) ) {
        $status = $paid >= $payable && $payable > 0 ? 'paid' : ( $paid > 0 ? 'partial' : 'pending' );
    }

    if ( 'unpaid' === $status ) {
        $status = 'pending';
    }

    return array(
        'id' => $redemption_id,
        'activity_fee' => $activity_fee,
        'points' => $points,
        'discount' => $discount,
        'payable' => $payable,
        'paid' => $paid,
        'due' => $due,
        'payment_status' => $status,
    );
}

function kla_admin_add_enrollment() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_enrollment', 'kla_admin_nonce' );

    $student_id      = absint( $_POST['enrollment_student'] ?? 0 );
    $activity_id     = absint( $_POST['enrollment_activity'] ?? 0 );
    $enrollment_date = sanitize_text_field( wp_unslash( $_POST['enrollment_date'] ?? current_time( 'Y-m-d' ) ) );
    $fee             = max( 0, (float) ( $_POST['enrollment_fee'] ?? 0 ) );
    $amount_paid     = max( 0, (float) ( $_POST['amount_paid'] ?? 0 ) );
    $payment_status  = sanitize_key( $_POST['payment_status'] ?? 'pending' );
    $status          = sanitize_key( $_POST['enrollment_status'] ?? 'active' );
    $remarks         = sanitize_textarea_field( wp_unslash( $_POST['enrollment_remarks'] ?? '' ) );
    $initial_payment_date = sanitize_text_field( wp_unslash( $_POST['initial_payment_date'] ?? $enrollment_date ) );
    $initial_payment_mode = sanitize_key( $_POST['initial_payment_mode'] ?? 'other' );
    $initial_payment_reference = sanitize_text_field( wp_unslash( $_POST['initial_payment_reference'] ?? '' ) );
    $initial_payment_notes = sanitize_textarea_field( wp_unslash( $_POST['initial_payment_notes'] ?? '' ) );

    if ( ! $student_id || 'kudos_student' !== get_post_type( $student_id ) ) {
        kla_admin_dashboard_redirect( 'Please select a valid student.', 'error', 'enrollments' );
    }

    if ( ! $activity_id || 'kudos_activity' !== get_post_type( $activity_id ) ) {
        kla_admin_dashboard_redirect( 'Please select a valid activity.', 'error', 'enrollments' );
    }

    /*
     * If a Kudos redemption already exists for this exact student/activity,
     * carry its fee, discount and payment information into the enrollment.
     */
    $matched_redemption = kla_admin_find_latest_redemption( $student_id, $activity_id );
    $redemption_data    = $matched_redemption
        ? kla_admin_redemption_snapshot( $matched_redemption->ID )
        : kla_admin_redemption_snapshot( 0 );

    if ( $matched_redemption ) {
        $fee = (float) get_post_meta( $matched_redemption->ID, '_kla_activity_fee', true );

        if ( $fee <= 0 ) {
            $fee = kla_enrollment_activity_fee( $activity_id );
        }

        $amount_paid    = $redemption_data['paid'];
        $payment_status = $redemption_data['payment_status'];
    }

    /*
     * Always prefer the configured activity fee when the submitted fee is
     * blank/zero. This prevents valid activities such as Art n Craft from
     * being enrolled at ₹0.
     */
    if ( $fee <= 0 ) {
        $fee = kla_enrollment_activity_fee( $activity_id );
    }

    $kudos_discount = max( 0, (float) $redemption_data['discount'] );
    $net_payable    = max( 0, $fee - $kudos_discount );

    /*
     * Payment is calculated against NET PAYABLE. We intentionally do not cap
     * a payment above net payable: the excess becomes a credit/advance.
     */

    if ( ! in_array( $payment_status, array( 'paid', 'pending', 'partial' ), true ) ) {
        $payment_status = 'pending';
    }

    if ( ! in_array( $status, array( 'active', 'completed', 'cancelled' ), true ) ) {
        $status = 'active';
    }

    if ( 'paid' === $payment_status ) {
        $amount_paid = $net_payable;
    } elseif ( 'pending' === $payment_status ) {
        $amount_paid = 0;
    } elseif ( $amount_paid <= 0 ) {
        $payment_status = 'pending';
    } elseif ( $amount_paid >= $net_payable && $net_payable > 0 ) {
        $amount_paid    = $net_payable;
        $payment_status = 'paid';
    } elseif ( $amount_paid < $net_payable ) {
        $payment_status = 'partial';
    }

    $amount_due = max( 0, $net_payable - $amount_paid );
    $credit_balance = max( 0, $amount_paid - $net_payable );

    $student_name  = kla_enrollment_student_name( $student_id );
    $activity_name = get_the_title( $activity_id );

    $enrollment_id = wp_insert_post(
        array(
            'post_title'  => $student_name . ' - ' . $activity_name,
            'post_status' => 'publish',
            'post_type'   => 'kudos_enrollment',
        ),
        true
    );

    if ( is_wp_error( $enrollment_id ) ) {
        kla_admin_dashboard_redirect( $enrollment_id->get_error_message(), 'error', 'enrollments' );
    }

    update_post_meta( $enrollment_id, '_kla_enrollment_student_id', $student_id );
    update_post_meta( $enrollment_id, '_kla_enrollment_activity_id', $activity_id );
    update_post_meta( $enrollment_id, '_kla_enrollment_redemption_id', $redemption_data['id'] );
    update_post_meta( $enrollment_id, '_kla_enrollment_kudos_redeemed', $redemption_data['points'] );
    update_post_meta( $enrollment_id, '_kla_enrollment_kudos_discount', $kudos_discount );
    update_post_meta( $enrollment_id, '_kla_enrollment_net_payable', $net_payable );
    update_post_meta( $enrollment_id, '_kla_enrollment_date', $enrollment_date );
    update_post_meta( $enrollment_id, '_kla_enrollment_start_date', $enrollment_date );
    update_post_meta( $enrollment_id, '_kla_enrollment_fee', $fee );
    update_post_meta( $enrollment_id, '_kla_enrollment_amount_paid', $amount_paid );
    update_post_meta( $enrollment_id, '_kla_enrollment_amount_due', $amount_due );
    update_post_meta( $enrollment_id, '_kla_enrollment_credit_balance', $credit_balance );
    update_post_meta( $enrollment_id, '_kla_enrollment_payment_status', $payment_status );
    update_post_meta( $enrollment_id, '_kla_enrollment_status', $status );
    update_post_meta( $enrollment_id, '_kla_enrollment_remarks', $remarks );

    if ( $amount_paid > 0 ) {
        $initial_payment_result = kla_admin_record_fee_payment(
            $enrollment_id,
            $amount_paid,
            $initial_payment_date,
            $initial_payment_mode,
            $initial_payment_reference,
            $initial_payment_notes,
            'enrollment'
        );

        if ( is_wp_error( $initial_payment_result ) ) {
            // Keep the enrollment intact even if payment-history creation fails.
            update_post_meta( $enrollment_id, '_kla_enrollment_payment_history_warning', $initial_payment_result->get_error_message() );
        }
    }

    kla_admin_dashboard_redirect(
        $student_name . ' enrolled in ' . $activity_name .
        ( $matched_redemption ? ' with the existing Kudos redemption applied.' : ' successfully.' ),
        'success',
        'enrollments'
    );
}
add_action( 'admin_post_kla_admin_add_enrollment', 'kla_admin_add_enrollment' );

function kla_admin_update_enrollment() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_update_enrollment', 'kla_admin_nonce' );

    $enrollment_id = absint( $_POST['enrollment_id'] ?? 0 );

    if ( ! $enrollment_id || 'kudos_enrollment' !== get_post_type( $enrollment_id ) ) {
        kla_admin_dashboard_redirect( 'Invalid enrollment record.', 'error', 'enrollments' );
    }

    $fee            = max( 0, (float) ( $_POST['enrollment_fee'] ?? 0 ) );
    $requested_paid = max( 0, (float) ( $_POST['amount_paid'] ?? 0 ) );
    $payment_status = sanitize_key( $_POST['payment_status'] ?? 'pending' );
    $status         = sanitize_key( $_POST['enrollment_status'] ?? 'active' );
    $remarks        = sanitize_textarea_field( wp_unslash( $_POST['enrollment_remarks'] ?? '' ) );
    $adjustment_date = sanitize_text_field( wp_unslash( $_POST['payment_date'] ?? current_time( 'Y-m-d' ) ) );
    $adjustment_mode = sanitize_key( $_POST['payment_mode'] ?? 'other' );
    $adjustment_reference = sanitize_text_field( wp_unslash( $_POST['payment_reference'] ?? '' ) );
    $adjustment_notes = sanitize_textarea_field( wp_unslash( $_POST['payment_notes'] ?? '' ) );

    /*
     * Preserve the Kudos discount already attached to this enrollment.
     * Fee changes therefore update NET PAYABLE while keeping the redemption
     * linked to the enrollment.
     */
    $kudos_discount = max(
        0,
        (float) get_post_meta( $enrollment_id, '_kla_enrollment_kudos_discount', true )
    );

    $net_payable = max( 0, $fee - $kudos_discount );

    $current_totals = kla_admin_enrollment_totals( $enrollment_id );
    $current_paid = $current_totals['paid'];

    /*
     * Fee updates can change the payable amount. Payment increases are
     * recorded as proper installment/payment-history records below.
     * We do not silently reduce an existing paid total because that would
     * make the payment history inconsistent.
     */
    $amount_paid = max( $current_paid, $requested_paid );

    if ( ! in_array( $payment_status, array( 'paid', 'pending', 'partial' ), true ) ) {
        $payment_status = 'pending';
    }

    if ( ! in_array( $status, array( 'active', 'completed', 'cancelled' ), true ) ) {
        $status = 'active';
    }

    /*
     * "Paid" means the NET payable amount has been paid.
     * It must NOT force payment of the original fee when Kudos reduced it.
     */
    if ( 'paid' === $payment_status ) {
        $amount_paid = max( $amount_paid, $net_payable );
    } elseif ( 'pending' === $payment_status ) {
        /*
         * Do not erase existing payments when an admin opens the update form
         * and leaves the status as Pending. Existing paid history remains
         * authoritative.
         */
        $amount_paid = $current_paid;
    } elseif ( $amount_paid <= 0 ) {
        $payment_status = 'pending';
    } elseif ( $amount_paid >= $net_payable && $net_payable > 0 ) {
        $payment_status = 'paid';
    } else {
        $payment_status = 'partial';
    }

    $amount_due = max( 0, $net_payable - $amount_paid );
    $credit_balance = max( 0, $amount_paid - $net_payable );

    update_post_meta( $enrollment_id, '_kla_enrollment_fee', $fee );
    update_post_meta( $enrollment_id, '_kla_enrollment_net_payable', $net_payable );
    update_post_meta( $enrollment_id, '_kla_enrollment_amount_paid', $amount_paid );
    update_post_meta( $enrollment_id, '_kla_enrollment_amount_due', $amount_due );
    update_post_meta( $enrollment_id, '_kla_enrollment_credit_balance', $credit_balance );
    update_post_meta( $enrollment_id, '_kla_enrollment_payment_status', $payment_status );
    update_post_meta( $enrollment_id, '_kla_enrollment_status', $status );
    update_post_meta( $enrollment_id, '_kla_enrollment_remarks', $remarks );

    $payment_increase = max( 0, $amount_paid - $current_paid );

    if ( $payment_increase > 0 ) {
        $payment_result = kla_admin_record_fee_payment(
            $enrollment_id,
            $payment_increase,
            $adjustment_date,
            $adjustment_mode,
            $adjustment_reference,
            $adjustment_notes,
            'fee_update'
        );

        if ( is_wp_error( $payment_result ) ) {
            // Restore the previous paid total if a payment-history record
            // could not be created.
            update_post_meta( $enrollment_id, '_kla_enrollment_amount_paid', $current_paid );
            update_post_meta( $enrollment_id, '_kla_enrollment_amount_due', max( 0, $net_payable - $current_paid ) );
        }
    }

    kla_admin_dashboard_redirect(
        'Enrollment and fee details updated successfully.',
        'success',
        'enrollments'
    );
}
add_action( 'admin_post_kla_admin_update_enrollment', 'kla_admin_update_enrollment' );

/* -------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */

function kla_admin_dashboard_is_admin() {
    return current_user_can( 'manage_options' );
}

function kla_admin_dashboard_redirect( $message = '', $type = 'success', $tab = 'overview' ) {
    $url = wp_get_referer();

    if ( ! $url ) {
        $url = home_url( '/admin-dashboard/' );
    }

    $url = remove_query_arg(
        array( 'kla_admin_msg', 'kla_admin_msg_type', 'kla_admin_tab' ),
        $url
    );

    $url = add_query_arg(
        array(
            'kla_admin_msg'      => rawurlencode( $message ),
            'kla_admin_msg_type' => sanitize_key( $type ),
            'kla_admin_tab'      => sanitize_key( $tab ),
        ),
        $url
    );

    wp_safe_redirect( $url );
    exit;
}

function kla_admin_dashboard_post_count( $post_type ) {
    $counts = wp_count_posts( $post_type );

    if ( ! $counts ) {
        return 0;
    }

    return isset( $counts->publish ) ? absint( $counts->publish ) : 0;
}

function kla_admin_dashboard_parent_options() {
    return get_posts(
        array(
            'post_type'      => 'kudos_parent',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}

function kla_admin_dashboard_activity_options() {
    return get_posts(
        array(
            'post_type'      => 'kudos_activity',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}

function kla_admin_dashboard_student_options() {
    return get_posts(
        array(
            'post_type'      => 'kudos_student',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}

/* -------------------------------------------------------------------------
 * Create Student
 * ------------------------------------------------------------------------- */

function kla_admin_add_student() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_student', 'kla_admin_nonce' );

    $name     = sanitize_text_field( wp_unslash( $_POST['student_name'] ?? '' ) );
    $class    = sanitize_text_field( wp_unslash( $_POST['student_class'] ?? '' ) );
    $parent   = absint( $_POST['student_parent'] ?? 0 );
    $status   = sanitize_text_field( wp_unslash( $_POST['student_status'] ?? 'Active' ) );

    if ( ! $name ) {
        kla_admin_dashboard_redirect( 'Student name is required.', 'error', 'students' );
    }

    $student_id = wp_insert_post(
        array(
            'post_title'  => $name,
            'post_status' => 'publish',
            'post_type'   => 'kudos_student',
        ),
        true
    );

    if ( is_wp_error( $student_id ) ) {
        kla_admin_dashboard_redirect( $student_id->get_error_message(), 'error', 'students' );
    }

    update_post_meta( $student_id, '_kudos_student_name', $name );
    update_post_meta( $student_id, '_kudos_student_class', $class );
    update_post_meta( $student_id, '_kudos_student_parent', $parent );
    update_post_meta( $student_id, '_kudos_student_status', $status );
    update_post_meta( $student_id, '_kudos_student_rewards', 0 );

    kla_admin_dashboard_redirect( $name . ' added successfully.', 'success', 'students' );
}

add_action( 'admin_post_kla_admin_add_student', 'kla_admin_add_student' );

/* -------------------------------------------------------------------------
 * Create Parent
 * ------------------------------------------------------------------------- */

function kla_admin_add_parent() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_parent', 'kla_admin_nonce' );

    $name   = sanitize_text_field( wp_unslash( $_POST['parent_name'] ?? '' ) );
    $mobile = sanitize_text_field( wp_unslash( $_POST['parent_mobile'] ?? '' ) );
    $email  = sanitize_email( wp_unslash( $_POST['parent_email'] ?? '' ) );

    if ( ! $name ) {
        kla_admin_dashboard_redirect( 'Parent name is required.', 'error', 'parents' );
    }

    $parent_id = wp_insert_post(
        array(
            'post_title'  => $name,
            'post_status' => 'publish',
            'post_type'   => 'kudos_parent',
        ),
        true
    );

    if ( is_wp_error( $parent_id ) ) {
        kla_admin_dashboard_redirect( $parent_id->get_error_message(), 'error', 'parents' );
    }

    update_post_meta( $parent_id, '_kudos_parent_name', $name );
    update_post_meta( $parent_id, '_kudos_parent_mobile', $mobile );
    update_post_meta( $parent_id, '_kudos_parent_email', $email );

    kla_admin_dashboard_redirect( $name . ' added successfully.', 'success', 'parents' );
}

add_action( 'admin_post_kla_admin_add_parent', 'kla_admin_add_parent' );

/* -------------------------------------------------------------------------
 * Create Activity
 * ------------------------------------------------------------------------- */

function kla_admin_add_activity() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_activity', 'kla_admin_nonce' );

    $name        = sanitize_text_field( wp_unslash( $_POST['activity_name'] ?? '' ) );
    $category    = sanitize_text_field( wp_unslash( $_POST['activity_category'] ?? '' ) );
    $fee         = sanitize_text_field( wp_unslash( $_POST['activity_fee'] ?? '' ) );
    $fee_type    = sanitize_text_field( wp_unslash( $_POST['activity_fee_type'] ?? 'fixed' ) );
    $description = wp_kses_post( wp_unslash( $_POST['activity_description'] ?? '' ) );

    if ( ! $name ) {
        kla_admin_dashboard_redirect( 'Activity name is required.', 'error', 'activities' );
    }

    $activity_id = wp_insert_post(
        array(
            'post_title'   => $name,
            'post_content' => $description,
            'post_status'  => 'publish',
            'post_type'    => 'kudos_activity',
        ),
        true
    );

    if ( is_wp_error( $activity_id ) ) {
        kla_admin_dashboard_redirect( $activity_id->get_error_message(), 'error', 'activities' );
    }

    update_post_meta( $activity_id, '_kudos_activity_category', $category );
    update_post_meta( $activity_id, '_kudos_activity_fee', $fee );
    update_post_meta( $activity_id, '_kudos_activity_fee_type', $fee_type );

    kla_admin_dashboard_redirect( $name . ' added successfully.', 'success', 'activities' );
}

add_action( 'admin_post_kla_admin_add_activity', 'kla_admin_add_activity' );

/* -------------------------------------------------------------------------
 * Create Teacher User
 * ------------------------------------------------------------------------- */

function kla_admin_add_teacher() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_add_teacher', 'kla_admin_nonce' );

    $username = sanitize_user( wp_unslash( $_POST['teacher_username'] ?? '' ) );
    $name     = sanitize_text_field( wp_unslash( $_POST['teacher_name'] ?? '' ) );
    $email    = sanitize_email( wp_unslash( $_POST['teacher_email'] ?? '' ) );
    $password = (string) ( $_POST['teacher_password'] ?? '' );

    if ( ! $username || ! $name || ! $password ) {
        kla_admin_dashboard_redirect( 'Username, teacher name and password are required.', 'error', 'teachers' );
    }

    if ( username_exists( $username ) ) {
        kla_admin_dashboard_redirect( 'That username already exists.', 'error', 'teachers' );
    }

    if ( $email && email_exists( $email ) ) {
        kla_admin_dashboard_redirect( 'That email is already in use.', 'error', 'teachers' );
    }

    $user_id = wp_create_user( $username, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        kla_admin_dashboard_redirect( $user_id->get_error_message(), 'error', 'teachers' );
    }

    $user = new WP_User( $user_id );
    $user->set_role( 'kudos_teacher' );
    wp_update_user(
        array(
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        )
    );

    kla_admin_dashboard_redirect( $name . ' teacher account created successfully.', 'success', 'teachers' );
}

add_action( 'admin_post_kla_admin_add_teacher', 'kla_admin_add_teacher' );


/* -------------------------------------------------------------------------
 * Parent Login Management
 * ------------------------------------------------------------------------- */

/**
 * Create a WordPress login for an existing Parent CPT record.
 *
 * The Parent CPT remains the source of parent/contact data while the
 * WordPress user provides authentication.
 */
function kla_admin_create_parent_login() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_create_parent_login', 'kla_admin_nonce' );

    $parent_id = absint( $_POST['parent_id'] ?? 0 );
    $username  = sanitize_user( wp_unslash( $_POST['parent_username'] ?? '' ) );
    $password  = (string) ( $_POST['parent_password'] ?? '' );

    if ( ! $parent_id || 'kudos_parent' !== get_post_type( $parent_id ) ) {
        kla_admin_dashboard_redirect( 'Invalid parent record.', 'error', 'parents' );
    }

    $parent_name = get_post_meta( $parent_id, '_kudos_parent_name', true );
    if ( ! $parent_name ) {
        $parent_name = get_the_title( $parent_id );
    }

    $mobile = sanitize_text_field( get_post_meta( $parent_id, '_kudos_parent_mobile', true ) );
    $email  = sanitize_email( get_post_meta( $parent_id, '_kudos_parent_email', true ) );

    if ( ! $username ) {
        $username = sanitize_user( $mobile );

        if ( ! $username ) {
            $username = sanitize_user( $parent_name );
        }
    }

    if ( ! $password || strlen( $password ) < 8 ) {
        kla_admin_dashboard_redirect( 'Password must be at least 8 characters.', 'error', 'parents' );
    }

    $existing_link = get_users(
        array(
            'meta_key'   => '_kla_parent_record_id',
            'meta_value' => $parent_id,
            'number'     => 1,
            'fields'     => 'ID',
        )
    );

    if ( ! empty( $existing_link ) ) {
        kla_admin_dashboard_redirect( 'This parent already has a login account.', 'error', 'parents' );
    }

    if ( username_exists( $username ) ) {
        kla_admin_dashboard_redirect( 'That username already exists. Please choose another username.', 'error', 'parents' );
    }

    if ( $email && email_exists( $email ) ) {
        kla_admin_dashboard_redirect( 'That email is already in use by another WordPress account.', 'error', 'parents' );
    }

    $user_id = wp_create_user(
        $username,
        $password,
        $email
    );

    if ( is_wp_error( $user_id ) ) {
        kla_admin_dashboard_redirect( $user_id->get_error_message(), 'error', 'parents' );
    }

    $user = new WP_User( $user_id );
    $user->set_role( 'kudos_parent' );

    wp_update_user(
        array(
            'ID'           => $user_id,
            'display_name' => $parent_name,
            'first_name'   => $parent_name,
        )
    );

    update_user_meta( $user_id, '_kla_parent_record_id', $parent_id );
    update_user_meta( $user_id, '_kla_parent_mobile', $mobile );
    update_user_meta( $user_id, '_kla_parent_email', $email );
    update_post_meta( $parent_id, '_kla_parent_user_id', $user_id );
    update_post_meta( $parent_id, '_kla_parent_login_status', 'active' );

    kla_admin_dashboard_redirect( 'Login created successfully for ' . $parent_name . '.', 'success', 'parents' );
}

add_action( 'admin_post_kla_admin_create_parent_login', 'kla_admin_create_parent_login' );

/**
 * Reset the password for an existing parent login.
 */
function kla_admin_reset_parent_password() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_reset_parent_password', 'kla_admin_nonce' );

    $parent_id = absint( $_POST['parent_id'] ?? 0 );
    $password  = (string) ( $_POST['parent_password'] ?? '' );

    if ( ! $parent_id || 'kudos_parent' !== get_post_type( $parent_id ) ) {
        kla_admin_dashboard_redirect( 'Invalid parent record.', 'error', 'parents' );
    }

    if ( strlen( $password ) < 8 ) {
        kla_admin_dashboard_redirect( 'Password must be at least 8 characters.', 'error', 'parents' );
    }

    $user_id = absint( get_post_meta( $parent_id, '_kla_parent_user_id', true ) );

    if ( ! $user_id ) {
        $linked = get_users(
            array(
                'meta_key'   => '_kla_parent_record_id',
                'meta_value' => $parent_id,
                'number'     => 1,
                'fields'     => 'ID',
            )
        );

        if ( ! empty( $linked ) ) {
            $user_id = absint( $linked[0] );
        }
    }

    if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
        kla_admin_dashboard_redirect( 'No parent login account was found.', 'error', 'parents' );
    }

    wp_set_password( $password, $user_id );
    update_post_meta( $parent_id, '_kla_parent_login_status', 'active' );

    kla_admin_dashboard_redirect( 'Parent password reset successfully.', 'success', 'parents' );
}

add_action( 'admin_post_kla_admin_reset_parent_password', 'kla_admin_reset_parent_password' );

/**
 * Enable/disable an existing parent login.
 *
 * Disabled parent accounts receive a role with no capabilities.
 * The original parent role is restored when enabled.
 */
function kla_admin_toggle_parent_login() {
    if ( ! kla_admin_dashboard_is_admin() ) {
        wp_die( 'Administrator access required.' );
    }

    check_admin_referer( 'kla_admin_toggle_parent_login', 'kla_admin_nonce' );

    $parent_id = absint( $_POST['parent_id'] ?? 0 );
    $mode      = sanitize_key( $_POST['mode'] ?? '' );

    if ( ! $parent_id || ! in_array( $mode, array( 'enable', 'disable' ), true ) ) {
        kla_admin_dashboard_redirect( 'Invalid parent login action.', 'error', 'parents' );
    }

    $user_id = absint( get_post_meta( $parent_id, '_kla_parent_user_id', true ) );

    if ( ! $user_id ) {
        $linked = get_users(
            array(
                'meta_key'   => '_kla_parent_record_id',
                'meta_value' => $parent_id,
                'number'     => 1,
                'fields'     => 'ID',
            )
        );

        if ( ! empty( $linked ) ) {
            $user_id = absint( $linked[0] );
        }
    }

    $user = $user_id ? get_user_by( 'id', $user_id ) : false;

    if ( ! $user ) {
        kla_admin_dashboard_redirect( 'Parent login account was not found.', 'error', 'parents' );
    }

    if ( 'disable' === $mode ) {
        $user->set_role( 'kudos_parent_disabled' );
        update_post_meta( $parent_id, '_kla_parent_login_status', 'disabled' );
        kla_admin_dashboard_redirect( 'Parent login disabled.', 'success', 'parents' );
    }

    $user->set_role( 'kudos_parent' );
    update_post_meta( $parent_id, '_kla_parent_login_status', 'active' );

    kla_admin_dashboard_redirect( 'Parent login enabled.', 'success', 'parents' );
}

add_action( 'admin_post_kla_admin_toggle_parent_login', 'kla_admin_toggle_parent_login' );

/**
 * Disabled parent role.
 */
function kla_create_disabled_parent_role() {
    if ( ! get_role( 'kudos_parent_disabled' ) ) {
        add_role(
            'kudos_parent_disabled',
            'Kudos Parent Disabled',
            array(
                'read' => false,
            )
        );
    }
}
add_action( 'init', 'kla_create_disabled_parent_role', 5 );

/* -------------------------------------------------------------------------
 * Dashboard shortcode
 * ------------------------------------------------------------------------- */

function kla_admin_dashboard_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="kla-admin-denied">Please log in as an administrator.</div>';
    }

    if ( ! kla_admin_dashboard_is_admin() ) {
        return '<div class="kla-admin-denied">Administrator access required.</div>';
    }

    $active_tab = sanitize_key( $_GET['kla_admin_tab'] ?? 'overview' );

    $allowed_tabs = array(
        'overview',
        'students',
        'parents',
        'teachers',
        'activities',
        'enrollments',
        'reports',
        'payment_history',
        'performance',
        'attendance',
        'kudos',
        'redemptions',
        'notifications',
        'management',
    );

    if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
        $active_tab = 'overview';
    }

    $students      = kla_admin_dashboard_post_count( 'kudos_student' );
    $parents       = kla_admin_dashboard_post_count( 'kudos_parent' );
    $activities    = kla_admin_dashboard_post_count( 'kudos_activity' );
    $enrollments   = kla_admin_dashboard_post_count( 'kudos_enrollment' );
    $performance   = kla_admin_dashboard_post_count( 'kudos_performance' );
    $attendance    = kla_admin_dashboard_post_count( 'kudos_attendance' );
    $transactions  = kla_admin_dashboard_post_count( 'kudos_transaction' );
    $redemptions   = kla_admin_dashboard_post_count( 'kudos_redemption' );
    $notifications = kla_admin_dashboard_post_count( 'kla_notification' );

    $teacher_users = get_users(
        array(
            'role'   => 'kudos_teacher',
            'fields' => 'ID',
        )
    );

    $total_wallet = 0;
    foreach ( kla_admin_dashboard_student_options() as $student ) {
        $total_wallet += absint( get_post_meta( $student->ID, '_kudos_student_rewards', true ) );
    }

    $recent_transactions = get_posts(
        array(
            'post_type'      => 'kudos_transaction',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    $recent_students = get_posts(
        array(
            'post_type'      => 'kudos_student',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    ob_start();
    ?>
    <div class="kla-admin-dashboard">

        <div class="kla-admin-topbar">
            <div>
                <span class="kla-admin-eyebrow">Kudos Learning Academy</span>
                <h1>Admin Dashboard</h1>
                <p>Manage students, parents, teachers, activities, performance, attendance and Kudos from one place.</p>
            </div>
            <div class="kla-admin-user">
                <span><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/admin-dashboard/' ) ) ); ?>">Logout</a>
            </div>
        </div>

        <?php if ( ! empty( $_GET['kla_admin_msg'] ) ) : ?>
            <div class="kla-admin-alert <?php echo 'error' === ( $_GET['kla_admin_msg_type'] ?? '' ) ? 'is-error' : 'is-success'; ?>">
                <?php echo esc_html( rawurldecode( wp_unslash( $_GET['kla_admin_msg'] ) ) ); ?>
            </div>
        <?php endif; ?>

        <div class="kla-admin-layout">

            <aside class="kla-admin-sidebar">
                <div class="kla-admin-sidebar-title">Administration</div>

                <?php
                $nav = array(
                    'overview'       => '📊 Overview',
                    'students'      => '👨‍🎓 Students',
                    'parents'       => '👨‍👩‍👧 Parents',
                    'teachers'      => '👩‍🏫 Teachers',
                    'activities'   => '🎯 Activities',
                    'enrollments'  => '🎓 Enrollments & Fees',
                    'reports'      => '📊 Fee Reports',
                    'payment_history' => '💳 Payment History',
                    'performance'  => '📈 Performance',
                    'attendance'   => '📅 Attendance',
                    'kudos'        => '⭐ Kudos Wallet',
                    'redemptions'  => '🎁 Redemptions',
                    'notifications'=> '🔔 Notifications',
                    'management'   => '⚙️ Management',
                );

                foreach ( $nav as $key => $label ) :
                    ?>
                    <a class="<?php echo $active_tab === $key ? 'active' : ''; ?>"
                       href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', $key ) ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </aside>

            <main class="kla-admin-main">

                <?php if ( 'overview' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div>
                            <span>Overview</span>
                            <h2>Academy at a glance</h2>
                        </div>
                    </div>

                    <div class="kla-admin-stats">
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'students' ) ); ?>">
                            <span>Students</span><strong><?php echo esc_html( $students ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'parents' ) ); ?>">
                            <span>Parents</span><strong><?php echo esc_html( $parents ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'teachers' ) ); ?>">
                            <span>Teachers</span><strong><?php echo esc_html( count( $teacher_users ) ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'activities' ) ); ?>">
                            <span>Activities</span><strong><?php echo esc_html( $activities ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'enrollments' ) ); ?>">
                            <span>Enrollments</span><strong><?php echo esc_html( $enrollments ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'enrollments' ) ); ?>">
                            <span>Fee Payments</span><strong><?php echo esc_html( kla_admin_dashboard_post_count( 'kudos_fee_payment' ) ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'performance' ) ); ?>">
                            <span>Performance Records</span><strong><?php echo esc_html( $performance ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'attendance' ) ); ?>">
                            <span>Attendance Records</span><strong><?php echo esc_html( $attendance ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'kudos' ) ); ?>">
                            <span>Kudos Transactions</span><strong><?php echo esc_html( $transactions ); ?></strong>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'redemptions' ) ); ?>">
                            <span>Redemptions</span><strong><?php echo esc_html( $redemptions ); ?></strong>
                        </a>
                    </div>

                    <div class="kla-admin-two-col">
                        <section class="kla-admin-card">
                            <div class="kla-admin-card-head">
                                <h3>Quick Actions</h3>
                            </div>
                            <div class="kla-admin-actions">
                                <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'students' ) ); ?>">＋ Add Student</a>
                                <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'parents' ) ); ?>">＋ Add Parent</a>
                                <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'teachers' ) ); ?>">＋ Add Teacher</a>
                                <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'activities' ) ); ?>">＋ Add Activity</a>
                                <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'enrollments' ) ); ?>">＋ Enroll Student</a>
                                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kudos_performance' ) ); ?>">＋ Performance</a>
                                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kudos_attendance' ) ); ?>">＋ Attendance</a>
                            </div>
                        </section>

                        <section class="kla-admin-card kla-admin-wallet-summary">
                            <div class="kla-admin-card-head">
                                <h3>Kudos Wallet</h3>
                            </div>
                            <strong>⭐ <?php echo esc_html( number_format_i18n( $total_wallet ) ); ?></strong>
                            <p>Total current Kudos balance across all students.</p>
                            <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'kudos' ) ); ?>">Open Kudos Ledger →</a>
                        </section>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head">
                            <h3>Recent Students</h3>
                            <a href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'students' ) ); ?>">Manage all →</a>
                        </div>
                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table">
                                <thead><tr><th>Student</th><th>Parent</th><th>Class / Batch</th><th>Status</th><th>Kudos</th></tr></thead>
                                <tbody>
                                <?php foreach ( $recent_students as $student ) :
                                    $parent_id = absint( get_post_meta( $student->ID, '_kudos_student_parent', true ) );
                                    $parent_name = $parent_id ? get_the_title( $parent_id ) : 'Not linked';
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( kla_admin_dashboard_student_name( $student->ID ) ); ?></td>
                                        <td><?php echo esc_html( $parent_name ); ?></td>
                                        <td><?php echo esc_html( get_post_meta( $student->ID, '_kudos_student_class', true ) ?: '—' ); ?></td>
                                        <td><?php echo esc_html( get_post_meta( $student->ID, '_kudos_student_status', true ) ?: 'Active' ); ?></td>
                                        <td>⭐ <?php echo esc_html( absint( get_post_meta( $student->ID, '_kudos_student_rewards', true ) ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <?php elseif ( 'students' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Students</span><h2>Student Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_student' ) ); ?>">Open Full Student List →</a>
                    </div>

                    <section class="kla-admin-form-card">
                        <h3>Add New Student</h3>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="kla_admin_add_student">
                            <?php wp_nonce_field( 'kla_admin_add_student', 'kla_admin_nonce' ); ?>
                            <div class="kla-admin-form-grid">
                                <label>Student Name<input required name="student_name" type="text"></label>
                                <label>Class / Batch<input name="student_class" type="text"></label>
                                <label>Parent
                                    <select name="student_parent">
                                        <option value="0">— Not linked —</option>
                                        <?php foreach ( kla_admin_dashboard_parent_options() as $parent ) : ?>
                                            <option value="<?php echo esc_attr( $parent->ID ); ?>"><?php echo esc_html( $parent->post_title ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Status
                                    <select name="student_status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </label>
                            </div>
                            <button class="kla-admin-primary" type="submit">＋ Add Student</button>
                        </form>
                    </section>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head"><h3>Student Management</h3></div>
                        <div class="kla-admin-manage-links">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_student' ) ); ?>">Students List</a>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kudos_student' ) ); ?>">WordPress Student Editor</a>
                        </div>
                    </section>

                <?php elseif ( 'parents' === $active_tab ) : ?>

                    <?php
                    $all_parents = kla_admin_dashboard_parent_options();
                    ?>

                    <div class="kla-admin-section-head">
                        <div><span>Parents</span><h2>Parent Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_parent' ) ); ?>">Open Parent List →</a>
                    </div>

                    <section class="kla-admin-form-card">
                        <h3>Add New Parent</h3>
                        <p class="kla-admin-help">First create the Parent record. You can create the login immediately from the parent list below.</p>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="kla_admin_add_parent">
                            <?php wp_nonce_field( 'kla_admin_add_parent', 'kla_admin_nonce' ); ?>
                            <div class="kla-admin-form-grid">
                                <label>Parent Name<input required name="parent_name" type="text"></label>
                                <label>Mobile Number<input name="parent_mobile" type="text"></label>
                                <label>Email<input name="parent_email" type="email"></label>
                            </div>
                            <button class="kla-admin-primary" type="submit">＋ Add Parent</button>
                        </form>
                    </section>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head">
                            <h3>Parent Accounts & Logins</h3>
                            <span class="kla-admin-help"><?php echo esc_html( count( $all_parents ) ); ?> parent records</span>
                        </div>

                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table kla-admin-parent-table">
                                <thead>
                                    <tr>
                                        <th>Parent</th>
                                        <th>Mobile</th>
                                        <th>Email</th>
                                        <th>Children</th>
                                        <th>Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ( $all_parents as $parent ) : ?>
                                    <?php
                                    $parent_id = $parent->ID;
                                    $mobile = get_post_meta( $parent_id, '_kudos_parent_mobile', true );
                                    $email  = get_post_meta( $parent_id, '_kudos_parent_email', true );

                                    $user_id = absint( get_post_meta( $parent_id, '_kla_parent_user_id', true ) );

                                    if ( ! $user_id ) {
                                        $linked_users = get_users(
                                            array(
                                                'meta_key'   => '_kla_parent_record_id',
                                                'meta_value' => $parent_id,
                                                'number'     => 1,
                                                'fields'     => 'ID',
                                            )
                                        );

                                        if ( ! empty( $linked_users ) ) {
                                            $user_id = absint( $linked_users[0] );
                                            update_post_meta( $parent_id, '_kla_parent_user_id', $user_id );
                                        }
                                    }

                                    $parent_user = $user_id ? get_user_by( 'id', $user_id ) : false;
                                    $login_status = get_post_meta( $parent_id, '_kla_parent_login_status', true );

                                    if ( $parent_user ) {
                                        $login_status = $parent_user->has_cap( 'read' ) ? 'active' : 'disabled';
                                    }

                                    $children = get_posts(
                                        array(
                                            'post_type'      => 'kudos_student',
                                            'post_status'    => 'publish',
                                            'posts_per_page' => -1,
                                            'fields'         => 'ids',
                                            'meta_query'     => array(
                                                array(
                                                    'key'     => '_kudos_student_parent',
                                                    'value'   => $parent_id,
                                                    'compare' => '=',
                                                    'type'    => 'NUMERIC',
                                                ),
                                            ),
                                            'no_found_rows' => true,
                                        )
                                    );
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $parent->post_title ); ?></strong>
                                        </td>
                                        <td><?php echo esc_html( $mobile ?: '—' ); ?></td>
                                        <td><?php echo esc_html( $email ?: '—' ); ?></td>
                                        <td>
                                            <?php
                                            if ( $children ) {
                                                echo esc_html( count( $children ) ) . ' — ';

                                                $child_names = array();
                                                foreach ( $children as $child_id ) {
                                                    $child_names[] = kla_admin_dashboard_student_name( $child_id );
                                                }
                                                echo esc_html( implode( ', ', $child_names ) );
                                            } else {
                                                echo 'None linked';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ( $parent_user ) : ?>
                                                <span class="kla-admin-login-status <?php echo 'active' === $login_status ? 'is-active' : 'is-disabled'; ?>">
                                                    <?php echo 'active' === $login_status ? '✓ Active' : '✕ Disabled'; ?>
                                                </span>
                                                <small class="kla-admin-login-username"><?php echo esc_html( $parent_user->user_login ); ?></small>
                                            <?php else : ?>
                                                <span class="kla-admin-login-status is-missing">✕ Not Created</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="kla-admin-parent-actions">

                                                <?php if ( ! $parent_user ) : ?>

                                                    <details class="kla-admin-inline-details">
                                                        <summary>Create Login</summary>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                                            <input type="hidden" name="action" value="kla_admin_create_parent_login">
                                                            <input type="hidden" name="parent_id" value="<?php echo esc_attr( $parent_id ); ?>">
                                                            <?php wp_nonce_field( 'kla_admin_create_parent_login', 'kla_admin_nonce' ); ?>

                                                            <label>Username
                                                                <input type="text" name="parent_username" value="<?php echo esc_attr( $mobile ? sanitize_user( $mobile ) : '' ); ?>" required>
                                                            </label>

                                                            <label>Temporary Password
                                                                <input type="password" name="parent_password" minlength="8" required>
                                                            </label>

                                                            <button type="submit" class="kla-admin-mini-primary">Create Login</button>
                                                        </form>
                                                    </details>

                                                <?php else : ?>

                                                    <details class="kla-admin-inline-details">
                                                        <summary>Reset Password</summary>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                                            <input type="hidden" name="action" value="kla_admin_reset_parent_password">
                                                            <input type="hidden" name="parent_id" value="<?php echo esc_attr( $parent_id ); ?>">
                                                            <?php wp_nonce_field( 'kla_admin_reset_parent_password', 'kla_admin_nonce' ); ?>

                                                            <label>New Password
                                                                <input type="password" name="parent_password" minlength="8" required>
                                                            </label>

                                                            <button type="submit" class="kla-admin-mini-primary">Reset Password</button>
                                                        </form>
                                                    </details>

                                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kla-admin-inline-form">
                                                        <input type="hidden" name="action" value="kla_admin_toggle_parent_login">
                                                        <input type="hidden" name="parent_id" value="<?php echo esc_attr( $parent_id ); ?>">
                                                        <input type="hidden" name="mode" value="<?php echo 'active' === $login_status ? 'disable' : 'enable'; ?>">
                                                        <?php wp_nonce_field( 'kla_admin_toggle_parent_login', 'kla_admin_nonce' ); ?>
                                                        <button type="submit" class="kla-admin-mini-secondary">
                                                            <?php echo 'active' === $login_status ? 'Disable Login' : 'Enable Login'; ?>
                                                        </button>
                                                    </form>

                                                    <a class="kla-admin-mini-link" href="<?php echo esc_url( get_edit_user_link( $parent_user->ID ) ); ?>">Edit User</a>

                                                <?php endif; ?>

                                                <a class="kla-admin-mini-link" href="<?php echo esc_url( get_edit_post_link( $parent_id ) ); ?>">Edit Parent</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <?php elseif ( 'teachers' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Teachers</span><h2>Teacher Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">Open WordPress Users →</a>
                    </div>

                    <section class="kla-admin-form-card">
                        <h3>Create Teacher Login</h3>
                        <p class="kla-admin-help">This creates a WordPress user with the existing <strong>Kudos Teacher</strong> role.</p>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="kla_admin_add_teacher">
                            <?php wp_nonce_field( 'kla_admin_add_teacher', 'kla_admin_nonce' ); ?>
                            <div class="kla-admin-form-grid">
                                <label>Username<input required name="teacher_username" type="text"></label>
                                <label>Teacher Name<input required name="teacher_name" type="text"></label>
                                <label>Email<input name="teacher_email" type="email"></label>
                                <label>Password<input required name="teacher_password" type="password" minlength="8"></label>
                            </div>
                            <button class="kla-admin-primary" type="submit">＋ Create Teacher</button>
                        </form>
                    </section>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head"><h3>Teacher Accounts</h3></div>
                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table">
                                <thead><tr><th>Teacher</th><th>Username</th><th>Email</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach ( get_users( array( 'role' => 'kudos_teacher' ) ) as $teacher ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $teacher->display_name ); ?></td>
                                        <td><?php echo esc_html( $teacher->user_login ); ?></td>
                                        <td><?php echo esc_html( $teacher->user_email ?: '—' ); ?></td>
                                        <td><a href="<?php echo esc_url( get_edit_user_link( $teacher->ID ) ); ?>">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <?php elseif ( 'activities' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Activities</span><h2>Activity & Fee Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_activity' ) ); ?>">Open Activities →</a>
                    </div>

                    <section class="kla-admin-form-card">
                        <h3>Add New Activity</h3>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="kla_admin_add_activity">
                            <?php wp_nonce_field( 'kla_admin_add_activity', 'kla_admin_nonce' ); ?>
                            <div class="kla-admin-form-grid">
                                <label>Activity Name<input required name="activity_name" type="text"></label>
                                <label>Category
                                    <select name="activity_category">
                                        <option value="Skill-Based / Education">Skill-Based / Education</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Event">Event</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </label>
                                <label>Fee<input name="activity_fee" type="number" min="0" step="1"></label>
                                <label>Fee Type
                                    <select name="activity_fee_type">
                                        <option value="fixed">Fixed</option>
                                        <option value="based_on_activity">Based on Activity</option>
                                    </select>
                                </label>
                            </div>
                            <label class="kla-admin-full-field">Description<textarea name="activity_description" rows="4"></textarea></label>
                            <button class="kla-admin-primary" type="submit">＋ Add Activity</button>
                        </form>
                    </section>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head"><h3>Existing Activities</h3></div>
                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table">
                                <thead><tr><th>Activity</th><th>Category</th><th>Fee</th><th>Fee Type</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php foreach ( kla_admin_dashboard_activity_options() as $activity ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $activity->post_title ); ?></td>
                                        <td><?php echo esc_html( get_post_meta( $activity->ID, '_kudos_activity_category', true ) ?: '—' ); ?></td>
                                        <td>₹<?php echo esc_html( get_post_meta( $activity->ID, '_kudos_activity_fee', true ) ?: '0' ); ?></td>
                                        <td><?php echo esc_html( get_post_meta( $activity->ID, '_kudos_activity_fee_type', true ) ?: 'fixed' ); ?></td>
                                        <td><a href="<?php echo esc_url( get_edit_post_link( $activity->ID ) ); ?>">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <?php elseif ( 'enrollments' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div>
                            <span>Activity Enrollment</span>
                            <h2>Enroll Students & Manage Fees</h2>
                        </div>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head">
                            <h3>New Enrollment</h3>
                            <span>Fee is captured at the time of enrollment and can be updated later.</span>
                        </div>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kla-admin-form">
                            <input type="hidden" name="action" value="kla_admin_add_enrollment">
                            <?php wp_nonce_field( 'kla_admin_add_enrollment', 'kla_admin_nonce' ); ?>
                            <div class="kla-admin-form-grid">
                                <label>Student
                                    <select name="enrollment_student" id="kla-enrollment-student" required>
                                        <option value="">Select student</option>
                                        <?php foreach ( kla_admin_dashboard_student_options() as $student ) : ?>
                                            <option value="<?php echo esc_attr( $student->ID ); ?>"><?php echo esc_html( kla_enrollment_student_name( $student->ID ) ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Activity
                                    <select name="enrollment_activity" id="kla-enrollment-activity" required>
                                        <option value="">Select activity</option>
                                        <?php foreach ( kla_admin_dashboard_activity_options() as $activity ) :
                                            $activity_fee = kla_enrollment_activity_fee( $activity->ID ); ?>
                                            <option value="<?php echo esc_attr( $activity->ID ); ?>" data-fee="<?php echo esc_attr( $activity_fee ); ?>"><?php echo esc_html( $activity->post_title ); ?> — ₹<?php echo esc_html( number_format_i18n( $activity_fee ) ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Enrollment Date
                                    <input type="date" name="enrollment_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
                                </label>
                                <label>Activity Fee (₹)
                                    <input type="number" step="0.01" min="0" name="enrollment_fee" id="kla-enrollment-fee" value="0" required>
                                </label>
                                <label>Kudos Redeemed
                                    <input type="number" id="kla-enrollment-kudos" value="0" readonly>
                                </label>
                                <label>Kudos Benefit (₹)
                                    <input type="number" step="0.01" id="kla-enrollment-discount" value="0" readonly>
                                </label>
                                <label>Net Payable (₹)
                                    <input type="number" step="0.01" id="kla-enrollment-payable" value="0" readonly>
                                </label>
                                <label>Amount Paid (₹)
                                    <input type="number" step="0.01" min="0" name="amount_paid" value="0" required>
                                </label>
                                <label>Initial Payment Date
                                    <input type="date" name="initial_payment_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                                </label>
                                <label>Payment Mode
                                    <select name="initial_payment_mode">
                                        <?php foreach ( kla_fee_payment_modes() as $mode_key => $mode_label ) : ?>
                                            <option value="<?php echo esc_attr( $mode_key ); ?>"><?php echo esc_html( $mode_label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Payment Reference
                                    <input type="text" name="initial_payment_reference" placeholder="UPI/transaction/receipt no.">
                                </label>
                                <label>Payment Status
                                    <select name="payment_status">
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partially Paid</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </label>
                                <label>Enrollment Status
                                    <select name="enrollment_status">
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </label>
                                <label class="kla-admin-full-field">Remarks
                                    <textarea name="enrollment_remarks" rows="3" placeholder="Optional notes about fee/payment or enrollment"></textarea>
                                </label>
                            </div>
                            <div class="kla-admin-form-note">Amount Due is calculated from <strong>Net Payable − Amount Paid</strong>. Any initial amount paid is also saved as the first payment-history entry.</div>
                            <button type="submit" class="kla-admin-primary">Create Enrollment</button>
                        </form>
                    </section>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head">
                            <h3>Enrollment & Fee Records</h3>
                            <span>Update fee, amount paid, payment status and enrollment status anytime.</span>
                        </div>
                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Activity</th>
                                        <th>Date</th>
                                        <th>Fee</th>
                                        <th>Kudos</th>
                                        <th>Benefit</th>
                                        <th>Net Payable</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $enrollment_records = get_posts(
                                    array(
                                        'post_type'      => 'kudos_enrollment',
                                        'post_status'    => 'publish',
                                        'posts_per_page' => 100,
                                        'orderby'        => 'date',
                                        'order'          => 'DESC',
                                    )
                                );
                                if ( $enrollment_records ) :
                                    foreach ( $enrollment_records as $enrollment ) :
                                        $sid = absint( get_post_meta( $enrollment->ID, '_kla_enrollment_student_id', true ) );
                                        $aid = absint( get_post_meta( $enrollment->ID, '_kla_enrollment_activity_id', true ) );
                                        $edate = get_post_meta( $enrollment->ID, '_kla_enrollment_date', true );
                                        $fee = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_fee', true );
                                        $paid = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_amount_paid', true );
                                        $stored_net_payable = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_net_payable', true );
                                        $pay_status = get_post_meta( $enrollment->ID, '_kla_enrollment_payment_status', true ) ?: 'pending';
                                        $enroll_status = get_post_meta( $enrollment->ID, '_kla_enrollment_status', true ) ?: 'active';
                                        $remarks = get_post_meta( $enrollment->ID, '_kla_enrollment_remarks', true );
                                        $redemption_id = absint( get_post_meta( $enrollment->ID, '_kla_enrollment_redemption_id', true ) );
                                        $kudos_redeemed = (int) get_post_meta( $enrollment->ID, '_kla_enrollment_kudos_redeemed', true );
                                        $kudos_discount = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_kudos_discount', true );
                                        $net_payable = $stored_net_payable > 0
                                            ? $stored_net_payable
                                            : max( 0, $fee - $kudos_discount );
                                        $paid = min( max( 0, $paid ), $net_payable );
                                        $due = max( 0, $net_payable - $paid );

                                        if ( 'paid' === $pay_status ) {
                                            $paid = $net_payable;
                                            $due  = 0;
                                        }

                                        $student_name = $sid ? kla_enrollment_student_name( $sid ) : '—';
                                        $activity_name = $aid ? get_the_title( $aid ) : '—';
                                        ?>
                                        <tr>
                                            <td><strong><?php echo esc_html( $student_name ); ?></strong></td>
                                            <td><?php echo esc_html( $activity_name ); ?></td>
                                            <td><?php echo esc_html( $edate ?: '—' ); ?></td>
                                            <td>₹<?php echo esc_html( number_format_i18n( $fee, 2 ) ); ?></td>
                                            <td><?php echo esc_html( $kudos_redeemed ?: '—' ); ?></td>
                                            <td><?php echo $kudos_discount > 0 ? '₹' . esc_html( number_format_i18n( $kudos_discount, 2 ) ) : '—'; ?></td>
                                            <td><strong>₹<?php echo esc_html( number_format_i18n( $net_payable, 2 ) ); ?></strong><?php if ( $redemption_id ) : ?><small class="kla-admin-redemption-link">🎁 Redemption linked</small><?php endif; ?></td>
                                            <td>₹<?php echo esc_html( number_format_i18n( $paid, 2 ) ); ?></td>
                                            <td><strong>₹<?php echo esc_html( number_format_i18n( $due, 2 ) ); ?></strong></td>
                                            <td><span class="kla-admin-fee-status <?php echo esc_attr( 'is-' . $pay_status ); ?>"><?php echo esc_html( 'partial' === $pay_status ? 'Partially Paid' : ucfirst( $pay_status ) ); ?></span></td>
                                            <td><?php echo esc_html( ucfirst( $enroll_status ) ); ?></td>
                                            <td>
                                                <details class="kla-admin-inline-details">
                                                    <summary>Manage</summary>

                                                    <div class="kla-admin-payment-summary">
                                                        <strong>Payment History</strong>
                                                        <?php
                                                        $payment_history = kla_admin_get_fee_payments( $enrollment->ID );
                                                        if ( $payment_history ) :
                                                            ?>
                                                            <div class="kla-admin-payment-history">
                                                                <?php foreach ( $payment_history as $payment_record ) :
                                                                    $p_amount = (float) get_post_meta( $payment_record->ID, '_kla_fee_payment_amount', true );
                                                                    $p_date = get_post_meta( $payment_record->ID, '_kla_fee_payment_date', true );
                                                                    $p_mode = get_post_meta( $payment_record->ID, '_kla_fee_payment_mode', true );
                                                                    $p_ref = get_post_meta( $payment_record->ID, '_kla_fee_payment_reference', true );
                                                                    $p_notes = get_post_meta( $payment_record->ID, '_kla_fee_payment_notes', true );
                                                                    ?>
                                                                    <div class="kla-admin-payment-row">
                                                                        <div>
                                                                            <strong>₹<?php echo esc_html( number_format_i18n( $p_amount, 2 ) ); ?></strong>
                                                                            <span><?php echo esc_html( $p_date ?: '—' ); ?> · <?php echo esc_html( kla_fee_payment_mode_label( $p_mode ) ); ?></span>
                                                                        </div>
                                                                        <?php if ( $p_ref ) : ?><small>Ref: <?php echo esc_html( $p_ref ); ?></small><?php endif; ?>
                                                                        <?php if ( $p_notes ) : ?><small><?php echo esc_html( $p_notes ); ?></small><?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else : ?>
                                                            <p class="kla-admin-help">No payment-history entries yet. Existing total paid is preserved as the current balance.</p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ( $due > 0 ) : ?>
                                                    <details class="kla-admin-subdetails" open>
                                                        <summary>＋ Record Payment / Installment</summary>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                                            <input type="hidden" name="action" value="kla_admin_add_fee_payment">
                                                            <input type="hidden" name="fee_payment_enrollment_id" value="<?php echo esc_attr( $enrollment->ID ); ?>">
                                                            <?php wp_nonce_field( 'kla_admin_add_fee_payment', 'kla_admin_nonce' ); ?>
                                                            <label>Amount (₹)
                                                                <input type="number" step="0.01" min="0.01" max="<?php echo esc_attr( $due ); ?>" name="fee_payment_amount" required>
                                                            </label>
                                                            <label>Payment Date
                                                                <input type="date" name="fee_payment_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
                                                            </label>
                                                            <label>Payment Mode
                                                                <select name="fee_payment_mode">
                                                                    <?php foreach ( kla_fee_payment_modes() as $mode_key => $mode_label ) : ?>
                                                                        <option value="<?php echo esc_attr( $mode_key ); ?>"><?php echo esc_html( $mode_label ); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </label>
                                                            <label>Reference / Receipt No.
                                                                <input type="text" name="fee_payment_reference" placeholder="UPI / receipt / transaction no.">
                                                            </label>
                                                            <label>Notes
                                                                <textarea name="fee_payment_notes" rows="2" placeholder="Optional payment note"></textarea>
                                                            </label>
                                                            <button type="submit" class="kla-admin-mini-primary">Record Payment</button>
                                                        </form>
                                                    </details>
                                                    <?php else : ?>
                                                        <div class="kla-admin-paid-note">✓ Fully paid — no amount due.</div>
                                                    <?php endif; ?>

                                                    <details class="kla-admin-subdetails">
                                                        <summary>⚙ Update Fee / Enrollment</summary>
                                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                                            <input type="hidden" name="action" value="kla_admin_update_enrollment">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo esc_attr( $enrollment->ID ); ?>">
                                                            <?php wp_nonce_field( 'kla_admin_update_enrollment', 'kla_admin_nonce' ); ?>
                                                            <label>Fee (₹)<input type="number" step="0.01" min="0" name="enrollment_fee" value="<?php echo esc_attr( $fee ); ?>" required></label>
                                                            <label>Total Paid (₹)<input type="number" step="0.01" min="0" name="amount_paid" value="<?php echo esc_attr( $paid ); ?>" required></label>
                                                            <label>Payment Status
                                                                <select name="payment_status">
                                                                    <option value="pending" <?php selected( $pay_status, 'pending' ); ?>>Pending</option>
                                                                    <option value="partial" <?php selected( $pay_status, 'partial' ); ?>>Partially Paid</option>
                                                                    <option value="paid" <?php selected( $pay_status, 'paid' ); ?>>Paid</option>
                                                                </select>
                                                            </label>
                                                            <label>Enrollment Status
                                                                <select name="enrollment_status">
                                                                    <option value="active" <?php selected( $enroll_status, 'active' ); ?>>Active</option>
                                                                    <option value="completed" <?php selected( $enroll_status, 'completed' ); ?>>Completed</option>
                                                                    <option value="cancelled" <?php selected( $enroll_status, 'cancelled' ); ?>>Cancelled</option>
                                                                </select>
                                                            </label>
                                                            <label>Payment Date
                                                                <input type="date" name="payment_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                                                            </label>
                                                            <label>Payment Mode
                                                                <select name="payment_mode">
                                                                    <?php foreach ( kla_fee_payment_modes() as $mode_key => $mode_label ) : ?>
                                                                        <option value="<?php echo esc_attr( $mode_key ); ?>"><?php echo esc_html( $mode_label ); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </label>
                                                            <label>Payment Reference
                                                                <input type="text" name="payment_reference" placeholder="If this update records a payment">
                                                            </label>
                                                            <label>Remarks<textarea name="enrollment_remarks" rows="3"><?php echo esc_textarea( $remarks ); ?></textarea></label>
                                                            <button type="submit" class="kla-admin-mini-primary">Save Changes</button>
                                                        </form>
                                                    </details>
                                                </details>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="12">No enrollments yet.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const student = document.getElementById('kla-enrollment-student');
                        const activity = document.getElementById('kla-enrollment-activity');
                        const fee = document.getElementById('kla-enrollment-fee');
                        const kudos = document.getElementById('kla-enrollment-kudos');
                        const discount = document.getElementById('kla-enrollment-discount');
                        const payable = document.getElementById('kla-enrollment-payable');
                        const paid = document.querySelector('input[name="amount_paid"]');
                        const payment = document.querySelector('select[name="payment_status"]');
                        const redemptionMap = <?php
                            $redemption_map = array();
                            $redemptions = get_posts(array(
                                'post_type' => 'kudos_redemption',
                                'post_status' => 'publish',
                                'posts_per_page' => 500,
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'meta_query' => array(
                                    array('key' => '_kla_status', 'value' => 'Approved', 'compare' => '=')
                                ),
                            ));
                            foreach ( $redemptions as $r ) {
                                $rs = absint(get_post_meta($r->ID, '_kla_student_id', true));
                                $ra = absint(get_post_meta($r->ID, '_kla_activity_id', true));
                                if (!$rs || !$ra) continue;
                                $key = $rs . '_' . $ra;
                                if (isset($redemption_map[$key])) continue;
                                $redemption_map[$key] = kla_admin_redemption_snapshot($r->ID);
                            }
                            echo wp_json_encode($redemption_map);
                        ?>;

                        function refreshEnrollmentAmounts() {
                            if (!student || !activity) return;
                            const sid = student.value;
                            const aid = activity.value;
                            const selected = activity.options[activity.selectedIndex];
                            const baseFee = selected && selected.dataset.fee ? parseFloat(selected.dataset.fee) || 0 : 0;
                            const data = redemptionMap[sid + '_' + aid];

                            fee.value = data && data.payable >= 0 ? (data.activity_fee || baseFee) : baseFee;
                            kudos.value = data ? data.points : 0;
                            discount.value = data ? data.discount : 0;
                            payable.value = data ? data.payable : baseFee;

                            if (data) {
                                paid.value = data.paid;
                                payment.value = data.payment_status === 'paid' ? 'paid' : (data.payment_status === 'partial' ? 'partial' : 'pending');
                            }
                        }

                        if (student) student.addEventListener('change', refreshEnrollmentAmounts);
                        if (activity) activity.addEventListener('change', refreshEnrollmentAmounts);
                        refreshEnrollmentAmounts();
                    });
                    </script>


                <?php elseif ( 'reports' === $active_tab ) : ?>

                    <?php
                    $report_payment_status = isset( $_GET['report_payment_status'] ) ? sanitize_key( wp_unslash( $_GET['report_payment_status'] ) ) : '';
                    $report_from = isset( $_GET['report_from'] ) ? sanitize_text_field( wp_unslash( $_GET['report_from'] ) ) : '';
                    $report_to   = isset( $_GET['report_to'] ) ? sanitize_text_field( wp_unslash( $_GET['report_to'] ) ) : '';

                    $report_args = array(
                        'post_type'      => 'kudos_enrollment',
                        'post_status'    => array( 'publish', 'draft' ),
                        'posts_per_page' => 50,
                        'paged'          => max( 1, absint( $_GET['report_page'] ?? 1 ) ),
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    );

                    if ( $report_from || $report_to ) {
                        $report_args['date_query'] = array( 'inclusive' => true );
                        if ( $report_from ) {
                            $report_args['date_query']['after'] = $report_from . ' 00:00:00';
                        }
                        if ( $report_to ) {
                            $report_args['date_query']['before'] = $report_to . ' 23:59:59';
                        }
                    }

                    $report_query = new WP_Query( $report_args );
                    $report_total_net = 0;
                    $report_total_paid = 0;
                    $report_total_due = 0;
                    $report_total_credit = 0;
                    $report_active = 0;
                    $report_rows = array();

                    if ( $report_query->have_posts() ) {
                        while ( $report_query->have_posts() ) {
                            $report_query->the_post();
                            $rid = get_the_ID();

                            $r_status = sanitize_key( (string) get_post_meta( $rid, '_kla_enrollment_payment_status', true ) );
                            if ( $report_payment_status && $r_status !== $report_payment_status ) {
                                continue;
                            }

                            $rsid = absint( get_post_meta( $rid, '_kla_enrollment_student_id', true ) );
                            $raid = absint( get_post_meta( $rid, '_kla_enrollment_activity_id', true ) );

                            $r_fee = (float) get_post_meta( $rid, '_kla_enrollment_activity_fee', true );
                            if ( $r_fee <= 0 && $raid ) {
                                $r_fee = kla_enrollment_activity_fee( $raid );
                                if ( $r_fee > 0 ) {
                                    update_post_meta( $rid, '_kla_enrollment_fee', $r_fee );
                                }
                            }
                            $r_benefit = (float) get_post_meta( $rid, '_kla_enrollment_kudos_benefit', true );
                            $r_net = (float) get_post_meta( $rid, '_kla_enrollment_net_payable', true );
                            $r_paid = max( 0, (float) get_post_meta( $rid, '_kla_enrollment_amount_paid', true ) );
                            $r_enroll_status = sanitize_key( (string) get_post_meta( $rid, '_kla_enrollment_status', true ) );

                            if ( $r_net <= 0 ) {
                                $r_net = max( 0, $r_fee - $r_benefit );
                            }

                            /*
                             * Calculate Due/Credit from the authoritative
                             * payable + paid values. Older enrollment records
                             * may contain stale _amount_due metadata.
                             */
                            $r_due = max( 0, $r_net - $r_paid );
                            $r_credit = max( 0, $r_paid - $r_net );

                            update_post_meta( $rid, '_kla_enrollment_amount_due', $r_due );
                            update_post_meta( $rid, '_kla_enrollment_credit_balance', $r_credit );

                            $report_total_net += $r_net;
                            $report_total_paid += $r_paid;
                            $report_total_due += $r_due;
                            $report_total_credit += $r_credit;

                            if ( 'active' === $r_enroll_status ) {
                                $report_active++;
                            }

                            $report_rows[] = array(
                                'student'   => $rsid ? kla_enrollment_student_name( $rsid ) : get_post_meta( $rid, '_kla_enrollment_student_name', true ),
                                'activity'  => $raid ? get_the_title( $raid ) : get_post_meta( $rid, '_kla_enrollment_activity_name', true ),
                                'net'       => $r_net,
                                'paid'      => $r_paid,
                                'due'       => max( 0, $r_due ),
                                'credit'    => $r_credit,
                                'status'    => $r_status ?: 'pending',
                                'enroll'    => $r_enroll_status ?: 'active',
                                'date'      => get_the_date( 'Y-m-d', $rid ),
                            );
                        }
                        wp_reset_postdata();
                    }
                    ?>

                    <div class="kla-admin-section-head">
                        <div>
                            <span>Fee Reports</span>
                            <h2>💰 Collection & Outstanding Fees</h2>
                        </div>
                    </div>

                    <div class="kla-admin-report-cards">
                        <div class="kla-admin-report-card"><span>Net Payable</span><strong>₹<?php echo esc_html( number_format_i18n( $report_total_net, 2 ) ); ?></strong></div>
                        <div class="kla-admin-report-card"><span>Total Collected</span><strong>₹<?php echo esc_html( number_format_i18n( $report_total_paid, 2 ) ); ?></strong></div>
                        <div class="kla-admin-report-card"><span>Total Outstanding</span><strong>₹<?php echo esc_html( number_format_i18n( $report_total_due, 2 ) ); ?></strong></div>
                        <div class="kla-admin-report-card"><span>Credits / Advance</span><strong>₹<?php echo esc_html( number_format_i18n( $report_total_credit, 2 ) ); ?></strong></div>
                        <div class="kla-admin-report-card"><span>Active Enrollments</span><strong><?php echo esc_html( number_format_i18n( $report_active ) ); ?></strong></div>
                    </div>

                    <form method="get" class="kla-admin-report-filters">
                        <input type="hidden" name="kla_admin_tab" value="reports">
                        <div>
                            <label>Payment Status</label>
                            <select name="report_payment_status">
                                <option value="">All</option>
                                <option value="paid" <?php selected( $report_payment_status, 'paid' ); ?>>Paid</option>
                                <option value="partial" <?php selected( $report_payment_status, 'partial' ); ?>>Partially Paid</option>
                                <option value="pending" <?php selected( $report_payment_status, 'pending' ); ?>>Pending</option>
                            </select>
                        </div>
                        <div><label>From Date</label><input type="date" name="report_from" value="<?php echo esc_attr( $report_from ); ?>"></div>
                        <div><label>To Date</label><input type="date" name="report_to" value="<?php echo esc_attr( $report_to ); ?>"></div>
                        <button type="submit" class="kla-admin-mini-primary">Apply Filters</button>
                        <a class="kla-admin-mini-secondary" href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'reports', home_url( '/admin-dashboard/' ) ) ); ?>">Reset</a>
                    </form>

                    <div class="kla-admin-table-wrap">
                        <table class="kla-admin-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Activity</th>
                                    <th>Net Payable</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Credit</th>
                                    <th>Payment Status</th>
                                    <th>Enrollment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( $report_rows ) : ?>
                                    <?php foreach ( $report_rows as $row ) : ?>
                                        <tr>
                                            <td><?php echo esc_html( $row['student'] ?: '—' ); ?></td>
                                            <td><?php echo esc_html( $row['activity'] ?: '—' ); ?></td>
                                            <td>₹<?php echo esc_html( number_format_i18n( $row['net'], 2 ) ); ?></td>
                                            <td>₹<?php echo esc_html( number_format_i18n( $row['paid'], 2 ) ); ?></td>
                                            <td><strong>₹<?php echo esc_html( number_format_i18n( $row['due'], 2 ) ); ?></strong></td>
                                            <td><?php echo $row['credit'] > 0 ? '₹' . esc_html( number_format_i18n( $row['credit'], 2 ) ) : '—'; ?></td>
                                            <td><?php echo esc_html( 'partial' === $row['status'] ? 'Partially Paid' : ucfirst( $row['status'] ) ); ?></td>
                                            <td><?php echo esc_html( ucfirst( $row['enroll'] ) ); ?></td>
                                            <td><?php echo esc_html( $row['date'] ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="9">No enrollment records found for the selected filters.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $report_pagination = paginate_links(
                        array(
                            'base'      => add_query_arg( 'report_page', '%#%' ),
                            'format'    => '',
                            'current'   => max( 1, absint( $_GET['report_page'] ?? 1 ) ),
                            'total'     => max( 1, (int) $report_query->max_num_pages ),
                            'type'      => 'list',
                            'add_args'  => array_filter(
                                array(
                                    'kla_admin_tab'       => 'reports',
                                    'report_payment_status' => $report_payment_status,
                                    'report_from'         => $report_from,
                                    'report_to'           => $report_to,
                                )
                            ),
                        )
                    );
                    if ( $report_pagination ) :
                        echo '<div class="kla-admin-pagination">' . wp_kses_post( $report_pagination ) . '</div>';
                    endif;
                    ?>


                <?php elseif ( 'payment_history' === $active_tab ) : ?>

                    <?php
                    $ph_student = absint( $_GET['ph_student'] ?? 0 );
                    $ph_mode = sanitize_key( $_GET['ph_mode'] ?? '' );
                    $ph_from = sanitize_text_field( wp_unslash( $_GET['ph_from'] ?? '' ) );
                    $ph_to = sanitize_text_field( wp_unslash( $_GET['ph_to'] ?? '' ) );
                    $ph_page = max( 1, absint( $_GET['ph_page'] ?? 1 ) );

                    $ph_args = array(
                        'post_type'      => 'kudos_fee_payment',
                        'post_status'    => 'publish',
                        'posts_per_page' => 25,
                        'paged'          => $ph_page,
                        'orderby'        => 'meta_value',
                        'meta_key'       => '_kla_fee_payment_date',
                        'order'          => 'DESC',
                    );

                    $ph_meta = array( 'relation' => 'AND' );

                    if ( $ph_student ) {
                        $ph_meta[] = array(
                            'key' => '_kla_fee_payment_student_id',
                            'value' => $ph_student,
                            'compare' => '=',
                            'type' => 'NUMERIC',
                        );
                    }
                    if ( $ph_mode ) {
                        $ph_meta[] = array(
                            'key' => '_kla_fee_payment_mode',
                            'value' => $ph_mode,
                            'compare' => '=',
                        );
                    }
                    if ( $ph_from || $ph_to ) {
                        $date_clause = array(
                            'key' => '_kla_fee_payment_date',
                            'type' => 'DATE',
                        );
                        if ( $ph_from ) $date_clause['compare'] = '>=';
                        if ( $ph_from ) {
                            $ph_meta[] = array(
                                'key' => '_kla_fee_payment_date',
                                'value' => $ph_from,
                                'compare' => '>=',
                                'type' => 'DATE',
                            );
                        }
                        if ( $ph_to ) {
                            $ph_meta[] = array(
                                'key' => '_kla_fee_payment_date',
                                'value' => $ph_to,
                                'compare' => '<=',
                                'type' => 'DATE',
                            );
                        }
                    }
                    if ( count( $ph_meta ) > 1 ) {
                        $ph_args['meta_query'] = $ph_meta;
                    }

                    $ph_query = new WP_Query( $ph_args );
                    $ph_students = get_posts(
                        array(
                            'post_type' => 'kudos_student',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                        )
                    );
                    ?>

                    <div class="kla-admin-section-head">
                        <div>
                            <span>Payment History</span>
                            <h2>💳 All Fee Payments</h2>
                        </div>
                        <a class="kla-admin-mini-primary" href="<?php echo esc_url( add_query_arg( array( 'kla_admin_tab' => 'enrollments' ), home_url( '/admin-dashboard/' ) ) ); ?>">← Enrollments & Fees</a>
                    </div>

                    <form method="get" class="kla-admin-report-filters">
                        <input type="hidden" name="kla_admin_tab" value="payment_history">
                        <div>
                            <label>Student</label>
                            <select name="ph_student">
                                <option value="">All Students</option>
                                <?php foreach ( $ph_students as $ph_s ) : ?>
                                    <option value="<?php echo esc_attr( $ph_s->ID ); ?>" <?php selected( $ph_student, $ph_s->ID ); ?>>
                                        <?php echo esc_html( kla_admin_dashboard_student_name( $ph_s->ID ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Payment Mode</label>
                            <select name="ph_mode">
                                <option value="">All Modes</option>
                                <?php foreach ( kla_fee_payment_modes() as $mode_key => $mode_label ) : ?>
                                    <option value="<?php echo esc_attr( $mode_key ); ?>" <?php selected( $ph_mode, $mode_key ); ?>><?php echo esc_html( $mode_label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label>From Date</label><input type="date" name="ph_from" value="<?php echo esc_attr( $ph_from ); ?>"></div>
                        <div><label>To Date</label><input type="date" name="ph_to" value="<?php echo esc_attr( $ph_to ); ?>"></div>
                        <button type="submit" class="kla-admin-mini-primary">Apply Filters</button>
                        <a class="kla-admin-mini-secondary" href="<?php echo esc_url( add_query_arg( 'kla_admin_tab', 'payment_history', home_url( '/admin-dashboard/' ) ) ); ?>">Reset</a>
                    </form>

                    <div class="kla-admin-table-wrap">
                        <table class="kla-admin-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Activity</th>
                                    <th>Amount</th>
                                    <th>Mode</th>
                                    <th>Reference</th>
                                    <th>Recorded By</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ( $ph_query->have_posts() ) : ?>
                                <?php while ( $ph_query->have_posts() ) : $ph_query->the_post(); ?>
                                    <?php
                                    $pid = get_the_ID();
                                    $psid = absint( get_post_meta( $pid, '_kla_fee_payment_student_id', true ) );
                                    $paid = (float) get_post_meta( $pid, '_kla_fee_payment_amount', true );
                                    $pdate = get_post_meta( $pid, '_kla_fee_payment_date', true );
                                    $pmode = get_post_meta( $pid, '_kla_fee_payment_mode', true );
                                    $pref = get_post_meta( $pid, '_kla_fee_payment_reference', true );
                                    $puid = absint( get_post_meta( $pid, '_kla_fee_payment_recorded_by', true ) );
                                    $penroll = absint( get_post_meta( $pid, '_kla_fee_payment_enrollment_id', true ) );
                                    $receipt_url = add_query_arg(
                                        array(
                                            'kla_admin_receipt' => $pid,
                                            'kla_receipt_nonce' => wp_create_nonce( 'kla_admin_receipt_' . $pid ),
                                        ),
                                        home_url( '/admin-dashboard/' )
                                    );
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $pdate ?: get_the_date( 'Y-m-d', $pid ) ); ?></td>
                                        <td><?php echo esc_html( $psid ? kla_admin_dashboard_student_name( $psid ) : '—' ); ?></td>
                                        <td><?php echo esc_html( $penroll ? get_the_title( absint( get_post_meta( $penroll, '_kla_enrollment_activity_id', true ) ) ) : '—' ); ?></td>
                                        <td><strong>₹<?php echo esc_html( number_format_i18n( $paid, 2 ) ); ?></strong></td>
                                        <td><?php echo esc_html( kla_fee_payment_mode_label( $pmode ) ); ?></td>
                                        <td><?php echo esc_html( $pref ?: '—' ); ?></td>
                                        <td><?php echo esc_html( $puid ? get_the_author_meta( 'display_name', $puid ) : 'Admin' ); ?></td>
                                        <td><a class="kla-admin-mini-primary" target="_blank" rel="noopener" href="<?php echo esc_url( $receipt_url ); ?>">Print Receipt</a></td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); ?>
                            <?php else : ?>
                                <tr><td colspan="8">No payment records found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $ph_pagination = paginate_links(
                        array(
                            'base' => add_query_arg( 'ph_page', '%#%' ),
                            'format' => '',
                            'current' => $ph_page,
                            'total' => max( 1, (int) $ph_query->max_num_pages ),
                            'type' => 'list',
                            'add_args' => array_filter(
                                array(
                                    'kla_admin_tab' => 'payment_history',
                                    'ph_student' => $ph_student,
                                    'ph_mode' => $ph_mode,
                                    'ph_from' => $ph_from,
                                    'ph_to' => $ph_to,
                                )
                            ),
                        )
                    );
                    if ( $ph_pagination ) {
                        echo '<div class="kla-admin-pagination">' . wp_kses_post( $ph_pagination ) . '</div>';
                    }
                    ?>

                <?php elseif ( 'performance' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Learning Progress</span><h2>Performance Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_performance' ) ); ?>">Open Performance →</a>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-manage-links">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_performance' ) ); ?>">View All Performance Records</a>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kudos_performance' ) ); ?>">Add Performance Record</a>
                            <a href="<?php echo esc_url( home_url( '/teacher-dashboard/#performance' ) ); ?>">Teacher Performance Dashboard</a>
                        </div>
                    </section>

                <?php elseif ( 'attendance' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Attendance</span><h2>Attendance Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_attendance' ) ); ?>">Open Attendance →</a>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-manage-links">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_attendance' ) ); ?>">View Attendance Records</a>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=kudos_attendance' ) ); ?>">Add Attendance</a>
                            <a href="<?php echo esc_url( home_url( '/teacher-dashboard/#attendance' ) ); ?>">Teacher Attendance Dashboard</a>
                        </div>
                    </section>

                <?php elseif ( 'kudos' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Rewards</span><h2>Kudos Wallet & Ledger</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_transaction' ) ); ?>">Open Full Ledger →</a>
                    </div>

                    <div class="kla-admin-stats kla-admin-small-stats">
                        <div><span>Current Wallet Balance</span><strong>⭐ <?php echo esc_html( number_format_i18n( $total_wallet ) ); ?></strong></div>
                        <div><span>Total Transactions</span><strong><?php echo esc_html( $transactions ); ?></strong></div>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-card-head"><h3>Recent Kudos Transactions</h3></div>
                        <div class="kla-admin-table-wrap">
                            <table class="kla-admin-table">
                                <thead><tr><th>Transaction</th><th>Student</th><th>Points</th><th>Type</th><th>Reason</th><th>Date</th></tr></thead>
                                <tbody>
                                <?php foreach ( $recent_transactions as $transaction ) :
                                    $student_id = absint( get_post_meta( $transaction->ID, '_kla_transaction_student_id', true ) );
                                    $points     = absint( get_post_meta( $transaction->ID, '_kla_transaction_points', true ) );
                                    $type       = get_post_meta( $transaction->ID, '_kla_transaction_type', true );
                                    $reason     = get_post_meta( $transaction->ID, '_kla_transaction_reason', true );
                                    $negative   = in_array( strtolower( $type ), array( 'redemption', 'redeem' ), true );
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $transaction->post_title ); ?></td>
                                        <td><?php echo esc_html( $student_id ? kla_admin_dashboard_student_name( $student_id ) : '—' ); ?></td>
                                        <td class="<?php echo $negative ? 'kla-negative' : 'kla-positive'; ?>"><?php echo $negative ? '-' : '+'; ?><?php echo esc_html( $points ); ?></td>
                                        <td><?php echo esc_html( ucfirst( $type ?: 'award' ) ); ?></td>
                                        <td><?php echo esc_html( $reason ?: '—' ); ?></td>
                                        <td><?php echo esc_html( get_the_date( 'd M Y', $transaction->ID ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <?php elseif ( 'redemptions' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Rewards</span><h2>Redemption Management</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_redemption' ) ); ?>">Open Redemptions →</a>
                    </div>

                    <div class="kla-admin-stats kla-admin-small-stats">
                        <div><span>Total Redemptions</span><strong><?php echo esc_html( $redemptions ); ?></strong></div>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-manage-links">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_redemption' ) ); ?>">View Redemption History</a>
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_activity' ) ); ?>">Manage Redeemable Activities</a>
                        </div>
                    </section>

                <?php elseif ( 'notifications' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>Communication</span><h2>Parent Notifications</h2></div>
                        <a class="kla-admin-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kla_notification' ) ); ?>">Open Notifications →</a>
                    </div>

                    <div class="kla-admin-stats kla-admin-small-stats">
                        <div><span>Total Notifications</span><strong><?php echo esc_html( $notifications ); ?></strong></div>
                    </div>

                    <section class="kla-admin-card">
                        <div class="kla-admin-manage-links">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kla_notification' ) ); ?>">Notification History</a>
                        </div>
                    </section>

                <?php elseif ( 'management' === $active_tab ) : ?>

                    <div class="kla-admin-section-head">
                        <div><span>System</span><h2>Complete Management</h2></div>
                    </div>

                    <div class="kla-admin-management-grid">
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_student' ) ); ?>"><strong>👨‍🎓 Students</strong><span>View, edit and manage all students</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_parent' ) ); ?>"><strong>👨‍👩‍👧 Parents</strong><span>Manage parents and linked children</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_activity' ) ); ?>"><strong>🎯 Activities</strong><span>Activities, fees and redemption setup</span></a>
                        <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><strong>👩‍🏫 Teachers</strong><span>Teacher accounts and permissions</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_performance' ) ); ?>"><strong>📈 Performance</strong><span>Learning progress records</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_attendance' ) ); ?>"><strong>📅 Attendance</strong><span>Attendance records</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_transaction' ) ); ?>"><strong>⭐ Kudos Ledger</strong><span>Wallet awards and adjustments</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudos_redemption' ) ); ?>"><strong>🎁 Redemptions</strong><span>Redeemed activities and benefits</span></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kla_notification' ) ); ?>"><strong>🔔 Notifications</strong><span>Parent notification history</span></a>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><strong>⚙️ WordPress Settings</strong><span>System-level settings</span></a>
                    </div>

                <?php endif; ?>

            </main>
        </div>
    </div>

    <style>
        /* ============================================================
           ADMIN DASHBOARD - ISOLATED FULL-WIDTH SHELL
           Keep the dashboard independent from the theme's page/container
           widths. The header, sidebar and content all share one shell.
           ============================================================ */

        body:has(.kla-admin-dashboard) .entry-header,
        body:has(.kla-admin-dashboard) .page-header,
        body:has(.kla-admin-dashboard) .page-title-wrap,
        body:has(.kla-admin-dashboard) .page-banner,
        body:has(.kla-admin-dashboard) .breadcrumb-area,
        body:has(.kla-admin-dashboard) .breadcrumb,
        body:has(.kla-admin-dashboard) .entry-title{
            display:none !important;
        }

        body:has(.kla-admin-dashboard) .site-main,
        body:has(.kla-admin-dashboard) main,
        body:has(.kla-admin-dashboard) .content-area,
        body:has(.kla-admin-dashboard) .container,
        body:has(.kla-admin-dashboard) .container-fluid,
        body:has(.kla-admin-dashboard) .page-content,
        body:has(.kla-admin-dashboard) .entry-content{
            width:100% !important;
            max-width:none !important;
            min-width:0 !important;
        }

        body:has(.kla-admin-dashboard) .entry-content{
            margin:0 !important;
            padding:0 !important;
        }

        body:has(.kla-admin-dashboard) .site-main{
            margin-left:0 !important;
            margin-right:0 !important;
            padding-left:0 !important;
            padding-right:0 !important;
        }

        .kla-admin-dashboard{
            --g:#2d7b3c;
            --p:#30205a;
            --c:#fffaf0;
            --soft:#eef6dd;
            --txt:#5b5668;

            width:calc(100vw - 48px) !important;
            max-width:1600px !important;
            margin:25px auto 70px !important;
            position:relative !important;
            left:auto !important;
            right:auto !important;
            transform:none !important;
            color:var(--txt);
            font-family:inherit;
        }

        .kla-admin-dashboard *{
            box-sizing:border-box;
        }

        .kla-admin-topbar{
            width:100%;
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            align-items:center;
            gap:24px;
            margin:0 0 20px;
            padding:0;
        }

        .kla-admin-topbar > div:first-child{
            min-width:0;
        }

        .kla-admin-eyebrow,
        .kla-admin-section-head>div>span{
            display:block;
            color:var(--g);
            font-size:10px;
            font-weight:900;
            letter-spacing:.12em;
            text-transform:uppercase;
            margin-bottom:4px;
        }

        .kla-admin-topbar h1{
            margin:0;
            color:var(--p);
            font-size:34px;
            line-height:1.1;
        }

        .kla-admin-topbar p{
            margin:7px 0 0;
            font-size:13px;
        }

        .kla-admin-user{
            display:flex !important;
            align-items:center !important;
            justify-content:flex-end !important;
            gap:10px;
            white-space:nowrap;
            font-size:12px;
            font-weight:800;
            min-width:max-content;
        }

        .kla-admin-user a{
            display:inline-flex !important;
            align-items:center;
            justify-content:center;
            padding:9px 14px;
            min-height:38px;
            border-radius:9px;
            background:var(--g) !important;
            color:#fff !important;
            text-decoration:none !important;
            line-height:1;
        }
        .kla-admin-alert{
            padding:12px 15px;
            border-radius:10px;
            margin-bottom:15px;
            font-size:12px;
            font-weight:700;
        }
        .kla-admin-alert.is-success{background:#eef6dd;color:#26743a}
        .kla-admin-alert.is-error{background:#fff0ed;color:#b42318}
        .kla-admin-layout{
            display:grid;
            grid-template-columns:250px minmax(0,1fr);
            gap:18px;
            align-items:start;
        }
        .kla-admin-sidebar{
            position:sticky;
            top:20px;
            padding:12px;
            background:#fff;
            border:1px solid #ece7d9;
            border-radius:16px;
            box-shadow:0 7px 24px rgba(48,32,90,.05);
        }
        .kla-admin-sidebar-title{
            padding:7px 9px 10px;
            color:#8a8491;
            font-size:9px;
            font-weight:900;
            letter-spacing:.1em;
            text-transform:uppercase;
        }
        .kla-admin-sidebar a{
            display:block;
            padding:11px 10px;
            margin:2px 0;
            border-radius:9px;
            color:var(--txt);
            text-decoration:none;
            font-size:11px;
            font-weight:800;
        }
        .kla-admin-sidebar a:hover,
        .kla-admin-sidebar a.active{
            background:var(--g);
            color:#fff;
        }
        .kla-admin-main{min-width:0}
        .kla-admin-section-head{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:15px;
            margin-bottom:15px;
        }
        .kla-admin-section-head h2{
            margin:0;
            color:var(--p);
            font-size:25px;
        }
        .kla-admin-primary{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:0;
            border-radius:9px;
            padding:10px 13px;
            background:var(--p);
            color:#fff!important;
            text-decoration:none!important;
            font-size:10px;
            font-weight:900;
            cursor:pointer;
        }
        .kla-admin-stats{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:10px;
            margin-bottom:15px;
        }
        .kla-admin-stats>a,
        .kla-admin-stats>div{
            display:block;
            padding:15px;
            background:#fff;
            border:1px solid #ece7d9;
            border-radius:13px;
            text-decoration:none;
            box-shadow:0 5px 16px rgba(48,32,90,.04);
        }
        .kla-admin-stats span{
            display:block;
            color:#8a8491;
            font-size:9px;
            font-weight:900;
            text-transform:uppercase;
        }
        .kla-admin-stats strong{
            display:block;
            margin-top:5px;
            color:var(--p);
            font-size:24px;
        }
        .kla-admin-small-stats{grid-template-columns:repeat(2,1fr)}
        .kla-admin-two-col{
            display:grid;
            grid-template-columns:1.5fr 1fr;
            gap:15px;
            margin-bottom:15px;
        }
        .kla-admin-card,
        .kla-admin-form-card{
            padding:18px;
            background:#fff;
            border:1px solid #ece7d9;
            border-radius:15px;
            box-shadow:0 6px 20px rgba(48,32,90,.04);
            margin-bottom:15px;
        }
        .kla-admin-card-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-bottom:13px;
        }
        .kla-admin-card-head h3,
        .kla-admin-form-card h3{
            margin:0;
            color:var(--p);
            font-size:16px;
        }
        .kla-admin-card-head a{
            color:var(--p);
            font-size:10px;
            font-weight:900;
            text-decoration:none;
        }
        .kla-admin-actions{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:8px;
        }
        .kla-admin-actions a,
        .kla-admin-manage-links a{
            display:block;
            padding:11px 12px;
            border:1px solid #e7e1d5;
            border-radius:9px;
            color:var(--p);
            background:var(--c);
            text-decoration:none;
            font-size:10px;
            font-weight:900;
        }
        .kla-admin-actions a:hover,
        .kla-admin-manage-links a:hover{border-color:var(--g)}
        .kla-admin-wallet-summary>strong{
            display:block;
            color:var(--p);
            font-size:31px;
            margin:10px 0 2px;
        }
        .kla-admin-wallet-summary p{font-size:11px;margin:0 0 10px}
        .kla-admin-wallet-summary a{font-size:10px;color:var(--p);font-weight:900;text-decoration:none}
        .kla-admin-table-wrap{width:100%;overflow:auto}
        .kla-admin-table{
            width:100%;
            border-collapse:collapse;
            font-size:10px;
            min-width:620px;
        }
        .kla-admin-table th{
            padding:9px;
            text-align:left;
            color:#777080;
            background:var(--c);
            font-size:8px;
            text-transform:uppercase;
            letter-spacing:.05em;
        }
        .kla-admin-table td{
            padding:10px 9px;
            border-bottom:1px solid #eee9df;
            color:#50495c;
        }
        .kla-admin-table td a{color:var(--p);font-weight:800}
        .kla-positive{color:var(--g)!important;font-weight:900}
        .kla-negative{color:#b42318!important;font-weight:900}
        .kla-admin-form-card form{margin-top:14px}
        .kla-admin-form-grid{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:12px;
            margin-bottom:13px;
        }
        .kla-admin-form-card label{
            display:block;
            color:#777080;
            font-size:10px;
            font-weight:900;
        }
        .kla-admin-form-card input,
        .kla-admin-form-card select,
        .kla-admin-form-card textarea{
            display:block;
            width:100%;
            margin-top:5px;
            padding:10px 11px;
            border:1px solid #ddd7ca;
            border-radius:8px;
            background:#fff;
            color:var(--p);
            font:inherit;
            outline:none;
        }
        .kla-admin-form-card textarea{resize:vertical}
        .kla-admin-form-card input:focus,
        .kla-admin-form-card select:focus,
        .kla-admin-form-card textarea:focus{
            border-color:var(--g);
            box-shadow:0 0 0 3px rgba(45,123,60,.08);
        }
        .kla-admin-full-field{
            display:block;
            margin-bottom:13px;
        }
        .kla-admin-help{
            font-size:11px;
            margin:7px 0 0;
            color:#777080;
        }
        .kla-admin-manage-links{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:9px;
        }
        .kla-admin-management-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:11px;
        }
        .kla-admin-management-grid a{
            display:block;
            padding:16px;
            background:#fff;
            border:1px solid #ece7d9;
            border-radius:13px;
            text-decoration:none;
            box-shadow:0 5px 16px rgba(48,32,90,.04);
        }
        .kla-admin-management-grid strong{
            display:block;
            color:var(--p);
            font-size:13px;
        }
        .kla-admin-management-grid span{
            display:block;
            color:#777080;
            font-size:10px;
            margin-top:5px;
            line-height:1.4;
        }

        .kla-admin-payment-summary{
            margin:10px 0 12px;
            padding:10px;
            background:#faf8f2;
            border:1px solid #eee8dc;
            border-radius:9px;
        }
        .kla-admin-payment-summary>strong{
            display:block;
            color:var(--p);
            font-size:11px;
            margin-bottom:7px;
        }
        .kla-admin-payment-history{
            display:grid;
            gap:6px;
        }
        .kla-admin-payment-row{
            padding:8px;
            background:#fff;
            border:1px solid #eee8dc;
            border-radius:7px;
            font-size:9px;
        }
        .kla-admin-payment-row>div{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
        }
        .kla-admin-payment-row strong{color:var(--p)}
        .kla-admin-payment-row span,
        .kla-admin-payment-row small{
            display:block;
            color:#777080;
            margin-top:3px;
            line-height:1.35;
        }
        .kla-admin-subdetails{
            margin-top:9px;
            padding:8px;
            border:1px solid #e7e1d5;
            border-radius:8px;
            background:#fff;
        }
        .kla-admin-subdetails>summary{
            cursor:pointer;
            color:var(--p);
            font-size:10px;
            font-weight:900;
        }
        .kla-admin-subdetails form{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:8px;
            margin-top:9px;
        }
        .kla-admin-subdetails label{
            display:block;
            color:#777080;
            font-size:9px;
            font-weight:900;
        }
        .kla-admin-subdetails input,
        .kla-admin-subdetails select,
        .kla-admin-subdetails textarea{
            display:block;
            width:100%;
            margin-top:4px;
            padding:8px;
            border:1px solid #ddd7ca;
            border-radius:7px;
            background:#fff;
            color:var(--p);
            font:inherit;
        }
        .kla-admin-subdetails textarea{resize:vertical}
        .kla-admin-subdetails button{
            grid-column:1 / -1;
            justify-self:start;
        }
        .kla-admin-paid-note{
            margin-top:9px;
            padding:8px 10px;
            border-radius:8px;
            background:#eef6dd;
            color:#26743a;
            font-size:9px;
            font-weight:900;
        }

        .kla-admin-denied{
            max-width:600px;
            margin:50px auto;
            padding:25px;
            background:#fff0ed;
            color:#b42318;
            border-radius:12px;
            text-align:center;
            font-weight:800;
        }

        .kla-admin-login-status{
            display:inline-flex;
            padding:5px 8px;
            border-radius:999px;
            font-size:8px;
            font-weight:900;
            white-space:nowrap;
        }
        .kla-admin-login-status.is-active{background:#eef6dd;color:#26743a}
        .kla-admin-login-status.is-disabled{background:#fff0ed;color:#b42318}
        .kla-admin-login-status.is-missing{background:#f7f4eb;color:#777080}
        .kla-admin-login-username{
            display:block;
            color:#8a8491;
            font-size:8px;
            margin-top:4px;
        }
        .kla-admin-parent-actions{
            display:flex;
            flex-wrap:wrap;
            gap:5px;
            align-items:center;
        }
        .kla-admin-inline-details{
            position:relative;
        }
        .kla-admin-inline-details summary{
            list-style:none;
            cursor:pointer;
            padding:7px 9px;
            border-radius:7px;
            background:#eef6dd;
            color:#26743a;
            font-size:9px;
            font-weight:900;
            white-space:nowrap;
        }
        .kla-admin-inline-details summary::-webkit-details-marker{display:none}
        .kla-admin-inline-details[open] summary{
            background:#30205a;
            color:#fff;
        }
        .kla-admin-inline-details form{
            position:absolute;
            z-index:20;
            top:35px;
            right:0;
            width:220px;
            padding:12px;
            background:#fff;
            border:1px solid #ddd7ca;
            border-radius:10px;
            box-shadow:0 12px 30px rgba(48,32,90,.16);
        }
        .kla-admin-inline-details label{
            display:block;
            margin-bottom:8px;
            color:#777080;
            font-size:9px;
            font-weight:900;
        }
        .kla-admin-inline-details input{
            display:block;
            width:100%;
            margin-top:4px;
            padding:8px;
            border:1px solid #ddd7ca;
            border-radius:6px;
            font-size:10px;
            box-sizing:border-box;
        }
        .kla-admin-mini-primary,
        .kla-admin-mini-secondary{
            border:0;
            border-radius:7px;
            padding:7px 9px;
            font-size:9px;
            font-weight:900;
            cursor:pointer;
        }
        .kla-admin-mini-primary{background:#2d7b3c;color:#fff}
        .kla-admin-mini-secondary{background:#fff0ed;color:#b42318}
        .kla-admin-mini-link{
            display:inline-block;
            padding:7px 8px;
            border-radius:7px;
            background:#f7f4eb;
            color:#30205a!important;
            text-decoration:none!important;
            font-size:9px;
            font-weight:900;
            white-space:nowrap;
        }
        .kla-admin-inline-form{margin:0}


        .kla-admin-form-grid{
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:12px;
            margin-bottom:14px;
        }
        .kla-admin-form-grid label,
        .kla-admin-inline-details form label{
            display:block;
            color:#777080;
            font-size:10px;
            font-weight:900;
        }
        .kla-admin-form-grid input,
        .kla-admin-form-grid select,
        .kla-admin-form-grid textarea{
            width:100%;
            margin-top:5px;
            padding:10px 11px;
            border:1px solid #ddd7ca;
            border-radius:8px;
            background:#fff;
            color:#40394d;
            font-size:11px;
            box-sizing:border-box;
        }
        .kla-admin-full-field{grid-column:1/-1}
        .kla-admin-form-note{
            margin:0 0 12px;
            padding:10px 12px;
            border-radius:8px;
            background:#eef6dd;
            color:#26743a;
            font-size:10px;
        }
        .kla-admin-fee-status{
            display:inline-flex;
            padding:5px 8px;
            border-radius:999px;
            font-size:8px;
            font-weight:900;
            white-space:nowrap;
        }
        .kla-admin-fee-status.is-paid{background:#eef6dd;color:#26743a}
        .kla-admin-fee-status.is-pending{background:#fff0ed;color:#b42318}
        .kla-admin-fee-status.is-partial{background:#fff7dc;color:#8a6500}
        .kla-admin-inline-details form select,
        .kla-admin-inline-details form textarea{
            display:block;
            width:100%;
            margin-top:4px;
            padding:8px;
            border:1px solid #ddd7ca;
            border-radius:6px;
            font-size:10px;
            box-sizing:border-box;
        }


        @media (max-width:900px){
            .kla-admin-subdetails form{grid-template-columns:1fr}
        }

        @media(max-width:1000px){
            .kla-admin-dashboard{
                width:calc(100vw - 32px) !important;
                max-width:none !important;
                margin:20px auto 50px !important;
            }
            .kla-admin-stats{grid-template-columns:repeat(2,1fr)}
            .kla-admin-management-grid{grid-template-columns:repeat(2,1fr)}
        }

        @media(max-width:760px){
            .kla-admin-dashboard{
                width:calc(100vw - 20px) !important;
                max-width:none !important;
                margin:15px auto 40px !important;
            }
            .kla-admin-topbar{
                grid-template-columns:1fr;
                align-items:flex-start;
                gap:12px;
            }
            .kla-admin-user{
                width:100%;
                justify-content:flex-start !important;
            }
            .kla-admin-layout{grid-template-columns:1fr}
            .kla-admin-sidebar{position:static;display:grid;grid-template-columns:repeat(2,1fr);gap:3px}
            .kla-admin-sidebar-title{grid-column:1/-1}
            .kla-admin-two-col{grid-template-columns:1fr}
            .kla-admin-actions{grid-template-columns:repeat(2,1fr)}
            .kla-admin-manage-links{grid-template-columns:1fr}
            .kla-admin-management-grid{grid-template-columns:1fr}
        }

        @media(max-width:760px){
            .kla-admin-inline-details form{
                position:fixed;
                top:50%;
                left:50%;
                right:auto;
                transform:translate(-50%,-50%);
                width:min(280px,calc(100vw - 30px));
            }
        }

        @media(max-width:520px){
            .kla-admin-stats{grid-template-columns:1fr}
            .kla-admin-small-stats{grid-template-columns:1fr}
            .kla-admin-form-grid{grid-template-columns:1fr}
            .kla-admin-topbar h1{font-size:28px}
        }
    
        .kla-admin-redemption-link{display:block;margin-top:4px;font-size:11px;color:#2d7b3c;font-weight:700;}
        #kla-enrollment-kudos,#kla-enrollment-discount,#kla-enrollment-payable{background:#f4f7f0;font-weight:700;}

        .kla-admin-report-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:0 0 22px}
        .kla-admin-report-card{background:#fff;border:1px solid #e7e3ed;border-radius:14px;padding:20px;box-shadow:0 5px 18px rgba(48,32,90,.06)}
        .kla-admin-report-card span{display:block;font-size:13px;color:#777185;margin-bottom:8px}
        .kla-admin-report-card strong{display:block;font-size:25px;color:#30205a}
        .kla-admin-report-filters{display:flex;flex-wrap:wrap;align-items:end;gap:12px;background:#fff;border:1px solid #e7e3ed;border-radius:14px;padding:18px;margin-bottom:20px}
        .kla-admin-report-filters>div{min-width:170px}
        .kla-admin-report-filters label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#5b5668}
        .kla-admin-report-filters select,.kla-admin-report-filters input{width:100%;min-height:40px;border:1px solid #ddd7e6;border-radius:8px;padding:8px 10px;background:#fff}
        .kla-admin-pagination{margin-top:18px}
        .kla-admin-pagination ul{display:flex;gap:7px;list-style:none;padding:0}
        .kla-admin-pagination a,.kla-admin-pagination span{display:inline-block;padding:7px 11px;border:1px solid #ddd7e6;border-radius:7px;text-decoration:none}
        @media(max-width:900px){.kla-admin-report-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.kla-admin-report-cards{grid-template-columns:1fr}.kla-admin-report-filters>div{width:100%}}
</style>
    <?php

    return ob_get_clean();
}


/* -------------------------------------------------------------------------
 * Printable Fee Receipt
 * ------------------------------------------------------------------------- */
function kla_admin_print_fee_receipt() {
    if ( empty( $_GET['kla_admin_receipt'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $payment_id = absint( $_GET['kla_admin_receipt'] );
    $nonce = sanitize_text_field( wp_unslash( $_GET['kla_receipt_nonce'] ?? '' ) );

    if ( ! $payment_id || 'kudos_fee_payment' !== get_post_type( $payment_id ) || ! wp_verify_nonce( $nonce, 'kla_admin_receipt_' . $payment_id ) ) {
        wp_die( 'Invalid or expired receipt link.' );
    }

    $sid = absint( get_post_meta( $payment_id, '_kla_fee_payment_student_id', true ) );
    $aid = absint( get_post_meta( $payment_id, '_kla_fee_payment_activity_id', true ) );
    $eid = absint( get_post_meta( $payment_id, '_kla_fee_payment_enrollment_id', true ) );

    $amount = (float) get_post_meta( $payment_id, '_kla_fee_payment_amount', true );
    $date = get_post_meta( $payment_id, '_kla_fee_payment_date', true );
    $mode = get_post_meta( $payment_id, '_kla_fee_payment_mode', true );
    $reference = get_post_meta( $payment_id, '_kla_fee_payment_reference', true );
    $notes = get_post_meta( $payment_id, '_kla_fee_payment_notes', true );

    $student = $sid ? kla_admin_dashboard_student_name( $sid ) : '—';
    $activity = $aid ? get_the_title( $aid ) : '—';
    $enrollment_no = $eid ? 'ENR-' . $eid : '—';
    $receipt_no = 'REC-' . str_pad( (string) $payment_id, 6, '0', STR_PAD_LEFT );
    $recorded_by = absint( get_post_meta( $payment_id, '_kla_fee_payment_recorded_by', true ) );
    $recorded_name = $recorded_by ? get_the_author_meta( 'display_name', $recorded_by ) : 'Admin';

    nocache_headers();
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html( $receipt_no ); ?> - Kudos Learning Academy</title>
        <style>
            body{margin:0;background:#f5f1e8;font-family:Arial,sans-serif;color:#30205a}
            .receipt{max-width:760px;margin:45px auto;background:#fff;padding:42px;border-radius:16px;box-shadow:0 10px 35px rgba(48,32,90,.12)}
            .brand{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #2d7b3c;padding-bottom:22px}
            .brand h1{margin:0 0 5px;font-size:28px}.brand p{margin:0;color:#666}
            .paid{padding:8px 14px;border-radius:20px;background:#eef6dd;color:#2d7b3c;font-weight:700;height:max-content}
            .meta{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:25px 0}
            .box{border:1px solid #e4dfd4;border-radius:10px;padding:13px}.box span{display:block;font-size:11px;text-transform:uppercase;color:#777;margin-bottom:5px}.box strong{font-size:15px}
            table{width:100%;border-collapse:collapse;margin:20px 0}th,td{padding:14px 10px;border-bottom:1px solid #eee;text-align:left}th{font-size:12px;color:#777;text-transform:uppercase}td:last-child,th:last-child{text-align:right}
            .total{font-size:24px;font-weight:700;text-align:right;padding:18px 0;border-bottom:2px solid #30205a}
            .notes{margin-top:22px;background:#faf8f2;padding:15px;border-radius:10px}
            .actions{margin-top:25px;display:flex;gap:10px}.btn{border:0;background:#30205a;color:#fff;padding:11px 16px;border-radius:8px;text-decoration:none;cursor:pointer}.secondary{background:#2d7b3c}
            @media(max-width:600px){.receipt{margin:15px;padding:24px}.meta{grid-template-columns:1fr}.brand{display:block}.paid{display:inline-block;margin-top:15px}}
            @media print{body{background:#fff}.receipt{margin:0;box-shadow:none;max-width:none}.actions{display:none}}
        </style>
    </head>
    <body>
        <main class="receipt">
            <div class="brand">
                <div>
                    <h1>Kudos Learning Academy</h1>
                    <p>Fee Payment Receipt</p>
                </div>
                <div class="paid">PAYMENT RECEIVED</div>
            </div>

            <div class="meta">
                <div class="box"><span>Receipt No.</span><strong><?php echo esc_html( $receipt_no ); ?></strong></div>
                <div class="box"><span>Payment Date</span><strong><?php echo esc_html( $date ?: get_the_date( 'Y-m-d', $payment_id ) ); ?></strong></div>
                <div class="box"><span>Student</span><strong><?php echo esc_html( $student ); ?></strong></div>
                <div class="box"><span>Activity</span><strong><?php echo esc_html( $activity ); ?></strong></div>
                <div class="box"><span>Enrollment</span><strong><?php echo esc_html( $enrollment_no ); ?></strong></div>
                <div class="box"><span>Payment Mode</span><strong><?php echo esc_html( kla_fee_payment_mode_label( $mode ) ); ?></strong></div>
            </div>

            <table>
                <thead><tr><th>Description</th><th>Reference</th><th>Amount</th></tr></thead>
                <tbody><tr><td>Academy Activity Fee Payment</td><td><?php echo esc_html( $reference ?: '—' ); ?></td><td>₹<?php echo esc_html( number_format_i18n( $amount, 2 ) ); ?></td></tr></tbody>
            </table>

            <div class="total">Total Paid: ₹<?php echo esc_html( number_format_i18n( $amount, 2 ) ); ?></div>

            <?php if ( $notes ) : ?><div class="notes"><strong>Notes:</strong><br><?php echo nl2br( esc_html( $notes ) ); ?></div><?php endif; ?>

            <div class="meta">
                <div class="box"><span>Recorded By</span><strong><?php echo esc_html( $recorded_name ); ?></strong></div>
                <div class="box"><span>Receipt ID</span><strong><?php echo esc_html( $receipt_no ); ?></strong></div>
            </div>

            <div class="actions">
                <button class="btn" onclick="window.print()">🖨 Print Receipt</button>
                <button class="btn secondary" onclick="window.close()">Close</button>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}
add_action( 'template_redirect', 'kla_admin_print_fee_receipt', 1 );

add_shortcode( 'kla_admin_dashboard', 'kla_admin_dashboard_shortcode' );

/* -------------------------------------------------------------------------
 * Student name helper
 * ------------------------------------------------------------------------- */

function kla_admin_dashboard_student_name( $student_id ) {
    $name = get_post_meta( $student_id, '_kudos_student_name', true );

    return $name ? $name : get_the_title( $student_id );
}
