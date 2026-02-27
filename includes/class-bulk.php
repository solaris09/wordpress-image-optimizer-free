<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles bulk optimization via AJAX (one attachment per request to avoid timeouts).
 */
class PNG_Optimizer_Bulk {

    public function __construct() {
        add_action( 'wp_ajax_png_opt_bulk_get_ids',  [ $this, 'ajax_get_ids' ] );
        add_action( 'wp_ajax_png_opt_bulk_optimize',  [ $this, 'ajax_optimize_one' ] );
    }

    /**
     * Return the list of all PNG attachment IDs.
     */
    public function ajax_get_ids() {
        check_ajax_referer( 'png_opt_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        $ids = PNG_Optimizer_Core::get_all_png_attachment_ids();
        wp_send_json_success( [ 'ids' => array_map( 'intval', $ids ) ] );
    }

    /**
     * Optimize a single attachment (called repeatedly by JS loop).
     */
    public function ajax_optimize_one() {
        check_ajax_referer( 'png_opt_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        $id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid ID.' ] );
        }

        // Extend time for large images
        if ( ! ini_get( 'safe_mode' ) ) {
            @set_time_limit( 120 );
        }

        $optimizer = new PNG_Optimizer_Core();
        $results   = $optimizer->optimize_attachment( $id );

        $saved = 0;
        if ( is_array( $results ) ) {
            $saved = array_sum( array_column( $results, 'saved_bytes' ) );
        }

        wp_send_json_success( [
            'attachment_id' => $id,
            'saved_bytes'   => $saved,
            'skipped'       => ! is_array( $results ) || count( $results ) === 0,
        ] );
    }
}
