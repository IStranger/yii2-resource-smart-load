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
        return $this->_req()->isAjax;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return $this->_req()->method;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentURL()
    {
        return $this->_req()->absoluteUrl;
    }

    /**
     * @inheritdoc
     */
    public function getReferrer()
    {
        return $this->_req()->referrer;
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
        $this->_req()->enableCookieValidation = false;
        $cookieObj = $this->_req()->cookies->get($cookieName);
        $this->_req()->enableCookieValidation = true;

        return ArrayHelper::getValue($cookieObj, $attribute, $default);
    }

    private function _req()
    {
        return \Yii::$app->request;
    }
}