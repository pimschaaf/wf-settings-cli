<?php
/**
 * WP-CLI Command for Wordfence Brute Force Protection
 *
 * @package Wordfence_Options
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Brute Force Protection settings via WP-CLI.
 */
class WF_Brute_Force_CLI_Command {

    /**
     * Backup Wordfence configuration to a timestamped option.
     *
     * ## EXAMPLES
     *
     *     wp wf-brute backup
     *
     * @when after_wp_load
     */
    public function backup($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $timestamp = current_time('timestamp');
        $backup_key = 'wf_backup_' . $timestamp;

        $settings = $this->get_all_settings();

        update_option($backup_key, [
            'timestamp' => $timestamp,
            'date' => gmdate('Y-m-d H:i:s', $timestamp),
            'settings' => $settings
        ], false);

        WP_CLI::success("Backup created: {$backup_key}");
        WP_CLI::line("Settings backed up:");
        foreach ($settings as $key => $value) {
            WP_CLI::line("  - {$key}: " . $this->format_value($value));
        }
    }

    /**
     * List all Wordfence brute force settings.
     *
     * ## EXAMPLES
     *
     *     wp wf-brute list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $settings = $this->get_all_settings();

        WP_CLI::line("\n=== Current Wordfence Brute Force Settings ===\n");

        foreach ($settings as $key => $value) {
            WP_CLI::line(sprintf("%-35s: %s", $key, $this->format_value($value)));
        }

        WP_CLI::line("");
    }

    /**
     * Configure Wordfence Brute Force Protection settings.
     *
     * ## OPTIONS
     *
     * [--enable]
     * : Enable brute force protection
     *
     * [--disable]
     * : Disable brute force protection
     *
     * [--max-login-failures=<number>]
     * : Maximum login failures before lockout (2-500)
     *
     * [--max-forgot-failures=<number>]
     * : Maximum forgot password failures before lockout (1-500)
     *
     * [--failure-window-mins=<minutes>]
     * : Time window to count failures in minutes (5-1440)
     *
     * [--lockout-duration-mins=<minutes>]
     * : Lockout duration in minutes (5-86400)
     *
     * [--block-invalid-usernames=<1|0>]
     * : Immediately block invalid usernames (1=yes, 0=no)
     *
     * [--dry-run]
     * : Show what would be changed without applying changes
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     # Enable protection with stricter settings
     *     wp wf-brute configure --enable --max-login-failures=5 --lockout-duration-mins=60
     *
     *     # Dry run to preview changes
     *     wp wf-brute configure --max-login-failures=10 --dry-run
     *
     *     # Disable brute force protection
     *     wp wf-brute configure --disable --force
     *
     * @when after_wp_load
     */
    public function configure($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        // Parse and validate inputs
        $changes = [];

        if (isset($assoc_args['enable'])) {
            $changes['loginSecurityEnabled'] = ['old' => wfConfig::get('loginSecurityEnabled'), 'new' => true];
        }

        if (isset($assoc_args['disable'])) {
            if (isset($assoc_args['enable'])) {
                WP_CLI::error('Cannot use both --enable and --disable');
            }
            $changes['loginSecurityEnabled'] = ['old' => wfConfig::get('loginSecurityEnabled'), 'new' => false];
        }

        if (isset($assoc_args['max-login-failures'])) {
            $value = intval($assoc_args['max-login-failures']);
            if ($value < 2 || $value > 500) {
                WP_CLI::error('max-login-failures must be between 2 and 500');
            }
            $changes['loginSec_maxFailures'] = ['old' => wfConfig::get('loginSec_maxFailures'), 'new' => $value];
        }

        if (isset($assoc_args['max-forgot-failures'])) {
            $value = intval($assoc_args['max-forgot-failures']);
            if ($value < 1 || $value > 500) {
                WP_CLI::error('max-forgot-failures must be between 1 and 500');
            }
            $changes['loginSec_maxForgotPasswd'] = ['old' => wfConfig::get('loginSec_maxForgotPasswd'), 'new' => $value];
        }

        if (isset($assoc_args['failure-window-mins'])) {
            $value = intval($assoc_args['failure-window-mins']);
            $allowed = [5, 10, 30, 60, 120, 240, 360, 720, 1440];
            if (!in_array($value, $allowed)) {
                WP_CLI::error('failure-window-mins must be one of: ' . implode(', ', $allowed));
            }
            $changes['loginSec_countFailMins'] = ['old' => wfConfig::getInt('loginSec_countFailMins'), 'new' => $value];
        }

        if (isset($assoc_args['lockout-duration-mins'])) {
            $value = intval($assoc_args['lockout-duration-mins']);
            $allowed = [5, 10, 30, 60, 120, 240, 360, 720, 1440, 2880, 7200, 14400, 28800, 43200, 86400];
            if (!in_array($value, $allowed)) {
                WP_CLI::error('lockout-duration-mins must be one of: ' . implode(', ', $allowed));
            }
            $changes['loginSec_lockoutMins'] = ['old' => wfConfig::getInt('loginSec_lockoutMins'), 'new' => $value];
        }

        if (isset($assoc_args['block-invalid-usernames'])) {
            $value = intval($assoc_args['block-invalid-usernames']);
            if ($value !== 0 && $value !== 1) {
                WP_CLI::error('block-invalid-usernames must be 0 or 1');
            }
            $changes['loginSec_lockInvalidUsers'] = ['old' => wfConfig::get('loginSec_lockInvalidUsers'), 'new' => (bool)$value];
        }

        if (empty($changes)) {
            WP_CLI::error('No changes specified. Use --help to see available options.');
        }

        // Display changes
        WP_CLI::line("\n=== Proposed Changes ===\n");
        foreach ($changes as $key => $values) {
            WP_CLI::line(sprintf(
                "%-35s: %s → %s",
                $key,
                $this->format_value($values['old']),
                $this->format_value($values['new'])
            ));
        }
        WP_CLI::line("");

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        // Confirm changes
        if (!$force) {
            WP_CLI::confirm('Apply these changes?', $assoc_args);
        }

        // Create automatic backup
        WP_CLI::line("Creating backup...");
        $this->backup([], []);

        // Apply changes
        WP_CLI::line("\nApplying changes...");
        foreach ($changes as $key => $values) {
            if (is_bool($values['new'])) {
                wfConfig::setBool($key, $values['new']);
            } else {
                wfConfig::set($key, $values['new']);
            }
            WP_CLI::line("  ✓ Updated {$key}");
        }

        // Verify changes
        WP_CLI::line("\n=== Verification ===\n");
        $all_success = true;
        foreach ($changes as $key => $values) {
            $current = wfConfig::get($key);
            $expected = $values['new'];

            // Type-safe comparison
            if (is_bool($expected)) {
                $current = (bool)$current;
            }

            if ($current == $expected) {
                WP_CLI::line("  ✓ {$key}: " . $this->format_value($current));
            } else {
                WP_CLI::warning("  ✗ {$key}: Expected " . $this->format_value($expected) . ", got " . $this->format_value($current));
                $all_success = false;
            }
        }

        WP_CLI::line("");
        if ($all_success) {
            WP_CLI::success('All settings updated and verified successfully!');
        } else {
            WP_CLI::error('Some settings failed verification. Check the output above.');
        }
    }

