# WP Config Transformer

Programmatically edit a `wp-config.php` file.

[![Testing](https://github.com/wp-cli/wp-config-transformer/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/wp-config-transformer/actions/workflows/testing.yml)

Quick links: [Using](#using) &#124; [Options](#options) &#124; [How it works](#how-it-works) &#124; [Testing](#testing)

## Using

### Instantiate

```php
$config_transformer = new WPConfigTransformer( '/path/to/wp-config.php' );
```

### Edit constants

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', array( 'raw' => true ) );
$config_transformer->add( 'constant', 'MY_SPECIAL_CONFIG', 'foo' );
$config_transformer->remove( 'constant', 'MY_SPECIAL_CONFIG' );
```

### Edit variables

```php
$config_transformer->update( 'variable', 'table_prefix', 'wp_custom_' );
$config_transformer->add( 'variable', 'my_special_global', 'foo' );
$config_transformer->remove( 'variable', 'my_special_global' );
```

### Check for existence

```php
if ( $config_transformer->exists( 'constant', 'MY_SPECIAL_CONFIG' ) ) {
	// do stuff
}

if ( $config_transformer->exists( 'variable', 'my_special_global' ) ) {
	// do stuff
}
```

## Options

Special behaviors when adding or updating configs are available using the options array.

### Normalization

In contrast to the "edit in place" strategy above, there is the option to normalize the output during a config update and effectively replace the existing syntax with output that adheres to WP Coding Standards.

Let's reconsider a poorly-formatted example:

```php
                 define   (    'WP_DEBUG'   ,
    false, false     )
;
```

This time running:

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', array( 'raw' => true, 'normalize' => true ) );
```

Now we will get an output of:

```php
define( 'WP_DEBUG', true );
```

Nice!

### Raw format

Suppose you want to change your `ABSPATH` config _(gasp!)_. To do that, we can run:

```php
$config_transformer->update( 'constant', 'ABSPATH', "dirname( __FILE__ ) . '/somewhere/else/'", array( 'raw' => true ) );
```

The `raw` option means that instead of placing the value inside the config as a string `"dirname( __FILE__ ) . '/somewhere/else/'"` it will become unquoted (and executable) syntax `dirname( __FILE__ ) . '/somewhere/else/'`.

### Anchor string

The anchor string is the piece of text that additions will be anchored to.

```php
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'anchor' => '/** Absolute path to the WordPress directory' ) ); // Default
```

### Anchor placement

By default, new configs will be placed before the anchor string.

```php
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'placement' => 'before' ) ); // Default
$config_transformer->update( 'constant', 'BAZ', 'qux', array( 'placement' => 'after' ) );
```

### Anchor separator

By default, the separator between a new config and its anchor string is an EOL ("\n" on *nix and "\r\n" on Windows).

```php
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'separator' => PHP_EOL . PHP_EOL ) ); // Default
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'separator' => PHP_EOL ) );
```

### Add if missing

By default, when attempting to update a config that doesn't exist, one will be added. This behavior can be overridden by specifying the `add` option and setting it to `false`.

```php
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'add' => true ) ); // Default
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'add' => false ) );
```

If the constant `FOO` exists, it will be updated in-place. And if not, the update will return `false`:

```php
$config_transformer->exists( 'constant', 'FOO' ); // Returns false
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'add' => false ) ); // Returns false
```

## How it works

### Parsing configs

Constants: https://regex101.com/r/6AeNGP/4

Variables: https://regex101.com/r/cSLZZz/4

### Editing in place

Due to the unsemantic nature of the `wp-config.php` file, and PHP's loose syntax in general, the WP Config Transformer takes an "edit in place" strategy in order to preserve the original formatting and whatever other obscurities may be taking place in the block. After all, we only care about transforming values, not constant or variable names.

To achieve this, the following steps are performed:

1. A PHP block containing a config is split into distinct parts.
2. Only the part containing the config value is targeted for replacement.
3. The parts are reassembled with the new value in place.
4. The old PHP block is replaced with the new PHP block.

Consider the following horrifically-valid PHP block, that also happens to be using the optional (and rare) 3rd argument for constant case-sensitivity:

```php
                 define   (    'WP_DEBUG'   ,
    false, false     )
;
```

The "edit in place" strategy means that running:

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', array( 'raw' => true ) );
```

Will give us a result that safely changes _only_ the value, leaving the formatting and additional argument(s) unscathed:

```php
                 define   (    'WP_DEBUG'   ,
    true, false     )
;
```

### Option forwarding

Any option supported by the `add()` method can also be passed through the `update()` method and forwarded along when the config does not exist.

For example, you want to update the `FOO` constant in-place if it exists, otherwise it should be added to a special location:

```php
$config_transformer->update( 'constant', 'FOO', 'bar', array( 'anchor' => '/** My special location' ) );
```

Which has the same effect as the long-form logic:

```php
if ( $config_transformer->exists( 'constant', 'FOO' ) ) {
    $config_transformer->update( 'constant', 'FOO', 'bar' );
} else {
    $config_transformer->add( 'constant', 'FOO', 'bar', array( 'anchor' => '/** My special area' ) );
}
```

Of course the exception to this is if you are using the `add => false` option, in which case the update will return `false` and no config will be added.

### Known issues

1. Regex will only match one config definition per line.

**CORRECT**
```php
define( 'WP_DEBUG', true );
define( 'WP_SCRIPT_DEBUG', true );
$table_prefix = 'wp_';
$my_var = 'foo';
```

**INCORRECT**
```php
define( 'WP_DEBUG', true ); define( 'WP_SCRIPT_DEBUG', true );
$table_prefix = 'wp_'; $my_var = 'foo';
```

2. If the third argument in `define()` is used, it _must_ be a boolean.

**CORRECT**
```php
define( 'WP_DEBUG', true, false );
define( 'WP_DEBUG', true, FALSE );
define( 'foo', true, true );
define( 'foo', true, TRUE );
```

**INCORRECT**
```php
define( 'WP_DEBUG', true, 0 );
define( 'WP_DEBUG', true, 'yes' );
define( 'WP_DEBUG', true, 'this comma, will break everything' );
```

## Testing

```bash
$ composer global require phpunit/phpunit
$ composer install
$ phpunit
```
