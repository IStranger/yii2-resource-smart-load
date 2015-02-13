<?php

namespace istranger\rSmartLoad\base;

/**
 * Base class for read properties of current request (method, http headers, cookie...)
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 */
abstract class RequestReader extends BaseObject
{
    /**
     * @return bool Returns =TRUE, if current request via AJAX
     */
    abstract public function getIsAjax();

    /**
     * @return string Method of current request (GET, POST, ...)
     */
    abstract public function getMethod();

    /**
     * @return string Requested URL
     */
    abstract public function getCurrentURL();

    /**
     * @return string Referrer URL (from which came request)
     */
    abstract public function getReferrer();

    /**
     * Returns value of HTTP header of <b><u>current</u></b> request (which has been sent from client to server). <br/>
     * By default, server stores these headers in {@link $_SERVER}.
     *
     * @param string $name            Name of header (case insensitive)
     * @param string $webserverPrefix Prefix of header names in array $_SERVER (it depends on the web server
     *                                configuration)
     * @return string                 Value of given HTTP header. If not found = null
     */
    abstract public function getHttpHeader($name, $webserverPrefix = 'HTTP_');

    /**
     * Returns cookie value (by default attribute "value").<br/>
     * To access the values of other attributes, use parameter $attribute (possible value = name, value, domain, path,
     * expire, secure)
     *
     * @param string $cookieName Cookie name
     * @param mixed  $default    Default value, which returned if given cookie not found
     * @param string $attribute  Property of cookie object. Available properties see above
     * @return string|null       Value of cookie (given property)
     */
    abstract public function getCookieValue($cookieName, $default = null, $attribute = 'value');

    /**
     * Extracts value of "client" variable from HTTP headers of current request.
     * If HTTP header not found, checks cookie (with the same name). <br/>
     * Works only for AJAX requests.
     *
     * @param string $name Name of "client" variable
     * @return string      Value of given variable. If not found, returns = null
     * @see Request::_getHttpHeader
     */
    public function getClientVar($name)
    {
        if (!$this->getIsAjax()) {
            return null;
        }
        $name = 'clientvar' . $name;
        $fromHeader = $this->getHttpHeader($name);
        $fromCookie = $this->getCookieValue($name);

        if ($fromHeader && $fromCookie && ($fromHeader !== $fromCookie)) { // По разным каналам должны прийти одинаковые данные (cookie - запасной)
            static::throwException('Error in method %className%::getClientVar >> from client obtained different data ' .
                'on the different channels (http-headers and cookie).', array('%className%' => static::className())
            );
        }
        return $fromHeader ? $fromHeader : $fromCookie;
    }
}