<?php

/* =========================================================
   KUDOS TEACHER ROLE
   ========================================================= */

function kudos_create_teacher_role() {

    if ( ! get_role( 'kudos_teacher' ) ) {

        add_role(
            'kudos_teacher',
            'Kudos Teacher',
            array(
                'read' => true,
            )
        );

    }
}

add_action(
    'init',
    'kudos_create_teacher_role'
);


/* =========================================================
   TEACHER LOGIN PAGE
   ========================================================= */

function kudos_teacher_login_form() {

    /*
     * If teacher is already logged in,
     * send them directly to dashboard.
     */

    if ( is_user_logged_in() ) {

        $user = wp_get_current_user();

        if ( in_array( 'kudos_teacher', $user->roles, true ) ) {

            wp_safe_redirect(
                home_url( '/teacher-dashboard/' )
            );

            exit;
        }
    }

    ob_start();

    ?>

    <div class="kudos-teacher-login">

        <div class="kudos-login-box">

            <div class="kudos-login-logo">
                🎓
            </div>

            <h1>
                Teacher Login
            </h1>

            <p>
                Login to access your teacher dashboard.
            </p>

            <?php

            wp_login_form(
                array(

                    'redirect' => home_url(
                        '/teacher-dashboard/'
                    ),

                    'label_username' =>
                        'Username or Email',

                    'label_password' =>
                        'Password',

                    'label_log_in' =>
                        'Login',

                    'remember' =>
                        true,

                    'form_id' =>
                        'kudos-teacher-login-form',

                )
            );

            ?>

        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'kudos_teacher_login',
    'kudos_teacher_login_form'
);


/* =========================================================
   TEACHER DASHBOARD
   ========================================================= */

