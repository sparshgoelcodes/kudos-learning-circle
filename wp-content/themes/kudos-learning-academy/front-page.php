<?php get_header(); ?>

<main id="primary" class="kla-site-main">

    <div class="kla-homepage">

        <!-- =====================================================
             TOP BAR
        ====================================================== -->
        <div class="kla-topbar">
            <div class="kla-container kla-topbar-inner">

                <div>📍 123 Learning Lane, New Delhi</div>

                <div>
                    ☎
                    <a href="tel:+919876543210">
                        +91 98765 43210
                    </a>
                </div>

                <div>
                    ✉
                    <a href="mailto:hello@kudosacademy.in">
                        hello@kudosacademy.in
                    </a>
                </div>

                <div class="kla-login-links">
                    <a href="<?php echo esc_url( home_url('/parent-login/') ); ?>">
                        Parent Login
                    </a>

                    <span>|</span>

                    <a href="<?php echo esc_url( home_url('/teacher-login/') ); ?>">
                        Teacher Login
                    </a>

                    <span>|</span>

                    <a href="<?php echo esc_url( wp_login_url( home_url('/admin-dashboard/') ) ); ?>">
                        Admin Login
                    </a>
                </div>

            </div>
        </div>


        <!-- =====================================================
             HEADER
        ====================================================== -->
        <header class="kla-header">

            <div class="kla-container kla-header-inner">

                <a class="kla-brand" href="<?php echo esc_url( home_url('/') ); ?>">

                    <span class="kla-brand-mark">🌱</span>

                    <span class="kla-brand-text">
                        <strong>Kudos</strong>
                        <small>Learning Academy</small>
                    </span>

                </a>


                <button
                    class="kla-menu-toggle"
                    type="button"
                    aria-label="Open menu"
                    aria-expanded="false"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>


                <nav class="kla-nav">

                    <a class="active" href="<?php echo esc_url( home_url('/') ); ?>">
                        Home
                    </a>

                    <a href="#about">About</a>

                    <a href="#programs">Programs</a>

                    <a href="#activities">Activities</a>

                    <a href="#rewards">Rewards</a>

                    <a href="<?php echo esc_url( home_url('/contact/') ); ?>">Contact</a>

                </nav>


                <a class="kla-btn kla-btn-green"
   href="<?php echo esc_url( home_url('/contact/') ); ?>">
    Talk to Us →
