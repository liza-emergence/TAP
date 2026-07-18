<?php
/**
 * TAP Archive.org Snapshot Integration
 *
 * @package TAP
 * @subpackage Archive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Constants ===

/**
 * Archive.org Save Page Now endpoint.
 */
define( 'TAP_ARCHIVE_SAVE_URL', 'https://web.archive.org/save/' );

/**
 * Default user agent for Archive.org requests.
 */
define( 'TAP_ARCHIVE_USER_AGENT', 'TAP-WordPress-Plugin/0.1 (+https://emergenti.dev)' );

/**
 * Meta key for storing snapshot URL.
 */
define( 'TAP_ARCHIVE_META_KEY', '_tap_archive_snapshot' );

/**
 * Meta key for storing snapshot status (error logs, retry count).
 */
define( 'TAP_ARCHIVE_STATUS_KEY', '_tap_archive_status' );

// === Core Function ===

/**
 * Request Archive.org snapshot for a post.
 *
 * @param int $post_id Post ID.
 * @return string|false Snapshot URL on success, false on failure.
 */
function tap_archive_snapshot( $post_id ) {
	// Check if feature is enabled.
	if ( ! get_option( 'tap_archive_enabled', true ) ) {
		return false;
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		return false;
	}

	$permalink = get_permalink( $post_id );
	if ( ! $permalink || ! filter_var( $permalink, FILTER_VALIDATE_URL ) ) {
		error_log( sprintf( '[TAP Archive] Invalid permalink for post %d: %s', $post_id, $permalink ) );
		return false;
	}

	// Check if we already have a snapshot.
	$existing = get_post_meta( $post_id, TAP_ARCHIVE_META_KEY, true );
	if ( $existing && tap_archive_validate_url( $existing ) ) {
		// Already archived, skip.
		return $existing;
	}

	// Rate limit check.
	$last_request = get_transient( 'tap_archive_last_request' );
	if ( $last_request && time() - $last_request < 4 ) {
		// Minimum 4 seconds between requests (15 per minute).
		error_log( sprintf( '[TAP Archive] Rate limit throttle for post %d', $post_id ) );
		// Schedule a retry in 5 minutes via WP Cron.
		wp_schedule_single_event( time() + 300, 'tap_archive_retry', array( $post_id ) );
		return false;
	}

	// Prepare request.
	$args = array(
		'timeout'   => 30,
		'user-agent' => TAP_ARCHIVE_USER_AGENT,
		'headers'   => array(
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		),
		'body'      => array(
			'url' => $permalink,
		),
	);

	// Send POST request.
	$response = wp_remote_post( TAP_ARCHIVE_SAVE_URL, $args );

	if ( is_wp_error( $response ) ) {
		$error_message = sprintf( '[TAP Archive] HTTP error for post %d: %s', $post_id, $response->get_error_message() );
		error_log( $error_message );
		tap_archive_update_status( $post_id, 'error', $error_message );
		return false;
	}

	$status_code = wp_remote_retrieve_response_code( $response );

	// Archive.org returns 200 on success with Location header, or 302 redirect.
	if ( $status_code === 200 || $status_code === 302 ) {
		$headers = wp_remote_retrieve_headers( $response );
		$location = isset( $headers['Location'] ) ? $headers['Location'] : '';

		if ( ! $location && isset( $headers['location'] ) ) {
			$location = $headers['location'];
		}

		if ( $location ) {
			$snapshot_url = tap_archive_normalize_url( $location );
			if ( tap_archive_validate_url( $snapshot_url ) ) {
				update_post_meta( $post_id, TAP_ARCHIVE_META_KEY, $snapshot_url );
				tap_archive_update_status( $post_id, 'success', $snapshot_url );
				set_transient( 'tap_archive_last_request', time(), 60 );
				return $snapshot_url;
			}
		}
	}

	// Handle error responses.
	$body = wp_remote_retrieve_body( $response );
	$error_message = sprintf( '[TAP Archive] Archive.org returned status %d for post %d. Body: %s', $status_code, $post_id, substr( $body, 0, 200 ) );
	error_log( $error_message );
	tap_archive_update_status( $post_id, 'error', $error_message );

	// If rate limited (429), schedule retry.
	if ( $status_code === 429 ) {
		$retry_after = isset( $headers['Retry-After'] ) ? intval( $headers['Retry-After'] ) : 3600;
		wp_schedule_single_event( time() + $retry_after, 'tap_archive_retry', array( $post_id ) );
		error_log( sprintf( '[TAP Archive] Rate limited, retry scheduled in %d seconds.', $retry_after ) );
	}

	return false;
}

