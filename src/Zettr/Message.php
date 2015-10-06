<?php

namespace Zettr;

class Message {

    const ERROR = 0;
    const WARNING = 1;
    const OK = 2;
    const SKIPPED = 3;
    const INFO = 4;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $level;

    /**
     * Constructor
     *
     * @param string $text
     * @param int $level
     */
    public function __construct($text, $level=self::OK) {
        $this->text = $text;
        $this->level = $level;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get level
     * see class constants
     *
     * @return int
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Get colored text message
     *
     * @return string
     * @throws \Exception
     */
    public function getColoredText() {

        $color = null;
        switch ($this->getLevel()) {
            case self::OK: $color = 'info'; break;
            case self::WARNING: $color = 'comment'; break;
            case self::SKIPPED: $color = 'comment'; break;
            case self::ERROR: $color = 'error'; break;
            case self::INFO: $color = null; break;
            default: throw new \Exception('Invalid level');
        }

        if (is_null($color)) {
            return $this->getText();
        } else {
            return sprintf('<%1$s>%2$s</%1$s>', $color, $this->getText());
        }
    }

}