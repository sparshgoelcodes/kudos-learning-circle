<?php
/**
 * Kudos Learning Academy
 * Parent Portal - Child Selector Learning Snapshot
 *
 * Replace the previous parent-learning-snapshot.php with this file.
 * Shows one child at a time to keep the Parent Dashboard compact.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kla_parent_snapshot_tabs_parent_id() {
    if ( function_exists( 'kla_get_parent_record_id' ) ) {
        $parent_id = absint( kla_get_parent_record_id() );
        if ( $parent_id ) {
            return $parent_id;
        }
    }

    $user = wp_get_current_user();

    if ( ! $user || ! $user->ID ) {
        return 0;
    }

    $linked = absint( get_user_meta( $user->ID, '_kla_parent_record_id', true ) );

    return ( $linked && 'kudos_parent' === get_post_type( $linked ) ) ? $linked : 0;
}

function kla_parent_snapshot_tabs_children( $parent_id ) {
    if ( ! $parent_id ) {
        return array();
    }

    return get_posts(
        array(
            'post_type'      => 'kudos_student',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_kudos_student_parent',
                    'value'   => $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );
}

function kla_parent_snapshot_tabs_student_name( $student_id ) {
    $name = get_post_meta( $student_id, '_kudos_student_name', true );
    return $name ? $name : get_the_title( $student_id );
}

function kla_parent_snapshot_tabs_performance( $student_id ) {
    $student_name = kla_parent_snapshot_tabs_student_name( $student_id );

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
                    'value'   => $student_name,
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

function kla_parent_snapshot_tabs_attendance( $student_id ) {
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

function kla_parent_snapshot_tabs_kudos( $student_id, $limit = 8 ) {
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
            $type   = strtolower( get_post_meta( $id, '_kla_transaction_type', true ) );
            $points = absint( get_post_meta( $id, '_kla_transaction_points', true ) );
            $negative = in_array( $type, array( 'redemption', 'redeem' ), true );

            $items[] = array(
                'points' => ( $negative ? '-' : '+' ) . $points,
                'reason' => get_post_meta( $id, '_kla_transaction_reason', true ),
                'date'   => get_the_date( 'd M Y', $id ),
                'type'   => $type,
            );
        }

        wp_reset_postdata();
    }

    return $items;
}

function kla_parent_snapshot_tabs_build( $parent_id ) {
    $children = kla_parent_snapshot_tabs_children( $parent_id );
    $result   = array();

    foreach ( $children as $child ) {
        $student_id = $child->ID;

        $result[] = array(
            'id'          => $student_id,
            'name'        => kla_parent_snapshot_tabs_student_name( $student_id ),
            'class'       => get_post_meta( $student_id, '_kudos_student_class', true ),
            'balance'     => absint( get_post_meta( $student_id, '_kudos_student_rewards', true ) ),
            'performance' => kla_parent_snapshot_tabs_performance( $student_id ),
            'attendance'  => kla_parent_snapshot_tabs_attendance( $student_id ),
            'kudos'       => kla_parent_snapshot_tabs_kudos( $student_id, 8 ),
        );
    }

    return $result;
}

function kla_parent_snapshot_tabs_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please log in again.' ), 403 );
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Parent access required.' ), 403 );
    }

    check_ajax_referer( 'kla_parent_snapshot_tabs_nonce', 'nonce' );

    $parent_id = kla_parent_snapshot_tabs_parent_id();

    if ( ! $parent_id ) {
        wp_send_json_error( array( 'message' => 'Your parent account is not linked yet.' ), 400 );
    }

    wp_send_json_success(
        array(
            'children' => kla_parent_snapshot_tabs_build( $parent_id ),
        )
    );
}

add_action( 'wp_ajax_kla_parent_snapshot_tabs', 'kla_parent_snapshot_tabs_ajax' );

function kla_parent_snapshot_tabs_render() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        return;
    }

    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'kla_parent_snapshot_tabs_nonce' );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dashboard =
            document.querySelector('.kla-parent-dashboard') ||
            document.querySelector('.parent-dashboard') ||
            document.querySelector('[data-parent-dashboard]');

        if (!dashboard || document.getElementById('kla-parent-learning-snapshot')) {
            return;
        }

        const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;
        let children = [];
        let selectedId = null;

        const section = document.createElement('section');
        section.id = 'kla-parent-learning-snapshot';

        const anchor =
            dashboard.querySelector('.kla-parent-dashboard-content') ||
            dashboard.querySelector('.kla-parent-main') ||
            dashboard;

        anchor.appendChild(section);

        function esc(value) {
            return String(value === undefined || value === null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function average(p) {
            if (!p) return '—';

            const fields = ['punctuality','discipline','fees','etiquettes','workshops','participation'];
            const values = fields.map(function(f){ return parseFloat(p[f]); })
                .filter(function(v){ return !isNaN(v); });

            if (!values.length) return '—';

            return Math.round(values.reduce(function(a,b){ return a+b; }, 0) / values.length) + '/100';
        }

        function attClass(status) {
            if (status === 'Present') return 'kla-pst-present';
            if (status === 'Absent') return 'kla-pst-absent';
            if (status === 'Late') return 'kla-pst-late';
            return '';
        }

        function renderLoading() {
            section.innerHTML = `
                <div class="kla-pst-loading">
                    <div class="kla-pst-spinner"></div>
                    Loading your children's latest updates...
                </div>
            `;
        }

        function renderEmpty() {
            section.innerHTML = `
                <div class="kla-pst-empty">
                    <strong>No linked students found.</strong>
                    <span>Please ask the academy administrator to link your child to your parent account.</span>
                </div>
            `;
        }

        function render() {
            if (!children.length) {
                renderEmpty();
                return;
            }

            if (!selectedId || !children.some(function(c){ return String(c.id) === String(selectedId); })) {
                selectedId = children[0].id;
            }

            const child = children.find(function(c){ return String(c.id) === String(selectedId); });

            section.innerHTML = `
                <div class="kla-pst-header">
                    <div>
                        <span class="kla-pst-eyebrow">Learning Updates</span>
                        <h2>Your Child's Progress</h2>
                        <p>Select a child to view their latest academy updates.</p>
                    </div>
                    <button type="button" class="kla-pst-refresh" id="kla-pst-refresh">↻ Refresh</button>
                </div>

                <div class="kla-pst-child-switcher">
                    <label>
                        <span>Child</span>
                        <select id="kla-pst-child-select">
                            ${children.map(function(c){
                                return `<option value="${esc(c.id)}" ${String(c.id) === String(selectedId) ? 'selected' : ''}>${esc(c.name)}</option>`;
                            }).join('')}
                        </select>
                    </label>

                    ${children.length > 1
                        ? `<small>${children.length} children linked to this parent account</small>`
                        : `<small>1 child linked to this parent account</small>`
                    }
                </div>

                <article class="kla-pst-card">
                    <div class="kla-pst-child-top">
                        <div>
                            <span class="kla-pst-label">Student</span>
                            <h3>${esc(child.name)}</h3>
                            <p>${esc(child.class || 'Class / Batch not set')}</p>
                        </div>
                        <div class="kla-pst-wallet">
                            <span>Kudos Balance</span>
                            <strong>🪙 ${esc(child.balance)}</strong>
                        </div>
                    </div>

                    <div class="kla-pst-summary">
                        <div>
                            <span>Performance</span>
                            <strong>${average(child.performance)}</strong>
                        </div>
                        <div>
                            <span>Attendance</span>
                            <strong class="${attClass(child.attendance ? child.attendance.status : '')}">
                                ${esc(child.attendance ? child.attendance.status : 'No record')}
                            </strong>
                            <small>${esc(child.attendance && child.attendance.date ? child.attendance.date : '')}</small>
                        </div>
                        <div>
                            <span>Recent Kudos</span>
                            <strong>${child.kudos && child.kudos.length ? esc(child.kudos[0].points) : '—'}</strong>
                            <small>${child.kudos && child.kudos.length ? esc(child.kudos[0].reason || 'Kudos activity') : 'No activity'}</small>
                        </div>
                    </div>

                    <div class="kla-pst-section">
                        <div class="kla-pst-title">
                            <h4>Performance</h4>
                            <span>Latest record</span>
                        </div>

                        ${child.performance
                            ? `
                                <div class="kla-pst-scores">
                                    <div><span>Punctuality</span><strong>${esc(child.performance.punctuality)}/100</strong></div>
                                    <div><span>Discipline</span><strong>${esc(child.performance.discipline)}/100</strong></div>
                                    <div><span>Fees</span><strong>${esc(child.performance.fees)}/100</strong></div>
                                    <div><span>Etiquettes</span><strong>${esc(child.performance.etiquettes)}/100</strong></div>
                                    <div><span>Workshops</span><strong>${esc(child.performance.workshops)}/100</strong></div>
                                    <div><span>Participation</span><strong>${esc(child.performance.participation)}/100</strong></div>
                                </div>
                                <div class="kla-pst-remark">
                                    <span>Teacher Remark</span>
                                    <p>${esc(child.performance.remarks || 'No teacher remark added yet.')}</p>
                                    ${child.performance.updated_by || child.performance.updated_at
                                        ? `<small>Updated by ${esc(child.performance.updated_by || 'Teacher')}${child.performance.updated_at ? ' · ' + esc(child.performance.updated_at) : ''}</small>`
                                        : ''}
                                </div>
                            `
                            : '<div class="kla-pst-no-record">No performance record available yet.</div>'
                        }
                    </div>

                    <div class="kla-pst-section">
                        <div class="kla-pst-title">
                            <h4>Latest Attendance</h4>
                            <span>${esc(child.attendance && child.attendance.date ? child.attendance.date : 'No record')}</span>
                        </div>

                        <div class="kla-pst-attendance">
                            <span class="${attClass(child.attendance ? child.attendance.status : '')}">
                                ${esc(child.attendance ? child.attendance.status : 'No attendance record')}
                            </span>
                            <p>${esc(child.attendance && child.attendance.remarks ? child.attendance.remarks : 'No attendance remarks.')}</p>
                        </div>
                    </div>

                    <div class="kla-pst-section">
                        <div class="kla-pst-title">
                            <h4>Kudos History</h4>
                            <span>Latest 8 transactions</span>
                        </div>

                        ${child.kudos && child.kudos.length
                            ? `<div class="kla-pst-kudos-list">
                                ${child.kudos.map(function(item){
                                    const positive = String(item.points).charAt(0) !== '-';
                                    return `
                                        <div class="kla-pst-kudos-row">
                                            <strong class="${positive ? 'kla-pst-positive' : 'kla-pst-negative'}">${esc(item.points)}</strong>
                                            <div>
                                                <span>${esc(item.reason || 'Kudos transaction')}</span>
                                                <small>${esc(item.date)}</small>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                              </div>`
                            : '<div class="kla-pst-no-record">No Kudos activity yet.</div>'
                        }
                    </div>
                </article>
            `;

            document.getElementById('kla-pst-child-select').addEventListener('change', function(){
                selectedId = this.value;
                render();
            });

            document.getElementById('kla-pst-refresh').addEventListener('click', load);
        }

        function load() {
            renderLoading();

            const data = new FormData();
            data.append('action', 'kla_parent_snapshot_tabs');
            data.append('nonce', nonce);

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(function(response){ return response.json(); })
            .then(function(result){
                if (!result.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : 'Unable to load updates.');
                }

                children = result.data.children || [];
                render();
            })
            .catch(function(error){
                section.innerHTML = `<div class="kla-pst-error">${esc(error.message)}</div>`;
            });
        }

        const style = document.createElement('style');

        style.textContent = `
            #kla-parent-learning-snapshot{width:100%;margin:26px 0 0}
            .kla-pst-header{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:16px}
            .kla-pst-eyebrow{display:inline-block;color:#2d7b3c;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:5px}
            .kla-pst-header h2{margin:0;color:#30205a;font-size:28px;line-height:1.15}
            .kla-pst-header p{margin:7px 0 0;color:#777080;font-size:13px}
            .kla-pst-refresh{border:0;border-radius:10px;background:#30205a;color:#fff;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer}
            .kla-pst-child-switcher{display:flex;align-items:end;justify-content:space-between;gap:15px;background:#fff;border:1px solid #ece7d9;border-radius:14px;padding:15px;margin-bottom:16px;box-shadow:0 5px 18px rgba(48,32,90,.04)}
            .kla-pst-child-switcher label{display:block;width:min(390px,100%)}
            .kla-pst-child-switcher label>span{display:block;color:#777080;font-size:10px;font-weight:800;text-transform:uppercase;margin-bottom:6px}
            .kla-pst-child-switcher select{width:100%;box-sizing:border-box;border:1px solid #ddd7ca;border-radius:9px;padding:11px 12px;background:#fff;color:#30205a;font-weight:700;outline:none}
            .kla-pst-child-switcher select:focus{border-color:#2d7b3c;box-shadow:0 0 0 3px rgba(45,123,60,.08)}
            .kla-pst-child-switcher small{color:#8a8491;font-size:10px}
            .kla-pst-card{background:#fff;border:1px solid #ece7d9;border-radius:18px;padding:20px;box-shadow:0 8px 24px rgba(48,32,90,.06)}
            .kla-pst-child-top{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding-bottom:16px;border-bottom:1px solid #eee9df}
            .kla-pst-label{display:block;color:#8a8491;font-size:9px;font-weight:800;text-transform:uppercase}
            .kla-pst-child-top h3{margin:3px 0 0;color:#30205a;font-size:22px}
            .kla-pst-child-top p{margin:5px 0 0;color:#777080;font-size:12px}
            .kla-pst-wallet{min-width:135px;background:#eef6dd;border-radius:12px;padding:10px 13px;text-align:right}
            .kla-pst-wallet span{display:block;color:#617052;font-size:9px;font-weight:800;text-transform:uppercase}
            .kla-pst-wallet strong{display:block;color:#2d7b3c;font-size:19px;margin-top:3px}
            .kla-pst-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0}
            .kla-pst-summary>div{padding:13px;border:1px solid #eee9df;border-radius:12px;background:#fffaf0}
            .kla-pst-summary span{display:block;color:#777080;font-size:10px;font-weight:800;text-transform:uppercase}
            .kla-pst-summary strong{display:block;color:#30205a;font-size:18px;margin-top:5px}
            .kla-pst-summary small{display:block;color:#8a8491;font-size:10px;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
            .kla-pst-section{padding-top:16px;margin-top:16px;border-top:1px solid #eee9df}
            .kla-pst-title{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
            .kla-pst-title h4{margin:0;color:#30205a;font-size:16px}
            .kla-pst-title span{color:#8a8491;font-size:10px}
            .kla-pst-scores{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
            .kla-pst-scores>div{padding:10px;border:1px solid #eee9df;border-radius:10px;text-align:center}
            .kla-pst-scores span{display:block;color:#777080;font-size:9px}
            .kla-pst-scores strong{display:block;color:#30205a;font-size:14px;margin-top:5px}
            .kla-pst-remark{margin-top:11px;padding:12px;background:#f7f4eb;border-radius:10px}
            .kla-pst-remark span{display:block;color:#777080;font-size:10px;font-weight:800;text-transform:uppercase}
            .kla-pst-remark p{margin:5px 0;color:#50495c;font-size:12px}
            .kla-pst-remark small{color:#8a8491;font-size:9px}
            .kla-pst-attendance{display:flex;align-items:center;gap:13px;padding:13px;border-radius:11px;background:#f7f4eb}
            .kla-pst-attendance>span{display:inline-flex;padding:6px 10px;border-radius:999px;background:#fff;font-size:10px;font-weight:800;white-space:nowrap}
            .kla-pst-attendance p{margin:0;color:#5d5867;font-size:12px}
            .kla-pst-present{color:#26743a!important}
            .kla-pst-absent{color:#b42318!important}
            .kla-pst-late{color:#986500!important}
            .kla-pst-kudos-list{border-top:1px solid #eee9df}
            .kla-pst-kudos-row{display:flex;align-items:center;gap:13px;padding:10px 2px;border-bottom:1px solid #eee9df}
            .kla-pst-kudos-row>strong{min-width:38px;font-size:16px}
            .kla-pst-kudos-row span{display:block;color:#50495c;font-size:12px}
            .kla-pst-kudos-row small{display:block;color:#8a8491;font-size:9px;margin-top:2px}
            .kla-pst-positive{color:#2d7b3c}
            .kla-pst-negative{color:#b42318}
            .kla-pst-no-record{padding:16px;border:1px dashed #ddd7ca;border-radius:10px;color:#8a8491;font-size:12px}
            .kla-pst-loading,.kla-pst-empty,.kla-pst-error{padding:24px;border:1px solid #ece7d9;border-radius:15px;background:#fff;text-align:center;color:#777080;font-size:12px}
            .kla-pst-empty strong{display:block;color:#30205a;margin-bottom:5px}
            .kla-pst-error{color:#b42318}
            .kla-pst-spinner{width:22px;height:22px;margin:0 auto 9px;border:3px solid #e4dfd2;border-top-color:#2d7b3c;border-radius:50%;animation:klaPstSpin .8s linear infinite}
            @keyframes klaPstSpin{to{transform:rotate(360deg)}}
            @media(max-width:900px){.kla-pst-scores{grid-template-columns:repeat(3,1fr)}}
            @media(max-width:650px){
                .kla-pst-header{align-items:flex-start;flex-direction:column}
                .kla-pst-child-switcher{align-items:flex-start;flex-direction:column}
                .kla-pst-child-switcher label{width:100%}
                .kla-pst-child-top{flex-direction:column}
                .kla-pst-wallet{width:100%;box-sizing:border-box;text-align:left}
                .kla-pst-summary{grid-template-columns:1fr}
                .kla-pst-scores{grid-template-columns:repeat(2,1fr)}
                .kla-pst-attendance{align-items:flex-start;flex-direction:column}
            }
        `;

        document.head.appendChild(style);
        load();
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'kla_parent_snapshot_tabs_render', 40 );
