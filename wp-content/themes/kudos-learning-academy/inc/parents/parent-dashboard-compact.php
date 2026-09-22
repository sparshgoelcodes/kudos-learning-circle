<?php
/**
 * Kudos Learning Academy
 * Parent Dashboard Compact History
 *
 * Keeps the parent dashboard compact without deleting any history.
 *
 * - Notifications: shows unread/latest items in a compact list.
 * - Kudos History: shows latest 5 items.
 * - "View all" opens an on-page modal containing the complete history.
 *
 * This module is intentionally separate from parents.php,
 * parent-learning-snapshot.php and notifications.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kla_parent_dashboard_compact_ui() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user = wp_get_current_user();

    if ( ! in_array( 'kudos_parent', (array) $user->roles, true ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        let compactTimer = null;

        function esc(value) {
            return String(value === undefined || value === null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function ensureModal() {
            let modal = document.getElementById('kla-history-modal');

            if (modal) {
                return modal;
            }

            modal = document.createElement('div');
            modal.id = 'kla-history-modal';
            modal.innerHTML = `
                <div class="kla-history-backdrop" data-close-history></div>
                <div class="kla-history-dialog" role="dialog" aria-modal="true" aria-labelledby="kla-history-modal-title">
                    <div class="kla-history-dialog-head">
                        <div>
                            <span class="kla-history-eyebrow">Parent Portal</span>
                            <h3 id="kla-history-modal-title">History</h3>
                        </div>
                        <button type="button" class="kla-history-close" data-close-history aria-label="Close">×</button>
                    </div>
                    <div class="kla-history-dialog-body" id="kla-history-modal-body"></div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.querySelectorAll('[data-close-history]').forEach(function(el) {
                el.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            return modal;
        }

        function openModal(title, html) {
            const modal = ensureModal();

            modal.querySelector('#kla-history-modal-title').textContent = title;
            modal.querySelector('#kla-history-modal-body').innerHTML = html;
            modal.classList.add('kla-history-modal-open');
            document.body.classList.add('kla-history-no-scroll');
        }

        function closeModal() {
            const modal = document.getElementById('kla-history-modal');

            if (modal) {
                modal.classList.remove('kla-history-modal-open');
            }

            document.body.classList.remove('kla-history-no-scroll');
        }

        function compactNotifications() {
            const section = document.getElementById('kla-parent-notifications');

            if (!section) {
                return;
            }

            const list = section.querySelector('.kla-notif-list');

            if (!list) {
                return;
            }

            const items = Array.from(list.querySelectorAll('.kla-notif-item'));

            if (!items.length) {
                return;
            }

            /*
             * Main dashboard:
             * - show only unread notifications
             * - if there are no unread notifications, show the latest 3 read
             * - never allow the dashboard section to grow indefinitely
             */
            const unread = items.filter(function(item) {
                return item.classList.contains('kla-notif-unread');
            });

            items.forEach(function(item) {
                item.style.display = 'none';
            });

            const visible = unread.length ? unread.slice(0, 3) : items.slice(0, 3);

            visible.forEach(function(item) {
                item.style.display = '';
            });

            let viewButton = section.querySelector('.kla-compact-notif-view');

            if (!viewButton) {
                viewButton = document.createElement('button');
                viewButton.type = 'button';
                viewButton.className = 'kla-compact-history-link kla-compact-notif-view';
                section.querySelector('.kla-notif-header')?.appendChild(viewButton);

                viewButton.addEventListener('click', function() {
                    const allItems = Array.from(list.querySelectorAll('.kla-notif-item'));

                    if (!allItems.length) {
                        return;
                    }

                    const html = `
                        <div class="kla-modal-history-list">
                            ${allItems.map(function(item) {
                                return `<div class="kla-modal-notification-item">${item.outerHTML}</div>`;
                            }).join('')}
                        </div>
                    `;

                    openModal('All Notifications', html);
                });
            }

            if (items.length > 3) {
                viewButton.textContent = 'View all notifications →';
                viewButton.style.display = '';
            } else {
                viewButton.style.display = 'none';
            }

            if (unread.length > 3) {
                viewButton.textContent = 'View all notifications →';
                viewButton.style.display = '';
            }
        }

        function compactKudosHistory() {
            const rows = Array.from(document.querySelectorAll('.kla-pst-kudos-row'));

            if (!rows.length) {
                return;
            }

            /*
             * Limit the dashboard to five transactions.
             * The remaining transactions stay in the DOM and are available
             * through the View all button.
             */
            rows.forEach(function(row, index) {
                row.style.display = index < 5 ? '' : 'none';
            });

            const section = rows[0].closest('.kla-pst-section');

            if (!section) {
                return;
            }

            const title = section.querySelector('.kla-pst-title');

            if (!title) {
                return;
            }

            let viewButton = section.querySelector('.kla-compact-kudos-view');

            if (!viewButton) {
                viewButton = document.createElement('button');
                viewButton.type = 'button';
                viewButton.className = 'kla-compact-history-link kla-compact-kudos-view';
                title.appendChild(viewButton);

                viewButton.addEventListener('click', function() {
                    const allRows = Array.from(section.querySelectorAll('.kla-pst-kudos-row'));

                    if (!allRows.length) {
                        return;
                    }

                    const html = `
                        <div class="kla-modal-kudos-list">
                            ${allRows.map(function(row) {
                                return `
                                    <div class="kla-modal-kudos-row">
                                        ${row.outerHTML}
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    `;

                    openModal('Complete Kudos History', html);
                });
            }

            if (rows.length > 5) {
                viewButton.textContent = 'View all →';
                viewButton.style.display = '';
            } else {
                viewButton.style.display = 'none';
            }
        }

        function applyCompactUI() {
            compactNotifications();
            compactKudosHistory();
        }

        const style = document.createElement('style');

        style.textContent = `
            .kla-compact-history-link{
                border:0!important;
                background:transparent!important;
                color:#30205a!important;
                padding:3px 0!important;
                margin-left:auto!important;
                font-size:10px!important;
                font-weight:800!important;
                cursor:pointer!important;
                white-space:nowrap!important;
            }

            .kla-compact-history-link:hover{
                text-decoration:underline!important;
            }

            .kla-history-modal-open{
                display:flex!important;
            }

            #kla-history-modal{
                position:fixed;
                inset:0;
                z-index:999999;
                display:none;
                align-items:center;
                justify-content:center;
                padding:22px;
                box-sizing:border-box;
            }

            .kla-history-backdrop{
                position:absolute;
                inset:0;
                background:rgba(30,22,45,.55);
                backdrop-filter:blur(3px);
            }

            .kla-history-dialog{
                position:relative;
                width:min(760px,100%);
                max-height:min(82vh,760px);
                overflow:hidden;
                background:#fff;
                border-radius:18px;
                box-shadow:0 25px 70px rgba(0,0,0,.25);
                display:flex;
                flex-direction:column;
            }

            .kla-history-dialog-head{
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                padding:17px 20px;
                border-bottom:1px solid #ece7d9;
                background:#fffaf0;
            }

            .kla-history-eyebrow{
                display:block;
                color:#2d7b3c;
                font-size:9px;
                font-weight:800;
                letter-spacing:.12em;
                text-transform:uppercase;
                margin-bottom:3px;
            }

            .kla-history-dialog-head h3{
                margin:0;
                color:#30205a;
                font-size:20px;
            }

            .kla-history-close{
                width:32px;
                height:32px;
                border:0;
                border-radius:50%;
                background:#eeeafa;
                color:#30205a;
                font-size:23px;
                line-height:1;
                cursor:pointer;
            }

            .kla-history-dialog-body{
                padding:15px 20px 20px;
                overflow:auto;
            }

            .kla-history-no-scroll{
                overflow:hidden!important;
            }

            .kla-modal-history-list{
                display:grid;
                gap:7px;
            }

            .kla-modal-notification-item{
                width:100%;
            }

            .kla-modal-notification-item .kla-notif-item{
                display:flex!important;
                opacity:1!important;
            }

            .kla-modal-kudos-list{
                display:grid;
                gap:0;
            }

            .kla-modal-kudos-row{
                border-bottom:1px solid #eee9df;
            }

            .kla-modal-kudos-row .kla-pst-kudos-row{
                display:flex!important;
                padding:11px 2px;
            }

            @media(max-width:600px){
                #kla-history-modal{
                    padding:10px;
                }

                .kla-history-dialog{
                    max-height:90vh;
                    border-radius:14px;
                }

                .kla-history-dialog-head{
                    padding:14px 15px;
                }

                .kla-history-dialog-body{
                    padding:12px 15px 16px;
                }
            }
        `;

        document.head.appendChild(style);

        const observer = new MutationObserver(function() {
            clearTimeout(compactTimer);
            compactTimer = setTimeout(applyCompactUI, 50);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        setTimeout(applyCompactUI, 300);
        setTimeout(applyCompactUI, 1000);
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'kla_parent_dashboard_compact_ui', 60 );
