# Scaleway Object Storage plugin for Craft CMS

[Scaleway Object Storage](https://www.scaleway.com/en/object-storage//) filesystem for Craft CMS

## Requirements

This plugin requires Craft CMS ^4.0.0-beta.1 and PHP ^8.0.2

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell composer to load the plugin:

        composer require calips-labs/scaleway-object-storage

3. Navigate to Settings -> Plugins and click the "Install" button for Scaleway Object Storage.

## API keys

You can create an API key in the [Scaleway console](https://console.scaleway.com/iam/api-keys).
An API key consists of an Access Key ID and a Secret Access Key.

When you configure an API key, make sure that it has access to the bucket you want to use.
At least the following permissions are
needed: `ObjectStorageBucketsRead`, `ObjectStorageObjectsRead`, `ObjectStorageObjectsWrite`, `ObjectStorageObjectsDelete`.

## Configuring a new filesystem

1. Navigate to Settings -> Filesystems and click the "New Filesystem" button.
2. Select "Scaleway Object Storage" from the "Filesystem Type" dropdown.
3. Select the region for your bucket.
4. Enter your Access Key ID, and Secret Access Key (it's recommended to store these in your `.env` file and
   reference the environment variables here).
5. Hit Refresh to load the bucket list, or choose the Manual option and enter the bucket name. (The bucket name can also
   be stored in your `.env` file and be referenced here.)
6. Save the filesystem.

## Misc

[Open an Issue](https://github.com/calips-labs/scaleway-object-storage/issues) if you encounter any problems or have
suggestions.

## Acknowledgements

Based on the excellent [Cloudflare R2 plugin](https://plugins.craftcms.com/cloudflare-r2)
by [Jarrod D Nix](https://jarrodnix.dev)
