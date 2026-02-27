<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core image optimization logic for PNG and JPEG.
 * Uses Imagick (preferred) or GD (fallback).
 */
class PNG_Optimizer_Core {

    private $options;

    public function __construct() {
        $this->options = get_option( 'png_optimizer_options', [] );
    }

    /**
     * Optimize a single image file on disk (PNG or JPEG).
     *
     * @param  string      $file_path      Absolute path to the image file.
     * @param  int         $attachment_id  Optional WP attachment ID for stat tracking.
     * @return array|false Result array on success, false on failure.
     */
    public function optimize_file( $file_path, $attachment_id = 0 ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return false;
        }

        $ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, [ 'png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'tiff', 'tif' ], true ) ) {
            return false;
        }

        $original_size = filesize( $file_path );
        if ( $original_size === false || $original_size === 0 ) {
            return false;
        }

        // Backup original if requested
        if ( ! empty( $this->options['backup_originals'] ) ) {
            $backup = $file_path . '.optimizer-backup';
            if ( ! file_exists( $backup ) ) {
                copy( $file_path, $backup );
            }
        }

        $success = false;

        switch ( $ext ) {
            case 'png':
                $success = $this->optimize_png( $file_path );
                break;
            case 'jpg':
            case 'jpeg':
                $success = $this->optimize_jpeg( $file_path );
                break;
            case 'webp':
                $success = $this->optimize_webp( $file_path );
                break;
            case 'gif':
                $success = $this->optimize_gif( $file_path );
                break;
            case 'bmp':
                $success = $this->optimize_bmp( $file_path );
                break;
            case 'tiff':
            case 'tif':
                $success = $this->optimize_tiff( $file_path );
                break;
            default:
                return false;
        }

        if ( ! $success ) {
            return false;
        }

        clearstatcache( true, $file_path );
        $new_size = filesize( $file_path );
        $saved    = $original_size - $new_size;

        // If optimized file is larger, restore backup
        if ( $new_size >= $original_size ) {
            if ( ! empty( $this->options['backup_originals'] ) ) {
                copy( $file_path . '.optimizer-backup', $file_path );
            }
            $new_size = $original_size;
            $saved    = 0;
        }

        // Record stats
        $this->record_stat( $attachment_id, $file_path, $original_size, $new_size, max( 0, $saved ) );

