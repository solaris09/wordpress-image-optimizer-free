<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PNG_Optimizer_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_init',            [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Media library column
        add_filter( 'manage_media_columns',       [ $this, 'add_media_column' ] );
        add_action( 'manage_media_custom_column', [ $this, 'render_media_column' ], 10, 2 );

        // Optimize button in attachment edit screen
        add_action( 'attachment_submitbox_misc_actions',  [ $this, 'add_optimize_button' ] );
        add_action( 'wp_ajax_png_opt_optimize_single',    [ $this, 'ajax_optimize_single' ] );

        // Dashboard widget
        add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
    }

    // -------------------------------------------------------------------------
    // Menu & settings registration
    // -------------------------------------------------------------------------

    public function add_menu() {
        add_media_page(
            png_opt_t( 'plugin_name' ),
            png_opt_t( 'plugin_name' ),
            'manage_options',
            'png-optimizer',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'png_optimizer_group', 'png_optimizer_options', [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
        ] );
    }

    public function sanitize_options( $input ) {
        $clean = [];
        $clean['auto_optimize']      = ! empty( $input['auto_optimize'] );
        $clean['compression_level']  = isset( $input['compression_level'] ) ? max( 0, min( 9, (int) $input['compression_level'] ) ) : 6;
        $clean['convert_webp']       = ! empty( $input['convert_webp'] );
        $clean['webp_quality']       = isset( $input['webp_quality'] ) ? max( 1, min( 100, (int) $input['webp_quality'] ) ) : 80;
        $clean['backup_originals']   = ! empty( $input['backup_originals'] );
        $clean['jpeg_quality']       = isset( $input['jpeg_quality'] ) ? max( 10, min( 95, (int) $input['jpeg_quality'] ) ) : 80;
        $clean['progressive_jpeg']   = ! empty( $input['progressive_jpeg'] );
        $clean['language']           = ( isset( $input['language'] ) && $input['language'] === 'tr' ) ? 'tr' : 'en';
        return $clean;
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue_assets( $hook ) {
        $allowed_hooks = [ 'media_page_png-optimizer', 'upload.php', 'post.php' ];
        if ( ! in_array( $hook, $allowed_hooks, true ) ) {
            return;
        }
        wp_enqueue_style(
            'png-optimizer-admin',
            PNG_OPT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            PNG_OPT_VERSION
        );
        wp_enqueue_script(
            'png-optimizer-admin',
            PNG_OPT_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            PNG_OPT_VERSION,
            true
        );
        wp_localize_script( 'png-optimizer-admin', 'pngOpt', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'png_opt_nonce' ),
            'i18n'     => [
                'optimizing' => png_opt_t( 'js_optimizing' ),
                'done'       => png_opt_t( 'js_done' ),
                'error'      => png_opt_t( 'js_error' ),
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Settings page
    // -------------------------------------------------------------------------

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = get_option( 'png_optimizer_options', [] );
        $lang    = isset( $options['language'] ) ? $options['language'] : 'en';
        $stats   = PNG_Optimizer_Core::get_stats_summary();
        $lib     = PNG_Optimizer_Core::get_available_library();
        $total   = PNG_Optimizer_Core::get_all_png_attachment_ids();
        ?>
        <div class="wrap png-opt-wrap">
            <h1><span class="dashicons dashicons-images-alt2"></span> <?php echo esc_html( png_opt_t( 'plugin_name', $lang ) ); ?></h1>

            <!-- Stats bar -->
            <div class="png-opt-stats-bar">
                <div class="png-opt-stat">
                    <span class="png-opt-stat-number"><?php echo esc_html( number_format( (int) ( $stats->total_files ?? 0 ) ) ); ?></span>
                    <span class="png-opt-stat-label"><?php echo esc_html( png_opt_t( 'files_optimized', $lang ) ); ?></span>
                </div>
                <div class="png-opt-stat">
                    <span class="png-opt-stat-number"><?php echo esc_html( $this->format_bytes( (int) ( $stats->total_saved ?? 0 ) ) ); ?></span>
                    <span class="png-opt-stat-label"><?php echo esc_html( png_opt_t( 'total_saved', $lang ) ); ?></span>
                </div>
                <div class="png-opt-stat">
                    <?php
                    $pct = 0;
                    if ( ! empty( $stats->total_original ) && $stats->total_original > 0 ) {
                        $pct = round( ( $stats->total_saved / $stats->total_original ) * 100, 1 );
                    }
                    ?>
                    <span class="png-opt-stat-number"><?php echo esc_html( $pct ); ?>%</span>
                    <span class="png-opt-stat-label"><?php echo esc_html( png_opt_t( 'avg_reduction', $lang ) ); ?></span>
                </div>
                <div class="png-opt-stat">
                    <span class="png-opt-stat-number"><?php echo esc_html( count( $total ) ); ?></span>
                    <span class="png-opt-stat-label"><?php echo esc_html( png_opt_t( 'supported_images', $lang ) ); ?></span>
                </div>
            </div>

            <div class="png-opt-columns">
                <!-- Settings -->
                <div class="png-opt-card">
                    <h2><?php echo esc_html( png_opt_t( 'settings', $lang ) ); ?></h2>
                    <form method="post" action="options.php">
                        <?php settings_fields( 'png_optimizer_group' ); ?>

                        <table class="form-table">

                            <!-- Language -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'language', $lang ) ); ?></th>
                                <td>
                                    <select name="png_optimizer_options[language]">
                                        <option value="en" <?php selected( $lang, 'en' ); ?>>English</option>
                                        <option value="tr" <?php selected( $lang, 'tr' ); ?>>Türkçe</option>
                                    </select>
                                </td>
                            </tr>

                            <!-- Auto-optimize -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'auto_optimize', $lang ) ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="png_optimizer_options[auto_optimize]" value="1"
                                            <?php checked( ! empty( $options['auto_optimize'] ) ); ?>>
                                        <?php echo esc_html( png_opt_t( 'auto_optimize_desc', $lang ) ); ?>
                                    </label>
                                </td>
                            </tr>

                            <!-- Compression level -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'compression_level', $lang ) ); ?></th>
                                <td>
                                    <input type="range" name="png_optimizer_options[compression_level]"
                                        min="0" max="9"
                                        value="<?php echo esc_attr( $options['compression_level'] ?? 6 ); ?>"
                                        oninput="document.getElementById('png-opt-level-val').textContent=this.value">
                                    <span id="png-opt-level-val"><?php echo esc_html( $options['compression_level'] ?? 6 ); ?></span> / 9
                                    <p class="description"><?php echo esc_html( png_opt_t( 'compression_desc', $lang ) ); ?></p>
                                </td>
                            </tr>

                            <!-- Backup originals -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'backup_originals', $lang ) ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="png_optimizer_options[backup_originals]" value="1"
                                            <?php checked( ! empty( $options['backup_originals'] ) ); ?>>
                                        <?php echo esc_html( png_opt_t( 'backup_desc', $lang ) ); ?>
                                    </label>
                                </td>
                            </tr>

                            <!-- Convert to WebP -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'convert_webp', $lang ) ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="png_optimizer_options[convert_webp]" value="1"
                                            id="png-opt-webp-toggle"
                                            <?php checked( ! empty( $options['convert_webp'] ) ); ?>>
                                        <?php echo esc_html( png_opt_t( 'convert_webp_desc', $lang ) ); ?>
                                    </label>
                                </td>
                            </tr>

                            <!-- WebP quality (hidden when convert_webp is off) -->
                            <tr id="png-opt-webp-quality-row" <?php echo empty( $options['convert_webp'] ) ? 'style="display:none"' : ''; ?>>
                                <th><?php echo esc_html( png_opt_t( 'webp_quality', $lang ) ); ?></th>
                                <td>
                                    <input type="number" name="png_optimizer_options[webp_quality]"
                                        min="1" max="100"
                                        value="<?php echo esc_attr( $options['webp_quality'] ?? 80 ); ?>"
                                        style="width:70px"> / 100
                                </td>
                            </tr>

                            <!-- JPEG quality -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'jpeg_quality', $lang ) ); ?></th>
                                <td>
                                    <input type="number" name="png_optimizer_options[jpeg_quality]"
                                        min="10" max="95"
                                        value="<?php echo esc_attr( $options['jpeg_quality'] ?? 80 ); ?>"
                                        style="width:70px"> / 95
                                    <p class="description"><?php echo esc_html( png_opt_t( 'jpeg_quality_desc', $lang ) ); ?></p>
                                </td>
                            </tr>

                            <!-- Progressive JPEG -->
                            <tr>
                                <th><?php echo esc_html( png_opt_t( 'progressive_jpeg', $lang ) ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="png_optimizer_options[progressive_jpeg]" value="1"
                                            <?php checked( ! empty( $options['progressive_jpeg'] ) ); ?>>
                                        <?php echo esc_html( png_opt_t( 'progressive_desc', $lang ) ); ?>
                                    </label>
                                </td>
                            </tr>

                        </table>

                        <?php submit_button( png_opt_t( 'save_settings', $lang ) ); ?>
                    </form>
                </div>

                <!-- Bulk optimizer & system info -->
                <div class="png-opt-sidebar">
                    <div class="png-opt-card">
                        <h2><?php echo esc_html( png_opt_t( 'bulk_optimize', $lang ) ); ?></h2>
                        <p><?php echo esc_html( sprintf( png_opt_t( 'images_found', $lang ), count( $total ) ) ); ?></p>
                        <div id="png-opt-bulk-progress" style="display:none">
                            <div class="png-opt-progress-bar"><div class="png-opt-progress-fill" id="png-opt-progress-fill"></div></div>
                            <p id="png-opt-bulk-status"></p>
                        </div>
                        <button id="png-opt-bulk-btn" class="button button-primary button-large">
                            <?php echo esc_html( png_opt_t( 'start_bulk', $lang ) ); ?>
                        </button>
                        <div id="png-opt-bulk-result"></div>
                    </div>

                    <div class="png-opt-card">
                        <h2><?php echo esc_html( png_opt_t( 'system_info', $lang ) ); ?></h2>
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'active_library', $lang ) ); ?></td>
                                    <td>
                                        <?php if ( $lib === 'none' ) : ?>
                                            <span class="png-opt-badge png-opt-badge-error"><?php echo esc_html( png_opt_t( 'none_install', $lang ) ); ?></span>
                                        <?php else : ?>
                                            <span class="png-opt-badge png-opt-badge-ok"><?php echo esc_html( $lib ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'imagick_lbl', $lang ) ); ?></td>
                                    <td><?php echo extension_loaded( 'imagick' ) ? '<span class="png-opt-badge png-opt-badge-ok">✓ Loaded</span>' : '<span class="png-opt-badge png-opt-badge-warn">✗ Not loaded</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'gd_lbl', $lang ) ); ?></td>
                                    <td><?php echo extension_loaded( 'gd' ) ? '<span class="png-opt-badge png-opt-badge-ok">✓ Loaded</span>' : '<span class="png-opt-badge png-opt-badge-warn">✗ Not loaded</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'webp_support_gd', $lang ) ); ?></td>
                                    <td><?php echo function_exists( 'imagewebp' ) ? '<span class="png-opt-badge png-opt-badge-ok">✓ Yes</span>' : '<span class="png-opt-badge png-opt-badge-warn">✗ No</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'php_version', $lang ) ); ?></td>
                                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo esc_html( png_opt_t( 'max_exec', $lang ) ); ?></td>
                                    <td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?>s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Media library column
    // -------------------------------------------------------------------------

    public function add_media_column( $columns ) {
        $columns['png_optimizer'] = png_opt_t( 'column_header' );
        return $columns;
    }

    public function render_media_column( $column, $post_id ) {
        if ( $column !== 'png_optimizer' ) {
            return;
        }

        $mime      = get_post_mime_type( $post_id );
        $supported = [ 'image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/x-ms-bmp', 'image/x-bmp', 'image/bmp', 'image/tiff', 'image/x-tiff' ];
        if ( ! in_array( $mime, $supported, true ) ) {
            echo '<span style="color:#aaa">—</span>';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'png_optimizer_stats';
        $row   = $wpdb->get_row( $wpdb->prepare(
            "SELECT SUM(saved_bytes) as saved, SUM(original_size) as orig FROM $table WHERE attachment_id = %d",
            $post_id
        ) );

        if ( $row && $row->saved > 0 ) {
            $pct = $row->orig > 0 ? round( ( $row->saved / $row->orig ) * 100, 1 ) : 0;
            echo '<span style="color:#2eb136;font-weight:600">-' . esc_html( $pct ) . '%</span>';
            echo '<br><small>' . esc_html( $this->format_bytes( (int) $row->saved ) ) . ' ' . esc_html( png_opt_t( 'saved_lbl' ) ) . '</small>';
        } else {
            echo '<button class="button button-small png-opt-single-btn" data-id="' . esc_attr( $post_id ) . '">'
                . esc_html( png_opt_t( 'optimize_btn' ) )
                . '</button>';
        }
    }

    // -------------------------------------------------------------------------
    // Attachment edit page button
    // -------------------------------------------------------------------------

    public function add_optimize_button() {
        global $post;
        if ( ! $post ) {
            return;
        }
        $mime      = get_post_mime_type( $post->ID );
        $supported = [ 'image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/x-ms-bmp', 'image/x-bmp', 'image/bmp', 'image/tiff', 'image/x-tiff' ];
        if ( ! in_array( $mime, $supported, true ) ) {
            return;
        }
        echo '<div class="misc-pub-section">';
        echo '<button type="button" class="button png-opt-single-btn" data-id="' . esc_attr( $post->ID ) . '">'
            . esc_html( png_opt_t( 'optimize_img_btn' ) )
            . '</button>';
        echo '<span id="png-opt-single-result-' . esc_attr( $post->ID ) . '" style="margin-left:8px"></span>';
        echo '</div>';
    }

    // -------------------------------------------------------------------------
    // AJAX: optimize single
    // -------------------------------------------------------------------------

    public function ajax_optimize_single() {
        check_ajax_referer( 'png_opt_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => png_opt_t( 'permission_denied' ) ] );
        }

        $id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => png_opt_t( 'invalid_attachment' ) ] );
        }

        $optimizer = new PNG_Optimizer_Core();
        $results   = $optimizer->optimize_attachment( $id );

        if ( ! $results ) {
            wp_send_json_error( [ 'message' => png_opt_t( 'optimization_failed' ) ] );
        }

        $total_saved    = array_sum( array_column( $results, 'saved_bytes' ) );
        $total_original = array_sum( array_column( $results, 'original_size' ) );
        $pct            = $total_original > 0 ? round( ( $total_saved / $total_original ) * 100, 1 ) : 0;

        wp_send_json_success( [
            'saved_bytes'   => $total_saved,
            'saved_percent' => $pct,
            'human'         => $this->format_bytes( $total_saved ),
            'files'         => count( $results ),
        ] );
    }

    // -------------------------------------------------------------------------
    // Dashboard widget
    // -------------------------------------------------------------------------

    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'png_optimizer_widget',
            png_opt_t( 'widget_title' ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    public function render_dashboard_widget() {
        $stats = PNG_Optimizer_Core::get_stats_summary();
        $pct   = 0;
        if ( ! empty( $stats->total_original ) && $stats->total_original > 0 ) {
            $pct = round( ( $stats->total_saved / $stats->total_original ) * 100, 1 );
        }
        ?>
        <ul style="margin:0">
            <li><?php echo esc_html( png_opt_t( 'files_optimized_lbl' ) ); ?> <strong><?php echo esc_html( number_format( (int) ( $stats->total_files ?? 0 ) ) ); ?></strong></li>
            <li><?php echo esc_html( png_opt_t( 'total_saved_lbl' ) ); ?> <strong><?php echo esc_html( $this->format_bytes( (int) ( $stats->total_saved ?? 0 ) ) ); ?></strong></li>
            <li><?php echo esc_html( png_opt_t( 'avg_reduction_lbl' ) ); ?> <strong><?php echo esc_html( $pct ); ?>%</strong></li>
        </ul>
        <p><a href="<?php echo esc_url( admin_url( 'upload.php?page=png-optimizer' ) ); ?>"><?php echo esc_html( png_opt_t( 'manage' ) ); ?></a></p>
        <?php
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function format_bytes( $bytes ) {
        if ( $bytes >= 1048576 ) {
            return number_format( $bytes / 1048576, 2 ) . ' MB';
        }
        if ( $bytes >= 1024 ) {
            return number_format( $bytes / 1024, 2 ) . ' KB';
        }
        return $bytes . ' B';
    }
}
