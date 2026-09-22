<?php
/**
 * Kudos Learning Academy - Rewards / Kudos Wallet / Ledger
 *
 * Handles:
 * - Student Kudos balance
 * - Kudos transaction ledger
 * - Teacher award integration via kla_award_kudos()
 * - Admin transaction list
 * - Student Kudos balance field
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   REWARDS / TRANSACTION CPT
   ========================================================= */

if ( ! function_exists( 'kla_register_kudos_transactions' ) ) {

    function kla_register_kudos_transactions() {

        register_post_type(
            'kudos_transaction',
            array(
                'labels' => array(
                    'name'          => 'Kudos Transactions',
                    'singular_name' => 'Kudos Transaction',
                    'menu_name'     => 'Kudos Ledger',
                    'add_new'       => 'Add Transaction',
                    'add_new_item'  => 'Add Kudos Transaction',
                    'edit_item'     => 'View Kudos Transaction',
                    'new_item'      => 'New Kudos Transaction',
                    'view_item'     => 'View Kudos Transaction',
                    'search_items'  => 'Search Transactions',
                    'not_found'     => 'No transactions found',
                ),
                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_admin_bar'  => false,
                'menu_position'      => 29,
                'menu_icon'          => 'dashicons-money-alt',
                'capability_type'    => 'post',
                'map_meta_cap'       => true,
                'supports'            => array( 'title' ),
                'show_in_rest'       => false,
            )
        );
    }

    add_action( 'init', 'kla_register_kudos_transactions' );
}


/* =========================================================
   STUDENT KUDOS BALANCE
   ========================================================= */

if ( ! function_exists( 'kla_get_student_kudos_balance' ) ) {

    function kla_get_student_kudos_balance( $student_id ) {

        $student_id = absint( $student_id );

        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            return 0;
        }

        return max(
            0,
            (int) get_post_meta(
                $student_id,
                '_kudos_student_rewards',
                true
            )
        );
    }
}


if ( ! function_exists( 'kla_set_student_kudos_balance' ) ) {

    function kla_set_student_kudos_balance( $student_id, $balance ) {

        $student_id = absint( $student_id );
        $balance    = max( 0, (int) $balance );

        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            return false;
        }

        update_post_meta(
            $student_id,
            '_kudos_student_rewards',
            $balance
        );

        return $balance;
    }
}


/* =========================================================
   STUDENT KUDOS BALANCE ADMIN FIELD
   ========================================================= */

if ( ! function_exists( 'kla_student_kudos_balance_meta_box' ) ) {

    function kla_student_kudos_balance_meta_box() {

        add_meta_box(
            'kla_student_kudos_balance',
            'Kudos Wallet',
            'kla_student_kudos_balance_callback',
            'kudos_student',
            'side',
            'high'
        );
    }

    add_action( 'add_meta_boxes', 'kla_student_kudos_balance_meta_box' );
}


if ( ! function_exists( 'kla_student_kudos_balance_callback' ) ) {

    function kla_student_kudos_balance_callback( $post ) {

        $balance = kla_get_student_kudos_balance( $post->ID );

        wp_nonce_field(
            'kla_save_student_kudos_balance',
            'kla_student_kudos_balance_nonce'
        );
        ?>

        <p style="margin:0 0 8px;">
            <strong>Current Balance</strong>
        </p>

        <p style="font-size:24px;margin:0 0 12px;color:#2d7b3c;">
            <?php echo esc_html( number_format_i18n( $balance ) ); ?> Kudos
        </p>

        <label for="kla_manual_kudos_balance">
            <strong>Manual Balance</strong>
        </label>

        <input
            type="number"
            min="0"
            step="1"
            id="kla_manual_kudos_balance"
            name="kla_manual_kudos_balance"
            value="<?php echo esc_attr( $balance ); ?>"
            style="width:100%;margin-top:6px;"
        />

        <p class="description">
            Use this only for an administrative correction. Normal awards and redemptions should go through the ledger.
        </p>

        <?php
    }
}


