<?php
/**
 * Kudos Learning Academy - Frontend Enquiries
 *
 * Handles the public "Book A Call / Send Enquiry" form.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kla_submit_enquiry() {

    if (
        ! isset( $_POST['kla_enquiry_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['kla_enquiry_nonce'] ) ),
            'kla_submit_enquiry'
        )
    ) {
        wp_safe_redirect( home_url('/#contact') );
        exit;
    }

    $parent_name = isset($_POST['parent_name']) ? sanitize_text_field(wp_unslash($_POST['parent_name'])) : '';
    $child_name  = isset($_POST['child_name']) ? sanitize_text_field(wp_unslash($_POST['child_name'])) : '';
    $phone       = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $email       = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $interest    = isset($_POST['interest']) ? sanitize_text_field(wp_unslash($_POST['interest'])) : 'General Enquiry';
    $message     = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    if ( '' === $parent_name || '' === $phone ) {
        wp_safe_redirect( home_url('/?kla_enquiry=error#contact') );
        exit;
    }

    $subject = 'New Kudos Academy Enquiry - ' . $interest;

    $body  = "New enquiry received from Kudos Learning Academy website.\n\n";
    $body .= "Parent Name: " . $parent_name . "\n";
    $body .= "Child Name: " . ( $child_name ?: 'Not provided' ) . "\n";
    $body .= "Mobile: " . $phone . "\n";
    $body .= "Email: " . ( $email ?: 'Not provided' ) . "\n";
    $body .= "Interest: " . $interest . "\n";
    $body .= "Message: " . ( $message ?: 'Not provided' ) . "\n";

    $admin_email = get_option('admin_email');

    if ( ! $admin_email ) {
        $admin_email = 'hello@kudosacademy.in';
    }

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    if ( $email ) {
        $headers[] = 'Reply-To: ' . $email;
    }

    $sent = wp_mail( $admin_email, $subject, $body, $headers );

    /*
     * Also keep the enquiry in WordPress so it is not lost if local SMTP/mail
     * is not configured. This creates an admin record without exposing it publicly.
     */
    $enquiry_id = wp_insert_post(
        array(
            'post_type'   => 'kla_enquiry',
            'post_status' => 'private',
            'post_title'  => $parent_name . ' - ' . $interest,
            'post_content'=> $message,
        ),
        true
    );

    if ( ! is_wp_error( $enquiry_id ) && $enquiry_id ) {
        update_post_meta($enquiry_id, '_kla_enquiry_parent_name', $parent_name);
        update_post_meta($enquiry_id, '_kla_enquiry_child_name', $child_name);
        update_post_meta($enquiry_id, '_kla_enquiry_phone', $phone);
        update_post_meta($enquiry_id, '_kla_enquiry_email', $email);
        update_post_meta($enquiry_id, '_kla_enquiry_interest', $interest);
        update_post_meta($enquiry_id, '_kla_enquiry_mail_sent', $sent ? 'yes' : 'no');
    }

    wp_safe_redirect( home_url('/?kla_enquiry=' . ($enquiry_id ? 'success' : 'error') . '#contact') );
    exit;
}

add_action('admin_post_nopriv_kla_submit_enquiry', 'kla_submit_enquiry');
add_action('admin_post_kla_submit_enquiry', 'kla_submit_enquiry');


function kla_register_enquiry_cpt() {

    register_post_type(
        'kla_enquiry',
        array(
            'labels' => array(
                'name'          => 'Enquiries',
                'singular_name' => 'Enquiry',
                'menu_name'     => 'Enquiries',
                'add_new_item'  => 'Add Enquiry',
                'edit_item'     => 'View Enquiry',
                'search_items'  => 'Search Enquiries',
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false,
            'supports'           => array('title', 'editor'),
            'menu_icon'          => 'dashicons-email-alt',
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        )
    );
}

add_action('init', 'kla_register_enquiry_cpt');
