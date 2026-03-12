# WP Feature-Flags Manager

Small utility plugin to quickly toggle feature flags and run one-click actions for WordPress plugins via the admin bar.


## Usage

While active, the plugin adds two menus to the top admin bar:

**Feature Flags** - Toggle feature flags on or off. Feature flags are set via simple WP filters that return true or false. The filters are added during the `plugins_loaded` event, so this plugin can change _any_ WP filter to return a boolean value.

**Feature Actions** - Run predefined database operations with a single click. Each action executes a list of changes (set/delete options, fire action hooks). Useful for triggering migrations, resetting state, etc.

![](screenshot.png)


## Installation

Run the `install.sh` script and define the target project as argument to install
this plugin into the relevant DDEV environment:

```sh
bash install.sh ~/Coding/wc-pp-plugin
```


## Configuration

### Feature Flags

Edit `flags.php` to add or modify feature flags. The file must return an array of flag definitions:

```php
return [
    'my/flag' => [
        'label'   => 'My Feature',
        'filter'  => 'my_plugin_feature_enabled',
        'default' => true,       // optional: bool or callable
        'display' => true,       // optional: set to false to hide
    ],
];
```

The legacy filename `config.php` is still supported as a fallback.

### Feature Actions

Edit `actions.php` to define one-click actions. The file must return an array of action definitions:

```php
return [
    'my/action' => [
        'label'   => 'Reset Onboarding',
        'changes' => [
            ['set_option', 'option_name', $value],
            ['set_option_key', 'option_name', 'array_key', $value],
            ['delete_option', 'option_name'],
            ['do_action', 'my_hook_name', ...$args],
        ],
    ],
];
```

Supported change types:
- `['set_option', 'key', $value]` - Sets a WP option to the given value
- `['set_option_key', 'key', 'array_key', $value]` - Updates a single key within an array option
- `['delete_option', 'key']` - Deletes a WP option
- `['do_action', 'hook', ...$args]` - Fires a WordPress action hook with optional arguments

### Group headings

Both config files support visual group headings. An item with only a `label` key (no `filter`/`changes`) renders as a non-clickable separator in the dropdown menu:

```php
return [
    'ppcp' => [
        'label' => 'PayPal Payments',
    ],
    'wcpp/applepay_enabled' => [
        'label'  => 'Apple Pay',
        'filter' => '...',
    ],
];
```

### Local overrides

Both config files can be copied to a `.local.php` variant (`flags.local.php`, `actions.local.php`) for local adjustments. The `.local.php` files are ignored by git and take precedence over the default files.
