# Scaleway Object Storage plugin for Craft CMS

[Scaleway Object Storage](https://www.scaleway.com/en/object-storage//) filesystem for Craft CMS

## Requirements

This plugin requires Craft CMS ^4.0.0-beta.1 and PHP ^8.0.2

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require calips-labs/scaleway-object-storage

3. Navigate to Settings -> Plugins and click the "Install" button for Scaleway Object Storage.

## Configuring filesystem

1. Navigate to Settings -> Filesystems and click the "New Filesystem" button.
2. Select "Scaleway Object Storage" from the "Filesystem Type" dropdown.
3. Enter your Account ID, Access Key ID, and Secret Access Key (it's recommended to store these in your `.env` file and
   reference the environment variables here).
4. Hit Refresh to load the bucket list, or choose the Manual option and enter the bucket name (again, you can store this
   in your `.env` file and reference the environment variable).
5. Optionally add a Subfolder, determine whether or not to add the Subfolder to the Base URL, and set the Cache Control
   duration.

## Misc

[Open an Issue](https://github.com/calips-labs/scaleway-object-storage/issues) if you encounter any problems or have
suggestions.

## Acknowledgements

Based on the excellent [Cloudflare R2 plugin](https://plugins.craftcms.com/cloudflare-r2)
by [Jarrod D Nix](https://jarrodnix.dev)
