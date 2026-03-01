<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns a translated string for the given key.
 * Language is read from plugin options (cached for the request).
 *
 * @param  string      $key   Translation key.
 * @param  string|null $lang  Optional language code ('en' or 'tr'). Reads from options if null.
 * @return string
 */
function png_opt_t( $key, $lang = null ) {
    static $cached_lang = null;
    if ( $lang === null ) {
        if ( $cached_lang === null ) {
            $options     = get_option( 'png_optimizer_options', [] );
            $cached_lang = isset( $options['language'] ) ? $options['language'] : 'en';
        }
        $lang = $cached_lang;
    }
    $strings = png_opt_strings();
    if ( isset( $strings[ $lang ][ $key ] ) ) {
        return $strings[ $lang ][ $key ];
    }
    return isset( $strings['en'][ $key ] ) ? $strings['en'][ $key ] : $key;
}

/**
 * All UI strings in supported languages.
 *
 * @return array
 */
function png_opt_strings() {
    return [
        'en' => [
            // General
            'plugin_name'         => 'Image Optimizer',
            'settings_link'       => 'Settings',
            'language'            => 'Language',
            'save_settings'       => 'Save Settings',

            // Stats bar
            'files_optimized'     => 'Files Optimized',
            'total_saved'         => 'Total Saved',
            'avg_reduction'       => 'Avg Reduction',
            'supported_images'    => 'Supported Images',

            // Settings panel
            'settings'            => 'Settings',
            'auto_optimize'       => 'Auto-Optimize on Upload',
            'auto_optimize_desc'  => 'Automatically optimize images when uploaded',
            'compression_level'   => 'Compression Level',
            'compression_desc'    => '0 = no compression (fastest), 9 = max compression (slowest, smallest file). Lossless only.',
            'backup_originals'    => 'Backup Originals',
            'backup_desc'         => 'Keep a .optimizer-backup copy of the original before optimizing',
            'convert_webp'        => 'Convert to WebP',
            'convert_webp_desc'   => 'Generate a .webp version alongside each PNG/JPEG',
            'webp_quality'        => 'WebP Quality',
            'jpeg_quality'        => 'JPEG Quality',
            'jpeg_quality_desc'   => '10 = smallest file (low quality), 95 = highest quality (larger file). Recommended: 75-85',
            'progressive_jpeg'    => 'Progressive JPEG',
            'progressive_desc'    => 'Generate progressive JPEGs (better perceived loading speed)',

            // Bulk optimize
            'bulk_optimize'       => 'Bulk Optimize',
            'images_found'        => '%d optimizable images found in your media library.',
            'start_bulk'          => 'Start Bulk Optimization',

            // System info
            'system_info'         => 'System Info',
            'active_library'      => 'Active Library',
            'none_install'        => 'None — install GD or Imagick!',
            'webp_support_gd'     => 'WebP Support (GD)',
            'imagick_lbl'         => 'Imagick',
            'gd_lbl'              => 'GD',
            'php_version'         => 'PHP Version',
            'max_exec'            => 'Max Execution Time',

            // Dashboard widget
            'widget_title'        => 'Image Optimizer Stats',
            'files_optimized_lbl' => 'Files optimized:',
            'total_saved_lbl'     => 'Total saved:',
            'avg_reduction_lbl'   => 'Avg reduction:',
            'manage'              => 'Manage →',

            // Media library column
            'column_header'       => 'Optimizer',
            'saved_lbl'           => 'saved',
            'optimize_btn'        => 'Optimize',
            'optimize_img_btn'    => 'Optimize Image',

            // AJAX error messages
            'permission_denied'   => 'Permission denied.',
            'invalid_attachment'  => 'Invalid attachment.',
            'optimization_failed' => 'Optimization failed or file is already optimal.',

            // JS strings (passed via wp_localize_script)
            'js_optimizing'       => 'Optimizing…',
            'js_done'             => 'Done!',
            'js_error'            => 'Error. Try again.',
        ],

        'tr' => [
            // General
            'plugin_name'         => 'Image Optimizer',
            'settings_link'       => 'Ayarlar',
            'language'            => 'Dil',
            'save_settings'       => 'Ayarları Kaydet',

            // Stats bar
            'files_optimized'     => 'Optimize Edilen',
            'total_saved'         => 'Toplam Kazanım',
            'avg_reduction'       => 'Ort. Azalma',
            'supported_images'    => 'Desteklenen Görseller',

            // Settings panel
            'settings'            => 'Ayarlar',
            'auto_optimize'       => 'Yüklemede Otomatik Optimize',
            'auto_optimize_desc'  => 'Yüklenen görselleri otomatik olarak optimize et',
            'compression_level'   => 'Sıkıştırma Seviyesi',
            'compression_desc'    => '0 = sıkıştırma yok (en hızlı), 9 = max sıkıştırma (en yavaş, en küçük dosya). Kayıpsız.',
            'backup_originals'    => 'Orijinalleri Yedekle',
            'backup_desc'         => 'Optimize etmeden önce orijinalin .optimizer-backup kopyasını sakla',
            'convert_webp'        => "WebP'ye Dönüştür",
            'convert_webp_desc'   => 'Her PNG/JPEG için yanında bir .webp versiyonu oluştur',
            'webp_quality'        => 'WebP Kalitesi',
            'jpeg_quality'        => 'JPEG Kalitesi',
            'jpeg_quality_desc'   => '10 = en küçük dosya (düşük kalite), 95 = en yüksek kalite (büyük dosya). Önerilen: 75-85',
            'progressive_jpeg'    => 'Progressive JPEG',
            'progressive_desc'    => 'Progressive JPEG oluştur (daha iyi algılanan yükleme hızı)',

            // Bulk optimize
            'bulk_optimize'       => 'Toplu Optimize',
            'images_found'        => 'Medya kütüphanesinde %d optimize edilebilir görsel bulundu.',
            'start_bulk'          => 'Toplu Optimizasyonu Başlat',

            // System info
            'system_info'         => 'Sistem Bilgisi',
            'active_library'      => 'Aktif Kütüphane',
            'none_install'        => 'Yok — GD veya Imagick yükleyin!',
            'webp_support_gd'     => 'WebP Desteği (GD)',
            'imagick_lbl'         => 'Imagick',
            'gd_lbl'              => 'GD',
            'php_version'         => 'PHP Versiyonu',
            'max_exec'            => 'Max Çalışma Süresi',

            // Dashboard widget
            'widget_title'        => 'Image Optimizer İstatistikleri',
            'files_optimized_lbl' => 'Optimize edilen:',
            'total_saved_lbl'     => 'Toplam kazanım:',
            'avg_reduction_lbl'   => 'Ort. azalma:',
            'manage'              => 'Yönet →',

            // Media library column
            'column_header'       => 'Optimizer',
            'saved_lbl'           => 'kazanıldı',
            'optimize_btn'        => 'Optimize Et',
            'optimize_img_btn'    => 'Görseli Optimize Et',

            // AJAX error messages
            'permission_denied'   => 'İzin reddedildi.',
            'invalid_attachment'  => 'Geçersiz dosya.',
            'optimization_failed' => 'Optimizasyon başarısız veya dosya zaten optimal.',

            // JS strings
            'js_optimizing'       => 'Optimize ediliyor…',
            'js_done'             => 'Tamamlandı!',
            'js_error'            => 'Hata. Tekrar deneyin.',
        ],
    ];
}
