<?php

declare(strict_types=1);
/**
 * @link https://jarrodnix.dev/
 * @copyright Copyright (c) Jarrod D Nix
 * @license MIT
 */

namespace calips\scaleway_object_storage;

use Aws\Credentials\Credentials;
use Aws\Rekognition\RekognitionClient;
use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\flysystem\base\FlysystemFs;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use DateTime;
use InvalidArgumentException;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;

/**
 * Class Fs
 *
 * @property mixed $settingsHtml
 * @property string $rootUrl
 * @author Jarrod D Nix
 * @since 1.0
 */
class Fs extends FlysystemFs
{
    // Constants
    // =========================================================================

    public const STORAGE_STANDARD = 'STANDARD';
    public const STORAGE_REDUCED_REDUNDANCY = 'REDUCED_REDUNDANCY';
    public const STORAGE_STANDARD_IA = 'STANDARD_IA';

    /**
     * Cache key to use for caching purposes
     */
    public const CACHE_KEY_PREFIX = 'scaleway.';

    /**
     * Cache duration for access token
     */
    public const CACHE_DURATION_SECONDS = 3600;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Scaleway Object Storage';
    }

    // Properties
    // =========================================================================

    /**
     * @var string Subfolder to use
     */
    public string $subfolder = '';

    /**
     * @var string Access key ID
     */
    public string $keyId = '';

    /**
     * @var string Access key secret
     */
    public string $secret = '';

    /**
     * @var string Bucket selection mode ('choose' or 'manual')
     */
    public string $bucketSelectionMode = 'choose';

    /**
     * @var string Bucket to use
     */
    public string $bucket = '';

    /**
     * @var string Region to use
     */
    public string $region = '';

    /**
     * @var string Cache expiration period.
     */
    public string $expires = '';

    /**
     * @var bool Set ACL for Uploads
     */
    public bool $makeUploadsPublic = true;

    /**
     * @var string S3 storage class to use.
     * @deprecated in 1.1.1
     */
    public string $storageClass = '';

    /**
     * @var bool Whether the specified sub folder should be added to the root URL
     */
    public bool $addSubfolderToRootUrl = true;

    /**
     * @var array A list of paths to invalidate at the end of request.
     */
    protected array $pathsToInvalidate = [];

    // Public Methods
    // =========================================================================

	/**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        if (isset($config['manualBucket'])) {
            if (isset($config['bucketSelectionMode']) && $config['bucketSelectionMode'] === 'manual') {
                $config['bucket'] = ArrayHelper::remove($config, 'manualBucket');
            } else {
                unset($config['manualBucket'], $config['manualRegion']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'keyId',
                'secret',
                'bucket',
                'subfolder',
                'region',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['keyId', 'secret', 'region', 'bucket'], 'required'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('scaleway-object-storage/fsSettings', [
            'fs' => $this,
            'periods' => array_merge(['' => ''], Assets::periodList()),
        ]);
    }

    /**
     * Get the bucket list using the specified credentials.
     *
	 * @param string|null $keyId The key ID
     * @param string|null $secret The key secret
     * @param string|null $region The region
     * @return array
     * @throws InvalidArgumentException
     */
    public static function loadBucketList(?string $keyId, ?string $secret, ?string $region): array
    {
        $config = self::buildConfigArray($keyId, $secret, $region);

        $client = static::client($config);

        $objects = $client->listBuckets();

        if (empty($objects['Buckets'])) {
            return [];
        }

        $buckets = $objects['Buckets'];
        $bucketList = [];

        foreach ($buckets as $bucket) {
            $bucketList[] = [
                'bucket' => $bucket['Name'],
                'urlPrefix' => 'https://' . $bucket['Name'] . '.s3.' . $region . '.scw.cloud/',
            ];
        }

        return $bucketList;
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        $rootUrl = parent::getRootUrl();

        if ($rootUrl) {
            $rootUrl .= $this->_getRootUrlPath();
        }

        return $rootUrl;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return FilesystemAdapter
     */
    protected function createAdapter(): FilesystemAdapter
    {
        $client = static::client($this->_getConfigArray(), $this->_getCredentials());
        return new ScalewayObjectStorageAdapter($client, App::parseEnv($this->bucket), $this->_subfolder(), new PortableVisibilityConverter($this->visibility()), null, [], false);
    }

    /**
     * Get the Amazon S3 client.
     *
     * @param array $config client config
     * @param array $credentials credentials to use when generating a new token
     * @return S3Client
     */
    protected static function client(array $config = [], array $credentials = []): S3Client
    {
        if (!empty($config['credentials']) && $config['credentials'] instanceof Credentials) {
            $config['generateNewConfig'] = static function() use ($credentials) {
                $args = [
                    $credentials['keyId'],
                    $credentials['secret'],
                    $credentials['region'],
                    true,
                ];
                return call_user_func_array(self::class . '::buildConfigArray', $args);
            };
        }

        return new S3Client($config);
    }

    /**
     * @inheritdoc
     */
    protected function addFileMetadataToConfig(array $config): array
    {
        if (!empty($this->expires) && DateTimeHelper::isValidIntervalString($this->expires)) {
            $expires = new DateTime();
            $now = new DateTime();
            $expires->modify('+' . $this->expires);
            $diff = (int)$expires->format('U') - (int)$now->format('U');
            $config['CacheControl'] = 'max-age=' . $diff;
        }

        return parent::addFileMetadataToConfig($config);
    }

    /**
     * @inheritdoc
     */
    protected function invalidateCdnPath(string $path): bool
    {
        return true;
    }

    /**
     * Purge any queued paths from the CDN.
     */
    public function purgeQueuedPaths(): void
    {
        return;
    }

    /**
     * Build the config array based on a keyID and secret
     *
     * @param ?string $keyId The key ID
     * @param ?string $secret The key secret
     * @param bool $refreshToken If true will always refresh token
     * @return array
     */
    public static function buildConfigArray(?string $keyId = null, ?string $secret = null, ?string $region = null, bool $refreshToken = false): array
    {
		$config = [
            'region' => $region,
            'endpoint' => 'https://s3.' . $region . '.scw.cloud/',
            'version' => 'latest',
			'credentials' => new Credentials($keyId, $secret)
        ];

        return $config;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the parsed subfolder path
     *
     * @return string
     */
    private function _subfolder(): string
    {
        if ($this->subfolder && ($subfolder = rtrim(App::parseEnv($this->subfolder), '/')) !== '') {
            return $subfolder . '/';
        }

        return '';
    }

    /**
     * Returns the root path for URLs
     *
     * @return string
     */
    private function _getRootUrlPath(): string
    {
        if ($this->addSubfolderToRootUrl) {
            return $this->_subfolder();
        }
        return '';
    }

    /**
     * Get the config array for AWS Clients.
     *
     * @return array
     */
    private function _getConfigArray(): array
    {
        $credentials = $this->_getCredentials();

        return self::buildConfigArray($credentials['keyId'], $credentials['secret'], $credentials['region']);
    }

    /**
     * Return the credentials as an array
     *
     * @return array
     */
    private function _getCredentials(): array
    {
        return [
            'keyId' => App::parseEnv($this->keyId),
            'secret' => App::parseEnv($this->secret),
            'region' => App::parseEnv($this->region),
        ];
    }

    /**
     * Returns the visibility setting for the Fs.
     *
     * @return string
     */
    protected function visibility(): string
    {
        return $this->makeUploadsPublic ? Visibility::PUBLIC : Visibility::PRIVATE;
    }
}