function kudos_teacher_dashboard() {

    /* ---------------------------------------------------------
       LOGIN CHECK
       --------------------------------------------------------- */

    if ( ! is_user_logged_in() ) {

        return '
        <div class="kudos-dashboard-message">

            <h2>
                Teacher Login Required
            </h2>

            <p>
                Please login to access the teacher dashboard.
            </p>

            <a
                class="teacher-dashboard-login-button"
                href="' .
                esc_url(
                    home_url( '/teacher-login/' )
                ) .
            '">
                Teacher Login
            </a>

        </div>';
    }


    /* ---------------------------------------------------------
       CURRENT USER
       --------------------------------------------------------- */

    $user = wp_get_current_user();


    /* ---------------------------------------------------------
       TEACHER ACCESS CHECK
       --------------------------------------------------------- */

    if (
        ! in_array(
            'kudos_teacher',
            $user->roles,
            true
        )
    ) {

        return '
        <div class="kudos-dashboard-message">

            <h2>
                Access Restricted
            </h2>

            <p>
                This dashboard is only available to teachers.
            </p>

        </div>';
    }


    /* ---------------------------------------------------------
       STUDENT COUNT
       --------------------------------------------------------- */

    $student_counts = wp_count_posts(
        'kudos_student'
    );

    $total_students = isset(
        $student_counts->publish
    )
        ? (int) $student_counts->publish
        : 0;


    /* ---------------------------------------------------------
       PERFORMANCE COUNT
       --------------------------------------------------------- */

    $performance_counts = wp_count_posts(
        'kudos_performance'
    );

    $total_performance = isset(
        $performance_counts->publish
    )
        ? (int) $performance_counts->publish
        : 0;


    /* ---------------------------------------------------------
       RECENT PERFORMANCE
       --------------------------------------------------------- */

    $recent_performance = new WP_Query(
        array(

            'post_type' =>
                'kudos_performance',

            'post_status' =>
                'publish',

            'posts_per_page' =>
                5,

            'orderby' =>
                'date',

            'order' =>
                'DESC',

        )
    );


    /* ---------------------------------------------------------
       LOGOUT URL
       --------------------------------------------------------- */

    $logout_url = wp_logout_url(
        home_url( '/teacher-login/' )
    );


    ob_start();

    ?>

    <div class="kudos-teacher-dashboard">


        <!-- =================================================
             TEACHER HEADER
             ================================================= -->

        <header class="teacher-dashboard-header">

            <div class="teacher-dashboard-brand">

                <div class="teacher-brand-icon">
                    🌱
                </div>

                <div>

                    <div class="teacher-brand-name">
                        Kudos
                    </div>

                    <div class="teacher-brand-subtitle">
                        Learning Academy
                    </div>

                </div>

            </div>


            <div class="teacher-dashboard-user">

                <div class="teacher-user-details">

                    <div class="teacher-user-avatar">
                        👤
                    </div>

                    <div>

                        <strong>
                            <?php
                            echo esc_html(
                                $user->display_name
                            );
                            ?>
                        </strong>

                        <span>
                            Teacher
                        </span>

                    </div>

                </div>


                <a
                    href="<?php
                    echo esc_url(
                        $logout_url
                    );
                    ?>"
                    class="teacher-logout"
                >
                    Logout
                </a>

            </div>

        </header>


        <!-- =================================================
             MAIN DASHBOARD
             ================================================= -->

        <div class="teacher-dashboard-container">


            <!-- =================================================
                 WELCOME
                 ================================================= -->

            <section class="teacher-welcome">

                <span class="teacher-badge">
                    👨‍🏫 Teacher Dashboard
                </span>

                <h1>

                    Welcome,
                    <?php
                    echo esc_html(
                        $user->display_name
                    );
                    ?>
                    👋

                </h1>

                <p>
                    Manage your students, performance,
                    attendance and achievements.
                </p>

            </section>


            <!-- =================================================
                 STATISTICS
                 ================================================= -->

            <section class="teacher-stats-grid">


                <!-- STUDENTS -->

                <div class="teacher-stat-card">

                    <div class="teacher-stat-icon">
                        👨‍🎓
                    </div>

                    <div>

                        <span>
                            My Students
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                $total_students
                            );
                            ?>
                        </strong>

                        <small>
                            Active student records
                        </small>

                    </div>

                </div>


                <!-- KUDOS -->

                <?php
                /*
                 * Calculate total Kudos awarded by this teacher.
                 */
                $teacher_kudos_total = 0;

                $teacher_kudos_query = new WP_Query(
                    array(
                        'post_type'      => 'kudos_transaction',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'meta_query'     => array(
                            array(
                                'key'     => '_kla_transaction_teacher_id',
                                'value'   => $user->ID,
                                'compare' => '=',
                                'type'    => 'NUMERIC',
                            ),
                            array(
                                'key'     => '_kla_transaction_type',
                                'value'   => 'award',
                                'compare' => '=',
                            ),
                        ),
                    )
                );

                if ( ! empty( $teacher_kudos_query->posts ) ) {
                    foreach ( $teacher_kudos_query->posts as $teacher_transaction_id ) {
                        $teacher_kudos_total += absint(
                            get_post_meta(
                                $teacher_transaction_id,
                                '_kla_transaction_points',
                                true
                            )
                        );
                    }
                }
                ?>

                <div class="teacher-stat-card">

                    <div class="teacher-stat-icon">
                        ⭐
                    </div>

                    <div>

                        <span>
                            Kudos Awarded
                        </span>

                        <strong>
                            <?php echo esc_html( $teacher_kudos_total ); ?>
                        </strong>

                        <small>
                            Total Kudos awarded by you
                        </small>

                    </div>

                </div>


                <!-- ATTENDANCE -->

                <div class="teacher-stat-card">

                    <div class="teacher-stat-icon">
                        📅
                    </div>

                    <div>

                        <span>
                            Attendance
                        </span>

                        <strong>
                            —
                        </strong>

                        <small>
                            Attendance system coming next
                        </small>

                    </div>

                </div>


                <!-- PERFORMANCE -->

                <div class="teacher-stat-card">

                    <div class="teacher-stat-icon">
                        📊
                    </div>

                    <div>

                        <span>
                            Performance Records
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                $total_performance
                            );
                            ?>
                        </strong>

                        <small>
                            Records available
                        </small>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 QUICK ACTIONS
                 ================================================= -->

            <section class="teacher-dashboard-section">

                <div class="teacher-section-heading">

                    <span>
                        Teacher Tools
                    </span>

                    <h2>
                        Quick Actions
                    </h2>

                </div>


                <div class="teacher-actions-grid">


                    <!-- AWARD KUDOS -->

                    <a
                        href="#award-kudos"
                        class="teacher-action-card action-highlight teacher-action-link"
                    >

                        <div class="teacher-action-icon">
                            ⭐
                        </div>

                        <div>

                            <h3>
                                Award Kudos
                            </h3>

                            <p>
                                Recognize a student's
                                effort and achievement.
                            </p>

                            <span class="action-coming">
                                Award Kudos →
                            </span>

                        </div>

                    </a>


                    <!-- PERFORMANCE -->

                    <div class="teacher-action-card">

                        <div class="teacher-action-icon">
                            📊
                        </div>

                        <div>

                            <h3>
                                Performance
                            </h3>

                            <p>
                                View and update student
                                performance records.
                            </p>

                            <span class="action-coming">
                                View Performance →
                            </span>

                        </div>

                    </div>


                    <!-- ATTENDANCE -->

                    <div class="teacher-action-card">

                        <div class="teacher-action-icon">
                            📅
                        </div>

                        <div>

                            <h3>
                                Attendance
                            </h3>

                            <p>
                                Record daily attendance
                                for your students.
                            </p>

                            <span class="action-coming">
                                Coming Next →
                            </span>

                        </div>

                    </div>


                    <!-- STUDENTS -->

                    <div class="teacher-action-card">

                        <div class="teacher-action-icon">
                            👨‍🎓
                        </div>

                        <div>

                            <h3>
                                My Students
                            </h3>

                            <p>
                                View students and their
                                academic information.
                            </p>

                            <span class="action-coming">
                                View Students →
                            </span>

                        </div>

                    </div>


                </div>

            </section>



            <!-- =================================================
                 AWARD KUDOS
                 ================================================= -->

            <section
                class="teacher-dashboard-section teacher-award-section"
                id="award-kudos"
            >

                <div class="teacher-section-heading">

                    <span>
                        Student Recognition
                    </span>

                    <h2>
                        Award Kudos
                    </h2>

                </div>

                <?php
                $award_message = '';

                if ( isset( $_GET['kudos_award'] ) ) {

                    $award_status = sanitize_key(
                        wp_unslash(
                            $_GET['kudos_award']
                        )
                    );

                    if ( $award_status === 'success' ) {

                        $awarded_points = isset( $_GET['points'] )
                            ? absint( $_GET['points'] )
                            : 0;

                        $awarded_student = isset( $_GET['student'] )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $_GET['student']
                                )
                            )
                            : '';

                        $award_message =
                            '<div class="teacher-award-notice success">'
                            . '⭐ '
                            . '<strong>Kudos awarded successfully.</strong> '
                            . esc_html( $awarded_student )
                            . ' earned +'
                            . esc_html( $awarded_points )
                            . ' Kudos.'
                            . '</div>';

                    } elseif ( $award_status === 'error' ) {

                        $award_error = isset( $_GET['message'] )
                            ? sanitize_text_field(
                                wp_unslash(
                                    $_GET['message']
                                )
                            )
                            : 'Unable to award Kudos.';

                        $award_message =
                            '<div class="teacher-award-notice error">'
                            . esc_html( $award_error )
                            . '</div>';
                    }
                }

                echo $award_message;
                ?>

                <div class="teacher-award-card">

                    <form
                        method="post"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                        class="teacher-award-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="kla_teacher_award_kudos"
                        />

                        <?php
                        wp_nonce_field(
                            'kla_teacher_award_kudos',
                            'kla_teacher_award_nonce'
                        );
                        ?>

                        <div class="teacher-form-grid">

                            <div class="teacher-form-field">

                                <label for="kla_award_student">
                                    Student
                                </label>

                                <select
                                    id="kla_award_student"
                                    name="student_id"
                                    required
                                >

                                    <option value="">
                                        Select Student
                                    </option>

                                    <?php

                                    $award_students = get_posts(
                                        array(
                                            'post_type'      => 'kudos_student',
                                            'post_status'    => 'publish',
                                            'posts_per_page' => -1,
                                            'orderby'        => 'meta_value',
                                            'meta_key'       => '_kudos_student_name',
                                            'order'          => 'ASC',
                                        )
                                    );

                                    foreach ( $award_students as $award_student ) :

                                        $award_student_name =
                                            get_post_meta(
                                                $award_student->ID,
                                                '_kudos_student_name',
                                                true
                                            );

                                        if ( ! $award_student_name ) {
                                            $award_student_name =
                                                $award_student->post_title;
                                        }

                                        $award_parent_id =
                                            get_post_meta(
                                                $award_student->ID,
                                                '_kudos_student_parent',
                                                true
                                            );

                                        $award_parent_name = '';

                                        if ( $award_parent_id ) {
                                            $award_parent_name =
                                                get_post_meta(
                                                    $award_parent_id,
                                                    '_kudos_parent_name',
                                                    true
                                                );
                                        }

                                        ?>

                                        <option
                                            value="<?php echo esc_attr( $award_student->ID ); ?>"
                                        >
                                            <?php
                                            echo esc_html(
                                                $award_student_name
                                            );

                                            if ( $award_parent_name ) {
                                                echo ' — '
                                                    . esc_html(
                                                        $award_parent_name
                                                    );
                                            }
                                            ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="teacher-form-field">

                                <label for="kla_award_points">
                                    Kudos Points
                                </label>

                                <input
                                    type="number"
                                    id="kla_award_points"
                                    name="points"
                                    min="1"
                                    step="1"
                                    required
                                    placeholder="Example: 100"
                                />

                            </div>


                            <div class="teacher-form-field">

                                <label for="kla_award_activity">
                                    Activity / Achievement
                                </label>

                                <select
                                    id="kla_award_activity"
                                    name="activity_id"
                                >

                                    <option value="">
                                        Select Activity (Optional)
                                    </option>

                                    <?php

                                    $award_activities = get_posts(
                                        array(
                                            'post_type'      => 'kudos_activity',
                                            'post_status'    => 'publish',
                                            'posts_per_page' => -1,
                                            'orderby'        => 'title',
                                            'order'          => 'ASC',
                                        )
                                    );

                                    foreach ( $award_activities as $award_activity ) :

                                        ?>

                                        <option
                                            value="<?php echo esc_attr( $award_activity->ID ); ?>"
                                        >
                                            <?php
                                            echo esc_html(
                                                $award_activity->post_title
                                            );
                                            ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="teacher-form-field teacher-form-field-wide">

                                <label for="kla_award_reason">
                                    Achievement / Reason
                                </label>

                                <input
                                    type="text"
                                    id="kla_award_reason"
                                    name="reason"
                                    required
                                    maxlength="200"
                                    placeholder="Example: Excellent performance in Mathematics"
                                />

                            </div>

                        </div>


                        <div class="teacher-award-footer">

                            <div class="teacher-award-info">
                                <strong>Recognition matters.</strong>
                                <span>
                                    Award Kudos for learning, effort,
                                    participation or positive behaviour.
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="teacher-award-button"
                            >
                                ⭐ Award Kudos
                            </button>

                        </div>

                    </form>

                </div>

            </section>


            <!-- =================================================
                 RECENT ACTIVITY
                 ================================================= -->

            <section class="teacher-dashboard-section">

                <div class="teacher-section-heading">

                    <span>
                        Latest Updates
                    </span>

                    <h2>
                        Recent Performance
                    </h2>

                </div>


                <div class="teacher-recent-activity">


                    <?php

                    if (
                        $recent_performance->have_posts()
                    ) :

                        while (
                            $recent_performance->have_posts()
                        ) :

                            $recent_performance->the_post();


                            /*
                             * Student name
                             */

                            $student_name =
                                get_post_meta(
                                    get_the_ID(),
                                    '_kudos_performance_student_name',
                                    true
                                );


                            /*
                             * Correct performance meta key
                             */

                            $school_performance =
                                get_post_meta(
                                    get_the_ID(),
                                    '_kudos_performance_school',
                                    true
                                );

                            ?>


                            <div class="teacher-activity-row">

                                <div class="teacher-activity-icon">
                                    📊
                                </div>


                                <div class="teacher-activity-content">

                                    <strong>

                                        <?php

                                        echo esc_html(
                                            $student_name
                                                ? $student_name
                                                : get_the_title()
                                        );

                                        ?>

                                    </strong>

                                    <p>
                                        Performance record available
                                    </p>

                                </div>


                                <?php

                                if (
                                    $school_performance !== ''
                                ) :

                                ?>

                                    <div class="teacher-performance-score">

                                        <?php

                                        echo esc_html(
                                            $school_performance
                                        );

                                        ?>/100

                                    </div>

                                <?php endif; ?>


                            </div>


                        <?php

                        endwhile;

                        wp_reset_postdata();

                    else :

                    ?>

                        <div class="teacher-no-activity">

                            <div>
                                ✨
                            </div>

                            <h3>
                                No recent performance records
                            </h3>

                            <p>
                                Performance updates will
                                appear here.
                            </p>

                        </div>

                    <?php endif; ?>


                </div>

            </section>


            <!-- =================================================
                 MOTIVATION
                 ================================================= -->

            <div class="teacher-dashboard-motivation">

                <div class="motivation-icon">
                    🌱
                </div>

                <div>

                    <strong>
                        Every effort deserves recognition.
                    </strong>

                    <p>
                        Use Kudos to celebrate learning,
                        progress and positive behaviour.
                    </p>

                </div>

            </div>


        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'kudos_teacher_dashboard',
    'kudos_teacher_dashboard'
);


