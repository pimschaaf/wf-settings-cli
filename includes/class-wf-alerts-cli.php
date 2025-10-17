<?php
/**
 * WP-CLI Command for Wordfence Alerts Configuration
 *
 * @package Wordfence_Options
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Alert settings via WP-CLI.
 */
class WF_Alerts_CLI_Command {

    /**
     * List current alert settings.
     *
     * ## EXAMPLES
     *
     *     wp wf-alerts list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $settings = $this->get_all_settings();

        WP_CLI::line("\n=== Current Wordfence Alert Settings ===\n");

        foreach ($settings as $key => $value) {
            WP_CLI::line(sprintf("%-40s: %s", $key, $this->format_value($value)));
        }

        WP_CLI::line("");
    }

    /**
     * Configure alert settings.
     *
     * ## OPTIONS
     *
     * [--email=<email>]
     * : Set alert email address(es) (comma-separated)
     *
     * [--alert-on-block=<1|0>]
     * : Alert when an IP is blocked
     *
     * [--alert-on-lockout=<1|0>]
     * : Alert on login lockouts
     *
     * [--alert-on-admin-login=<1|0>]
     * : Alert on administrator logins
     *
     * [--alert-on-breach-login=<1|0>]
     * : Alert on compromised password login attempts
     *
     * [--alert-on-scan-issues=<1|0>]
     * : Alert when scan finds issues
     *
     * [--dry-run]
     * : Preview changes
     *
     * [--force]
     * : Skip confirmation
     *
     * ## EXAMPLES
     *
     *     wp wf-alerts configure --email="admin@example.com,security@example.com"
     *     wp wf-alerts configure --alert-on-block=1 --alert-on-lockout=1
     *
     * @when after_wp_load
     */
    public function configure($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $dry_run = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);
        $changes = [];

        if (isset($assoc_args['email'])) {
            $changes['alertEmails'] = ['old' => wfConfig::get('alertEmails'), 'new' => $assoc_args['email']];
        }

        $bool_options = [
            'alert-on-block' => 'alertOn_block',
            'alert-on-lockout' => 'alertOn_loginLockout',
            'alert-on-admin-login' => 'alertOn_adminLogin',
            'alert-on-breach-login' => 'alertOn_breachLogin',
            'alert-on-scan-issues' => 'alertOn_scanIssues',
        ];

        foreach ($bool_options as $arg_name => $config_key) {
            if (isset($assoc_args[$arg_name])) {
                $value = intval($assoc_args[$arg_name]);
                if ($value !== 0 && $value !== 1) {
                    WP_CLI::error("{$arg_name} must be 0 or 1");
                }
                $changes[$config_key] = ['old' => wfConfig::get($config_key), 'new' => (bool)$value];
            }
        }

        if (empty($changes)) {
            WP_CLI::error('No changes specified. Use --help to see available options.');
        }

        // Display changes
        WP_CLI::line("\n=== Proposed Changes ===\n");
        foreach ($changes as $key => $values) {
            WP_CLI::line(sprintf("%-40s: %s → %s", $key, $this->format_value($values['old']), $this->format_value($values['new'])));
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
        foreach ($changes as $key => $values) {
            if (is_bool($values['new'])) {
                wfConfig::setBool($key, $values['new']);
            } else {
                wfConfig::set($key, $values['new']);
            }
        }

        WP_CLI::success('Alert settings updated successfully!');
    }

    /**
     * Get all alert settings.
     */
    private function get_all_settings() {
        return [
            'alertEmails' => wfConfig::get('alertEmails'),
            'alertOn_block' => wfConfig::get('alertOn_block'),
            'alertOn_loginLockout' => wfConfig::get('alertOn_loginLockout'),
            'alertOn_adminLogin' => wfConfig::get('alertOn_adminLogin'),
            'alertOn_breachLogin' => wfConfig::get('alertOn_breachLogin'),
            'alertOn_scanIssues' => wfConfig::get('alertOn_scanIssues'),
            'alertOn_update' => wfConfig::get('alertOn_update'),
            'alertOn_wordfenceDeactivated' => wfConfig::get('alertOn_wordfenceDeactivated'),
            'alert_maxHourly' => wfConfig::get('alert_maxHourly'),
        ];
    }

    /**
     * Format value for display.
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

WP_CLI::add_command('wf-alerts', 'WF_Alerts_CLI_Command');
