<?php

namespace Zettr\Handler;

use Zettr\Message;

/**
 * Copy file
 *
 * Parameters
 * - param1: targetFile
 * - param2: not used
 * - param3: not used
 *
 * @author Fabrizio Branca
 * @since 2014-08-19
 */
class CopyFile extends AbstractHandler {

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply() {

        // let's use some speaking variable names... :)
        $sourceFile = $this->value;
        $targetFile = $this->param1;

        if (empty($sourceFile)) {
            $this->setStatus(HandlerInterface::STATUS_SKIPPED);
            return true;
        }

        if (!is_file($sourceFile)) {
            throw new \Exception(sprintf('Source file "%s" does not exist', $targetFile));
        }

        if (is_file($targetFile) && (md5_file($targetFile) == md5_file($sourceFile))){
            $this->setStatus(HandlerInterface::STATUS_ALREADYINPLACE);
            $this->addMessage(new Message(
                sprintf('Files "%s" and "%s" are identical', $sourceFile, $targetFile),
                Message::SKIPPED
            ));
        } else {
            $res = copy($sourceFile, $targetFile);
            if ($res) {
                $this->setStatus(HandlerInterface::STATUS_DONE);
                $this->addMessage(new Message(
                    sprintf('Successfully copied file "%s" to "%s"', $sourceFile, $targetFile),
                    Message::OK
                ));
            } else {
                $this->setStatus(HandlerInterface::STATUS_ERROR);
                $this->addMessage(new Message(
                    sprintf('Error while copying file "%s" to "%s"', $sourceFile, $targetFile),
                    Message::ERROR
                ));
            }
        }

        return true;
    }


}