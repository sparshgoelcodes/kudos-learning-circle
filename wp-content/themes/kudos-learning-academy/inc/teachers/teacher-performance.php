<?php
/**
 * Kudos Learning Academy
 * Teacher Performance Portal
 *
 * Adds the teacher-facing Performance panel without changing
 * teachers.php, teacher-tools.php, or the theme stylesheet.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   AJAX: LOAD STUDENT PERFORMANCE
   ========================================================= */

add_action( 'wp_ajax_kla_teacher_get_performance', 'kla_teacher_get_performance' );

function kla_teacher_get_performance() {

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Login required.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Teacher access required.' ), 403 );
    }

    check_ajax_referer( 'kla_teacher_performance_nonce', 'nonce' );

    $student_id = isset( $_POST['student_id'] )
        ? absint( $_POST['student_id'] )
        : 0;

    if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
        wp_send_json_error( array( 'message' => 'Invalid student.' ), 400 );
    }

    $student_name = get_post_meta(
        $student_id,
        '_kudos_student_name',
        true
    );

    if ( ! $student_name ) {
        $student_name = get_the_title( $student_id );
    }

    /*
     * Prefer a performance record explicitly linked to this Student ID.
     * Fall back to the imported student-name record so the existing
     * academy data continues to work.
     */
    $records = get_posts(
        array(
            'post_type'      => 'kudos_performance',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kudos_performance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );

    if ( empty( $records ) ) {
        $records = get_posts(
            array(
                'post_type'      => 'kudos_performance',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_performance_student_name',
                        'value'   => $student_name,
                        'compare' => '=',
                    ),
                ),
            )
        );
    }

    $record = ! empty( $records ) ? $records[0] : null;

    $fields = array(
        'punctuality'   => 0,
        'discipline'    => 0,
        'fees'          => 0,
        'etiquettes'    => 0,
        'workshops'     => 0,
        'participation' => 0,
    );

    $remarks = '';

    if ( $record ) {
        $fields['punctuality'] = get_post_meta(
            $record->ID,
            '_kudos_performance_punctuality',
            true
        );

        $fields['discipline'] = get_post_meta(
            $record->ID,
            '_kudos_performance_discipline',
            true
        );

        $fields['fees'] = get_post_meta(
            $record->ID,
            '_kudos_performance_fees',
            true
        );

        $fields['etiquettes'] = get_post_meta(
            $record->ID,
            '_kudos_performance_etiquettes',
            true
        );

        $fields['workshops'] = get_post_meta(
            $record->ID,
            '_kudos_performance_workshops',
            true
        );

        $fields['participation'] = get_post_meta(
            $record->ID,
            '_kudos_performance_participation',
            true
        );

        $remarks = get_post_meta(
            $record->ID,
            '_kudos_performance_teacher_remarks',
            true
        );
    }

    wp_send_json_success(
        array(
            'student_id'   => $student_id,
            'student_name' => $student_name,
            'record_id'    => $record ? $record->ID : 0,
            'fields'       => $fields,
            'remarks'      => $remarks,
            'updated_by'   => $record
                ? get_post_meta( $record->ID, '_kudos_performance_updated_by_name', true )
                : '',
            'updated_at'   => $record
                ? get_post_meta( $record->ID, '_kudos_performance_updated_at', true )
                : '',
        )
    );
}


/* =========================================================
   AJAX: SAVE STUDENT PERFORMANCE
   ========================================================= */

add_action( 'wp_ajax_kla_teacher_save_performance', 'kla_teacher_save_performance' );

