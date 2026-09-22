<?php
/**
 * Kudos Learning Academy
 * Teacher Performance + Attendance Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   ATTENDANCE CPT
   ========================================================= */

if ( ! function_exists( 'kla_register_attendance' ) ) {
    function kla_register_attendance() {
        register_post_type(
            'kudos_attendance',
            array(
                'labels' => array(
                    'name'          => 'Attendance',
                    'singular_name' => 'Attendance Record',
                    'menu_name'     => 'Attendance',
                    'add_new_item'  => 'Add Attendance',
                    'edit_item'     => 'Edit Attendance',
                ),
                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'menu_position'      => 29,
                'menu_icon'          => 'dashicons-calendar-alt',
                'supports'           => array( 'title' ),
                'capability_type'    => 'post',
                'map_meta_cap'       => true,
                'show_in_rest'       => true,
            )
        );
    }
    add_action( 'init', 'kla_register_attendance' );
}

/* =========================================================
   ATTENDANCE META
   ========================================================= */

if ( ! function_exists( 'kla_attendance_meta_box' ) ) {
    function kla_attendance_meta_box() {
        add_meta_box(
            'kla_attendance_details',
            'Attendance Details',
            'kla_attendance_details_callback',
            'kudos_attendance',
            'normal',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'kla_attendance_meta_box' );
}

if ( ! function_exists( 'kla_attendance_details_callback' ) ) {
    function kla_attendance_details_callback( $post ) {
        $student_id = absint( get_post_meta( $post->ID, '_kla_attendance_student_id', true ) );
        $date       = get_post_meta( $post->ID, '_kla_attendance_date', true );
        $status     = get_post_meta( $post->ID, '_kla_attendance_status', true );
        $remarks    = get_post_meta( $post->ID, '_kla_attendance_remarks', true );

        if ( ! $date ) {
            $date = current_time( 'Y-m-d' );
        }
        if ( ! $status ) {
            $status = 'present';
        }

        wp_nonce_field( 'kla_save_attendance_details', 'kla_attendance_nonce' );
        ?>
        <p>
            <label><strong>Student</strong></label><br>
            <select name="kla_attendance_student_id" style="width:100%;">
                <option value="">Select Student</option>
                <?php
                $students = get_posts(
                    array(
                        'post_type'      => 'kudos_student',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    )
                );
                foreach ( $students as $student ) :
                    ?>
                    <option value="<?php echo esc_attr( $student->ID ); ?>" <?php selected( $student_id, $student->ID ); ?>>
                        <?php echo esc_html( get_post_meta( $student->ID, '_kudos_student_name', true ) ?: $student->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong>Date</strong></label><br>
            <input type="date" name="kla_attendance_date" value="<?php echo esc_attr( $date ); ?>">
        </p>
        <p>
            <label><strong>Status</strong></label><br>
            <select name="kla_attendance_status">
                <option value="present" <?php selected( $status, 'present' ); ?>>Present</option>
                <option value="absent" <?php selected( $status, 'absent' ); ?>>Absent</option>
                <option value="late" <?php selected( $status, 'late' ); ?>>Late</option>
            </select>
        </p>
        <p>
            <label><strong>Remarks</strong></label><br>
            <textarea name="kla_attendance_remarks" rows="4" style="width:100%;"><?php echo esc_textarea( $remarks ); ?></textarea>
        </p>
        <?php
    }
}

if ( ! function_exists( 'kla_save_attendance_details' ) ) {
    function kla_save_attendance_details( $post_id ) {
        if ( get_post_type( $post_id ) !== 'kudos_attendance' ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if (
            ! isset( $_POST['kla_attendance_nonce'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['kla_attendance_nonce'] ) ),
                'kla_save_attendance_details'
            )
        ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $student_id = isset( $_POST['kla_attendance_student_id'] ) ? absint( $_POST['kla_attendance_student_id'] ) : 0;
        $date       = isset( $_POST['kla_attendance_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kla_attendance_date'] ) ) : '';
        $status     = isset( $_POST['kla_attendance_status'] ) ? sanitize_key( $_POST['kla_attendance_status'] ) : 'present';
        $remarks    = isset( $_POST['kla_attendance_remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kla_attendance_remarks'] ) ) : '';

        if ( ! in_array( $status, array( 'present', 'absent', 'late' ), true ) ) {
            $status = 'present';
        }

        update_post_meta( $post_id, '_kla_attendance_student_id', $student_id );
        update_post_meta( $post_id, '_kla_attendance_date', $date );
        update_post_meta( $post_id, '_kla_attendance_status', $status );
        update_post_meta( $post_id, '_kla_attendance_remarks', $remarks );

        if ( $student_id ) {
            $student_name = get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id );
            if ( $date ) {
                remove_action( 'save_post_kudos_attendance', 'kla_save_attendance_details' );
                wp_update_post(
                    array(
                        'ID'         => $post_id,
                        'post_title' => $student_name . ' - ' . $date,
                    )
                );
                add_action( 'save_post_kudos_attendance', 'kla_save_attendance_details' );
            }
        }
    }
    add_action( 'save_post_kudos_attendance', 'kla_save_attendance_details' );
}

