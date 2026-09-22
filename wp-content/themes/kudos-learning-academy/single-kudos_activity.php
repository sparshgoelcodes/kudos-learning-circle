<?php
/**
 * Single Activity Template
 * Kudos Learning Academy
 */

get_header();

while ( have_posts() ) :
    the_post();

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

    $fallback_image = 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=85';

    $fallbacks = array(
        'abacus' => 'https://images.unsplash.com/photo-1596495578065-6e0763fa1178?auto=format&fit=crop&w=1200&q=85',
        'ai' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1200&q=85',
        'art' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1200&q=85',
        'craft' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=1200&q=85',
        'dance' => 'https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=1200&q=85',
        'music' => 'https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?auto=format&fit=crop&w=1200&q=85',
        'phonics' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=85',
        'rubik' => 'https://images.unsplash.com/photo-1585366119957-e9730b6d0f60?auto=format&fit=crop&w=1200&q=85',
        'skates' => 'https://images.unsplash.com/photo-1547447134-cd3f5c716030?auto=format&fit=crop&w=1200&q=85',
        'taekwondo' => 'https://images.unsplash.com/photo-1555597673-b21d5c935865?auto=format&fit=crop&w=1200&q=85',
        'computer' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85',
        'it' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85',
        'math' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=1200&q=85',
        'vedic' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=1200&q=85',
        'english' => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?auto=format&fit=crop&w=1200&q=85',
        'coaching' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=85',
        'mentorship' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=85',
        'workshop' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=85',
        'classical' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=1200&q=85',
        'sketch' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1200&q=85',
    );

    foreach ( $fallbacks as $keyword => $image_url ) {
        if ( false !== strpos( $title_key, $keyword ) ) {
            $fallback_image = $image_url;
            break;
        }
    }
    ?>

    <main id="primary" class="site-main">

        <section class="kla-single-activity">

            <div class="kla-container">

                <a class="kla-back-link" href="<?php echo esc_url( home_url( '/activities/' ) ); ?>">
                    ← Back to Activities
                </a>

                <div class="kla-single-activity-grid">

                    <div class="kla-single-activity-media">

                        <?php if ( has_post_thumbnail() ) : ?>

                            <?php
                            the_post_thumbnail(
                                'large',
                                array(
                                    'loading' => 'eager',
                                )
                            );
                            ?>

                        <?php else : ?>

                            <img
                                src="<?php echo esc_url( $fallback_image ); ?>"
                                alt="<?php echo esc_attr( get_the_title() ); ?>"
                            >

                        <?php endif; ?>

                    </div>

                    <article class="kla-single-activity-content">

                        <?php if ( $category ) : ?>
                            <span class="kla-eyebrow">
                                <?php echo esc_html( $category ); ?>
                            </span>
                        <?php endif; ?>

                        <h1 class="kla-title">
                            <?php the_title(); ?>
                        </h1>

                        <?php if ( $fee !== '' && $fee !== null ) : ?>

                            <div class="kla-single-activity-fee">
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

                        <div class="kla-single-activity-description">
                            <?php the_content(); ?>
                        </div>

                        <div class="kla-single-activity-actions">

                            <a
                                class="kla-btn kla-btn-green"
                                href="tel:+919876543210"
                            >
                                ☎ Book A Call
                            </a>

                            <a
                                class="kla-cta-whatsapp kla-single-whatsapp"
                                href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20know%20more%20about%20<?php echo rawurlencode( get_the_title() ); ?>."
                                target="_blank"
                                rel="noopener"
                            >
                                WhatsApp Us
                            </a>

                        </div>

                    </article>

                </div>

            </div>

        </section>

    </main>

    <?php

endwhile;

get_footer();
