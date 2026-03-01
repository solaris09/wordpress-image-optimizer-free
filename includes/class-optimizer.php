<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core image optimization logic for PNG, JPEG, WebP, GIF, BMP, TIFF.
 * Uses Imagick (preferred) or GD (fallback).
 */
class PNG_Optimizer_Core {

    private $options;

    public function __construct() {
        $this->options = get_option( 'png_optimizer_options', [] );
    }

    /**
     * Optimize a single image file on disk.
     *
     * @param  string $file_path      Absolute path to the image file.
     * @param  int    $attachment_id  Optional WP attachment ID for stat tracking.
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

        // Even if optimization failed, still try WebP conversion if enabled.
        // (e.g. server has GD+WebP but Imagick is missing — optimize fails but WebP can work)
        if ( ! $success ) {
            if ( ! empty( $this->options['convert_webp'] ) && in_array( $ext, [ 'png', 'jpg', 'jpeg' ], true ) ) {
                $this->create_webp_version( $file_path, $ext );
            }
            return false;
        }

        clearstatcache( true, $file_path );
        $new_size = filesize( $file_path );
        $saved    = $original_size - $new_size;

        // If optimized file is larger, restore from backup
        if ( $new_size >= $original_size ) {
            if ( ! empty( $this->options['backup_originals'] ) ) {
                copy( $file_path . '.optimizer-backup', $file_path );
            }
            $new_size = $original_size;
            $saved    = 0;
        }

        // Record stats
        $this->record_stat( $attachment_id, $file_path, $original_size, $new_size, max( 0, $saved ) );

        // Generate WebP version if enabled (PNG and JPEG only)
        if ( ! empty( $this->options['convert_webp'] ) && in_array( $ext, [ 'png', 'jpg', 'jpeg' ], true ) ) {
            $this->create_webp_version( $file_path, $ext );
        }

        return [
            'file'          => $file_path,
            'original_size' => $original_size,
            'new_size'      => $new_size,
            'saved_bytes'   => max( 0, $saved ),
            'saved_percent' => $original_size > 0 ? round( ( max( 0, $saved ) / $original_size ) * 100, 1 ) : 0,
        ];
    }

    // -------------------------------------------------------------------------
    // PNG
    // -------------------------------------------------------------------------

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
            $imagick->stripImage();

            $level = isset( $this->options['compression_level'] ) ? (int) $this->options['compression_level'] : 6;
            $level = max( 0, min( 9, $level ) );

            // Use ZIP compression explicitly
            $imagick->setImageCompression( Imagick::COMPRESSION_ZIP );

            // Composite quality: compression_level * 10 + filter (5 = Paeth, best general filter)
            $imagick->setImageCompressionQuality( $level * 10 + 5 );

            // Fine-grained PNG options — Paeth filter + default strategy for best overall compression
            $imagick->setOption( 'png:compression-level',   (string) $level );
            $imagick->setOption( 'png:compression-filter',  '5' );  // Paeth
            $imagick->setOption( 'png:compression-strategy','0' );  // Z_DEFAULT_STRATEGY

            // Strip unnecessary PNG chunks
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

        // PNG_ALL_FILTERS lets the encoder try every filter and pick the smallest result
        $result = imagepng( $image, $file_path, $level, PNG_ALL_FILTERS );
        imagedestroy( $image );
        return $result;
    }

    // -------------------------------------------------------------------------
    // JPEG
    // -------------------------------------------------------------------------

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
            $imagick->stripImage();

            $quality = isset( $this->options['jpeg_quality'] ) ? (int) $this->options['jpeg_quality'] : 80;
            $quality = max( 10, min( 95, $quality ) );
            $imagick->setImageCompressionQuality( $quality );

            // 4:2:0 chroma subsampling — reduces file size ~15-20% with minimal visual impact
            $imagick->setSamplingFactors( [ '2x2', '1x1', '1x1' ] );

            // Progressive JPEG if enabled
            if ( ! empty( $this->options['progressive_jpeg'] ) ) {
                $imagick->setImageInterlaceScheme( Imagick::INTERLACE_JPEG );
            }

            // Normalize colorspace
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

    // -------------------------------------------------------------------------
    // WebP (optimize existing WebP files)
    // -------------------------------------------------------------------------

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
        $result  = imagewebp( $image, $file_path, $quality );
        imagedestroy( $image );
        return $result;
    }

    // -------------------------------------------------------------------------
    // WebP conversion (PNG/JPEG → WebP)
    // -------------------------------------------------------------------------

    /**
     * Generate a .webp file next to the source image.
     * Called when the "Convert to WebP" option is enabled.
     *
     * @param  string $file_path Source image path (PNG or JPEG).
     * @param  string $ext       Lowercase file extension without dot (png|jpg|jpeg).
     * @return bool
     */
    private function create_webp_version( $file_path, $ext ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return false;
        }

        // Build .webp path by replacing the extension
        $webp_path = substr( $file_path, 0, strrpos( $file_path, '.' ) + 1 ) . 'webp';

