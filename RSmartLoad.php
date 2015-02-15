<?php

namespace istranger\rSmartLoad;

use istranger\rSmartLoad\base;

/**
 * Yii2 RSmartLoad class
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 * @since   2.0.2
 */
class RSmartLoad extends base\RSmartLoad
{
    /**
     * @inheritdoc
     */
    protected function publishExtensionResources()
    {
        /** @var View $resourceManager */
        $resourceManager = $this->getResourceManager();

        // Initialization of extension resources
        $assetsExt = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'assets';
        $assetsPaths = \Yii::$app->getAssetManager()->publish($assetsExt); // [0] - path, [1] - URL
        $resourceManager->registerJsFile($assetsPaths[1] . '/resource-smart-load.js', [
            'depends' => [
                \yii\web\JqueryAsset::className(),
                \yii\web\YiiAsset::className()
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function writeLog($msg)
    {
        \Yii::getLogger()->log($msg, \yii\log\Logger::LEVEL_INFO, 'resource-smart-load');
    }

    /**
     * @inheritdoc
     */
    protected function jsGlobalObjPublicPath()
    {
        return 'yii.resourceSmartLoad';
    }
}