<?php

/* =========================================================
   PARENTS
   ========================================================= */

function kudos_register_parents() {

    register_post_type('kudos_parent', array(

        'labels' => array(
            'name'               => 'Parents',
            'singular_name'      => 'Parent',
            'menu_name'          => 'Parents',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Parent',
            'edit_item'          => 'Edit Parent',
            'new_item'           => 'New Parent',
            'view_item'          => 'View Parent',
            'search_items'       => 'Search Parents',
            'not_found'          => 'No parents found',
            'not_found_in_trash' => 'No parents found in Trash',
        ),

        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_admin_bar' => true,

        'menu_position'     => 26,
        'menu_icon'         => 'dashicons-groups',

        'capability_type'   => 'post',
        'map_meta_cap'      => true,

        'supports' => array(
            'title',
        ),

        'show_in_rest' => true,
    ));
}

add_action('init', 'kudos_register_parents');


/* =========================================================
   PARENT DETAILS
   ========================================================= */

function kudos_parent_meta_box() {

    add_meta_box(
        'kudos_parent_details',
        'Parent Details',
        'kudos_parent_details_callback',
        'kudos_parent',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kudos_parent_meta_box'
);


function kudos_parent_details_callback($post) {

    wp_nonce_field(
        'kudos_save_parent_details',
        'kudos_parent_nonce'
    );

    $parent_name = get_post_meta(
        $post->ID,
        '_kudos_parent_name',
        true
    );

    $mobile = get_post_meta(
        $post->ID,
        '_kudos_parent_mobile',
        true
    );

    $email = get_post_meta(
        $post->ID,
        '_kudos_parent_email',
        true
    );

    ?>

    <p>
        <label>
            <strong>Parent Name</strong>
        </label>
    </p>

    <input
        type="text"
        name="kudos_parent_name"
        value="<?php echo esc_attr($parent_name); ?>"
        style="width:100%;max-width:500px;"
        placeholder="Parent Name"
    />


    <p style="margin-top:20px;">
        <label>
            <strong>Mobile Number</strong>
        </label>
    </p>

    <input
        type="text"
        name="kudos_parent_mobile"
        value="<?php echo esc_attr($mobile); ?>"
        style="width:100%;max-width:500px;"
        placeholder="+91 9876543210"
    />


    <p style="margin-top:20px;">
        <label>
            <strong>Email Address</strong>
        </label>
    </p>

    <input
        type="email"
        name="kudos_parent_email"
        value="<?php echo esc_attr($email); ?>"
        style="width:100%;max-width:500px;"
        placeholder="parent@example.com"
    />

    <p style="color:#666;margin-top:15px;">
        Parent login account will be created separately using
        WordPress's secure user authentication system.
    </p>

    <?php
}


function kudos_save_parent_details($post_id) {

    if (
        !isset($_POST['kudos_parent_nonce']) ||
        !wp_verify_nonce(
            $_POST['kudos_parent_nonce'],
            'kudos_save_parent_details'
        )
    ) {
        return;
    }

    if (
        defined('DOING_AUTOSAVE') &&
        DOING_AUTOSAVE
    ) {
        return;
    }

    if (
        !current_user_can(
            'edit_post',
            $post_id
        )
    ) {
        return;
    }


    if (
        isset($_POST['kudos_parent_name'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_parent_name',
            sanitize_text_field(
                $_POST['kudos_parent_name']
            )
        );
    }


    if (
        isset($_POST['kudos_parent_mobile'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_parent_mobile',
            sanitize_text_field(
                $_POST['kudos_parent_mobile']
            )
        );
    }


    if (
        isset($_POST['kudos_parent_email'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_parent_email',
            sanitize_email(
                $_POST['kudos_parent_email']
            )
        );
    }
}

add_action(
    'save_post_kudos_parent',
    'kudos_save_parent_details'
);



/* =========================================================
   PARENT ↔ STUDENT CONNECTION
   ========================================================= */

/*
 * Show linked students on Parent edit screen
 */

function kudos_parent_children_meta_box() {

    add_meta_box(
        'kudos_parent_children',
        'Linked Students',
        'kudos_parent_children_callback',
        'kudos_parent',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kudos_parent_children_meta_box'
);


/*
 * Display children of selected parent
 */

function kudos_parent_children_callback($post) {

    $students = get_posts(array(

        'post_type'      => 'kudos_student',
        'post_status' => 'any',
        'posts_per_page' => -1,

        'meta_query' => array(
            array(
                'key'     => '_kudos_student_parent',
                'value'   => $post->ID,
                'compare' => '=',
            ),
        ),

        'orderby' => 'title',
        'order'   => 'ASC',
    ));

    if (empty($students)) {

        echo '<p style="color:#666;">';
        echo 'No students are linked to this parent yet.';
        echo '</p>';

        return;
    }

    echo '<div style="background:#f7f9fc;padding:15px;border-radius:8px;">';

    echo '<strong>';
    echo count($students);
    echo ' linked student(s)';
    echo '</strong>';

    echo '<ul style="margin-top:12px;">';

    foreach ($students as $student) {

        $student_name = get_post_meta(
            $student->ID,
            '_kudos_student_name',
            true
        );

        $student_class = get_post_meta(
            $student->ID,
            '_kudos_student_class',
            true
        );

        $display_name = $student_name
            ? $student_name
            : $student->post_title;

        echo '<li style="margin-bottom:8px;">';

        echo '<a href="' .
            esc_url(
                get_edit_post_link(
                    $student->ID
                )
            ) .
            '">';

        echo '<strong>';
        echo esc_html($display_name);
        echo '</strong>';

        echo '</a>';

        if ($student_class) {

            echo ' — ';

            echo esc_html(
                $student_class
            );
        }

        echo '</li>';
    }

    echo '</ul>';

    echo '</div>';
}


/* =========================================================
   PARENT ADMIN COLUMNS
   ========================================================= */

function kudos_parent_columns($columns) {

    return array(
        'cb'       => '<input type="checkbox" />',
        'title'    => 'Parent',
        'mobile'   => 'Mobile',
        'email'    => 'Email',
        'children' => 'Children',
        'date'     => 'Date',
    );
}

add_filter(
    'manage_kudos_parent_posts_columns',
    'kudos_parent_columns'
);


/*
 * Fill Parent columns
 */

function kudos_parent_column_content(
    $column,
    $post_id
) {

    if ($column === 'mobile') {

        $mobile = get_post_meta(
            $post_id,
            '_kudos_parent_mobile',
            true
        );

        echo $mobile
            ? esc_html($mobile)
            : '—';
    }


    if ($column === 'email') {

        $email = get_post_meta(
            $post_id,
            '_kudos_parent_email',
            true
        );

        echo $email
            ? esc_html($email)
            : '—';
    }


    if ($column === 'children') {

        $students = get_posts(array(

            'post_type'      => 'kudos_student',
            'post_status'    => 'any',
            'posts_per_page' => -1,

            'meta_query' => array(
                array(
                    'key'     => '_kudos_student_parent',
                    'value'   => $post_id,
                    'compare' => '=',
                ),
            ),
        ));

        if (empty($students)) {

            echo '<span style="color:#999;">0</span>';

            return;
        }

        echo '<strong>';
        echo count($students);
        echo '</strong>';

        echo '<br>';

        $names = array();

        foreach ($students as $student) {

            $student_name = get_post_meta(
                $student->ID,
                '_kudos_student_name',
                true
            );

            if (!$student_name) {

                $student_name = $student->post_title;
            }

            $names[] = $student_name;
        }

        echo '<small>';
        echo esc_html(
            implode(', ', $names)
        );
        echo '</small>';
    }
}

add_action(
    'manage_kudos_parent_posts_custom_column',
    'kudos_parent_column_content',
    10,
    2
);

/* =========================================================
   PARENT ROLE
   ========================================================= */

function kla_create_parent_role() {

    if ( ! get_role( 'kudos_parent' ) ) {

        add_role(
            'kudos_parent',
            'Kudos Parent',
            array(
                'read' => true,
            )
        );
    }
}

add_action(
    'init',
    'kla_create_parent_role'
);


/* =========================================================
   PARENT LOGIN
   ========================================================= */

function kla_parent_login_shortcode() {

    if ( is_user_logged_in() ) {

        $user = wp_get_current_user();

        if (
            in_array(
                'kudos_parent',
                (array) $user->roles,
                true
            )
        ) {

            wp_safe_redirect(
                home_url( '/parent-dashboard/' )
            );

            exit;
        }
    }

    ob_start();
    ?>

    <div class="kla-parent-login">

        <div class="kla-parent-login-box">

            <div class="kla-parent-login-icon">
                👨‍👩‍👧
            </div>

            <div class="kla-parent-brand">
                <strong>Kudos</strong>
                <span>Learning Academy</span>
            </div>

            <h1>Parent Login</h1>

            <p>
                Login to view your child's learning,
                performance and Kudos rewards.
            </p>

            <?php

            wp_login_form(
                array(
                    'redirect'       =>
                        home_url(
                            '/parent-dashboard/'
                        ),

                    'label_username' =>
                        'Username or Email',

                    'label_password' =>
                        'Password',

                    'label_log_in' =>
                        'Login',

                    'remember' =>
                        true,

                    'form_id' =>
                        'kla-parent-login-form',
                )
            );

            ?>

        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'kla_parent_login',
    'kla_parent_login_shortcode'
);


/* =========================================================
   PARENT LOGIN REDIRECT
   ========================================================= */

function kla_parent_login_redirect(
    $redirect_to,
    $requested_redirect_to,
    $user
) {

    if (
        isset( $user->roles ) &&
        is_array( $user->roles ) &&
        in_array(
            'kudos_parent',
            $user->roles,
            true
        )
    ) {

        return home_url(
            '/parent-dashboard/'
        );
    }

    return $redirect_to;
}

add_filter(
    'login_redirect',
    'kla_parent_login_redirect',
    20,
    3
);


/* =========================================================
   FIND PARENT RECORD
   ========================================================= */

function kla_get_parent_record_id() {

    if ( ! is_user_logged_in() ) {
        return 0;
    }

    $user = wp_get_current_user();

    if ( ! $user || ! $user->ID ) {
        return 0;
    }

    /*
     * 1. If parent record is already linked, use it.
     */
    $linked_parent = get_user_meta(
        $user->ID,
        '_kla_parent_record_id',
        true
    );

    if ( $linked_parent ) {
        $linked_parent = absint( $linked_parent );

        if ( get_post_type( $linked_parent ) === 'kudos_parent' ) {
            return $linked_parent;
        }
    }

    /*
     * 2. Try to find parent using username/login.
     *
     * Example:
     * WordPress username = 9999355572
     * Parent mobile      = 9999355572
     */
    $login_value = sanitize_text_field( $user->user_login );

    if ( $login_value ) {

        $parents = get_posts(
            array(
                'post_type'      => 'kudos_parent',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_parent_mobile',
                        'value'   => $login_value,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( ! empty( $parents ) ) {

            $parent_id = $parents[0]->ID;

            update_user_meta(
                $user->ID,
                '_kla_parent_record_id',
                $parent_id
            );

            return $parent_id;
        }
    }

    /*
     * 3. Try using WordPress email.
     *
     * Parent email in CPT must match user email.
     */
    if ( ! empty( $user->user_email ) ) {

        $parents = get_posts(
            array(
                'post_type'      => 'kudos_parent',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_parent_email',
                        'value'   => $user->user_email,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( ! empty( $parents ) ) {

            $parent_id = $parents[0]->ID;

            update_user_meta(
                $user->ID,
                '_kla_parent_record_id',
                $parent_id
            );

            return $parent_id;
        }
    }

    /*
     * 4. Try matching username with parent name.
     * Useful if username is the parent's name instead of mobile.
     */
    if ( $login_value ) {

        $parents = get_posts(
            array(
                'post_type'      => 'kudos_parent',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_parent_name',
                        'value'   => $login_value,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( ! empty( $parents ) ) {

            $parent_id = $parents[0]->ID;

            update_user_meta(
                $user->ID,
                '_kla_parent_record_id',
                $parent_id
            );

            return $parent_id;
        }
    }

    return 0;
}

/* =========================================================
   GET STUDENT KUDOS BALANCE
   ========================================================= */

function kla_get_kudos_balance( $student_id ) {

    $balance = get_post_meta(
        $student_id,
        '_kudos_student_rewards',
        true
    );

    if ( $balance === '' ) {
        $balance = 0;
    }

    return max(
        0,
        intval( $balance )
    );
}


/* =========================================================
   SET STUDENT KUDOS BALANCE
   ========================================================= */

function kla_set_kudos_balance(
    $student_id,
    $balance
) {

    update_post_meta(
        $student_id,
        '_kudos_student_rewards',
        max(
            0,
            intval( $balance )
        )
    );
}


/* =========================================================
   POINT VALUE
   1 KUDOS = ₹1
   ========================================================= */

function kla_kudos_value() {

    return 1;
}


/* =========================================================
   REDEMPTION POST TYPE
   ========================================================= */

function kla_register_redemption_post_type() {

    register_post_type(
        'kudos_redemption',
        array(

            'labels' => array(
                'name'          => 'Redemptions',
                'singular_name' => 'Redemption',
                'menu_name'     => 'Redemptions',
                'add_new_item'  => 'Add Redemption',
                'edit_item'     => 'View Redemption',
            ),

            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,

            'menu_position' => 29,

            'menu_icon' =>
                'dashicons-tickets-alt',

            'supports' =>
                array( 'title' ),

            'show_in_rest' => false,
        )
    );
}

add_action(
    'init',
    'kla_register_redemption_post_type'
);


/* =========================================================
   PROCESS REDEMPTION
   ========================================================= */

function kla_process_redemption() {

    if (
        ! isset(
            $_POST['kla_redeem_nonce']
        )
    ) {
        return;
    }


    if (
        ! wp_verify_nonce(
            $_POST['kla_redeem_nonce'],
            'kla_redeem_action'
        )
    ) {
        return;
    }


    if ( ! is_user_logged_in() ) {
        return;
    }


    $user = wp_get_current_user();


    if (
        ! in_array(
            'kudos_parent',
            (array) $user->roles,
            true
        )
    ) {
        return;
    }


    $parent_id =
        kla_get_parent_record_id();


    if ( ! $parent_id ) {
        return;
    }


    $student_id = isset(
        $_POST['kla_student_id']
    )
        ? absint(
            $_POST['kla_student_id']
        )
        : 0;


    $activity_id = isset(
        $_POST['kla_activity_id']
    )
        ? absint(
            $_POST['kla_activity_id']
        )
        : 0;


    $points = isset(
        $_POST['kla_points']
    )
        ? absint(
            $_POST['kla_points']
        )
        : 0;


    if (
        ! $student_id ||
        ! $activity_id ||
        ! $points
    ) {
        return;
    }


    /*
     * Verify student belongs to parent.
     */

    $student_parent = get_post_meta(
        $student_id,
        '_kudos_student_parent',
        true
    );


    if (
        intval( $student_parent ) !==
        intval( $parent_id )
    ) {
        return;
    }


    /*
     * Verify activity.
     */

    if (
        get_post_type( $activity_id ) !==
        'kudos_activity'
    ) {
        return;
    }


    if (
        get_post_status( $activity_id ) !==
        'publish'
    ) {
        return;
    }


    /*
     * Get fee.
     */

    $fee = get_post_meta(
        $activity_id,
        '_kudos_activity_fee',
        true
    );


    $fee_type = get_post_meta(
        $activity_id,
        '_kudos_activity_fee_type',
        true
    );


    /*
     * Activity based fees cannot
     * automatically calculate yet.
     */

    if (
        $fee_type === 'activity_based' ||
        ! is_numeric( $fee ) ||
        floatval( $fee ) <= 0
    ) {
        return;
    }


    $fee = floatval( $fee );


    /*
     * Current balance.
     */

    $balance =
        kla_get_kudos_balance(
            $student_id
        );


    /*
     * Cannot spend more than balance.
     */

    if (
        $points > $balance
    ) {
        return;
    }


    /*
     * Calculate discount.
     */

    $discount =
        $points *
        kla_kudos_value();


    /*
     * Discount cannot exceed fee.
     */

    if ( $discount > $fee ) {

        $discount = $fee;

        $points = min(
            $balance,
            (int) ceil(
                $fee /
                kla_kudos_value()
            )
        );
    }


    /*
     * Final payable fee.
     */

    $payable =
        max(
            0,
            $fee - $discount
        );


    /*
     * New balance.
     */

    $new_balance =
        $balance - $points;


    /*
     * Student name.
     */

    $student_name = get_post_meta(
        $student_id,
        '_kudos_student_name',
        true
    );

    if ( ! $student_name ) {

        $student_name =
            get_the_title(
                $student_id
            );
    }


    /*
     * Activity name.
     */

    $activity_name =
        get_the_title(
            $activity_id
        );


    /*
     * Create redemption record.
     */

    $redemption_id =
        wp_insert_post(
            array(

                'post_title' =>
                    $student_name .
                    ' - ' .
                    $activity_name .
                    ' - ' .
                    current_time(
                        'Y-m-d H:i'
                    ),

                'post_status' =>
                    'publish',

                'post_type' =>
                    'kudos_redemption',
            ),
            true
        );


    if (
        is_wp_error(
            $redemption_id
        )
    ) {
        return;
    }


    /*
     * Store transaction.
     */

    update_post_meta(
        $redemption_id,
        '_kla_parent_id',
        $parent_id
    );

    update_post_meta(
        $redemption_id,
        '_kla_student_id',
        $student_id
    );

    update_post_meta(
        $redemption_id,
        '_kla_activity_id',
        $activity_id
    );

    update_post_meta(
        $redemption_id,
        '_kla_points_used',
        $points
    );

    update_post_meta(
        $redemption_id,
        '_kla_activity_fee',
        $fee
    );

    update_post_meta(
        $redemption_id,
        '_kla_discount',
        $discount
    );

    update_post_meta(
        $redemption_id,
        '_kla_payable_fee',
        $payable
    );

    update_post_meta(
        $redemption_id,
        '_kla_old_balance',
        $balance
    );

    update_post_meta(
        $redemption_id,
        '_kla_new_balance',
        $new_balance
    );

    update_post_meta(
        $redemption_id,
        '_kla_status',
        'Approved'
    );


    /*
     * Deduct Kudos.
     */

    kla_set_kudos_balance(
        $student_id,
        $new_balance
    );


    /*
     * Also write the redemption into the central Kudos Ledger.
     *
     * The existing wallet remains the source of the current balance,
     * while this transaction provides the permanent history entry.
     */
    $ledger_transaction_id = wp_insert_post(
        array(
            'post_title'  =>
                $student_name .
                ' - Redeemed ' .
                $points .
                ' Kudos - ' .
                $activity_name,
            'post_status' => 'publish',
            'post_type'   => 'kudos_transaction',
        ),
        true
    );

    if ( ! is_wp_error( $ledger_transaction_id ) ) {

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_student_id',
            $student_id
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_points',
            $points
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_type',
            'redemption'
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_reason',
            'Redeemed for ' . $activity_name
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_teacher_id',
            0
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_activity_id',
            $activity_id
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_previous_balance',
            $balance
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_new_balance',
            $new_balance
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_parent_id',
            $parent_id
        );

        update_post_meta(
            $ledger_transaction_id,
            '_kla_transaction_redemption_id',
            $redemption_id
        );
    }


    /*
     * Success information.
     */

    set_transient(
        'kla_redemption_' .
        $user->ID,

        array(

            'student' =>
                $student_name,

            'activity' =>
                $activity_name,

            'points' =>
                $points,

            'discount' =>
                $discount,

            'payable' =>
                $payable,

            'balance' =>
                $new_balance,

        ),

        60
    );


    /*
     * Redirect to dashboard.
     */

    wp_safe_redirect(
        home_url(
            '/parent-dashboard/?redeemed=1'
        )
    );

    exit;
}

add_action(
    'template_redirect',
    'kla_process_redemption'
);




/* =========================================================
   PARENT - ACTIVE ACTIVITY ENROLLMENTS
   ========================================================= */

if ( ! function_exists( 'kla_get_parent_active_enrollments' ) ) {
    function kla_get_parent_active_enrollments( $children ) {
        $student_ids = wp_list_pluck( $children, 'ID' );
        if ( empty( $student_ids ) || ! post_type_exists( 'kudos_enrollment' ) ) {
            return array();
        }
        return get_posts( array(
            'post_type'      => 'kudos_enrollment',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array( 'key' => '_kla_enrollment_student_id', 'value' => $student_ids, 'compare' => 'IN' ),
                array( 'key' => '_kla_enrollment_status', 'value' => 'active', 'compare' => '=' ),
            ),
        ) );
    }
}

/* =========================================================
   PARENT DASHBOARD
   ========================================================= */


/* =========================================================
   PARENT DASHBOARD - REWARD HISTORY HELPERS
   ========================================================= */

/**
 * Get the latest performance record for a student.
 * Supports both the Student post ID relationship and the
 * legacy student-name relationship.
 */
function kla_get_latest_student_performance( $student_id ) {

    $records = get_posts(
        array(
            'post_type'      => 'kudos_performance',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_kudos_performance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kudos_performance_student_name',
                    'value'   => get_post_meta(
                        $student_id,
                        '_kudos_student_name',
                        true
                    ),
                    'compare' => '=',
                ),
            ),
        )
    );

    return ! empty( $records ) ? $records[0] : false;
}


/**
 * Get Kudos ledger history for a student.
 */
function kla_get_student_kudos_history( $student_id, $limit = 10 ) {

    return get_posts(
        array(
            'post_type'      => 'kudos_transaction',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $limit ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_transaction_student_id',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );
}


/**
 * Get parent redemption history.
 */
function kla_get_parent_redemption_history( $parent_id, $limit = 10 ) {

    return get_posts(
        array(
            'post_type'      => 'kudos_redemption',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $limit ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_parent_id',
                    'value'   => $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );
}


function kla_parent_dashboard_shortcode() {

    if ( ! is_user_logged_in() ) {

        return '
        <div class="kla-parent-message">

            <h2>Parent Login Required</h2>

            <p>
                Please login to access your dashboard.
            </p>

            <a href="' .
            esc_url(
                home_url(
                    '/parent-login/'
                )
            ) .
            '">
                Parent Login
            </a>

        </div>';
    }


    $user =
        wp_get_current_user();


    if (
        ! in_array(
            'kudos_parent',
            (array) $user->roles,
            true
        )
    ) {

        return '
        <div class="kla-parent-message">

            <h2>Access Restricted</h2>

            <p>
                This dashboard is available
                only to parent accounts.
            </p>

        </div>';
    }


    $parent_id =
        kla_get_parent_record_id();


    if ( ! $parent_id ) {

        return '
        <div class="kla-parent-message">

            <h2>Parent Profile Not Linked</h2>

            <p>
                Your login account is not linked
                with a parent profile yet.
            </p>

        </div>';
    }


    $parent_name =
        get_post_meta(
            $parent_id,
            '_kudos_parent_name',
            true
        );


    if ( ! $parent_name ) {
        $parent_name =
            $user->display_name;
    }


    /*
     * Get children.
     */

    $children =
        get_posts(
            array(

                'post_type' =>
                    'kudos_student',

                'post_status' =>
                    'publish',

                'posts_per_page' =>
                    -1,

                'orderby' =>
                    'title',

                'order' =>
                    'ASC',

                'meta_query' =>
                    array(

                        array(

                            'key' =>
                                '_kudos_student_parent',

                            'value' =>
                                $parent_id,

                            'compare' =>
                                '=',

                        ),

                    ),

            )
        );


    /*
     * Total Kudos.
     */

    $total_kudos = 0;

    foreach ( $children as $child ) {

        $total_kudos +=
            kla_get_kudos_balance(
                $child->ID
            );
    }


    /*
     * Logout.
     */

    $logout_url =
        wp_logout_url(
            home_url(
                '/parent-login/'
            )
        );


    ob_start();

    ?>

    <div class="kla-parent-dashboard">


        <!-- HEADER -->

        <header class="kla-parent-header">

            <div class="kla-parent-logo">

                <div class="kla-parent-logo-icon">
                    🌱
                </div>

                <div>

                    <strong>
                        Kudos
                    </strong>

                    <span>
                        Learning Academy
                    </span>

                </div>

            </div>


            <div class="kla-parent-user">

                <div>

                    <strong>
                        <?php
                        echo esc_html(
                            $parent_name
                        );
                        ?>
                    </strong>

                    <span>
                        Parent
                    </span>

                </div>

                <a
                    href="<?php
                    echo esc_url(
                        $logout_url
                    );
                    ?>"
                >
                    Logout
                </a>

            </div>

        </header>


        <main class="kla-parent-main">


            <!-- WELCOME -->

            <section class="kla-parent-welcome">

                <span>
                    👨‍👩‍👧 Parent Dashboard
                </span>

                <h1>
                    Welcome,
                    <?php
                    echo esc_html(
                        $parent_name
                    );
                    ?>
                    👋
                </h1>

                <p>
                    Track your child's learning,
                    progress and Kudos rewards.
                </p>

            </section>


            <!-- STATS -->

            <section class="kla-parent-stats">

                <div>

                    <span>
                        My Children
                    </span>

                    <strong>
                        <?php
                        echo count(
                            $children
                        );
                        ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Kudos Wallet
                    </span>

                    <strong>
                        ⭐
                        <?php
                        echo esc_html(
                            $total_kudos
                        );
                        ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Learning Records
                    </span>

                    <strong>
                        <?php

                        $performance_count =
                            0;

                        foreach (
                            $children
                            as $child
                        ) {

                            $name =
                                get_post_meta(
                                    $child->ID,
                                    '_kudos_student_name',
                                    true
                                );

                            $records =
                                get_posts(
                                    array(

                                        'post_type' =>
                                            'kudos_performance',

                                        'post_status' =>
                                            'publish',

                                        'posts_per_page' =>
                                            1,

                                        'meta_query' =>
                                            array(

                                                array(

                                                    'key' =>
                                                    '_kudos_performance_student_name',

                                                    'value' =>
                                                    $name,

                                                    'compare' =>
                                                    '=',

                                                ),

                                            ),

                                    )
                                );

                            if (
                                ! empty(
                                    $records
                                )
                            ) {

                                $performance_count++;
                            }
                        }

                        echo esc_html(
                            $performance_count
                        );

                        ?>
                    </strong>

                </div>

            </section>


            <!-- CHILDREN -->

            <section class="kla-parent-section">

                <div class="kla-section-title">

                    <span>
                        FAMILY
                    </span>

                    <h2>
                        My Children
                    </h2>

                </div>


                <div class="kla-children-grid">

                    <?php foreach (
                        $children
                        as $child
                    ) : ?>

                        <?php

                        $student_name =
                            get_post_meta(
                                $child->ID,
                                '_kudos_student_name',
                                true
                            );

                        if (
                            ! $student_name
                        ) {

                            $student_name =
                                $child->post_title;
                        }


                        $class =
                            get_post_meta(
                                $child->ID,
                                '_kudos_student_class',
                                true
                            );


                        $balance =
                            kla_get_kudos_balance(
                                $child->ID
                            );

                        ?>

                        <div class="kla-child-card">

                            <div class="kla-child-icon">
                                🎓
                            </div>

                            <div>

                                <h3>
                                    <?php
                                    echo esc_html(
                                        $student_name
                                    );
                                    ?>
                                </h3>

                                <p>
                                    <?php
                                    echo $class
                                        ? esc_html(
                                            $class
                                        )
                                        : 'Academy Student';
                                    ?>
                                </p>

                            </div>

                            <div class="kla-child-kudos">

                                <span>
                                    Kudos Balance
                                </span>

                                <strong>
                                    ⭐
                                    <?php
                                    echo esc_html(
                                        $balance
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>


            <!-- PERFORMANCE -->

            <section class="kla-parent-section">

                <div class="kla-section-title">

                    <span>
                        LEARNING
                    </span>

                    <h2>
                        Learning & Performance
                    </h2>

                </div>


                <?php foreach (
                    $children
                    as $child
                ) : ?>

                    <?php

                    $student_name =
                        get_post_meta(
                            $child->ID,
                            '_kudos_student_name',
                            true
                        );

                    if (
                        ! $student_name
                    ) {

                        $student_name =
                            $child->post_title;
                    }


                    $records =
                        get_posts(
                            array(

                                'post_type' =>
                                    'kudos_performance',

                                'post_status' =>
                                    'publish',

                                'posts_per_page' =>
                                    1,

                                'meta_query' =>
                                    array(

                                        array(

                                            'key' =>
                                            '_kudos_performance_student_name',

                                            'value' =>
                                            $student_name,

                                            'compare' =>
                                            '=',

                                        ),

                                    ),

                            )
                        );

                    if (
                        empty(
                            $records
                        )
                    ) {
                        continue;
                    }


                    $performance =
                        $records[0]->ID;


                    $metrics = array(

                        'Punctuality' =>
                            '_kudos_performance_punctuality',

                        'Discipline' =>
                            '_kudos_performance_discipline',

                        'Etiquettes' =>
                            '_kudos_performance_etiquettes',

                        'Workshop Participation' =>
                            '_kudos_performance_workshops',

                        'Learning Progress' =>
                            '_kudos_performance_school',

                        'Activity Participation' =>
                            '_kudos_performance_participation',

                    );

                    ?>

                    <div class="kla-performance-card">

                        <h3>
                            🎓
                            <?php
                            echo esc_html(
                                $student_name
                            );
                            ?>
                        </h3>

                        <div class="kla-performance-grid">

                            <?php foreach (
                                $metrics
                                as $label => $meta_key
                            ) : ?>

                                <?php

                                $value =
                                    get_post_meta(
                                        $performance,
                                        $meta_key,
                                        true
                                    );

                                ?>

                                <div>

                                    <span>
                                        <?php
                                        echo esc_html(
                                            $label
                                        );
                                        ?>
                                    </span>

                                    <strong>
                                        <?php
                                        echo $value !== ''
                                            ? esc_html(
                                                $value
                                            ) . '/100'
                                            : '—';
                                        ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </section>


            <!-- ENROLLED ACTIVITIES -->

            <?php
            $active_enrollments = function_exists( 'kla_get_active_enrollments_for_parent' )
                ? kla_get_active_enrollments_for_parent( $parent_id )
                : array();
            ?>

            <section class="kla-parent-section kla-my-activities-section">

                <div class="kla-section-title">
                    <span>ACTIVE LEARNING</span>
                    <h2>Enrolled Activities</h2>
                    <p>Activities your children are currently enrolled in..</p>
                </div>

                <?php if ( empty( $active_enrollments ) ) : ?>
                    <div class="kla-my-activities-empty">
                        <div class="kla-my-activities-empty-icon">📚</div>
                        <strong>No active activities yet</strong>
                        <p>Your registered activities will appear here.</p>
                    </div>
                <?php else : ?>
                    <div class="kla-my-activities-list">
                        <?php foreach ( $active_enrollments as $enrollment ) : ?>
                            <?php
                            $enrollment_student_id = absint( get_post_meta( $enrollment->ID, '_kla_enrollment_student_id', true ) );
                            $enrollment_activity_id = absint( get_post_meta( $enrollment->ID, '_kla_enrollment_activity_id', true ) );
                            $enrollment_fee = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_fee', true );
                            if ( $enrollment_fee <= 0 && $enrollment_activity_id ) {
                                $activity_fee = (float) get_post_meta( $enrollment_activity_id, '_kudos_activity_fee', true );
                                if ( $activity_fee > 0 ) {
                                    $enrollment_fee = $activity_fee;
                                    update_post_meta( $enrollment->ID, '_kla_enrollment_fee', $enrollment_fee );
                                }
                            }
                            $enrollment_start = get_post_meta( $enrollment->ID, '_kla_enrollment_start_date', true );
                            $enrollment_student_name = get_post_meta( $enrollment_student_id, '_kudos_student_name', true );
                            if ( ! $enrollment_student_name ) {
                                $enrollment_student_name = get_the_title( $enrollment_student_id );
                            }
                            $enrollment_activity_name = get_the_title( $enrollment_activity_id );

                            $enrollment_kudos_redeemed = (int) get_post_meta( $enrollment->ID, '_kla_enrollment_kudos_redeemed', true );
                            $enrollment_kudos_discount = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_kudos_discount', true );

                            /*
                             * Read payment data from the ENROLLMENT record.
                             * The admin dashboard is the source of truth for
                             * fee/payment updates.
                             */
                            $enrollment_net_payable = (float) get_post_meta( $enrollment->ID, '_kla_enrollment_net_payable', true );
                            if ( $enrollment_net_payable <= 0 ) {
                                $enrollment_net_payable = max(
                                    0,
                                    $enrollment_fee - $enrollment_kudos_discount
                                );
                            }

                            $enrollment_amount_paid = (float) get_post_meta(
                                $enrollment->ID,
                                '_kla_enrollment_amount_paid',
                                true
                            );

                            $enrollment_amount_due = (float) get_post_meta(
                                $enrollment->ID,
                                '_kla_enrollment_amount_due',
                                true
                            );

                            $enrollment_payment_status = sanitize_key(
                                get_post_meta(
                                    $enrollment->ID,
                                    '_kla_enrollment_payment_status',
                                    true
                                )
                            );

                            /*
                             * Backward compatibility for older enrollment
                             * records that do not yet have payment metadata.
                             */
                            if ( ! in_array(
                                $enrollment_payment_status,
                                array( 'pending', 'partial', 'paid' ),
                                true
                            ) ) {
                                if ( $enrollment_amount_paid >= $enrollment_net_payable && $enrollment_net_payable > 0 ) {
                                    $enrollment_payment_status = 'paid';
                                } elseif ( $enrollment_amount_paid > 0 ) {
                                    $enrollment_payment_status = 'partial';
                                } else {
                                    $enrollment_payment_status = 'pending';
                                }
                            }

                            /*
                             * Preserve any overpayment so the parent can see
                             * the real amount paid and any resulting credit.
                             */
                            $enrollment_amount_paid = max( 0, $enrollment_amount_paid );

                            if ( 'paid' === $enrollment_payment_status ) {
                                $enrollment_amount_due = 0;
                            } else {
                                $enrollment_amount_due = max(
                                    0,
                                    $enrollment_net_payable - $enrollment_amount_paid
                                );
                            }

                            $enrollment_credit = max(
                                0,
                                $enrollment_amount_paid - $enrollment_net_payable
                            );

                            $payment_label = 'paid' === $enrollment_payment_status
                                ? 'Paid'
                                : ( 'partial' === $enrollment_payment_status ? 'Partially Paid' : 'Unpaid' );

                            $payment_class = 'paid' === $enrollment_payment_status
                                ? 'kla-payment-paid'
                                : ( 'partial' === $enrollment_payment_status ? 'kla-payment-partial' : 'kla-payment-unpaid' );

                            $enrollment_status = sanitize_key(
                                get_post_meta(
                                    $enrollment->ID,
                                    '_kla_enrollment_status',
                                    true
                                )
                            );

                            $enrollment_status_label = $enrollment_status
                                ? ucfirst( $enrollment_status )
                                : 'Active';

                            $enrollment_redemption_id = absint(
                                get_post_meta(
                                    $enrollment->ID,
                                    '_kla_enrollment_redemption_id',
                                    true
                                )
                            );
                            ?>

                            <div class="kla-my-activity-card">
                                <div class="kla-my-activity-icon">🎯</div>
                                <div class="kla-my-activity-main">
                                    <div class="kla-my-activity-topline">
                                        <div>
                                            <h3><?php echo esc_html( $enrollment_activity_name ); ?></h3>
                                            <p><?php echo esc_html( $enrollment_student_name ); ?></p>
                                        </div>
                                        <span class="kla-my-activity-status">
                                            <?php echo esc_html( strtoupper( $enrollment_status_label ) ); ?>
                                        </span>
                                    </div>

                                    <div class="kla-my-activity-details">
                                        <div>
                                            <span>Registered</span>
                                            <strong><?php echo esc_html( $enrollment_start ?: get_post_meta( $enrollment->ID, '_kla_enrollment_date', true ) ?: '—' ); ?></strong>
                                        </div>

                                        <div>
                                            <span>Activity Fee</span>
                                            <strong>₹<?php echo esc_html( number_format( $enrollment_fee ) ); ?></strong>
                                        </div>

                                        <div>
                                            <span>Kudos Benefit</span>
                                            <strong>₹<?php echo esc_html( number_format( $enrollment_kudos_discount ) ); ?></strong>
                                        </div>

                                        <div>
                                            <span>Payable Fee</span>
                                            <strong>₹<?php echo esc_html( number_format( $enrollment_net_payable ) ); ?></strong>
                                        </div>
                                    </div>

                                    <div class="kla-my-activity-payment-details">
                                        <div>
                                            <span>Payment</span>
                                            <strong class="<?php echo esc_attr( $payment_class ); ?>">
                                                <?php echo esc_html( $payment_label ); ?>
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Paid</span>
                                            <strong>₹<?php echo esc_html( number_format( $enrollment_amount_paid ) ); ?></strong>
                                        </div>

                                        <div>
                                            <span>Due</span>
                                            <strong>₹<?php echo esc_html( number_format( $enrollment_amount_due ) ); ?></strong>
                                        </div>

                                        <?php if ( $enrollment_redemption_id ) : ?>
                                            <div>
                                                <span>Reward</span>
                                                <strong>🎁 Kudos Redeemed</strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </section>


            <!-- REDEEM -->

            <section class="kla-parent-section">

                <div class="kla-section-title">

                    <span>
                        KUDOS MARKETPLACE
                    </span>

                    <h2>
                        Redeem Kudos
                    </h2>

                    <p>
                        Use Kudos to reduce the fee
                        of eligible academy activities.
                    </p>

                </div>


                <?php

                $activities =
                    get_posts(
                        array(

                            'post_type' =>
                                'kudos_activity',

                            'post_status' =>
                                'publish',

                            'posts_per_page' =>
                                -1,

                            'orderby' =>
                                'title',

                            'order' =>
                                'ASC',

                        )
                    );

                ?>


                <div class="kla-redemption-list">

                    <?php foreach (
                        $activities
                        as $activity
                    ) : ?>

                        <?php

                        $fee =
                            get_post_meta(
                                $activity->ID,
                                '_kudos_activity_fee',
                                true
                            );


                        $fee_type =
                            get_post_meta(
                                $activity->ID,
                                '_kudos_activity_fee_type',
                                true
                            );


                        /*
                         * Only fixed-fee
                         * activities.
                         */

                        if (
                            $fee_type ===
                            'activity_based'
                        ) {
                            continue;
                        }


                        if (
                            ! is_numeric(
                                $fee
                            ) ||
                            floatval(
                                $fee
                            ) <= 0
                        ) {
                            continue;
                        }

                        ?>

                        <div class="kla-activity-card">

                            <div>

                                <div class="kla-activity-icon">
                                    🎯
                                </div>

                                <div>

                                    <h3>
                                        <?php
                                        echo esc_html(
                                            $activity->post_title
                                        );
                                        ?>
                                    </h3>

                                    <p>
                                        Academy Activity
                                    </p>

                                    <strong>
                                        Fee:
                                        ₹<?php
                                        echo esc_html(
                                            number_format(
                                                $fee
                                            )
                                        );
                                        ?>
                                    </strong>

                                </div>

                            </div>


                            <?php foreach (
                                $children
                                as $child
                            ) : ?>

                                <?php

                                $child_name =
                                    get_post_meta(
                                        $child->ID,
                                        '_kudos_student_name',
                                        true
                                    );

                                if (
                                    ! $child_name
                                ) {

                                    $child_name =
                                        $child->post_title;
                                }


                                $balance =
                                    kla_get_kudos_balance(
                                        $child->ID
                                    );

                                /*
                                 * Calculate the current student-specific
                                 * payable fee for this activity from all
                                 * successful redemptions already made.
                                 * The activity's base fee remains unchanged.
                                 */
                                $activity_redemptions = get_posts(
                                    array(
                                        'post_type'      => 'kudos_redemption',
                                        'post_status'    => 'publish',
                                        'posts_per_page' => -1,
                                        'fields'         => 'ids',
                                        'meta_query'     => array(
                                            'relation' => 'AND',
                                            array(
                                                'key'   => '_kla_parent_id',
                                                'value' => $parent_id,
                                            ),
                                            array(
                                                'key'   => '_kla_student_id',
                                                'value' => $child->ID,
                                            ),
                                            array(
                                                'key'   => '_kla_activity_id',
                                                'value' => $activity->ID,
                                            ),
                                        ),
                                    )
                                );

                                $total_redeemed_points = 0;
                                $total_discount         = 0;

                                foreach ( $activity_redemptions as $redemption_post_id ) {
                                    $total_redeemed_points += absint(
                                        get_post_meta(
                                            $redemption_post_id,
                                            '_kla_points_used',
                                            true
                                        )
                                    );

                                    $total_discount += (float) get_post_meta(
                                        $redemption_post_id,
                                        '_kla_discount',
                                        true
                                    );
                                }

                                $current_payable_fee = max(
                                    0,
                                    (float) $fee - $total_discount
                                );

                                ?>

                                <form
                                    method="post"
                                    class="kla-redeem-form"
                                >

                                    <div>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $child_name
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            ⭐
                                            <?php
                                            echo esc_html(
                                                $balance
                                            );
                                            ?>
                                            Kudos
                                        </span>

                                        <?php if ( $total_redeemed_points > 0 ) : ?>
                                            <small class="kla-current-fee">
                                                Redeemed: -<?php echo esc_html( $total_redeemed_points ); ?> Kudos
                                                · Benefit: ₹<?php echo esc_html( number_format( $total_discount ) ); ?>
                                                · <strong>Payable: ₹<?php echo esc_html( number_format( $current_payable_fee ) ); ?></strong>
                                            </small>
                                        <?php endif; ?>

                                    </div>


                                    <input
                                        type="number"
                                        name="kla_points"
                                        min="1"
                                        max="<?php
                                        echo esc_attr(
                                            $balance
                                        );
                                        ?>"
                                        placeholder="Kudos to redeem"
                                        required
                                        <?php
                                        disabled(
                                            $balance <= 0,
                                            true
                                        );
                                        ?>
                                    />


                                    <input
                                        type="hidden"
                                        name="kla_student_id"
                                        value="<?php
                                        echo esc_attr(
                                            $child->ID
                                        );
                                        ?>"
                                    />


                                    <input
                                        type="hidden"
                                        name="kla_activity_id"
                                        value="<?php
                                        echo esc_attr(
                                            $activity->ID
                                        );
                                        ?>"
                                    />


                                    <?php

                                    wp_nonce_field(
                                        'kla_redeem_action',
                                        'kla_redeem_nonce'
                                    );

                                    ?>


                                    <button
                                        type="submit"
                                        <?php
                                        disabled(
                                            $balance <= 0,
                                            true
                                        );
                                        ?>
                                    >
                                        Redeem Kudos
                                    </button>

                                </form>

                            <?php endforeach; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>



            <!-- =================================================
                 KUDOS HISTORY
                 ================================================= -->

            <section class="kla-parent-section">

                <div class="kla-section-title">

                    <span>
                        KUDOS WALLET
                    </span>

                    <h2>
                        Kudos History
                    </h2>

                    <p>
                        See Kudos earned and redeemed for your children.
                    </p>

                </div>

                <?php
                foreach ( $children as $history_child ) :

                    $history_child_name = get_post_meta(
                        $history_child->ID,
                        '_kudos_student_name',
                        true
                    );

                    if ( ! $history_child_name ) {
                        $history_child_name = $history_child->post_title;
                    }

                    $history_balance = kla_get_kudos_balance(
                        $history_child->ID
                    );

                    $history_transactions = kla_get_student_kudos_history(
                        $history_child->ID,
                        10
                    );
                ?>

                    <div class="kla-wallet-card">

                        <div class="kla-wallet-header">

                            <div>
                                <span class="kla-wallet-label">
                                    Student
                                </span>

                                <h3>
                                    <?php echo esc_html( $history_child_name ); ?>
                                </h3>
                            </div>

                            <div class="kla-wallet-balance">
                                <span>
                                    Current Balance
                                </span>

                                <strong>
                                    ⭐ <?php echo esc_html( $history_balance ); ?>
                                </strong>
                            </div>

                        </div>

                        <?php if ( ! empty( $history_transactions ) ) : ?>

                            <div class="kla-wallet-transactions">

                                <?php foreach ( $history_transactions as $transaction ) : ?>

                                    <?php
                                    $transaction_type = get_post_meta(
                                        $transaction->ID,
                                        '_kla_transaction_type',
                                        true
                                    );

                                    $transaction_points = absint(
                                        get_post_meta(
                                            $transaction->ID,
                                            '_kla_transaction_points',
                                            true
                                        )
                                    );

                                    $transaction_reason = get_post_meta(
                                        $transaction->ID,
                                        '_kla_transaction_reason',
                                        true
                                    );

                                    $teacher_id = absint(
                                        get_post_meta(
                                            $transaction->ID,
                                            '_kla_transaction_teacher_id',
                                            true
                                        )
                                    );

                                    $teacher_name = '';

                                    if ( $teacher_id ) {
                                        $teacher = get_userdata( $teacher_id );

                                        if ( $teacher ) {
                                            $teacher_name = $teacher->display_name;
                                        }
                                    }

                                    $is_redemption =
                                        in_array(
                                            $transaction_type,
                                            array(
                                                'redemption',
                                                'redeem',
                                            ),
                                            true
                                        );

                                    $display_points =
                                        $is_redemption
                                            ? '-' . $transaction_points
                                            : '+' . $transaction_points;
                                    ?>

                                    <div class="kla-wallet-transaction">

                                        <div class="kla-wallet-transaction-icon">
                                            <?php echo $is_redemption ? '🎁' : '⭐'; ?>
                                        </div>

                                        <div class="kla-wallet-transaction-content">

                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    $transaction_reason
                                                        ? $transaction_reason
                                                        : ucfirst(
                                                            $transaction_type
                                                        )
                                                );
                                                ?>
                                            </strong>

                                            <span>
                                                <?php if ( $teacher_name ) : ?>
                                                    Awarded by
                                                    <?php echo esc_html( $teacher_name ); ?>
                                                    ·
                                                <?php endif; ?>

                                                <?php
                                                echo esc_html(
                                                    get_the_date(
                                                        'd M Y',
                                                        $transaction->ID
                                                    )
                                                );
                                                ?>
                                            </span>

                                        </div>

                                        <strong
                                            class="<?php
                                            echo $is_redemption
                                                ? 'kla-wallet-negative'
                                                : 'kla-wallet-positive';
                                            ?>"
                                        >
                                            <?php echo esc_html( $display_points ); ?>
                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else : ?>

                            <div class="kla-wallet-empty">
                                No Kudos transactions yet.
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </section>


            <!-- =================================================
                 REDEMPTION HISTORY
                 ================================================= -->

            <section class="kla-parent-section" id="redemption-history">

                <div class="kla-section-title">

                    <span>
                        REDEMPTIONS
                    </span>

                    <h2>
                        Redemption History
                    </h2>

                    <p>
                        Review activities redeemed using Kudos.
                    </p>

                </div>

                <?php
                $show_all_redemptions = isset( $_GET['kla_show_all_redemptions'] )
                    && '1' === sanitize_text_field( wp_unslash( $_GET['kla_show_all_redemptions'] ) );

                $redemption_history = kla_get_parent_redemption_history(
                    $parent_id,
                    $show_all_redemptions ? 100 : 5
                );
                ?>

                <div class="kla-redemption-history">

                    <?php if ( ! empty( $redemption_history ) ) : ?>

                        <?php foreach ( $redemption_history as $redemption ) : ?>

                            <?php
                            $redemption_student_id = absint(
                                get_post_meta(
                                    $redemption->ID,
                                    '_kla_student_id',
                                    true
                                )
                            );

                            $redemption_activity_id = absint(
                                get_post_meta(
                                    $redemption->ID,
                                    '_kla_activity_id',
                                    true
                                )
                            );

                            $redemption_points = absint(
                                get_post_meta(
                                    $redemption->ID,
                                    '_kla_points_used',
                                    true
                                )
                            );

                            $redemption_discount = get_post_meta(
                                $redemption->ID,
                                '_kla_discount',
                                true
                            );

                            $redemption_payable = get_post_meta(
                                $redemption->ID,
                                '_kla_payable_fee',
                                true
                            );

                            $redemption_student_name =
                                $redemption_student_id
                                    ? get_post_meta(
                                        $redemption_student_id,
                                        '_kudos_student_name',
                                        true
                                    )
                                    : '';

                            if ( ! $redemption_student_name ) {
                                $redemption_student_name =
                                    $redemption_student_id
                                        ? get_the_title(
                                            $redemption_student_id
                                        )
                                        : 'Student';
                            }

                            $redemption_activity_name =
                                $redemption_activity_id
                                    ? get_the_title(
                                        $redemption_activity_id
                                    )
                                    : 'Activity';
                            ?>

                            <div class="kla-redemption-history-row">

                                <div class="kla-redemption-history-icon">
                                    🎁
                                </div>

                                <div class="kla-redemption-history-content">

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $redemption_activity_name
                                        );
                                        ?>
                                    </strong>

                                    <span>
                                        <?php
                                        echo esc_html(
                                            $redemption_student_name
                                        );
                                        ?>
                                        ·
                                        <?php
                                        echo esc_html(
                                            get_the_date(
                                                'd M Y',
                                                $redemption->ID
                                            )
                                        );
                                        ?>
                                    </span>

                                </div>

                                <div class="kla-redemption-history-points">
                                    <strong>
                                        -<?php echo esc_html( $redemption_points ); ?>
                                        Kudos
                                    </strong>

                                    <span>
                                        ₹<?php
                                        echo esc_html(
                                            number_format(
                                                (float) $redemption_discount
                                            )
                                        );
                                        ?> benefit
                                    </span>

                                    <small>
                                        Payable:
                                        ₹<?php
                                        echo esc_html(
                                            number_format(
                                                (float) $redemption_payable
                                            )
                                        );
                                        ?>
                                    </small>
                                </div>

                            </div>

                        <?php endforeach; ?>

                        <?php if ( ! $show_all_redemptions && count( $redemption_history ) >= 5 ) : ?>

                            <div class="kla-redemption-history-action">
                                <a href="<?php echo esc_url( add_query_arg( 'kla_show_all_redemptions', '1', get_permalink() ) . '#rewards' ); ?>">
                                    View All History
                                </a>
                            </div>

                        <?php elseif ( $show_all_redemptions && count( $redemption_history ) >= 5 ) : ?>

                            <div class="kla-redemption-history-action">
                                <a href="<?php echo esc_url( remove_query_arg( 'kla_show_all_redemptions', get_permalink() ) . '#rewards' ); ?>">
                                    Show Latest 5
                                </a>
                            </div>

                        <?php endif; ?>

                    <?php else : ?>

                        <div class="kla-wallet-empty">
                            No redemptions yet.
                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- BENEFIT INFO -->

            <section class="kla-parent-section">

                <div class="kla-benefit-box">

                    <div>
                        💳
                    </div>

                    <div>

                        <h3>
                            How Kudos Redemption Works
                        </h3>

                        <p>
                            1 Kudos = ₹1 fee benefit.
                            Choose an eligible activity,
                            enter the number of Kudos you
                            want to redeem and the remaining
                            payable fee will be calculated
                            automatically.
                        </p>

                    </div>

                </div>

            </section>


        </main>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'kla_parent_dashboard',
    'kla_parent_dashboard_shortcode'
);


/* =========================================================
   PARENT PORTAL CSS
   ========================================================= */

function kla_parent_portal_css() {

    if (
        is_page( 'parent-login' ) ||
        is_page( 'parent-dashboard' )
    ) {

        ?>

        <style>


        /* =========================================================
           FULL-WIDTH WORDPRESS THEME OVERRIDE
           Prevent the academy portal from inheriting the theme's
           narrow page/content container.
           ========================================================= */

        body:has(.kla-parent-dashboard) {
            overflow-x: hidden;
        }

        body:has(.kla-parent-dashboard) .site-page,
        body:has(.kla-parent-dashboard) .page-content,
        body:has(.kla-parent-dashboard) .page-body,
        body:has(.kla-parent-dashboard) .page-main,
        body:has(.kla-parent-dashboard) .content-area,
        body:has(.kla-parent-dashboard) main.site-main {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
        }

        body:has(.kla-parent-dashboard) .container,
        body:has(.kla-parent-dashboard) .site-container {
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
        }

        body:has(.kla-parent-dashboard) .entry-content,
        body:has(.kla-parent-dashboard) .page-content {
            margin: 0 !important;
        }

        .kla-parent-dashboard {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .kla-parent-dashboard *,
        .kla-parent-dashboard *::before,
        .kla-parent-dashboard *::after {
            box-sizing: border-box;
        }

        /* Keep the actual portal content comfortably centered. */
        .kla-parent-dashboard .kla-parent-main {
            width: min(1180px, calc(100% - 48px)) !important;
            max-width: 1180px !important;
            margin: 0 auto !important;
        }

        .kla-parent-dashboard .kla-parent-header {
            width: 100% !important;
            padding-left: max(24px, calc((100% - 1180px) / 2)) !important;
            padding-right: max(24px, calc((100% - 1180px) / 2)) !important;
        }

        @media (max-width: 900px) {
            .kla-parent-dashboard .kla-parent-main {
                width: calc(100% - 32px) !important;
            }

            .kla-parent-dashboard .kla-parent-header {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
        }

        @media (max-width: 640px) {
            .kla-parent-dashboard .kla-parent-main {
                width: calc(100% - 20px) !important;
            }
        }

        .kla-parent-dashboard {
            min-height:100vh;
            background:#fffaf0;
            color:#30205a;
        }

        .kla-parent-header {
            background:#fff;
            border-bottom:1px solid #eee5d7;
            padding:18px 6%;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .kla-parent-logo,
        .kla-parent-user {
            display:flex;
            align-items:center;
            gap:12px;
        }

        .kla-parent-logo-icon {
            width:48px;
            height:48px;
            border-radius:15px;
            background:#eef6dd;
            display:grid;
            place-items:center;
            font-size:25px;
        }

        .kla-parent-logo strong {
            display:block;
            font-size:24px;
        }

        .kla-parent-logo span {
            display:block;
            color:#2d7b3c;
            font-size:12px;
            font-weight:800;
        }

        .kla-parent-user > div {
            display:flex;
            flex-direction:column;
        }

        .kla-parent-user span {
            color:#777;
            font-size:12px;
        }

        .kla-parent-user a {
            background:#2d7b3c;
            color:#fff !important;
            text-decoration:none;
            padding:10px 18px;
            border-radius:25px;
            font-weight:700;
        }

        .kla-parent-main {
            max-width:1120px;
            margin:auto;
            padding:55px 22px 80px;
        }

        .kla-parent-welcome span,
        .kla-section-title > span {
            color:#2d7b3c;
            font-weight:800;
            font-size:12px;
            letter-spacing:1px;
        }

        .kla-parent-welcome h1 {
            font-size:46px;
            margin:15px 0 8px;
        }

        .kla-parent-welcome p {
            color:#6f6a76;
            font-size:17px;
            margin:0;
        }

        .kla-parent-stats {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:18px;
            margin:40px 0 60px;
        }

        .kla-parent-stats > div {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:18px;
            padding:22px;
        }

        .kla-parent-stats span {
            display:block;
            color:#777;
            font-size:13px;
        }

        .kla-parent-stats strong {
            display:block;
            font-size:30px;
            margin-top:6px;
        }

        .kla-parent-section {
            margin-top:55px;
        }

        .kla-section-title {
            margin-bottom:22px;
        }

        .kla-section-title h2 {
            margin:6px 0;
            font-size:30px;
        }

        .kla-section-title p {
            color:#777;
            margin:0;
        }

        .kla-children-grid {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:18px;
        }

        .kla-child-card {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:20px;
            padding:22px;
        }

        .kla-child-icon {
            width:50px;
            height:50px;
            background:#eef6dd;
            border-radius:15px;
            display:grid;
            place-items:center;
            font-size:24px;
        }

        .kla-child-card h3 {
            margin:14px 0 4px;
        }

        .kla-child-card p {
            margin:0;
            color:#777;
        }

        .kla-child-kudos {
            margin-top:20px;
            background:#faf8f2;
            border-radius:12px;
            padding:12px;
        }

        .kla-child-kudos span {
            display:block;
            font-size:11px;
            color:#777;
        }

        .kla-child-kudos strong {
            display:block;
            font-size:22px;
            margin-top:3px;
        }

        .kla-performance-card {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:20px;
            padding:24px;
            margin-bottom:15px;
        }

        .kla-performance-card h3 {
            margin:0 0 20px;
        }

        .kla-performance-grid {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:12px;
        }

        .kla-performance-grid > div {
            background:#faf8f2;
            border-radius:12px;
            padding:14px;
        }

        .kla-performance-grid span {
            display:block;
            color:#777;
            font-size:12px;
        }

        .kla-performance-grid strong {
            display:block;
            margin-top:5px;
            font-size:20px;
        }

        .kla-redemption-list {
            display:flex;
            flex-direction:column;
            gap:18px;
        }

        .kla-activity-card {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:20px;
            padding:22px;
        }

        .kla-activity-card > div:first-child {
            display:flex;
            align-items:center;
            gap:14px;
            margin-bottom:20px;
        }

        .kla-activity-icon {
            width:52px;
            height:52px;
            background:#eef6dd;
            border-radius:15px;
            display:grid;
            place-items:center;
            font-size:25px;
        }

        .kla-activity-card h3 {
            margin:0;
        }

        .kla-activity-card p {
            margin:4px 0;
            color:#777;
            font-size:12px;
        }

        .kla-redeem-form {
            display:grid;
            grid-template-columns:1fr 170px 150px;
            align-items:center;
            gap:12px;
            background:#faf8f2;
            border-radius:13px;
            padding:12px;
            margin-top:9px;
        }

        .kla-redeem-form > div {
            display:flex;
            flex-direction:column;
        }

        .kla-redeem-form span {
            color:#777;
            font-size:12px;
            margin-top:3px;
        }

        .kla-redeem-form input[type="number"] {
            width:100%;
            box-sizing:border-box;
            padding:11px;
            border:1px solid #ddd;
            border-radius:9px;
            background:#fff;
        }

        .kla-redeem-form button {
            border:0;
            padding:11px 14px;
            border-radius:25px;
            background:#2d7b3c;
            color:#fff;
            font-weight:800;
            cursor:pointer;
        }

        .kla-redeem-form button:disabled {
            background:#bbb;
            cursor:not-allowed;
        }

        .kla-my-activities-list {
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .kla-my-activity-card {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:18px;
            padding:18px;
            display:flex;
            gap:15px;
            align-items:flex-start;
        }

        .kla-my-activity-icon {
            width:48px;
            height:48px;
            border-radius:14px;
            background:#eef6dd;
            display:grid;
            place-items:center;
            font-size:23px;
            flex:0 0 48px;
        }

        .kla-my-activity-main {
            flex:1;
            min-width:0;
        }

        .kla-my-activity-topline {
            display:flex;
            justify-content:space-between;
            gap:15px;
            align-items:flex-start;
        }

        .kla-my-activity-topline h3 {
            margin:0;
        }

        .kla-my-activity-topline p {
            margin:4px 0 0;
            color:#777;
            font-size:13px;
        }

        .kla-my-activity-status {
            background:#e8f6ea;
            color:#217a36;
            border-radius:20px;
            padding:5px 10px;
            font-size:10px;
            font-weight:800;
            letter-spacing:.4px;
            white-space:nowrap;
        }

        .kla-my-activity-details {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:10px;
            margin-top:14px;
        }

        .kla-my-activity-details > div {
            background:#faf8f2;
            border-radius:10px;
            padding:10px 12px;
        }

        .kla-my-activity-details span {
            display:block;
            color:#777;
            font-size:11px;
        }

        .kla-my-activity-details strong {
            display:block;
            margin-top:3px;
            font-size:15px;
        }

        .kla-my-activity-payment-details {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:10px;
            margin-top:10px;
        }

        .kla-my-activity-payment-details > div {
            background:#faf8f2;
            border-radius:10px;
            padding:10px 12px;
        }

        .kla-my-activity-payment-details span {
            display:block;
            color:#777;
            font-size:11px;
        }

        .kla-my-activity-payment-details strong {
            display:block;
            margin-top:3px;
            font-size:15px;
        }

        .kla-payment-paid { color:#2d7b3c; }
        .kla-payment-partial { color:#b36b00; }
        .kla-payment-unpaid { color:#c0392b; }

        @media (max-width: 900px) {
            .kla-my-activity-payment-details {
                grid-template-columns:repeat(2,1fr);
            }
        }

        @media (max-width: 640px) {
            .kla-my-activity-details,
            .kla-my-activity-payment-details {
                grid-template-columns:1fr 1fr;
            }
        }

        .kla-my-activities-empty {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:18px;
            padding:28px;
            text-align:center;
        }

        .kla-my-activities-empty-icon {
            font-size:34px;
            margin-bottom:8px;
        }

        .kla-my-activities-empty p {
            margin:5px 0 0;
            color:#777;
        }

        .kla-benefit-box {
            background:#eef6dd;
            border-radius:20px;
            padding:25px;
            display:flex;
            gap:18px;
            align-items:center;
        }

        .kla-benefit-box > div:first-child {
            font-size:35px;
        }

        .kla-benefit-box h3 {
            margin:0;
        }

        .kla-benefit-box p {
            color:#666;
            margin:6px 0 0;
        }

        .kla-parent-enrollment-list { display:flex; flex-direction:column; gap:14px; }
        .kla-enrollment-card { background:#fff; border:1px solid #eee5d7; border-radius:20px; padding:20px; }
        .kla-enrollment-card-top { display:flex; align-items:center; gap:14px; }
        .kla-enrollment-icon { width:48px; height:48px; border-radius:14px; background:#eef6dd; display:grid; place-items:center; font-size:23px; flex:0 0 48px; }
        .kla-enrollment-heading { flex:1; }
        .kla-enrollment-heading h3 { margin:0; color:#30205a; }
        .kla-enrollment-heading p { margin:4px 0 0; color:#777; font-size:13px; }
        .kla-enrollment-active { background:#eef6dd; color:#2d7b3c; border-radius:999px; padding:6px 10px; font-size:11px; font-weight:800; }
        .kla-enrollment-details { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-top:16px; }
        .kla-enrollment-details > div { background:#faf8f2; border-radius:12px; padding:11px; }
        .kla-enrollment-details span { display:block; color:#777; font-size:11px; }
        .kla-enrollment-details strong { display:block; margin-top:4px; color:#30205a; font-size:14px; }
        .kla-enrollment-payment-paid { color:#2d7b3c !important; }
        .kla-enrollment-payment-partial { color:#b26a00 !important; }
        .kla-enrollment-payment-unpaid { color:#c43b3b !important; }
        @media (max-width:1000px) { .kla-enrollment-details { grid-template-columns:repeat(3,1fr); } }
        @media (max-width:640px) { .kla-enrollment-card-top { align-items:flex-start; } .kla-enrollment-active { margin-left:auto; } .kla-enrollment-details { grid-template-columns:repeat(2,1fr); } }

        .kla-parent-message {
            max-width:600px;
            margin:60px auto;
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:20px;
            padding:40px;
            text-align:center;
        }

        .kla-parent-message a {
            display:inline-block;
            margin-top:15px;
            padding:12px 22px;
            background:#2d7b3c;
            color:#fff;
            text-decoration:none;
            border-radius:25px;
        }

        .kla-parent-login {
            min-height:70vh;
            background:#fffaf0;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:50px 20px;
        }

        .kla-parent-login-box {
            width:100%;
            max-width:450px;
            background:#fff;
            padding:40px;
            border-radius:24px;
            box-shadow:0 15px 45px rgba(48,32,90,.08);
            text-align:center;
            border:1px solid #eee5d7;
        }

        .kla-parent-login-icon {
            font-size:45px;
        }

        .kla-parent-brand strong {
            display:block;
            font-size:25px;
        }

        .kla-parent-brand span {
            color:#2d7b3c;
            font-size:12px;
            font-weight:800;
        }

        .kla-parent-login-box h1 {
            margin-top:25px;
        }

        #kla-parent-login-form {
            text-align:left;
        }

        #kla-parent-login-form input[type="text"],
        #kla-parent-login-form input[type="password"] {
            width:100%;
            box-sizing:border-box;
            padding:12px;
            border:1px solid #ddd;
            border-radius:9px;
        }

        #kla-parent-login-form input[type="submit"] {
            width:100%;
            border:0;
            padding:13px;
            border-radius:25px;
            background:#2d7b3c;
            color:#fff;
            font-weight:800;
            cursor:pointer;
        }

        @media(max-width:850px) {

            .kla-children-grid {
                grid-template-columns:1fr 1fr;
            }

            .kla-parent-stats {
                grid-template-columns:1fr;
            }

        }

        @media(max-width:650px) {

            .kla-parent-header {
                padding:15px;
            }

            .kla-parent-user > div {
                display:none;
            }

            .kla-parent-main {
                padding:35px 16px 60px;
            }

            .kla-parent-welcome h1 {
                font-size:35px;
            }

            .kla-children-grid,
            .kla-performance-grid {
                grid-template-columns:1fr;
            }

            .kla-current-fee {
                display: block;
                margin-top: 4px;
                color: #2d7b3c;
                font-size: 12px;
                line-height: 1.45;
            }

            .kla-my-activity-details {
                grid-template-columns:1fr 1fr;
            }

            .kla-my-activity-topline {
                flex-direction:column;
            }

            .kla-redeem-form {
                grid-template-columns:1fr;
            }

            .kla-redeem-form input,
            .kla-redeem-form button {
                width:100%;
            }

            .kla-parent-login-box {
                padding:28px 20px;
            }

        }


        /* =================================================
           WALLET + HISTORY
           ================================================= */

        .kla-wallet-card,
        .kla-redemption-history {
            background:#fff;
            border:1px solid #eee5d7;
            border-radius:20px;
            overflow:hidden;
        }

        .kla-wallet-card {
            margin-bottom:18px;
        }

        .kla-wallet-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            padding:22px;
            background:#faf8f2;
            border-bottom:1px solid #eee5d7;
        }

        .kla-wallet-label {
            display:block;
            color:#777;
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.8px;
            font-weight:800;
        }

        .kla-wallet-header h3 {
            margin:5px 0 0;
            font-size:20px;
        }

        .kla-wallet-balance {
            text-align:right;
        }

        .kla-wallet-balance span {
            display:block;
            color:#777;
            font-size:11px;
        }

        .kla-wallet-balance strong {
            display:block;
            margin-top:4px;
            color:#2d7b3c;
            font-size:22px;
        }

        .kla-wallet-transactions {
            display:flex;
            flex-direction:column;
        }

        .kla-wallet-transaction {
            display:grid;
            grid-template-columns:42px 1fr auto;
            align-items:center;
            gap:13px;
            padding:16px 20px;
            border-bottom:1px solid #f0ece5;
        }

        .kla-wallet-transaction:last-child {
            border-bottom:0;
        }

        .kla-wallet-transaction-icon {
            width:42px;
            height:42px;
            border-radius:12px;
            background:#eef6dd;
            display:grid;
            place-items:center;
        }

        .kla-wallet-transaction-content strong {
            display:block;
            color:#30205a;
            font-size:14px;
        }

        .kla-wallet-transaction-content span {
            display:block;
            margin-top:4px;
            color:#777;
            font-size:12px;
        }

        .kla-wallet-positive {
            color:#2d7b3c;
            font-size:16px;
        }

        .kla-wallet-negative {
            color:#a33b3b;
            font-size:16px;
        }

        .kla-wallet-empty {
            padding:25px;
            text-align:center;
            color:#777;
            font-size:13px;
        }

        .kla-redemption-history-row {
            display:grid;
            grid-template-columns:46px 1fr auto;
            align-items:center;
            gap:14px;
            padding:18px 20px;
            border-bottom:1px solid #f0ece5;
        }

        .kla-redemption-history-row:last-child {
            border-bottom:0;
        }

        .kla-redemption-history-icon {
            width:46px;
            height:46px;
            border-radius:13px;
            background:#eef6dd;
            display:grid;
            place-items:center;
            font-size:21px;
        }

        .kla-redemption-history-content strong {
            display:block;
            color:#30205a;
            font-size:14px;
        }

        .kla-redemption-history-content span {
            display:block;
            margin-top:4px;
            color:#777;
            font-size:12px;
        }

        .kla-redemption-history-points {
            text-align:right;
        }

        .kla-redemption-history-points strong {
            display:block;
            color:#a33b3b;
            font-size:14px;
        }

        .kla-redemption-history-points span,
        .kla-redemption-history-points small {
            display:block;
            margin-top:3px;
            color:#777;
            font-size:11px;
        }

        .kla-redemption-history-action {
            padding:14px 18px;
            text-align:center;
            border-top:1px solid #eee5d7;
            background:#fffaf0;
        }

        .kla-redemption-history-action a {
            color:#2d7b3c;
            font-size:13px;
            font-weight:800;
            text-decoration:none;
        }

        .kla-redemption-history-action a:hover {
            text-decoration:underline;
        }

        @media(max-width:650px) {

            .kla-wallet-header {
                align-items:flex-start;
                flex-direction:column;
            }

            .kla-wallet-balance {
                text-align:left;
            }

            .kla-wallet-transaction {
                grid-template-columns:42px 1fr auto;
                padding:14px;
            }

            .kla-redemption-history-row {
                grid-template-columns:46px 1fr;
                padding:15px;
            }

            .kla-redemption-history-points {
                grid-column:2;
                text-align:left;
            }

        }
/* =========================================================
   PARENT DASHBOARD - FULL WIDTH CONTENT FIX
   ========================================================= */

.kla-parent-dashboard .kla-parent-main {
    width: calc(100vw - 48px) !important;
    max-width: 1240px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

.kla-parent-dashboard .kla-parent-portal-layout {
    width: 100% !important;
    max-width: 100% !important;
    display: grid !important;
    grid-template-columns: 220px minmax(0, 1fr) !important;
    gap: 28px !important;
    align-items: start !important;
}

.kla-parent-dashboard .kla-parent-portal-content {
    width: 100% !important;
    min-width: 0 !important;
}

.kla-parent-dashboard .kla-parent-portal-content .kla-parent-section {
    width: 100% !important;
    max-width: none !important;
}

.kla-parent-dashboard .kla-redemption-list {
    width: 100% !important;
}

.kla-parent-dashboard .kla-activity-card {
    width: 100% !important;
    max-width: none !important;
}

.kla-parent-dashboard .kla-redeem-form {
    width: 100% !important;
}

@media (max-width: 900px) {

    .kla-parent-dashboard .kla-parent-main {
        width: calc(100vw - 32px) !important;
        max-width: none !important;
    }

    .kla-parent-dashboard .kla-parent-portal-layout {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 640px) {

    .kla-parent-dashboard .kla-parent-main {
        width: calc(100vw - 20px) !important;
    }

    .kla-parent-dashboard .kla-redeem-form {
        grid-template-columns: 1fr !important;
    }
}

        </style>

        <?php
    }
}

add_action(
    'wp_head',
    'kla_parent_portal_css'
);

/* =========================================================
   PARENT PORTAL - TAB/SIDEBAR LAYOUT
   Keeps the existing dashboard data and forms, but presents
   them as a compact portal instead of one long scrolling page.
   ========================================================= */
function kla_parent_portal_layout_css() {

    if ( ! is_page( 'parent-dashboard' ) ) {
        return;
    }
    ?>
    <style>
        .kla-parent-dashboard .kla-parent-main {
            max-width: 1240px;
            padding: 32px 22px 60px;
        }

        .kla-parent-portal-layout {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 28px;
            align-items: start;
        }

        .kla-parent-sidebar {
            position: sticky;
            top: 18px;
            background: #fff;
            border: 1px solid #eee5d7;
            border-radius: 22px;
            padding: 14px;
            box-shadow: 0 10px 30px rgba(48, 32, 90, .06);
            z-index: 5;
        }

        .kla-parent-sidebar-title {
            padding: 12px 12px 10px;
            color: #777;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .kla-parent-tab {
            width: 100%;
            border: 0;
            background: transparent;
            color: #5b5668;
            padding: 12px 13px;
            border-radius: 13px;
            text-align: left;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: .2s ease;
        }

        .kla-parent-tab:hover {
            background: #f4f8ea;
            color: #2d7b3c;
        }

        .kla-parent-tab.is-active {
            background: #2d7b3c;
            color: #fff;
            box-shadow: 0 8px 18px rgba(45,123,60,.18);
        }

        .kla-parent-tab-icon {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: rgba(255,255,255,.55);
            flex: 0 0 28px;
        }

        .kla-parent-tab.is-active .kla-parent-tab-icon {
            background: rgba(255,255,255,.18);
        }

        .kla-parent-portal-content {
            min-width: 0;
        }

        .kla-parent-tab-section-hidden {
            display: none !important;
        }

        .kla-parent-portal-content .kla-parent-section {
            margin-top: 32px;
        }

        .kla-parent-portal-content .kla-parent-welcome {
            margin-top: 0;
        }

        .kla-parent-portal-content .kla-parent-stats {
            margin: 28px 0 0;
        }

        .kla-parent-tab-note {
            margin-top: 28px;
            background: #fff;
            border: 1px solid #eee5d7;
            border-radius: 20px;
            padding: 30px;
            color: #6f6a76;
        }

        .kla-parent-tab-note strong {
            display: block;
            color: #30205a;
            font-size: 18px;
            margin-bottom: 6px;
        }

        @media (max-width: 900px) {
            .kla-parent-portal-layout {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .kla-parent-sidebar {
                position: sticky;
                top: 8px;
                display: flex;
                gap: 6px;
                overflow-x: auto;
                padding: 8px;
                border-radius: 16px;
                scrollbar-width: thin;
            }

            .kla-parent-sidebar-title {
                display: none;
            }

            .kla-parent-tab {
                width: auto;
                min-width: max-content;
                padding: 9px 12px;
                white-space: nowrap;
                font-size: 13px;
            }

            .kla-parent-tab-icon {
                width: 24px;
                height: 24px;
                flex-basis: 24px;
            }
        }

        @media (max-width: 640px) {
            .kla-parent-dashboard .kla-parent-main {
                padding: 18px 12px 40px;
            }

            .kla-parent-welcome h1 {
                font-size: 34px;
            }

            .kla-parent-stats {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }

            .kla-parent-portal-content .kla-parent-section {
                margin-top: 22px;
            }
        }
    </style>
    <?php
}

add_action(
    'wp_head',
    'kla_parent_portal_layout_css',
    30
);

function kla_parent_portal_layout_script() {

    if ( ! is_page( 'parent-dashboard' ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const main = document.querySelector('.kla-parent-dashboard .kla-parent-main');
        if (!main) return;

        const sections = Array.from(main.children).filter(function (el) {
            return el.tagName === 'SECTION';
        });

        if (!sections.length) return;

        /* Current dashboard order:
           0 Welcome
           1 Stats
           2 My Children
           3 Performance
           4 Enrolled Activities
           5 Redeem Kudos
           6 Kudos History
           7 Redemption History
           8 Benefit info
        */
        const tabs = [
            { key: 'overview', label: 'Overview', icon: '🏠' },
            { key: 'activities', label: 'Enrolled Activities', icon: '🎯' },
            { key: 'performance', label: 'Performance', icon: '📊' },
            { key: 'kudos', label: 'Kudos Wallet', icon: '⭐' },
            { key: 'rewards', label: 'Rewards', icon: '🎁' }
        ];

        const layout = document.createElement('div');
        layout.className = 'kla-parent-portal-layout';

        const sidebar = document.createElement('aside');
        sidebar.className = 'kla-parent-sidebar';
        sidebar.setAttribute('aria-label', 'Parent portal navigation');

        const sidebarTitle = document.createElement('div');
        sidebarTitle.className = 'kla-parent-sidebar-title';
        sidebarTitle.textContent = 'Parent Portal';
        sidebar.appendChild(sidebarTitle);

        const content = document.createElement('div');
        content.className = 'kla-parent-portal-content';

        sections.forEach(function (section) {
            content.appendChild(section);
        });

        layout.appendChild(sidebar);
        layout.appendChild(content);
        main.appendChild(layout);

        function showTab(key) {
            sections.forEach(function (section, index) {
                let visible = false;

                if (key === 'overview') {
                    visible = [0, 1, 2].includes(index);
                } else if (key === 'activities') {
                    visible = index === 4;
                } else if (key === 'performance') {
                    visible = index === 3;
                } else if (key === 'kudos') {
                    visible = index === 6;
                } else if (key === 'rewards') {
                    visible = [5, 7, 8].includes(index);
                }

                section.classList.toggle('kla-parent-tab-section-hidden', !visible);
            });

            sidebar.querySelectorAll('.kla-parent-tab').forEach(function (button) {
                const active = button.dataset.tab === key;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        tabs.forEach(function (tab) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'kla-parent-tab';
            button.dataset.tab = tab.key;
            button.setAttribute('aria-selected', 'false');

            const icon = document.createElement('span');
            icon.className = 'kla-parent-tab-icon';
            icon.textContent = tab.icon;

            const label = document.createElement('span');
            label.textContent = tab.label;

            button.appendChild(icon);
            button.appendChild(label);

            button.addEventListener('click', function () {
                showTab(tab.key);
                history.replaceState(null, '', '#' + tab.key);
            });

            sidebar.appendChild(button);
        });

        const initialTab = window.location.hash.replace('#', '');
        const requestedTab = initialTab === 'redemption-history' ? 'rewards' : initialTab;
        const activeTab = tabs.some(function (tab) { return tab.key === requestedTab; }) ? requestedTab : 'overview';
        showTab(activeTab);

        if (new URLSearchParams(window.location.search).get('kla_show_all_redemptions') === '1') {
            const redemptionSection = document.getElementById('redemption-history');
            if (redemptionSection) {
                setTimeout(function () {
                    redemptionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    });
    </script>
    <?php
}

add_action(
    'wp_footer',
    'kla_parent_portal_layout_script',
    30
);

/* =========================================================
   PARENT USER ACCOUNT LINKING
   ========================================================= */

function kla_parent_user_link_meta_box() {

    add_meta_box(
        'kla_parent_user_account',
        'Parent Login Account',
        'kla_parent_user_account_callback',
        'kudos_parent',
        'side',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kla_parent_user_link_meta_box'
);


/*
 * Parent Login Account Box
 */
function kla_parent_user_account_callback($post) {

    $linked_user_id = get_post_meta(
        $post->ID,
        '_kla_parent_user_id',
        true
    );

    echo '<div style="padding:5px 0;">';

    if ($linked_user_id) {

        $user = get_userdata($linked_user_id);

        if ($user) {

            echo '<p>';
            echo '<strong>Status:</strong> ';
            echo '<span style="color:#22863a;">Linked</span>';
            echo '</p>';

            echo '<p>';
            echo '<strong>Username:</strong><br>';
            echo esc_html($user->user_login);
            echo '</p>';

            echo '<p>';
            echo '<strong>Email:</strong><br>';
            echo esc_html($user->user_email);
            echo '</p>';

            echo '<p>';
            echo '<a href="' .
                esc_url(
                    get_edit_user_link($user->ID)
                ) .
                '" class="button">';
            echo 'Edit Login Account';
            echo '</a>';
            echo '</p>';

            echo '<p style="color:#666;font-size:12px;">';
            echo 'This parent profile is already linked to a WordPress login account.';
            echo '</p>';

        } else {

            delete_post_meta(
                $post->ID,
                '_kla_parent_user_id'
            );

            echo '<p style="color:#b32d2e;">';
            echo 'Linked user no longer exists.';
            echo '</p>';
        }

    } else {

        echo '<p>';
        echo '<strong>No login account linked.</strong>';
        echo '</p>';

        /*
         * Find available Kudos Parent users
         */
        $parent_users = get_users(
            array(
                'role'   => 'kudos_parent',
                'orderby'=> 'display_name',
                'order'  => 'ASC',
            )
        );

        if (empty($parent_users)) {

            echo '<p style="color:#b32d2e;">';
            echo 'No Kudos Parent users found.';
            echo '</p>';

            echo '<p>';
            echo 'Create a WordPress user with the role ';
            echo '<strong>Kudos Parent</strong>';
            echo ' first.';
            echo '</p>';

        } else {

            echo '<p>';
            echo '<label><strong>Select Parent Login</strong></label>';
            echo '</p>';

            echo '<select ';
            echo 'name="kla_parent_user_id" ';
            echo 'style="width:100%;"';
            echo '>';

            echo '<option value="">Select User</option>';

            foreach ($parent_users as $user) {

                echo '<option value="' .
                    esc_attr($user->ID) .
                    '">';

                echo esc_html(
                    $user->display_name
                );

                echo ' — ';

                echo esc_html(
                    $user->user_login
                );

                echo '</option>';
            }

            echo '</select>';

            echo '<p style="margin-top:10px;">';
            echo '<strong>Important:</strong> ';
            echo 'Select the WordPress account belonging to this parent.';
            echo '</p>';
        }
    }

    echo '</div>';
}


/*
 * Save Parent ↔ WordPress User Link
 */
function kla_save_parent_user_link($post_id) {

    if (
        !isset($_POST['kla_parent_user_id'])
    ) {
        return;
    }

    if (
        defined('DOING_AUTOSAVE') &&
        DOING_AUTOSAVE
    ) {
        return;
    }

    if (
        get_post_type($post_id) !== 'kudos_parent'
    ) {
        return;
    }

    if (
        !current_user_can(
            'edit_post',
            $post_id
        )
    ) {
        return;
    }

    $user_id = absint(
        $_POST['kla_parent_user_id']
    );

    if (!$user_id) {

        delete_post_meta(
            $post_id,
            '_kla_parent_user_id'
        );

        return;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    /*
     * Only allow Kudos Parent users
     */
    if (
        !in_array(
            'kudos_parent',
            (array) $user->roles,
            true
        )
    ) {
        return;
    }

    /*
     * Save link on Parent record
     */
    update_post_meta(
        $post_id,
        '_kla_parent_user_id',
        $user_id
    );

    /*
     * Also save reverse link on WordPress user
     */
    update_user_meta(
        $user_id,
        '_kla_parent_record_id',
        $post_id
    );
}

add_action(
    'save_post_kudos_parent',
    'kla_save_parent_user_link'
);

/* =========================================================
   IMPORT CONFIRMED PARENT + STUDENT RELATIONSHIPS
   ========================================================= */

function kla_import_confirmed_parent_data() {

    /*
     * New import flag.
     * This allows this confirmed-data import to run once.
     */
    if ( get_option( 'kla_confirmed_parent_data_imported' ) ) {
        return;
    }


    /*
     * CONFIRMED PARENT DATA
     *
     * One parent record can have multiple children.
     */

    $parent_data = array(

        array(
            'name'     => 'Shitali',
            'mobile'   => '9999355572',
            'email'    => '',
            'children' => array(
                'Hitarsh',
            ),
        ),

        array(
            'name'     => 'Kamal',
            'mobile'   => '7206526549',
            'email'    => '',
            'children' => array(
                'Saisha',
            ),
        ),

        array(
            'name'     => 'Ruby',
            'mobile'   => '9999560693',
            'email'    => '',
            'children' => array(
                'Rishit',
                'Sanvi',
            ),
        ),

        array(
            'name'     => 'Chaya',
            'mobile'   => '9711333480',
            'email'    => '',
            'children' => array(
                'Saeysha',
            ),
        ),

        array(
            'name'     => 'Govind',
            'mobile'   => '9999972821',
            'email'    => '',
            'children' => array(
                'Rishika',
            ),
        ),

        array(
            'name'     => 'Garima',
            'mobile'   => '9711431900',
            'email'    => '',
            'children' => array(
                'Vayodant',
            ),
        ),

        array(
            'name'     => 'Bharti',
            'mobile'   => '8527411741',
            'email'    => '',
            'children' => array(
                'Lakshita',
                'Harshita',
                'Pari',
                'Adharav',
            ),
        ),

        array(
            'name'     => 'Neha',
            'mobile'   => '9310441281',
            'email'    => '',
            'children' => array(
                'Amayra',
            ),
        ),

        array(
            'name'     => 'Seema',
            'mobile'   => '9999274053',
            'email'    => '',
            'children' => array(
                'Nihaal',
            ),
        ),

        array(
            'name'     => 'Charu',
            'mobile'   => '9871501096',
            'email'    => '',
            'children' => array(
                'Samriddhi',
                'Ojasvi',
            ),
        ),

        array(
            'name'     => 'Aabha',
            'mobile'   => '9910358319',
            'email'    => '',
            'children' => array(
                'Miraya',
            ),
        ),

        array(
            'name'     => 'Aasha',
            'mobile'   => '9891400687',
            'email'    => '',
            'children' => array(
                'Priyansh',
            ),
        ),

        array(
            'name'     => 'Megha Sharma',
            'mobile'   => '9810415624',
            'email'    => '',
            'children' => array(
                'Vernicia',
            ),
        ),

    );


    /*
     * =====================================================
     * CREATE PARENTS AND LINK CHILDREN
     * =====================================================
     */

    foreach ( $parent_data as $parent ) {


        /*
         * Find existing Parent by mobile number.
         *
         * This prevents duplicate parent records.
         */

        $existing_parent = get_posts(
            array(
                'post_type'      => 'kudos_parent',
                'post_status'    => 'any',
                'posts_per_page' => 1,

                'meta_query' => array(
                    array(
                        'key'     => '_kudos_parent_mobile',
                        'value'   => $parent['mobile'],
                        'compare' => '=',
                    ),
                ),
            )
        );


        /*
         * Existing parent found
         */
        if ( ! empty( $existing_parent ) ) {

            $parent_id = $existing_parent[0]->ID;

        }

        /*
         * Otherwise create new Parent
         */
        else {

            $parent_id = wp_insert_post(
                array(
                    'post_title'  => $parent['name'],
                    'post_status' => 'publish',
                    'post_type'   => 'kudos_parent',
                ),
                true
            );


            if ( is_wp_error( $parent_id ) ) {
                continue;
            }


            /*
             * Save Parent details
             */

            update_post_meta(
                $parent_id,
                '_kudos_parent_name',
                $parent['name']
            );

            update_post_meta(
                $parent_id,
                '_kudos_parent_mobile',
                $parent['mobile']
            );

            update_post_meta(
                $parent_id,
                '_kudos_parent_email',
                $parent['email']
            );
        }


        /*
         * =================================================
         * LINK CHILDREN TO THIS PARENT
         * =================================================
         */

        foreach ( $parent['children'] as $child_name ) {


            /*
             * First try exact student name.
             */

            $students = get_posts(
                array(
                    'post_type'      => 'kudos_student',
                    'post_status'    => 'any',
                    'posts_per_page' => 1,

                    'meta_query' => array(
                        array(
                            'key'     => '_kudos_student_name',
                            'value'   => $child_name,
                            'compare' => '=',
                        ),
                    ),
                )
            );


            /*
             * If exact name is not found,
             * try the older names already used in the system.
             *
             * This prevents duplicate Student records.
             */

            if ( empty( $students ) ) {

                $name_aliases = array();


                if ( $child_name === 'Amayra' ) {

                    $name_aliases = array(
                        'Anayara',
                    );
                }


                if ( $child_name === 'Samriddhi' ) {

                    $name_aliases = array(
                        'Samridhi',
                    );
                }


                if ( $child_name === 'Priyansh' ) {

                    $name_aliases = array(
                        'Priyankh',
                    );
                }


                if ( $child_name === 'Vernicia' ) {

                    $name_aliases = array(
                        'Vernica',
                    );
                }


                foreach ( $name_aliases as $alias ) {

                    $students = get_posts(
                        array(
                            'post_type'      => 'kudos_student',
                            'post_status'    => 'any',
                            'posts_per_page' => 1,

                            'meta_query' => array(
                                array(
                                    'key'     => '_kudos_student_name',
                                    'value'   => $alias,
                                    'compare' => '=',
                                ),
                            ),
                        )
                    );


                    if ( ! empty( $students ) ) {
                        break;
                    }
                }
            }


            /*
             * Student found
             */
            if ( ! empty( $students ) ) {

                $student_id = $students[0]->ID;


                /*
                 * Link Student → Parent
                 */

                update_post_meta(
                    $student_id,
                    '_kudos_student_parent',
                    $parent_id
                );
            }
        }
    }


    /*
     * Mark import completed
     */

    update_option(
        'kla_confirmed_parent_data_imported',
        1
    );
}


/*
 * Run once when WordPress Admin loads.
 */
add_action(
    'admin_init',
    'kla_import_confirmed_parent_data'
);



/*
 * Add Parent Login Account meta box
 */
function kla_parent_login_meta_box() {

    add_meta_box(
        'kla_parent_login_account',
        'Parent Login Account',
        'kla_parent_login_meta_box_callback',
        'kudos_parent',
        'side',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kla_parent_login_meta_box'
);


/*
 * Display Login Account section
 */
function kla_parent_login_meta_box_callback( $post ) {

    $user_id = get_post_meta(
        $post->ID,
        '_kla_parent_user_id',
        true
    );

    echo '<div style="padding:5px 0;">';


    /*
     * Existing account
     */
    if ( $user_id ) {

        $user = get_userdata( $user_id );


        if ( $user ) {

            echo '<p style="margin-top:0;">';
            echo '<strong style="color:#22863a;">✓ Login Account Created</strong>';
            echo '</p>';

            echo '<p>';
            echo '<strong>Username:</strong><br>';
            echo esc_html( $user->user_login );
            echo '</p>';

            echo '<p>';
            echo '<strong>Email:</strong><br>';

            echo $user->user_email
                ? esc_html( $user->user_email )
                : '<span style="color:#999;">Not set</span>';

            echo '</p>';

            echo '<p>';
            echo '<a class="button" href="' .
                esc_url(
                    get_edit_user_link( $user->ID )
                ) .
                '">';
            echo 'Edit User';
            echo '</a>';
            echo '</p>';

            echo '<p style="font-size:12px;color:#666;">';
            echo 'This WordPress user is linked to this Parent profile.';
            echo '</p>';

        } else {

            /*
             * User was deleted.
             * Remove broken link.
             */

            delete_post_meta(
                $post->ID,
                '_kla_parent_user_id'
            );

            echo '<p style="color:#b32d2e;">';
            echo 'Previous login account no longer exists.';
            echo '</p>';
        }


    } else {


        /*
         * No account yet
         */

        echo '<p>';
        echo '<strong>No login account created.</strong>';
        echo '</p>';

        echo '<p style="font-size:12px;color:#666;">';
        echo 'Save this Parent first, then use the button below to create the login account.';
        echo '</p>';

        echo '<button ';
        echo 'type="button" ';
        echo 'class="button button-primary" ';
        echo 'name="kla_create_parent_login" ';
        echo 'value="1" ';
        echo 'onclick="this.form.submit();">';
        echo 'Create Login Account';
        echo '</button>';

    }

    echo '</div>';
}


/*
 * Create Parent Login Account
 */
function kla_create_parent_login_account( $post_id ) {

    /*
     * Only for Parent CPT
     */
    if (
        get_post_type( $post_id ) !== 'kudos_parent'
    ) {
        return;
    }


    /*
     * Don't run during autosave
     */
    if (
        defined( 'DOING_AUTOSAVE' ) &&
        DOING_AUTOSAVE
    ) {
        return;
    }


    /*
     * Only admin users can create accounts
     */
    if (
        ! current_user_can( 'manage_options' )
    ) {
        return;
    }


    /*
     * Check button
     */
    if (
        empty( $_POST['kla_create_parent_login'] )
    ) {
        return;
    }


    /*
     * Get Parent details
     */

    $parent_name = get_post_meta(
        $post_id,
        '_kudos_parent_name',
        true
    );

    $mobile = get_post_meta(
        $post_id,
        '_kudos_parent_mobile',
        true
    );

    $email = get_post_meta(
        $post_id,
        '_kudos_parent_email',
        true
    );


    /*
     * Mobile is required
     */

    if ( empty( $mobile ) ) {

        add_filter(
            'redirect_post_location',
            function( $location ) {

                return add_query_arg(
                    'kla_parent_error',
                    'missing_mobile',
                    $location
                );
            }
        );

        return;
    }


    /*
     * Clean mobile number
     *
     * Example:
     * +91 99993 55572
     * becomes:
     * 919999355572
     */

    $username = preg_replace(
        '/[^0-9]/',
        '',
        $mobile
    );


    /*
     * If username already exists,
     * use existing WordPress user.
     */

    $existing_user = get_user_by(
        'login',
        $username
    );


    if ( $existing_user ) {

        /*
         * Make sure existing user is a parent
         */

        if (
            ! in_array(
                'kudos_parent',
                (array) $existing_user->roles,
                true
            )
        ) {

            add_filter(
                'redirect_post_location',
                function( $location ) {

                    return add_query_arg(
                        'kla_parent_error',
                        'username_exists',
                        $location
                    );
                }
            );

            return;
        }


        /*
         * Link existing user
         */

        update_post_meta(
            $post_id,
            '_kla_parent_user_id',
            $existing_user->ID
        );

        update_user_meta(
            $existing_user->ID,
            '_kla_parent_record_id',
            $post_id
        );


        return;
    }


    /*
     * Create new password
     *
     * WordPress securely stores the password hash.
     */

    $password = wp_generate_password(
        12,
        true,
        true
    );


    /*
     * Prepare email
     */

    if ( ! empty( $email ) ) {

        $user_email = sanitize_email(
            $email
        );

    } else {

        /*
         * Local development fallback.
         *
         * WordPress requires an email.
         */

        $user_email =
            $username .
            '@kudos.local';
    }


    /*
     * Create WordPress user
     */

    $new_user_id = wp_create_user(
        $username,
        $password,
        $user_email
    );


    if (
        is_wp_error( $new_user_id )
    ) {

        add_filter(
            'redirect_post_location',
            function( $location ) {

                return add_query_arg(
                    'kla_parent_error',
                    'user_creation_failed',
                    $location
                );
            }
        );

        return;
    }


    /*
     * Assign Parent role
     */

    $new_user = new WP_User(
        $new_user_id
    );

    $new_user->set_role(
        'kudos_parent'
    );


    /*
     * Set display name
     */

    if ( ! empty( $parent_name ) ) {

        wp_update_user(
            array(
                'ID'           => $new_user_id,
                'display_name' => $parent_name,
                'first_name'   => $parent_name,
            )
        );
    }


    /*
     * Link User → Parent
     */

    update_post_meta(
        $post_id,
        '_kla_parent_user_id',
        $new_user_id
    );


    /*
     * Link Parent → User
     */

    update_user_meta(
        $new_user_id,
        '_kla_parent_record_id',
        $post_id
    );


    /*
     * Store generated password temporarily
     * for admin display only.
     *
     * This is NOT used for authentication.
     */

    update_post_meta(
        $post_id,
        '_kla_initial_login_password',
        $password
    );


    /*
     * Store username
     */

    update_post_meta(
        $post_id,
        '_kla_login_username',
        $username
    );


    /*
     * Redirect after creation
     */

    add_filter(
        'redirect_post_location',
        function( $location ) {

            return add_query_arg(
                'kla_parent_created',
                '1',
                $location
            );
        }
    );
}

add_action(
    'save_post_kudos_parent',
    'kla_create_parent_login_account',
    20
);


/*
 * Show success/error messages
 */
function kla_parent_login_admin_notice() {

    if (
        isset( $_GET['kla_parent_created'] )
    ) {

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>';
        echo '<strong>Parent login account created successfully.</strong>';
        echo '</p>';
        echo '</div>';
    }


    if (
        isset( $_GET['kla_parent_error'] )
    ) {

        $error = sanitize_text_field(
            $_GET['kla_parent_error']
        );

        $message = 'Unable to create parent login account.';


        if (
            $error === 'missing_mobile'
        ) {

            $message =
                'Parent mobile number is required before creating a login account.';
        }


        if (
            $error === 'username_exists'
        ) {

            $message =
                'This mobile number already belongs to a non-parent WordPress user.';
        }


        if (
            $error === 'user_creation_failed'
        ) {

            $message =
                'WordPress could not create the user account.';
        }


        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>';
        echo esc_html( $message );
        echo '</p>';
        echo '</div>';
    }
}

add_action(
    'admin_notices',
    'kla_parent_login_admin_notice'
);