        return [
            'file'          => $file_path,
            'original_size' => $original_size,
            'new_size'      => $new_size,
            'saved_bytes'   => max( 0, $saved ),
            'saved_percent' => $original_size > 0 ? round( ( max( 0, $saved ) / $original_size ) * 100, 1 ) : 0,
        ];
    }

    /**
     * Optimize PNG with lossless compression.
     */
    private function optimize_png( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            if ( $this->optimize_png_imagick( $file_path ) ) {
                return true;
            }
        }

        if ( extension_loaded( 'gd' ) ) {
            return $this->optimize_png_gd( $file_path );
        }

        return false;
    }

    private function optimize_png_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'PNG' );

            // Strip metadata
            $imagick->stripImage();

            // Enhanced compression
            $level = isset( $this->options['compression_level'] ) ? (int) $this->options['compression_level'] : 6;
            $level = max( 0, min( 9, $level ) );
            $imagick->setImageCompressionQuality( $level * 10 );
            $imagick->setOption( 'png:compression-level', (string) $level );
            $imagick->setOption( 'png:compression-filter', '5' );
            $imagick->setOption( 'png:compression-strategy', '1' );

            // Remove extra PNG chunks for maximum optimization
            $imagick->setOption( 'png:exclude-chunk', 'all' );
            $imagick->setOption( 'png:include-chunk', 'none,trns,gAMA' );

            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    private function optimize_png_gd( $file_path ) {
        $image = @imagecreatefrompng( $file_path );
        if ( ! $image ) {
            return false;
        }

        imagealphablending( $image, false );
        imagesavealpha( $image, true );

        $level = isset( $this->options['compression_level'] ) ? (int) $this->options['compression_level'] : 6;
        $level = max( 0, min( 9, $level ) );

        $result = imagepng( $image, $file_path, $level );
        imagedestroy( $image );
        return $result;
    }

    /**
     * Optimize JPEG with quality adjustment.
     */
    private function optimize_jpeg( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            if ( $this->optimize_jpeg_imagick( $file_path ) ) {
                return true;
            }
        }

        if ( extension_loaded( 'gd' ) ) {
            return $this->optimize_jpeg_gd( $file_path );
        }

        return false;
    }

    private function optimize_jpeg_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'JPEG' );

            // Strip metadata for smaller file size
            $imagick->stripImage();

            // Enhanced JPEG compression
            $quality = isset( $this->options['jpeg_quality'] ) ? (int) $this->options['jpeg_quality'] : 80;
            $quality = max( 10, min( 95, $quality ) );
            $imagick->setImageCompressionQuality( $quality );

            // Enable progressive JPEG if enabled
            if ( ! empty( $this->options['progressive_jpeg'] ) ) {
                $imagick->setImageInterlaceScheme( Imagick::INTERLACE_JPEG );
            }

            // Optimize colorspace
            $imagick->transformImageColorspace( Imagick::COLORSPACE_SRGB );

            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    private function optimize_jpeg_gd( $file_path ) {
        $image = @imagecreatefromjpeg( $file_path );
        if ( ! $image ) {
            return false;
        }

        $quality = isset( $this->options['jpeg_quality'] ) ? (int) $this->options['jpeg_quality'] : 80;
        $quality = max( 10, min( 95, $quality ) );

        $result = imagejpeg( $image, $file_path, $quality );
        imagedestroy( $image );
        return $result;
    }

    /**
     * Optimize WebP.
     */
    private function optimize_webp( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            if ( $this->optimize_webp_imagick( $file_path ) ) {
                return true;
            }
        }
        if ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) {
            return $this->optimize_webp_gd( $file_path );
        }
        return false;
    }

    private function optimize_webp_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'WEBP' );
            $imagick->stripImage();
            $quality = isset( $this->options['webp_quality'] ) ? (int) $this->options['webp_quality'] : 80;
            $quality = max( 1, min( 100, $quality ) );
            $imagick->setImageCompressionQuality( $quality );
            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    private function optimize_webp_gd( $file_path ) {
        $image = @imagecreatefromwebp( $file_path );
        if ( ! $image ) {
            return false;
        }
        $quality = isset( $this->options['webp_quality'] ) ? (int) $this->options['webp_quality'] : 80;
        $quality = max( 1, min( 100, $quality ) );
        $result = imagewebp( $image, $file_path, $quality );
        imagedestroy( $image );
        return $result;
    }

    /**
     * Optimize GIF.
     */
    private function optimize_gif( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            if ( $this->optimize_gif_imagick( $file_path ) ) {
                return true;
            }
        }
        if ( extension_loaded( 'gd' ) ) {
            return $this->optimize_gif_gd( $file_path );
        }
        return false;
    }

    private function optimize_gif_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'GIF' );
            $imagick->stripImage();
            $imagick->optimizeImageLayers();
            $written = $imagick->writeImages( $file_path, true );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    private function optimize_gif_gd( $file_path ) {
        $image = @imagecreatefromgif( $file_path );
        if ( ! $image ) {
            return false;
        }
        $result = imagegif( $image, $file_path );
        imagedestroy( $image );
        return $result;
    }

    /**
     * Optimize BMP.
     */
    private function optimize_bmp( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            return $this->optimize_bmp_imagick( $file_path );
        }
        return false;
    }

    private function optimize_bmp_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'BMP' );
            $imagick->stripImage();
            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Optimize TIFF.
     */
    private function optimize_tiff( $file_path ) {
        if ( extension_loaded( 'imagick' ) ) {
            return $this->optimize_tiff_imagick( $file_path );
        }
        return false;
    }

    private function optimize_tiff_imagick( $file_path ) {
        try {
            $imagick = new Imagick( $file_path );
            $imagick->setImageFormat( 'TIFF' );
            $imagick->stripImage();
            $imagick->setImageCompression( Imagick::COMPRESSION_JPEG );
            $quality = isset( $this->options['jpeg_quality'] ) ? (int) $this->options['jpeg_quality'] : 80;
            $quality = max( 10, min( 95, $quality ) );
            $imagick->setImageCompressionQuality( $quality );
            $written = $imagick->writeImage( $file_path );
            $imagick->destroy();
            return $written;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Get all image attachments (all supported formats).
     */
    public static function get_all_image_attachment_ids() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
             AND post_status = 'inherit'
             AND post_mime_type IN ('image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/x-ms-bmp', 'image/x-bmp', 'image/bmp', 'image/tiff', 'image/x-tiff')"
        );
    }

    /**
     * Get all PNG attachment IDs (for backward compatibility).
     */
    public static function get_all_png_attachment_ids() {
        return self::get_all_image_attachment_ids();
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
     * Optimize all sizes of an attachment.
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
                    $ext       = strtolower( pathinfo( $size_file, PATHINFO_EXTENSION ) );
                    if ( file_exists( $size_file ) && in_array( $ext, [ 'png', 'jpg', 'jpeg' ], true ) ) {
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
     * Save optimization stats to database.
     */
    private function record_stat( $attachment_id, $file_path, $original_size, $optimized_size, $saved ) {
        global $wpdb;
        $table = $wpdb->prefix . 'png_optimizer_stats';

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
     * Check available library.
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
