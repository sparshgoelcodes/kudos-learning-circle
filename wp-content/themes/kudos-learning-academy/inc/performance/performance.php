<?php

/* =========================================================
   PERFORMANCE
   ========================================================= */

function kudos_register_performance() {

    register_post_type('kudos_performance', array(

        'labels' => array(
            'name'               => 'Performance',
            'singular_name'      => 'Performance Record',
            'menu_name'          => 'Performance',
            'add_new'            => 'Add Record',
            'add_new_item'       => 'Add Performance Record',
            'edit_item'          => 'Edit Performance Record',
            'new_item'           => 'New Performance Record',
            'view_item'          => 'View Performance Record',
            'search_items'       => 'Search Performance',
            'not_found'          => 'No performance records found',
        ),

        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_admin_bar' => true,

        'menu_position'     => 28,
        'menu_icon'         => 'dashicons-chart-bar',

        'capability_type'   => 'post',
        'map_meta_cap'      => true,

        'supports' => array(
            'title',
        ),

        'show_in_rest' => true,
    ));
}

add_action('init', 'kudos_register_performance');


/* =========================================================
   PERFORMANCE DETAILS
   ========================================================= */

function kudos_performance_meta_box() {

    add_meta_box(
        'kudos_performance_details',
        'Performance Details',
        'kudos_performance_details_callback',
        'kudos_performance',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kudos_performance_meta_box'
);


function kudos_performance_details_callback($post) {

    wp_nonce_field(
        'kudos_save_performance_details',
        'kudos_performance_nonce'
    );

    $student_id = get_post_meta(
        $post->ID,
        '_kudos_performance_student',
        true
    );

    $punctuality = get_post_meta(
        $post->ID,
        '_kudos_performance_punctuality',
        true
    );

    $discipline = get_post_meta(
        $post->ID,
        '_kudos_performance_discipline',
        true
    );

    $fees = get_post_meta(
        $post->ID,
        '_kudos_performance_fees',
        true
    );

    $etiquettes = get_post_meta(
        $post->ID,
        '_kudos_performance_etiquettes',
        true
    );

    $workshops = get_post_meta(
        $post->ID,
        '_kudos_performance_workshops',
        true
    );

    $school_performance = get_post_meta(
        $post->ID,
        '_kudos_performance_school',
        true
    );

    $participation = get_post_meta(
        $post->ID,
        '_kudos_performance_participation',
        true
    );


    $students = get_posts(array(
        'post_type'      => 'kudos_student',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));

    ?>

    <p>
        <label>
            <strong>Student</strong>
        </label>
    </p>

    <select
        name="kudos_performance_student"
        style="width:100%;max-width:500px;"
    >

        <option value="">
            Select Student
        </option>

        <?php foreach ($students as $student) : ?>

            <?php
            $student_name = get_post_meta(
                $student->ID,
                '_kudos_student_name',
                true
            );

            $display_name = $student_name
                ? $student_name
                : $student->post_title;
            ?>

            <option
                value="<?php echo esc_attr($student->ID); ?>"
                <?php selected(
                    $student_id,
                    $student->ID
                ); ?>
            >
                <?php echo esc_html($display_name); ?>
            </option>

        <?php endforeach; ?>

    </select>


    <?php
    kudos_performance_number_field(
        'Punctuality',
        'kudos_performance_punctuality',
        $punctuality
    );

    kudos_performance_number_field(
        'Discipline',
        'kudos_performance_discipline',
        $discipline
    );

    kudos_performance_number_field(
        'Fees',
        'kudos_performance_fees',
        $fees
    );

    kudos_performance_number_field(
        'Etiquettes / Manners',
        'kudos_performance_etiquettes',
        $etiquettes
    );

    kudos_performance_number_field(
        'Workshops',
        'kudos_performance_workshops',
        $workshops
    );

    kudos_performance_number_field(
        'Learning Progress',
        'kudos_performance_school',
        $school_performance
    );

    kudos_performance_number_field(
        'Participation (Activity)',
        'kudos_performance_participation',
        $participation
    );

    ?>

    <p style="color:#666;margin-top:20px;">
        Enter the score/value exactly as maintained by the academy.
    </p>

    <?php
}


/* =========================================================
   PERFORMANCE NUMBER FIELD
   ========================================================= */

function kudos_performance_number_field(
    $label,
    $name,
    $value
) {

    ?>

    <p style="margin-top:20px;">

        <label>
            <strong>
                <?php echo esc_html($label); ?>
            </strong>
        </label>

    </p>

    <input
        type="number"
        name="<?php echo esc_attr($name); ?>"
        value="<?php echo esc_attr($value); ?>"
        min="0"
        step="1"
        style="width:100%;max-width:500px;"
        placeholder="0"
    />

    <?php
}


/* =========================================================
   SAVE PERFORMANCE
   ========================================================= */

function kudos_save_performance_details($post_id) {

    if (
        !isset($_POST['kudos_performance_nonce']) ||
        !wp_verify_nonce(
            $_POST['kudos_performance_nonce'],
            'kudos_save_performance_details'
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


    /* Student */

    if (
        isset($_POST['kudos_performance_student'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_student',
            absint(
                $_POST['kudos_performance_student']
            )
        );
    }


    /* Punctuality */

    if (
        isset($_POST['kudos_performance_punctuality'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_punctuality',
            absint(
                $_POST['kudos_performance_punctuality']
            )
        );
    }


    /* Discipline */

    if (
        isset($_POST['kudos_performance_discipline'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_discipline',
            absint(
                $_POST['kudos_performance_discipline']
            )
        );
    }


    /* Fees */

    if (
        isset($_POST['kudos_performance_fees'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_fees',
            absint(
                $_POST['kudos_performance_fees']
            )
        );
    }


    /* Etiquettes */

    if (
        isset($_POST['kudos_performance_etiquettes'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_etiquettes',
            absint(
                $_POST['kudos_performance_etiquettes']
            )
        );
    }


    /* Workshops */

    if (
        isset($_POST['kudos_performance_workshops'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_workshops',
            absint(
                $_POST['kudos_performance_workshops']
            )
        );
    }


    /* School Performance */

    if (
        isset($_POST['kudos_performance_school'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_school',
            absint(
                $_POST['kudos_performance_school']
            )
        );
    }


    /* Activity Participation */

    if (
        isset($_POST['kudos_performance_participation'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_performance_participation',
            absint(
                $_POST['kudos_performance_participation']
            )
        );
    }
}

add_action(
    'save_post_kudos_performance',
    'kudos_save_performance_details'
);

/* =========================================================
   IMPORT INITIAL PERFORMANCE DATA
   ========================================================= */

function kudos_import_initial_performance_data() {

    // Prevent importing the same records again
    if (get_option('kudos_initial_performance_imported')) {
        return;
    }

    $performance_data = array(

        array('Hitarsh', 50, 45, 100, 50, 40, 100, 100),
        array('Saisha', 50, 45, 100, 50, 40, 100, 100),
        array('Rishit', 45, 40, 100, 50, 50, 100, 100),
        array('Sanvi', 50, 40, 100, 50, 50, 100, 100),
        array('Saeysha', 45, 40, 85, 45, 50, 100, 100),
        array('Rishika', 45, 40, 100, 50, 50, 70, 100),
        array('Vayodant', 40, 40, 100, 50, 50, 80, 100),
        array('Lakshita', 35, 45, 100, 50, 45, 70, 100),
        array('Harshita', 38, 45, 100, 50, 45, 70, 90),
        array('Pari', 30, 40, 100, 45, 45, 80, 90),
        array('Adharav', 40, 45, 100, 50, 45, 70, 95),
        array('Anayara', 38, 50, 100, 50, 45, 80, 95),
        array('Nihaal', 37, 45, 100, 50, 40, 90, 95),
        array('Samridhi', 45, 50, 100, 50, 40, 80, 90),
        array('Ojasvi', 40, 50, 100, 50, 40, 80, 85),
        array('Miraya', 40, 35, 100, 45, 40, 85, 80),
        array('Rachael', 45, 45, 100, 50, 45, 90, 90),
        array('Priyankh', 40, 45, 100, 50, 45, 85, 95),
        array('Vernicia', 38, 45, 100, 50, 45, 80, 90),
        array('Ishaan', 45, 45, 100, 50, 45, 85, 90),

    );


    foreach ($performance_data as $data) {

        $student_name          = $data[0];
        $punctuality           = $data[1];
        $discipline            = $data[2];
        $fees                  = $data[3];
        $etiquettes            = $data[4];
        $workshops             = $data[5];
        $school_performance    = $data[6];
        $participation         = $data[7];


        $post_id = wp_insert_post(
            array(
                'post_title'  => $student_name . ' - Initial Performance',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type'   => 'kudos_performance',
            ),
            true
        );


        if (!is_wp_error($post_id)) {

            /*
             * Store student name temporarily.
             * We will connect it to the actual Student record later.
             */

            update_post_meta(
                $post_id,
                '_kudos_performance_student_name',
                $student_name
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_punctuality',
                $punctuality
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_discipline',
                $discipline
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_fees',
                $fees
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_etiquettes',
                $etiquettes
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_workshops',
                $workshops
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_school',
                $school_performance
            );


            update_post_meta(
                $post_id,
                '_kudos_performance_participation',
                $participation
            );
        }
    }


    // Mark import as completed
    update_option(
        'kudos_initial_performance_imported',
        1
    );
}

add_action(
    'admin_init',
    'kudos_import_initial_performance_data'
);

