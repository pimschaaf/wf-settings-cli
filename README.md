# Wordfence Settings CLI

Powerful WP-CLI commands for managing all Wordfence Security settings programmatically.

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Overview

**Wordfence Settings CLI** provides comprehensive WP-CLI commands to configure and manage all Wordfence Security settings via the command line. Perfect for automation, bulk configuration, and DevOps workflows.

## Features

- 🔧 **Generic Interface** - Get/set any Wordfence setting via `wp wf-config`
- 🛡️ **Brute Force Protection** - Configure login security with `wp wf-brute`
- 🔥 **Firewall & WAF** - Manage firewall settings with `wp wf-firewall`
- 🔍 **Scanner Configuration** - Control malware scanning via `wp wf-scanner`
- 📧 **Alert Management** - Set up email alerts with `wp wf-alerts`
- 💾 **Backup & Restore** - Automatic backups before changes
- 🔄 **Export/Import** - Save and share configurations as JSON
- ✅ **Dry-Run Mode** - Preview changes before applying
- 📊 **Input Validation** - Enforces Wordfence's allowed value ranges
- 🔒 **100% Compatible** - Uses Wordfence's native API

## Requirements

- **Wordfence Security** plugin (required)
- **WP-CLI** command line tool
- WordPress 5.0+
- PHP 7.2+

## Installation

### Via WordPress Admin

1. Download the latest release
2. Upload to WordPress via Plugins → Add New → Upload
3. Activate the plugin
4. Ensure Wordfence Security is installed and active

### Via WP-CLI

```bash
wp plugin install wordfence-settings-cli --activate
```

### Manual Installation

```bash
cd wp-content/plugins
git clone https://github.com/pimschaaf/wordfence-settings-cli.git
wp plugin activate wordfence-settings-cli
```

## Quick Start

```bash
# List current brute force settings
wp wf-brute list

# Configure stricter login security
wp wf-brute configure \
  --max-login-failures=5 \
  --lockout-duration-mins=120

# Preview changes (dry-run)
wp wf-brute configure --max-login-failures=10 --dry-run

# Export all Wordfence settings
wp wf-config export /tmp/wordfence-config.json

# Apply settings across multiple sites
for site in site1.com site2.com site3.com; do
  wp wf-brute configure --url=$site --max-login-failures=10
done
```

## Commands

### `wp wf-config` - Generic Interface

Get/set any Wordfence setting:

```bash
# Get a setting
wp wf-config get loginSecurityEnabled

# Set a setting
wp wf-config set loginSec_maxFailures 10

# List all settings
wp wf-config list

# Search for settings
wp wf-config list --search=login

# Filter by category
wp wf-config list --category=brute-force

# Export ALL settings (complete audit)
wp wf-config export /tmp/config.json

# Export only managed settings (blueprint for deployment)
wp wf-config export /tmp/blueprint.json --managed-only

# Export specific category
wp wf-config export /tmp/brute-force.json --category=brute-force

# Import settings (with preview and backup)
wp wf-config import /tmp/config.json --dry-run
wp wf-config import /tmp/config.json --backup
```

### `wp wf-brute` - Brute Force Protection

Manage login security settings:

```bash
# List settings
wp wf-brute list

# Enable/disable
wp wf-brute configure --enable
wp wf-brute configure --disable

# Configure settings
wp wf-brute configure \
  --max-login-failures=10 \
  --max-forgot-failures=5 \
  --failure-window-mins=60 \
  --lockout-duration-mins=240 \
  --block-invalid-usernames=1

# Backup & restore
wp wf-brute backup
wp wf-brute restore wf_backup_1234567890

# Export configuration
wp wf-brute export /tmp/brute-force-config.json
```

### `wp wf-firewall` - Firewall Settings

Manage Wordfence firewall:

```bash
# List firewall settings
wp wf-firewall list

# Enable/disable firewall
wp wf-firewall configure --enable
wp wf-firewall configure --disable
```

### `wp wf-scanner` - Scanner Configuration

Configure malware scanner:

