<?php
/**
 * Kudos Learning Academy - Fee & Redemption Management
 *
 * Admin-only fee collection view for Kudos redemptions.
 * Uses the existing kudos_redemption records created by the Parent portal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   ADMIN MENU
   ========================================================= */

if ( ! function_exists( 'kla_register_fee_redemptions_admin' ) ) {

    function kla_register_fee_redemptions_admin() {

        add_menu_page(
            'Fee & Redemptions',
            'Fee & Redemptions',
            'manage_options',
            'kla-fee-redemptions',
            'kla_fee_redemptions_admin_page',
            'dashicons-money-alt',
            30
        );
    }

    add_action( 'admin_menu', 'kla_register_fee_redemptions_admin' );
}


/* =========================================================
   PAYMENT META DEFAULTS
   ========================================================= */

if ( ! function_exists( 'kla_get_payment_status' ) ) {

    function kla_get_payment_status( $redemption_id ) {

        $status = get_post_meta(
            $redemption_id,
            '_kla_payment_status',
            true
        );

        if ( ! in_array( $status, array( 'unpaid', 'partial', 'paid' ), true ) ) {
            $status = 'unpaid';
        }

        return $status;
    }
}


if ( ! function_exists( 'kla_get_amount_paid' ) ) {

    function kla_get_amount_paid( $redemption_id ) {

        return max(
            0,
            (float) get_post_meta(
                $redemption_id,
                '_kla_amount_paid',
                true
            )
        );
    }
}


/* =========================================================
   SAVE PAYMENT
   ========================================================= */

