<?php

namespace Zettr\Exception;

class HandlerCheckFailed extends CheckFailed {

    /**
     * @var \Zettr\Handler\HandlerInterface
     */
    protected $handler;

    public function setHandler(\Zettr\Handler\HandlerInterface $handler) {
        $this->handler = $handler;
    }

}