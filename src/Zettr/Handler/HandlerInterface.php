<?php


namespace Zettr\Handler;

/**
 * Abstract handler class
 */
interface HandlerInterface {

    CONST STATUS_NOTEXECUTED = 'NOT_EXECUTED';
    CONST STATUS_RUNNING = 'RUNNING';
    CONST STATUS_FINISHED = 'FINISHED'; // we don't have any detailed information besides that it finished without exeptions here
    CONST STATUS_SKIPPED = 'SKIPPED';
    CONST STATUS_ALREADYINPLACE = 'ALREADY IN PLACE';
    CONST STATUS_SUBJECTNOTFOUND = 'SUBJECT_NOT_FOUND';
    CONST STATUS_DONE = 'DONE';
    CONST STATUS_ERROR = 'ERROR';
    CONST STATUS_IGNORED_ERROR = 'IGNORED_ERROR';

    /**
     * Apply setting
     *
     * @return bool
     */
    public function apply();

    /**
     * This will be called while registering the handler
     *
     * @return mixed
     */
    public function register();

    public function setParam1($param1);

    public function setParam2($param2);

    public function setParam3($param3);

    public function getParam1();

    public function getParam2();

    public function getParam3();

    public function setValue($value);

    public function getValue();

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages();

    /**
     * Get a speaking label
     *
     * @return string
     */
    public function getLabel();

    public function getStatus();

}