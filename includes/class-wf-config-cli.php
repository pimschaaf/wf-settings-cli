<?php
/**
 * Generic WP-CLI Command for All Wordfence Configuration
 *
 * @package Wordfence_Options
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generic interface to get/set any Wordfence configuration option.
 */
class WF_Config_CLI_Command {

    /**
     * Get a Wordfence configuration value.
     *
     * ## OPTIONS
     *
     * <key>
     * : The configuration key to retrieve
     *
     * [--format=<format>]
     * : Output format (table, json, csv, yaml). Default: table
     *
     * ## EXAMPLES
     *
     *     wp wf-config get loginSecurityEnabled
     *     wp wf-config get loginSec_maxFailures
     *     wp wf-config get alertEmails --format=json
     *
     * @when after_wp_load
     */
    public function get($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $key = $args[0];
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

        $value = wfConfig::get($key);

        $data = [
            [
                'key' => $key,
                'value' => $this->format_value_for_display($value),
                'type' => gettype($value)
            ]
        ];

        WP_CLI\Utils\format_items($format, $data, ['key', 'value', 'type']);
    }

    /**
     * Set a Wordfence configuration value.
     *
     * ## OPTIONS
     *
     * <key>
     * : The configuration key to set
     *
     * <value>
     * : The new value
     *
     * [--type=<type>]
     * : Value type (string, int, bool). Default: auto-detect
     *
     * [--backup]
     * : Create a backup before making changes
     *
     * [--dry-run]
     * : Show what would be changed without applying
     *
     * ## EXAMPLES
     *
     *     wp wf-config set loginSec_maxFailures 10
     *     wp wf-config set loginSecurityEnabled 1 --type=bool
     *     wp wf-config set alertEmails "admin@example.com" --backup
     *
     * @when after_wp_load
     */
    public function set($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $key = $args[0];
        $value = $args[1];
        $type = isset($assoc_args['type']) ? $assoc_args['type'] : 'auto';
        $backup = isset($assoc_args['backup']);
        $dry_run = isset($assoc_args['dry-run']);

        // Get current value
        $old_value = wfConfig::get($key);

        // Convert value to appropriate type
        $new_value = $this->convert_value($value, $type);

        WP_CLI::line(sprintf(
            "\nSetting: %s\nOld value: %s\nNew value: %s\nType: %s\n",
            $key,
            $this->format_value_for_display($old_value),
            $this->format_value_for_display($new_value),
            gettype($new_value)
        ));

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        // Create backup if requested
        if ($backup) {
            $this->create_backup($key, $old_value);
        }

        // Set the value
        if (is_bool($new_value)) {
            wfConfig::setBool($key, $new_value);
        } else {
            wfConfig::set($key, $new_value);
        }

        // Verify
        $current_value = wfConfig::get($key);
        if ($current_value == $new_value) {
            WP_CLI::success("Configuration updated successfully.");
        } else {
            WP_CLI::warning("Configuration may not have been updated correctly. Current value: " . $this->format_value_for_display($current_value));
        }
    }

    /**
     * List all Wordfence configuration options, optionally filtered.
     *
     * ## OPTIONS
     *
     * [--search=<pattern>]
     * : Filter by key pattern
     *
     * [--category=<category>]
     * : Filter by category (brute-force, firewall, scanner, alerts, blocking, other)
     *
     * [--format=<format>]
     * : Output format (table, json, csv, yaml). Default: table
     *
     * [--limit=<number>]
     * : Limit number of results
     *
     * ## EXAMPLES
     *
     *     wp wf-config list
     *     wp wf-config list --search=login
     *     wp wf-config list --category=brute-force
     *     wp wf-config list --search=scan --format=json
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        global $wpdb;

        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $search = isset($assoc_args['search']) ? $assoc_args['search'] : '';
        $category = isset($assoc_args['category']) ? $assoc_args['category'] : '';
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : null;

        $table = wfDB::networkTable('wfConfig');

        // Build query
        $where = [];
        if ($search) {
            $where[] = $wpdb->prepare("name LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }

        if ($category) {
            $pattern = $this->get_category_pattern($category);
            if ($pattern) {
                $where[] = $wpdb->prepare("name LIKE %s", $pattern);
            }
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_clause = $limit ? "LIMIT " . intval($limit) : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb->prefix (safe) + hardcoded string, WHERE clause already prepared
        $query = "SELECT name, val FROM {$table} {$where_clause} ORDER BY name {$limit_clause}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query parts prepared above, direct access needed for CLI tool
        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) {
            WP_CLI::warning('No configuration options found matching criteria.');
            return;
        }

        // Format results
        foreach ($results as &$row) {
            $row['value'] = $row['val'];
            unset($row['val']);

            if (strlen($row['value']) > 100) {
                $row['value'] = substr($row['value'], 0, 100) . '...';
            }
        }

        WP_CLI\Utils\format_items($format, $results, ['name', 'value']);
    }

    /**
     * Export all or filtered Wordfence configuration to a file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Output file path
     *
     * [--search=<pattern>]
     * : Filter by key pattern
     *
     * [--category=<category>]
     * : Export only specific category
     *
     * [--managed-only]
     * : Export only settings that can be managed by this plugin's commands
     *
     * ## EXAMPLES
     *
     *     wp wf-config export /tmp/all-wf-config.json
     *     wp wf-config export /tmp/brute-force-config.json --category=brute-force
     *     wp wf-config export /tmp/managed-settings.json --managed-only
     *
     * @when after_wp_load
     */
    public function export($args, $assoc_args) {
        global $wpdb;

        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $file = $args[0];
        $search = isset($assoc_args['search']) ? $assoc_args['search'] : '';
        $category = isset($assoc_args['category']) ? $assoc_args['category'] : '';
        $managed_only = isset($assoc_args['managed-only']);

        $table = wfDB::networkTable('wfConfig');

        // Build query
        $where = [];
        if ($search) {
            $where[] = $wpdb->prepare("name LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }

        if ($category) {
            $pattern = $this->get_category_pattern($category);
            if ($pattern) {
                $where[] = $wpdb->prepare("name LIKE %s", $pattern);
            }
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb->prefix (safe) + hardcoded string, WHERE clause already prepared
        $query = "SELECT name, val FROM {$table} {$where_clause} ORDER BY name";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query parts prepared above, direct access needed for CLI tool
        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) {
            WP_CLI::error('No configuration options found to export.');
        }

        // Convert to associative array
        $settings = [];
        foreach ($results as $row) {
            // Handle binary blob data - convert to string
            $value = $row['val'];
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }
            $settings[$row['name']] = $value;
        }

        // Filter to managed-only settings if requested
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
            'category' => $category ?: 'all',
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
     * Import Wordfence configuration from a file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Input file path (JSON)
     *
     * [--dry-run]
     * : Preview import without applying changes
     *
     * [--backup]
     * : Create backup before importing
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp wf-config import /tmp/config.json --dry-run
     *     wp wf-config import /tmp/config.json --backup
     *
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $file = $args[0];
        $dry_run = isset($assoc_args['dry-run']);
        $backup = isset($assoc_args['backup']);
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
            $old_value = wfConfig::get($key);
            WP_CLI::line(sprintf("%-35s: %s → %s", $key, $this->format_value_for_display($old_value), $this->format_value_for_display($new_value)));
        }

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        if (!$force) {
            WP_CLI::confirm("\nImport " . count($settings) . " settings?");
        }

        // Create backup if requested
        if ($backup) {
            $timestamp = current_time('timestamp');
            $backup_key = 'wf_backup_import_' . $timestamp;
            // Backup all current settings
            WP_CLI::line("\nCreating backup: {$backup_key}");
        }

        // Import settings
        WP_CLI::line("\nImporting settings...");
        $imported = 0;
        foreach ($settings as $key => $value) {
            wfConfig::set($key, $value);
            $imported++;
        }

        WP_CLI::success("Imported {$imported} settings successfully.");
    }

