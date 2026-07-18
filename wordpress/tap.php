<?php
/**
 * Plugin Name: TAP — Transparent Authorship Protocol
 * Plugin URI: https://emergenti.dev
 * Description: Adds transparent AI-human authorship attribution to WordPress posts using the TAP protocol.
 * Version: 0.1
 * Author: Aleksej & Liza Emergence
 * Author URI: https://emergenti.dev
 * License: CC BY 4.0
 * Text Domain: tap
 */

if (!defined('ABSPATH')) exit;

// === Shortcodes ===

function tap_ai_block($atts, $content = null) {
    $a = shortcode_atts(array(
        'name' => 'AI',
        'model' => '',
        'role' => 'drafting',
    ), $atts);
    
    $label = esc_html($a['name']);
    if ($a['model']) $label .= ' · ' . esc_html($a['model']);
    
    return '<div class="tap-block tap-block-ai" data-author-type="ai" data-role="' . esc_attr($a['role']) . '">'
         . '<div class="tap-label">🤖 ' . $label . '</div>'
         . '<div class="tap-content">' . do_shortcode($content) . '</div>'
         . '</div>';
}
add_shortcode('ai', 'tap_ai_block');

function tap_human_block($atts, $content = null) {
    $a = shortcode_atts(array(
        'name' => 'Human',
        'input' => 'keyboard',
        'role' => 'ideation',
        'edited' => 'false',
    ), $atts);
    
    $input_label = ($a['input'] === 'voice') ? ' · voice' : '';
    
    return '<div class="tap-block tap-block-human" data-author-type="human" data-role="' . esc_attr($a['role']) . '" data-input="' . esc_attr($a['input']) . '" data-edited="' . esc_attr($a['edited']) . '">'
         . '<div class="tap-label">👤 ' . esc_html($a['name']) . $input_label . '</div>'
         . '<div class="tap-content">' . do_shortcode($content) . '</div>'
         . '</div>';
}
add_shortcode('human', 'tap_human_block');

function tap_provenance_block($atts, $content = null) {
    $a = shortcode_atts(array(
        'authors' => '',
        'type' => 'collaborative',
        'method' => '',
    ), $atts);
    
    return '<div class="tap-provenance" data-source-type="' . esc_attr($a['type']) . '">'
         . '<div class="tap-provenance-title">Authorship</div>'
         . ($a['authors'] ? '<div class="tap-provenance-authors">' . esc_html($a['authors']) . '</div>' : '')
         . ($a['method'] ? '<div class="tap-provenance-method">' . esc_html($a['method']) . '</div>' : '')
         . ($content ? '<div class="tap-provenance-detail">' . do_shortcode($content) . '</div>' : '')
         . '</div>';
}
add_shortcode('provenance', 'tap_provenance_block');

// === Styles ===

function tap_enqueue_styles() {
    wp_enqueue_style('tap-style', plugin_dir_url(__FILE__) . 'tap.css', array(), '0.1');
}
add_action('wp_enqueue_scripts', 'tap_enqueue_styles');

// === Allow data attributes ===

function tap_allow_data_attributes($allowed, $context) {
    if ($context === 'post') {
        $attrs = array('data-author', 'data-author-type', 'data-role', 'data-input', 
                       'data-edited', 'data-model', 'data-original-lang', 
                       'data-translated-by', 'data-translation-type', 'data-source-type');
        foreach ($attrs as $attr) {
            $allowed['div'][$attr] = true;
        }
    }
    return $allowed;
}
add_filter('wp_kses_allowed_html', 'tap_allow_data_attributes', 10, 2);

// === Meta tag ===

function tap_add_meta_tag() {
    echo '<meta name="tap:version" content="0.2">' . "\n";
}
add_action('wp_head', 'tap_add_meta_tag');

// === TAP Source Viewer ===

/**
 * Get TAP source content for a post.
 *
 * @param int $post_id Post ID.
 * @return string HTML-escaped source wrapped in <pre><code>.
 */
function tap_get_source_content($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return '<p>Post not found.</p>';
    }
    $content = $post->post_content;
    // Escape HTML entities for safe display.
    $escaped = esc_html($content);
    return '<pre class="tap-source"><code>' . $escaped . '</code></pre>';
}

