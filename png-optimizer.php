<?php
/**
 * Plugin Name: Image Optimizer
 * Plugin URI:  https://github.com/solaris09/wordpress-image-optimizer-free
 * Description: Automatically optimizes all image formats (PNG, JPEG, WebP, GIF, BMP, TIFF) on upload. Supports lossless compression via GD/Imagick with bulk optimization and optional WebP conversion.
 * Version:     1.1.0
 * Author:      CEMAL HEKIMOGLU
 * License:     GPL-2.0+
 * Text Domain: png-optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PNG_OPT_VERSION',  '1.0.0' );
define( 'PNG_OPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PNG_OPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PNG_OPT_PLUGIN_DIR . 'includes/class-optimizer.php';
require_once PNG_OPT_PLUGIN_DIR . 'includes/class-admin.php';
require_once PNG_OPT_PLUGIN_DIR . 'includes/class-bulk.php';

function png_optimizer_init() {
    new PNG_Optimizer_Admin();
    new PNG_Optimizer_Bulk();

    // Auto-optimize on upload
    add_filter( 'wp_handle_upload', 'png_optimizer_on_upload' );
}
add_action( 'plugins_loaded', 'png_optimizer_init' );

function png_optimizer_on_upload( $upload ) {
    if ( ! isset( $upload['file'] ) ) {
        return $upload;
    }

    $ext = strtolower( pathinfo( $upload['file'], PATHINFO_EXTENSION ) );
    if ( ! in_array( $ext, [ 'png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'tiff', 'tif' ], true ) ) {
        return $upload;
    }

    $options = get_option( 'png_optimizer_options', [] );
    $auto    = isset( $options['auto_optimize'] ) ? (bool) $options['auto_optimize'] : true;

    if ( $auto ) {
        $optimizer = new PNG_Optimizer_Core();
        $optimizer->optimize_file( $upload['file'] );
    }
    return $upload;
}

register_activation_hook( __FILE__, 'png_optimizer_activate' );
function png_optimizer_activate() {
    $defaults = [
        'auto_optimize'    => true,
        'compression_level'=> 6,
        'convert_webp'     => false,
        'webp_quality'     => 80,
        'backup_originals' => true,
        'jpeg_quality'     => 80,
        'progressive_jpeg' => false,
    ];
    add_option( 'png_optimizer_options', $defaults );

    // Stats table
    global $wpdb;
    $table   = $wpdb->prefix . 'png_optimizer_stats';
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE IF NOT EXISTS $table (
        id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        attachment_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        file_path    VARCHAR(500) NOT NULL,
        original_size BIGINT(20) NOT NULL DEFAULT 0,
        optimized_size BIGINT(20) NOT NULL DEFAULT 0,
        saved_bytes  BIGINT(20) NOT NULL DEFAULT 0,
        optimized_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY attachment_id (attachment_id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

register_deactivation_hook( __FILE__, 'png_optimizer_deactivate' );
function png_optimizer_deactivate() {
    // Nothing to clean on deactivation
}

// "Ayarlar" linki — Eklentiler listesinde plugin isminin hemen altında görünür
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'png_optimizer_action_links' );
function png_optimizer_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'upload.php?page=png-optimizer' ) ) . '">'
        . __( 'Ayarlar', 'png-optimizer' )
        . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
