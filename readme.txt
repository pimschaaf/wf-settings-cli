=== WF Settings CLI ===
Contributors: pimschaaf
Tags: wp-cli, cli, automation, security, configuration
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 2.0.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Independent WP-CLI tool for managing WF Security plugin settings programmatically. Perfect for automation and bulk configuration workflows.

== Description ==

**WF Settings CLI** is an **independent, open-source tool** that provides WP-CLI commands to configure WF Security plugin settings via command line.

**IMPORTANT LEGAL NOTICE**: This is an independent plugin that is:
* **NOT** affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence
* Uses ONLY public APIs from the WF Security plugin
* Licensed under GPLv3 (fully open source)
* Works only with **FREE** version features
* "Wordfence" is a registered trademark of Defiant, Inc.

This plugin provides WP-CLI commands for system administrators, DevOps teams, and developers who need to automate security configurations across multiple WordPress installations.

= Features =

* **Generic Interface** (`wp wf-config`) - Get/set any Wordfence setting
* **Brute Force Protection** (`wp wf-brute`) - Configure login security settings
* **Firewall & WAF** (`wp wf-firewall`) - Manage firewall settings
* **Scanner Configuration** (`wp wf-scanner`) - Control malware scanning options
* **Alert Management** (`wp wf-alerts`) - Set up email alerts and notifications

= Key Benefits =

* ✅ **Automation Ready** - Script all Wordfence configurations
* ✅ **Bulk Operations** - Apply settings across multiple sites simultaneously
* ✅ **Safety First** - Automatic backups before changes, dry-run mode
* ✅ **100% Compatible** - Uses Wordfence's native API, no breaking changes
* ✅ **Export/Import** - Save and share configurations as JSON
* ✅ **Input Validation** - Enforces Wordfence's allowed value ranges

= Requirements =

* **Wordfence Security** plugin must be installed and active
* **WP-CLI** must be available on your system
* WordPress 5.0 or higher
* PHP 7.2 or higher

= Use Cases =

