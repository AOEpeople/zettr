<?php

namespace Zettr;

/**
 * Div
 *
 * @author Fabrizio Branca
 */
class Div {

    /**
     * Trim explode
     *
     * @param string	$delim	Delimiter string to explode with
     * @param string	$string	The string to explode
     * @param boolean	$removeEmptyValues	If set, all empty values will be removed in output
     * @return array
     * @see TYPO3
     */
    static public function trimExplode($delim, $string, $removeEmptyValues = FALSE) {
        $explodedValues = explode($delim, $string);
        $result = array_map('trim', $explodedValues);
        if ($removeEmptyValues) {
            $temp = array();
            foreach ($result as $value) {
                if ($value !== '') {
                    $temp[] = $value;
                }
            }
            $result = $temp;
        }
        return $result;
    }

}
