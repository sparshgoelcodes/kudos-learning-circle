<?php
/**
 * Default Page Template
 * Kudos Learning Academy
 */

get_header();
?>

<main id="primary" class="site-main">

    <?php if ( is_page( 'admin-dashboard' ) ) : ?>

        <!-- Admin Dashboard Page -->
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>

    <?php else : ?>

        <!-- Normal WordPress Pages -->
        <div class="site-page">
            <div class="container">

                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>

                        <h1 class="page-title">
                            <?php the_title(); ?>
                        </h1>

                        <div class="page-body">
                            <?php the_content(); ?>
                        </div>

                    </article>

                    <?php
                endwhile;
                ?>

            </div>
        </div>

    <?php endif; ?>

</main>

<?php get_footer(); ?>