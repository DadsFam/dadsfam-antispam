/* DadsFam Anti-Spam — Admin JS */
(function ($) {
    'use strict';

    const { nonce, ajaxurl, strings } = window.dfsasAdmin || {};

    // ── Utility ──────────────────────────────────────────────────────────────

    function showMsg(selector, text, type) {
        const $el = $(selector);
        $el.html(text)
           .css('color', type === 'success' ? '#1a8a4a' : type === 'error' ? '#c0392b' : '#1a5e9a')
           .show();
        setTimeout(() => $el.fadeOut(), 4000);
    }

    function ajax(action, data) {
        return $.post(ajaxurl, Object.assign({ action, nonce }, data));
    }

    // ── Delete single log entry ────────────────────────────────────────────

    $(document).on('click', '.dfsas-delete-log', function () {
        if (!confirm(strings.confirm_delete)) return;
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const id   = $btn.data('id');

        $row.addClass('dfsas-row-removing');
        ajax('dfsas_delete_log', { id })
            .done(() => setTimeout(() => $row.remove(), 300))
            .fail(() => { $row.removeClass('dfsas-row-removing'); alert('Failed to delete.'); });
    });

    // ── Clear all logs ────────────────────────────────────────────────────

    $(document).on('click', '#dfsas-clear-logs', function () {
        if (!confirm(strings.confirm_clear)) return;
        const $btn = $(this).prop('disabled', true).text('Clearing…');

        ajax('dfsas_clear_logs')
            .done(res => {
                showMsg('#dfsas-msg, #dfsas-action-msg', res.data?.message || 'All logs cleared.', 'success');
                $('table.dfsas-table tbody').html(
                    '<tr><td colspan="8" style="text-align:center;padding:32px;color:#6c757d;">Log cleared.</td></tr>'
                );
            })
            .fail(() => showMsg('#dfsas-msg, #dfsas-action-msg', 'Failed to clear logs.', 'error'))
            .always(() => $btn.prop('disabled', false).text('Clear All Logs'));
    });

    // ── Export CSV (PRO) ──────────────────────────────────────────────────

    $(document).on('click', '#dfsas-export-csv', function () {
        const $btn = $(this).prop('disabled', true).text('Exporting…');

        // Trigger via form post to get file download
        const $form = $('<form method="post" action="' + ajaxurl + '" style="display:none">')
            .append('<input name="action" value="dfsas_export_csv">')
            .append('<input name="nonce" value="' + nonce + '">');
        $('body').append($form);
        $form[0].submit();
        $form.remove();

        setTimeout(() => $btn.prop('disabled', false).text('Export CSV'), 1500);
    });

    // ── Unblock IP ────────────────────────────────────────────────────────

    $(document).on('click', '.dfsas-unblock-ip', function () {
        if (!confirm(strings.confirm_unblock)) return;
        const $btn = $(this);
        const ip   = $btn.data('ip');

        $btn.prop('disabled', true).text('…');
        ajax('dfsas_unblock_ip', { ip })
            .done(res => {
                $btn.text('✅ Whitelisted').prop('disabled', false);
                setTimeout(() => {
                    $btn.closest('tr').find('.dfsas-ip-status').text('Whitelisted');
                    $btn.text('✅');
                }, 2000);
            })
            .fail(() => { $btn.prop('disabled', false).text('🔓'); alert('Failed.'); });
    });

    // ── License: Activate ─────────────────────────────────────────────────

    $(document).on('click', '#dfsas-license-activate', function () {
        const key  = $('#dfsas-license-key').val().trim();
        const $btn = $(this).prop('disabled', true).text('Verifying…');
        const $msg = $('#dfsas-license-msg');

        if (!key) {
            $msg.text('Please enter a license key.').css('color','#c0392b');
            $btn.prop('disabled', false).text('Activate License');
            return;
        }

        $msg.text('Checking with dadsfam.co.za…').css('color','#6c757d');

        ajax('dfsas_license_activate', { key })
            .done(res => {
                if (res.success) {
                    $msg.text(res.data.message).css('color','#1a8a4a');
                    if (res.data.reload) {
                        setTimeout(() => {
                            window.location.href = ajaxurl.replace('admin-ajax.php', 'admin.php?page=dadsfam-antispam');
                        }, 1200);
                    }
                } else {
                    $msg.text('❌ ' + res.data).css('color','#c0392b');
                    $btn.prop('disabled', false).text('Activate License');
                }
            })
            .fail(() => {
                $msg.text('❌ Connection error.').css('color','#c0392b');
                $btn.prop('disabled', false).text('Activate License');
            });
    });

    // ── License: Deactivate ───────────────────────────────────────────────

    $(document).on('click', '#dfsas-license-deactivate', function () {
        if (!confirm('Remove license key from this site? PRO features will be deactivated.')) return;
        const $btn = $(this).prop('disabled', true).text('Removing…');
        const $msg = $('#dfsas-license-msg');

        ajax('dfsas_license_deactivate')
            .done(res => {
                if (res.success) {
                    $msg.text(res.data.message).css('color','#1a8a4a');
                    if (res.data.reload) setTimeout(() => location.reload(), 1200);
                } else {
                    $msg.text('❌ ' + res.data).css('color','#c0392b');
                    $btn.prop('disabled', false).text('Remove License Key');
                }
            })
            .fail(() => {
                $msg.text('❌ Connection error.').css('color','#c0392b');
                $btn.prop('disabled', false).text('Remove License Key');
            });
    });

    // ── Upload Domain List File ───────────────────────────────────────────

    $(document).on('change', '#dfsas-file-input', function () {
        const file = this.files[0];
        if (file) {
            $('#dfsas-file-name').text(file.name);
            $('#dfsas-upload-list').prop('disabled', false);
        } else {
            $('#dfsas-file-name').text('No file chosen');
            $('#dfsas-upload-list').prop('disabled', true);
        }
    });

    $(document).on('click', '#dfsas-upload-list', function () {
        const file = document.getElementById('dfsas-file-input').files[0];
        if (!file) return;

        const $btn = $(this).prop('disabled', true).text('Uploading…');
        const $msg = $('#dfsas-upload-msg');
        $msg.text('Reading file…').css('color', '#6c757d');

        const formData = new FormData();
        formData.append('action', 'dfsas_upload_domain_list');
        formData.append('nonce', dfsasAdmin.nonce);
        formData.append('domain_list', file);

        $.ajax({
            url: dfsasAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        })
        .done(res => {
            if (res.success) {
                $msg.text(res.data.message).css('color', '#1a8a4a');
                $('#dfsas-domain-count').text(res.data.count.toLocaleString());
                $('#dfsas-last-updated').text(res.data.last_updated);
                $('#dfsas-file-name').text('No file chosen');
                document.getElementById('dfsas-file-input').value = '';
                $btn.prop('disabled', true).text('⬆️ Upload & Import');
            } else {
                $msg.text('❌ ' + res.data).css('color', '#c0392b');
                $btn.prop('disabled', false).text('⬆️ Upload & Import');
            }
        })
        .fail(() => {
            $msg.text('❌ Upload failed. Please try again.').css('color', '#c0392b');
            $btn.prop('disabled', false).text('⬆️ Upload & Import');
        });
    });

    // ── Update Domain List (PRO) ──────────────────────────────────────────

    $(document).on('click', '#dfsas-update-list-now', function () {
        const $btn = $(this).prop('disabled', true).text('Fetching…');
        const $msg = $('#dfsas-update-msg');

        $msg.text('Connecting to remote list…').css('color', '#6c757d');

        ajax('dfsas_update_domain_list')
            .done(res => {
                if (res.success) {
                    $msg.text(res.data.message).css('color', '#1a8a4a');
                    $('#dfsas-domain-count').text(res.data.count.toLocaleString());
                    $('#dfsas-last-updated').text(res.data.last_updated);
                } else {
                    $msg.text('❌ ' + res.data).css('color', '#c0392b');
                }
            })
            .fail(() => $msg.text('❌ Connection error.').css('color', '#c0392b'))
            .always(() => $btn.prop('disabled', false).text('🔄 Update Now'));
    });

    // ── Test Email ────────────────────────────────────────────────────────

    $(document).on('click', '#dfsas-test-email', function () {
        const $btn = $(this).prop('disabled', true).text('Sending…');

        ajax('dfsas_test_email')
            .done(res => {
                const sent = res.data?.sent;
                showMsg('#dfsas-msg, #dfsas-action-msg',
                    sent ? '✅ Test email sent to admin address.' : '⚠️ wp_mail returned false. Check your mail settings.',
                    sent ? 'success' : 'error'
                );
            })
            .fail(() => showMsg('#dfsas-msg, #dfsas-action-msg', '❌ AJAX error.', 'error'))
            .always(() => $btn.prop('disabled', false).text('Send Test Email'));
    });

    // ── License Activation ────────────────────────────────────────────────

    $(document).on('click', '#dfsas-activate-license', function () {
        const key  = $('#dfsas-license-key').val().trim();
        const $btn = $(this).prop('disabled', true).text('Activating…');
        const $msg = $('#dfsas-license-msg');

        if (!key) {
            $msg.text('Please enter a license key.').css('color', '#c0392b');
            $btn.prop('disabled', false).text('Activate License');
            return;
        }

        $msg.text('Checking with DF Licensing…').css('color', '#6c757d');

        // The actual activation is handled by the DF Licensing plugin.
        // Here we just save the key option and show a status message.
        ajax('dfsas_activate_license', { key })
            .done(res => {
                if (res.success) {
                    $msg.text('✅ ' + (res.data?.message || 'License activated! Reload to unlock PRO features.')).css('color', '#1a8a4a');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    $msg.text('❌ ' + (res.data || 'License validation failed.')).css('color', '#c0392b');
                }
            })
            .fail(() => $msg.text('❌ Connection error.').css('color', '#c0392b'))
            .always(() => $btn.prop('disabled', false).text('Activate License'));
    });

    // ── Settings: highlight locked PRO fields on click ────────────────────

    $(document).on('click', '.dfsas-card--locked', function (e) {
        if ($(e.target).closest('.dfsas-lock-overlay').length) {
            // Let the overlay link handle it
        }
    });

    // ── Auto-update blocklist entry counts ────────────────────────────────

    $('textarea[name^="dfsas_options[blocked_"]').on('input', function () {
        const lines = this.value.split('\n').filter(l => l.trim().length > 0).length;
        const $card  = $(this).closest('.dfsas-card');
        const $badge = $card.find('.dfsas-count-badge').first();
        const text   = $badge.text().replace(/^\d+/, lines);
        $badge.text(text);
    });

})(jQuery);