if ( ! function_exists( 'kla_save_student_kudos_balance' ) ) {

    function kla_save_student_kudos_balance( $post_id ) {

        if ( ! isset( $_POST['kla_student_kudos_balance_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash( $_POST['kla_student_kudos_balance_nonce'] )
            ),
            'kla_save_student_kudos_balance'
        ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( get_post_type( $post_id ) !== 'kudos_student' ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST['kla_manual_kudos_balance'] ) ) {
            return;
        }

        $balance = absint(
            $_POST['kla_manual_kudos_balance']
        );

        update_post_meta(
            $post_id,
            '_kudos_student_rewards',
            $balance
        );
    }

    add_action(
        'save_post_kudos_student',
        'kla_save_student_kudos_balance'
    );
}


/* =========================================================
   TRANSACTION HELPERS
   ========================================================= */

if ( ! function_exists( 'kla_create_kudos_transaction' ) ) {

    function kla_create_kudos_transaction( $args = array() ) {

        $defaults = array(
            'student_id'  => 0,
            'points'      => 0,
            'type'        => 'award',
            'reason'      => '',
            'teacher_id'  => 0,
            'parent_id'   => 0,
            'activity_id' => 0,
            'reference_id'=> 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $student_id   = absint( $args['student_id'] );
        $points       = (int) $args['points'];
        $type         = sanitize_key( $args['type'] );
        $reason       = sanitize_text_field( $args['reason'] );
        $teacher_id   = absint( $args['teacher_id'] );
        $parent_id    = absint( $args['parent_id'] );
        $activity_id  = absint( $args['activity_id'] );
        $reference_id = absint( $args['reference_id'] );

        $allowed_types = array( 'award', 'redemption', 'adjustment' );

        if ( ! in_array( $type, $allowed_types, true ) ) {
            return new WP_Error(
                'invalid_transaction_type',
                'Invalid Kudos transaction type.'
            );
        }

        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            return new WP_Error(
                'invalid_student',
                'Invalid student.'
            );
        }

        if ( $points === 0 ) {
            return new WP_Error(
                'invalid_points',
                'Transaction points cannot be zero.'
            );
        }

        if ( $type === 'award' && $points < 1 ) {
            return new WP_Error(
                'invalid_award_points',
                'Award points must be greater than zero.'
            );
        }

        if ( $type === 'redemption' && $points > 0 ) {
            $points = -$points;
        }

        if ( $type === 'adjustment' && ! $points ) {
            return new WP_Error(
                'invalid_adjustment',
                'Adjustment cannot be zero.'
            );
        }

        $old_balance = kla_get_student_kudos_balance( $student_id );
        $new_balance = $old_balance + $points;

        if ( $new_balance < 0 ) {
            return new WP_Error(
                'insufficient_kudos',
                'Student does not have enough Kudos.'
            );
        }

        $student_name = get_post_meta(
            $student_id,
            '_kudos_student_name',
            true
        );

        if ( ! $student_name ) {
            $student_name = get_the_title( $student_id );
        }

        $type_label = ucfirst( $type );

        $title = sprintf(
            '%s - %s %s Kudos',
            $student_name,
            $type_label,
            abs( $points )
        );

        $transaction_id = wp_insert_post(
            array(
                'post_type'   => 'kudos_transaction',
                'post_status' => 'publish',
                'post_title'  => $title,
            ),
            true
        );

        if ( is_wp_error( $transaction_id ) ) {
            return $transaction_id;
        }

        update_post_meta( $transaction_id, '_kla_transaction_student_id', $student_id );
        update_post_meta( $transaction_id, '_kla_transaction_points', $points );
        update_post_meta( $transaction_id, '_kla_transaction_type', $type );
        update_post_meta( $transaction_id, '_kla_transaction_reason', $reason );
        update_post_meta( $transaction_id, '_kla_transaction_teacher_id', $teacher_id );
        update_post_meta( $transaction_id, '_kla_transaction_parent_id', $parent_id );
        update_post_meta( $transaction_id, '_kla_transaction_activity_id', $activity_id );
        update_post_meta( $transaction_id, '_kla_transaction_reference_id', $reference_id );
        update_post_meta( $transaction_id, '_kla_transaction_old_balance', $old_balance );
        update_post_meta( $transaction_id, '_kla_transaction_new_balance', $new_balance );

        update_post_meta(
            $student_id,
            '_kudos_student_rewards',
            $new_balance
        );

        return $transaction_id;
    }
}


/* =========================================================
   TEACHER AWARD INTEGRATION
   Existing teachers.php calls this function.
   ========================================================= */

if ( ! function_exists( 'kla_award_kudos' ) ) {

    function kla_award_kudos(
        $student_id,
        $points,
        $reason = '',
        $teacher_id = 0,
        $activity_id = 0
    ) {

        $student_id  = absint( $student_id );
        $points      = absint( $points );
        $teacher_id  = absint( $teacher_id );
        $activity_id = absint( $activity_id );
        $reason      = sanitize_text_field( $reason );

        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            return new WP_Error(
                'invalid_student',
                'Invalid student selected.'
            );
        }

        if ( $points < 1 ) {
            return new WP_Error(
                'invalid_points',
                'Kudos points must be greater than zero.'
            );
        }

        if ( strlen( $reason ) < 2 ) {
            return new WP_Error(
                'missing_reason',
                'Please provide a reason for awarding Kudos.'
            );
        }

        if ( $teacher_id ) {
            $teacher = get_user_by( 'id', $teacher_id );

            if ( ! $teacher || ! in_array( 'kudos_teacher', (array) $teacher->roles, true ) ) {
                return new WP_Error(
                    'invalid_teacher',
                    'Invalid teacher account.'
                );
            }
        }

        if ( $activity_id && get_post_type( $activity_id ) !== 'kudos_activity' ) {
            return new WP_Error(
                'invalid_activity',
                'Invalid activity selected.'
            );
        }

        return kla_create_kudos_transaction(
            array(
                'student_id'  => $student_id,
                'points'      => $points,
                'type'        => 'award',
                'reason'      => $reason,
                'teacher_id'  => $teacher_id,
                'activity_id' => $activity_id,
            )
        );
    }
}


