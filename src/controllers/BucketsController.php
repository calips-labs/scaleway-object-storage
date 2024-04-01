<?php

namespace calips\scaleway_object_storage\controllers;

use Craft;
use calips\scaleway_object_storage\Fs;
use craft\helpers\App;
use craft\web\Controller as BaseController;
use yii\web\Response;

/**
 * This controller provides functionality to load data from Cloudflare.
 *
 * @author Jarrod D Nix
 * @since 1.0
 */
class BucketsController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->defaultAction = 'load-bucket-data';
    }

    /**
     * Load bucket data for specified credentials.
     *
     * @return Response
     */
    public function actionLoadBucketData(): Response
    {
		$this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $keyId = App::parseEnv($request->getRequiredBodyParam('keyId'));
        $secret = App::parseEnv($request->getRequiredBodyParam('secret'));
        $region = App::parseEnv($request->getRequiredBodyParam('region'));

        try {
			return $this->asJson([
                'buckets' => Fs::loadBucketList($keyId, $secret, $region),
            ]);
        } catch (\Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }
}