</a>

            </div>

        </header>


        <!-- =====================================================
             MOBILE NAVIGATION
        ====================================================== -->
        <div class="kla-mobile-nav">

            <a href="<?php echo esc_url( home_url('/') ); ?>">
                Home
            </a>

            <a href="#about">About</a>

            <a href="#programs">Programs</a>

            <a href="#activities">Activities</a>

            <a href="#rewards">Rewards</a>

            <a href="<?php echo esc_url( home_url('/contact/') ); ?>">Contact</a>

            <div class="kla-mobile-login">

                <a href="<?php echo esc_url( home_url('/parent-login/') ); ?>">
                    Parent Login
                </a>

                <a href="<?php echo esc_url( home_url('/teacher-login/') ); ?>">
                    Teacher Login
                </a>

                <a href="<?php echo esc_url( wp_login_url( home_url('/admin-dashboard/') ) ); ?>">
                    Admin Login
                </a>

            </div>

            <a class="kla-btn kla-btn-green" href="<?php echo esc_url( home_url('/contact/') ); ?>">
                Enquire Now →
            </a>

        </div>


        <!-- =====================================================
             HERO
        ====================================================== -->
        <section class="kla-hero">

            <div class="kla-container kla-hero-grid">

                <div class="kla-hero-copy">

                    <span class="kla-eyebrow kla-hero-pill">
                        Nurturing Young Minds Since 2010
                    </span>

                    <h1>
                        Where Little<br>
                        Minds Grow Big<br>
                        Dreams
                    </h1>

                    <p>
                        A joyful learning academy where children learn through
                        play, science, creativity and caring guidance — while
                        every effort can earn Kudos rewards.
                    </p>


                    <div class="kla-actions">

                        <a class="kla-btn kla-btn-green" href="<?php echo esc_url( home_url('/contact/') ); ?>">
                            ▣ Book A Call
                        </a>

                        <a class="kla-btn kla-btn-outline" href="#programs">
                            🎓 View Programs
                        </a>

                    </div>


                    <div class="kla-hero-trust">

                        <div>
                            <strong>350+</strong>
                            <span>Happy Children</span>
                        </div>

                        <div>
                            <strong>20+</strong>
                            <span>Programs</span>
                        </div>

                        <div>
                            <strong>100%</strong>
                            <span>Learning Focus</span>
                        </div>

                    </div>

                </div>


                <div class="kla-hero-art">

                    <div class="kla-badge kla-badge-one">
                        <strong>
                            Small<br>
                            Efforts
                        </strong>
                        <span>Big Rewards</span>
                    </div>


                    <div class="kla-badge kla-badge-two">
                        <strong>Happy Learners</strong>
                        <span>Brighter Futures</span>
                    </div>


                    <div class="kla-hero-image"></div>


                    <div class="kla-hero-stack">

                        <span>Learn</span>
                        <span>Play</span>
                        <span>Achieve</span>
                        <span>Earn Kudos</span>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FEATURES
        ====================================================== -->
        <section class="kla-features">

            <div class="kla-container kla-feature-grid">

                <div>
                    🌿
                    <strong>Holistic Learning</strong>
                    <small>Learn beyond textbooks</small>
                </div>

                <div>
                    ⭐
                    <strong>Recognition & Rewards</strong>
                    <small>Celebrate every effort</small>
                </div>

                <div>
                    👥
                    <strong>Expert Educators</strong>
                    <small>Guidance that cares</small>
                </div>

                <div>
                    ♡
                    <strong>Safe & Nurturing</strong>
                    <small>A place to belong</small>
                </div>

            </div>

        </section>


        <!-- =====================================================
             ABOUT
        ====================================================== -->
        <section id="about" class="kla-section kla-about">

            <div class="kla-container kla-two-col">

                <div>

                    <span class="kla-eyebrow">
                        ABOUT KUDOS
                    </span>

                    <h2 class="kla-title">
                        A Place to Learn,<br>
                        Grow and Belong
                    </h2>

                    <p>
                        Kudos Learning Academy is a modern learning space
                        dedicated to helping children build knowledge,
                        confidence and real-world skills.
                    </p>

                    <p>
                        Our nurturing environment and experienced educators
                        inspire curiosity, creativity and confidence while
                        making learning enjoyable.
                    </p>


                    <div class="kla-check-list">

                        <div>✓ Child-focused learning</div>

                        <div>✓ Practical & creative activities</div>

                        <div>✓ Positive recognition system</div>

                    </div>


                   <a class="kla-btn kla-btn-green"
   href="<?php echo esc_url( home_url('/about-us/') ); ?>">
    More About Us →
</a>

                </div>


                <div class="kla-image-card kla-about-image">

                    <div class="kla-image-note">

                        <strong>Learn with joy</strong>

                        <span>Grow with confidence</span>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             PROGRAMS
        ====================================================== -->
        <section id="programs" class="kla-section kla-programs">
<div class="kla-container">

    <div class="kla-section-heading">
        <div>
            <span class="kla-eyebrow">OUR PROGRAMS</span>
            <h2 class="kla-title">Discover Our Learning Programs</h2>
        </div>

        <p>
            Thoughtfully designed learning experiences that help children
            explore, practise and grow.
        </p>
    </div>

    <div class="kla-cards">

        <article class="kla-card">
            <div class="kla-card-image p1"></div>

            <div class="kla-card-body">
                <span class="kla-card-number">01</span>

                <h3>Early Learners</h3>

                <p>
                    Build strong foundations through playful learning,
                    phonics, abacus, creative activities and confidence-building.
                </p>

                <a href="#activities">
                    Explore Activities →
                </a>
            </div>
        </article>


        <article class="kla-card">
            <div class="kla-card-image p2"></div>

            <div class="kla-card-body">
                <span class="kla-card-number">02</span>

                <h3>Creative Explorers</h3>

                <p>
                    Discover creativity through Art n Craft, Sketching,
                    Classical Music, Dance and other expressive activities.
                </p>

                <a href="#activities">
                    Explore Activities →
                </a>
            </div>
        </article>


        <article class="kla-card">
            <div class="kla-card-image p3"></div>

            <div class="kla-card-body">
                <span class="kla-card-number">03</span>

                <h3>Curious Minds</h3>

                <p>
                    Develop problem-solving and logical thinking through
                    AI, Computers / IT, Vedic Maths and Rubik's Cube.
                </p>

                <a href="#activities">
                    Explore Activities →
                </a>
            </div>
        </article>


        <article class="kla-card">
            <div class="kla-card-image p4"></div>

            <div class="kla-card-body">
                <span class="kla-card-number">04</span>

                <h3>Future Ready</h3>

                <p>
                    Build communication, confidence and real-world skills
                    through English Proficiency, Coaching, Mentorship and Workshops.
                </p>

                <a href="#activities">
                    Explore Activities →
                </a>
            </div>
        </article>

    </div>

