<?php

namespace Zettr\Handler;

use Zettr\Message;

/**
 * Replace a value in a php array return file by an array
 *
 * Parameters
 * - param1: file
 * - param2: path
 * - param3: not used
 *
 * @author Tobias Schifftner
 */
class PhpEnvFile extends AbstractHandler {

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply() {

        // let's use some speaking variable names... :)
        $file = $this->param1;
        $expression = $this->param2;

        if (!is_file($file)) {
            throw new \Exception(sprintf('File "%s" does not exist', $file));
        }
        if (!is_writable($file)) {
            throw new \Exception(sprintf('File "%s" is not writeable', $file));
        }
        if (empty($expression)) {
            throw new \Exception('No xpath defined');
        }
        if (!empty($this->param3)) {
            throw new \Exception('Param3 is not used in this handler and must be empty');
        }

        // read file
        $returnArray = include $file;
        if ( ! is_array($returnArray)) {
            throw new \Exception(sprintf('Error while reading file "%s"', $file));
        }

        $path = (array) explode('/', $expression);
        $this->_applyValue($path, $returnArray);

        file_put_contents($file, $this->_formatter($returnArray));

        return true;
    }

    /**
     * Walks array and adds keys/replaces values
     *
     * @param $arrayKeys
     * @param $returnArray
     */
    protected function _applyValue($arrayKeys, &$returnArray)
    {
        $arrayKey = array_shift($arrayKeys);

        if(count($arrayKeys)) {
            if( ! array_key_exists($arrayKey, $returnArray) || ! is_array($returnArray[$arrayKey]) ) {
                $returnArray[$arrayKey] = array();
            }
            return $this->_applyValue($arrayKeys, $returnArray[$arrayKey]);
        }

        $returnArray[$arrayKey] = $this->value;
    }

    /**
     * Returns formatted php array code
     *
     * @param $data
     * @return string
     */
    protected function _formatter($data)
    {
        return "<?php\nreturn " . var_export($data, true) . ";\n";
    }

}