/* =========================================================
   TEACHER TOOL HELPERS
   ========================================================= */

if ( ! function_exists( 'kla_teacher_is_user' ) ) {
    function kla_teacher_is_user() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $user = wp_get_current_user();
        return in_array( 'kudos_teacher', (array) $user->roles, true );
    }
}

if ( ! function_exists( 'kla_get_latest_performance_for_student' ) ) {
    function kla_get_latest_performance_for_student( $student_id ) {
        $student_name = get_post_meta( $student_id, '_kudos_student_name', true );
        $meta_queries = array(
            'relation' => 'OR',
            array(
                'key'     => '_kudos_performance_student',
                'value'   => $student_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        );

        if ( $student_name ) {
            $meta_queries[] = array(
                'key'     => '_kudos_performance_student_name',
                'value'   => $student_name,
                'compare' => '=',
            );
        }

        $records = get_posts(
            array(
                'post_type'      => 'kudos_performance',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => $meta_queries,
            )
        );

        return $records ? $records[0] : null;
    }
}

/* =========================================================
   TEACHER PERFORMANCE UPDATE
   ========================================================= */

if ( ! function_exists( 'kla_teacher_update_performance' ) ) {
    function kla_teacher_update_performance() {
        if ( ! kla_teacher_is_user() ) {
            wp_die( 'Only teachers can update performance.' );
        }

        check_admin_referer( 'kla_teacher_performance_update', 'kla_teacher_performance_nonce' );

        $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            wp_die( 'Invalid student selected.' );
        }

        $fields = array(
            'punctuality'   => '_kudos_performance_punctuality',
            'discipline'    => '_kudos_performance_discipline',
            'fees'          => '_kudos_performance_fees',
            'etiquettes'    => '_kudos_performance_etiquettes',
            'workshops'     => '_kudos_performance_workshops',
            'school'        => '_kudos_performance_school',
            'participation' => '_kudos_performance_participation',
        );

        $performance = kla_get_latest_performance_for_student( $student_id );

        if ( ! $performance ) {
            $student_name = get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id );
            $performance_id = wp_insert_post(
                array(
                    'post_title'  => $student_name . ' - Performance',
                    'post_type'   => 'kudos_performance',
                    'post_status' => 'publish',
                ),
                true
            );
            if ( is_wp_error( $performance_id ) ) {
                wp_die( 'Could not create performance record.' );
            }
        } else {
            $performance_id = $performance->ID;
        }

        update_post_meta( $performance_id, '_kudos_performance_student', $student_id );
        update_post_meta(
            $performance_id,
            '_kudos_performance_student_name',
            get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id )
        );

        foreach ( $fields as $input => $meta_key ) {
            if ( ! isset( $_POST[ $input ] ) || $_POST[ $input ] === '' ) {
                continue;
            }
            $value = max( 0, min( 100, absint( $_POST[ $input ] ) ) );
            update_post_meta( $performance_id, $meta_key, $value );
        }

        wp_update_post(
            array(
                'ID' => $performance_id,
            )
        );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'teacher_tool' => 'performance_saved',
                ),
                home_url( '/teacher-dashboard/' )
            )
        );
        exit;
    }
    add_action( 'admin_post_kla_teacher_update_performance', 'kla_teacher_update_performance' );
}