</div>
</section>


        <!-- =====================================================
             REWARDS
        ====================================================== -->
        <section id="rewards" class="kla-section kla-rewards">

            <div class="kla-container kla-rewards-grid">

                <div>

                    <span class="kla-eyebrow">
                        KUDOS REWARDS
                    </span>

                    <h2 class="kla-title">
                        Every Effort Deserves Recognition
                    </h2>

                    <p>
                        Children can earn Kudos for achievements,
                        participation, attendance and positive behaviour.
                        Parents can use eligible Kudos for academy activities
                        and applicable fee benefits.
                    </p>


                    <div class="kla-reward-highlight">
                        ⭐
                        <strong>
                            Learn. Achieve. Earn Kudos. Grow.
                        </strong>
                    </div>


                    <a class="kla-btn kla-btn-green"
   href="<?php echo esc_url( home_url('/rewards/') ); ?>">
    How Rewards Work →
</a>

                </div>


                <div class="kla-reward-flow">

                    <div class="kla-step">

                        <span>📖</span>

                        <strong>Learn</strong>

                        <small>
                            Join classes and activities.
                        </small>

                    </div>


                    <div class="kla-step">

                        <span>🏆</span>

                        <strong>Achieve</strong>

                        <small>
                            Show effort and progress.
                        </small>

                    </div>


                    <div class="kla-step">

                        <span>⭐</span>

                        <strong>Earn Kudos</strong>

                        <small>
                            Good effort earns points.
                        </small>

                    </div>


                    <div class="kla-step">

                        <span>🎁</span>

                        <strong>Redeem</strong>

                        <small>
                            Activities, workshops or eligible benefits.
                        </small>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             ACTIVITIES
        ====================================================== -->
        <!-- =====================================================
     ACTIVITIES