    /**
     * Restore Wordfence settings from a backup.
     *
     * ## OPTIONS
     *
     * <backup-key>
     * : The backup key (e.g., wf_backup_1234567890)
     *
     * [--dry-run]
     * : Show what would be restored without applying changes
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     # List available backups
     *     wp option list --search="wf_backup_*"
     *
     *     # Restore from backup
     *     wp wf-brute restore wf_backup_1234567890
     *
     * @when after_wp_load
     */
    public function restore($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        if (empty($args[0])) {
            WP_CLI::error('Please specify a backup key. List backups with: wp option list --search="wf_backup_*"');
        }

        $backup_key = $args[0];
        $backup = get_option($backup_key);

        if (!$backup || !isset($backup['settings'])) {
            WP_CLI::error("Backup not found: {$backup_key}");
        }

        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        WP_CLI::line("\n=== Backup Information ===");
        WP_CLI::line("Backup key: {$backup_key}");
        WP_CLI::line("Created: {$backup['date']}");
        WP_CLI::line("");

        WP_CLI::line("=== Settings to Restore ===\n");
        foreach ($backup['settings'] as $key => $value) {
            $current = wfConfig::get($key);
            WP_CLI::line(sprintf(
                "%-35s: %s → %s",
                $key,
                $this->format_value($current),
                $this->format_value($value)
            ));
        }
        WP_CLI::line("");

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        if (!$force) {
            WP_CLI::confirm('Restore these settings?', $assoc_args);
        }

        // Create backup of current state before restoring
        WP_CLI::line("Creating backup of current state...");
        $this->backup([], []);

        // Restore settings
        WP_CLI::line("\nRestoring settings...");
        foreach ($backup['settings'] as $key => $value) {
            if (is_bool($value)) {
                wfConfig::setBool($key, $value);
            } else {
                wfConfig::set($key, $value);
            }
            WP_CLI::line("  ✓ Restored {$key}");
        }

        WP_CLI::success('Settings restored successfully!');
    }

