# Roundcube plugin geolocation

Roundcube plugin to provide geolocation utilities.

The plugin also allows the administrator to configure which networks are internal to the organization and give them a specific description. IPs from internal networks will use that description, instead of being looked up for its geolocation information.

This plugin is not meant to be configured by users, only by the Roundcube Webmail administrator via configuration file.

Stable versions of the plugin are available from the [Roundcube plugin repository][rcplugrepo] (for 1.0 and above) or the [releases section][releases] of the GitHub repository.

**NOTES:**
- *This plugins only provides geolocation functions to be used by other plugins. It does not need to be enabled in the Roundcube config file.*
- Support for IPv6 was not tested.

**WHY:** I have written other Roundcube plugins to use within my organization. Some of them were using geolocation functions with duplicated configs, so i thought of moving them into a plugin, with centralized config. For now, the only published plugin that uses the geolocation plugin is a very small one: [Blacklist][blacklist]. I plan to release another plugin that shows the login info for the user, with history and all. It's already in production in my organization (i use that info for my anti-spam toolbox), i just have to rewrite some bits of it before publishing.


## Requirements

- [PHP GeoIP extension][phpgeoip] if using the `system` method to fetch geolocation information (recommended).


## Installation

#### Installation with composer

Add the plugin to your `composer.json` file:

    "require": {
        (...)
        "dsoares/geolocation": "~0.1"
    }

And run `$ composer update [--your-options]`.

Copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.

#### Manual Installation

Place this directory under your Rouncdube `plugins/` folder, copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.


## Configuration

- **$config['geolocation_fetch_method']** - can be one of `system` (default), `geoplugin`, `geoiptool`.

- **$config['geolocation_internal_networks']** - `array` defining the organization internal networks with descriptions.

See the `config.inc.php.dist` for more information about the fetch methods and on how to declare your organization internal networks.

## License

This plugin is released under the [GNU General Public License Version 3+][gpl].

## Contact

Comments and suggestions are welcome!

Email: [Diana Soares][dsoares]

[rcplugrepo]: https://plugins.roundcube.net/packages/dsoares/geolocation
[releases]: https://github.com/dsoares/roundcube-geolocation/releases
[phpgeoip]: https://php.net/manual/en/book.geoip.php
[gpl]: https://www.gnu.org/licenses/gpl.html
[dsoares]: mailto:diana.soares@gmail.com
[blacklist]: https://github.com/dsoares/roundcube-blacklist