        $quality = isset( $this->options['webp_quality'] ) ? (int) $this->options['webp_quality'] : 80;
        $quality = max( 1, min( 100, $quality ) );

        // ── Imagick ──────────────────────────────────────────────────────────
        if ( extension_loaded( 'imagick' ) ) {
            try {
                $formats = Imagick::queryFormats( 'WEBP' );
                if ( ! empty( $formats ) ) {
                    $imagick = new Imagick( $file_path );
                    $imagick->setImageFormat( 'WEBP' );
                    $imagick->stripImage();
                    $imagick->setImageCompressionQuality( $quality );
                    $imagick->writeImage( $webp_path );
                    $imagick->destroy();
                    if ( file_exists( $webp_path ) && filesize( $webp_path ) > 0 ) {
                        return true;
                    }
                }
            } catch ( Exception $e ) {
                // fall through to GD
            }
        }

        // ── GD ───────────────────────────────────────────────────────────────
        if ( ! extension_loaded( 'gd' ) || ! function_exists( 'imagewebp' ) ) {
            return false;
        }

        $image = null;

        // Try type-specific loader first
        if ( $ext === 'png' ) {
            $image = @imagecreatefrompng( $file_path );
            if ( $image ) {
                imagealphablending( $image, false );
                imagesavealpha( $image, true );
            }
        } elseif ( in_array( $ext, [ 'jpg', 'jpeg' ], true ) ) {
            $image = @imagecreatefromjpeg( $file_path );
        }

        // Generic fallback via imagecreatefromstring (works for any format GD can decode)
        if ( ! $image ) {
            $data = @file_get_contents( $file_path );
            if ( $data ) {
                $image = @imagecreatefromstring( $data );
            }
        }

        if ( $image ) {
            $result = @imagewebp( $image, $webp_path, $quality );
            imagedestroy( $image );

            // Verify the file was actually written (imagewebp can return true but write 0 bytes)
            if ( $result && file_exists( $webp_path ) && filesize( $webp_path ) > 0 ) {
                return true;
            }
        }

        // ── cwebp CLI fallback ────────────────────────────────────────────────
        // Works even when GD/Imagick have no WebP support, as long as cwebp is installed.
        if ( function_exists( 'exec' ) ) {
            $cmd = 'cwebp -q ' . (int) $quality
                 . ' ' . escapeshellarg( $file_path )
                 . ' -o ' . escapeshellarg( $webp_path )
                 . ' 2>/dev/null';
            $return_code = -1;
            @exec( $cmd, $out, $return_code );
            if ( $return_code === 0 && file_exists( $webp_path ) && filesize( $webp_path ) > 0 ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the server can generate WebP files.
     * Used by the admin UI to show warnings when WebP conversion is enabled.
     *
     * @return bool
     */
    public static function can_create_webp() {
        // Imagick with WEBP format support
        if ( extension_loaded( 'imagick' ) ) {
            try {
                $formats = Imagick::queryFormats( 'WEBP' );
                if ( ! empty( $formats ) ) {
                    return true;
                }
            } catch ( Exception $e ) {}
        }

        // GD with imagewebp() support
        if ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Convert a single attachment's main file to WebP (public wrapper).
     * Called from the admin AJAX "Convert to WebP" button.
     *
     * @param  int $attachment_id
     * @return bool  true if the .webp file was written, false otherwise.
     */
    public function convert_attachment_to_webp( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) {
            return false;
        }
        $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, [ 'png', 'jpg', 'jpeg' ], true ) ) {
            return false;
        }
        return $this->create_webp_version( $file, $ext );
    }

    // -------------------------------------------------------------------------
    // GIF
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // BMP
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // TIFF
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Attachment helpers
    // -------------------------------------------------------------------------

    /**
     * Optimize all sizes of an attachment.
     */
    public function optimize_attachment( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return false;
        }

        $results = [];

        $result = $this->optimize_file( $file, $attachment_id );
        if ( $result ) {
            $results[] = $result;
        }

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

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    public static function get_all_image_attachment_ids() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
             AND post_status = 'inherit'
             AND post_mime_type IN ('image/png','image/jpeg','image/webp','image/gif','image/x-ms-bmp','image/x-bmp','image/bmp','image/tiff','image/x-tiff')"
        );
    }

    public static function get_all_png_attachment_ids() {
        return self::get_all_image_attachment_ids();
    }

    public static function get_stats_summary() {
        global $wpdb;
        $table = $wpdb->prefix . 'png_optimizer_stats';
        return $wpdb->get_row( "SELECT COUNT(*) as total_files, SUM(saved_bytes) as total_saved, SUM(original_size) as total_original FROM $table" );
    }

    public static function get_available_library() {
        if ( extension_loaded( 'imagick' ) ) {
            return 'Imagick';
        }
        if ( extension_loaded( 'gd' ) ) {
            return 'GD';
        }
        return 'none';
    }

    // -------------------------------------------------------------------------
    // Stats
    // -------------------------------------------------------------------------

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
}
