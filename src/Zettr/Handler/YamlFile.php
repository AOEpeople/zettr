<?php

namespace Zettr\Handler;

use Zettr\Message;
use Symfony\Component\Yaml\Yaml;

/**
 * Replace a value in a yaml file by path
 *
 * Parameters
 * - param1: file
 * - param2: path
 * - param3: not used
 *
 * @author Simone Marcato
 * @since  2018-03-01
 */
class YamlFile extends AbstractHandler
{

    /**
     * Apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply()
    {

        $file = $this->param1;
        $expression = $this->param2;

        if (!is_file($file)) {
            throw new \Exception(sprintf('File "%s" does not exist', $file));
        }
        if (!is_writable($file)) {
            throw new \Exception(sprintf('File "%s" is not writeable', $file));
        }
        if (empty($expression)) {
            throw new \Exception('No path defined');
        }
        if (!empty($this->param3)) {
            throw new \Exception('Param3 is not used in this handler and must be empty');
        }

        // read file
        $fileContent = file_get_contents($file);
        if ($fileContent === false) {
            throw new \Exception(sprintf('Error while reading file "%s"', $file));
        }


        $data = Yaml::parse($fileContent);
        $parts = explode('/', $expression);

        $parsed = $data;

        $search = $parsed;
        $pointer = &$parsed;

        foreach ($parts as $part) {
            if (!array_key_exists($part, $search)) {
                throw new \Exception(sprintf('Error while reading elements by path "%s"', $expression));
            }
            $search = $search[$part];
            $pointer = &$pointer[$part];
        }

        $changes = 0;
        if ($pointer == $this->value) {
            $this->addMessage(new Message(sprintf('Value "%s" is already in place. Skipping.', $this->value), Message::SKIPPED));
        } else {
            $this->addMessage(new Message(sprintf('Updated value from "%s" to "%s"', $pointer, $this->value)));
            $pointer = $this->value;
            $changes++;
        }


        if ($changes > 0) {
            $ymlout = Yaml::dump($parsed);
            $res = file_put_contents($file, $ymlout);
            if ($res === false) {
                throw new \Exception(sprintf('Error while writing file "%s"', $file));
            }
            $this->setStatus(HandlerInterface::STATUS_DONE);
        } else {
            $this->setStatus(HandlerInterface::STATUS_ALREADYINPLACE);
        }

        return true;
    }


}