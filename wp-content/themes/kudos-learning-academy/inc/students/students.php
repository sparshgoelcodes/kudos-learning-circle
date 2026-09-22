<?php

/* =========================================================
   STUDENTS
   ========================================================= */

function kudos_register_students() {

    register_post_type('kudos_student', array(

        'labels' => array(
            'name'               => 'Students',
            'singular_name'      => 'Student',
            'menu_name'          => 'Students',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Student',
            'edit_item'          => 'Edit Student',
            'new_item'           => 'New Student',
            'view_item'          => 'View Student',
            'search_items'       => 'Search Students',
            'not_found'          => 'No students found',
            'not_found_in_trash' => 'No students found in Trash',
        ),

        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_admin_bar' => true,

        'menu_position'     => 27,
        'menu_icon'         => 'dashicons-id',

        'capability_type'   => 'post',
        'map_meta_cap'      => true,

        'supports' => array(
            'title',
        ),

        'show_in_rest' => true,
    ));
}

add_action('init', 'kudos_register_students');


/* =========================================================
   STUDENT DETAILS
   ========================================================= */

function kudos_student_meta_box() {

    add_meta_box(
        'kudos_student_details',
        'Student Details',
        'kudos_student_details_callback',
        'kudos_student',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kudos_student_meta_box'
);


function kudos_student_details_callback($post) {

    wp_nonce_field(
        'kudos_save_student_details',
        'kudos_student_nonce'
    );

    $student_name = get_post_meta(
        $post->ID,
        '_kudos_student_name',
        true
    );

    $class = get_post_meta(
        $post->ID,
        '_kudos_student_class',
        true
    );

    $parent_id = get_post_meta(
        $post->ID,
        '_kudos_student_parent',
        true
    );

    $status = get_post_meta(
        $post->ID,
        '_kudos_student_status',
        true
    );

    /*
     * Existing wallet balance.
     * We intentionally use the existing meta key
     * because the Parent Dashboard already reads this key.
     */
    $kudos_balance = get_post_meta(
        $post->ID,
        '_kudos_student_rewards',
        true
    );

    if ($kudos_balance === '') {
        $kudos_balance = 0;
    }

    $parents = get_posts(array(
        'post_type'      => 'kudos_parent',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));

    ?>

    <p>
        <label>
            <strong>Student Name</strong>
        </label>
    </p>

    <input
        type="text"
        name="kudos_student_name"
        value="<?php echo esc_attr($student_name); ?>"
        style="width:100%;max-width:500px;"
        placeholder="Student Name"
    />


    <p style="margin-top:20px;">
        <label>
            <strong>Class / Batch</strong>
        </label>
    </p>

    <input
        type="text"
        name="kudos_student_class"
        value="<?php echo esc_attr($class); ?>"
        style="width:100%;max-width:500px;"
        placeholder="Example: Class 5 - A"
    />


    <p style="margin-top:20px;">
        <label>
            <strong>Parent</strong>
        </label>
    </p>

    <select
        name="kudos_student_parent"
        style="width:100%;max-width:500px;"
    >

        <option value="">
            Select Parent
        </option>

        <?php foreach ($parents as $parent) : ?>

            <?php

            $parent_name = get_post_meta(
                $parent->ID,
                '_kudos_parent_name',
                true
            );

            $display_name = $parent_name
                ? $parent_name
                : $parent->post_title;

            ?>

            <option
                value="<?php echo esc_attr($parent->ID); ?>"
                <?php selected(
                    $parent_id,
                    $parent->ID
                ); ?>
            >
                <?php echo esc_html($display_name); ?>
            </option>

        <?php endforeach; ?>

    </select>


    <p style="margin-top:20px;">
        <label>
            <strong>Status</strong>
        </label>
    </p>

    <select
        name="kudos_student_status"
        style="width:100%;max-width:500px;"
    >

        <option
            value="active"
            <?php selected(
                $status,
                'active'
            ); ?>
        >
            Active
        </option>

        <option
            value="inactive"
            <?php selected(
                $status,
                'inactive'
            ); ?>
        >
            Inactive
        </option>

    </select>


    <!-- =====================================================
         KUDOS BALANCE
         ===================================================== -->

    <p style="margin-top:20px;">
        <label>
            <strong>Kudos Balance</strong>
        </label>
    </p>

    <input
        type="number"
        name="kudos_student_rewards"
        value="<?php echo esc_attr($kudos_balance); ?>"
        min="0"
        step="1"
        style="width:100%;max-width:500px;"
        placeholder="0"
    />

    <p style="margin-top:6px;color:#666;">
        Current available Kudos Coins for this student.
        <br>
        <strong>1 Kudos = ₹1 fee benefit.</strong>
    </p>

    <?php
}


/* =========================================================
   SAVE STUDENT DETAILS
   ========================================================= */

function kudos_save_student_details($post_id) {

    if (
        !isset($_POST['kudos_student_nonce']) ||
        !wp_verify_nonce(
            $_POST['kudos_student_nonce'],
            'kudos_save_student_details'
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
        wp_is_post_revision($post_id)
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


    /* =====================================================
       STUDENT NAME
       ===================================================== */

    if (
        isset($_POST['kudos_student_name'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_student_name',
            sanitize_text_field(
                $_POST['kudos_student_name']
            )
        );
    }


    /* =====================================================
       CLASS / BATCH
       ===================================================== */

    if (
        isset($_POST['kudos_student_class'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_student_class',
            sanitize_text_field(
                $_POST['kudos_student_class']
            )
        );
    }


    /* =====================================================
       PARENT
       ===================================================== */

    if (
        isset($_POST['kudos_student_parent'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_student_parent',
            absint(
                $_POST['kudos_student_parent']
            )
        );
    }


    /* =====================================================
       STATUS
       ===================================================== */

    if (
        isset($_POST['kudos_student_status'])
    ) {

        $status = sanitize_text_field(
            $_POST['kudos_student_status']
        );

        if (
            !in_array(
                $status,
                array(
                    'active',
                    'inactive'
                ),
                true
            )
        ) {
            $status = 'active';
        }

        update_post_meta(
            $post_id,
            '_kudos_student_status',
            $status
        );
    }


    /* =====================================================
       KUDOS BALANCE
       ===================================================== */

    if (
        isset($_POST['kudos_student_rewards'])
    ) {

        $balance = absint(
            $_POST['kudos_student_rewards']
        );

        update_post_meta(
            $post_id,
            '_kudos_student_rewards',
            $balance
        );
    }
}

add_action(
    'save_post_kudos_student',
    'kudos_save_student_details'
);


/* =========================================================
   DEFAULT KUDOS BALANCE
   ========================================================= */

function kudos_student_default_balance($post_id) {

    if (
        get_post_type($post_id) !== 'kudos_student'
    ) {
        return;
    }

    /*
     * Do not overwrite an existing balance.
     */
    if (
        get_post_meta(
            $post_id,
            '_kudos_student_rewards',
            true
        ) === ''
    ) {

        update_post_meta(
            $post_id,
            '_kudos_student_rewards',
            0
        );
    }
}

add_action(
    'save_post_kudos_student',
    'kudos_student_default_balance',
    20
);


/* =========================================================
   STUDENT ADMIN COLUMNS
   ========================================================= */

function kudos_student_columns($columns) {

    return array(
        'cb'       => '<input type="checkbox" />',
        'title'    => 'Student',
        'parent'   => 'Parent',
        'class'    => 'Class / Batch',
        'kudos'    => 'Kudos',
        'status'   => 'Status',
        'date'     => 'Date',
    );
}

add_filter(
    'manage_kudos_student_posts_columns',
    'kudos_student_columns'
);


/* =========================================================
   STUDENT ADMIN COLUMN CONTENT
   ========================================================= */

function kudos_student_column_content(
    $column,
    $post_id
) {

    /* -----------------------------------------------------
       PARENT
       ----------------------------------------------------- */

    if ($column === 'parent') {

        $parent_id = get_post_meta(
            $post_id,
            '_kudos_student_parent',
            true
        );

        if ($parent_id) {

            $parent_name = get_post_meta(
                $parent_id,
                '_kudos_parent_name',
                true
            );

            if (!$parent_name) {
                $parent_name = get_the_title($parent_id);
            }

            echo esc_html($parent_name);

        } else {

            echo '<span style="color:#999;">Not linked</span>';
        }
    }


    /* -----------------------------------------------------
       CLASS
       ----------------------------------------------------- */

    if ($column === 'class') {

        $class = get_post_meta(
            $post_id,
            '_kudos_student_class',
            true
        );

        echo $class
            ? esc_html($class)
            : '—';
    }


    /* -----------------------------------------------------
       KUDOS
       ----------------------------------------------------- */

    if ($column === 'kudos') {

        $balance = get_post_meta(
            $post_id,
            '_kudos_student_rewards',
            true
        );

        if ($balance === '') {
            $balance = 0;
        }

        echo '<strong style="color:#2d7b3c;">'
            . esc_html($balance)
            . '</strong>';
    }


    /* -----------------------------------------------------
       STATUS
       ----------------------------------------------------- */

    if ($column === 'status') {

        $status = get_post_meta(
            $post_id,
            '_kudos_student_status',
            true
        );

        if ($status === 'inactive') {

            echo '<span style="color:#b32d2e;">Inactive</span>';

        } else {

            echo '<span style="color:#22863a;">Active</span>';
        }
    }
}
    
add_action(
    'manage_kudos_student_posts_custom_column',
    'kudos_student_column_content',
    10,
    2
);
/* =========================================================
   CONFIRMED STUDENT DATA
   ========================================================= */

function kla_confirmed_student_data() {

    return array(

        array(
            'name'   => 'Hitarsh',
            'parent' => 'Shitali',
            'mobile' => '9999355572',
        ),

        array(
            'name'   => 'Saisha',
            'parent' => 'Kamal',
            'mobile' => '7206526549',
        ),

        array(
            'name'   => 'Rishit',
            'parent' => 'Ruby',
            'mobile' => '9999560693',
        ),

        array(
            'name'   => 'Sanvi',
            'parent' => 'Ruby',
            'mobile' => '9999560693',
        ),

        array(
            'name'   => 'Saeysha',
            'parent' => 'Chaya',
            'mobile' => '9711333480',
        ),

        array(
            'name'   => 'Rishika',
            'parent' => 'Govind',
            'mobile' => '9999972821',
        ),

        array(
            'name'   => 'Vayodant',
            'parent' => 'Garima',
            'mobile' => '9711431900',
        ),

        array(
            'name'   => 'Lakshita',
            'parent' => 'Bharti',
            'mobile' => '8527411741',
        ),

        array(
            'name'   => 'Harshita',
            'parent' => 'Bharti',
            'mobile' => '8527411741',
        ),

        array(
            'name'   => 'Pari',
            'parent' => 'Bharti',
            'mobile' => '8527411741',
        ),

        array(
            'name'   => 'Adharav',
            'parent' => 'Bharti',
            'mobile' => '8527411741',
        ),

        array(
            'name'   => 'Amayra',
            'parent' => 'Neha',
            'mobile' => '9310441281',
        ),

        array(
            'name'   => 'Nihaal',
            'parent' => 'Seema',
            'mobile' => '9999274053',
        ),

        array(
            'name'   => 'Samriddhi',
            'parent' => 'Charu',
            'mobile' => '9871501096',
        ),

        array(
            'name'   => 'Ojasvi',
            'parent' => 'Charu',
            'mobile' => '9871501096',
        ),

        array(
            'name'   => 'Miraya',
            'parent' => 'Aabha',
            'mobile' => '9910358319',
        ),

        array(
            'name'   => 'Priyansh',
            'parent' => 'Aasha',
            'mobile' => '9891400687',
        ),

        array(
            'name'   => 'Vernicia',
            'parent' => 'Megha Sharma',
            'mobile' => '9810415624',
        ),
    );
}


/* =========================================================
   STUDENT IMPORT BUTTON
   ========================================================= */

function kla_student_import_button($which) {

    if ($which !== 'top') {
        return;
    }

    if (
        !isset($_GET['post_type']) ||
        $_GET['post_type'] !== 'kudos_student'
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $url = wp_nonce_url(
        admin_url(
            'admin-post.php?action=kla_import_confirmed_students'
        ),
        'kla_import_confirmed_students'
    );

    echo '<div style="display:inline-block;margin-left:10px;">';

    echo '<a href="' . esc_url($url) . '" class="button button-primary">';
    echo 'Import Confirmed Students';
    echo '</a>';

    echo '</div>';
}

add_action(
    'manage_posts_extra_tablenav',
    'kla_student_import_button'
);


/* =========================================================
   PROCESS STUDENT IMPORT
   ========================================================= */

function kla_process_confirmed_student_import() {

    if (!current_user_can('manage_options')) {

        wp_die(
            'You do not have permission to import students.'
        );
    }

    check_admin_referer(
        'kla_import_confirmed_students'
    );

    $students = kla_confirmed_student_data();

    $created = 0;
    $updated = 0;
    $failed  = 0;


    foreach ($students as $student) {

        $student_name = $student['name'];
        $parent_name  = $student['parent'];
        $mobile       = $student['mobile'];


        /* =================================================
           FIND PARENT BY MOBILE
           ================================================= */

        $parent_id = 0;

        $parents = get_posts(array(
            'post_type'      => 'kudos_parent',
            'post_status'    => 'publish',
            'posts_per_page' => 1,

            'meta_query' => array(
                array(
                    'key'     => '_kudos_parent_mobile',
                    'value'   => $mobile,
                    'compare' => '=',
                ),
            ),
        ));


        if (!empty($parents)) {

            $parent_id = $parents[0]->ID;
        }


        /* =================================================
           FIND PARENT BY NAME
           ================================================= */

        if (!$parent_id) {

            $parents = get_posts(array(
                'post_type'      => 'kudos_parent',
                'post_status'    => 'publish',
                'posts_per_page' => 1,

                'meta_query' => array(
                    array(
                        'key'     => '_kudos_parent_name',
                        'value'   => $parent_name,
                        'compare' => '=',
                    ),
                ),
            ));


            if (!empty($parents)) {

                $parent_id = $parents[0]->ID;
            }
        }


        /* =================================================
           CREATE PARENT IF MISSING
           ================================================= */

        if (!$parent_id) {

            $parent_id = wp_insert_post(array(
                'post_type'   => 'kudos_parent',
                'post_status' => 'publish',
                'post_title'  => $parent_name,
            ));


            if (
                !$parent_id ||
                is_wp_error($parent_id)
            ) {

                $failed++;

                continue;
            }


            update_post_meta(
                $parent_id,
                '_kudos_parent_name',
                $parent_name
            );


            update_post_meta(
                $parent_id,
                '_kudos_parent_mobile',
                $mobile
            );
        }


        /* =================================================
           FIND EXISTING STUDENT
           ================================================= */

        $student_id = 0;


        $student_posts = get_posts(array(
            'post_type'      => 'kudos_student',
            'post_status'    => 'any',
            'posts_per_page' => 1,

            'meta_query' => array(
                array(
                    'key'     => '_kudos_student_name',
                    'value'   => $student_name,
                    'compare' => '=',
                ),
            ),
        ));


        if (!empty($student_posts)) {

            $student_id = $student_posts[0]->ID;
        }


        /* =================================================
           CHECK OLD NAME ALIASES
           ================================================= */

        if (!$student_id) {

            $aliases = array();


            if ($student_name === 'Amayra') {

                $aliases = array(
                    'Anayara'
                );
            }


            if ($student_name === 'Samriddhi') {

                $aliases = array(
                    'Samridhi'
                );
            }


            if ($student_name === 'Priyansh') {

                $aliases = array(
                    'Priyankh'
                );
            }


            if ($student_name === 'Vernicia') {

                $aliases = array(
                    'Vernica'
                );
            }


            foreach ($aliases as $alias) {

                $alias_posts = get_posts(array(
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
                ));


                if (!empty($alias_posts)) {

                    $student_id = $alias_posts[0]->ID;

                    break;
                }
            }
        }


        /* =================================================
           CREATE STUDENT
           ================================================= */

        if (!$student_id) {

            $student_id = wp_insert_post(array(
                'post_type'   => 'kudos_student',
                'post_status' => 'publish',
                'post_title'  => $student_name,
            ));


            if (
                !$student_id ||
                is_wp_error($student_id)
            ) {

                $failed++;

                continue;
            }


            $created++;

        } else {

            $updated++;
        }


        /* =================================================
           SAVE STUDENT DETAILS
           ================================================= */

        update_post_meta(
            $student_id,
            '_kudos_student_name',
            $student_name
        );


        update_post_meta(
            $student_id,
            '_kudos_student_parent',
            $parent_id
        );


        update_post_meta(
            $student_id,
            '_kudos_student_status',
            'active'
        );


        /* =================================================
           INITIAL KUDOS BALANCE
           ================================================= */

        if (
            get_post_meta(
                $student_id,
                '_kudos_student_rewards',
                true
            ) === ''
        ) {

            update_post_meta(
                $student_id,
                '_kudos_student_rewards',
                0
            );
        }
    }


    /* =================================================
       REDIRECT
       ================================================= */

    $redirect_url = add_query_arg(
        array(
            'post_type'     => 'kudos_student',
            'kla_created'   => $created,
            'kla_updated'   => $updated,
            'kla_failed'    => $failed,
        ),
        admin_url('edit.php')
    );


    wp_safe_redirect(
        $redirect_url
    );

    exit;
}

add_action(
    'admin_post_kla_import_confirmed_students',
    'kla_process_confirmed_student_import'
);


/* =========================================================
   IMPORT RESULT NOTICE
   ========================================================= */

function kla_student_import_notice() {

    if (
        !isset($_GET['kla_created'])
    ) {
        return;
    }

    $created = absint(
        $_GET['kla_created']
    );

    $updated = absint(
        $_GET['kla_updated']
    );

    $failed = absint(
        $_GET['kla_failed']
    );


    echo '<div class="notice notice-success is-dismissible">';

    echo '<p>';
    echo '<strong>Student import completed.</strong> ';

    echo 'Created: ';
    echo esc_html($created);

    echo ' | Updated: ';
    echo esc_html($updated);

    echo ' | Failed: ';
    echo esc_html($failed);

    echo '</p>';

    echo '</div>';
}

add_action(
    'admin_notices',
    'kla_student_import_notice'
);