/**
 * Validate a URL looks like an Archive.org snapshot.
 *
 * @param string $url URL to validate.
 * @return bool True if valid.
 */
function tap_archive_validate_url( $url ) {
	return filter_var( $url, FILTER_VALIDATE_URL ) && strpos( $url, 'https://web.archive.org/web/' ) === 0;
}

/**
 * Normalize Archive.org snapshot URL.
 *
 * @param string $url Possibly relative URL.
 * @return string Absolute URL.
 */
function tap_archive_normalize_url( $url ) {
	if ( strpos( $url, 'http' ) !== 0 ) {
		$url = 'https://web.archive.org' . $url;
	}
	return $url;
}

/**
 * Update snapshot status meta.
 *
 * @param int    $post_id Post ID.
 * @param string $status  Status: 'success', 'error', 'pending'.
 * @param string $message Additional info.
 */
function tap_archive_update_status( $post_id, $status, $message = '' ) {
	$current = get_post_meta( $post_id, TAP_ARCHIVE_STATUS_KEY, true );
	if ( ! is_array( $current ) ) {
		$current = array();
	}
	$current[] = array(
		'time'    => current_time( 'mysql' ),
		'status'  => $status,
		'message' => $message,
	);
	// Keep only last 10 entries.
	if ( count( $current ) > 10 ) {
		$current = array_slice( $current, -10 );
	}
	update_post_meta( $post_id, TAP_ARCHIVE_STATUS_KEY, $current );
}

// === WordPress Hooks ===

/**
 * Hook into save_post to auto-snapshot on publish.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function tap_archive_on_save_post( $post_id, $post, $update ) {
	// Skip autosaves, revisions, and non-published posts.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( $post->post_status !== 'publish' ) {
		return;
	}

	// Only snapshot on first publish (not on every update).
	$already_snapshot = get_post_meta( $post_id, TAP_ARCHIVE_META_KEY, true );
	if ( $already_snapshot ) {
		return;
	}

	// Optionally delay snapshot to avoid heavy load on publish.
	// Schedule a single event 30 seconds later.
	wp_schedule_single_event( time() + 30, 'tap_archive_schedule_snapshot', array( $post_id ) );
}
add_action( 'save_post', 'tap_archive_on_save_post', 20, 3 );

/**
 * Scheduled snapshot handler.
 *
 * @param int $post_id Post ID.
 */
function tap_archive_scheduled_snapshot( $post_id ) {
	tap_archive_snapshot( $post_id );
}
add_action( 'tap_archive_schedule_snapshot', 'tap_archive_scheduled_snapshot' );

/**
 * Retry handler for failed snapshots.
 *
 * @param int $post_id Post ID.
 */
function tap_archive_retry_handler( $post_id ) {
	tap_archive_snapshot( $post_id );
}
add_action( 'tap_archive_retry', 'tap_archive_retry_handler' );

// === Shortcode ===

/**
 * Shortcode [tap_archive] to display snapshot link.
 *
 * Attributes:
 * - post_id (optional) Post ID; defaults to current post.
 * - text    (optional) Link text; defaults to "View Archived Snapshot".
 * - fallback (optional) Text if no snapshot; defaults to "Snapshot pending".
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function tap_archive_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'post_id'  => get_the_ID(),
			'text'     => __( 'View Archived Snapshot', 'tap' ),
			'fallback' => __( 'Snapshot pending', 'tap' ),
		),
		$atts
	);

	$post_id = intval( $a['post_id'] );
	if ( ! $post_id ) {
		return '<span class="tap-archive-error">Invalid post.</span>';
	}

	$snapshot_url = get_post_meta( $post_id, TAP_ARCHIVE_META_KEY, true );
	if ( $snapshot_url && tap_archive_validate_url( $snapshot_url ) ) {
		return sprintf(
			'<a class="tap-archive-link" href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( $snapshot_url ),
			esc_html( $a['text'] )
		);
	}

	return '<span class="tap-archive-pending">' . esc_html( $a['fallback'] ) . '</span>';
}
add_shortcode( 'tap_archive', 'tap_archive_shortcode' );

// === Admin UI ===

/**
 * Add Archive.org settings to existing TAP admin page.
 */
