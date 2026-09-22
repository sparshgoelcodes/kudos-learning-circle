<?php
/**
 * Kudos Learning Academy
 * Teacher Attendance Module
 *
 * Adds a standalone Attendance UI to the existing teacher dashboard.
 * Does not replace teachers.php or teacher-tools.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX: Load attendance history for a student.
 */
function kla_teacher_get_attendance() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Teacher access required.' ), 403 );
    }

    check_ajax_referer( 'kla_teacher_attendance_nonce', 'nonce' );

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

    if ( ! $student_id || 'kudos_student' !== get_post_type( $student_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid student.' ), 400 );
    }

    $student_name = get_post_meta( $student_id, '_kudos_student_name', true );

    if ( ! $student_name ) {
        $student_name = get_the_title( $student_id );
    }

    $records = new WP_Query(
        array(
            'post_type'      => 'kudos_attendance',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 20,
            'orderby'        => 'meta_value',
            'meta_key'       => '_kudos_attendance_date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kudos_attendance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    $history = array();

    if ( $records->have_posts() ) {
        while ( $records->have_posts() ) {
            $records->the_post();

            $post_id = get_the_ID();

            $history[] = array(
                'id'       => $post_id,
                'date'     => get_post_meta( $post_id, '_kudos_attendance_date', true ),
                'status'   => get_post_meta( $post_id, '_kudos_attendance_status', true ),
                'remarks'  => get_post_meta( $post_id, '_kudos_attendance_remarks', true ),
                'teacher'  => get_post_meta( $post_id, '_kudos_attendance_teacher_name', true ),
            );
        }

        wp_reset_postdata();
    }

    wp_send_json_success(
        array(
            'student_name' => $student_name,
            'history'      => $history,
        )
    );
}

/**
 * AJAX: Save attendance.
 */
function kla_teacher_save_attendance_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Teacher access required.' ), 403 );
    }

    check_ajax_referer( 'kla_teacher_attendance_nonce', 'nonce' );

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    $date       = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
    $status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
    $remarks    = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';

    if ( ! $student_id || 'kudos_student' !== get_post_type( $student_id ) ) {
        wp_send_json_error( array( 'message' => 'Please select a valid student.' ), 400 );
    }

    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        wp_send_json_error( array( 'message' => 'Please select a valid date.' ), 400 );
    }

    $allowed_statuses = array( 'Present', 'Absent', 'Late' );

    if ( ! in_array( $status, $allowed_statuses, true ) ) {
        wp_send_json_error( array( 'message' => 'Please select a valid attendance status.' ), 400 );
    }

    $student_name = get_post_meta( $student_id, '_kudos_student_name', true );

    if ( ! $student_name ) {
        $student_name = get_the_title( $student_id );
    }

    /*
     * One attendance record per student per date.
     * If one already exists, update it instead of creating a duplicate.
     */
    $existing = new WP_Query(
        array(
            'post_type'      => 'kudos_attendance',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_kudos_attendance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kudos_attendance_date',
                    'value'   => $date,
                    'compare' => '=',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    $attendance_id = ! empty( $existing->posts ) ? absint( $existing->posts[0] ) : 0;

    $title = $student_name . ' - Attendance - ' . $date;

    if ( $attendance_id ) {
        wp_update_post(
            array(
                'ID'         => $attendance_id,
                'post_title' => $title,
                'post_status'=> 'publish',
            )
        );
    } else {
        $attendance_id = wp_insert_post(
            array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'kudos_attendance',
            ),
            true
        );

        if ( is_wp_error( $attendance_id ) ) {
            wp_send_json_error(
                array(
                    'message' => $attendance_id->get_error_message(),
                ),
                500
            );
        }
    }

    update_post_meta( $attendance_id, '_kudos_attendance_student', $student_id );
    update_post_meta( $attendance_id, '_kudos_attendance_student_name', $student_name );
    update_post_meta( $attendance_id, '_kudos_attendance_date', $date );
    update_post_meta( $attendance_id, '_kudos_attendance_status', $status );
    update_post_meta( $attendance_id, '_kudos_attendance_remarks', $remarks );
    update_post_meta( $attendance_id, '_kudos_attendance_teacher', $user->ID );
    update_post_meta( $attendance_id, '_kudos_attendance_teacher_name', $user->display_name ? $user->display_name : $user->user_login );
    update_post_meta( $attendance_id, '_kudos_attendance_updated_at', current_time( 'mysql' ) );

    wp_send_json_success(
        array(
            'message' => $existing->have_posts()
                ? 'Attendance updated successfully.'
                : 'Attendance saved successfully.',
            'attendance_id' => $attendance_id,
        )
    );
}

add_action( 'wp_ajax_kla_teacher_get_attendance', 'kla_teacher_get_attendance' );
add_action( 'wp_ajax_kla_teacher_save_attendance', 'kla_teacher_save_attendance_ajax' );