/* =========================================================
   TRANSACTION ADMIN COLUMNS
   ========================================================= */

if ( ! function_exists( 'kla_kudos_transaction_columns' ) ) {

    function kla_kudos_transaction_columns( $columns ) {

        return array(
            'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
            'title'     => 'Transaction',
            'student'   => 'Student',
            'type'      => 'Type',
            'points'    => 'Kudos',
            'reason'    => 'Reason',
            'teacher'   => 'Teacher',
            'balance'   => 'Balance After',
            'date'      => 'Date',
        );
    }

    add_filter(
        'manage_kudos_transaction_posts_columns',
        'kla_kudos_transaction_columns'
    );
}


if ( ! function_exists( 'kla_kudos_transaction_column_content' ) ) {

    function kla_kudos_transaction_column_content( $column, $post_id ) {

        $student_id = absint( get_post_meta( $post_id, '_kla_transaction_student_id', true ) );
        $type       = get_post_meta( $post_id, '_kla_transaction_type', true );
        $points     = (int) get_post_meta( $post_id, '_kla_transaction_points', true );
        $reason     = get_post_meta( $post_id, '_kla_transaction_reason', true );
        $teacher_id = absint( get_post_meta( $post_id, '_kla_transaction_teacher_id', true ) );
        $balance    = (int) get_post_meta( $post_id, '_kla_transaction_new_balance', true );

        switch ( $column ) {

            case 'student':
                echo esc_html(
                    get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id )
                );
                break;

            case 'type':
                echo esc_html( ucfirst( $type ) );
                break;

            case 'points':
                echo esc_html( $points > 0 ? '+' . $points : (string) $points );
                break;

            case 'reason':
                echo esc_html( $reason );
                break;

            case 'teacher':
                if ( $teacher_id ) {
                    $teacher = get_user_by( 'id', $teacher_id );
                    echo esc_html( $teacher ? $teacher->display_name : '—' );
                } else {
                    echo '—';
                }
                break;

            case 'balance':
                echo esc_html( $balance . ' Kudos' );
                break;
        }
    }

    add_action(
        'manage_kudos_transaction_posts_custom_column',
        'kla_kudos_transaction_column_content',
        10,
        2
    );
}


/* =========================================================
   TRANSACTION DETAIL META BOX
   ========================================================= */

if ( ! function_exists( 'kla_transaction_detail_meta_box' ) ) {

    function kla_transaction_detail_meta_box() {

        add_meta_box(
            'kla_transaction_details',
            'Transaction Details',
            'kla_transaction_detail_callback',
            'kudos_transaction',
            'normal',
            'high'
        );
    }

    add_action( 'add_meta_boxes', 'kla_transaction_detail_meta_box' );
}


