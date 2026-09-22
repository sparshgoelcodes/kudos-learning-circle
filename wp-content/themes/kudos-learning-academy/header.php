<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>

    <meta charset="<?php bloginfo('charset'); ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?php
        if (is_front_page()) {
            bloginfo('name');
        } else {
            wp_title('|', true, 'right');
            bloginfo('name');
        }
        ?>
    </title>

    <?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>