/* =========================================================
   TEACHER AWARD KUDOS HANDLER
   ========================================================= */

function kla_process_teacher_award_kudos() {

    if ( ! is_user_logged_in() ) {

        wp_safe_redirect(
            home_url( '/teacher-login/' )
        );

        exit;
    }


    $user = wp_get_current_user();


    if (
        ! in_array(
            'kudos_teacher',
            $user->roles,
            true
        )
    ) {

        wp_die(
            'Only teachers can award Kudos.'
        );
    }


    if (
        ! isset(
            $_POST['kla_teacher_award_nonce']
        )
    ) {

        wp_die(
            'Security check failed.'
        );
    }


    if (
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['kla_teacher_award_nonce']
                )
            ),
            'kla_teacher_award_kudos'
        )
    ) {

        wp_die(
            'Security check failed.'
        );
    }


    $student_id = isset( $_POST['student_id'] )
        ? absint( $_POST['student_id'] )
        : 0;

    $points = isset( $_POST['points'] )
        ? absint( $_POST['points'] )
        : 0;

    $activity_id = isset( $_POST['activity_id'] )
        ? absint( $_POST['activity_id'] )
        : 0;

    $reason = isset( $_POST['reason'] )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['reason']
            )
        )
        : '';


    if ( ! $student_id ) {

        kla_teacher_award_redirect(
            'error',
            'Please select a student.'
        );
    }


    if (
        get_post_type( $student_id )
        !== 'kudos_student'
    ) {

        kla_teacher_award_redirect(
            'error',
            'Invalid student selected.'
        );
    }


    if ( $points < 1 ) {

        kla_teacher_award_redirect(
            'error',
            'Kudos points must be greater than zero.'
        );
    }


    if ( strlen( $reason ) < 2 ) {

        kla_teacher_award_redirect(
            'error',
            'Please enter the achievement or reason.'
        );
    }


    if ( $activity_id ) {

        if (
            get_post_type( $activity_id )
            !== 'kudos_activity'
        ) {

            kla_teacher_award_redirect(
                'error',
                'Invalid activity selected.'
            );
        }
    }


    $result = kla_award_kudos(
        $student_id,
        $points,
        $reason,
        $user->ID,
        $activity_id
    );


    if ( is_wp_error( $result ) ) {

        kla_teacher_award_redirect(
            'error',
            $result->get_error_message()
        );
    }


    $student_name = get_post_meta(
        $student_id,
        '_kudos_student_name',
        true
    );


    if ( ! $student_name ) {

        $student_name =
            get_the_title(
                $student_id
            );
    }


    wp_safe_redirect(
        add_query_arg(
            array(
                'kudos_award' => 'success',
                'points'      => $points,
                'student'     => $student_name,
            ),
            home_url(
                '/teacher-dashboard/#award-kudos'
            )
        )
    );

    exit;
}