```bash
# List scanner settings
wp wf-scanner list

# Enable comprehensive scanning
wp wf-scanner configure \
  --scan-core=1 \
  --scan-themes=1 \
  --scan-plugins=1 \
  --scan-malware=1 \
  --high-sensitivity=1
```

### `wp wf-alerts` - Alert Management

Configure email alerts:

```bash
# List alert settings
wp wf-alerts list

# Configure alerts
wp wf-alerts configure \
  --email="security@example.com" \
  --alert-on-lockout=1 \
  --alert-on-block=1 \
  --alert-on-scan-issues=1
```

## Common Use Cases

### 1. Harden Security After Attack

```bash
# Create backup
wp db export /tmp/backup-$(date +%Y%m%d).sql

# Tighten security
wp wf-brute configure \
  --max-login-failures=3 \
  --lockout-duration-mins=1440 \
  --block-invalid-usernames=1

wp wf-scanner configure \
  --scan-themes=1 \
  --scan-plugins=1 \
  --high-sensitivity=1

wp wf-alerts configure \
  --email="security@example.com" \
  --alert-on-lockout=1 \
  --alert-on-breach-login=1
```

### 2. Apply Standard Configuration Across Sites

```bash
#!/bin/bash
SITES=("site1.com" "site2.com" "site3.com")

for site in "${SITES[@]}"; do
  wp wf-brute configure \
    --url=$site \
    --max-login-failures=10 \
    --lockout-duration-mins=240 \
    --force
done
```

### 3. Clone Configuration Between Sites

```bash
# Export from production
wp wf-config export /tmp/prod-config.json --url=production.com

# Import to staging
wp wf-config import /tmp/prod-config.json --url=staging.com --backup
```

### 4. Automated Security Audits

```bash
# Export ALL configuration for complete audit
wp wf-config export /tmp/audit-$(date +%Y%m%d).json

# Export ONLY manageable settings (blueprint)
wp wf-config export /tmp/baseline-$(date +%Y%m%d).json --managed-only

# Review critical settings
wp wf-brute list
wp wf-scanner list
wp wf-alerts list
```

### 5. Configuration Blueprints & Site Migrations

```bash
# Create a security baseline blueprint from your production site
wp wf-config export /tmp/security-blueprint.json --managed-only

# Preview what will change on a new site
wp wf-config import /tmp/security-blueprint.json --dry-run

# Apply blueprint to staging/development/new sites
wp wf-config import /tmp/security-blueprint.json --backup

# Bulk deployment across multiple sites
for site in site1.com site2.com site3.com; do
  wp wf-config import /tmp/security-blueprint.json --url=$site --force
done
```

**What's the difference?**
- **Full Export** (`wp wf-config export`): All 280+ Wordfence settings including internal state
- **Blueprint Export** (`--managed-only`): Only ~42 settings this plugin can manage (security policies)

**Use Cases for Blueprints:**
- 📋 Create reusable security configuration templates
- 🔄 Standardize settings across multiple WordPress sites
- 🚀 Automate new site deployment with pre-approved security settings
- 📊 Version control your security policies in git
- 🔍 Easier auditing (smaller, focused configuration file)

## Options Reference

### Brute Force Protection

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--enable/--disable` | - | - | Enable/disable brute force protection |
| `--max-login-failures` | 2-500 | 20 | Max login attempts before lockout |
| `--max-forgot-failures` | 1-500 | 20 | Max forgot password attempts |
| `--failure-window-mins` | 5,10,30,60,120,240,360,720,1440 | 240 | Time window to count failures |
| `--lockout-duration-mins` | 5-86400 | 240 | Lockout duration |
| `--block-invalid-usernames` | 0,1 | 0 | Immediately block invalid usernames |

### Scanner Options

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--scan-core` | 0,1 | 1 | Scan WordPress core files |
| `--scan-themes` | 0,1 | 0 | Scan theme files |
| `--scan-plugins` | 0,1 | 0 | Scan plugin files |
| `--scan-malware` | 0,1 | 1 | Enable malware scanning |
| `--scan-images` | 0,1 | 0 | Scan image files |
| `--high-sensitivity` | 0,1 | 0 | High sensitivity mode |

