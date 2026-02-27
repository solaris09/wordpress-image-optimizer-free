<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core PNG optimization logic.
 * Uses Imagick (preferred) or GD (fallback).
 */
class PNG_Optimizer_Core {

    private $options;

    public function __construct() {
        $this->options = get_option( 'png_optimizer_options', [] );
    }

    /**
     * Optimize a single PNG file on disk.
     *
     * @param  string      $file_path      Absolute path to the PNG file.
     * @param  int         $attachment_id  Optional WP attachment ID for stat tracking.
     * @return array|false Result array on success, false on failure.
     */
    public function optimize_file( $file_path, $attachment_id = 0 ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return false;
        }

        $ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
        if ( $ext !== 'png' ) {
            return false;
        }

        $original_size = filesize( $file_path );
        if ( $original_size === false || $original_size === 0 ) {
            return false;
        }

        // Backup original if requested
        if ( ! empty( $this->options['backup_originals'] ) ) {
            $backup = $file_path . '.png-opt-backup';
            if ( ! file_exists( $backup ) ) {
                copy( $file_path, $backup );
            }
        }

        $success = false;

        if ( extension_loaded( 'imagick' ) ) {
            $success = $this->optimize_with_imagick( $file_path );
        }

        if ( ! $success && extension_loaded( 'gd' ) ) {
            $success = $this->optimize_with_gd( $file_path );
        }

        if ( ! $success ) {
            return false;
        }

        // Clear PHP file stat cache so new size is accurate
        clearstatcache( true, $file_path );
        $new_size   = filesize( $file_path );
        $saved      = $original_size - $new_size;

        // If optimized file is larger, restore backup
        if ( $new_size >= $original_size ) {
            if ( ! empty( $this->options['backup_originals'] ) ) {
                copy( $file_path . '.png-opt-backup', $file_path );
            }
            $new_size = $original_size;
            $saved    = 0;
        }

        // Record stats
        $this->record_stat( $attachment_id, $file_path, $original_size, $new_size, max( 0, $saved ) );

        $result = [
            'file'          => $file_path,
            'original_size' => $original_size,
            'new_size'      => $new_size,
            'saved_bytes'   => max( 0, $saved ),
            'saved_percent' => $original_size > 0 ? round( ( max( 0, $saved ) / $original_size ) * 100, 1 ) : 0,
        ];

        // Optional WebP conversion
        if ( ! empty( $this->options['convert_webp'] ) ) {
            $this->convert_to_webp( $file_path );
        }