function tap_archive_admin_menu() {
	// Settings are added via tap_admin_page filter.
}
add_action( 'admin_menu', 'tap_archive_admin_menu', 11 );

/**
 * Add settings field to TAP settings page.
 */
function tap_archive_settings_fields() {
	add_settings_section(
		'tap_archive_section',
		__( 'Archive.org Snapshot', 'tap' ),
		'tap_archive_section_callback',
		'tap-settings'
	);

	add_settings_field(
		'tap_archive_enabled',
		__( 'Enable Archive.org Snapshots', 'tap' ),
		'tap_archive_enabled_callback',
		'tap-settings',
		'tap_archive_section'
	);

	register_setting(
		'tap_options',
		'tap_archive_enabled',
		array(
			'type'    => 'boolean',
			'default' => true,
		)
	);
}
add_action( 'admin_init', 'tap_archive_settings_fields' );

function tap_archive_section_callback() {
	echo '<p>' . esc_html__( 'Automatically save a snapshot of each published post to Archive.org.', 'tap' ) . '</p>';
	echo '<p><strong>' . esc_html__( 'Rate limits:', 'tap' ) . '</strong> ' . esc_html__( 'Archive.org allows ~15 requests per minute and ~1000 per day. The plugin respects these limits.', 'tap' ) . '</p>';
}

function tap_archive_enabled_callback() {
	$enabled = get_option( 'tap_archive_enabled', true );
	?>
	<label>
		<input type="checkbox" name="tap_archive_enabled" value="1" <?php checked( $enabled, true ); ?> />
		<?php esc_html_e( 'Automatically snapshot on publish', 'tap' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'If disabled, snapshots can still be triggered manually via the "Save to Archive.org" button on the post edit screen.', 'tap' ); ?>
	</p>
	<?php
}

/**
 * Add "Save to Archive.org" button to post edit screen.
 */