### Alert Options

| Option | Values | Description |
|--------|--------|-------------|
| `--email` | email(s) | Alert email address(es) (comma-separated) |
| `--alert-on-block` | 0,1 | Alert when IP is blocked |
| `--alert-on-lockout` | 0,1 | Alert on login lockouts |
| `--alert-on-admin-login` | 0,1 | Alert on admin logins |
| `--alert-on-breach-login` | 0,1 | Alert on compromised password attempts |
| `--alert-on-scan-issues` | 0,1 | Alert when scan finds issues |

## Safety Features

All commands include safety features:

- ✅ **Automatic Backups** - Created before every change
- ✅ **Dry-Run Mode** - Use `--dry-run` to preview changes
- ✅ **Input Validation** - Validates all values against Wordfence constraints
- ✅ **Confirmation Prompts** - Confirms destructive actions (use `--force` to skip)
- ✅ **Rollback Support** - Restore previous configurations from backups

## Security

This plugin follows WordPress security best practices:

- Only loads in WP-CLI context (not web-accessible)
- Uses Wordfence's native API (no direct database manipulation)
- All inputs validated and sanitized
- Respects Wordfence's internal validation
- Automatic dependency checking
- Follows WordPress Coding Standards

## Troubleshooting

### Plugin Won't Activate

Ensure Wordfence is installed and active:

```bash
wp plugin list | grep wordfence
wp plugin activate wordfence
wp plugin activate wordfence-settings-cli
```

### Commands Not Found

Verify WP-CLI can see the commands:

```bash
wp cli cmd-dump | grep wf-
```

### Settings Not Persisting

Check if Wordfence tables exist:

```bash
wp db query "SHOW TABLES LIKE 'wp_wfConfig'"
```

### Locked Out After Changes

Disable brute force protection via database:

```bash
wp db query "UPDATE wp_wfConfig SET val = '0' WHERE name = 'loginSecurityEnabled'"
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Submit a pull request

## Support

- **Issues**: [GitHub Issues](https://github.com/pimschaaf/wordfence-settings-cli/issues)
- **Documentation**: This README and inline command help (`wp wf-brute --help`)
- **Wordfence Docs**: [Wordfence Documentation](https://www.wordfence.com/help/)

## License

GPL v3 or later. See [LICENSE](LICENSE) for details.

## Credits

Developed by [Pim Schaaf](https://open-roads.nl)

Built with ❤️ for the WordPress community.

## Changelog

### 2.0.4 (2025-01-30)
- **New Feature**: `--managed-only` flag for `wp wf-config export` to create configuration blueprints
- **Enhancement**: Export only the ~42 settings that this plugin can manage (security policies)
- **Feature**: Blueprint exports perfect for site migrations, standardized deployments, and version control
- **Enhancement**: Better error handling for export with JSON encoding validation and bytes written confirmation
- **Enhancement**: Binary blob (LONGBLOB) handling for database values
- **Improvement**: Export command now supports invalid UTF-8 characters with JSON_INVALID_UTF8_SUBSTITUTE

### 2.0.3 (2025-01-30)
- **Bug Fix**: Fixed table name case sensitivity issue for Wordfence configuration table
- **Enhancement**: Now uses `wfDB::networkTable()` method to respect Wordfence's table naming convention
- **Fix**: Resolves "Table 'wp_wfConfig' doesn't exist" error on systems using lowercase table names
- Improved compatibility with different MySQL/MariaDB case sensitivity configurations

### 1.0.0 (2025-10-16)
- Initial release
- Brute force protection commands
- Backup/restore functionality
- Export/import support
- **New**: Generic `wp wf-config` command
- **New**: Firewall, scanner, and alerts commands
- **Enhanced**: Automatic dependency checking
- **Enhanced**: WordPress coding standards compliance
- **Security**: Improved input validation