if ( ! function_exists( 'kla_save_redemption_payment' ) ) {

    function kla_save_redemption_payment() {

        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['kla_save_payment_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['kla_save_payment_nonce'] ) ),
            'kla_save_redemption_payment'
        ) ) {
            wp_die( 'Security check failed.' );
        }

        $redemption_id = isset( $_POST['redemption_id'] )
            ? absint( $_POST['redemption_id'] )
            : 0;

        if ( ! $redemption_id || get_post_type( $redemption_id ) !== 'kudos_redemption' ) {
            wp_die( 'Invalid redemption record.' );
        }

        $payable_fee = (float) get_post_meta(
            $redemption_id,
            '_kla_payable_fee',
            true
        );

        $amount_paid = isset( $_POST['kla_amount_paid'] )
            ? (float) str_replace( ',', '', sanitize_text_field( wp_unslash( $_POST['kla_amount_paid'] ) ) )
            : 0;

        $amount_paid = max( 0, round( $amount_paid, 2 ) );

        if ( $amount_paid > $payable_fee ) {
            $amount_paid = $payable_fee;
        }

        if ( $amount_paid <= 0 ) {
            $payment_status = 'unpaid';
            $payment_method = '';
        } elseif ( $amount_paid < $payable_fee ) {
            $payment_status = 'partial';
            $payment_method = isset( $_POST['kla_payment_method'] )
                ? sanitize_key( wp_unslash( $_POST['kla_payment_method'] ) )
                : 'cash';
        } else {
            $payment_status = 'paid';
            $payment_method = isset( $_POST['kla_payment_method'] )
                ? sanitize_key( wp_unslash( $_POST['kla_payment_method'] ) )
                : 'cash';
        }

        $allowed_methods = array( 'cash', 'upi', 'card', 'bank_transfer', 'other' );

        if ( ! in_array( $payment_method, $allowed_methods, true ) ) {
            $payment_method = $amount_paid > 0 ? 'cash' : '';
        }

        $payment_date = isset( $_POST['kla_payment_date'] )
            ? sanitize_text_field( wp_unslash( $_POST['kla_payment_date'] ) )
            : '';

        if ( $amount_paid > 0 && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $payment_date ) ) {
            $payment_date = current_time( 'Y-m-d' );
        }

        $notes = isset( $_POST['kla_payment_notes'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['kla_payment_notes'] ) )
            : '';

        update_post_meta( $redemption_id, '_kla_payment_status', $payment_status );
        update_post_meta( $redemption_id, '_kla_amount_paid', $amount_paid );
        update_post_meta( $redemption_id, '_kla_payment_method', $payment_method );
        update_post_meta( $redemption_id, '_kla_payment_date', $amount_paid > 0 ? $payment_date : '' );
        update_post_meta( $redemption_id, '_kla_payment_received_by', get_current_user_id() );
        update_post_meta( $redemption_id, '_kla_payment_notes', $notes );

        $redirect_url = add_query_arg(
            array(
                'page'    => 'kla-fee-redemptions',
                'updated' => 1,
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    add_action( 'admin_post_kla_save_redemption_payment', 'kla_save_redemption_payment' );
}


/* =========================================================
   HELPERS
   ========================================================= */

if ( ! function_exists( 'kla_fee_student_name' ) ) {

    function kla_fee_student_name( $student_id ) {

        $name = get_post_meta( $student_id, '_kudos_student_name', true );

        return $name ?: get_the_title( $student_id );
    }
}


if ( ! function_exists( 'kla_fee_parent_name' ) ) {

    function kla_fee_parent_name( $parent_id ) {

        $name = get_post_meta( $parent_id, '_kudos_parent_name', true );

        return $name ?: get_the_title( $parent_id );
    }
}


if ( ! function_exists( 'kla_fee_activity_name' ) ) {

    function kla_fee_activity_name( $activity_id ) {
        return $activity_id ? get_the_title( $activity_id ) : '—';
    }
}


if ( ! function_exists( 'kla_fee_money' ) ) {

    function kla_fee_money( $amount ) {
        return '₹' . number_format( (float) $amount, 2 );
    }
}


/* =========================================================
   ADMIN PAGE
   ========================================================= */

if ( ! function_exists( 'kla_fee_redemptions_admin_page' ) ) {

    function kla_fee_redemptions_admin_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to access this page.' );
        }

        $editing_id = isset( $_GET['edit'] )
            ? absint( $_GET['edit'] )
            : 0;

        $search = isset( $_GET['s'] )
            ? sanitize_text_field( wp_unslash( $_GET['s'] ) )
            : '';

        $status_filter = isset( $_GET['payment_status'] )
            ? sanitize_key( wp_unslash( $_GET['payment_status'] ) )
            : '';

        if ( ! in_array( $status_filter, array( '', 'unpaid', 'partial', 'paid' ), true ) ) {
            $status_filter = '';
        }

        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page = 15;

        $meta_query = array();

        if ( $status_filter ) {
            $meta_query[] = array(
                'key'     => '_kla_payment_status',
                'value'   => $status_filter,
                'compare' => '=',
            );
        }

        $redemptions = new WP_Query(
            array(
                'post_type'      => 'kudos_redemption',
                'post_status'    => 'publish',
                'posts_per_page' => $per_page,
                'paged'          => $paged,
                'orderby'        => 'date',
                'order'          => 'DESC',
                's'              => $search,
                'meta_query'     => $meta_query,
            )
        );

        /* Summary totals across all redemption records. */
        $all_ids = get_posts(
            array(
                'post_type'      => 'kudos_redemption',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );

        $total_payable = 0;
        $total_paid    = 0;
        $total_pending = 0;
        $total_count   = count( $all_ids );

        foreach ( $all_ids as $id ) {
            $payable = (float) get_post_meta( $id, '_kla_payable_fee', true );
            $paid    = kla_get_amount_paid( $id );

            $total_payable += $payable;
            $total_paid    += min( $paid, $payable );
            $total_pending += max( 0, $payable - $paid );
        }

        ?>

        <div class="wrap kla-admin-fee-page">

            <style>
                .kla-admin-fee-page .kla-fee-header { margin: 20px 0; }
                .kla-admin-fee-page .kla-fee-header h1 { margin-bottom: 6px; }
                .kla-admin-fee-page .kla-fee-header p { color:#646970; font-size:14px; }
                .kla-admin-fee-page .kla-fee-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin:18px 0 22px; }
                .kla-admin-fee-page .kla-fee-summary-card { background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:18px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
                .kla-admin-fee-page .kla-fee-summary-card span { display:block; color:#646970; font-size:12px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:7px; }
                .kla-admin-fee-page .kla-fee-summary-card strong { font-size:24px; color:#30205a; }
                .kla-admin-fee-page .kla-fee-filters { background:#fff; border:1px solid #dcdcde; padding:12px; margin-bottom:14px; border-radius:8px; }
                .kla-admin-fee-page .kla-fee-table-wrap { background:#fff; border:1px solid #dcdcde; border-radius:8px; overflow:auto; }
                .kla-admin-fee-page .kla-fee-table { margin:0; border:0; }
                .kla-admin-fee-page .kla-fee-table th { white-space:nowrap; }
                .kla-admin-fee-page .kla-fee-table td { vertical-align:middle; }
                .kla-admin-fee-page .kla-status { display:inline-block; padding:4px 9px; border-radius:20px; font-size:12px; font-weight:600; }
                .kla-admin-fee-page .kla-status-unpaid { background:#fbeaea; color:#b42318; }
                .kla-admin-fee-page .kla-status-partial { background:#fff4d6; color:#8a5a00; }
                .kla-admin-fee-page .kla-status-paid { background:#e8f6ec; color:#1f7a38; }
                .kla-admin-fee-page .kla-payable { font-weight:700; color:#30205a; }
                .kla-admin-fee-page .kla-due { color:#b42318; font-weight:700; }
                .kla-admin-fee-page .kla-payment-form { max-width:760px; background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:22px; margin:20px 0; }
                .kla-admin-fee-page .kla-payment-form h2 { margin-top:0; color:#30205a; }
                .kla-admin-fee-page .kla-payment-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
                .kla-admin-fee-page .kla-payment-grid label { display:block; font-weight:600; margin-bottom:6px; }
                .kla-admin-fee-page .kla-payment-grid input,.kla-admin-fee-page .kla-payment-grid select,.kla-admin-fee-page .kla-payment-grid textarea { width:100%; }
                .kla-admin-fee-page .kla-payment-summary { background:#f6f8ef; border-radius:8px; padding:14px; margin:14px 0 18px; }
                .kla-admin-fee-page .kla-payment-summary strong { color:#30205a; }
                @media(max-width:900px){ .kla-admin-fee-page .kla-fee-summary{grid-template-columns:repeat(2,minmax(0,1fr));} }
                @media(max-width:600px){ .kla-admin-fee-page .kla-fee-summary{grid-template-columns:1fr;} .kla-admin-fee-page .kla-payment-grid{grid-template-columns:1fr;} }
            </style>

            <div class="kla-fee-header">
                <h1>Fee &amp; Redemptions</h1>
                <p>Track Kudos redemptions and record payments received from parents.</p>
            </div>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Payment details updated successfully.</strong></p>
                </div>
            <?php endif; ?>

            <?php if ( $editing_id ) : ?>

                <?php
                $student_id  = absint( get_post_meta( $editing_id, '_kla_student_id', true ) );
                $parent_id   = absint( get_post_meta( $editing_id, '_kla_parent_id', true ) );
                $activity_id = absint( get_post_meta( $editing_id, '_kla_activity_id', true ) );
                $activity_fee = (float) get_post_meta( $editing_id, '_kla_activity_fee', true );
                $points_used  = (int) get_post_meta( $editing_id, '_kla_points_used', true );
                $discount     = (float) get_post_meta( $editing_id, '_kla_discount', true );
                $payable      = (float) get_post_meta( $editing_id, '_kla_payable_fee', true );
                $paid         = kla_get_amount_paid( $editing_id );
                $due          = max( 0, $payable - $paid );
                $payment_status = kla_get_payment_status( $editing_id );
                $payment_method = get_post_meta( $editing_id, '_kla_payment_method', true );
                $payment_date   = get_post_meta( $editing_id, '_kla_payment_date', true );
                $payment_notes  = get_post_meta( $editing_id, '_kla_payment_notes', true );
                ?>

                <div class="kla-payment-form">
                    <h2>Update Payment</h2>

                    <div class="kla-payment-summary">
                        <strong><?php echo esc_html( kla_fee_student_name( $student_id ) ); ?></strong>
                        &nbsp;·&nbsp;
                        <?php echo esc_html( kla_fee_activity_name( $activity_id ) ); ?><br>
                        Parent: <?php echo esc_html( kla_fee_parent_name( $parent_id ) ); ?><br><br>
                        Original Fee: <strong><?php echo esc_html( kla_fee_money( $activity_fee ) ); ?></strong>
                        &nbsp; | &nbsp;
                        Kudos Used: <strong><?php echo esc_html( $points_used ); ?></strong>
                        &nbsp; | &nbsp;
                        Benefit: <strong><?php echo esc_html( kla_fee_money( $discount ) ); ?></strong>
                        &nbsp; | &nbsp;
                        Payable: <strong><?php echo esc_html( kla_fee_money( $payable ) ); ?></strong>
                    </div>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="kla_save_redemption_payment">
                        <input type="hidden" name="redemption_id" value="<?php echo esc_attr( $editing_id ); ?>">
                        <?php wp_nonce_field( 'kla_save_redemption_payment', 'kla_save_payment_nonce' ); ?>

                        <div class="kla-payment-grid">
                            <div>
                                <label for="kla_amount_paid">Amount Paid</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="<?php echo esc_attr( $payable ); ?>"
                                    id="kla_amount_paid"
                                    name="kla_amount_paid"
                                    value="<?php echo esc_attr( $paid ); ?>"
                                >
                                <p class="description">Maximum payable amount: <?php echo esc_html( kla_fee_money( $payable ) ); ?></p>
                            </div>

                            <div>
                                <label for="kla_payment_method">Payment Method</label>
                                <select id="kla_payment_method" name="kla_payment_method">
                                    <option value="cash" <?php selected( $payment_method, 'cash' ); ?>>Cash</option>
                                    <option value="upi" <?php selected( $payment_method, 'upi' ); ?>>UPI</option>
                                    <option value="card" <?php selected( $payment_method, 'card' ); ?>>Card</option>
                                    <option value="bank_transfer" <?php selected( $payment_method, 'bank_transfer' ); ?>>Bank Transfer</option>
                                    <option value="other" <?php selected( $payment_method, 'other' ); ?>>Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="kla_payment_date">Payment Date</label>
                                <input type="date" id="kla_payment_date" name="kla_payment_date" value="<?php echo esc_attr( $payment_date ); ?>">
                            </div>

                            <div>
                                <label>Current Status</label>
                                <p style="margin-top:8px;"><span class="kla-status kla-status-<?php echo esc_attr( $payment_status ); ?>"><?php echo esc_html( ucfirst( $payment_status ) ); ?></span></p>
                                <p class="description">Status is calculated automatically from Amount Paid.</p>
                            </div>

                            <div style="grid-column:1/-1;">
                                <label for="kla_payment_notes">Payment Notes</label>
                                <textarea id="kla_payment_notes" name="kla_payment_notes" rows="4" placeholder="Example: Cash received at academy reception."><?php echo esc_textarea( $payment_notes ); ?></textarea>
                            </div>
                        </div>

                        <p style="margin-top:18px;">
                            <button type="submit" class="button button-primary button-large">Save Payment</button>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kla-fee-redemptions' ) ); ?>" class="button button-large">Cancel</a>
                        </p>

                        <?php if ( $paid > 0 ) : ?>
                            <p><strong>Remaining Due:</strong> <span class="kla-due"><?php echo esc_html( kla_fee_money( $due ) ); ?></span></p>
                        <?php endif; ?>
                    </form>
                </div>

            <?php endif; ?>

            <div class="kla-fee-summary">
                <div class="kla-fee-summary-card">
                    <span>Total Redemptions</span>
                    <strong><?php echo esc_html( $total_count ); ?></strong>
                </div>
                <div class="kla-fee-summary-card">
                    <span>Total Payable</span>
                    <strong><?php echo esc_html( kla_fee_money( $total_payable ) ); ?></strong>
                </div>
                <div class="kla-fee-summary-card">
                    <span>Total Collected</span>
                    <strong><?php echo esc_html( kla_fee_money( $total_paid ) ); ?></strong>
                </div>
                <div class="kla-fee-summary-card">
                    <span>Pending</span>
                    <strong><?php echo esc_html( kla_fee_money( $total_pending ) ); ?></strong>
                </div>
            </div>

            <div class="kla-fee-filters">
                <form method="get">
                    <input type="hidden" name="page" value="kla-fee-redemptions">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search student, activity or parent..." style="min-width:280px;">
                    <select name="payment_status">
                        <option value="">All Payment Status</option>
                        <option value="unpaid" <?php selected( $status_filter, 'unpaid' ); ?>>Unpaid</option>
                        <option value="partial" <?php selected( $status_filter, 'partial' ); ?>>Partial</option>
                        <option value="paid" <?php selected( $status_filter, 'paid' ); ?>>Paid</option>
                    </select>
                    <button class="button">Filter</button>
                    <?php if ( $search || $status_filter ) : ?>
                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=kla-fee-redemptions' ) ); ?>">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="kla-fee-table-wrap">
                <table class="widefat striped kla-fee-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Parent</th>
                            <th>Activity</th>
                            <th>Original Fee</th>
                            <th>Kudos</th>
                            <th>Benefit</th>
                            <th>Payable</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ( $redemptions->have_posts() ) : ?>

                            <?php while ( $redemptions->have_posts() ) : $redemptions->the_post(); ?>

                                <?php
                                $redemption_id = get_the_ID();
                                $student_id    = absint( get_post_meta( $redemption_id, '_kla_student_id', true ) );
                                $parent_id     = absint( get_post_meta( $redemption_id, '_kla_parent_id', true ) );
                                $activity_id   = absint( get_post_meta( $redemption_id, '_kla_activity_id', true ) );
                                $activity_fee  = (float) get_post_meta( $redemption_id, '_kla_activity_fee', true );
                                $points_used   = (int) get_post_meta( $redemption_id, '_kla_points_used', true );
                                $discount      = (float) get_post_meta( $redemption_id, '_kla_discount', true );
                                $payable       = (float) get_post_meta( $redemption_id, '_kla_payable_fee', true );
                                $paid          = kla_get_amount_paid( $redemption_id );
                                $due           = max( 0, $payable - $paid );
                                $status        = kla_get_payment_status( $redemption_id );
                                $method        = get_post_meta( $redemption_id, '_kla_payment_method', true );
                                $date          = get_post_meta( $redemption_id, '_kla_payment_date', true );
                                ?>

                                <tr>
                                    <td><strong><?php echo esc_html( kla_fee_student_name( $student_id ) ); ?></strong></td>
                                    <td><?php echo esc_html( kla_fee_parent_name( $parent_id ) ); ?></td>
                                    <td><?php echo esc_html( kla_fee_activity_name( $activity_id ) ); ?></td>
                                    <td><?php echo esc_html( kla_fee_money( $activity_fee ) ); ?></td>
                                    <td><?php echo esc_html( $points_used ); ?></td>
                                    <td><?php echo esc_html( kla_fee_money( $discount ) ); ?></td>
                                    <td class="kla-payable"><?php echo esc_html( kla_fee_money( $payable ) ); ?></td>
                                    <td><?php echo esc_html( kla_fee_money( $paid ) ); ?></td>
                                    <td class="kla-due"><?php echo esc_html( kla_fee_money( $due ) ); ?></td>
                                    <td>
                                        <span class="kla-status kla-status-<?php echo esc_attr( $status ); ?>">
                                            <?php echo esc_html( ucfirst( $status ) ); ?>
                                        </span>
                                        <?php if ( $method && $paid > 0 ) : ?>
                                            <br><small><?php echo esc_html( ucwords( str_replace( '_', ' ', $method ) ) ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $date ?: '—' ); ?></td>
                                    <td>
                                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'kla-fee-redemptions', 'edit' => $redemption_id ), admin_url( 'admin.php' ) ) ); ?>">Update Payment</a>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else : ?>
                            <tr>
                                <td colspan="12">No redemption records found.</td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <?php
            $pagination = paginate_links(
                array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => max( 1, (int) $redemptions->max_num_pages ),
                    'type'      => 'list',
                    'add_args'  => array_filter(
                        array(
                            'page'           => 'kla-fee-redemptions',
                            's'              => $search,
                            'payment_status' => $status_filter,
                        )
                    ),
                )
            );

            if ( $pagination ) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>';
            }
            ?>

        </div>

        <?php

        wp_reset_postdata();
    }
}