/* =========================================================
   TEACHER ATTENDANCE SAVE
   ========================================================= */

if ( ! function_exists( 'kla_teacher_save_attendance' ) ) {
    function kla_teacher_save_attendance() {
        if ( ! kla_teacher_is_user() ) {
            wp_die( 'Only teachers can update attendance.' );
        }

        check_admin_referer( 'kla_teacher_attendance_save', 'kla_teacher_attendance_nonce' );

        $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
        $date       = isset( $_POST['attendance_date'] ) ? sanitize_text_field( wp_unslash( $_POST['attendance_date'] ) ) : '';
        $status     = isset( $_POST['attendance_status'] ) ? sanitize_key( $_POST['attendance_status'] ) : 'present';
        $remarks    = isset( $_POST['attendance_remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['attendance_remarks'] ) ) : '';

        if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
            wp_die( 'Invalid student selected.' );
        }
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_die( 'Please select a valid attendance date.' );
        }
        if ( ! in_array( $status, array( 'present', 'absent', 'late' ), true ) ) {
            wp_die( 'Invalid attendance status.' );
        }

        $student_name = get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id );

        $attendance_id = wp_insert_post(
            array(
                'post_title'  => $student_name . ' - ' . $date,
                'post_type'   => 'kudos_attendance',
                'post_status' => 'publish',
            ),
            true
        );

        if ( is_wp_error( $attendance_id ) ) {
            wp_die( 'Could not save attendance.' );
        }

        update_post_meta( $attendance_id, '_kla_attendance_student_id', $student_id );
        update_post_meta( $attendance_id, '_kla_attendance_date', $date );
        update_post_meta( $attendance_id, '_kla_attendance_status', $status );
        update_post_meta( $attendance_id, '_kla_attendance_remarks', $remarks );
        update_post_meta( $attendance_id, '_kla_attendance_teacher_id', get_current_user_id() );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'teacher_tool' => 'attendance_saved',
                ),
                home_url( '/teacher-dashboard/' )
            )
        );
        exit;
    }
    add_action( 'admin_post_kla_teacher_save_attendance', 'kla_teacher_save_attendance' );
}

/* =========================================================
   TEACHER DASHBOARD - COMPACT PORTAL
   ========================================================= */

