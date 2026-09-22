<?php
/**
 * Contact Page
 * Kudos Learning Academy
 */

get_header();
?>

<!-- HEADER -->
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

            <a href="<?php echo esc_url( home_url('/') ); ?>">
                Home
            </a>

            <a href="<?php echo esc_url( home_url('/about-us/') ); ?>">
                About Us
            </a>

            <a href="<?php echo esc_url( home_url('/programs/') ); ?>">
                Programs
            </a>

            <a href="<?php echo esc_url( home_url('/activities/') ); ?>">
                Activities
            </a>

            <a href="<?php echo esc_url( home_url('/rewards/') ); ?>">
                Rewards
            </a>

            <a class="active"
               href="<?php echo esc_url( home_url('/contact/') ); ?>">
                Contact
            </a>

        </nav>

        <a class="kla-btn kla-btn-green"
           href="#contact-options">
            Enquire Now →
        </a>

    </div>

</header>


<!-- MOBILE NAV -->
<div class="kla-mobile-nav">

    <a href="<?php echo esc_url( home_url('/') ); ?>">
        Home
    </a>

    <a href="<?php echo esc_url( home_url('/about-us/') ); ?>">
        About Us
    </a>

    <a href="<?php echo esc_url( home_url('/programs/') ); ?>">
        Programs
    </a>

    <a href="<?php echo esc_url( home_url('/activities/') ); ?>">
        Activities
    </a>

    <a href="<?php echo esc_url( home_url('/rewards/') ); ?>">
        Rewards
    </a>

    <a href="<?php echo esc_url( home_url('/contact/') ); ?>">
        Contact
    </a>

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

    <a class="kla-btn kla-btn-green"
       href="#contact-options">
        Enquire Now →
    </a>

</div>


