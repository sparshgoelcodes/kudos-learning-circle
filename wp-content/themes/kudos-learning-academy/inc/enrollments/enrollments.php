<?php
/**
 * Kudos Learning Academy - Activity Enrollment Management
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   ENROLLMENT POST TYPE
   ========================================================= */

if ( ! function_exists( 'kla_register_enrollments' ) ) {
    function kla_register_enrollments() {
        register_post_type(
            'kudos_enrollment',
            array(
                'labels' => array(
                    'name'               => 'Activity Enrollments',
                    'singular_name'      => 'Activity Enrollment',
                    'menu_name'          => 'Enrollments',
                    'add_new'            => 'Add Enrollment',
                    'add_new_item'       => 'Add Activity Enrollment',
                    'edit_item'          => 'Edit Activity Enrollment',
                    'new_item'           => 'New Activity Enrollment',
                    'view_item'          => 'View Activity Enrollment',
                    'search_items'       => 'Search Enrollments',
                    'not_found'          => 'No enrollments found',
                    'not_found_in_trash' => 'No enrollments found in Trash',
                ),
                'public'            => false,
                'show_ui'           => true,
                'show_in_menu'      => true,
                'show_in_admin_bar' => true,
                'menu_position'     => 31,
                'menu_icon'         => 'dashicons-welcome-learn-more',
                'capability_type'   => 'post',
                'map_meta_cap'      => true,
                'supports'          => array( 'title' ),
                'show_in_rest'      => true,
            )
        );
    }
    add_action( 'init', 'kla_register_enrollments' );
}

/* =========================================================
   ENROLLMENT META BOX
   ========================================================= */