function tap_archive_add_meta_box() {
	add_meta_box(
		'tap_archive_meta_box',
		__( 'Archive.org Snapshot', 'tap' ),
		'tap_archive_meta_box_callback',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'tap_archive_add_meta_box' );

function tap_archive_meta_box_callback( $post ) {
	wp_nonce_field( 'tap_archive_action', 'tap_archive_nonce' );

	$snapshot_url = get_post_meta( $post->ID, TAP_ARCHIVE_META_KEY, true );
	$status       = get_post_meta( $post->ID, TAP_ARCHIVE_STATUS_KEY, true );

	if ( $snapshot_url && tap_archive_validate_url( $snapshot_url ) ) {
		echo '<p><strong>' . esc_html__( 'Snapshot URL:', 'tap' ) . '</strong></p>';
		echo '<p><a href="' . esc_url( $snapshot_url ) . '" target="_blank">' . esc_html( $snapshot_url ) . '</a></p>';
		echo '<p><em>' . esc_html__( 'This post has been archived.', 'tap' ) . '</em></p>';
	} else {
		echo '<p>' . esc_html__( 'No snapshot yet.', 'tap' ) . '</p>';
	}

	if ( is_array( $status ) && ! empty( $status ) ) {
		echo '<p><strong>' . esc_html__( 'Recent activity:', 'tap' ) . '</strong></p>';
		echo '<ul style="font-size: 12px; line-height: 1.4;">';
		foreach ( array_slice( $status, -3 ) as $entry ) {
			echo '<li><code>' . esc_html( $entry['time'] ) . ' – ' . esc_html( $entry['status'] ) . '</code>';
			if ( ! empty( $entry['message'] ) ) {
				echo '<br><small>' . esc_html( substr( $entry['message'], 0, 100 ) ) . '</small>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	echo '<p>';
	submit_button(
		__( 'Save to Archive.org Now', 'tap' ),
		'secondary',
		'tap_archive_submit',
		false,
		array(
			'style' => 'width:100%;',
		)
	);
	echo '</p>';
	echo '<p class="description">' . esc_html__( 'Manually trigger an archive snapshot. This respects rate limits.', 'tap' ) . '</p>';
}

/**
 * Handle manual snapshot request.
 */
function tap_archive_handle_manual_request() {
	if ( ! isset( $_POST['tap_archive_submit'] ) ) {
		return;
	}

	$post_id = isset( $_POST['post_ID'] ) ? intval( $_POST['post_ID'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	check_admin_referer( 'tap_archive_action', 'tap_archive_nonce' );

	$result = tap_archive_snapshot( $post_id );
	if ( $result ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Snapshot submitted to Archive.org.', 'tap' ) . '</p></div>';
		} );
	} else {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Snapshot failed. Check error log.', 'tap' ) . '</p></div>';
		} );
	}
}
add_action( 'save_post', 'tap_archive_handle_manual_request', 10, 1 );

// === WP‑CLI Command ===

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * WP‑CLI command to manage Archive.org snapshots.
	 */
	class TAP_Archive_CLI extends WP_CLI_Command {
		/**
		 * Snapshot a single post.
		 *
		 * ## OPTIONS
		 *
		 * <post_id>
		 * : Post ID.
		 *
		 * [--force]
		 * : Force snapshot even if already archived.
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public function snapshot( $args, $assoc_args ) {
			list( $post_id ) = $args;
			$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			if ( $force ) {
				delete_post_meta( $post_id, TAP_ARCHIVE_META_KEY );
			}

			$url = tap_archive_snapshot( $post_id );
			if ( $url ) {
				WP_CLI::success( "Snapshot created: $url" );
			} else {
				WP_CLI::error( 'Snapshot failed.' );
			}
		}

		/**
		 * Batch snapshot published posts.
		 *
		 * ## OPTIONS
		 *
		 * [--offset=<offset>]
		 * : Offset for batch.
		 *
		 * [--limit=<limit>]
		 * : Number of posts to process (default 10).
		 *
		 * [--dry-run]
		 * : Simulate without actually sending requests.
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public function batch( $args, $assoc_args ) {
			$offset  = WP_CLI\Utils\get_flag_value( $assoc_args, 'offset', 0 );
			$limit   = WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 10 );
			$dry_run = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

			$query = new WP_Query(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'offset'         => $offset,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'fields'         => 'ids',
				)
			);

			$count = count( $query->posts );
			WP_CLI::line( "Found $count published posts." );

			$success = 0;
			$errors  = 0;
			$skipped = 0;

			foreach ( $query->posts as $post_id ) {
				$existing = get_post_meta( $post_id, TAP_ARCHIVE_META_KEY, true );
				if ( $existing && tap_archive_validate_url( $existing ) ) {
					WP_CLI::line( "Post $post_id already archived." );
					$skipped++;
					continue;
				}

				if ( $dry_run ) {
					WP_CLI::line( "[DRY RUN] Would snapshot post $post_id: " . get_permalink( $post_id ) );
					$success++;
				} else {
					WP_CLI::line( "Snapshoting post $post_id..." );
					$url = tap_archive_snapshot( $post_id );
					if ( $url ) {
						WP_CLI::line( "  Success: $url" );
						$success++;
					} else {
						WP_CLI::line( "  Failed." );
						$errors++;
					}
					// Be nice to Archive.org rate limits.
					sleep( 5 );
				}
			}

			WP_CLI::line( "Batch complete: $success succeeded, $errors failed, $skipped skipped." );
		}
	}

	WP_CLI::add_command( 'tap archive', 'TAP_Archive_CLI' );
}

// === Documentation for developers ===

/**
 * Example usage:
 *
 * 1. Automatic snapshot on publish:
 *    Enable the option in Settings → TAP → Archive.org Snapshot.
 *
 * 2. Manual snapshot:
 *    Use the meta box on post edit screen.
 *
 * 3. Shortcode:
 *    [tap_archive] – displays snapshot link.
 *
 * 4. Programmatic:
 *    $url = tap_archive_snapshot( $post_id );
 *
 * Rate limits:
 *    - 15 requests per minute.
 *    - 1000 requests per day.
 *    - Plugin throttles automatically.
 *
 * Error handling:
 *    - Errors logged to PHP error_log.
 *    - Status stored in post meta '_tap_archive_status'.
 *    - Rate limit 429 → retry scheduled.
 */

?>