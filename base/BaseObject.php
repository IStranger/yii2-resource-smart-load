<?php

namespace istranger\rSmartLoad\base;

/**
 * Base object with common methods
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load       Yii 2.0.x ext
 * @link    https://github.com/IStranger/yii-resource-smart-load        Yii 1.1.x ext
 */
class BaseObject
{
    /**
     * @return string Name of current class
     */
    static function className()
    {
        return get_called_class();
    }

    /**
     * Sets properties values
     *
     * @param array $propValues Array of properties values in format: ['propName' => 'propValue']
     */
    public function setProperties($propValues)
    {
        foreach ($propValues as $propName => $propValue) {
            if (property_exists($this, $propName)) {
                $this->{$propName} = $propValue;
            } else {
                $this->throwException('Property "%propName%" does not exist in class "%className%"', array(
                    '%propName%'  => $propName,
                    '%className%' => static::className(),
                ));
            }
        }
    }

    /**
     * Throws exception with given message
     *
     * @param string $msg
     * @param array  $params   Array of params, which will be replaced
     *                         in given string (via {@link strtr})
     * @throws \Exception
     */
    protected static function throwException($msg = 'ResourceSmartLoad exception', $params = array())
    {
        throw new \Exception(strtr($msg, $params));
    }
}