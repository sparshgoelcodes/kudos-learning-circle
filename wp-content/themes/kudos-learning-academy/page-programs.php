<?php
/**
 * Programs Page Template
 * Kudos Learning Academy
 */

get_header();

$kla_programs = array(
    array(
        'eyebrow' => '01',
        'title'   => 'Early Learners',
        'text'    => 'A gentle, engaging learning environment where children build strong foundations through curiosity, play and everyday discovery.',
        'items'   => array(
            'Foundational learning',
            'Play-based exploration',
            'Creative expression',
            'Confidence building',
        ),
        'class'   => 'kla-program-card-green',
    ),
    array(
        'eyebrow' => '02',
        'title'   => 'Creative Explorers',
        'text'    => 'A space for children to explore ideas, express themselves and discover the joy of creating something of their own.',
        'items'   => array(
            'Art & craft',
            'Music & movement',
            'Imagination & creativity',
            'Hands-on activities',
        ),
        'class'   => 'kla-program-card-yellow',
    ),
    array(
        'eyebrow' => '03',
        'title'   => 'Curious Minds',
        'text'    => 'Learning experiences designed to encourage questions, problem-solving and active participation in the world around us.',
        'items'   => array(
            'Mathematics & logic',
            'Language development',
            'Technology & digital learning',
            'Problem-solving',
        ),
        'class'   => 'kla-program-card-purple',
    ),
    array(
        'eyebrow' => '04',
        'title'   => 'Future Ready',
        'text'    => 'A balanced learning journey that helps children develop confidence, discipline, communication and skills for the next stage.',
        'items'   => array(
            'Skill development',
            'Communication',
            'Leadership & participation',
            'Confidence & independence',
        ),
        'class'   => 'kla-program-card-blue',
    ),
);
?>

<main id="primary" class="site-main kla-programs-page">

    <!-- Programs Hero -->
    <section class="kla-programs-hero">
        <div class="container">
            <div class="kla-programs-hero-grid">

                <div class="kla-programs-hero-copy">
                    <div class="kla-eyebrow">LEARN • EXPLORE • ACHIEVE</div>

                    <h1>
                        Learning Experiences
                        <span>Made for Growing Minds.</span>
                    </h1>

                    <p>
                        Our programs bring together learning, creativity, activities and
                        encouragement to help every child discover what they can do.
                    </p>

                    <div class="kla-programs-hero-actions">
                        <a class="kla-btn kla-btn-green"
                           href="<?php echo esc_url( home_url('/activities/') ); ?>">
                            Explore Activities →
                        </a>

                        <a class="kla-programs-text-link"
                           href="<?php echo esc_url( home_url('/contact/') ); ?>">
                            Talk to Us
                        </a>
                    </div>
                </div>

                <div class="kla-programs-hero-card">
                    <div class="kla-programs-hero-badge">KUDOS WAY</div>
                    <h2>Learn.<br>Explore.<br><strong>Grow.</strong></h2>
                    <p>
                        A learning journey where effort is noticed, progress is encouraged
                        and every achievement matters.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Programs -->
    <section class="kla-programs-section">
        <div class="container">

            <div class="kla-programs-heading">
                <div>
                    <div class="kla-eyebrow">OUR PROGRAMS</div>
                    <h2>Find the Right Learning Journey.</h2>
                </div>

                <p>
                    From foundational learning to future-ready skills, our approach keeps
                    children engaged, supported and excited to learn.
                </p>
            </div>

            <div class="kla-programs-grid">

                <?php foreach ( $kla_programs as $program ) : ?>

                    <article class="kla-program-card <?php echo esc_attr( $program['class'] ); ?>">

                        <div class="kla-program-card-top">
                            <span class="kla-program-number">
                                <?php echo esc_html( $program['eyebrow'] ); ?>
                            </span>

                            <span class="kla-program-spark">✦</span>
                        </div>

                        <h3><?php echo esc_html( $program['title'] ); ?></h3>

                        <p><?php echo esc_html( $program['text'] ); ?></p>

                        <ul>
                            <?php foreach ( $program['items'] as $item ) : ?>
                                <li>
                                    <span>✓</span>
                                    <?php echo esc_html( $item ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?php echo esc_url( home_url('/activities/') ); ?>"
                           class="kla-program-card-link">
                            Explore Activities →
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        </div>
    </section>

    <!-- Learning Approach -->
    <section class="kla-programs-approach">
        <div class="container">

            <div class="kla-programs-approach-grid">

                <div class="kla-programs-approach-copy">
                    <div class="kla-eyebrow">HOW WE LEARN</div>

                    <h2>More Than a Classroom.</h2>

                    <p>
                        At Kudos, learning is not limited to books and worksheets.
                        Children get opportunities to participate, create, practise,
                        communicate and celebrate their progress.
                    </p>

                    <a class="kla-btn kla-btn-green"
                       href="<?php echo esc_url( home_url('/activities/') ); ?>">
                        See Our Activities →
                    </a>
                </div>

                <div class="kla-programs-journey">

                    <div class="kla-program-step">
                        <span>01</span>
                        <div>
                            <h3>Learn</h3>
                            <p>Build concepts through meaningful learning experiences.</p>
                        </div>
                    </div>

                    <div class="kla-program-step">
                        <span>02</span>
                        <div>
                            <h3>Explore</h3>
                            <p>Try new activities and discover individual interests.</p>
                        </div>
                    </div>

                    <div class="kla-program-step">
                        <span>03</span>
                        <div>
                            <h3>Achieve</h3>
                            <p>Recognise effort, participation and progress.</p>
                        </div>
                    </div>

                    <div class="kla-program-step">
                        <span>04</span>
                        <div>
                            <h3>Grow</h3>
                            <p>Develop confidence, independence and a love for learning.</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- Activities Preview -->
    <section class="kla-programs-activities">
        <div class="container">

            <div class="kla-programs-activities-box">
                <div>
                    <div class="kla-eyebrow">LEARNING IN ACTION</div>
                    <h2>Discover What Children Can Explore.</h2>
                    <p>
                        From creative activities to skill-building experiences,
                        explore the activities available at Kudos Learning Academy.
                    </p>
                </div>

                <a class="kla-btn kla-btn-green"
                   href="<?php echo esc_url( home_url('/activities/') ); ?>">
                    View All Activities →
                </a>
            </div>

        </div>
    </section>

    <!-- CTA -->
    <section class="kla-programs-cta">
        <div class="container">

            <div class="kla-programs-cta-inner">
                <div>
                    <div class="kla-eyebrow">LET'S START THE JOURNEY</div>
                    <h2>Ready to Find the Right Program?</h2>
                    <p>
                        Talk to us about your child's learning journey and discover
                        how Kudos can support their next step.
                    </p>
                </div>

                <div class="kla-cta-actions">
                    <a class="kla-btn kla-btn-white"
                       href="tel:+919876543210">
                        ☎ Book A Call →
                    </a>

                    <a class="kla-cta-whatsapp"
                       href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20know%20more%20about%20the%20programs."
                       target="_blank"
                       rel="noopener">
                        WhatsApp Us
                    </a>
                </div>
            </div>

        </div>
    </section>

</main>

<?php get_footer(); ?>
