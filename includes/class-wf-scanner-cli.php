<?php
/**
 * WP-CLI Command for Wordfence Scanner Configuration
 *
 * @package Wordfence_Options
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Wordfence Scanner settings via WP-CLI.
 */
class WF_Scanner_CLI_Command {

    /**
     * List current scanner settings.
     *
     * ## EXAMPLES
     *
     *     wp wf-scanner list
     *
     * @when after_wp_load
     */
    public function list($args, $assoc_args) {
        if (!class_exists('wfConfig')) {
            WP_CLI::error('Wordfence plugin is not installed or activated.');
        }

        $settings = $this->get_all_settings();

        WP_CLI::line("\n=== Current Wordfence Scanner Settings ===\n");

        foreach ($settings as $key => $value) {
            WP_CLI::line(sprintf("%-40s: %s", $key, $this->format_value($value)));
        }

        WP_CLI::line("");
    }

    /**
     * Configure scanner settings.
     *
     * ## OPTIONS
     *
     * [--scan-core=<1|0>]
     * : Scan core files
     *
     * [--scan-themes=<1|0>]
     * : Scan theme files
     *
     * [--scan-plugins=<1|0>]
     * : Scan plugin files
     *
     * [--scan-malware=<1|0>]
     * : Enable malware scanning
     *
     * [--scan-images=<1|0>]
     * : Scan image files
     *
     * [--scan-comments=<1|0>]
     * : Scan comments for spam/malicious content
     *
     * [--scan-posts=<1|0>]
     * : Scan posts for malicious content
     *
     * [--high-sensitivity=<1|0>]
     * : Enable high sensitivity scanning
     *
     * [--dry-run]
     * : Preview changes
     *
     * [--force]
     * : Skip confirmation
     *
     * ## EXAMPLES
     *
     *     wp wf-scanner configure --scan-themes=1 --scan-plugins=1
     *     wp wf-scanner configure --high-sensitivity=1 --dry-run
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

        $options_map = [
            'scan-core' => 'scansEnabled_core',
            'scan-themes' => 'scansEnabled_themes',
            'scan-plugins' => 'scansEnabled_plugins',
            'scan-malware' => 'scansEnabled_malware',
            'scan-images' => 'scansEnabled_scanImages',
            'scan-comments' => 'scansEnabled_comments',
            'scan-posts' => 'scansEnabled_posts',
            'high-sensitivity' => 'scansEnabled_highSense',
        ];

        foreach ($options_map as $arg_name => $config_key) {
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
            wfConfig::setBool($key, $values['new']);
        }

        WP_CLI::success('Scanner settings updated successfully!');
    }

    /**
     * Get all scanner settings.
     */
    private function get_all_settings() {
        return [
            'scheduledScansEnabled' => wfConfig::get('scheduledScansEnabled'),
            'scansEnabled_core' => wfConfig::get('scansEnabled_core'),
            'scansEnabled_themes' => wfConfig::get('scansEnabled_themes'),
            'scansEnabled_plugins' => wfConfig::get('scansEnabled_plugins'),
            'scansEnabled_malware' => wfConfig::get('scansEnabled_malware'),
            'scansEnabled_fileContents' => wfConfig::get('scansEnabled_fileContents'),
            'scansEnabled_posts' => wfConfig::get('scansEnabled_posts'),
            'scansEnabled_comments' => wfConfig::get('scansEnabled_comments'),
            'scansEnabled_scanImages' => wfConfig::get('scansEnabled_scanImages'),
            'scansEnabled_highSense' => wfConfig::get('scansEnabled_highSense'),
            'scan_maxIssues' => wfConfig::get('scan_maxIssues'),
            'scan_maxDuration' => wfConfig::get('scan_maxDuration'),
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

WP_CLI::add_command('wf-scanner', 'WF_Scanner_CLI_Command');