/**
 * Render Attendance panel inside the existing Teacher Dashboard.
 */
function kla_teacher_attendance_panel() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        return;
    }

    $students = get_posts(
        array(
            'post_type'      => 'kudos_student',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'kla_teacher_attendance_nonce' );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.querySelector('[data-panel="attendance"]');

        if (!panel) {
            return;
        }

        const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;

        const students = <?php
            $student_options = array();

            foreach ( $students as $student ) {
                $name = get_post_meta( $student->ID, '_kudos_student_name', true );

                if ( ! $name ) {
                    $name = $student->post_title;
                }

                $student_options[] = array(
                    'id'   => $student->ID,
                    'name' => $name,
                );
            }

            echo wp_json_encode( $student_options );
        ?>;

        function esc(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function today() {
            const d = new Date();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + month + '-' + day;
        }

        function statusClass(status) {
            if (status === 'Present') return 'kla-att-present';
            if (status === 'Absent') return 'kla-att-absent';
            return 'kla-att-late';
        }

        function renderPanel() {
            panel.innerHTML = `
                <div class="kla-attendance-panel">
                    <div class="kla-attendance-heading">
                        <div>
                            <span class="kla-attendance-eyebrow">Teacher Tools</span>
                            <h2>Attendance</h2>
                            <p>Mark and update daily attendance for your students.</p>
                        </div>
                    </div>

                    <div class="kla-attendance-form-card">
                        <div class="kla-attendance-form-grid">
                            <label>
                                <span>Student</span>
                                <select id="kla-att-student">
                                    <option value="">Select Student</option>
                                    ${students.map(function(s) {
                                        return '<option value="' + esc(s.id) + '">' + esc(s.name) + '</option>';
                                    }).join('')}
                                </select>
                            </label>

                            <label>
                                <span>Date</span>
                                <input type="date" id="kla-att-date" value="${today()}">
                            </label>

                            <label>
                                <span>Status</span>
                                <select id="kla-att-status">
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                    <option value="Late">Late</option>
                                </select>
                            </label>
                        </div>

                        <label class="kla-attendance-remarks">
                            <span>Remarks</span>
                            <textarea id="kla-att-remarks" rows="3" placeholder="Optional remarks..."></textarea>
                        </label>

                        <div class="kla-attendance-actions">
                            <button type="button" class="kla-attendance-save" id="kla-att-save">
                                Save Attendance
                            </button>
                            <span class="kla-attendance-message" id="kla-att-message"></span>
                        </div>
                    </div>

                    <div class="kla-attendance-history-card">
                        <div class="kla-attendance-history-header">
                            <div>
                                <h3>Attendance History</h3>
                                <p id="kla-att-history-subtitle">Select a student to view history.</p>
                            </div>
                        </div>

                        <div id="kla-att-history">
                            <div class="kla-attendance-empty">No student selected.</div>
                        </div>
                    </div>
                </div>
            `;

            bindEvents();
        }

        function bindEvents() {
            const student = document.getElementById('kla-att-student');
            const date = document.getElementById('kla-att-date');
            const status = document.getElementById('kla-att-status');
            const remarks = document.getElementById('kla-att-remarks');
            const save = document.getElementById('kla-att-save');
            const message = document.getElementById('kla-att-message');
            const history = document.getElementById('kla-att-history');
            const subtitle = document.getElementById('kla-att-history-subtitle');

            student.addEventListener('change', function () {
                loadHistory(student.value);
            });

            save.addEventListener('click', function () {
                if (!student.value) {
                    showMessage('Please select a student.', true);
                    return;
                }

                if (!date.value) {
                    showMessage('Please select a date.', true);
                    return;
                }

                save.disabled = true;
                save.textContent = 'Saving...';
                message.textContent = '';

                const data = new FormData();
                data.append('action', 'kla_teacher_save_attendance');
                data.append('nonce', nonce);
                data.append('student_id', student.value);
                data.append('date', date.value);
                data.append('status', status.value);
                data.append('remarks', remarks.value);

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(result.data && result.data.message ? result.data.message : 'Unable to save attendance.');
                    }

                    showMessage(result.data.message, false);
                    loadHistory(student.value);
                })
                .catch(function (error) {
                    showMessage(error.message, true);
                })
                .finally(function () {
                    save.disabled = false;
                    save.textContent = 'Save Attendance';
                });
            });

            function showMessage(text, isError) {
                message.textContent = text;
                message.className = 'kla-attendance-message ' + (isError ? 'is-error' : 'is-success');
            }

            function loadHistory(studentId) {
                if (!studentId) {
                    history.innerHTML = '<div class="kla-attendance-empty">No student selected.</div>';
                    subtitle.textContent = 'Select a student to view history.';
                    return;
                }

                history.innerHTML = '<div class="kla-attendance-loading">Loading attendance...</div>';

                const data = new FormData();
                data.append('action', 'kla_teacher_get_attendance');
                data.append('nonce', nonce);
                data.append('student_id', studentId);

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(result.data && result.data.message ? result.data.message : 'Unable to load attendance.');
                    }

                    const payload = result.data;
                    subtitle.textContent = payload.student_name + ' · Latest 20 records';

                    if (!payload.history || !payload.history.length) {
                        history.innerHTML = '<div class="kla-attendance-empty">No attendance records found for this student.</div>';
                        return;
                    }

                    history.innerHTML = `
                        <div class="kla-attendance-table-wrap">
                            <table class="kla-attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${payload.history.map(function(row) {
                                        return `
                                            <tr>
                                                <td>${esc(row.date)}</td>
                                                <td><span class="kla-att-status ${statusClass(row.status)}">${esc(row.status)}</span></td>
                                                <td>${esc(row.remarks || '—')}</td>
                                                <td>${esc(row.teacher || '—')}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                })
                .catch(function (error) {
                    history.innerHTML = '<div class="kla-attendance-empty is-error">' + esc(error.message) + '</div>';
                });
            }
        }

        const style = document.createElement('style');
        style.textContent = `
            .kla-attendance-panel{padding:2px 0 24px}
            .kla-attendance-heading{margin-bottom:22px}
            .kla-attendance-eyebrow{display:inline-block;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#2d7b3c;margin-bottom:5px}
            .kla-attendance-heading h2{margin:0;color:#30205a;font-size:30px;line-height:1.15}
            .kla-attendance-heading p{margin:8px 0 0;color:#6b6677;font-size:14px}
            .kla-attendance-form-card,.kla-attendance-history-card{background:#fff;border:1px solid #ece7d9;border-radius:18px;padding:22px;box-shadow:0 8px 24px rgba(48,32,90,.06);margin-bottom:18px}
            .kla-attendance-form-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px}
            .kla-attendance-form-card label{display:block}
            .kla-attendance-form-card label>span{display:block;font-size:13px;font-weight:700;color:#30205a;margin-bottom:7px}
            .kla-attendance-form-card select,.kla-attendance-form-card input,.kla-attendance-form-card textarea{width:100%;box-sizing:border-box;border:1px solid #ddd7ca;border-radius:10px;background:#fff;padding:11px 12px;color:#403a4b;font-size:14px;outline:none}
            .kla-attendance-form-card select:focus,.kla-attendance-form-card input:focus,.kla-attendance-form-card textarea:focus{border-color:#2d7b3c;box-shadow:0 0 0 3px rgba(45,123,60,.08)}
            .kla-attendance-remarks{margin-top:16px}
            .kla-attendance-actions{display:flex;align-items:center;gap:14px;margin-top:18px;flex-wrap:wrap}
            .kla-attendance-save{border:0;border-radius:10px;background:#2d7b3c;color:#fff;padding:12px 20px;font-weight:800;cursor:pointer}
            .kla-attendance-save:hover{filter:brightness(.95)}
            .kla-attendance-save:disabled{opacity:.65;cursor:wait}
            .kla-attendance-message{font-size:13px;font-weight:700}
            .kla-attendance-message.is-success{color:#2d7b3c}
            .kla-attendance-message.is-error{color:#b42318}
            .kla-attendance-history-header{margin-bottom:14px}
            .kla-attendance-history-header h3{margin:0;color:#30205a;font-size:20px}
            .kla-attendance-history-header p{margin:5px 0 0;color:#777080;font-size:13px}
            .kla-attendance-table-wrap{overflow:auto}
            .kla-attendance-table{width:100%;border-collapse:collapse;min-width:650px}
            .kla-attendance-table th{background:#f7f4eb;color:#50495c;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:11px}
            .kla-attendance-table td{padding:12px 11px;border-top:1px solid #eee9df;color:#5d5867;font-size:13px;vertical-align:top}
            .kla-att-status{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800}
            .kla-att-present{background:#e7f5e8;color:#26743a}
            .kla-att-absent{background:#fdeaea;color:#b42318}
            .kla-att-late{background:#fff3d6;color:#986500}
            .kla-attendance-empty,.kla-attendance-loading{padding:24px;text-align:center;border:1px dashed #ddd7ca;border-radius:12px;color:#777080;font-size:13px}
            .kla-attendance-empty.is-error{color:#b42318}
            @media(max-width:760px){
                .kla-attendance-form-grid{grid-template-columns:1fr}
                .kla-attendance-form-card,.kla-attendance-history-card{padding:16px}
                .kla-attendance-heading h2{font-size:25px}
            }
        `;
        document.head.appendChild(style);

        renderPanel();
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'kla_teacher_attendance_panel', 30 );
