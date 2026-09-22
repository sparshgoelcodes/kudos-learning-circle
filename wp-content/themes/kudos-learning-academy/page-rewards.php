<?php
/**
 * Rewards Page Template
 * Kudos Learning Academy
 */

get_header();

/*
 * Pull published reward items from the existing Kudos reward system when available.
 * The frontend also provides a graceful fallback if no reward items have been configured yet.
 */
$kla_rewards = array();

if ( function_exists( 'kudos_get_rewards' ) ) {
    $possible_rewards = kudos_get_rewards();

    if ( is_array( $possible_rewards ) ) {
        $kla_rewards = $possible_rewards;
    }
}

/* Fallback presentation items — no points/values are invented. */
$kla_reward_steps = array(
    array(
        'number' => '01',
        'title'  => 'Learn',
        'text'   => 'Take part in learning experiences and build new skills.',
    ),
    array(
        'number' => '02',
        'title'  => 'Achieve',
        'text'   => 'Show effort, participation and progress in everyday learning.',
    ),
    array(
        'number' => '03',
        'title'  => 'Earn Kudos',
        'text'   => 'Teachers can recognise achievements with Kudos Coins.',
    ),
    array(
        'number' => '04',
        'title'  => 'Redeem',
        'text'   => 'Use earned Kudos according to the rewards available to your child.',
    ),
);
?>

