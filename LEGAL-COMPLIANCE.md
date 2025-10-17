# Legal Compliance & Licensing

## License

**WF Settings CLI** is licensed under the GNU General Public License v3.0 (GPLv3).

- **License**: GPLv3
- **License URI**: https://www.gnu.org/licenses/gpl-3.0.html
- **Full License**: See [LICENSE](LICENSE) file

## Trademark & Affiliation Notice

**IMPORTANT**: This plugin is an **independent tool** and is:

- ❌ **NOT** affiliated with Defiant, Inc.
- ❌ **NOT** endorsed by Wordfence
- ❌ **NOT** sponsored by Defiant, Inc. or Wordfence
- ❌ **NOT** an official Wordfence product

"Wordfence" is a **registered trademark** of Defiant, Inc.

This plugin merely provides WP-CLI commands that interact with the **publicly available API** of the Wordfence Security plugin (https://wordpress.org/plugins/wordfence/).

## GPLv3 Compliance

### Public API Usage Only

This plugin uses **ONLY public APIs** provided by the Wordfence plugin:

✅ **Allowed APIs Used**:
- `wfConfig::get($key)` - Public method to retrieve configuration values
- `wfConfig::set($key, $value)` - Public method to set configuration values
- `wfConfig::getBool($key)` - Public method to retrieve boolean values
- `wfConfig::setBool($key, $value)` - Public method to set boolean values
- `wfConfig::getInt($key)` - Public method to retrieve integer values

❌ **NOT Used**:
- Direct database table access (`wp_wfConfig` table)
- Private/protected Wordfence methods
- Wordfence internal hooks or filters
- Serialized data manipulation
- Premium-only features or bypasses

### No Core File Modification

✅ This plugin:
- Does NOT modify any Wordfence core files
- Does NOT replace Wordfence functions
- Does NOT override Wordfence classes
- Does NOT bypass Wordfence validation

❌ This plugin does NOT:
- Patch Wordfence code
- Hook into Wordfence private methods
- Access Wordfence premium features
- Circumvent license checks

### Free Version Only

This plugin **ONLY** works with settings available in the **FREE version** of Wordfence:

✅ **Free Features Supported**:
- Brute force protection settings (login/forgot password limits)
- Firewall enable/disable
- Scanner configuration (core/themes/plugins/malware scanning)
- Email alert settings
- Public configuration options

❌ **Premium Features NOT Accessed**:
- Real-time IP blocklist
- Country blocking (requires premium)
- Two-factor authentication (some features require premium)
- Advanced firewall rules
- Premium-only scan types

## Safe API Patterns

### Correct Usage (Used in This Plugin)

```php
// ✅ CORRECT: Using public API
$value = wfConfig::get('loginSec_maxFailures');
wfConfig::set('loginSec_maxFailures', 10);
```

### Incorrect Usage (NOT Used in This Plugin)

```php
// ❌ WRONG: Direct database access
global $wpdb;
$wpdb->update('wp_wfConfig', ['val' => '10'], ['name' => 'loginSec_maxFailures']);

// ❌ WRONG: Accessing private methods
$config = new wfConfig();
$config->privateMethod(); // Hypothetical private method

// ❌ WRONG: Bypassing validation
update_option('wordfence_premium_key', 'fake-key');
```

## Documentation & Open Source

### Transparency

All code in this plugin is:

✅ **Open Source**: Fully inspectable, modifiable, and distributable
✅ **Well-Documented**: Every function has clear comments explaining what it does
✅ **No Obfuscation**: No encoded, encrypted, or minified code
✅ **GPL Compatible**: Can be freely redistributed under GPLv3

### Code Inspection

You are **encouraged** to:

- Review all source code
- Modify the code for your needs
- Redistribute the modified code (under GPLv3)
- Report issues or suggest improvements

### Contribution Guidelines

If you contribute to this project:

1. Your code must be GPLv3 compatible
2. Do not add code that accesses premium features
3. Do not add code that directly modifies Wordfence files
4. Do not add code that bypasses Wordfence validation
5. Clearly document all public API usage

## Premium Feature Detection

### How This Plugin Avoids Premium Features

```php
// This plugin does NOT check for premium license
// It only uses public APIs available in free version

// Example: Setting a brute force option (FREE feature)
wfConfig::set('loginSec_maxFailures', 10); // ✅ Free feature

// Example: NOT accessing country blocking (PREMIUM feature)
// wfConfig::set('cbl_countries', 'CN,RU'); // ❌ Would require premium
```

### Settings This Plugin Manages

All settings managed by this plugin are from the **FREE version**:

| Setting Category | Free Version | Premium Version | This Plugin |
|------------------|--------------|-----------------|-------------|
| Brute Force Protection | ✅ Yes | ✅ Enhanced | ✅ Free only |
| Firewall Enable/Disable | ✅ Yes | ✅ Enhanced | ✅ Free only |
| Scanner (Core/Themes/Plugins) | ✅ Yes | ✅ Enhanced | ✅ Free only |
| Email Alerts | ✅ Yes | ✅ Enhanced | ✅ Free only |
| Country Blocking | ❌ No | ✅ Yes | ❌ Not accessed |
| Real-time IP List | ❌ No | ✅ Yes | ❌ Not accessed |
| Advanced Firewall Rules | ❌ No | ✅ Yes | ❌ Not accessed |

## Legal Safeguards in Code

### 1. Dependency Check

```php
// Plugin checks if Wordfence API is available
if (!class_exists('wfConfig')) {
    // Deactivate gracefully if not available
    deactivate_plugins(plugin_basename(__FILE__));
}
```

### 2. Public API Only

```php
// Only use public methods
wfConfig::get('setting_name');  // Public method ✅
wfConfig::set('setting_name', 'value');  // Public method ✅
```

### 3. No Direct Database Access

```php
// This plugin does NOT do this:
// global $wpdb;
// $wpdb->query("UPDATE wp_wfConfig ..."); // ❌ NEVER

// Instead, it uses the public API:
wfConfig::set('setting_name', 'value'); // ✅ ALWAYS
```

### 4. Respect Wordfence Validation

```php
// Wordfence's wfConfig::set() method includes validation
// This plugin respects that validation by using the public API
wfConfig::set('loginSec_maxFailures', 10); // Validated by Wordfence
```

## Disclaimer

```
WF Settings CLI - DISCLAIMER

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

The authors are not responsible for any damage, data loss, or
security issues that may arise from using this plugin.

Always test configuration changes in a non-production environment first.
Always maintain backups before making changes.

This plugin is an independent tool and is not affiliated with,
endorsed by, or sponsored by Defiant, Inc. or Wordfence.
```

## Contact

For legal questions or concerns:

- **Plugin Author**: Open Roads (https://open-roads.nl)
- **Issues**: https://github.com/pimschaaf/wf-settings-cli/issues
- **License**: GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)

For Wordfence-related questions:

- **Wordfence Support**: https://www.wordfence.com/help/
- **Wordfence Plugin**: https://wordpress.org/plugins/wordfence/

---

**Last Updated**: 2025-10-17
**Plugin Version**: 2.0.0
**License**: GPLv3