if ( ! function_exists( 'kla_enrollment_meta_box' ) ) {
    function kla_enrollment_meta_box() {
        add_meta_box(
            'kla_enrollment_details',
            'Enrollment Details',
            'kla_enrollment_details_callback',
            'kudos_enrollment',
            'normal',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'kla_enrollment_meta_box' );
}

if ( ! function_exists( 'kla_enrollment_financial_meta_box' ) ) {
    function kla_enrollment_financial_meta_box() {
        add_meta_box(
            'kla_enrollment_financial_summary',
            'Fee & Payment Summary',
            'kla_enrollment_financial_summary_callback',
            'kudos_enrollment',
            'side',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'kla_enrollment_financial_meta_box' );
}

if ( ! function_exists( 'kla_get_enrollment_financials' ) ) {
    function kla_get_enrollment_financials( $enrollment_id ) {
        $student_id  = absint( get_post_meta( $enrollment_id, '_kla_enrollment_student_id', true ) );
        $activity_id = absint( get_post_meta( $enrollment_id, '_kla_enrollment_activity_id', true ) );
        $fee         = (float) get_post_meta( $enrollment_id, '_kla_enrollment_fee', true );

        $result = array(
            'fee'     => max( 0, $fee ),
            'kudos'   => 0,
            'benefit' => 0,
            'payable' => max( 0, $fee ),
            'paid'    => 0,
            'due'     => max( 0, $fee ),
            'status'  => 'unpaid',
        );

        if ( ! $student_id || ! $activity_id ) {
            return $result;
        }

        $redemptions = get_posts(
            array(
                'post_type'      => 'kudos_redemption',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( 'key' => '_kla_student_id', 'value' => $student_id, 'compare' => '=' ),
                    array( 'key' => '_kla_activity_id', 'value' => $activity_id, 'compare' => '=' ),
                ),
            )
        );

        foreach ( $redemptions as $redemption_id ) {
            $points = absint( get_post_meta( $redemption_id, '_kla_points_used', true ) );
            $result['kudos'] += $points;
            $result['paid']  += max( 0, (float) get_post_meta( $redemption_id, '_kla_amount_paid', true ) );
        }

        $result['benefit'] = $result['kudos'] * ( function_exists( 'kla_kudos_value' ) ? (float) kla_kudos_value() : 1 );
        $result['payable'] = max( 0, $result['fee'] - $result['benefit'] );
        $result['paid']    = min( $result['payable'], round( $result['paid'], 2 ) );
        $result['due']     = max( 0, round( $result['payable'] - $result['paid'], 2 ) );

        if ( $result['due'] <= 0 && $result['payable'] > 0 ) {
            $result['status'] = 'paid';
        } elseif ( $result['paid'] > 0 ) {
            $result['status'] = 'partial';
        }

        return $result;
    }
}

if ( ! function_exists( 'kla_enrollment_financial_summary_callback' ) ) {
    function kla_enrollment_financial_summary_callback( $post ) {
        $f = kla_get_enrollment_financials( $post->ID );
        $labels = array( 'unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid' );
        $status = isset( $labels[ $f['status'] ] ) ? $labels[ $f['status'] ] : 'Unpaid';
        ?>
        <div class="kla-enrollment-financial-summary">
            <p><strong>Activity Fee</strong><br>₹<?php echo esc_html( number_format( $f['fee'], 2 ) ); ?></p>
            <p><strong>Kudos Used</strong><br><?php echo esc_html( $f['kudos'] ); ?></p>
            <p><strong>Kudos Benefit</strong><br>₹<?php echo esc_html( number_format( $f['benefit'], 2 ) ); ?></p>
            <p><strong>Payable</strong><br><span class="kla-enrollment-payable">₹<?php echo esc_html( number_format( $f['payable'], 2 ) ); ?></span></p>
            <p><strong>Paid</strong><br>₹<?php echo esc_html( number_format( $f['paid'], 2 ) ); ?></p>
            <p><strong>Due</strong><br><span class="kla-enrollment-due">₹<?php echo esc_html( number_format( $f['due'], 2 ) ); ?></span></p>
            <p><strong>Payment Status</strong><br><span class="kla-enrollment-status-<?php echo esc_attr( $f['status'] ); ?>"><?php echo esc_html( $status ); ?></span></p>
            <p class="description">Payment is recorded from the related Kudos redemption records.</p>
        </div>
        <?php
    }
}

if ( ! function_exists( 'kla_enrollment_details_callback' ) ) {
    function kla_enrollment_details_callback( $post ) {
        wp_nonce_field( 'kla_save_enrollment_details', 'kla_enrollment_nonce' );

        $student_id = absint( get_post_meta( $post->ID, '_kla_enrollment_student_id', true ) );
        $activity_id = absint( get_post_meta( $post->ID, '_kla_enrollment_activity_id', true ) );
        $start_date = get_post_meta( $post->ID, '_kla_enrollment_start_date', true );
        $end_date = get_post_meta( $post->ID, '_kla_enrollment_end_date', true );
        $status = get_post_meta( $post->ID, '_kla_enrollment_status', true );
        $fee = get_post_meta( $post->ID, '_kla_enrollment_fee', true );
        $notes = get_post_meta( $post->ID, '_kla_enrollment_notes', true );

        if ( ! $status ) {
            $status = 'active';
        }

        $students = get_posts(
            array(
                'post_type'      => 'kudos_student',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $activities = get_posts(
            array(
                'post_type'      => 'kudos_activity',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        ?>

        <p>
            <label><strong>Student</strong></label>
        </p>
        <select name="kla_enrollment_student_id" style="width:100%;max-width:600px;">
            <option value="">Select Student</option>
            <?php foreach ( $students as $student ) : ?>
                <?php
                $student_name = get_post_meta( $student->ID, '_kudos_student_name', true );
                if ( ! $student_name ) {
                    $student_name = $student->post_title;
                }
                ?>
                <option value="<?php echo esc_attr( $student->ID ); ?>" <?php selected( $student_id, $student->ID ); ?>>
                    <?php echo esc_html( $student_name ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p style="margin-top:20px;">
            <label><strong>Activity</strong></label>
        </p>
        <select name="kla_enrollment_activity_id" style="width:100%;max-width:600px;">
            <option value="">Select Activity</option>
            <?php foreach ( $activities as $activity ) : ?>
                <option value="<?php echo esc_attr( $activity->ID ); ?>" <?php selected( $activity_id, $activity->ID ); ?>>
                    <?php echo esc_html( $activity->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p style="margin-top:20px;">
            <label><strong>Start Date</strong></label>
        </p>
        <input type="date" name="kla_enrollment_start_date" value="<?php echo esc_attr( $start_date ); ?>" style="width:100%;max-width:300px;" />

        <p style="margin-top:20px;">
            <label><strong>End Date (optional)</strong></label>
        </p>
        <input type="date" name="kla_enrollment_end_date" value="<?php echo esc_attr( $end_date ); ?>" style="width:100%;max-width:300px;" />

        <p style="margin-top:20px;">
            <label><strong>Status</strong></label>
        </p>
        <select name="kla_enrollment_status" style="width:100%;max-width:300px;">
            <option value="active" <?php selected( $status, 'active' ); ?>>Active</option>
            <option value="completed" <?php selected( $status, 'completed' ); ?>>Completed</option>
            <option value="inactive" <?php selected( $status, 'inactive' ); ?>>Inactive</option>
        </select>

        <p style="margin-top:20px;">
            <label><strong>Enrollment Fee (₹)</strong></label>
        </p>
        <input type="number" name="kla_enrollment_fee" value="<?php echo esc_attr( $fee ); ?>" min="0" step="1" style="width:100%;max-width:300px;" placeholder="Leave blank to use activity fee" />
        <p style="color:#666;">If left blank, the current activity fee will be saved automatically as the enrollment fee.</p>

        <p style="margin-top:20px;">
            <label><strong>Notes</strong></label>
        </p>
        <textarea name="kla_enrollment_notes" rows="4" style="width:100%;max-width:600px;" placeholder="Optional enrollment notes"><?php echo esc_textarea( $notes ); ?></textarea>

        <?php
    }
}

/* =========================================================
   SAVE ENROLLMENT
   ========================================================= */

if ( ! function_exists( 'kla_save_enrollment_details' ) ) {
    function kla_save_enrollment_details( $post_id ) {
        if ( ! isset( $_POST['kla_enrollment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kla_enrollment_nonce'] ) ), 'kla_save_enrollment_details' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'kudos_enrollment' || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $student_id = isset( $_POST['kla_enrollment_student_id'] ) ? absint( $_POST['kla_enrollment_student_id'] ) : 0;
        $activity_id = isset( $_POST['kla_enrollment_activity_id'] ) ? absint( $_POST['kla_enrollment_activity_id'] ) : 0;
        $start_date = isset( $_POST['kla_enrollment_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kla_enrollment_start_date'] ) ) : '';
        $end_date = isset( $_POST['kla_enrollment_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kla_enrollment_end_date'] ) ) : '';
        $status = isset( $_POST['kla_enrollment_status'] ) ? sanitize_key( wp_unslash( $_POST['kla_enrollment_status'] ) ) : 'active';
        $notes = isset( $_POST['kla_enrollment_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kla_enrollment_notes'] ) ) : '';
        $fee = isset( $_POST['kla_enrollment_fee'] ) ? (float) str_replace( ',', '', sanitize_text_field( wp_unslash( $_POST['kla_enrollment_fee'] ) ) ) : 0;

        if ( ! in_array( $status, array( 'active', 'completed', 'inactive' ), true ) ) {
            $status = 'active';
        }

        if ( ! $fee && $activity_id ) {
            $fee = (float) get_post_meta( $activity_id, '_kudos_activity_fee', true );
        }

        update_post_meta( $post_id, '_kla_enrollment_student_id', $student_id );
        update_post_meta( $post_id, '_kla_enrollment_activity_id', $activity_id );
        update_post_meta( $post_id, '_kla_enrollment_start_date', $start_date );
        update_post_meta( $post_id, '_kla_enrollment_end_date', $end_date );
        update_post_meta( $post_id, '_kla_enrollment_status', $status );
        update_post_meta( $post_id, '_kla_enrollment_fee', max( 0, $fee ) );
        update_post_meta( $post_id, '_kla_enrollment_notes', $notes );

        $student_name = $student_id ? get_post_meta( $student_id, '_kudos_student_name', true ) : '';
        $activity_name = $activity_id ? get_the_title( $activity_id ) : '';
        if ( ! $student_name && $student_id ) {
            $student_name = get_the_title( $student_id );
        }

        $new_title = trim( $student_name . ' - ' . $activity_name );
        if ( $new_title ) {
            remove_action( 'save_post_kudos_enrollment', 'kla_save_enrollment_details' );
            wp_update_post(
                array(
                    'ID'         => $post_id,
                    'post_title' => $new_title,
                )
            );
            add_action( 'save_post_kudos_enrollment', 'kla_save_enrollment_details' );
        }
    }
    add_action( 'save_post_kudos_enrollment', 'kla_save_enrollment_details' );
}

/* =========================================================
   ADMIN COLUMNS
   ========================================================= */

if ( ! function_exists( 'kla_enrollment_columns' ) ) {
    function kla_enrollment_columns( $columns ) {
        return array(
            'cb'       => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
            'title'    => 'Enrollment',
            'student'  => 'Student',
            'activity' => 'Activity',
            'fee'      => 'Fee',
            'kudos'    => 'Kudos',
            'benefit'  => 'Benefit',
            'payable'  => 'Payable',
            'paid'     => 'Paid',
            'due'      => 'Due',
            'payment'  => 'Payment',
            'start'    => 'Start Date',
            'status'   => 'Enrollment Status',
            'date'     => 'Created',
        );
    }
    add_filter( 'manage_kudos_enrollment_posts_columns', 'kla_enrollment_columns' );
}

if ( ! function_exists( 'kla_enrollment_column_content' ) ) {
    function kla_enrollment_column_content( $column, $post_id ) {
        $student_id = absint( get_post_meta( $post_id, '_kla_enrollment_student_id', true ) );
        $activity_id = absint( get_post_meta( $post_id, '_kla_enrollment_activity_id', true ) );
        $start = get_post_meta( $post_id, '_kla_enrollment_start_date', true );
        $status = get_post_meta( $post_id, '_kla_enrollment_status', true );
        $financials = kla_get_enrollment_financials( $post_id );

        if ( 'student' === $column ) {
            $name = get_post_meta( $student_id, '_kudos_student_name', true );
            echo esc_html( $name ?: get_the_title( $student_id ) );
        } elseif ( 'activity' === $column ) {
            echo esc_html( get_the_title( $activity_id ) );
        } elseif ( 'fee' === $column ) {
            echo '₹' . esc_html( number_format( $financials['fee'] ) );
        } elseif ( 'kudos' === $column ) {
            echo esc_html( $financials['kudos'] );
        } elseif ( 'benefit' === $column ) {
            echo '₹' . esc_html( number_format( $financials['benefit'] ) );
        } elseif ( 'payable' === $column ) {
            echo '<strong>₹' . esc_html( number_format( $financials['payable'] ) ) . '</strong>';
        } elseif ( 'paid' === $column ) {
            echo '₹' . esc_html( number_format( $financials['paid'] ) );
        } elseif ( 'due' === $column ) {
            echo '<strong class="kla-enrollment-due-text">₹' . esc_html( number_format( $financials['due'] ) ) . '</strong>';
        } elseif ( 'payment' === $column ) {
            $label = ucfirst( $financials['status'] );
            echo '<span class="kla-enrollment-payment-status kla-enrollment-payment-' . esc_attr( $financials['status'] ) . '">' . esc_html( $label ) . '</span>';
        } elseif ( 'start' === $column ) {
            echo esc_html( $start ?: '—' );
        } elseif ( 'status' === $column ) {
            $label = ucfirst( $status ?: 'active' );
            $class = 'active' === $status ? 'kla-enrollment-status-active' : 'kla-enrollment-status-other';
            echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
        }
    }
    add_action( 'manage_kudos_enrollment_posts_custom_column', 'kla_enrollment_column_content', 10, 2 );
}

if ( ! function_exists( 'kla_enrollment_admin_css' ) ) {
    function kla_enrollment_admin_css( $hook ) {
        if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'kudos_enrollment' !== $_GET['post_type'] ) {
            return;
        }
        echo '<style>
            .kla-enrollment-status-active,.kla-enrollment-status-other{display:inline-block;padding:4px 9px;border-radius:20px;font-weight:700;font-size:11px}
            .kla-enrollment-status-active{background:#e8f6ea;color:#217a36}
            .kla-enrollment-status-other{background:#f2f2f2;color:#666}
            .kla-enrollment-payment-status{display:inline-block;padding:4px 9px;border-radius:20px;font-weight:700;font-size:11px}
            .kla-enrollment-payment-unpaid{background:#fbeaea;color:#b42318}
            .kla-enrollment-payment-partial{background:#fff4d6;color:#8a5a00}
            .kla-enrollment-payment-paid{background:#e8f6ec;color:#217a36}
            .kla-enrollment-due-text{color:#b42318}
            .kla-enrollment-financial-summary p{margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid #eee}
            .kla-enrollment-financial-summary p:last-child{border-bottom:0}
            .kla-enrollment-financial-summary .description{color:#666;font-size:12px}
            .kla-enrollment-payable{font-size:18px;color:#30205a}
            .kla-enrollment-due{font-size:18px;color:#b42318}
            .kla-enrollment-status-unpaid,.kla-enrollment-status-partial,.kla-enrollment-status-paid{display:inline-block;padding:4px 9px;border-radius:20px;font-weight:700;font-size:11px}
            .kla-enrollment-status-unpaid{background:#fbeaea;color:#b42318}
            .kla-enrollment-status-partial{background:#fff4d6;color:#8a5a00}
            .kla-enrollment-status-paid{background:#e8f6ec;color:#217a36}
        </style>';
    }
    add_action( 'admin_head', 'kla_enrollment_admin_css' );
}

/* =========================================================
   HELPER
   ========================================================= */

if ( ! function_exists( 'kla_get_active_enrollments_for_parent' ) ) {
    function kla_get_active_enrollments_for_parent( $parent_id ) {
        $children = get_posts(
            array(
                'post_type'      => 'kudos_student',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_student_parent',
                        'value'   => absint( $parent_id ),
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $children ) ) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => 'kudos_enrollment',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_kla_enrollment_student_id',
                        'value'   => $children,
                        'compare' => 'IN',
                    ),
                    array(
                        'key'     => '_kla_enrollment_status',
                        'value'   => 'active',
                        'compare' => '=',
                    ),
                ),
            )
        );
    }
}
