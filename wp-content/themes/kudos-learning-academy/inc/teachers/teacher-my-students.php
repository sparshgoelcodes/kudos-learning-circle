<?php
/**
 * Kudos Learning Academy
 * Teacher - My Students Module
 *
 * Standalone module for the existing Teacher Dashboard.
 * Does not replace teachers.php, teacher-tools.php,
 * teacher-performance.php or teacher-attendance.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get student display name.
 */
function kla_my_students_name( $student_id ) {
    $name = get_post_meta( $student_id, '_kudos_student_name', true );

    return $name ? $name : get_the_title( $student_id );
}

/**
 * Get latest performance record for a student.
 */
function kla_my_students_performance( $student_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'kudos_performance',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_kudos_performance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_kudos_performance_student_name',
                    'value'   => kla_my_students_name( $student_id ),
                    'compare' => '=',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    if ( ! $query->have_posts() ) {
        return null;
    }

    $query->the_post();

    $id = get_the_ID();

    $data = array(
        'id'            => $id,
        'punctuality'   => get_post_meta( $id, '_kudos_performance_punctuality', true ),
        'discipline'    => get_post_meta( $id, '_kudos_performance_discipline', true ),
        'fees'          => get_post_meta( $id, '_kudos_performance_fees', true ),
        'etiquettes'    => get_post_meta( $id, '_kudos_performance_etiquettes', true ),
        'workshops'     => get_post_meta( $id, '_kudos_performance_workshops', true ),
        'participation' => get_post_meta( $id, '_kudos_performance_participation', true ),
        'remarks'       => get_post_meta( $id, '_kudos_performance_teacher_remarks', true ),
        'updated_by'    => get_post_meta( $id, '_kudos_performance_updated_by_name', true ),
        'updated_at'    => get_post_meta( $id, '_kudos_performance_updated_at', true ),
    );

    wp_reset_postdata();

    return $data;
}

/**
 * Get latest attendance record for a student.
 */
function kla_my_students_attendance( $student_id ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'kudos_attendance',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 1,
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

    if ( ! $query->have_posts() ) {
        return null;
    }

    $query->the_post();

    $id = get_the_ID();

    $data = array(
        'date'    => get_post_meta( $id, '_kudos_attendance_date', true ),
        'status'  => get_post_meta( $id, '_kudos_attendance_status', true ),
        'remarks' => get_post_meta( $id, '_kudos_attendance_remarks', true ),
    );

    wp_reset_postdata();

    return $data;
}

/**
 * Get parent name from linked parent record.
 */
function kla_my_students_parent_name( $student_id ) {
    $parent_id = absint( get_post_meta( $student_id, '_kudos_student_parent', true ) );

    if ( ! $parent_id || 'kudos_parent' !== get_post_type( $parent_id ) ) {
        return 'Not linked';
    }

    $name = get_post_meta( $parent_id, '_kudos_parent_name', true );

    return $name ? $name : get_the_title( $parent_id );
}

/**
 * Get recent Kudos transactions.
 */
function kla_my_students_kudos_history( $student_id, $limit = 5 ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'kudos_transaction',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kla_transaction_student_id',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
        )
    );

    $items = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $id     = get_the_ID();
            $type   = get_post_meta( $id, '_kla_transaction_type', true );
            $points = absint( get_post_meta( $id, '_kla_transaction_points', true ) );

            $is_negative = in_array( strtolower( $type ), array( 'redemption', 'redeem' ), true );

            $items[] = array(
                'points' => ( $is_negative ? '-' : '+' ) . $points,
                'reason' => get_post_meta( $id, '_kla_transaction_reason', true ),
                'date'   => get_the_date( 'd M Y', $id ),
                'type'   => $type,
            );
        }

        wp_reset_postdata();
    }

    return $items;
}

/**
 * AJAX student details.
 */