    /**
     * Export current Wordfence settings to a file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Output file path
     *
     * ## EXAMPLES
     *
     *     wp wf-brute export /tmp/wf-settings.json
     *
     * @when after_wp_load
     */
    public function export($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        if (empty($args[0])) {
            WP_CLI::error('Please specify an output file path');
        }

        $file = $args[0];
        $settings = $this->get_all_settings();

        $export = [
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => $settings
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT);

        if (file_put_contents($file, $json) === false) {
            WP_CLI::error("Failed to write to file: {$file}");
        }

        WP_CLI::success("Settings exported to: {$file}");
    }

    /**
     * Show all Wordfence configuration keys in the database.
     *
     * ## OPTIONS
     *
     * [--search=<pattern>]
     * : Filter keys by pattern (e.g., 'login', 'brute', 'lock')
     *
     * [--format=<format>]
     * : Output format (table, json, csv). Default: table
     *
     * ## EXAMPLES
     *
     *     # Show all config keys
     *     wp wf-brute discover
     *
     *     # Search for login-related keys
     *     wp wf-brute discover --search=login
     *
     * @when after_wp_load
     */
    public function discover($args, $assoc_args) {
        global $wpdb;

        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $search = isset($assoc_args['search']) ? $assoc_args['search'] : '';
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

        $table = wfDB::networkTable('wfConfig');

        if ($search) {
            $query = $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb->prefix (safe) + hardcoded string, cannot be prepared
                "SELECT name, val FROM {$table} WHERE name LIKE %s ORDER BY name",
                '%' . $wpdb->esc_like($search) . '%'
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from wpdb->prefix (safe) + hardcoded string, cannot be prepared
            $query = "SELECT name, val FROM {$table} ORDER BY name";
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic query with table name, CLI tool
        $results = $wpdb->get_results($query, ARRAY_A);

        if (empty($results)) {
            WP_CLI::warning('No configuration keys found.');
            return;
        }

        // Format values for display
        foreach ($results as &$row) {
            if (strlen($row['val']) > 100) {
                $row['val'] = substr($row['val'], 0, 100) . '...';
            }
        }

        WP_CLI\Utils\format_items($format, $results, ['name', 'val']);
    }

    // Helper methods

    /**
     * Get all brute force settings.
     *
     * @return array Settings array
     */
    private function get_all_settings() {
        return [
            'loginSecurityEnabled' => wfConfig::get('loginSecurityEnabled'),
            'loginSec_maxFailures' => wfConfig::get('loginSec_maxFailures'),
            'loginSec_maxForgotPasswd' => wfConfig::get('loginSec_maxForgotPasswd'),
            'loginSec_countFailMins' => wfConfig::getInt('loginSec_countFailMins'),
            'loginSec_lockoutMins' => wfConfig::getInt('loginSec_lockoutMins'),
            'loginSec_lockInvalidUsers' => wfConfig::get('loginSec_lockInvalidUsers'),
            'loginSec_userBlacklist' => wfConfig::get('loginSec_userBlacklist'),
            'loginSec_maskLoginErrors' => wfConfig::get('loginSec_maskLoginErrors'),
            'loginSec_blockAdminReg' => wfConfig::get('loginSec_blockAdminReg'),
            'loginSec_disableAuthorScan' => wfConfig::get('loginSec_disableAuthorScan'),
        ];
    }

    /**
     * Format a value for display.
     *
     * @param mixed $value Value to format
     * @return string Formatted value
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
        return (string)$value;
    }
}

WP_CLI::add_command('wf-brute', 'WF_Brute_Force_CLI_Command');