function kla_teacher_save_performance() {

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Login required.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_teacher', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Teacher access required.' ), 403 );
    }

    check_ajax_referer( 'kla_teacher_performance_nonce', 'nonce' );

    $student_id = isset( $_POST['student_id'] )
        ? absint( $_POST['student_id'] )
        : 0;

    if ( ! $student_id || get_post_type( $student_id ) !== 'kudos_student' ) {
        wp_send_json_error( array( 'message' => 'Invalid student.' ), 400 );
    }

    $student_name = get_post_meta(
        $student_id,
        '_kudos_student_name',
        true
    );

    if ( ! $student_name ) {
        $student_name = get_the_title( $student_id );
    }

    $field_keys = array(
        'punctuality',
        'discipline',
        'fees',
        'etiquettes',
        'workshops',
        'participation',
    );

    $values = array();

    foreach ( $field_keys as $key ) {

        $raw = isset( $_POST[ $key ] )
            ? wp_unslash( $_POST[ $key ] )
            : 0;

        /*
         * Academy scores are maintained on a 0-100 scale.
         * Blank is treated as 0 for the stored value.
         */
        if ( $raw === '' ) {
            $raw = 0;
        }

        $value = intval( $raw );

        if ( $value < 0 ) {
            $value = 0;
        }

        if ( $value > 100 ) {
            $value = 100;
        }

        $values[ $key ] = $value;
    }

    $remarks = isset( $_POST['remarks'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) )
        : '';

    /*
     * Find the latest existing record for this student.
     * We update it rather than creating duplicates.
     */
    $records = get_posts(
        array(
            'post_type'      => 'kudos_performance',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_kudos_performance_student',
                    'value'   => $student_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );

    if ( empty( $records ) ) {
        $records = get_posts(
            array(
                'post_type'      => 'kudos_performance',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'     => '_kudos_performance_student_name',
                        'value'   => $student_name,
                        'compare' => '=',
                    ),
                ),
            )
        );
    }

    $record_id = ! empty( $records ) ? $records[0]->ID : 0;

    if ( ! $record_id ) {

        $record_id = wp_insert_post(
            array(
                'post_title'   => $student_name . ' - Performance',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'kudos_performance',
            ),
            true
        );

        if ( is_wp_error( $record_id ) ) {
            wp_send_json_error(
                array(
                    'message' => 'Could not create the performance record.',
                ),
                500
            );
        }
    }

    /*
     * Link the record permanently to the Student CPT.
     */
    update_post_meta(
        $record_id,
        '_kudos_performance_student',
        $student_id
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_student_name',
        $student_name
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_punctuality',
        $values['punctuality']
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_discipline',
        $values['discipline']
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_fees',
        $values['fees']
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_etiquettes',
        $values['etiquettes']
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_workshops',
        $values['workshops']
    );

    /*
     * We intentionally keep the existing _kudos_performance_school
     * meta untouched. It is not part of the academy teacher form.
     */
    update_post_meta(
        $record_id,
        '_kudos_performance_participation',
        $values['participation']
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_teacher_remarks',
        $remarks
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_updated_by',
        $user->ID
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_updated_by_name',
        $user->display_name
    );

    update_post_meta(
        $record_id,
        '_kudos_performance_updated_at',
        current_time( 'mysql' )
    );

    /*
     * Touch the post so the existing Recent Performance query
     * (orderby modified DESC) shows this record at the top.
     */
    wp_update_post(
        array(
            'ID'          => $record_id,
            'post_title'  => $student_name . ' - Performance',
            'post_status' => 'publish',
        )
    );

    wp_send_json_success(
        array(
            'message'     => $student_name . '\'s performance has been updated successfully.',
            'record_id'   => $record_id,
            'student_id'  => $student_id,
            'student_name'=> $student_name,
            'updated_by'  => $user->display_name,
            'updated_at'  => current_time( 'mysql' ),
        )
    );
}


/* =========================================================
   TEACHER PERFORMANCE PANEL
   ========================================================= */

add_action( 'wp_footer', 'kla_teacher_performance_panel', 9998 );