<main id="primary" class="site-main kla-contact-page">


    <!-- HERO -->
    <section class="kla-contact-new-hero">

        <div class="kla-container">

            <div class="kla-contact-new-grid">

                <div>

                    <span class="kla-eyebrow">
                        GET IN TOUCH
                    </span>

                    <h1>
                        We'd Love to
                        <span>Hear From You.</span>
                    </h1>

                    <p>
                        Have questions about our programs, activities,
                        admissions or Kudos Rewards? Our team is here to help.
                    </p>

                    <div class="kla-contact-new-actions">

                        <a class="kla-btn kla-btn-green"
                           href="tel:+919876543210">
                            ☎ Book a Call →
                        </a>

                        <a class="kla-contact-new-whatsapp"
                           href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20enquire%20about%20your%20programs."
                           target="_blank"
                           rel="noopener">
                            💬 WhatsApp Us
                        </a>

                    </div>

                </div>


                <div class="kla-contact-new-card">

                    <div class="kla-contact-new-icon">
                        🌱
                    </div>

                    <span>
                        KUDOS LEARNING ACADEMY
                    </span>

                    <h2>
                        Let's Start a Conversation.
                    </h2>

                    <p>
                        We're happy to help you find the right learning
                        experience for your child.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- CONTACT INFORMATION -->
    <section class="kla-contact-info-section">

        <div class="kla-container">

            <div class="kla-contact-info-heading">

                <span class="kla-eyebrow">
                    CONTACT INFORMATION
                </span>

                <h2>
                    We're Here to Help
                </h2>

                <p>
                    Reach out to us through any of the options below.
                </p>

            </div>


            <div class="kla-contact-info-grid">


                <div class="kla-contact-info-card">

                    <div class="kla-contact-info-icon">
                        ☎
                    </div>

                    <span>
                        CALL US
                    </span>

                    <h3>
                        +91 98765 43210
                    </h3>

                    <a href="tel:+919876543210">
                        Call Now →
                    </a>

                </div>


                <div class="kla-contact-info-card">

                    <div class="kla-contact-info-icon">
                        ✉
                    </div>

                    <span>
                        EMAIL US
                    </span>

                    <h3>
                        hello@kudoslearningacademy.com
                    </h3>

                    <a href="mailto:hello@kudoslearningacademy.com">
                        Send Email →
                    </a>

                </div>


                <div class="kla-contact-info-card">

                    <div class="kla-contact-info-icon">
                        ⌂
                    </div>

                    <span>
                        VISIT US
                    </span>

                    <h3>
                        Kudos Learning Academy
                    </h3>

                    <p>
                        Address details can be added here.
                    </p>

                </div>


                <div class="kla-contact-info-card">

                    <div class="kla-contact-info-icon">
                        ◷
                    </div>

                    <span>
                        WORKING HOURS
                    </span>

                    <h3>
                        Monday – Saturday
                    </h3>

                    <p>
                        9:00 AM – 6:00 PM
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- MAIN CALL / WHATSAPP CTA -->
    <section id="contact-options"
             class="kla-contact-options-section">

        <div class="kla-container">

            <div class="kla-contact-options-box">

                <span class="kla-eyebrow">
                    LET'S CONNECT
                </span>

                <h2>
                    Have a Question? Let's Talk.
                </h2>

                <p>
                    Our team is happy to help you with programs, activities,
                    admissions, rewards and anything else you would like to know.
                </p>

                <div class="kla-contact-options-buttons">

                    <a class="kla-btn kla-btn-green"
                       href="tel:+919876543210">
                        ☎ Book a Call →
                    </a>

                    <a class="kla-contact-new-whatsapp"
                       href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20enquire%20about%20your%20programs."
                       target="_blank"
                       rel="noopener">
                        💬 WhatsApp Us
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- QUICK LINKS -->
    <section class="kla-contact-explore">

        <div class="kla-container">

            <div class="kla-contact-info-heading">

                <span class="kla-eyebrow">
                    EXPLORE KUDOS
                </span>

                <h2>
                    Looking for Something Else?
                </h2>

            </div>


            <div class="kla-contact-explore-grid">

                <a href="<?php echo esc_url( home_url('/programs/') ); ?>">
                    <span>PROGRAMS</span>
                    <strong>Explore Programs →</strong>
                </a>

                <a href="<?php echo esc_url( home_url('/activities/') ); ?>">
                    <span>ACTIVITIES</span>
                    <strong>Explore Activities →</strong>
                </a>

                <a href="<?php echo esc_url( home_url('/rewards/') ); ?>">
                    <span>REWARDS</span>
                    <strong>Discover Rewards →</strong>
                </a>

            </div>

        </div>

    </section>


    <!-- FINAL CTA -->
    <section class="kla-contact-final">

        <div class="kla-container">

            <div class="kla-contact-final-inner">

                <div>

                    <span class="kla-eyebrow">
                        KUDOS LEARNING ACADEMY
                    </span>

                    <h2>
                        Let's Help Your Child
                        Learn, Grow & Shine.
                    </h2>

                    <p>
                        Get in touch with our team and discover the right
                        learning experience for your child.
                    </p>

                </div>


                <div class="kla-contact-final-buttons">

                    <a class="kla-btn kla-btn-white"
                       href="tel:+919876543210">
                        ☎ Book a Call →
                    </a>

                    <a class="kla-contact-final-whatsapp"
                       href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20know%20more."
                       target="_blank"
                       rel="noopener">
                        💬 WhatsApp Us
                    </a>

                </div>

            </div>

        </div>

    </section>

</main>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggle = document.querySelector('.kla-menu-toggle');
    const mobileNav = document.querySelector('.kla-mobile-nav');

    if (!toggle || !mobileNav) {
        return;
    }

    toggle.addEventListener('click', function () {

        const isOpen = mobileNav.classList.toggle('is-open');

        toggle.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });

    mobileNav.querySelectorAll('a').forEach(function (link) {

        link.addEventListener('click', function () {
            mobileNav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });

    });

});
</script>


<?php get_footer(); ?>