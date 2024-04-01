<?php
/**
 * @link https://jarrodnix.dev/
 * @copyright Copyright (c) Jarrod D Nix
 * @license MIT
 */

namespace calips\scaleway_object_storage\migrations;

use Craft;
use calips\scaleway_object_storage\Fs;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * Installation Migration
 *
 * @author Jarrod D Nix
 * @since 1.0
 */
class Install extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
