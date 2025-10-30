<?php
/**
 * WP-CLI Command for Wordfence Login Security 2FA Management
 *
 * @package Wordfence_Settings_CLI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Login Security 2FA settings via WP-CLI.
 */
class WFLS_2FA_CLI_Command {

    /**
     * Configure 2FA requirements for roles.
     *
     * ## OPTIONS
     *
     * [--administrator=<state>]
     * : 2FA state for administrators (required, optional, disabled)
     *
     * [--editor=<state>]
     * : 2FA state for editors
     *
     * [--author=<state>]
     * : 2FA state for authors
     *
     * [--contributor=<state>]
     * : 2FA state for contributors
     *
     * [--subscriber=<state>]
     * : 2FA state for subscribers
     *
     * [--grace-period=<days>]
     * : Grace period in days (1-99)
     *
     * [--enable-grace-period]
     * : Enable grace period
     *
     * [--disable-grace-period]
     * : Disable grace period
     *
     * [--remember-device]
     * : Enable remember device feature
     *
     * [--remember-duration=<seconds>]
     * : Remember device duration in seconds
     *
     * [--dry-run]
     * : Show what would be changed without applying
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     # Require 2FA for all administrators
     *     wp wfls-2fa configure --administrator=required
     *
     *     # Require 2FA for admins and editors
     *     wp wfls-2fa configure --administrator=required --editor=required
     *
     *     # Set with grace period
     *     wp wfls-2fa configure --administrator=required --enable-grace-period --grace-period=7
     *
     *     # Make 2FA optional for all roles
     *     wp wfls-2fa configure --administrator=optional --editor=optional --author=optional
     *
     * @when after_wp_load
     */
    public function configure($args, $assoc_args) {
        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        $controller = \WordfenceLS\Controller_Settings::shared();
        $changes = [];

        // Role-based 2FA settings
        $roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        foreach ($roles as $role) {
            if (isset($assoc_args[$role])) {
                $state = $assoc_args[$role];
                if (!in_array($state, ['required', 'optional', 'disabled'])) {
                    WP_CLI::error("Invalid state for {$role}: {$state}. Use: required, optional, or disabled");
                }

                $key = 'require-2fa.' . $role;
                $old_value = $controller->get($key);
                $new_value = $state;

                $changes[$key] = [
                    'old' => $this->format_2fa_state($old_value),
                    'new' => $new_value
                ];
            }
        }

        // Grace period enabled
        if (isset($assoc_args['enable-grace-period'])) {
            $changes['require-2fa-grace-period-enabled'] = [
                'old' => $controller->get_bool('require-2fa-grace-period-enabled') ? 'true' : 'false',
                'new' => 'true'
            ];
        } else if (isset($assoc_args['disable-grace-period'])) {
            $changes['require-2fa-grace-period-enabled'] = [
                'old' => $controller->get_bool('require-2fa-grace-period-enabled') ? 'true' : 'false',
                'new' => 'false'
            ];
        }

        // Grace period days
        if (isset($assoc_args['grace-period'])) {
            $days = intval($assoc_args['grace-period']);
            if ($days < 1 || $days > 99) {
                WP_CLI::error('Grace period must be between 1 and 99 days');
            }

            $changes['2fa-user-grace-period'] = [
                'old' => $controller->get_int('2fa-user-grace-period'),
                'new' => $days
            ];
        }

        // Remember device
        if (isset($assoc_args['remember-device'])) {
            $changes['remember-device'] = [
                'old' => $controller->get_bool('remember-device') ? 'true' : 'false',
                'new' => 'true'
            ];
        }

        // Remember duration
        if (isset($assoc_args['remember-duration'])) {
            $duration = intval($assoc_args['remember-duration']);
            if ($duration < 1) {
                WP_CLI::error('Remember duration must be positive');
            }

            $changes['remember-device-duration'] = [
                'old' => $controller->get_int('remember-device-duration'),
                'new' => $duration
            ];
        }

        if (empty($changes)) {
            WP_CLI::error('No changes specified. Use --help to see available options.');
        }

        // Display changes
        WP_CLI::line("\n=== Proposed Changes ===\n");
        foreach ($changes as $key => $values) {
            WP_CLI::line(sprintf(
                "%-40s: %s → %s",
                $key,
                $values['old'],
                $values['new']
            ));
        }
        WP_CLI::line("");

        if ($dry_run) {
            WP_CLI::warning('DRY RUN - No changes applied');
            return;
        }

        if (!$force) {
            WP_CLI::confirm('Apply these changes?', $assoc_args);
        }

        // Apply changes
        WP_CLI::line("\nApplying changes...");
        foreach ($changes as $key => $values) {
            $result = $controller->set($key, $values['new']);
            if ($result !== false) {
                WP_CLI::line("  ✓ Updated {$key}");
            } else {
                WP_CLI::warning("  ✗ Failed to update {$key}");
            }
        }

        WP_CLI::success('Settings updated successfully!');
    }

    /**
     * List current 2FA status for all roles.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format (table, json, csv). Default: table
     *
     * ## EXAMPLES
     *
     *     wp wfls-2fa list
     *     wp wfls-2fa list --format=json
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('WordfenceLS\Controller_Settings')) {
            WP_CLI::error('Wordfence Login Security is not available.');
        }

        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        $controller = \WordfenceLS\Controller_Settings::shared();

        $roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        $results = [];

        foreach ($roles as $role) {
            $key = 'require-2fa.' . $role;
            $value = $controller->get($key);

            $results[] = [
                'role' => $role,
                'status' => $this->format_2fa_state($value)
            ];
        }

        WP_CLI::line("\n=== 2FA Role Requirements ===\n");
        WP_CLI\Utils\format_items($format, $results, ['role', 'status']);

        // Show grace period settings
        WP_CLI::line("\n=== Grace Period Settings ===\n");
        $grace_enabled = $controller->get_bool('require-2fa-grace-period-enabled');
        $grace_days = $controller->get_int('2fa-user-grace-period', 10);

        WP_CLI::line(sprintf("Grace Period Enabled: %s", $grace_enabled ? 'Yes' : 'No'));
        WP_CLI::line(sprintf("Grace Period Days: %d", $grace_days));

        // Show remember device settings
        WP_CLI::line("\n=== Remember Device Settings ===\n");
        $remember = $controller->get_bool('remember-device');
        $duration = $controller->get_int('remember-device-duration', 2592000);
        $duration_days = round($duration / 86400);

        WP_CLI::line(sprintf("Remember Device: %s", $remember ? 'Yes' : 'No'));
        WP_CLI::line(sprintf("Duration: %d days (%d seconds)", $duration_days, $duration));
        WP_CLI::line("");
    }

    /**
     * Require 2FA for all administrators (quick command).
     *
     * ## OPTIONS
     *
     * [--force]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp wfls-2fa require-admin
     *
     * @when after_wp_load
     */
    public function require_admin($args, $assoc_args) {
        $this->configure([], array_merge($assoc_args, ['administrator' => 'required']));
    }

    /**
     * Format 2FA state value for display.
     *
     * @param mixed $value
     * @return string
     */
    private function format_2fa_state($value) {
        if (empty($value) || $value === 'disabled' || $value === false || $value === '0') {
            return 'disabled';
        }
        if ($value === 'required' || $value === 'true' || $value === '1' || $value === true) {
            return 'required';
        }
        if ($value === 'optional') {
            return 'optional';
        }
        return (string)$value;
    }
}

WP_CLI::add_command('wfls-2fa', 'WFLS_2FA_CLI_Command');
