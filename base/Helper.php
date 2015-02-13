<?php

namespace istranger\rSmartLoad\base;

/**
 * Helper class for RSmartLoadClientScript.
 * Contains common helper functions for data access/array manipulation.
 *
 * @author  G.Azamat <m@fx4web.com>
 * @link    http://fx4.ru/
 * @link    https://github.com/IStranger/yii2-resource-smart-load
 */
class Helper extends BaseObject
{
    /**
     * Evaluates the value of the specified attribute for the given object or array.
     * The attribute name can be given in a path syntax. For example, if the attribute
     * is "author.firstName", this method will return the value of "$object->author->firstName"
     * or "$array['author']['firstName']".
     * A default value (passed as the last parameter) will be returned if the attribute does
     * not exist or is broken in the middle (e.g. $object->author is null).
     *
     * Anonymous function could also be used for attribute calculation as follows:
     * <code>
     * $taskClosedSecondsAgo = self::value($closedTask,function($model) {
     *    return time() - $model->closed_at;
     * });
     * </code>
     * Your anonymous function should receive one argument, which is the object, the current
     * value is calculated from.
     *
     * @param mixed $object       This can be either an object or an array.
     * @param mixed $attribute    the attribute name (use dot to concatenate multiple attributes)
     *                            or anonymous function (PHP 5.3+). Remember that functions created by "create_function"
     *                            are not supported by this method. Also note that numeric value is meaningless when
     *                            first parameter is object typed.
     * @param mixed $defaultValue the default value to return when the attribute does not exist.
     * @return mixed the attribute value.
     */
    public static function value($object, $attribute, $defaultValue = null)
    {
        if (is_scalar($attribute)) {
            foreach (explode('.', $attribute) as $name) {
                if (isset($object->$name)) {
                    $object = $object->$name;
                } elseif (isset($object[$name])) { //A::isArrayable($object) AND
                    $object = $object[$name];
                } else {
                    return $defaultValue;
                }
            }
            return $object;
        } elseif (is_callable($attribute)) {
            if ($attribute instanceof \Closure) {
                $attribute = \Closure::bind($attribute, $object);
            }
            return call_user_func($attribute, $object);
        } else {
            return null;
        }
    }

    /**
     * Removes item (with specified key) from given array, and return it.
     * If item not found, returns $defaultValue.
     *
     * @param  array  &$array           Source array
     * @param  string $key              Item key
     * @param  mixed  $defaultValue     Default value, if key not found
     * @return mixed                    Item value, or default value
     */
    public static function pullFromArray(array &$array, $key, $defaultValue = null)
    {
        $value = self::value($array, $key, $defaultValue);
        self::_deleteFromArray($array, $key);
        return $value;
    }

    /**
     * Filters values of given array by $callback.
     * If $callback function return true, current element included in result array
     *
     * <code>
     * // Select only elements with height>$data
     * $items = A::filter($a, function($key, $val, $data){{
     *      return $val['height'] > $data;
     * }, $data);
     * </code>
     *
     * @param array    $array
     * @param callable $callback
     * @param null     $data
     * @return array
     * @param boolean  $bind
     * @see  firstByFn(), lastByFn()
     * @uses execute()
     */
    public static function filterByFn(array $array, callable $callback, $data = null, $bind = true)
    {
        $handler = function (&$array, $key, $item, $result) {
            if ($result) {
                $array[$key] = $item;
            }
        };
        return self::_execute($array, $callback, $handler, $data, $bind);
    }

    /**
     * Returns a new array built using a callback.
     * <code>
     *
     * $array = A::createByFn($array, function($key,$item) {  // array
     *      return [$key.' '.count($item), $item];
     * });
     * // Result: $array = ['key1 cnt1'=>$item1,'key1 cnt2'=>$item2,...];
     *
     * $objects = A::createByFn($objects, function() {    // if array of objects
     *      return [$this->name.'-'.$this->id, $this];
     * });
     * </code>
     *
     * @param  array    $array
     * @param  callable $callback
     * @param  array    $data
     * @return array
     * @uses execute()
     */
    public static function createByFn($array, callable $callback, $data = null)
    {
        $handler = function (&$array, $key, $item, $result) {
            list($newKey, $newValue) = $result;
            $array[$newKey] = $newValue;
            return $result;
        };
        return self::_execute($array, $callback, $handler, $data, true);
    }

    /**
     * Returns result execute a $callback over each item of array.
     * Result prepare with execute $handler.
     *
     * @param array    $array
     * @param callable $callback
     * @param callable $handler
     * @param mixed    $data
     * @param boolean  $bind
     * @return array
     */
    private static function _execute($array, callable $callback, callable $handler, $data = null, $bind = false)
    {
        $resultValue = array();
        foreach ($array as $key => $item) {
            if (is_object($item)) {
                $item = clone $item;
                if ($callback instanceof \Closure and $bind) {
                    $callback = \Closure::bind($callback, $item);
                }
            }
            $result = call_user_func_array($callback, array($key, &$item, $data));
            if (call_user_func_array($handler, array(&$resultValue, $key, $item, $result)) === false) {
                break;
            }
        }
        return $resultValue;
    }

    /**
     * Remove an array item from a given array using key(path).
     *
     * @param  array        &$array
     * @param  string|array $key
     * @return array
     */
    private static function _deleteFromArray(array &$array, $key)
    {
        $original =& $array;
        $parts = explode('.', $key);
        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (!isset($array[$part]) OR !is_array($array[$part])) {
                return $array;
            }
            $array =& $array[$part];
        }
        unset($array[array_shift($parts)]);
        return $original;
    }
}