function kla_teacher_performance_panel() {

    if ( ! is_page( 'teacher-dashboard' ) || ! is_user_logged_in() ) {
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

        $name = get_post_meta(
            $student->ID,
            '_kudos_student_name',
            true
        );

        if ( ! $name ) {
            $name = $student->post_title;
        }

        $student_data[] = array(
            'id'   => $student->ID,
            'name' => $name,
        );
    }

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'kla_teacher_performance_nonce' );
    ?>
    <style id="kla-teacher-performance-panel-css">

        .kla-performance-panel-wrap {
            background: #ffffff;
            border: 1px solid #eee8dc;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 25px rgba(48,32,90,.05);
        }

        .kla-performance-panel-head {
            margin-bottom: 25px;
        }

        .kla-performance-panel-head span {
            display: block;
            color: #2d7b3c;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .kla-performance-panel-head h2 {
            margin: 6px 0 7px;
            color: #30205a;
            font-size: 30px;
        }

        .kla-performance-panel-head p {
            margin: 0;
            color: #6d6876;
        }

        .kla-performance-student-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: end;
            margin-bottom: 25px;
        }

        .kla-performance-field label {
            display: block;
            margin-bottom: 7px;
            color: #30205a;
            font-size: 13px;
            font-weight: 800;
        }

        .kla-performance-field select,
        .kla-performance-field input,
        .kla-performance-field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ddd7cc;
            border-radius: 11px;
            background: #fff;
            padding: 12px 13px;
            color: #30205a;
            font-size: 14px;
        }

        .kla-performance-field select:focus,
        .kla-performance-field input:focus,
        .kla-performance-field textarea:focus {
            outline: none;
            border-color: #2d7b3c;
            box-shadow: 0 0 0 3px rgba(45,123,60,.10);
        }

        .kla-performance-status {
            min-height: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .kla-performance-status.loading {
            color: #6d6876;
        }

        .kla-performance-status.success {
            color: #2d7b3c;
        }

        .kla-performance-status.error {
            color: #b42318;
        }

        .kla-performance-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .kla-performance-score-card {
            border: 1px solid #eee8dc;
            border-radius: 15px;
            padding: 18px;
            background: #fffdf9;
        }

        .kla-performance-score-card label {
            display: block;
            margin-bottom: 9px;
            color: #30205a;
            font-weight: 800;
            font-size: 14px;
        }

        .kla-performance-score-input {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .kla-performance-score-input input {
            max-width: 150px;
        }

        .kla-performance-score-input span {
            color: #77717f;
            font-size: 13px;
            font-weight: 700;
        }

        .kla-performance-remarks {
            margin-top: 18px;
        }

        .kla-performance-save-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
        }

        .kla-performance-save {
            border: 0;
            border-radius: 25px;
            background: #2d7b3c;
            color: #fff;
            padding: 13px 24px;
            font-weight: 800;
            cursor: pointer;
        }

        .kla-performance-save:hover {
            opacity: .92;
        }

        .kla-performance-save:disabled {
            opacity: .55;
            cursor: wait;
        }

        .kla-performance-last-updated {
            color: #77717f;
            font-size: 12px;
        }

        @media (max-width: 650px) {
            .kla-performance-panel-wrap {
                padding: 20px;
            }

            .kla-performance-student-row,
            .kla-performance-grid {
                grid-template-columns: 1fr;
            }

            .kla-performance-save-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .kla-performance-save {
                width: 100%;
            }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const performancePanel = document.querySelector('[data-panel="performance"]');

        if (!performancePanel) {
            return;
        }

        const students = <?php echo wp_json_encode( $student_data ); ?>;
        const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;

        let options = '<option value="">Select Student</option>';

        students.forEach(function (student) {
            options += '<option value="' + String(student.id) + '">' +
                escapeHtml(student.name) +
                '</option>';
        });

        performancePanel.innerHTML =
            '<div class="kla-performance-panel-wrap">' +

                '<div class="kla-performance-panel-head">' +
                    '<span>STUDENT PERFORMANCE</span>' +
                    '<h2>Update Performance</h2>' +
                    '<p>Select a student to load their current academy performance.</p>' +
                '</div>' +

                '<div class="kla-performance-student-row">' +
                    '<div class="kla-performance-field">' +
                        '<label for="kla-performance-student">Student</label>' +
                        '<select id="kla-performance-student">' +
                            options +
                        '</select>' +
                    '</div>' +
                    '<div id="kla-performance-status" class="kla-performance-status"></div>' +
                '</div>' +

                '<form id="kla-performance-form" style="display:none;">' +

                    '<div class="kla-performance-grid">' +

                        scoreField('Punctuality', 'punctuality') +
                        scoreField('Discipline', 'discipline') +
                        scoreField('Fees', 'fees') +
                        scoreField('Etiquettes / Manners', 'etiquettes') +
                        scoreField('Workshops', 'workshops') +
                        scoreField('Participation / Activity', 'participation') +

                    '</div>' +

                    '<div class="kla-performance-field kla-performance-remarks">' +
                        '<label for="kla-performance-remarks">Teacher Remarks</label>' +
                        '<textarea id="kla-performance-remarks" rows="4" placeholder="Add a short observation or feedback for the student..."></textarea>' +
                    '</div>' +

                    '<div class="kla-performance-save-row">' +
                        '<span id="kla-performance-last-updated" class="kla-performance-last-updated"></span>' +
                        '<button type="submit" id="kla-performance-save" class="kla-performance-save">Save Performance</button>' +
                    '</div>' +

                '</form>' +

            '</div>';

        const studentSelect = document.getElementById('kla-performance-student');
        const form = document.getElementById('kla-performance-form');
        const status = document.getElementById('kla-performance-status');
        const saveButton = document.getElementById('kla-performance-save');
        const lastUpdated = document.getElementById('kla-performance-last-updated');

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function scoreField(label, key) {
            return '<div class="kla-performance-score-card">' +
                '<label for="kla-performance-' + key + '">' + label + '</label>' +
                '<div class="kla-performance-score-input">' +
                    '<input type="number" min="0" max="100" step="1" id="kla-performance-' + key + '" name="' + key + '" value="0">' +
                    '<span>/ 100</span>' +
                '</div>' +
            '</div>';
        }

        function setStatus(message, type) {
            status.textContent = message || '';
            status.className = 'kla-performance-status ' + (type || '');
        }

        function setFormValues(data) {

            Object.keys(data.fields || {}).forEach(function (key) {
                const input = document.getElementById('kla-performance-' + key);

                if (input) {
                    input.value = data.fields[key] === '' ? 0 : data.fields[key];
                }
            });

            document.getElementById('kla-performance-remarks').value =
                data.remarks || '';

            if (data.updated_by && data.updated_at) {
                lastUpdated.textContent =
                    'Last updated by ' + data.updated_by +
                    ' on ' + data.updated_at;
            } else {
                lastUpdated.textContent = 'Initial performance record';
            }
        }

        function loadPerformance(studentId) {

            if (!studentId) {
                form.style.display = 'none';
                setStatus('', '');
                lastUpdated.textContent = '';
                return;
            }

            setStatus('Loading performance…', 'loading');
            form.style.display = 'none';

            const body = new URLSearchParams();
            body.append('action', 'kla_teacher_get_performance');
            body.append('nonce', nonce);
            body.append('student_id', studentId);

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data && result.data.message
                            ? result.data.message
                            : 'Unable to load performance.'
                    );
                }

                setFormValues(result.data);
                form.style.display = 'block';
                setStatus('', '');
            })
            .catch(function (error) {
                form.style.display = 'none';
                setStatus(error.message, 'error');
            });
        }

        studentSelect.addEventListener('change', function () {
            loadPerformance(this.value);
        });

        form.addEventListener('submit', function (event) {

            event.preventDefault();

            const studentId = studentSelect.value;

            if (!studentId) {
                setStatus('Please select a student.', 'error');
                return;
            }

            saveButton.disabled = true;
            setStatus('Saving performance…', 'loading');

            const body = new URLSearchParams();

            body.append('action', 'kla_teacher_save_performance');
            body.append('nonce', nonce);
            body.append('student_id', studentId);

            ['punctuality', 'discipline', 'fees', 'etiquettes', 'workshops', 'participation']
                .forEach(function (key) {
                    body.append(
                        key,
                        document.getElementById('kla-performance-' + key).value
                    );
                });

            body.append(
                'remarks',
                document.getElementById('kla-performance-remarks').value
            );

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data && result.data.message
                            ? result.data.message
                            : 'Unable to save performance.'
                    );
                }

                setStatus(result.data.message, 'success');

                lastUpdated.textContent =
                    'Last updated by ' + result.data.updated_by +
                    ' on ' + result.data.updated_at;
            })
            .catch(function (error) {
                setStatus(error.message, 'error');
            })
            .finally(function () {
                saveButton.disabled = false;
            });
        });
    });
    </script>
    <?php
}