/**
 * Shortcode [tap_source] to display TAP source.
 *
 * Attributes:
 * - post_id (optional) Post ID; defaults to current post.
 */
function tap_source_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => 0,
    ), $atts);
    
    $post_id = $atts['post_id'];
    if (!$post_id) {
        global $post;
        if ($post) {
            $post_id = $post->ID;
        } else {
            return '<p>No post specified.</p>';
        }
    }
    
    // Security: only users with edit_posts capability can view source.
    if (!current_user_can('edit_posts')) {
        return '<p>Access denied.</p>';
    }
    
    return tap_get_source_content($post_id);
}
add_shortcode('tap_source', 'tap_source_shortcode');

/**
 * Add TAP Source button to admin bar.
 */
function tap_admin_bar_button($wp_admin_bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Only show on single post/page in frontend or admin edit screen.
    global $post;
    if (!$post) {
        return;
    }
    
    $args = array(
        'id'    => 'tap-source-view',
        'title' => '🔍 TAP Source',
        'href'  => '#tap-source-preview',
        'meta'  => array(
            'class' => 'tap-source-button',
            'onclick' => 'jQuery("#tap-source-preview").toggle(); return false;',
        ),
    );
    $wp_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'tap_admin_bar_button', 100);

/**
 * Add meta box for TAP source preview in post editor.
 */
function tap_source_meta_box() {
    add_meta_box(
        'tap_source_meta_box',
        'TAP Source Preview',
        'tap_source_meta_box_callback',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'tap_source_meta_box');

function tap_source_meta_box_callback($post) {
    if (!current_user_can('edit_posts')) {
        echo '<p>Access denied.</p>';
        return;
    }
    echo tap_get_source_content($post->ID);
    echo '<style>
        .tap-source {
            background: #f6f6f6;
            border: 1px solid #ddd;
            padding: 10px;
            overflow: auto;
            max-height: 300px;
            font-size: 12px;
            line-height: 1.4;
        }
    </style>';
}

// === OpenTimestamps Proof ===

/**
 * Generate OpenTimestamps proof for a post.
 *
 * @param int $post_id Post ID.
 * @return string|false Path to .ots file on success, false on failure.
 */
function tap_generate_ots($post_id) {
    // Get post content
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    $content = $post->post_content;
    // Strip shortcodes and tags for raw text? Use raw content as stored.
    // Generate SHA256 hash
    $hash = hash('sha256', $content);
    
    // Check if ots command is available
    $ots_path = shell_exec('which ots');
    if (empty($ots_path)) {
        // Log warning
        error_log('[TAP] OpenTimestamps CLI (ots) not found. Proof generation skipped.');
        return false;
    }
    
    // Ensure proof directory exists
    $upload_dir = wp_upload_dir();
    $proof_dir = $upload_dir['basedir'] . '/tap-proofs';
    if (!file_exists($proof_dir)) {
        wp_mkdir_p($proof_dir);
    }
    
    // Create temporary file with hash
    $temp_file = tempnam(sys_get_temp_dir(), 'tap_');
    file_put_contents($temp_file, hex2bin($hash));
    
    // Stamp via ots
    $ots_file = $proof_dir . '/proof-' . $post_id . '.ots';
    $cmd = sprintf('ots stamp %s -o %s 2>&1', escapeshellarg($temp_file), escapeshellarg($ots_file));
    $output = shell_exec($cmd);
    
    // Cleanup temp file
    unlink($temp_file);
    
    if (!file_exists($ots_file) || filesize($ots_file) === 0) {
        error_log('[TAP] OpenTimestamps proof generation failed: ' . $output);
        return false;
    }
    
    // Store proof file path in post meta for easy retrieval
    update_post_meta($post_id, '_tap_ots_proof', $ots_file);
    
    return $ots_file;
}

/**
 * Hook into save_post to auto-generate proof on publish/update.
 *
 * @param int $post_id Post ID.
 * @param WP_Post $post Post object.
 * @param bool $update Whether this is an update.
 */
function tap_generate_ots_on_save($post_id, $post, $update) {
    // Avoid autosave and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Check if post is published
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Optionally check if proof generation is enabled via option
    $enabled = get_option('tap_ots_enabled', '1');
    if ($enabled !== '1') {
        return;
    }
    
    tap_generate_ots($post_id);
}
add_action('save_post', 'tap_generate_ots_on_save', 10, 3);

/**
 * Shortcode [tap_proof] to display proof link.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML link or message.
 */
function tap_proof_shortcode($atts) {
    $a = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'text' => 'Download OpenTimestamps Proof',
    ), $atts);
    
    $post_id = intval($a['post_id']);
    $proof_file = get_post_meta($post_id, '_tap_ots_proof', true);
    
    if (!$proof_file || !file_exists($proof_file)) {
        return '<span class="tap-proof-missing">' . esc_html__('Proof not yet generated.', 'tap') . '</span>';
    }
    
    $url = wp_get_upload_dir()['baseurl'] . '/tap-proofs/' . basename($proof_file);
    
    return '<a class="tap-proof-link" href="' . esc_url($url) . '" download>' . esc_html($a['text']) . '</a>';
}
add_shortcode('tap_proof', 'tap_proof_shortcode');

