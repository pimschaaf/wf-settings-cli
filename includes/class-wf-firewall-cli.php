<?php
/**
 * WP-CLI Command for Wordfence Firewall/WAF Configuration
 *
 * @package Wordfence_Options
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Firewall and WAF settings via WP-CLI.
 */
class WF_Firewall_CLI_Command {

    /**
     * List current firewall settings.
     *
     * ## EXAMPLES
     *
     *     wp wf-firewall list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $settings = $this->get_all_settings();

        WP_CLI::line("\n=== Current Wordfence Firewall/WAF Settings ===\n");

        foreach ($settings as $key => $value) {
            WP_CLI::line(sprintf("%-35s: %s", $key, $this->format_value($value)));
        }

        WP_CLI::line("");
    }

    /**
     * Configure firewall settings.
     *
     * ## OPTIONS
     *
     * [--enable]
     * : Enable firewall
     *
     * [--disable]
     * : Disable firewall
     *
     * [--learning-mode=<1|0>]
     * : Enable/disable learning mode
     *
     * [--dry-run]
     * : Preview changes
     *
     * [--force]
     * : Skip confirmation
     *
     * ## EXAMPLES
     *
     *     wp wf-firewall configure --enable
     *     wp wf-firewall configure --learning-mode=0
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

        if (isset($assoc_args['enable'])) {
            $changes['firewallEnabled'] = ['old' => wfConfig::get('firewallEnabled'), 'new' => true];
        }

        if (isset($assoc_args['disable'])) {
            if (isset($assoc_args['enable'])) {
                WP_CLI::error('Cannot use both --enable and --disable');
            }
            $changes['firewallEnabled'] = ['old' => wfConfig::get('firewallEnabled'), 'new' => false];
        }

        if (empty($changes)) {
            WP_CLI::error('No changes specified. Use --help to see available options.');
        }

        // Display changes
        WP_CLI::line("\n=== Proposed Changes ===\n");
        foreach ($changes as $key => $values) {
            WP_CLI::line(sprintf("%-35s: %s → %s", $key, $this->format_value($values['old']), $this->format_value($values['new'])));
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

        WP_CLI::success('Firewall settings updated successfully!');
    }

    /**
     * Get all firewall settings.
     */
    private function get_all_settings() {
        return [
            'firewallEnabled' => wfConfig::get('firewallEnabled'),
            'autoBlockScanners' => wfConfig::get('autoBlockScanners'),
            'neverBlockBG' => wfConfig::get('neverBlockBG'),
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

WP_CLI::add_command('wf-firewall', 'WF_Firewall_CLI_Command');
