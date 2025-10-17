<?php
/**
 * Plugin Name: WF Settings CLI
 * Plugin URI: https://github.com/pimschaaf/wf-settings-cli
 * Description: WP-CLI commands for managing WF Security plugin settings programmatically. Independent tool for automation and bulk configuration.
 * Version: 2.0.0
 * Author: Open Roads
 * Author URI: https://open-roads.nl
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Requires Plugins: wordfence
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wf-settings-cli
 *
 * WF Settings CLI - GPL Compliance Notice
 *
 * Copyright (C) 2025 Open Roads
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * IMPORTANT LEGAL NOTICE:
 *
 * This plugin is an independent tool that provides WP-CLI commands for
 * configuring the Wordfence Security plugin (https://wordpress.org/plugins/wordfence/).
 *
 * This plugin:
 * - Is NOT affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence
 * - Uses only PUBLIC APIs provided by the Wordfence plugin (wfConfig class)
 * - Does NOT modify Wordfence core files or bypass any premium features
 * - Only works with settings available in the FREE version of Wordfence
 * - Is provided as-is under GPLv3 license
 *
 * "Wordfence" is a registered trademark of Defiant, Inc.
 * This plugin merely interacts with the publicly available Wordfence plugin API.
 *
 * API Usage Compliance:
 * - Uses wfConfig::get() and wfConfig::set() public methods
 * - Uses wfConfig::getBool() and wfConfig::setBool() public methods
 * - Does NOT directly access wp_wfConfig database table
 * - Does NOT bypass Wordfence's internal validation
 * - Respects all Wordfence hooks and filters
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WF_SETTINGS_CLI_VERSION', '2.0.0');
define('WF_SETTINGS_CLI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WF_SETTINGS_CLI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WF_SETTINGS_CLI_PLUGIN_FILE', __FILE__);

/**
 * Check if WF Security plugin is active.
 *
 * This plugin requires the Wordfence Security plugin to be installed and active
 * because it uses the public wfConfig API provided by that plugin.
 *
 * @return bool True if WF Security is active and API is available
 */
function wf_settings_cli_check_dependencies() {
    // Check if wfConfig class exists (public API from Wordfence plugin)
    if (!class_exists('wfConfig')) {
        add_action('admin_notices', 'wf_settings_cli_missing_dependency_notice');
        return false;
    }
    return true;
}

/**
 * Display admin notice when required plugin is not active.
 */
function wf_settings_cli_missing_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('WF Settings CLI:', 'wf-settings-cli'); ?></strong>
            <?php esc_html_e('This plugin requires Wordfence Security to be installed and activated.', 'wf-settings-cli'); ?>
            <a href="<?php echo esc_url(admin_url('plugin-install.php?s=wordfence&tab=search&type=term')); ?>">
                <?php esc_html_e('Install Wordfence Security', 'wf-settings-cli'); ?>
            </a>
        </p>
        <p>
            <em><?php esc_html_e('Note: This is an independent plugin that provides CLI commands for Wordfence configuration. It is not affiliated with Defiant, Inc.', 'wf-settings-cli'); ?></em>
        </p>
    </div>
    <?php
}

/**
 * Deactivate plugin if required dependency is not active.
 *
 * This ensures the plugin doesn't cause errors if Wordfence is not available.
 */
function wf_settings_cli_deactivate_if_missing_dependency() {
    if (!class_exists('wfConfig')) {
        deactivate_plugins(plugin_basename(__FILE__));

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only unsetting superglobal, not processing
        if (isset($_GET['activate'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only unsetting superglobal, not processing
            unset($_GET['activate']);
        }

        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php esc_html_e('WF Settings CLI has been deactivated.', 'wf-settings-cli'); ?></strong>
                    <?php esc_html_e('This plugin requires Wordfence Security to be installed and activated first.', 'wf-settings-cli'); ?>
                </p>
            </div>
            <?php
        });
    }
}

// Check dependencies on plugin activation
register_activation_hook(__FILE__, 'wf_settings_cli_deactivate_if_missing_dependency');

// Check dependencies on every admin page load
add_action('admin_init', 'wf_settings_cli_check_dependencies');

/**
 * Load WP-CLI commands if WP-CLI is available and dependency is met.
 *
 * Commands are only loaded in WP-CLI context (not web-accessible).
 * All commands use the public wfConfig API provided by Wordfence.
 */
if (defined('WP_CLI') && WP_CLI) {
    // Verify the public API is available
    if (class_exists('wfConfig')) {
        require_once WF_SETTINGS_CLI_PLUGIN_DIR . 'includes/class-wf-config-cli.php';
        require_once WF_SETTINGS_CLI_PLUGIN_DIR . 'includes/class-wf-brute-force-cli.php';
        require_once WF_SETTINGS_CLI_PLUGIN_DIR . 'includes/class-wf-firewall-cli.php';
        require_once WF_SETTINGS_CLI_PLUGIN_DIR . 'includes/class-wf-scanner-cli.php';
        require_once WF_SETTINGS_CLI_PLUGIN_DIR . 'includes/class-wf-alerts-cli.php';
    } else {
        WP_CLI::warning('WF Settings CLI: Required plugin (Wordfence Security) is not active. Commands will not be available.');
    }
}
