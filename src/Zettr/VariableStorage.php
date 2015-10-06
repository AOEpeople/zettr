<?php

namespace Zettr;

/**
 * Class Est_VariableStorage
 *
 * @author Fabrizio Branca
 * @since 2013-11-08
 */
class VariableStorage {

    /**
     * Value storage
     *
     * @var array
     */
    protected static $storage = array();

    /**
     * Add value
     *
     * @param $name
     * @param $value
     */
    public static function add($name, $value) {
        self::$storage[$name] = $value;
    }

    /**
     * Get value
     *
     * @param $name
     * @return bool
     */
    public static function get($name) {
        return isset(self::$storage[$name]) ? self::$storage[$name] : false;
    }

}
