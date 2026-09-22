<?php

/* =========================================================
   ACTIVITIES
   ========================================================= */

function kudos_register_activities() {

    register_post_type('kudos_activity', array(

        'labels' => array(
            'name'               => 'Activities',
            'singular_name'      => 'Activity',
            'menu_name'          => 'Activities',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Activity',
            'edit_item'          => 'Edit Activity',
            'new_item'           => 'New Activity',
            'view_item'          => 'View Activity',
            'search_items'       => 'Search Activities',
            'not_found'          => 'No activities found',
            'not_found_in_trash' => 'No activities found in Trash',
        ),

        'public'            => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_admin_bar' => true,

        'menu_position'     => 25,
        'menu_icon'         => 'dashicons-awards',

        'capability_type'   => 'post',
        'map_meta_cap'      => true,

        'supports' => array(
            'title',
            'editor',
            'thumbnail',
        ),

        'has_archive' => false,

'rewrite' => array(
    'slug'       => 'activity',
    'with_front' => false,
),

        'show_in_rest' => true,
    ));
}

add_action('init', 'kudos_register_activities');


/* =========================================================
   ACTIVITY DETAILS META BOX
   ========================================================= */

function kudos_activity_meta_box() {

    add_meta_box(
        'kudos_activity_details',
        'Activity Details',
        'kudos_activity_details_callback',
        'kudos_activity',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'kudos_activity_meta_box');


function kudos_activity_details_callback($post) {

    wp_nonce_field(
        'kudos_save_activity_details',
        'kudos_activity_nonce'
    );

    $fee = get_post_meta(
        $post->ID,
        '_kudos_activity_fee',
        true
    );

    $category = get_post_meta(
        $post->ID,
        '_kudos_activity_category',
        true
    );

    $fee_type = get_post_meta(
        $post->ID,
        '_kudos_activity_fee_type',
        true
    );

    ?>

    <p>
        <label>
            <strong>Activity Category</strong>
        </label>
    </p>

    <select
        name="kudos_activity_category"
        style="width:100%;max-width:500px;"
    >

        <option value="">
            Select Category
        </option>

        <option
            value="Skill-Based / Education"
            <?php selected(
                $category,
                'Skill-Based / Education'
            ); ?>
        >
            Skill-Based / Education
        </option>

        <option
            value="Entertainment"
            <?php selected(
                $category,
                'Entertainment'
            ); ?>
        >
            Entertainment
        </option>

    </select>


    <p style="margin-top:20px;">
        <label>
            <strong>Fee Type</strong>
        </label>
    </p>

    <select
        name="kudos_activity_fee_type"
        style="width:100%;max-width:500px;"
    >

        <option
            value="fixed"
            <?php selected(
                $fee_type,
                'fixed'
            ); ?>
        >
            Fixed Fee
        </option>

        <option
            value="activity_based"
            <?php selected(
                $fee_type,
                'activity_based'
            ); ?>
        >
            Based on Activity / Event
        </option>

    </select>


    <p style="margin-top:20px;">
        <label>
            <strong>Activity Fee (₹)</strong>
        </label>
    </p>

    <input
        type="number"
        name="kudos_activity_fee"
        value="<?php echo esc_attr($fee); ?>"
        placeholder="1800"
        min="0"
        step="1"
        style="width:100%;max-width:500px;"
    />

    <p style="color:#666;">
        For Workshops or Events where the fee depends on the activity,
        select "Based on Activity / Event".
    </p>

    <?php
}


function kudos_save_activity_details($post_id) {

    if (
        !isset($_POST['kudos_activity_nonce']) ||
        !wp_verify_nonce(
            $_POST['kudos_activity_nonce'],
            'kudos_save_activity_details'
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
        isset($_POST['kudos_activity_category'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_activity_category',
            sanitize_text_field(
                $_POST['kudos_activity_category']
            )
        );
    }


    if (
        isset($_POST['kudos_activity_fee'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_activity_fee',
            sanitize_text_field(
                $_POST['kudos_activity_fee']
            )
        );
    }


    if (
        isset($_POST['kudos_activity_fee_type'])
    ) {

        update_post_meta(
            $post_id,
            '_kudos_activity_fee_type',
            sanitize_text_field(
                $_POST['kudos_activity_fee_type']
            )
        );
    }
}

add_action(
    'save_post_kudos_activity',
    'kudos_save_activity_details'
);



