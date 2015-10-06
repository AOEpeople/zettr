<?php

namespace Zettr\Handler;

use Zettr\VariableStorage;
use Zettr\Message;

/**
 * Store a variable in memory for later use in values
 *
 * Parameters
 * - param1: variable name
 * - param2: not used
 * - param3: not used
 *
 * @author Fabrizio Branca
 * @since 2013-11-08
 */
class SetVar extends AbstractHandler {

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _register() {

        // let's use some speaking variable names... :)
        $variableName = $this->param1;
        $value = $this->value;

        VariableStorage::add($variableName, $value);

        $this->addMessage(new Message(sprintf('Storing value "%s" for variable "%s".', $value, $variableName), Message::OK));
    }

    protected function _apply() {
        return true;
    }

}