<main id="primary" class="site-main kla-rewards-page">

    <!-- Rewards Hero -->
    <section class="kla-rewards-hero">
        <div class="container">
            <div class="kla-rewards-hero-grid">

                <div class="kla-rewards-hero-copy">
                    <div class="kla-eyebrow">RECOGNISE • REWARD • GROW</div>

                    <h1>
                        Every Effort
                        <span>Deserves a Little Kudos.</span>
                    </h1>

                    <p>
                        Our Kudos Rewards system turns everyday achievements into
                        meaningful recognition — helping children see that their
                        effort, participation and progress matter.
                    </p>

                    <div class="kla-rewards-hero-actions">
                        <a class="kla-btn kla-btn-green"
                           href="<?php echo esc_url( home_url('/parent-login/') ); ?>">
                            Parent Login →
                        </a>

                        <a class="kla-rewards-text-link"
                           href="<?php echo esc_url( home_url('/contact/') ); ?>">
                            Talk to Us
                        </a>
                    </div>
                </div>

                <div class="kla-rewards-coin-card">
                    <div class="kla-rewards-coin">
                        <span>★</span>
                    </div>

                    <div class="kla-rewards-coin-label">KUDOS COINS</div>

                    <h2>Learn.<br>Achieve.<br><strong>Earn.</strong></h2>

                    <p>
                        A simple way to make progress visible and celebrate
                        the journey of every child.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- How Rewards Work -->
    <section class="kla-rewards-flow">
        <div class="container">

            <div class="kla-rewards-heading">
                <div>
                    <div class="kla-eyebrow">HOW IT WORKS</div>
                    <h2>The Kudos Journey.</h2>
                </div>

                <p>
                    Children learn, participate, achieve and receive recognition
                    along the way.
                </p>
            </div>

            <div class="kla-rewards-flow-grid">

                <?php foreach ( $kla_reward_steps as $step ) : ?>

                    <article class="kla-reward-step">
                        <div class="kla-reward-step-number">
                            <?php echo esc_html( $step['number'] ); ?>
                        </div>

                        <div class="kla-reward-step-icon">✦</div>

                        <h3><?php echo esc_html( $step['title'] ); ?></h3>

                        <p><?php echo esc_html( $step['text'] ); ?></p>
                    </article>

                <?php endforeach; ?>

            </div>

        </div>
    </section>

    <!-- Recognition -->
    <section class="kla-rewards-recognition">
        <div class="container">

            <div class="kla-rewards-recognition-grid">

                <div class="kla-rewards-recognition-copy">
                    <div class="kla-eyebrow">WHY KUDOS REWARDS</div>

                    <h2>Recognition That Builds Confidence.</h2>

                    <p>
                        Rewards are not just about what a child receives.
                        They help children understand that consistency, effort,
                        participation and achievement are worth celebrating.
                    </p>

                    <div class="kla-rewards-points">

                        <div class="kla-rewards-point">
                            <span>✓</span>
                            <div>
                                <h3>Celebrate Progress</h3>
                                <p>Make positive moments visible throughout the learning journey.</p>
                            </div>
                        </div>

                        <div class="kla-rewards-point">
                            <span>✓</span>
                            <div>
                                <h3>Encourage Participation</h3>
                                <p>Recognise children for getting involved and giving their best.</p>
                            </div>
                        </div>

                        <div class="kla-rewards-point">
                            <span>✓</span>
                            <div>
                                <h3>Keep Parents Connected</h3>
                                <p>Parents can follow their child's performance and Kudos history through the portal.</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="kla-rewards-recognition-card">
                    <div class="kla-rewards-card-top">
                        <span>KUDOS</span>
                        <span>✦</span>
                    </div>

                    <div class="kla-rewards-card-coin">K</div>

                    <h3>Small Wins.<br>Big Confidence.</h3>

                    <p>
                        A positive recognition system designed around the child's
                        learning journey.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- Reward Catalogue -->
    <section class="kla-rewards-catalogue">
        <div class="container">

            <div class="kla-rewards-heading">
                <div>
                    <div class="kla-eyebrow">REWARD CATALOGUE</div>
                    <h2>Explore Available Rewards.</h2>
                </div>

                <p>
                    Rewards can be configured by the academy and made available
                    for redemption through the parent portal.
                </p>
            </div>

            <?php if ( ! empty( $kla_rewards ) ) : ?>

                <div class="kla-rewards-catalogue-grid">

                    <?php foreach ( $kla_rewards as $reward ) : ?>

                        <?php
                        $reward_name = '';
                        $reward_description = '';

                        if ( is_object( $reward ) ) {
                            $reward_name = isset( $reward->name ) ? $reward->name : '';
                            $reward_description = isset( $reward->description ) ? $reward->description : '';
                        } elseif ( is_array( $reward ) ) {
                            $reward_name = isset( $reward['name'] ) ? $reward['name'] : '';
                            $reward_description = isset( $reward['description'] ) ? $reward['description'] : '';
                        }
                        ?>

                        <?php if ( $reward_name ) : ?>
                            <article class="kla-reward-catalogue-card">
                                <div class="kla-reward-catalogue-icon">★</div>
                                <h3><?php echo esc_html( $reward_name ); ?></h3>

                                <?php if ( $reward_description ) : ?>
                                    <p><?php echo esc_html( $reward_description ); ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

            <?php else : ?>

                <div class="kla-rewards-empty">
                    <div class="kla-rewards-empty-icon">✦</div>
                    <h3>Rewards Are Coming Soon.</h3>
                    <p>
                        The academy can add and manage rewards from the admin
                        reward system. Available rewards will appear here.
                    </p>
                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- Parent Portal -->
    <section class="kla-rewards-parent">
        <div class="container">

            <div class="kla-rewards-parent-box">

                <div>
                    <div class="kla-eyebrow">FOR PARENTS</div>
                    <h2>Stay Connected With Your Child's Progress.</h2>
                    <p>
                        Log in to view Kudos Coins, performance, achievement history,
                        notifications and available redemptions.
                    </p>
                </div>

                <a class="kla-btn kla-btn-green"
                   href="<?php echo esc_url( home_url('/parent-login/') ); ?>">
                    Parent Login →
                </a>

            </div>

        </div>
    </section>

    <!-- CTA -->
    <section class="kla-rewards-cta">
        <div class="container">

            <div class="kla-rewards-cta-inner">
                <div>
                    <div class="kla-eyebrow">MAKE EVERY ACHIEVEMENT COUNT</div>
                    <h2>Let's Celebrate Your Child's Journey.</h2>
                    <p>
                        Talk to us to learn more about Kudos Learning Academy,
                        our programs and our recognition system.
                    </p>
                </div>

                <div class="kla-cta-actions">
                    <a class="kla-btn kla-btn-white"
                       href="tel:+919876543210">
                        ☎ Book A Call →
                    </a>

                    <a class="kla-cta-whatsapp"
                       href="https://wa.me/919876543210?text=Hello%20Kudos%20Learning%20Academy%2C%20I%20would%20like%20to%20know%20more%20about%20Kudos%20Rewards."
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
