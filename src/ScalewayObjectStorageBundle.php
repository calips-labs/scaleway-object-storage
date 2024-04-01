<?php
/**
 * @link https://jarrodnix.dev/
 * @copyright Copyright (c) Jarrod D Nix
 * @license MIT
 */

namespace calips\scaleway_object_storage;

use craft\web\assets\cp\CpAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Dashboard
 */
class ScalewayObjectStorageBundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@calips/scaleway_object_storage/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/editVolume.js',
    ];
}
