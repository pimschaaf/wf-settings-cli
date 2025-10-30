<?php
/**
 * WP-CLI Command for Wordfence Login Security Configuration
 *
 * @package Wordfence_Settings_CLI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Login Security settings via WP-CLI.
 */
class WFLS_Config_CLI_Command {

    /**
     * Get a Login Security setting value.
     *
     * ## OPTIONS
     *
     * <key>
     * : Setting key name (e.g., 'require-2fa.administrator', 'enable-auth-captcha')
     *
     * ## EXAMPLES
     *
     *     wp wfls-config get require-2fa.administrator
     *     wp wfls-config get enable-auth-captcha
     *
     * @when after_wp_load
     */
    public function get($args, $assoc_args) {
        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $key = $args[0];
        $settings = \WordfenceLS\Controller_Settings::shared();
        $value = $settings->get($key);

        if ($value === false || $value === null) {
            WP_CLI::warning("Setting '{$key}' not found or empty.");
            return;
        }

        WP_CLI::line($this->format_value($value));
    }

    /**
     * Set a Login Security setting value.
     *
     * ## OPTIONS
     *
     * <key>
     * : Setting key name
     *
     * <value>
     * : New value
     *
     * ## EXAMPLES
     *
     *     wp wfls-config set require-2fa.administrator 1
     *     wp wfls-config set enable-auth-captcha 1
     *     wp wfls-config set recaptcha-threshold 0.5
     *
     * @when after_wp_load
     */
    public function set($args, $assoc_args) {
        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $key = $args[0];
        $value = $args[1];

        $settings = \WordfenceLS\Controller_Settings::shared();

        // Get old value for display
        $old_value = $settings->get($key);

        // Set new value
        $result = $settings->set($key, $value);

        if ($result === false) {
            WP_CLI::error("Failed to set '{$key}'. Value may be invalid.");
        }

        WP_CLI::success("Updated {$key}: " . $this->format_value($old_value) . " → " . $this->format_value($value));
    }

