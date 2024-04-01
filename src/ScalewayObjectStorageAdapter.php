<?php

namespace calips\scaleway_object_storage;

use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Visibility;

class ScalewayObjectStorageAdapter extends AwsS3V3Adapter
{
    public function visibility(string $path): FileAttributes
    {
        // R2 assets are always private
        return new FileAttributes($path, null, Visibility::PRIVATE);
    }
}
