<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Base Model class to handle caching
 */

abstract class ModelBase extends Model
{
	/**
 	* Constants
 	*/
	const DEFAULT_KEY_TIME = 300;

	/**
 	* Model method caching overrides
 	*/
	public static function count($parameters = null)
    {
        // Convert the parameters to an array
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if (!isset($parameters['cache'])) {
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => self::DEFAULT_KEY_TIME,
            ];
       } else if (is_int($parameters['cache'])) {
            // override the default cache time
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => $parameters['cache'],
            ];
        } else if ($parameters['cache'] === false) {
            // do not cache if the param is set to false

            unset($parameters['cache']);
        }

        return parent::count($parameters);
    }

    public static function sum($parameters = null)
    {
        // Convert the parameters to an array
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if (!isset($parameters['cache'])) {
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => self::DEFAULT_KEY_TIME,
            ];
        } else if (is_int($parameters['cache'])) {
            // override the default cache time
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => $parameters['cache'],
            ];
        } else if ($parameters['cache'] === false) {
            // do not cache if the param is set to false

            unset($parameters['cache']);
        }

        return parent::sum($parameters);
    }

    public static function find($parameters = null)
    {
        // Convert the parameters to an array
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if (!isset($parameters['cache'])) {
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => self::DEFAULT_KEY_TIME,
            ];
        } else if (is_int($parameters['cache'])) {
            // override the default cache time
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => $parameters['cache'],
            ];
        } else if ($parameters['cache'] === false) {
            // do not cache if the param is set to false

            unset($parameters['cache']);
        }

        return parent::find($parameters);
    }

    public static function findFirst($parameters = null)
    {
        // Convert the parameters to an array
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if (!isset($parameters['cache'])) {
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => self::DEFAULT_KEY_TIME,
            ];
        } else if (is_int($parameters['cache'])) {
            // override the default cache time
            $parameters['cache'] = [
                'key'      => self::_createKey($parameters),
                'lifetime' => $parameters['cache'],
            ];
        } else if ($parameters['cache'] === false) {
            // do not cache if the param is set to false

            unset($parameters['cache']);
        }

        return parent::findFirst($parameters);
    }

    /**
     * execute raw sql query
     * @param  string $sql       [sql to execute]
     * @param  string $method    [method to define resultset (fetchAll, fetchOne, fetchColumn, query, execute)]
     * @param  int    $cacheTime [time in seconds to cache the resultset]
     * @return mixed             [depends on $method]
     */
    public static function rawQuery($sql, $method, $cacheTime, $adapter = 'oltp')
    {
        $read_operation = true;

        if ($method === 'execute') {
            $db = \Phalcon\Di::getDefault()->get($adapter . '-write');
            $read_operation = false;
        } else {
            $db = \Phalcon\Di::getDefault()->get($adapter . '-read');
        }

        if ($cacheTime || $method === 'flush') {
            $cache = \Phalcon\Di::getDefault()->get('modelsCache');
            $cacheKey = self::_createRawKey($sql);

            // flush cache
            if ($method === 'flush') {
                return $cache->delete($cacheKey);
            }

            $result = $cache->get($cacheKey);
        } else {
            $result = null;
        }

        // $called_class = get_called_class();
        // $called_model = substr($called_class, strrpos($called_class, '\\') + 1);
        // $called_method = debug_backtrace()[1]['function'];

        // $logger = \Phalcon\Di::getDefault()->getShared('logger');
        // $logger->error('Using cache key ' . $cacheKey . ' for ' . $called_model . '_' . $called_method);

        if ($result === null) {
            $result = $db->$method($sql);

            if ($cacheTime && $read_operation) {
                $cache->save($cacheKey, $result, $cacheTime);
            }
        }

        return $result;
    }

	/**
 	* Private Functions
 	*/
    private static function _createKey($parameters)
    {
        $uniqueKey = [];

        foreach ($parameters as $key => $value) {
            if (is_scalar($value)) {
                $uniqueKey[] = $key . ':' . $value;
            } elseif (is_array($value)) {
                $uniqueKey[] = $key . ':[' . self::_createKey($value) . ']';
            }
        }

        //Get the model name
        $called_class = get_called_class();
        $model = substr($called_class, strrpos($called_class, '\\') + 1);
        $method = debug_backtrace()[1]['function'];

        //Split the keys into comma seperated list, sha256 for security
        $keys = hash('sha256', join(',', $uniqueKey));

        //Return the assembled key
        return $model.'_'.$method.'_'.$keys;
    }

    private static function _createRawKey($sql)
    {
        // get the model name
        $called_class = get_called_class();
        $model = substr($called_class, strrpos($called_class, '\\') + 1);
        $method = debug_backtrace()[2]['function'];

        $hash = hash('sha256', $sql);

        // return $model . '_' . $method . '_' . $hash;
        return $model . '_' . $hash;
    }

    /**
     * convert byte to appropriate unit
     * @param  integer $bytes     [source bytes]
     * @param  integer $precision [precision]
     * @param  integer $type      [type of format]
     *         1: convert automatically and attach unit
     *         2: convert automatically and return separate value & unit array
     *         3: convert value to passed unit and just return value
     * @param  string  $unit      [unit to convert to - only useful when type=3]
     * @return [type]             [description]
     */
    public function formatBytes($bytes, $precision = 2, $type = 1, $unit = false) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $formattedBytes = $bytes / pow(1024, $pow);

        switch ($type) {
            case 1:
            return round($formattedBytes, $precision) . $units[$pow];
        case 2:
            return [
                'original'  => $bytes,
                'value'     => round($formattedBytes, $precision),
                'unit'      => $units[$pow],
            ];
        case 3:
            $pow = array_search($unit, $units);
            if ($pow !== -1) {
                // unit found
                $formattedBytes = $bytes / pow(1024, $pow);
            }

            return round($formattedBytes, $precision);
        }
    }

}