/**
 * Check ots availability and display admin notice if missing.
 */
function tap_check_ots_availability() {
    $ots_path = shell_exec('which ots');
    if (empty($ots_path)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            _e('<strong>TAP Plugin:</strong> OpenTimestamps CLI (ots) not found. Proof generation will be disabled.', 'tap');
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'tap_check_ots_availability');

/**
 * Add admin option to enable/disable proof generation.
 */
function tap_ots_admin_menu() {
    add_options_page(
        'TAP OpenTimestamps Settings',
        'TAP Proof',
        'manage_options',
        'tap-ots-settings',
        'tap_ots_settings_page'
    );
}
add_action('admin_menu', 'tap_ots_admin_menu');

function tap_ots_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings
    if (isset($_POST['tap_ots_nonce']) && wp_verify_nonce($_POST['tap_ots_nonce'], 'tap_ots_settings')) {
        $enabled = isset($_POST['tap_ots_enabled']) ? '1' : '0';
        update_option('tap_ots_enabled', $enabled);
        echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'tap') . '</p></div>';
    }
    
    $enabled = get_option('tap_ots_enabled', '1');
    ?>
    <div class="wrap">
        <h1><?php _e('TAP OpenTimestamps Proof', 'tap'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('tap_ots_settings', 'tap_ots_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Proof Generation', 'tap'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="tap_ots_enabled" value="1" <?php checked($enabled, '1'); ?> />
                            <?php _e('Automatically generate OpenTimestamps proof on publish', 'tap'); ?>
                        </label>
                        <p class="description"><?php _e('Requires ots CLI tool installed on server.', 'tap'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// === Keybase Proofs & OpenTimestamps ===

/**
 * Generate OpenTimestamps proof (placeholder for future integration).
 *
 * @param int $post_id Post ID.
 * @return bool Always true for now.
 */
function tap_generate_opentimestamps( $post_id ) {
    // TODO: Implement OpenTimestamps integration.
    return true;
}

/**
 * Check if Keybase CLI is available.
 *
 * @return bool True if keybase command exists and is executable.
 */
function tap_keybase_available() {
    static $available = null;
    if ( $available === null ) {
        $available = (bool) function_exists( 'exec' ) && @exec( 'which keybase 2>/dev/null' );
    }
    return $available;
}

/**
 * Generate a Keybase proof file for a post.
 *
 * @param int $post_id Post ID.
 * @return string|false Path to the signed proof file, or false on failure.
 */
function tap_generate_keybase_proof( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_status !== 'publish' ) {
        return false;
    }

    // Ensure proof directory exists.
    $upload_dir = wp_upload_dir();
    $proof_dir  = trailingslashit( $upload_dir['basedir'] ) . 'tap-proofs/';
    if ( ! wp_mkdir_p( $proof_dir ) ) {
        error_log( 'TAP: Unable to create proof directory: ' . $proof_dir );
        return false;
    }

    // Prepare proof content.
    $permalink = get_permalink( $post_id );
    $content   = $post->post_title . "\n" . $post->post_content;
    $hash      = hash( 'sha256', $content );
    $timestamp = current_time( 'Y-m-d H:i:s' );

    $proof_text = "TAP proof for post ID: $post_id\n"
                . "URL: $permalink\n"
                . "Hash: $hash\n"
                . "Timestamp: $timestamp\n";

    // Save unsigned proof.
    $unsigned_file = $proof_dir . "proof-{$post_id}.txt";
    if ( file_put_contents( $unsigned_file, $proof_text ) === false ) {
        error_log( 'TAP: Failed to write unsigned proof file: ' . $unsigned_file );
        return false;
    }

    // Sign with Keybase if available.
    if ( tap_keybase_available() ) {
        $signed_file = $proof_dir . "proof-{$post_id}.signed.txt";
        $command = sprintf( 'keybase pgp sign --clearsign --infile %s --outfile %s 2>&1',
            escapeshellarg( $unsigned_file ),
            escapeshellarg( $signed_file )
        );
        exec( $command, $output, $return_var );
        if ( $return_var === 0 ) {
            return $signed_file;
        } else {
            error_log( 'TAP: Keybase signing failed: ' . implode( "\n", $output ) );
        }
    }

    // If signing not possible, return unsigned file.
    return $unsigned_file;
}

/**
 * Save post hook to generate Keybase proof.
 *
 * @param int $post_id Post ID.
 */
function tap_save_post_keybase_proof( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    $enabled = get_option( 'tap_keybase_enabled', false );
    if ( ! $enabled ) {
        return;
    }
    tap_generate_keybase_proof( $post_id );
}
add_action( 'save_post', 'tap_save_post_keybase_proof' );

/**
 * Shortcode to display Keybase proof link.
 *
 * Usage: [tap_keybase_proof]
 */
function tap_keybase_proof_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'post_id' => get_the_ID(),
    ), $atts );

    $post_id = intval( $a['post_id'] );
    if ( ! $post_id ) {
        return '';
    }

    $upload_dir = wp_upload_dir();
    $signed_file = trailingslashit( $upload_dir['basedir'] ) . "tap-proofs/proof-{$post_id}.signed.txt";
    $unsigned_file = trailingslashit( $upload_dir['basedir'] ) . "tap-proofs/proof-{$post_id}.txt";

    $file = file_exists( $signed_file ) ? $signed_file : ( file_exists( $unsigned_file ) ? $unsigned_file : null );
    if ( ! $file ) {
        return '<span class="tap-proof-missing">No proof available.</span>';
    }

    $url = trailingslashit( $upload_dir['baseurl'] ) . "tap-proofs/" . basename( $file );
    return sprintf( '<a href="%s" class="tap-proof-link">View Keybase proof</a>', esc_url( $url ) );
}
add_shortcode( 'tap_keybase_proof', 'tap_keybase_proof_shortcode' );

/**
 * Add admin option for Keybase proofs.
 */
function tap_add_admin_menu() {
    add_options_page(
        'TAP Settings',
        'TAP',
        'manage_options',
        'tap-settings',
        'tap_admin_page'
    );
}
add_action( 'admin_menu', 'tap_add_admin_menu' );

function tap_admin_page() {
    ?>
    <div class="wrap">
        <h1>TAP Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'tap_options' ); ?>
            <?php do_settings_sections( 'tap-settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Keybase Proofs</th>
                    <td>
                        <label>
                            <input type="checkbox" name="tap_keybase_enabled" value="1" <?php checked( get_option( 'tap_keybase_enabled' ), 1 ); ?> />
                            Enable Keybase proof generation on post save
                        </label>
                        <p class="description">
                            Requires Keybase CLI installed and logged in.
                            <?php if ( tap_keybase_available() ): ?>
                                <strong style="color: green;">Keybase detected.</strong>
                            <?php else: ?>
                                <strong style="color: red;">Keybase not found.</strong>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function tap_register_settings() {
    register_setting( 'tap_options', 'tap_keybase_enabled', array(
        'type' => 'boolean',
        'default' => false,
    ) );
}
add_action( 'admin_init', 'tap_register_settings' );
