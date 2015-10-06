<?php

namespace Zettr\Handler;

use Zettr\Message;

/**
 * Add stuff to a file
 *
 * Parameters
 * - param1: targetFile
 * - param2: 'before', 'prepend', 'after', or 'append'
 * - param3: not used
 *
 * @author Fabrizio Branca
 * @since  2012-10-01
 */
class AddFileContent extends AbstractHandler
{
    protected $actions = array(
        'before'  => true,
        'prepend' => true,
        'after'   => false,
        'append'  => false
    );

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply()
    {
        $contentFile = $this->value;
        $targetFile = $this->param1;
        $action = strtolower(trim($this->param2));

        if (empty($this->value)) {
            $this->setStatus(HandlerInterface::STATUS_SKIPPED);
            return true;
        }

        if (!is_writable($targetFile)) {
            throw new \Exception(sprintf('File "%s" is not writable', $targetFile));
        }
        if (!is_file($contentFile)) {
            throw new \Exception(sprintf('File "%s" does not exist', $contentFile));
        }

        if (!array_key_exists($action, $this->actions)) {
            throw new \Exception('Param2 is not valid.');
        }

        if (!empty($this->param3)) {
            throw new \Exception('Param3 is not used in this handler and must be empty');
        }

        // read file
        $contentFileContent = file_get_contents($contentFile);
        if ($contentFileContent === false) {
            throw new \Exception(sprintf('Error while reading file "%s"', $contentFileContent));
        }

        $targetFileContent = file_get_contents($targetFile);
        if ($targetFileContent === false) {
            throw new \Exception(sprintf('Error while reading file "%s"', $targetFileContent));
        }

        // pre-process content
        $replace = array(
            '###CWD###' => getcwd()
        );

        $contentFileContent = str_replace(array_keys($replace), array_values($replace), $contentFileContent);

        if (empty($contentFileContent)) {
            $this->addMessage(
                new Message(
                    sprintf('No content found in file "%s" to add to "%s"', $contentFile, $targetFile),
                    Message::SKIPPED
                )
            );
        }

        $contentFileContent = trim($contentFileContent, "\n");

        // check if this content is already present in targetFile
        if (strpos($targetFileContent, $contentFileContent) !== false) {
            $this->setStatus(HandlerInterface::STATUS_ALREADYINPLACE);
            $this->addMessage(
                new Message(
                    sprintf('Content from file "%s" already present in "%s"', $contentFile, $targetFile),
                    Message::SKIPPED
                )
            );
        } else {
            if ($this->actions[$action]) {
                $newContent = $contentFileContent . "\n" . $targetFileContent;
            } else {
                $newContent = $targetFileContent . "\n" . $contentFileContent;
            }

            $result = file_put_contents($targetFile, $newContent);
            if ($result === false) {
                throw new \Exception(sprintf('Error while writing file "%s"', $targetFile));
            }

            $this->setStatus(HandlerInterface::STATUS_DONE);
            $this->addMessage(
                new Message(
                    sprintf(
                        ($this->actions[$action] ? 'Prepended' : 'Appended') . ' content from file "%s" to "%s"',
                        $contentFile,
                        $targetFile
                    ),
                    Message::OK
                )
            );
        }

        return true;
    }
}