    /**
     * List all Login Security settings.
     *
     * ## OPTIONS
     *
     * [--search=<pattern>]
     * : Filter by key pattern
     *
     * [--format=<format>]
     * : Output format (table, json, csv). Default: table
     *
     * ## EXAMPLES
     *
     *     wp wfls-config list
     *     wp wfls-config list --search=2fa
     *     wp wfls-config list --format=json
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        global $wpdb;

        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $search = isset($assoc_args['search']) ? $assoc_args['search'] : '';
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

        $table = $wpdb->prefix . 'wfls_settings';

        if ($search) {
            $query = $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT name, value FROM {$table} WHERE name LIKE %s ORDER BY name",
                '%' . $wpdb->esc_like($search) . '%'
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query = "SELECT name, value FROM {$table} ORDER BY name";
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) {
            WP_CLI::warning('No settings found.');
            return;
        }

        // Format values for display
        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'name' => $row['name'],
                'value' => $this->format_value($row['value'])
            ];
        }

        WP_CLI\Utils\format_items($format, $formatted, ['name', 'value']);
    }

    /**
     * Export Login Security settings to a JSON file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Output file path
     *
     * [--managed-only]
     * : Export only manageable settings
     *
     * ## EXAMPLES
     *
     *     wp wfls-config export /tmp/wfls-config.json
     *     wp wfls-config export /tmp/wfls-managed.json --managed-only
     *
     * @when after_wp_load
     */
    public function export($args, $assoc_args) {
        global $wpdb;

        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $file = $args[0];
        $managed_only = isset($assoc_args['managed-only']);

        $table = $wpdb->prefix . 'wfls_settings';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results("SELECT name, value FROM {$table} ORDER BY name", ARRAY_A);

        if (empty($results)) {
            WP_CLI::error('No settings found to export.');
        }

        // Convert to associative array
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['name']] = $row['value'];
        }

        // Filter to managed-only if requested
        if ($managed_only) {
            $managed_keys = $this->get_managed_settings_keys();
            $settings = array_intersect_key($settings, array_flip($managed_keys));

            if (empty($settings)) {
                WP_CLI::error('No managed settings found to export.');
            }
        }

        $export = [
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'managed_only' => $managed_only,
            'count' => count($settings),
            'settings' => $settings
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($json === false) {
            WP_CLI::error("Failed to encode settings to JSON: " . json_last_error_msg());
        }

        $bytes_written = file_put_contents($file, $json);

        if ($bytes_written === false) {
            WP_CLI::error("Failed to write to file: {$file}");
        }

        if ($bytes_written === 0) {
            WP_CLI::error("File created but no data written to: {$file}");
        }

        WP_CLI::success(sprintf("Exported %d settings (%d bytes) to: %s", count($settings), $bytes_written, $file));
    }

    /**
     * Import Login Security settings from a JSON file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Input file path (JSON)
     *
     * [--dry-run]
     * : Preview import without applying changes
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp wfls-config import /tmp/wfls-config.json --dry-run
     *     wp wfls-config import /tmp/wfls-config.json --force
     *
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        if (!file_exists($file)) {
            WP_CLI::error("File not found: {$file}");
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error("Invalid JSON in file: " . json_last_error_msg());
        }

        if (!isset($data['settings']) || !is_array($data['settings'])) {
            WP_CLI::error("Invalid export file format. Missing 'settings' key.");
        }

        $settings = $data['settings'];
        $controller = \WordfenceLS\Controller_Settings::shared();

        WP_CLI::line("\n=== Import Preview ===");
        WP_CLI::line("File: {$file}");
        WP_CLI::line("Exported: " . ($data['exported_at'] ?? 'unknown'));
        WP_CLI::line("Settings count: " . count($settings));
        WP_CLI::line("");

        // Show first 10 changes
        $count = 0;
        foreach ($settings as $key => $new_value) {
            if ($count++ >= 10) {
                WP_CLI::line("... and " . (count($settings) - 10) . " more");
                break;
            }

            $current = $controller->get($key);
            WP_CLI::line(sprintf(
                "%-40s: %s → %s",
                $key,
                $this->format_value($current),
                $this->format_value($new_value)
            ));
        }

        WP_CLI::line("");

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        if (!$force) {
            WP_CLI::confirm('Apply these settings?', $assoc_args);
        }

        // Apply settings
        WP_CLI::line("\nImporting settings...");
        $success_count = 0;
        $error_count = 0;

        foreach ($settings as $key => $value) {
            $result = $controller->set($key, $value);
            if ($result !== false) {
                $success_count++;
            } else {
                $error_count++;
                WP_CLI::warning("  Failed to import: {$key}");
            }
        }

        WP_CLI::line("");
        if ($error_count > 0) {
            WP_CLI::warning(sprintf("Imported %d settings with %d errors", $success_count, $error_count));
        } else {
            WP_CLI::success(sprintf("Imported %d settings successfully!", $success_count));
        }
    }

    /**
     * Get list of manageable Login Security settings.
     *
     * @return array
     */
    private function get_managed_settings_keys() {
        return [
            // 2FA Settings
            'require-2fa.administrator',
            'require-2fa.editor',
            'require-2fa.author',
            'require-2fa.contributor',
            'require-2fa.subscriber',
            'require-2fa-grace-period-enabled',
            '2fa-user-grace-period',
            'remember-device',
            'remember-device-duration',
            'whitelisted',

            // CAPTCHA Settings
            'enable-auth-captcha',
            'recaptcha-threshold',

            // XML-RPC
            'xmlrpc-enabled',
            'allow-xml-rpc',

            // Integration
            'enable-woocommerce-integration',
            'enable-woocommerce-account-integration',
            'enable-shortcode',

            // UI
            'enable-login-history-columns',
            'stack-ui-columns',

            // Cleanup
            'delete-deactivation',
        ];
    }

    /**
     * Format a value for display.
     *
     * @param mixed $value
     * @return string
     */
    private function format_value($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if ($value === '') {
            return '(empty)';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (strlen($value) > 100) {
            return substr($value, 0, 100) . '...';
        }
        return (string)$value;
    }
}

WP_CLI::add_command('wfls-config', 'WFLS_Config_CLI_Command');