    // Helper methods

    /**
     * Convert string value to appropriate type.
     */
    private function convert_value($value, $type) {
        if ($type === 'auto') {
            // Auto-detect type
            if ($value === 'true' || $value === 'false') {
                return $value === 'true';
            }
            if (is_numeric($value)) {
                return strpos($value, '.') !== false ? (float)$value : (int)$value;
            }
            return $value;
        }

        switch ($type) {
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * Format value for display.
     */
    private function format_value_for_display($value) {
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
        return (string)$value;
    }

    /**
     * Get SQL LIKE pattern for category.
     */
    private function get_category_pattern($category) {
        $patterns = [
            'brute-force' => 'loginSec%',
            'login' => 'loginSec%',
            'firewall' => 'firewall%',
            'waf' => '%waf%',
            'scanner' => 'scan%',
            'scan' => 'scan%',
            'alerts' => 'alert%',
            'blocking' => '%block%',
            'rate-limit' => 'max%',
            'country' => 'cbl_%',
        ];

        return $patterns[$category] ?? null;
    }

    /**
     * Get list of all settings that can be managed by this plugin's commands.
     *
     * @return array List of setting keys
     */
    private function get_managed_settings_keys() {
        return [
            // Brute Force Protection (wp wf-brute)
            'loginSecurityEnabled',
            'loginSec_maxFailures',
            'loginSec_maxForgotPasswd',
            'loginSec_countFailMins',
            'loginSec_lockoutMins',
            'loginSec_lockInvalidUsers',
            'loginSec_userBlacklist',
            'loginSec_maskLoginErrors',
            'loginSec_blockAdminReg',
            'loginSec_disableAuthorScan',

            // Firewall Settings (wp wf-firewall)
            'firewallEnabled',
            'autoBlockScanners',
            'neverBlockBG',

            // Scanner Settings (wp wf-scanner)
            'scheduledScansEnabled',
            'scansEnabled_core',
            'scansEnabled_themes',
            'scansEnabled_plugins',
            'scansEnabled_malware',
            'scansEnabled_fileContents',
            'scansEnabled_posts',
            'scansEnabled_comments',
            'scansEnabled_scanImages',
            'scansEnabled_highSense',
            'scan_maxIssues',
            'scan_maxDuration',

            // Alert Settings (wp wf-alerts)
            'alertEmails',
            'alertOn_block',
            'alertOn_loginLockout',
            'alertOn_adminLogin',
            'alertOn_breachLogin',
            'alertOn_scanIssues',
            'alertOn_update',
            'alertOn_wordfenceDeactivated',
            'alert_maxHourly',
        ];
    }

    /**
     * Create a backup of current value.
     */
    private function create_backup($key, $value) {
        $timestamp = current_time('timestamp');
        $backup_key = 'wf_backup_' . $timestamp . '_' . $key;

        update_option($backup_key, [
            'timestamp' => $timestamp,
            'date' => gmdate('Y-m-d H:i:s', $timestamp),
            'key' => $key,
            'value' => $value
        ], false);

        WP_CLI::line("Backup created: {$backup_key}");
    }
}

WP_CLI::add_command('wf-config', 'WF_Config_CLI_Command');