* Apply standard security configurations across multiple WordPress sites
* Automate Wordfence setup in deployment scripts
* Quickly tighten security after detecting an attack
* Export configurations for documentation or backup
* Manage Wordfence settings in CI/CD pipelines

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wordfence-settings-cli/` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure Wordfence Security is installed and activated (required)
4. Use WP-CLI commands to configure Wordfence settings

**Note**: This plugin requires Wordfence Security to be installed and active. It will automatically deactivate if Wordfence is not available.

== Frequently Asked Questions ==

= Does this plugin work without Wordfence? =

No. This plugin requires Wordfence Security to be installed and activated. It provides WP-CLI commands to configure Wordfence, but cannot function without it.

= Will this break my existing Wordfence configuration? =

No. The plugin uses Wordfence's native API (`wfConfig::get()` and `wfConfig::set()`) ensuring 100% compatibility. All settings configured via CLI work identically in the Wordfence admin interface.

= Can I use this on WordPress.com? =

No. This plugin requires WP-CLI access, which is not available on WordPress.com. It works on self-hosted WordPress installations and managed hosting that supports WP-CLI.

= How do I undo changes? =

The plugin creates automatic backups before making changes. Use `wp wf-brute restore <backup-key>` to rollback. You can also use the `--dry-run` flag to preview changes before applying them.

= Is this plugin secure? =

Yes. The plugin follows WordPress coding standards and security best practices:
* Only loads in WP-CLI context (not web-accessible)
* Uses Wordfence's native API (no direct database manipulation)
* All inputs are validated and sanitized
* Respects Wordfence's internal validation
* Automatic dependency checking

= Can I manage multiple sites? =

Yes! Use WP-CLI's `--url` flag to manage multiple sites:
`wp wf-brute configure --url=site1.com --max-login-failures=10`

Or create bash scripts to loop through multiple sites.

== Usage ==

= Quick Start =

List current brute force settings:
`wp wf-brute list`

Configure stricter login security:
`wp wf-brute configure --max-login-failures=5 --lockout-duration-mins=120`

Preview changes before applying (dry-run):
`wp wf-brute configure --max-login-failures=10 --dry-run`

= Generic Interface =

Get any Wordfence setting:
`wp wf-config get loginSecurityEnabled`

Set any Wordfence setting:
`wp wf-config set loginSec_maxFailures 10`

List all settings:
`wp wf-config list`

Search for specific settings:
`wp wf-config list --search=login`

Export all settings to JSON:
`wp wf-config export /tmp/wordfence-config.json`

Export only manageable settings (blueprint):
`wp wf-config export /tmp/blueprint.json --managed-only`

Import settings from JSON:
`wp wf-config import /tmp/wordfence-config.json --dry-run`
`wp wf-config import /tmp/blueprint.json --backup`

= Configuration Blueprints & Migrations =

Create reusable security configuration templates:
`wp wf-config export /tmp/security-baseline.json --managed-only`

Preview changes before applying:
`wp wf-config import /tmp/security-baseline.json --dry-run`

Deploy to multiple sites:
`wp wf-config import /tmp/security-baseline.json --url=site.com --force`

**Blueprint exports** include only the ~42 settings this plugin can manage (security policies), perfect for:
* Creating standardized security configurations
* Site migrations and cloning
* Version control of security policies
* Automated deployment workflows

= Brute Force Protection =

Enable brute force protection:
`wp wf-brute configure --enable`

Set maximum login failures:
`wp wf-brute configure --max-login-failures=10`

Block invalid usernames immediately:
`wp wf-brute configure --block-invalid-usernames=1`

Full configuration example:
`wp wf-brute configure \
  --max-login-failures=10 \
  --max-forgot-failures=5 \
  --failure-window-mins=60 \
  --lockout-duration-mins=240`

Create manual backup:
`wp wf-brute backup`

Restore from backup:
`wp wf-brute restore wf_backup_1234567890`

= Firewall Settings =

Enable firewall:
`wp wf-firewall configure --enable`

List firewall settings:
`wp wf-firewall list`

= Scanner Settings =

Enable theme and plugin scanning:
`wp wf-scanner configure --scan-themes=1 --scan-plugins=1`

Enable high sensitivity mode:
`wp wf-scanner configure --high-sensitivity=1`

List scanner settings:
`wp wf-scanner list`

= Alert Settings =

Configure alert email:
`wp wf-alerts configure --email="security@example.com"`

Enable specific alerts:
`wp wf-alerts configure --alert-on-lockout=1 --alert-on-block=1`

List alert settings:
`wp wf-alerts list`

For complete documentation, visit: [GitHub Repository](https://github.com/pimschaaf/wordfence-settings-cli)

== Screenshots ==

1. WP-CLI command showing current brute force protection settings
2. Dry-run preview before applying configuration changes
3. Exporting Wordfence configuration to JSON file
4. Applying security settings across multiple WordPress installations

== Changelog ==

= 2.0.4 - 2025-01-30 =
* New: `--managed-only` flag for `wp wf-config export` to create configuration blueprints
* New: Export only the ~42 settings that this plugin can manage (vs. all 280+ Wordfence settings)
* Feature: Blueprint exports perfect for site migrations, audits, and version control
* Enhancement: Better error handling for export with JSON encoding validation
* Enhancement: Binary blob handling for LONGBLOB database values
* Enhancement: Shows bytes written confirmation on successful export
* Improved: Export command now supports binary data and invalid UTF-8 characters

= 2.0.3 - 2025-01-30 =
* Fixed: Table name case sensitivity issue for Wordfence configuration table
* Fixed: "Table 'wp_wfConfig' doesn't exist" error on systems using lowercase table names
* Enhancement: Now uses wfDB::networkTable() method to respect Wordfence's table naming convention
* Improved: Compatibility with different MySQL/MariaDB case sensitivity configurations

= 2.0.2 - 2025-10-29 =
* Fixed: Plugin loading order issue where WP-CLI commands weren't registered
* Fixed: Wrapped command registration in 'plugins_loaded' hook to ensure Wordfence loads first
* Improved: Commands now properly available when both plugins are active

= 2.0.1 - 2025-10-29 =
* Fixed: WordPress.org plugin directory compliance issues
* Fixed: Replaced date() with gmdate() to avoid timezone issues
* Fixed: Added proper phpcs:ignore annotations for database queries
* Fixed: Updated "Tested up to" WordPress 6.8
* Fixed: Removed .gitignore from distribution

= 1.0.0 - 2025-10-16 =
* Initial release
* `wp wf-brute` command for brute force protection settings
* Automatic backup before changes
* Dry-run mode
* Export/import functionality
* **New**: Generic `wp wf-config` command for any Wordfence setting
* **New**: `wp wf-firewall` command for firewall/WAF configuration
* **New**: `wp wf-scanner` command for scanner settings
* **New**: `wp wf-alerts` command for alert management
* **Enhanced**: Automatic Wordfence dependency checking
* **Enhanced**: Plugin deactivates automatically if Wordfence is missing
* **Enhanced**: Better error handling and validation
* **Enhanced**: WordPress coding standards compliance
* **Security**: Added phpcs annotations for superglobal access
* **Security**: Improved input sanitization and validation

== Upgrade Notice ==

= 2.0.4 =
New feature: Configuration blueprints with --managed-only flag. Export only manageable settings for site migrations and standardized deployments.

= 2.0.3 =
Bug fix release: Fixes table name case sensitivity issue that caused errors on some MySQL/MariaDB configurations.

= 1.0.0 =
Initial release. Requires Wordfence Security to be installed and activated.
With commands for firewall, scanner, and alerts.

== Additional Info ==

= Development =

This plugin is actively maintained. Feature requests and bug reports are welcome on [GitHub](https://github.com/pimschaaf/wordfence-settings-cli/issues).

= Credits =

Developed by [Open Roads](https://open-roads.nl)

= Support =

For support, please visit the plugin's [support forum](https://wordpress.org/support/plugin/wordfence-settings-cli/) or [GitHub repository](https://github.com/pimschaaf/wordfence-settings-cli).
