/* PNG Optimizer – Admin JS */
(function ($) {
    'use strict';

    // ── Single-image optimize button ──────────────────────────────
    $(document).on('click', '.png-opt-single-btn', function () {
        var $btn = $(this);
        var id   = $btn.data('id');
        var $result = $('#png-opt-single-result-' + id);

        $btn.prop('disabled', true).text(pngOpt.i18n.optimizing);

        $.post(pngOpt.ajax_url, {
            action:        'png_opt_optimize_single',
            nonce:         pngOpt.nonce,
            attachment_id: id
        })
        .done(function (res) {
            if (res.success) {
                $btn.replaceWith(
                    '<strong style="color:#2eb136">-' + res.data.saved_percent + '% (' + res.data.human + ' saved)</strong>'
                );
            } else {
                $btn.prop('disabled', false).text('Optimize');
                alert(res.data.message || pngOpt.i18n.error);
            }
        })
        .fail(function () {
            $btn.prop('disabled', false).text('Optimize');
            $result.text(pngOpt.i18n.error);
        });
    });

    // ── Convert to WebP button (attachment edit screen) ──────────
    $(document).on('click', '.png-opt-webp-btn', function () {
        var $btn    = $(this);
        var id      = $btn.data('id');
        var $result = $('#png-opt-webp-result-' + id);

        $btn.prop('disabled', true).text(pngOpt.i18n.converting_webp);
        $result.text('');

        $.post(pngOpt.ajax_url, {
            action:        'png_opt_webp_single',
            nonce:         pngOpt.nonce,
            attachment_id: id
        })
        .done(function (res) {
            if (res.success) {
                $btn.replaceWith('<span style="color:#2eb136;font-weight:600">' + pngOpt.i18n.webp_done + '</span>');
            } else {
                $btn.prop('disabled', false).text(pngOpt.i18n.convert_webp_btn);
                $result.css('color', '#cc1818').text(res.data.message || pngOpt.i18n.error);
            }
        })
        .fail(function () {
            $btn.prop('disabled', false).text(pngOpt.i18n.convert_webp_btn);
            $result.css('color', '#cc1818').text(pngOpt.i18n.error);
        });
    });

    // ── WebP quality row toggle ───────────────────────────────────
    $('#png-opt-webp-toggle').on('change', function () {
        if ($(this).is(':checked')) {
            $('#png-opt-webp-quality-row').show();
        } else {
            $('#png-opt-webp-quality-row').hide();
        }
    });

    // ── Bulk optimizer ────────────────────────────────────────────
    $('#png-opt-bulk-btn').on('click', function () {
        var $btn      = $(this);
        var $progress = $('#png-opt-bulk-progress');
        var $fill     = $('#png-opt-progress-fill');
        var $status   = $('#png-opt-bulk-status');
        var $result   = $('#png-opt-bulk-result');

        $btn.prop('disabled', true).text('Loading…');
        $progress.show();
        $result.text('').removeClass('success error');

        // Step 1: get IDs
        $.post(pngOpt.ajax_url, {
            action: 'png_opt_bulk_get_ids',
            nonce:  pngOpt.nonce
        })
        .done(function (res) {
            if (!res.success || !res.data.ids.length) {
                $btn.prop('disabled', false).text('Start Bulk Optimization');
                $result.addClass('error').text('No PNG images found.');
                return;
            }

            var ids         = res.data.ids;
            var total       = ids.length;
            var done        = 0;
            var totalSaved  = 0;
            var skipped     = 0;

            $btn.text('Optimizing…');

            function processNext() {
                if (ids.length === 0) {
                    $fill.css('width', '100%');
                    $status.text('Done! ' + total + ' images processed, ' + skipped + ' skipped.');
                    $result
                        .addClass('success')
                        .html('<strong>Total saved: ' + formatBytes(totalSaved) + '</strong> across ' + (done - skipped) + ' files.');
                    $btn.prop('disabled', false).text('Run Again');
                    return;
                }

                var id = ids.shift();

                $.post(pngOpt.ajax_url, {
                    action:        'png_opt_bulk_optimize',
                    nonce:         pngOpt.nonce,
                    attachment_id: id
                })
                .done(function (r) {
                    done++;
                    if (r.success) {
                        totalSaved += r.data.saved_bytes || 0;
                        if (r.data.skipped) { skipped++; }
                    } else {
                        skipped++;
                    }

                    var pct = Math.round((done / total) * 100);
                    $fill.css('width', pct + '%');
                    $status.text(done + ' / ' + total + ' (' + pct + '%)  —  saved: ' + formatBytes(totalSaved));
                    processNext();
                })
                .fail(function () {
                    done++;
                    skipped++;
                    processNext();
                });
            }

            processNext();
        })
        .fail(function () {
            $btn.prop('disabled', false).text('Start Bulk Optimization');
            $result.addClass('error').text(pngOpt.i18n.error);
        });
    });

    function formatBytes(bytes) {
        if (bytes >= 1048576) { return (bytes / 1048576).toFixed(2) + ' MB'; }
        if (bytes >= 1024)    { return (bytes / 1024).toFixed(2) + ' KB'; }
        return bytes + ' B';
    }

}(jQuery));