====================================================== -->
<section id="activities" class="kla-section kla-activities">

    <?php
    $kla_activities = new WP_Query(
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

    <div class="kla-container">

        <div class="kla-section-heading kla-activities-heading">
            <div>
                <span class="kla-eyebrow">LEARN • CREATE • PLAY • DISCOVER</span>
                <h2 class="kla-title">Explore Our Activities</h2>
            </div>

            <p>
                Fun, creative and skill-building activities designed to help
                every child learn something new every day.
            </p>
        </div>

        <?php if ( $kla_activities->have_posts() ) : ?>

            <div class="kla-activities-slider">
                <div class="kla-activities-track">

                    <?php while ( $kla_activities->have_posts() ) : $kla_activities->the_post(); ?>
                        <?php
                        $activity_id    = get_the_ID();
                        $activity_title = get_the_title();
                        $activity_url   = get_permalink();
                        $activity_image = has_post_thumbnail()
                            ? get_the_post_thumbnail_url( $activity_id, 'large' )
                            : '';
                        ?>

                        <a class="kla-activity-card"
                           href="<?php echo esc_url( $activity_url ); ?>">

                            <div class="kla-activity-image">
                                <?php if ( $activity_image ) : ?>
                                    <img
                                        src="<?php echo esc_url( $activity_image ); ?>"
                                        alt="<?php echo esc_attr( $activity_title ); ?>"
                                        loading="lazy"
                                    >
                                <?php else : ?>
                                    <div class="kla-activity-placeholder">
                                        <span>★</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="kla-activity-name">
                                <?php echo esc_html( $activity_title ); ?>
                            </div>

                        </a>

                    <?php endwhile; ?>

                    <?php $kla_activities->rewind_posts(); ?>

                    <?php while ( $kla_activities->have_posts() ) : $kla_activities->the_post(); ?>
                        <?php
                        $activity_id    = get_the_ID();
                        $activity_title = get_the_title();
                        $activity_url   = get_permalink();
                        $activity_image = has_post_thumbnail()
                            ? get_the_post_thumbnail_url( $activity_id, 'large' )
                            : '';
                        ?>

                        <a class="kla-activity-card"
                           href="<?php echo esc_url( $activity_url ); ?>"
                           aria-hidden="true"
                           tabindex="-1">

                            <div class="kla-activity-image">
                                <?php if ( $activity_image ) : ?>
                                    <img
                                        src="<?php echo esc_url( $activity_image ); ?>"
                                        alt=""
                                        loading="lazy"
                                    >
                                <?php else : ?>
                                    <div class="kla-activity-placeholder">
                                        <span>★</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="kla-activity-name">
                                <?php echo esc_html( $activity_title ); ?>
                            </div>

                        </a>

                    <?php endwhile; ?>

                </div>
            </div>

            <div class="kla-activities-bottom">
                <a
                    class="kla-btn kla-btn-green"
                    href="<?php echo esc_url( home_url( '/activities/' ) ); ?>"
                >
                    View All Activities →
                </a>
            </div>

        <?php else : ?>

            <div class="kla-no-activities">
                Activities will be added soon.
            </div>

        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

    </div>

</section>


<!-- =====================================================
             STATS
        ====================================================== -->
        <section class="kla-stats">

            <div class="kla-container kla-stat-grid">

                <div>
                    <strong>350+</strong>
                    <span>Happy Children</span>
                </div>

                <div>
                    <strong>25+</strong>
                    <span>Caring Educators</span>
                </div>

                <div>
                    <strong>20+</strong>
                    <span>Learning Programs</span>
                </div>

                <div>
                    <strong>95%</strong>
                    <span>Parent Satisfaction</span>
                </div>

            </div>

        </section>


        <!-- =====================================================
             TESTIMONIALS
        ====================================================== -->
        <section class="kla-section kla-testimonials">

            <div class="kla-container">

                <div class="kla-section-heading">

                    <div>

                        <span class="kla-eyebrow">
                            PARENT STORIES
                        </span>

                        <h2 class="kla-title">
                            Loved by Families Everywhere
                        </h2>

                    </div>

                    <p>
                        What families appreciate about the Kudos learning
                        experience.
                    </p>

                </div>


                <div class="kla-quotes">

                    <article class="kla-quote">

                        <b>★★★★★</b>

                        <p>
                            “The teachers are warm and attentive. Our child
                            has become much more confident.”
                        </p>

                        <strong>
                            — Parent of Aarav
                        </strong>

                    </article>


                    <article class="kla-quote">

                        <b>★★★★★</b>

                        <p>
                            “The Kudos rewards make achievement feel exciting
                            and positive.”
                        </p>

                        <strong>
                            — Parent of Anaya
                        </strong>

                    </article>


                    <article class="kla-quote">

                        <b>★★★★★</b>

                        <p>
                            “The activities make learning something our child
                            genuinely looks forward to.”
                        </p>

                        <strong>
                            — Parent of Vihaan
                        </strong>

                    </article>

                </div>

            </div>

        </section>


        <!-- =====================================================
             CONTACT CTA
        ====================================================== -->
        <section id="contact" class="kla-cta">

            <div class="kla-container kla-cta-inner">

                <div>

                    <span class="kla-eyebrow">
                        READY TO GET STARTED?
                    </span>

                    <h2>
                        Give Your Child a Bright,<br>
                        Joyful Start
                    </h2>

                    <p>
                        Meet our educators and discover how your child can
                        learn, grow and earn Kudos.
                    </p>

                </div>


                <div class="kla-cta-actions">

                    <a class="kla-btn kla-btn-white"
                       href="tel:+919876543210">
                        ☎ Book A Call →
                    </a>

                    <a class="kla-cta-whatsapp"
                       href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20book%20a%20call."
                       target="_blank"
                       rel="noopener">
                        WhatsApp Us
                    </a>

                </div>

            </div>

        </section>


    </div><!-- .kla-homepage -->

</main><!-- #primary -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const button = document.querySelector('.kla-menu-toggle');
    const menu = document.querySelector('.kla-mobile-nav');

    if (!button || !menu) {
        return;
    }

    button.addEventListener('click', function () {

        const isOpen =
            button.getAttribute('aria-expanded') === 'true';

        button.setAttribute(
            'aria-expanded',
            isOpen ? 'false' : 'true'
        );

        menu.classList.toggle('is-open', !isOpen);

    });


    menu.querySelectorAll('a').forEach(function (link) {

        link.addEventListener('click', function () {

            button.setAttribute(
                'aria-expanded',
                'false'
            );

            menu.classList.remove('is-open');

        });

    });

});
</script>

<?php get_footer(); ?>