add_action(
    'admin_post_kla_teacher_award_kudos',
    'kla_process_teacher_award_kudos'
);


/* =========================================================
   TEACHER AWARD REDIRECT HELPER
   ========================================================= */

function kla_teacher_award_redirect(
    $status,
    $message
) {

    $url = add_query_arg(
        array(
            'kudos_award' => $status,
            'message'     => $message,
        ),
        home_url(
            '/teacher-dashboard/#award-kudos'
        )
    );


    wp_safe_redirect(
        $url
    );

    exit;
}


/* =========================================================
   TEACHER LOGIN REDIRECT
   ========================================================= */

function kudos_teacher_login_redirect(
    $redirect_to,
    $requested_redirect_to,
    $user
) {

    if (
        isset( $user->roles ) &&
        is_array( $user->roles ) &&
        in_array(
            'kudos_teacher',
            $user->roles,
            true
        )
    ) {

        return home_url(
            '/teacher-dashboard/'
        );
    }

    return $redirect_to;
}

add_filter(
    'login_redirect',
    'kudos_teacher_login_redirect',
    10,
    3
);


/* =========================================================
   TEACHER DASHBOARD CSS
   ========================================================= */

function kudos_teacher_dashboard_styles() {

    if (
        is_page( 'teacher-login' ) ||
        is_page( 'teacher-dashboard' )
    ) {

        ?>

        <style>

        /* =================================================
           LOGIN PAGE
           ================================================= */

        .kudos-teacher-login {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            background: #fffaf0;
        }

        .kudos-login-box {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 15px 45px rgba(48,32,90,.08);
            text-align: center;
            border: 1px solid #eee8dc;
        }

        .kudos-login-logo {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: #eef6dd;
            font-size: 32px;
        }

        .kudos-login-box h1 {
            margin: 0 0 8px;
            color: #30205a;
            font-size: 32px;
        }

        .kudos-login-box > p {
            color: #77717f;
            margin-bottom: 28px;
        }

        .kudos-login-box form {
            text-align: left;
        }

        .kudos-login-box label {
            display: block;
            margin-bottom: 7px;
            color: #30205a;
            font-weight: 700;
        }

        .kudos-login-box input[type="text"],
        .kudos-login-box input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 15px;
            border: 1px solid #ddd8cf;
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .kudos-login-box input[type="submit"] {
            width: 100%;
            border: 0;
            background: #2d7b3c;
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }

        .kudos-login-box input[type="submit"]:hover {
            opacity: .9;
        }


        /* =================================================
           DASHBOARD BASE
           ================================================= */

        .kudos-teacher-dashboard {
            background: #fffaf0;
            min-height: 100vh;
            color: #30205a;
        }

        /* FINAL FULL-WIDTH OVERRIDE: escape any narrow WordPress/theme page wrapper */
        html body .kudos-teacher-dashboard {
            display: block !important;
            width: 100vw !important;
            max-width: none !important;
            min-width: 100vw !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            padding: 0 !important;
            position: relative !important;
            left: 0 !important;
            right: auto !important;
            box-sizing: border-box !important;
            overflow: visible !important;
        }

        html body .kudos-teacher-dashboard .teacher-dashboard-header {
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box !important;
        }

        html body .kudos-teacher-dashboard .teacher-dashboard-container {
            width: 100% !important;
            max-width: 1250px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
        }


        /* =================================================
           HEADER
           ================================================= */

        .teacher-dashboard-header {
            background: #ffffff;
            border-bottom: 1px solid #eee8dc;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .teacher-dashboard-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .teacher-brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #eef6dd;
            display: grid;
            place-items: center;
            font-size: 25px;
        }

        .teacher-brand-name {
            font-size: 27px;
            line-height: 1;
            font-weight: 800;
            color: #30205a;
        }

        .teacher-brand-subtitle {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 800;
            color: #2d7b3c;
        }


        /* =================================================
           USER
           ================================================= */

        .teacher-dashboard-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .teacher-user-details {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .teacher-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #eef6dd;
            display: grid;
            place-items: center;
        }

        .teacher-user-details strong {
            display: block;
            color: #30205a;
            font-size: 14px;
        }

        .teacher-user-details span {
            display: block;
            color: #77717f;
            font-size: 12px;
        }

        .teacher-logout {
            text-decoration: none !important;
            background: #2d7b3c;
            color: #ffffff !important;
            padding: 11px 21px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
            transition: .2s ease;
        }

        .teacher-logout:hover {
            transform: translateY(-2px);
            opacity: .9;
        }


        /* =================================================
           CONTAINER
           ================================================= */

        .teacher-dashboard-container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 55px 25px 70px;
        }


        /* =================================================
           WELCOME
           ================================================= */

        .teacher-welcome {
            margin-bottom: 35px;
        }

        .teacher-badge {
            display: inline-block;
            background: #eef6dd;
            padding: 9px 17px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
        }

        .teacher-welcome h1 {
            font-family: Georgia, serif;
            font-size: clamp(40px, 5vw, 58px);
            line-height: 1.05;
            color: #30205a;
            margin: 20px 0 10px;
        }

        .teacher-welcome p {
            color: #5b5668;
            font-size: 17px;
            margin: 0;
        }


        /* =================================================
           STATS
           ================================================= */

        .teacher-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 55px;
        }

        .teacher-stat-card {
            background: #ffffff;
            border: 1px solid #eee8dc;
            border-radius: 20px;
            padding: 23px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 8px 25px rgba(48,32,90,.05);
        }

        .teacher-stat-icon {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            border-radius: 15px;
            background: #eef6dd;
            display: grid;
            place-items: center;
            font-size: 25px;
        }

        .teacher-stat-card span {
            display: block;
            color: #77717f;
            font-size: 13px;
            font-weight: 700;
        }

        .teacher-stat-card strong {
            display: block;
            color: #30205a;
            font-size: 28px;
            margin-top: 3px;
        }

        .teacher-stat-card small {
            display: block;
            color: #99939f;
            font-size: 10px;
            margin-top: 3px;
        }


        /* =================================================
           SECTIONS
           ================================================= */

        .teacher-dashboard-section {
            margin-bottom: 48px;
        }

        .teacher-section-heading {
            margin-bottom: 20px;
        }

        .teacher-section-heading span {
            color: #2d7b3c;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .teacher-section-heading h2 {
            margin: 5px 0 0;
            font-size: 30px;
            color: #30205a;
        }


        /* =================================================
           QUICK ACTIONS
           ================================================= */

        .teacher-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .teacher-action-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #eee8dc;
            border-radius: 20px;
            padding: 27px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            box-shadow: 0 8px 25px rgba(48,32,90,.04);
            transition: .25s ease;
        }

        .teacher-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(48,32,90,.10);
        }

        .teacher-action-card.action-highlight {
            border: 2px solid #2d7b3c;
        }

        .teacher-action-icon {
            width: 55px;
            height: 55px;
            flex: 0 0 55px;
            border-radius: 16px;
            background: #eef6dd;
            display: grid;
            place-items: center;
            font-size: 26px;
        }

        .teacher-action-card h3 {
            margin: 2px 0 7px;
            font-family: Georgia, serif;
            font-size: 21px;
            color: #30205a;
        }

        .teacher-action-card p {
            margin: 0;
            color: #6d6876;
            font-size: 14px;
            line-height: 1.6;
        }

        .action-coming {
            display: inline-block;
            margin-top: 12px;
            color: #2d7b3c;
            font-size: 13px;
            font-weight: 800;
        }


        /* =================================================
           RECENT ACTIVITY
           ================================================= */

        .teacher-recent-activity {
            background: #ffffff;
            border: 1px solid #eee8dc;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(48,32,90,.04);
        }

        .teacher-activity-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px 22px;
            border-bottom: 1px solid #f0ece5;
        }

        .teacher-activity-row:last-child {
            border-bottom: 0;
        }

        .teacher-activity-icon {
            width: 43px;
            height: 43px;
            flex: 0 0 43px;
            border-radius: 13px;
            background: #eef6dd;
            display: grid;
            place-items: center;
        }

        .teacher-activity-content {
            flex: 1;
        }

        .teacher-activity-content strong {
            color: #30205a;
        }

        .teacher-activity-content p {
            margin: 3px 0 0;
            color: #77717f;
            font-size: 13px;
        }

        .teacher-performance-score {
            background: #eef6dd;
            color: #2d7b3c;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
        }

        .teacher-no-activity {
            text-align: center;
            padding: 50px 20px;
        }

        .teacher-no-activity > div {
            font-size: 35px;
        }

        .teacher-no-activity h3 {
            margin: 10px 0 5px;
            color: #30205a;
        }

        .teacher-no-activity p {
            margin: 0;
            color: #77717f;
        }


        /* =================================================
           MOTIVATION
           ================================================= */

        .teacher-dashboard-motivation {
            background: #eef6dd;
            border-radius: 20px;
            padding: 23px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .motivation-icon {
            font-size: 30px;
        }

        .teacher-dashboard-motivation strong {
            color: #30205a;
        }

        .teacher-dashboard-motivation p {
            margin: 4px 0 0;
            color: #686271;
            font-size: 13px;
        }


        /* =================================================
           AWARD KUDOS
           ================================================= */

        .teacher-action-link {
            text-decoration: none !important;
            color: inherit !important;
        }

        .teacher-award-card {
            background: #ffffff;
            border: 1px solid #eee8dc;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 25px rgba(48,32,90,.04);
        }

        .teacher-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .teacher-form-field {
            display: flex;
            flex-direction: column;
        }

        .teacher-form-field-wide {
            grid-column: 1 / -1;
        }

        .teacher-form-field label {
            margin-bottom: 8px;
            color: #30205a;
            font-size: 14px;
            font-weight: 800;
        }

        .teacher-form-field input,
        .teacher-form-field select {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #ddd8cf;
            border-radius: 12px;
            background: #ffffff;
            color: #30205a;
            font-size: 14px;
        }

        .teacher-form-field input:focus,
        .teacher-form-field select:focus {
            outline: none;
            border-color: #2d7b3c;
            box-shadow: 0 0 0 3px rgba(45,123,60,.10);
        }

        .teacher-award-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f0ece5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .teacher-award-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .teacher-award-info strong {
            color: #30205a;
            font-size: 14px;
        }

        .teacher-award-info span {
            color: #77717f;
            font-size: 13px;
        }

        .teacher-award-button {
            border: 0;
            background: #2d7b3c;
            color: #ffffff;
            padding: 13px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .teacher-award-button:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .teacher-award-notice {
            margin-bottom: 18px;
            padding: 14px 17px;
            border-radius: 12px;
            font-size: 14px;
        }

        .teacher-award-notice.success {
            background: #eef6dd;
            color: #23632f;
            border: 1px solid #d8e8bd;
        }

        .teacher-award-notice.error {
            background: #fff1f1;
            color: #9b2c2c;
            border: 1px solid #f0caca;
        }


        /* =================================================
           ACCESS MESSAGE
           ================================================= */

        .kudos-dashboard-message {
            max-width: 650px;
            margin: 60px auto;
            padding: 40px;
            text-align: center;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }

        .teacher-dashboard-login-button {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 22px;
            border-radius: 30px;
            background: #2d7b3c;
            color: #ffffff !important;
            text-decoration: none !important;
            font-weight: 800;
        }


        /* =================================================
           TABLET
           ================================================= */

        @media (max-width: 1000px) {

            .teacher-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        /* =================================================
           MOBILE
           ================================================= */

        @media (max-width: 650px) {

            .teacher-dashboard-header {
                padding: 15px 18px;
            }

            .teacher-user-details {
                display: none;
            }

            .teacher-dashboard-user {
                gap: 0;
            }

            .teacher-brand-name {
                font-size: 23px;
            }

            .teacher-brand-icon {
                width: 42px;
                height: 42px;
            }

            .teacher-dashboard-container {
                padding: 35px 18px 50px;
            }

            .teacher-welcome h1 {
                font-size: 38px;
            }

            .teacher-stats-grid {
                grid-template-columns: 1fr;
            }

            .teacher-actions-grid {
                grid-template-columns: 1fr;
            }

            .teacher-action-card {
                padding: 22px;
            }

            .teacher-form-grid {
                grid-template-columns: 1fr;
            }

            .teacher-form-field-wide {
                grid-column: auto;
            }

            .teacher-award-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .teacher-award-button {
                width: 100%;
            }

            .teacher-activity-row {
                padding: 15px;
            }

            .teacher-dashboard-motivation {
                align-items: flex-start;
            }

            .kudos-login-box {
                padding: 28px 22px;
            }

        }
/* =========================================================
   TEACHER DASHBOARD - ISOLATED LAYOUT FIX
   ========================================================= */

/*
 * The WordPress theme wraps page content inside .site-page/.container.
 * The teacher dashboard has its own complete layout, so neutralize only
 * the theme wrapper on this page and keep all dashboard styling scoped.
 */

body:has(.kudos-teacher-dashboard) .site-page {
    padding: 0 !important;
    margin: 0 !important;
}

body:has(.kudos-teacher-dashboard) .site-page > .container {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

body:has(.kudos-teacher-dashboard) .page-content {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

body:has(.kudos-teacher-dashboard) .page-body {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

body:has(.kudos-teacher-dashboard) .page-title {
    display: none !important;
}


/* =========================================================
   DASHBOARD SHELL
   ========================================================= */

body:has(.kudos-teacher-dashboard) .kudos-teacher-dashboard {
    display: block !important;
    width: 100vw !important;
    max-width: none !important;
    min-height: 100vh !important;
    margin-left: calc(50% - 50vw) !important;
    margin-right: 0 !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
    background: #fffaf0 !important;
}

body:has(.kudos-teacher-dashboard) .kudos-teacher-dashboard * {
    box-sizing: border-box;
}


/* =========================================================
   HEADER
   ========================================================= */

body:has(.kudos-teacher-dashboard) .teacher-dashboard-header {
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
    margin: 0 !important;
    padding: 18px 6% !important;
}


/* =========================================================
   MAIN CONTENT
   ========================================================= */

body:has(.kudos-teacher-dashboard) .teacher-dashboard-container {
    width: 100% !important;
    max-width: 1250px !important;
    box-sizing: border-box;
    margin: 0 auto !important;
    padding: 55px 25px 70px !important;
}


/* =========================================================
   RESET POSSIBLE THEME HEADING / LINK OVERRIDES
   ========================================================= */

body:has(.kudos-teacher-dashboard) .teacher-dashboard-container h1,
body:has(.kudos-teacher-dashboard) .teacher-dashboard-container h2,
body:has(.kudos-teacher-dashboard) .teacher-dashboard-container h3,
body:has(.kudos-teacher-dashboard) .teacher-dashboard-container p {
    max-width: none;
}

body:has(.kudos-teacher-dashboard) .teacher-action-link {
    text-decoration: none !important;
}


/* =========================================================
   AWARD KUDOS
   ========================================================= */

body:has(.kudos-teacher-dashboard) .teacher-award-card {
    width: 100%;
    box-sizing: border-box;
}

body:has(.kudos-teacher-dashboard) .teacher-form-grid {
    width: 100%;
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 650px) {

    body:has(.kudos-teacher-dashboard) .teacher-dashboard-header {
        padding: 15px 18px !important;
    }

    body:has(.kudos-teacher-dashboard) .teacher-dashboard-container {
        width: 100% !important;
        padding: 35px 18px 50px !important;
    }

}

</style>

        <?php
    }
}

add_action(
    'wp_head',
    'kudos_teacher_dashboard_styles'
);




/* =========================================================
   TEACHER PORTAL — COMPACT SIDEBAR LAYOUT
   ========================================================= */

add_action(
    'wp_footer',
    'kla_teacher_compact_portal_layout',
    99
);

function kla_teacher_compact_portal_layout() {

    if ( ! is_page( 'teacher-dashboard' ) || ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        return;
    }

    $teacher_total = 0;

    $teacher_kudos_query = new WP_Query(
        array(
            'post_type'      => 'kudos_transaction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => '_kla_transaction_teacher_id',
                    'value'   => $user->ID,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_kla_transaction_type',
                    'value'   => 'award',
                    'compare' => '=',
                ),
            ),
        )
    );

    if ( ! empty( $teacher_kudos_query->posts ) ) {
        foreach ( $teacher_kudos_query->posts as $transaction_id ) {
            $teacher_total += absint(
                get_post_meta(
                    $transaction_id,
                    '_kla_transaction_points',
                    true
                )
            );
        }
    }

    ?>
    <style>
        body:has(.kudos-teacher-dashboard) .kla-teacher-tools{
            width:100% !important;
            max-width:1180px !important;
            margin:0 auto 60px !important;
            padding:35px 24px 60px !important;
            box-sizing:border-box !important;
            display:grid !important;
            grid-template-columns:190px minmax(0,1fr) !important;
            gap:25px !important;
            background:transparent !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tools-nav{
            width:190px !important;
            height:max-content !important;
            position:sticky !important;
            top:25px !important;
            box-sizing:border-box !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tool-content{
            min-width:0 !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tool-panel{
            display:none !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tool-panel.active{
            display:block !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tools .kla-teacher-tool-card{
            margin:0 !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tool-content > .kla-teacher-tool-card{
            margin:0 !important;
        }

        .kla-teacher-award-panel{
            display:none;
        }

        .kla-teacher-award-panel.active{
            display:block;
        }

        .kla-compact-award-wrap{
            background:#fff;
            border:1px solid #eee;
            border-radius:18px;
            padding:26px;
            box-shadow:0 8px 24px rgba(48,32,90,.05);
        }

        .kla-compact-award-wrap .teacher-award-card{
            margin:0 !important;
            box-shadow:none !important;
            border:0 !important;
            padding:0 !important;
        }

        @media(max-width:760px){
            body:has(.kudos-teacher-dashboard) .kla-teacher-tools{
                grid-template-columns:1fr !important;
                padding:25px 18px 50px !important;
            }
            body:has(.kudos-teacher-dashboard) .kla-teacher-tools-nav{
                width:100% !important;
                position:static !important;
            }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function(){

        const oldDashboard = document.querySelector('.teacher-dashboard-container');
        const teacherHeader = document.querySelector('.teacher-dashboard-header');
        const tools = document.querySelector('.kla-teacher-tools');

        if (!oldDashboard || !tools || !teacherHeader) {
            return;
        }

        /*
         * Move the actual Teacher Portal directly below the header.
         * The old long dashboard is hidden because its functions are now
         * available through the sidebar.
         */
        teacherHeader.insertAdjacentElement('afterend', tools);
        oldDashboard.style.display = 'none';

        const toolContent = tools.querySelector('.kla-teacher-tool-content');
        const nav = tools.querySelector('.kla-teacher-tools-nav');

        if (!toolContent || !nav) {
            return;
        }

        /* Remove the old Overview button; we'll use our own compact menu. */
        nav.innerHTML =
            '<div class="kla-teacher-tools-nav-title">TEACHER PORTAL</div>' +
            '<button type="button" class="kla-tool-nav active" data-tool="overview">🏠 Overview</button>' +
            '<button type="button" class="kla-tool-nav" data-tool="award">⭐ Award Kudos</button>' +
            '<button type="button" class="kla-tool-nav" data-tool="performance">📊 Performance</button>' +
            '<button type="button" class="kla-tool-nav" data-tool="attendance">📅 Attendance</button>' +
            '<button type="button" class="kla-tool-nav" data-tool="students">👨‍🎓 My Students</button>';

        const overview = tools.querySelector('[data-panel="overview"]');
        const performance = tools.querySelector('[data-panel="performance"]');
        const attendance = tools.querySelector('[data-panel="attendance"]');
        const students = tools.querySelector('[data-panel="students"]');

        /* Build a compact overview using the existing real dashboard stats. */
        if (overview) {
            overview.innerHTML =
                '<div class="kla-teacher-tool-card">' +
                    '<div class="kla-teacher-tool-heading">' +
                        '<span>TEACHER PORTAL</span>' +
                        '<h2>Welcome, <?php echo esc_js( $user->display_name ); ?> 👋</h2>' +
                        '<p>Manage Kudos, performance, attendance and your students from the menu.</p>' +
                    '</div>' +
                    '<div class="teacher-stats-grid" style="margin-top:20px;">' +
                        '<div class="teacher-stat-card"><div class="teacher-stat-icon">👨‍🎓</div><div><span>My Students</span><strong><?php echo esc_js( $total_students ); ?></strong><small>Active student records</small></div></div>' +
                        '<div class="teacher-stat-card"><div class="teacher-stat-icon">⭐</div><div><span>Kudos Awarded</span><strong><?php echo esc_js( $teacher_total ); ?></strong><small>Total awarded by you</small></div></div>' +
                        '<div class="teacher-stat-card"><div class="teacher-stat-icon">📊</div><div><span>Performance</span><strong><?php echo esc_js( $total_performance ); ?></strong><small>Records available</small></div></div>' +
                        '<div class="teacher-stat-card"><div class="teacher-stat-icon">📅</div><div><span>Attendance</span><strong>Ready</strong><small>Daily records available</small></div></div>' +
                    '</div>' +
                '</div>';
        }

        /* Move the existing Award Kudos section from the old dashboard into a panel. */
        const award = document.querySelector('#award-kudos');

        if (award) {
            const awardPanel = document.createElement('div');
            awardPanel.className = 'kla-teacher-award-panel';
            awardPanel.setAttribute('data-panel', 'award');
            awardPanel.appendChild(award);
            toolContent.appendChild(awardPanel);
        }

        /* Remove duplicated tool success notices from the overview area. */
        toolContent.querySelectorAll('.kla-teacher-success').forEach(function(el){
            if (!el.closest('[data-panel]')) {
                el.remove();
            }
        });

        const panels = [
            overview,
            performance,
            attendance,
            students,
            tools.querySelector('[data-panel="award"]')
        ].filter(Boolean);

        const buttons = Array.from(nav.querySelectorAll('.kla-tool-nav'));

        function showPanel(name) {
            panels.forEach(function(panel){
                panel.classList.toggle('active', panel.getAttribute('data-panel') === name);
            });

            buttons.forEach(function(button){
                button.classList.toggle('active', button.getAttribute('data-tool') === name);
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        buttons.forEach(function(button){
            button.addEventListener('click', function(){
                showPanel(this.getAttribute('data-tool'));
            });
        });

        showPanel('overview');
    });
    </script>
    <?php
}

/* =========================================================
   FINAL TEACHER PORTAL SHELL FIX
   Move the self-contained teacher dashboard out of the
   theme's narrow page container so it can use the full viewport.
   ========================================================= */
add_action( 'wp_footer', 'kla_teacher_force_full_viewport_shell', 9999 );

function kla_teacher_force_full_viewport_shell() {
    if ( ! is_page( 'teacher-dashboard' ) || ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        return;
    }
    ?>
    <style id="kla-teacher-final-shell-fix">
        html, body {
            overflow-x: hidden !important;
        }

        body:has(.kudos-teacher-dashboard) {
            margin: 0 !important;
            padding: 0 !important;
            background: #fffaf0 !important;
        }

        /* Neutralize the theme's page/container constraints at every common level. */
        body:has(.kudos-teacher-dashboard) .site,
        body:has(.kudos-teacher-dashboard) .site-main,
        body:has(.kudos-teacher-dashboard) main,
        body:has(.kudos-teacher-dashboard) .site-content,
        body:has(.kudos-teacher-dashboard) .content-area,
        body:has(.kudos-teacher-dashboard) .page-wrapper,
        body:has(.kudos-teacher-dashboard) .site-page,
        body:has(.kudos-teacher-dashboard) .page-content,
        body:has(.kudos-teacher-dashboard) .page-body,
        body:has(.kudos-teacher-dashboard) .entry-content,
        body:has(.kudos-teacher-dashboard) article,
        body:has(.kudos-teacher-dashboard) article > .container,
        body:has(.kudos-teacher-dashboard) .site-page .container,
        body:has(.kudos-teacher-dashboard) .page-content .container,
        body:has(.kudos-teacher-dashboard) .page-body .container {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
        }

        /* Hide only the theme page chrome on this self-contained portal page. */
        body:has(.kudos-teacher-dashboard) .site-header,
        body:has(.kudos-teacher-dashboard) .site-footer,
        body:has(.kudos-teacher-dashboard) .page-title-area,
        body:has(.kudos-teacher-dashboard) .breadcrumb-area,
        body:has(.kudos-teacher-dashboard) .page-header {
            display: none !important;
        }

        body:has(.kudos-teacher-dashboard) .kudos-teacher-dashboard {
            position: relative !important;
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
            box-sizing: border-box !important;
            background: #fffaf0 !important;
        }

        body:has(.kudos-teacher-dashboard) .teacher-dashboard-header {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 18px clamp(20px, 6vw, 90px) !important;
        }

        body:has(.kudos-teacher-dashboard) .teacher-dashboard-container,
        body:has(.kudos-teacher-dashboard) .kla-teacher-tools {
            width: 100% !important;
            max-width: 1180px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tools {
            display: grid !important;
            grid-template-columns: 190px minmax(0, 1fr) !important;
            gap: 28px !important;
            padding: 35px 20px 70px !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tools-nav {
            width: 190px !important;
            max-width: 190px !important;
        }

        body:has(.kudos-teacher-dashboard) .kla-teacher-tool-content {
            width: 100% !important;
            min-width: 0 !important;
        }

        @media (max-width: 760px) {
            body:has(.kudos-teacher-dashboard) .kla-teacher-tools {
                grid-template-columns: 1fr !important;
                padding: 25px 16px 50px !important;
            }

            body:has(.kudos-teacher-dashboard) .kla-teacher-tools-nav {
                width: 100% !important;
                max-width: none !important;
                position: static !important;
            }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var dashboard = document.querySelector('.kudos-teacher-dashboard');
        if (!dashboard) return;

        /*
         * This is the definitive fix: detach the self-contained portal
         * from whatever narrow wrapper the active WordPress theme uses.
         */
        if (dashboard.parentNode !== document.body) {
            document.body.appendChild(dashboard);
        }

        document.body.classList.add('kudos-teacher-page');

        /* Keep the portal as the only visible page content. */
        var wrappers = document.querySelectorAll(
            '.site-header, .site-footer, .page-title-area, .breadcrumb-area, .page-header'
        );
        wrappers.forEach(function (el) {
            if (!el.closest('.kudos-teacher-dashboard')) {
                el.style.display = 'none';
            }
        });
    });
    </script>
    <?php
}

/* =========================================================
   FINAL FULL-BLEED FIX
   The theme is constraining the shortcode's parent to a narrow
   centered column. This makes the self-contained teacher portal
   break out of that column without changing the theme globally.
   ========================================================= */
add_action( 'wp_head', 'kla_teacher_final_bleed_fix', 100000 );
function kla_teacher_final_bleed_fix() {
    if ( ! is_page( 'teacher-dashboard' ) || ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();
    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        return;
    }
    ?>
    <style id="kla-teacher-final-bleed-fix">
        html body .kudos-teacher-dashboard {
            position: relative !important;
            display: block !important;
            width: 100vw !important;
            max-width: 100vw !important;
            min-width: 100vw !important;
            margin-left: 50% !important;
            margin-right: 0 !important;
            transform: translateX(-50%) !important;
            left: auto !important;
            right: auto !important;
            box-sizing: border-box !important;
            overflow: visible !important;
        }

        html body .kudos-teacher-dashboard .teacher-dashboard-header {
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box !important;
        }

        html body .kudos-teacher-dashboard .kla-teacher-tools {
            width: min(1180px, calc(100vw - 40px)) !important;
            max-width: 1180px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
        }

        html body .kudos-teacher-dashboard .teacher-dashboard-container {
            width: min(1180px, calc(100vw - 40px)) !important;
            max-width: 1180px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 760px) {
            html body .kudos-teacher-dashboard .kla-teacher-tools,
            html body .kudos-teacher-dashboard .teacher-dashboard-container {
                width: calc(100vw - 28px) !important;
            }
        }
    </style>
    <?php
}