        return $result;
    }

    /**
     * Optimize using Imagick.
     */
    private function optimize_with_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'PNG' );

            // Strip metadata
            $imagick->stripImage();

            // PNG compression 0-9 (9 = max lossless compression)
            $level = isset( $this->options['compression_level'] ) ? (int) $this->options['compression_level'] : 6;
            $level = max( 0, min( 9, $level ) );
            $imagick->setImageCompressionQuality( $level * 10 );
            $imagick->setOption( 'png:compression-level', (string) $level );
            $imagick->setOption( 'png:compression-filter', '5' );
            $imagick->setOption( 'png:compression-strategy', '1' );

            // Remove extra PNG chunks
            $imagick->setOption( 'png:exclude-chunk', 'all' );
            $imagick->setOption( 'png:include-chunk', 'none,trns,gAMA' );

            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Optimize using GD (lossless re-save with compression).
     */
    private function optimize_with_gd( $file_path ) {
        $image = @imagecreatefrompng( $file_path );
        if ( ! $image ) {
            return false;
        }

        // Preserve alpha
        imagealphablending( $image, false );
        imagesavealpha( $image, true );

        $level = isset( $this->options['compression_level'] ) ? (int) $this->options['compression_level'] : 6;
        $level = max( 0, min( 9, $level ) );

        $result = imagepng( $image, $file_path, $level );
        imagedestroy( $image );
        return $result;
    }

    /**
     * Convert PNG to WebP alongside the original.
     */
    private function convert_to_webp( $file_path ) {
        $webp_path = preg_replace( '/\.png$/i', '.webp', $file_path );
        $quality   = isset( $this->options['webp_quality'] ) ? (int) $this->options['webp_quality'] : 80;

        if ( extension_loaded( 'imagick' ) ) {
            try {
                $imagick = new Imagick( $file_path );
                $imagick->setImageFormat( 'WEBP' );
                $imagick->setImageCompressionQuality( $quality );
                $imagick->writeImage( $webp_path );
                $imagick->destroy();
                return true;
            } catch ( Exception $e ) {
                // fall through to GD
            }
        }

        if ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) {
            $image = @imagecreatefrompng( $file_path );
            if ( $image ) {
                imagealphablending( $image, false );
                imagesavealpha( $image, true );
                $result = imagewebp( $image, $webp_path, $quality );
                imagedestroy( $image );
                return $result;
            }
        }

        return false;
    }

    /**
     * Get all image sizes for a given attachment and optimize each.
     */
    public function optimize_attachment( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return false;
        }

        $results = [];

        // Main file
        $result = $this->optimize_file( $file, $attachment_id );
        if ( $result ) {
            $results[] = $result;
        }

        // Thumbnail sizes
        $metadata = wp_get_attachment_metadata( $attachment_id );
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $dir = trailingslashit( dirname( $file ) );
            foreach ( $metadata['sizes'] as $size_data ) {
                if ( isset( $size_data['file'] ) ) {
                    $size_file = $dir . $size_data['file'];
                    if ( file_exists( $size_file ) && strtolower( pathinfo( $size_file, PATHINFO_EXTENSION ) ) === 'png' ) {
                        $r = $this->optimize_file( $size_file, $attachment_id );
                        if ( $r ) {
                            $results[] = $r;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Save optimization stats to the database.
     */
    private function record_stat( $attachment_id, $file_path, $original_size, $optimized_size, $saved ) {
        global $wpdb;
        $table = $wpdb->prefix . 'png_optimizer_stats';

        // Check if a record for this file already exists; update it if so
        $existing = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM $table WHERE file_path = %s LIMIT 1", $file_path )
        );

        if ( $existing ) {
            $wpdb->update(
                $table,
                [
                    'original_size'  => $original_size,
                    'optimized_size' => $optimized_size,
                    'saved_bytes'    => $saved,
                    'optimized_at'   => current_time( 'mysql' ),
                ],
                [ 'id' => $existing ],
                [ '%d', '%d', '%d', '%s' ],
                [ '%d' ]
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'attachment_id'  => (int) $attachment_id,
                    'file_path'      => $file_path,
                    'original_size'  => $original_size,
                    'optimized_size' => $optimized_size,
                    'saved_bytes'    => $saved,
                    'optimized_at'   => current_time( 'mysql' ),
                ],
                [ '%d', '%s', '%d', '%d', '%d', '%s' ]
            );
        }
    }

    /**
     * Get overall stats summary.
     */
    public static function get_stats_summary() {
        global $wpdb;
        $table = $wpdb->prefix . 'png_optimizer_stats';
        return $wpdb->get_row( "SELECT COUNT(*) as total_files, SUM(saved_bytes) as total_saved, SUM(original_size) as total_original FROM $table" );
    }

    /**
     * Get list of all PNG attachments (IDs) in the media library.
     */
    public static function get_all_png_attachment_ids() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
             AND post_status = 'inherit'
             AND post_mime_type = 'image/png'"
        );
    }

    /**
     * Check which library is available.
     */
    public static function get_available_library() {
        if ( extension_loaded( 'imagick' ) ) {
            return 'Imagick';
        }
        if ( extension_loaded( 'gd' ) ) {
            return 'GD';
        }
        return 'none';
    }
}
