<?php

namespace Zettr\Handler;

use Zettr\Message;

/**
 * Abstract handler class
 *
 * @author Fabrizio Branca
 * @since 2012-09-20
 */
abstract class AbstractHandler implements HandlerInterface {

    /**
     * @var array
     */
    protected $messages = array();

    protected $param1;
    protected $param2;
    protected $param3;
    protected $value;

    /**
     * Used by the handler to find the config value(s)
     *
     * @var string
     */
    protected $configKey;

    /**
     * @var bool $ignore errors
     */
    protected $ignoreErrors = false;



    protected $status = HandlerInterface::STATUS_NOTEXECUTED;


    /**
     * Protected method that actually applies the settings. This method is implemented in the inheriting classes and
     * called from ->apply
     *
     * @return bool
     */
    abstract protected function _apply();

    /**
     * Apply setting
     *
     * @return bool
     */
    public function apply() {
        try {
            if (strtolower(trim($this->value)) == '--skip--') {
                $this->setStatus(HandlerInterface::STATUS_SKIPPED);
                $this->addMessage(new Message('Skipped because of --skip-- value'), Message::SKIPPED);
                $result = true;
            } else {
                $this->setStatus(HandlerInterface::STATUS_RUNNING);
                $result = $this->_apply();
                if ($this->getStatus() == HandlerInterface::STATUS_RUNNING) {
                    $this->setStatus(HandlerInterface::STATUS_FINISHED);
                }
            }
            return $result;
        } catch (\Exception $e) {
            if ($this->ignoreErrors) {
                $this->setStatus(HandlerInterface::STATUS_IGNORED_ERROR);
                $this->addMessage(new Message(
                    '[IGNORED] ' . $e->getMessage(),
                    Message::ERROR
                ));
                return true;
            } else {
                $this->setStatus(HandlerInterface::STATUS_ERROR);
                $this->addMessage(new Message(
                    ($this->ignoreErrors ? '[IGNORED] ' : '') . $e->getMessage(),
                    Message::ERROR
                ));
                return false;
            }
        }
    }

    /**
     * Extract settings
     *
     * @return bool
     */
    public function extractSettings() {

        // TODO add messaging here to wrap the raw csv result
        echo $this->_extract();
        return true;
    }

    /**
     * Protected method that actually extracts the settings. This method is implemented in the inheriting classes and
     * called from ->extractSettings
     *
     * @return bool
     */
    protected function _extract()
    {
        echo 'No extract method implemented for this handler' . PHP_EOL;
        return true;
    }

    /**
     * @return mixed
     */
    protected function _register() {
        // nothing happens here by default
    }

    public function register() {
        return $this->_register();
    }

    public function setParam1($param1) {
        $this->param1 = $param1;
    }

    public function setParam2($param2) {
        $this->param2 = $param2;
    }

    public function setParam3($param3) {
        $this->param3 = $param3;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function setConfigKey($value) {
        $this->configKey = $value;
    }

    public function getParam1() {
        return $this->param1;
    }

    public function getParam2() {
        return $this->param2;
    }

    public function getParam3() {
        return $this->param3;
    }

    public function getValue() {
        return $this->value;
    }

    public function getConfigKey() {
        return $this->configKey;
    }

    /**
     * Add message
     *
     * @param $message
     */
    protected function addMessage($message) {
        $this->messages[] = $message;
    }

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Get a speaking label
     *
     * @return string
     */
    public function getLabel() {
        $label = get_class($this);
        $label .= sprintf("('%s', '%s', '%s')",
            $this->formatParam($this->param1),
            $this->formatParam($this->param2),
            $this->formatParam($this->param3)
        );
        $label .= ' = ';
        $label .= $this->value;
        return $label;
    }

    protected function formatParam($param) {
        if (is_null($param)) {
            $param = 'null';
        }
        return $param;
    }

    protected function setStatus($status) {
        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }

    /**
     * @return boolean
     */
    public function getIgnoreErrors() {
        return $this->ignoreErrors;
    }

    /**
     * @param boolean $ignoreErrors
     */
    public function setIgnoreErrors($ignoreErrors) {
        $this->ignoreErrors = $ignoreErrors;
    }

}