if ( ! function_exists( 'kla_transaction_detail_callback' ) ) {

    function kla_transaction_detail_callback( $post ) {

        $student_id  = absint( get_post_meta( $post->ID, '_kla_transaction_student_id', true ) );
        $points      = (int) get_post_meta( $post->ID, '_kla_transaction_points', true );
        $type        = get_post_meta( $post->ID, '_kla_transaction_type', true );
        $reason      = get_post_meta( $post->ID, '_kla_transaction_reason', true );
        $teacher_id  = absint( get_post_meta( $post->ID, '_kla_transaction_teacher_id', true ) );
        $activity_id = absint( get_post_meta( $post->ID, '_kla_transaction_activity_id', true ) );
        $old_balance = (int) get_post_meta( $post->ID, '_kla_transaction_old_balance', true );
        $new_balance = (int) get_post_meta( $post->ID, '_kla_transaction_new_balance', true );
        ?>

        <table class="widefat striped">
            <tbody>
                <tr>
                    <td><strong>Student</strong></td>
                    <td><?php echo esc_html( get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id ) ); ?></td>
                </tr>
                <tr>
                    <td><strong>Type</strong></td>
                    <td><?php echo esc_html( ucfirst( $type ) ); ?></td>
                </tr>
                <tr>
                    <td><strong>Kudos</strong></td>
                    <td><?php echo esc_html( $points > 0 ? '+' . $points : $points ); ?></td>
                </tr>
                <tr>
                    <td><strong>Reason</strong></td>
                    <td><?php echo esc_html( $reason ); ?></td>
                </tr>
                <tr>
                    <td><strong>Teacher</strong></td>
                    <td>
                        <?php
                        if ( $teacher_id ) {
                            $teacher = get_user_by( 'id', $teacher_id );
                            echo esc_html( $teacher ? $teacher->display_name : '—' );
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Activity</strong></td>
                    <td><?php echo esc_html( $activity_id ? get_the_title( $activity_id ) : '—' ); ?></td>
                </tr>
                <tr>
                    <td><strong>Previous Balance</strong></td>
                    <td><?php echo esc_html( $old_balance . ' Kudos' ); ?></td>
                </tr>
                <tr>
                    <td><strong>Balance After</strong></td>
                    <td><?php echo esc_html( $new_balance . ' Kudos' ); ?></td>
                </tr>
            </tbody>
        </table>

        <?php
    }
}


/* =========================================================
   STUDENT ADMIN COLUMN - KUDOS BALANCE
   ========================================================= */

if ( ! function_exists( 'kla_student_kudos_column' ) ) {

    function kla_student_kudos_column( $columns ) {

        $new_columns = array();

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            if ( 'title' === $key || 'student' === $key ) {
                $new_columns['kudos_balance'] = 'Kudos Balance';
            }
        }

        if ( ! isset( $new_columns['kudos_balance'] ) ) {
            $new_columns['kudos_balance'] = 'Kudos Balance';
        }

        return $new_columns;
    }

    add_filter(
        'manage_kudos_student_posts_columns',
        'kla_student_kudos_column'
    );
}


if ( ! function_exists( 'kla_student_kudos_column_content' ) ) {

    function kla_student_kudos_column_content( $column, $post_id ) {

        if ( 'kudos_balance' === $column ) {
            echo '<strong>' . esc_html( kla_get_student_kudos_balance( $post_id ) ) . '</strong>';
        }
    }

    add_action(
        'manage_kudos_student_posts_custom_column',
        'kla_student_kudos_column_content',
        10,
        2
    );
}


/* =========================================================
   ADMIN NOTICE
   ========================================================= */

if ( ! function_exists( 'kla_rewards_admin_notice' ) ) {

    function kla_rewards_admin_notice() {

        $screen = get_current_screen();

        if ( ! $screen || 'kudos_student' !== $screen->post_type ) {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Kudos Wallet:</strong>
                Student Kudos are managed through the Kudos Ledger. Normal awards should be made from the Teacher Dashboard.
            </p>
        </div>
        <?php
    }

    add_action( 'admin_notices', 'kla_rewards_admin_notice' );
}
