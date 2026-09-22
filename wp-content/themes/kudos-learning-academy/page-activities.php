<?php
/**
 * Activities Page
 * Kudos Learning Academy
 */

get_header();
?>

<main id="primary" class="site-main">

    <section class="kla-public-activities-page">

        <div class="kla-container">

            <div class="kla-public-activities-hero">
                <div>
                    <span class="kla-eyebrow">LEARN • CREATE • PLAY • DISCOVER</span>

                    <h1 class="kla-title">
                        Explore Our Activities
                    </h1>

                    <p>
                        Fun, creative and skill-building activities designed
                        to help every child learn something new, build confidence
                        and discover what they enjoy.
                    </p>
                </div>

                <div class="kla-public-activities-hero-note">
                    <strong>Something for every curious mind.</strong>
                    <span>
                        From Abacus and AI to Dance, Art, Music and Sports,
                        children can learn, practise and grow through experiences
                        beyond the classroom.
                    </span>
                </div>
            </div>

            <?php
            $activities = new WP_Query(
                array(
                    'post_type'      => 'kudos_activity',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'no_found_rows'  => true,
                )
            );
            ?>

            <?php if ( $activities->have_posts() ) : ?>

                <div class="kla-public-activities-grid">

                    <?php while ( $activities->have_posts() ) : $activities->the_post(); ?>

                        <?php
                        $activity_id = get_the_ID();

                        $category = get_post_meta(
                            $activity_id,
                            '_kudos_activity_category',
                            true
                        );

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

                        $title_key = strtolower( get_the_title() );

                        $fallback_images = array(
                            'abacus' => 'https://images.unsplash.com/photo-1596495578065-6e0763fa1178?auto=format&fit=crop&w=900&q=85',
                            'ai' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=900&q=85',
                            'art' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=900&q=85',
                            'craft' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=900&q=85',
                            'dance' => 'https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=900&q=85',
                            'music' => 'https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?auto=format&fit=crop&w=900&q=85',
                            'phonics' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=900&q=85',
                            'rubik' => 'https://images.unsplash.com/photo-1585366119957-e9730b6d0f60?auto=format&fit=crop&w=900&q=85',
                            'skates' => 'https://images.unsplash.com/photo-1547447134-cd3f5c716030?auto=format&fit=crop&w=900&q=85',
                            'taekwondo' => 'https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=900&q=85',
                            'computer' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=85',
                            'it' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=85',
                            'math' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=900&q=85',
                            'vedic' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=900&q=85',
                            'english' => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?auto=format&fit=crop&w=900&q=85',
                            'coaching' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&q=85',
                            'mentorship' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&q=85',
                            'workshop' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=85',
                            'classical' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=85',
                            'sketch' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=900&q=85',
                        );

                        $fallback_image = '';

                        foreach ( $fallback_images as $keyword => $image_url ) {
                            if ( false !== strpos( $title_key, $keyword ) ) {
                                $fallback_image = $image_url;
                                break;
                            }
                        }

                        if ( ! $fallback_image ) {
                            $fallback_image = 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=85';
                        }
                        ?>

                        <article class="kla-public-activity-card">

                            <a href="<?php the_permalink(); ?>" class="kla-public-activity-image">

                                <?php if ( has_post_thumbnail() ) : ?>

                                    <?php
                                    the_post_thumbnail(
                                        'large',
                                        array(
                                            'loading' => 'lazy',
                                        )
                                    );
                                    ?>

                                <?php else : ?>

                                    <img
                                        src="<?php echo esc_url( $fallback_image ); ?>"
                                        alt="<?php echo esc_attr( get_the_title() ); ?>"
                                        loading="lazy"
                                    >

                                <?php endif; ?>

                                <span class="kla-public-activity-image-overlay">
                                    Explore →
                                </span>

                            </a>

                            <div class="kla-public-activity-body">

                                <?php if ( $category ) : ?>
                                    <span class="kla-public-activity-category">
                                        <?php echo esc_html( $category ); ?>
                                    </span>
                                <?php endif; ?>

                                <h2>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <?php if ( get_the_excerpt() ) : ?>
                                    <p>
                                        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="kla-public-activity-footer">

                                    <?php if ( $fee !== '' && $fee !== null ) : ?>

                                        <div class="kla-public-activity-fee">
                                            <small>Activity Fee</small>
                                            <strong>
                                                <?php
                                                if ( is_numeric( $fee ) ) {
                                                    echo '₹' . esc_html(
                                                        number_format_i18n(
                                                            (float) $fee
                                                        )
                                                    );
                                                } else {
                                                    echo esc_html( $fee );
                                                }
                                                ?>
                                            </strong>

                                            <?php if ( $fee_type ) : ?>
                                                <span>
                                                    <?php echo esc_html( str_replace( '_', ' ', $fee_type ) ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                    <?php endif; ?>

                                    <a
                                        class="kla-public-activity-link"
                                        href="<?php the_permalink(); ?>"
                                    >
                                        View Activity →
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endwhile; ?>

                </div>

            <?php else : ?>

                <div class="kla-no-activities">
                    <h2>Activities Coming Soon</h2>
                    <p>
                        We are preparing exciting learning activities
                        for our students.
                    </p>
                </div>

            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

        </div>

    </section>

    <section class="kla-public-activities-cta">
        <div class="kla-container">
            <div class="kla-public-activities-cta-inner">
                <div>
                    <span class="kla-eyebrow">READY TO EXPLORE?</span>
                    <h2>Help Your Child Discover What They Love.</h2>
                    <p>
                        Talk to our team about activities, schedules and
                        suitable learning options for your child.
                    </p>
                </div>

                <div class="kla-cta-actions">
                    <a class="kla-btn kla-btn-white" href="tel:+919876543210">
                        ☎ Book A Call →
                    </a>

                    <a
                        class="kla-cta-whatsapp"
                        href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20know%20more%20about%20the%20activities."
                        target="_blank"
                        rel="noopener"
                    >
                        WhatsApp Us
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
