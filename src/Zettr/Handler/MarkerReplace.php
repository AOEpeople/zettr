<?php

namespace Zettr\Handler;
use Zettr\Message;

/**
 * Replace a marker in a file
 *
 * Parameters
 * - param1: file
 * - param2: marker
 * - param3: not used
 *
 * @author Fabrizio Branca
 * @since 2012-09-20
 */
class MarkerReplace extends AbstractHandler {

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply() {

        // let's use some speaking variable names... :)
        $file = $this->param1;
        $marker = $this->param2;

        if (!is_file($file)) {
            throw new \Exception(sprintf('File "%s" does not exist', $file));
        }
        if (!is_writable($file)) {
            throw new \Exception(sprintf('File "%s" is not writeable', $file));
        }
        if (empty($marker)) {
            throw new \Exception('No marker defined');
        }
        if (!empty($this->param3)) {
            throw new \Exception('Param3 is not used in this handler and must be empty');
        }

        // read file
        $fileContent = file_get_contents($file);
        if ($fileContent === false) {
            throw new \Exception(sprintf('Error while reading file "%s"', $file));
        }
        $count = 0;

        // do the replacement
        $fileContent = str_replace($marker, $this->value, $fileContent, $count);

        if ($count > 0) {
            // write back to file
            $res = file_put_contents($file, $fileContent);
            if ($res === false) {
                throw new \Exception(sprintf('Error while writing file "%s"', $file));
            }

            $this->addMessage(new Message(
                sprintf('Replaced %s occurence(s) of marker "%s" in file "%s" with value "%s"', $count, $marker, $file, $this->value),
                Message::OK
            ));
            $this->setStatus(HandlerInterface::STATUS_DONE);
        } else {
            $this->addMessage(new Message(
                sprintf('Could not find marker "%s" in file "%s"', $marker, $file),
                Message::WARNING
            ));
            $this->setStatus(HandlerInterface::STATUS_SUBJECTNOTFOUND);
        }

        return true;
    }


}