if ( ! function_exists( 'kla_teacher_dashboard_tools_content' ) ) {
    function kla_teacher_dashboard_tools_content( $content ) {
        if ( ! is_page( 'teacher-dashboard' ) || ! kla_teacher_is_user() ) {
            return $content;
        }

        $user = wp_get_current_user();

        $students = get_posts(
            array(
                'post_type'      => 'kudos_student',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $total_students = count( $students );

        $performance_counts = wp_count_posts( 'kudos_performance' );
        $total_performance = isset( $performance_counts->publish ) ? (int) $performance_counts->publish : 0;

        $teacher_kudos_total = 0;
        $teacher_kudos_ids = get_posts(
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

        foreach ( $teacher_kudos_ids as $transaction_id ) {
            $teacher_kudos_total += absint( get_post_meta( $transaction_id, '_kla_transaction_points', true ) );
        }

        $today_attendance = get_posts(
            array(
                'post_type'      => 'kudos_attendance',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_kla_attendance_date',
                        'value'   => current_time( 'Y-m-d' ),
                        'compare' => '=',
                    ),
                ),
            )
        );

        $logout_url = wp_logout_url( home_url( '/teacher-login/' ) );

        ob_start();
        ?>

        <div class="kla-teacher-portal">

            <header class="kla-teacher-portal-header">
                <div class="kla-teacher-brand">
                    <div class="kla-teacher-brand-icon">🌱</div>
                    <div>
                        <strong>Kudos</strong>
                        <span>Learning Academy</span>
                    </div>
                </div>

                <div class="kla-teacher-user">
                    <div>
                        <strong><?php echo esc_html( $user->display_name ); ?></strong>
                        <span>Teacher</span>
                    </div>
                    <a href="<?php echo esc_url( $logout_url ); ?>">Logout</a>
                </div>
            </header>

            <div class="kla-teacher-portal-shell">

                <aside class="kla-teacher-sidebar">
                    <div class="kla-teacher-sidebar-title">TEACHER PORTAL</div>

                    <button type="button" class="kla-tool-nav active" data-tool="overview">🏠 <span>Overview</span></button>
                    <button type="button" class="kla-tool-nav" data-tool="award">⭐ <span>Award Kudos</span></button>
                    <button type="button" class="kla-tool-nav" data-tool="performance">📊 <span>Performance</span></button>
                    <button type="button" class="kla-tool-nav" data-tool="attendance">📅 <span>Attendance</span></button>
                    <button type="button" class="kla-tool-nav" data-tool="students">👨‍🎓 <span>My Students</span></button>
                </aside>

                <main class="kla-teacher-main">

                    <?php if ( isset( $_GET['teacher_tool'] ) && $_GET['teacher_tool'] === 'performance_saved' ) : ?>
                        <div class="kla-teacher-success">Performance updated successfully.</div>
                    <?php elseif ( isset( $_GET['teacher_tool'] ) && $_GET['teacher_tool'] === 'attendance_saved' ) : ?>
                        <div class="kla-teacher-success">Attendance saved successfully.</div>
                    <?php endif; ?>

                    <!-- OVERVIEW -->
                    <section class="kla-teacher-panel active" data-panel="overview">
                        <div class="kla-panel-heading">
                            <span>TEACHER DASHBOARD</span>
                            <h1>Welcome, <?php echo esc_html( $user->display_name ); ?> 👋</h1>
                            <p>Manage your students, performance, attendance and achievements from one place.</p>
                        </div>

                        <div class="kla-teacher-stats">
                            <div class="kla-stat-card"><div>👨‍🎓</div><span>My Students</span><strong><?php echo esc_html( $total_students ); ?></strong></div>
                            <div class="kla-stat-card"><div>⭐</div><span>Kudos Awarded</span><strong><?php echo esc_html( $teacher_kudos_total ); ?></strong></div>
                            <div class="kla-stat-card"><div>📅</div><span>Today's Attendance</span><strong><?php echo esc_html( count( $today_attendance ) ); ?></strong></div>
                            <div class="kla-stat-card"><div>📊</div><span>Performance Records</span><strong><?php echo esc_html( $total_performance ); ?></strong></div>
                        </div>

                        <div class="kla-quick-grid">
                            <button type="button" class="kla-quick-card" data-open-tool="award"><b>⭐ Award Kudos</b><span>Recognize a student's effort and achievement.</span></button>
                            <button type="button" class="kla-quick-card" data-open-tool="performance"><b>📊 Performance</b><span>Update the latest learning progress.</span></button>
                            <button type="button" class="kla-quick-card" data-open-tool="attendance"><b>📅 Attendance</b><span>Record Present, Absent or Late.</span></button>
                            <button type="button" class="kla-quick-card" data-open-tool="students"><b>👨‍🎓 My Students</b><span>View all students and their Kudos balance.</span></button>
                        </div>
                    </section>

                    <!-- AWARD KUDOS -->
                    <section class="kla-teacher-panel" data-panel="award">
                        <div class="kla-panel-heading">
                            <span>STUDENT RECOGNITION</span>
                            <h2>Award Kudos</h2>
                            <p>Celebrate learning, effort, participation and positive behaviour.</p>
                        </div>

                        <?php
                        $award_message = '';
                        if ( isset( $_GET['kudos_award'] ) ) {
                            $award_status = sanitize_key( wp_unslash( $_GET['kudos_award'] ) );
                            if ( 'success' === $award_status ) {
                                $awarded_points = isset( $_GET['points'] ) ? absint( $_GET['points'] ) : 0;
                                $awarded_student = isset( $_GET['student'] ) ? sanitize_text_field( wp_unslash( $_GET['student'] ) ) : '';
                                $award_message = '<div class="kla-teacher-success">⭐ ' . esc_html( $awarded_points ) . ' Kudos awarded to ' . esc_html( $awarded_student ) . '.</div>';
                            } elseif ( 'error' === $award_status ) {
                                $award_error = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : 'Unable to award Kudos.';
                                $award_message = '<div class="kla-teacher-error">' . esc_html( $award_error ) . '</div>';
                            }
                        }
                        echo $award_message;
                        ?>

                        <div class="kla-teacher-form-card">
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="kla_teacher_award_kudos">
                                <?php wp_nonce_field( 'kla_teacher_award_kudos', 'kla_teacher_award_nonce' ); ?>

                                <div class="kla-form-grid">
                                    <label>Student
                                        <select name="student_id" required>
                                            <option value="">Select Student</option>
                                            <?php foreach ( $students as $student ) :
                                                $student_name = get_post_meta( $student->ID, '_kudos_student_name', true ) ?: $student->post_title;
                                                $parent_id = get_post_meta( $student->ID, '_kudos_student_parent', true );
                                                $parent_name = $parent_id ? get_post_meta( $parent_id, '_kudos_parent_name', true ) : '';
                                                ?>
                                                <option value="<?php echo esc_attr( $student->ID ); ?>">
                                                    <?php echo esc_html( $student_name . ( $parent_name ? ' — ' . $parent_name : '' ) ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label>Kudos Points
                                        <input type="number" name="points" min="1" step="1" required placeholder="Example: 10">
                                    </label>

                                    <label>Activity / Achievement
                                        <select name="activity_id">
                                            <option value="">Select Activity (Optional)</option>
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
                                            foreach ( $award_activities as $activity ) :
                                                ?>
                                                <option value="<?php echo esc_attr( $activity->ID ); ?>"><?php echo esc_html( $activity->post_title ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label class="wide">Achievement / Reason
                                        <input type="text" name="reason" maxlength="200" required placeholder="Example: Excellent performance in Mathematics">
                                    </label>
                                </div>

                                <button type="submit" class="kla-teacher-primary-button">⭐ Award Kudos</button>
                            </form>
                        </div>
                    </section>

                    <!-- PERFORMANCE -->
                    <section class="kla-teacher-panel" data-panel="performance">
                        <div class="kla-panel-heading">
                            <span>LEARNING PROGRESS</span>
                            <h2>Update Performance</h2>
                            <p>Select a student and update the latest performance record.</p>
                        </div>

                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kla-teacher-form-card">
                            <input type="hidden" name="action" value="kla_teacher_update_performance">
                            <?php wp_nonce_field( 'kla_teacher_performance_update', 'kla_teacher_performance_nonce' ); ?>
                            <div class="kla-form-grid">
                                <label>Student
                                    <select name="student_id" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ( $students as $student ) : ?>
                                            <option value="<?php echo esc_attr( $student->ID ); ?>"><?php echo esc_html( get_post_meta( $student->ID, '_kudos_student_name', true ) ?: $student->post_title ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Punctuality<input type="number" name="punctuality" min="0" max="100" placeholder="0-100" required></label>
                                <label>Discipline<input type="number" name="discipline" min="0" max="100" placeholder="0-100" required></label>
                                <label>Etiquettes<input type="number" name="etiquettes" min="0" max="100" placeholder="0-100" required></label>
                                <label>Workshop Participation<input type="number" name="workshops" min="0" max="100" placeholder="0-100" required></label>
                                <label>Learning Progress<input type="number" name="school" min="0" max="100" placeholder="0-100" required></label>
                                <label>Activity Participation<input type="number" name="participation" min="0" max="100" placeholder="0-100" required></label>
                            </div>
                            <button type="submit" class="kla-teacher-primary-button">Save Performance</button>
                        </form>
                    </section>

                    <!-- ATTENDANCE -->
                    <section class="kla-teacher-panel" data-panel="attendance">
                        <div class="kla-panel-heading">
                            <span>DAILY RECORD</span>
                            <h2>Mark Attendance</h2>
                            <p>Record Present, Absent or Late for a student.</p>
                        </div>

                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kla-teacher-form-card">
                            <input type="hidden" name="action" value="kla_teacher_save_attendance">
                            <?php wp_nonce_field( 'kla_teacher_attendance_save', 'kla_teacher_attendance_nonce' ); ?>
                            <div class="kla-form-grid">
                                <label>Student
                                    <select name="student_id" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ( $students as $student ) : ?>
                                            <option value="<?php echo esc_attr( $student->ID ); ?>"><?php echo esc_html( get_post_meta( $student->ID, '_kudos_student_name', true ) ?: $student->post_title ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Date<input type="date" name="attendance_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label>
                                <label>Status
                                    <select name="attendance_status" required>
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                    </select>
                                </label>
                                <label class="wide">Remarks<input type="text" name="attendance_remarks" maxlength="200" placeholder="Optional remark"></label>
                            </div>
                            <button type="submit" class="kla-teacher-primary-button">Save Attendance</button>
                        </form>
                    </section>

                    <!-- STUDENTS -->
                    <section class="kla-teacher-panel" data-panel="students">
                        <div class="kla-panel-heading">
                            <span>CLASS OVERVIEW</span>
                            <h2>My Students</h2>
                            <p>View the students currently registered in the academy.</p>
                        </div>

                        <div class="kla-student-list">
                            <?php if ( $students ) : foreach ( $students as $student ) :
                                $student_name = get_post_meta( $student->ID, '_kudos_student_name', true ) ?: $student->post_title;
                                $student_class = get_post_meta( $student->ID, '_kudos_student_class', true );
                                $student_status = get_post_meta( $student->ID, '_kudos_student_status', true ) ?: 'active';
                                $student_kudos = get_post_meta( $student->ID, '_kudos_student_rewards', true );
                                if ( '' === $student_kudos ) $student_kudos = 0;
                                ?>
                                <div class="kla-student-row">
                                    <div class="kla-student-avatar">👨‍🎓</div>
                                    <div class="kla-student-info">
                                        <strong><?php echo esc_html( $student_name ); ?></strong>
                                        <span><?php echo esc_html( $student_class ?: 'Academy Student' ); ?></span>
                                    </div>
                                    <div class="kla-student-kudos">⭐ <?php echo esc_html( $student_kudos ); ?> Kudos</div>
                                    <div class="kla-student-status <?php echo 'inactive' === $student_status ? 'inactive' : 'active'; ?>"><?php echo esc_html( ucfirst( $student_status ) ); ?></div>
                                </div>
                            <?php endforeach; else : ?>
                                <div class="kla-student-empty">No students found.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="kla-teacher-footer-note">
                        <span>🌱</span>
                        <div><strong>Every effort deserves recognition.</strong><p>Use Kudos to celebrate learning, progress and positive behaviour.</p></div>
                    </div>

                </main>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.kla-tool-nav, .kla-quick-card');
            const panels = document.querySelectorAll('.kla-teacher-panel');

            function showPanel(name) {
                panels.forEach(function (panel) {
                    panel.classList.toggle('active', panel.dataset.panel === name);
                });
                document.querySelectorAll('.kla-tool-nav').forEach(function (button) {
                    button.classList.toggle('active', button.dataset.tool === name);
                });
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    showPanel(this.dataset.tool || this.dataset.openTool);
                });
            });
        });
        </script>

        <style>
        .kla-teacher-portal{background:#fffaf0;min-height:100vh;width:100%;box-sizing:border-box;color:#30205a}
        .kla-teacher-portal-header{height:82px;background:#fff;border-bottom:1px solid #eee8dc;display:flex;align-items:center;justify-content:space-between;padding:0 max(28px,5vw);box-sizing:border-box}
        .kla-teacher-brand,.kla-teacher-user{display:flex;align-items:center;gap:12px}.kla-teacher-brand-icon{width:42px;height:42px;border-radius:13px;background:#eef6dd;display:grid;place-items:center;font-size:22px}.kla-teacher-brand strong{display:block;font-size:20px}.kla-teacher-brand span,.kla-teacher-user span{display:block;font-size:12px;color:#6f6978}.kla-teacher-user{text-align:right}.kla-teacher-user a{background:#2d7b3c;color:#fff!important;text-decoration:none!important;padding:10px 17px;border-radius:999px;font-weight:800;font-size:13px}
        .kla-teacher-portal-shell{max-width:1250px;margin:0 auto;padding:38px 25px 70px;display:grid;grid-template-columns:190px minmax(0,1fr);gap:24px;box-sizing:border-box}
        .kla-teacher-sidebar{background:#fff;border:1px solid #eee8dc;border-radius:20px;padding:16px 12px;height:max-content;position:sticky;top:25px;box-shadow:0 8px 25px rgba(48,32,90,.05)}
        .kla-teacher-sidebar-title{font-size:11px;letter-spacing:1.4px;font-weight:900;color:#77717f;padding:10px 12px 14px}.kla-tool-nav{width:100%;border:0;background:transparent;text-align:left;padding:12px 11px;border-radius:12px;color:#514b5d;font-weight:800;cursor:pointer;margin:2px 0;font-size:13px}.kla-tool-nav:hover{background:#f4f8ea}.kla-tool-nav.active{background:#2d7b3c;color:#fff;box-shadow:0 5px 14px rgba(45,123,60,.18)}
        .kla-teacher-main{min-width:0}.kla-teacher-panel{display:none}.kla-teacher-panel.active{display:block}.kla-panel-heading span{font-size:11px;letter-spacing:1.5px;font-weight:900;color:#2d7b3c}.kla-panel-heading h1,.kla-panel-heading h2{margin:4px 0 5px;font-size:31px;line-height:1.15}.kla-panel-heading p{margin:0 0 22px;color:#706a79;font-size:14px}
        .kla-teacher-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin:24px 0}.kla-stat-card{background:#fff;border:1px solid #eee8dc;border-radius:16px;padding:17px;box-shadow:0 7px 20px rgba(48,32,90,.04)}.kla-stat-card div{font-size:20px;margin-bottom:7px}.kla-stat-card span{display:block;color:#77717f;font-size:12px}.kla-stat-card strong{display:block;font-size:24px;margin-top:3px}
        .kla-quick-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}.kla-quick-card{border:1px solid #eee8dc;background:#fff;border-radius:17px;padding:21px;text-align:left;cursor:pointer;box-shadow:0 7px 20px rgba(48,32,90,.04)}.kla-quick-card:hover{border-color:#2d7b3c;transform:translateY(-1px)}.kla-quick-card b{display:block;font-size:16px;margin-bottom:6px}.kla-quick-card span{font-size:13px;color:#77717f}
        .kla-teacher-form-card{background:#fff;border:1px solid #eee8dc;border-radius:18px;padding:25px;box-shadow:0 8px 25px rgba(48,32,90,.05)}.kla-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}.kla-form-grid label{display:flex;flex-direction:column;gap:7px;color:#30205a;font-size:13px;font-weight:800}.kla-form-grid label.wide{grid-column:1/-1}.kla-form-grid input,.kla-form-grid select{width:100%;box-sizing:border-box;border:1px solid #ddd8cf;border-radius:11px;padding:12px;background:#fff;color:#30205a;font-size:14px}.kla-form-grid input:focus,.kla-form-grid select:focus{outline:none;border-color:#2d7b3c;box-shadow:0 0 0 3px rgba(45,123,60,.1)}.kla-teacher-primary-button{margin-top:20px;border:0;background:#2d7b3c;color:#fff;border-radius:11px;padding:12px 20px;font-weight:900;cursor:pointer}.kla-teacher-success{background:#eaf7ed;border:1px solid #b9dfc1;color:#216b30;padding:12px 15px;border-radius:11px;font-weight:800;margin-bottom:15px}.kla-teacher-error{background:#fdecec;border:1px solid #efb8b8;color:#a32222;padding:12px 15px;border-radius:11px;font-weight:800;margin-bottom:15px}
        .kla-student-list{display:flex;flex-direction:column;gap:9px}.kla-student-row{display:grid;grid-template-columns:45px minmax(0,1fr) auto auto;align-items:center;gap:13px;background:#fff;border:1px solid #eee8dc;border-radius:14px;padding:12px 15px}.kla-student-avatar{width:42px;height:42px;border-radius:12px;background:#eef6dd;display:grid;place-items:center}.kla-student-info strong{display:block}.kla-student-info span{font-size:12px;color:#77717f}.kla-student-kudos{font-weight:900;color:#2d7b3c;white-space:nowrap}.kla-student-status{font-size:10px;font-weight:900;text-transform:uppercase;padding:5px 9px;border-radius:999px}.kla-student-status.active{background:#eaf7ed;color:#2d7b3c}.kla-student-status.inactive{background:#fdeaea;color:#b42318}.kla-student-empty{text-align:center;background:#fff;border:1px solid #eee8dc;border-radius:15px;padding:30px;color:#77717f}.kla-teacher-footer-note{margin-top:22px;background:#eef6dd;border-radius:16px;padding:17px 20px;display:flex;gap:12px;align-items:center}.kla-teacher-footer-note span{font-size:25px}.kla-teacher-footer-note strong{font-size:14px}.kla-teacher-footer-note p{margin:3px 0 0;font-size:12px;color:#6f6978}
        @media(max-width:850px){.kla-teacher-portal-shell{grid-template-columns:1fr}.kla-teacher-sidebar{position:static;display:flex;overflow-x:auto;gap:5px;padding:10px}.kla-teacher-sidebar-title{display:none}.kla-tool-nav{white-space:nowrap;width:auto;flex:0 0 auto}.kla-teacher-stats{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:600px){.kla-teacher-portal-header{padding:0 16px}.kla-teacher-brand-icon{display:none}.kla-teacher-user a{padding:8px 12px}.kla-teacher-portal-shell{padding:20px 15px 45px}.kla-form-grid,.kla-quick-grid{grid-template-columns:1fr}.kla-form-grid label.wide{grid-column:auto}.kla-student-row{grid-template-columns:42px minmax(0,1fr)}.kla-student-kudos,.kla-student-status{grid-column:2;justify-self:start}.kla-panel-heading h1,.kla-panel-heading h2{font-size:26px}}
        </style>
        <?php

        return ob_get_clean();
    }

    add_filter( 'the_content', 'kla_teacher_dashboard_tools_content', 99 );
}

/* =========================================================
   ADMIN ATTENDANCE COLUMNS
   ========================================================= */

if ( ! function_exists( 'kla_attendance_columns' ) ) {
    function kla_attendance_columns( $columns ) {
        return array(
            'cb'       => '<input type="checkbox" />',
            'title'    => 'Record',
            'student'  => 'Student',
            'date'     => 'Date',
            'status'   => 'Status',
            'teacher'  => 'Teacher',
        );
    }
    add_filter( 'manage_kudos_attendance_posts_columns', 'kla_attendance_columns' );
}

if ( ! function_exists( 'kla_attendance_column_content' ) ) {
    function kla_attendance_column_content( $column, $post_id ) {
        if ( $column === 'student' ) {
            $student_id = absint( get_post_meta( $post_id, '_kla_attendance_student_id', true ) );
            echo esc_html( $student_id ? ( get_post_meta( $student_id, '_kudos_student_name', true ) ?: get_the_title( $student_id ) ) : '—' );
        } elseif ( $column === 'date' ) {
            echo esc_html( get_post_meta( $post_id, '_kla_attendance_date', true ) ?: '—' );
        } elseif ( $column === 'status' ) {
            echo esc_html( ucfirst( get_post_meta( $post_id, '_kla_attendance_status', true ) ?: '—' ) );
        } elseif ( $column === 'teacher' ) {
            $teacher_id = absint( get_post_meta( $post_id, '_kla_attendance_teacher_id', true ) );
            echo esc_html( $teacher_id ? ( get_the_author_meta( 'display_name', $teacher_id ) ?: '—' ) : '—' );
        }
    }
    add_action( 'manage_kudos_attendance_posts_custom_column', 'kla_attendance_column_content', 10, 2 );
}
