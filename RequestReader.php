<?php

namespace istranger\rSmartLoad;

use istranger\rSmartLoad\base;
use yii\helpers\ArrayHelper;

/**
 * Yii2 class for read properties of current request (method, http headers, cookie...)
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 * @since   2.0.2
 */
class RequestReader extends base\RequestReader
{
    /**
     * @inheritdoc
     */
    public function getIsAjax()
    {
        return \Yii::$app->request->isAjax;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return \Yii::$app->request->method;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentURL()
    {
        return \Yii::$app->request->absoluteUrl;
    }

    /**
     * @inheritdoc
     */
    public function getReferrer()
    {
        return \Yii::$app->request->referrer;
    }

    /**
     * @inheritdoc
     */
    public function getHttpHeader($name, $webserverPrefix = 'HTTP_')
    {
        $name = str_replace(array('-', ' '), '_', $name);
        return ArrayHelper::getValue($_SERVER, mb_strtoupper($webserverPrefix . $name));
    }

    /**
     * @inheritdoc
     */
    public function getCookieValue($cookieName, $default = null, $attribute = 'value')
    {
        return ArrayHelper::getValue(\Yii::$app->request->cookies->toArray(), $cookieName . '.' . $attribute, $default);
    }
}