function kla_teacher_my_student_details() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Teacher access required.' ), 403 );
    }

    check_ajax_referer( 'kla_my_students_nonce', 'nonce' );

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

    if ( ! $student_id || 'kudos_student' !== get_post_type( $student_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid student.' ), 400 );
    }

    $performance = kla_my_students_performance( $student_id );
    $attendance  = kla_my_students_attendance( $student_id );
    $balance     = absint( get_post_meta( $student_id, '_kudos_student_rewards', true ) );

    wp_send_json_success(
        array(
            'student_name' => kla_my_students_name( $student_id ),
            'class'        => get_post_meta( $student_id, '_kudos_student_class', true ),
            'parent'       => kla_my_students_parent_name( $student_id ),
            'balance'      => $balance,
            'performance'  => $performance,
            'attendance'   => $attendance,
            'history'      => kla_my_students_kudos_history( $student_id, 5 ),
        )
    );
}

add_action( 'wp_ajax_kla_teacher_my_student_details', 'kla_teacher_my_student_details' );

/**
 * Render My Students panel.
 */
function kla_teacher_my_students_panel() {
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

    $student_data = array();

    foreach ( $students as $student ) {
        $student_id = $student->ID;
        $student_data[] = array(
            'id'      => $student_id,
            'name'    => kla_my_students_name( $student_id ),
            'class'   => get_post_meta( $student_id, '_kudos_student_class', true ),
            'parent'  => kla_my_students_parent_name( $student_id ),
            'balance' => absint( get_post_meta( $student_id, '_kudos_student_rewards', true ) ),
        );
    }

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'kla_my_students_nonce' );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.querySelector('[data-panel="students"]');

        if (!panel) {
            return;
        }

        const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;
        const students = <?php echo wp_json_encode( $student_data ); ?>;

        function esc(value) {
            return String(value === undefined || value === null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function performanceAverage(performance) {
            if (!performance) return '—';

            const fields = [
                'punctuality',
                'discipline',
                'fees',
                'etiquettes',
                'workshops',
                'participation'
            ];

            const values = fields
                .map(function (field) {
                    return parseFloat(performance[field]);
                })
                .filter(function (value) {
                    return !isNaN(value);
                });

            if (!values.length) return '—';

            const total = values.reduce(function (sum, value) {
                return sum + value;
            }, 0);

            return Math.round(total / values.length) + '/100';
        }

        function attendanceLabel(attendance) {
            if (!attendance) return 'No record';
            return attendance.status || 'No record';
        }

        function attendanceClass(status) {
            if (status === 'Present') return 'kla-ms-present';
            if (status === 'Absent') return 'kla-ms-absent';
            if (status === 'Late') return 'kla-ms-late';
            return '';
        }

        function render() {
            panel.innerHTML = `
                <div class="kla-my-students">
                    <div class="kla-my-students-header">
                        <div>
                            <span class="kla-ms-eyebrow">Teacher Tools</span>
                            <h2>My Students</h2>
                            <p>View each student's current learning, attendance and Kudos activity.</p>
                        </div>
                        <div class="kla-ms-count">${students.length} Student${students.length === 1 ? '' : 's'}</div>
                    </div>

                    <div class="kla-ms-search-row">
                        <input type="search" id="kla-ms-search" placeholder="Search student or parent...">
                    </div>

                    <div class="kla-ms-table-wrap">
                        <table class="kla-ms-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class / Batch</th>
                                    <th>Parent</th>
                                    <th>Kudos</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="kla-ms-table-body"></tbody>
                        </table>
                    </div>

                    <div class="kla-ms-mobile-list" id="kla-ms-mobile-list"></div>

                    <div class="kla-ms-details" id="kla-ms-details" hidden></div>
                </div>
            `;

            const search = document.getElementById('kla-ms-search');
            search.addEventListener('input', function () {
                drawRows(search.value);
            });

            drawRows('');
        }

        function drawRows(term) {
            const body = document.getElementById('kla-ms-table-body');
            const mobile = document.getElementById('kla-ms-mobile-list');

            const query = String(term || '').trim().toLowerCase();

            const filtered = students.filter(function (student) {
                return !query ||
                    student.name.toLowerCase().indexOf(query) !== -1 ||
                    String(student.parent || '').toLowerCase().indexOf(query) !== -1 ||
                    String(student.class || '').toLowerCase().indexOf(query) !== -1;
            });

            body.innerHTML = filtered.map(function (student) {
                return `
                    <tr>
                        <td><strong>${esc(student.name)}</strong></td>
                        <td>${esc(student.class || '—')}</td>
                        <td>${esc(student.parent || '—')}</td>
                        <td><span class="kla-ms-kudos">${esc(student.balance)} 🪙</span></td>
                        <td>
                            <button type="button" class="kla-ms-view" data-student="${esc(student.id)}">
                                View Details
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            mobile.innerHTML = filtered.map(function (student) {
                return `
                    <div class="kla-ms-mobile-card">
                        <div class="kla-ms-mobile-top">
                            <strong>${esc(student.name)}</strong>
                            <span class="kla-ms-kudos">${esc(student.balance)} 🪙</span>
                        </div>
                        <div class="kla-ms-mobile-meta">
                            <span>${esc(student.class || 'Class not set')}</span>
                            <span>Parent: ${esc(student.parent || 'Not linked')}</span>
                        </div>
                        <button type="button" class="kla-ms-view" data-student="${esc(student.id)}">
                            View Details
                        </button>
                    </div>
                `;
            }).join('');

            if (!filtered.length) {
                body.innerHTML = '<tr><td colspan="5" class="kla-ms-empty">No students found.</td></tr>';
                mobile.innerHTML = '<div class="kla-ms-empty">No students found.</div>';
            }

            document.querySelectorAll('.kla-ms-view').forEach(function (button) {
                button.addEventListener('click', function () {
                    loadDetails(button.getAttribute('data-student'));
                });
            });
        }

        function loadDetails(studentId) {
            const details = document.getElementById('kla-ms-details');

            details.hidden = false;
            details.innerHTML = '<div class="kla-ms-loading">Loading student details...</div>';
            details.scrollIntoView({ behavior: 'smooth', block: 'start' });

            const data = new FormData();
            data.append('action', 'kla_teacher_my_student_details');
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
                    throw new Error(result.data && result.data.message ? result.data.message : 'Unable to load student.');
                }

                drawDetails(result.data);
            })
            .catch(function (error) {
                details.innerHTML = '<div class="kla-ms-empty kla-ms-error">' + esc(error.message) + '</div>';
            });
        }

        function drawDetails(data) {
            const details = document.getElementById('kla-ms-details');
            const p = data.performance;
            const a = data.attendance;

            const performanceFields = [
                ['Punctuality', p ? p.punctuality : '—'],
                ['Discipline', p ? p.discipline : '—'],
                ['Fees', p ? p.fees : '—'],
                ['Etiquettes', p ? p.etiquettes : '—'],
                ['Workshops', p ? p.workshops : '—'],
                ['Participation', p ? p.participation : '—']
            ];

            details.innerHTML = `
                <div class="kla-ms-detail-head">
                    <div>
                        <span class="kla-ms-eyebrow">Student Profile</span>
                        <h3>${esc(data.student_name)}</h3>
                        <p>${esc(data.class || 'Class / Batch not set')} · Parent: ${esc(data.parent)}</p>
                    </div>
                    <button type="button" class="kla-ms-close" id="kla-ms-close">Close</button>
                </div>

                <div class="kla-ms-summary">
                    <div class="kla-ms-summary-card">
                        <span>Kudos Balance</span>
                        <strong>${esc(data.balance)} 🪙</strong>
                    </div>
                    <div class="kla-ms-summary-card">
                        <span>Performance Average</span>
                        <strong>${esc(performanceAverage(p))}</strong>
                    </div>
                    <div class="kla-ms-summary-card">
                        <span>Latest Attendance</span>
                        <strong class="${attendanceClass(a ? a.status : '')}">${esc(attendanceLabel(a))}</strong>
                    </div>
                </div>

                <div class="kla-ms-detail-grid">
                    <section class="kla-ms-section">
                        <div class="kla-ms-section-title">
                            <h4>Performance</h4>
                            <span>${p && p.updated_at ? esc(p.updated_at) : 'No record'}</span>
                        </div>
                        <div class="kla-ms-performance-grid">
                            ${performanceFields.map(function (field) {
                                return `
                                    <div class="kla-ms-score">
                                        <span>${esc(field[0])}</span>
                                        <strong>${esc(field[1])}${field[1] !== '—' ? '/100' : ''}</strong>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                        <div class="kla-ms-remark">
                            <span>Teacher Remarks</span>
                            <p>${esc(p && p.remarks ? p.remarks : 'No remarks added yet.')}</p>
                        </div>
                    </section>

                    <section class="kla-ms-section">
                        <div class="kla-ms-section-title">
                            <h4>Attendance</h4>
                            <span>${a && a.date ? esc(a.date) : 'No record'}</span>
                        </div>
                        <div class="kla-ms-attendance-box">
                            <span class="${attendanceClass(a ? a.status : '')}">
                                ${esc(a ? a.status : 'No attendance record')}
                            </span>
                            <p>${esc(a && a.remarks ? a.remarks : 'No attendance remarks.')}</p>
                        </div>
                    </section>

                    <section class="kla-ms-section kla-ms-kudos-section">
                        <div class="kla-ms-section-title">
                            <h4>Recent Kudos Activity</h4>
                            <span>Latest 5</span>
                        </div>

                        ${
                            data.history && data.history.length
                            ? `<div class="kla-ms-kudos-history">
                                ${data.history.map(function (item) {
                                    const positive = String(item.points).charAt(0) !== '-';
                                    return `
                                        <div class="kla-ms-kudos-row">
                                            <strong class="${positive ? 'kla-ms-positive' : 'kla-ms-negative'}">${esc(item.points)}</strong>
                                            <div>
                                                <span>${esc(item.reason || 'Kudos transaction')}</span>
                                                <small>${esc(item.date)}</small>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                              </div>`
                            : '<div class="kla-ms-empty">No Kudos transactions found.</div>'
                        }
                    </section>
                </div>
            `;

            document.getElementById('kla-ms-close').addEventListener('click', function () {
                details.hidden = true;
                details.innerHTML = '';
            });
        }

        const style = document.createElement('style');

        style.textContent = `
            .kla-my-students{padding:2px 0 30px}
            .kla-my-students-header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px}
            .kla-ms-eyebrow{display:inline-block;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#2d7b3c;margin-bottom:5px}
            .kla-my-students h2{margin:0;color:#30205a;font-size:30px;line-height:1.15}
            .kla-my-students-header p{margin:8px 0 0;color:#6b6677;font-size:14px}
            .kla-ms-count{background:#eef6dd;color:#2d7b3c;border-radius:999px;padding:8px 13px;font-size:12px;font-weight:800;white-space:nowrap}
            .kla-ms-search-row{margin-bottom:14px}
            .kla-ms-search-row input{width:100%;box-sizing:border-box;border:1px solid #ddd7ca;border-radius:11px;padding:12px 14px;background:#fff;color:#403a4b;outline:none}
            .kla-ms-search-row input:focus{border-color:#2d7b3c;box-shadow:0 0 0 3px rgba(45,123,60,.08)}
            .kla-ms-table-wrap{background:#fff;border:1px solid #ece7d9;border-radius:16px;overflow:auto;box-shadow:0 8px 24px rgba(48,32,90,.05)}
            .kla-ms-table{width:100%;border-collapse:collapse;min-width:720px}
            .kla-ms-table th{padding:13px 14px;text-align:left;background:#f7f4eb;color:#50495c;font-size:11px;text-transform:uppercase;letter-spacing:.05em}
            .kla-ms-table td{padding:14px;border-top:1px solid #eee9df;color:#5d5867;font-size:13px;vertical-align:middle}
            .kla-ms-table td strong{color:#30205a}
            .kla-ms-kudos{font-weight:800;color:#986500;white-space:nowrap}
            .kla-ms-view{border:0;border-radius:9px;background:#30205a;color:#fff;padding:9px 13px;font-size:12px;font-weight:800;cursor:pointer}
            .kla-ms-view:hover{filter:brightness(.94)}
            .kla-ms-mobile-list{display:none}
            .kla-ms-empty,.kla-ms-loading{padding:24px;text-align:center;color:#777080;font-size:13px}
            .kla-ms-error{color:#b42318}
            .kla-ms-details{margin-top:20px;background:#fff;border:1px solid #ece7d9;border-radius:18px;padding:22px;box-shadow:0 8px 24px rgba(48,32,90,.06)}
            .kla-ms-detail-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start}
            .kla-ms-detail-head h3{margin:0;color:#30205a;font-size:25px}
            .kla-ms-detail-head p{margin:6px 0 0;color:#777080;font-size:13px}
            .kla-ms-close{border:1px solid #ddd7ca;background:#fff;color:#50495c;border-radius:9px;padding:8px 12px;font-weight:700;cursor:pointer}
            .kla-ms-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}
            .kla-ms-summary-card{border:1px solid #eee9df;border-radius:13px;padding:15px;background:#fffaf0}
            .kla-ms-summary-card span{display:block;color:#777080;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
            .kla-ms-summary-card strong{display:block;margin-top:7px;color:#30205a;font-size:20px}
            .kla-ms-detail-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:16px}
            .kla-ms-section{border:1px solid #eee9df;border-radius:14px;padding:17px}
            .kla-ms-kudos-section{grid-column:1/-1}
            .kla-ms-section-title{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:13px}
            .kla-ms-section-title h4{margin:0;color:#30205a;font-size:17px}
            .kla-ms-section-title span{color:#8a8491;font-size:11px}
            .kla-ms-performance-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}
            .kla-ms-score{border:1px solid #eee9df;border-radius:10px;padding:11px}
            .kla-ms-score span{display:block;color:#777080;font-size:11px}
            .kla-ms-score strong{display:block;color:#30205a;font-size:17px;margin-top:4px}
            .kla-ms-remark{margin-top:13px;padding-top:13px;border-top:1px solid #eee9df}
            .kla-ms-remark span{font-size:11px;font-weight:800;text-transform:uppercase;color:#777080}
            .kla-ms-remark p{margin:6px 0 0;color:#5d5867;font-size:13px}
            .kla-ms-attendance-box{border-radius:12px;background:#f7f4eb;padding:18px}
            .kla-ms-attendance-box span{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800}
            .kla-ms-attendance-box p{margin:11px 0 0;color:#5d5867;font-size:13px}
            .kla-ms-present{color:#26743a!important;background:#e7f5e8!important}
            .kla-ms-absent{color:#b42318!important;background:#fdeaea!important}
            .kla-ms-late{color:#986500!important;background:#fff3d6!important}
            .kla-ms-kudos-history{border-top:1px solid #eee9df}
            .kla-ms-kudos-row{display:flex;gap:13px;align-items:center;padding:12px 2px;border-bottom:1px solid #eee9df}
            .kla-ms-kudos-row>strong{font-size:17px;min-width:40px}
            .kla-ms-kudos-row span{display:block;color:#50495c;font-size:13px}
            .kla-ms-kudos-row small{display:block;color:#8a8491;font-size:11px;margin-top:3px}
            .kla-ms-positive{color:#2d7b3c}
            .kla-ms-negative{color:#b42318}
            @media(max-width:800px){
                .kla-ms-detail-grid{grid-template-columns:1fr}
                .kla-ms-kudos-section{grid-column:auto}
                .kla-ms-summary{grid-template-columns:1fr}
            }
            @media(max-width:650px){
                .kla-my-students-header{align-items:flex-start;flex-direction:column}
                .kla-ms-table-wrap{display:none}
                .kla-ms-mobile-list{display:grid;gap:10px}
                .kla-ms-mobile-card{background:#fff;border:1px solid #ece7d9;border-radius:14px;padding:15px;box-shadow:0 5px 16px rgba(48,32,90,.04)}
                .kla-ms-mobile-top{display:flex;justify-content:space-between;gap:10px;align-items:center}
                .kla-ms-mobile-top strong{color:#30205a}
                .kla-ms-mobile-meta{display:flex;flex-direction:column;gap:4px;margin:8px 0 12px;color:#777080;font-size:12px}
                .kla-ms-mobile-card .kla-ms-view{width:100%}
                .kla-ms-details{padding:16px}
                .kla-ms-detail-head{flex-direction:column}
                .kla-ms-close{align-self:flex-start}
                .kla-ms-performance-grid{grid-template-columns:repeat(2,1fr)}
            }
        `;

        document.head.appendChild(style);
        render();
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'kla_teacher_my_students_panel', 35 );
