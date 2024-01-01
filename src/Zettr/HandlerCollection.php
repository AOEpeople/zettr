<?php

namespace Zettr;

class HandlerCollection implements \Iterator {

    /**
     * @var array
     */
    protected $handlers = array();

    /**
     * @var array
     */
    protected $labels = array();


    /**
     * Build from settings csv file
     *
     * @param $csvFile
     * @param $environment
     * @param string $defaultEnvironment
     * @param array $includeGroups
     * @param array $excludeGroups
     * @throws \Exception
     */
    public function buildFromSettingsCSVFile($csvFile, $environment, $defaultEnvironment='DEFAULT', array $includeGroups=array(), array $excludeGroups=array()) {
        if (!is_file($csvFile)) {
            throw new \Exception('File "'.$csvFile.'" not found.');
        }
        $fh = fopen($csvFile, 'r');

        // first line: labels
        $this->labels = fgetcsv($fh);
        if (!$this->labels) {
            throw new \Exception('Error while reading labels from csv file');
        }

        $line=1;
        while ($row = fgetcsv($fh)) {
            $line++;
            if (count($row) != count($this->labels)) {
                throw new \Zettr\Exception\CheckFailed(sprintf('Incorrect column count in line %s (got: %s, expected: %s)', $line, count($row), count($this->labels)));
            }

            $rowGroups = $this->getGroupsForRow($row);

            if (count($includeGroups) && count(array_intersect($rowGroups, $includeGroups)) == 0) {
                // current row's groups do not match given include groups
                continue;
            }

            if (count($excludeGroups) && count(array_intersect($rowGroups, $excludeGroups)) > 0) {
                // current row's groups do match given exclude groups
                continue;
            }


            $handlerClassname = trim($row[0]);

            if (empty($handlerClassname) || $handlerClassname[0] == '#' || $handlerClassname[0] == '/') {
                // This is a comment line. Skipping...
                continue;
            }

            $ignoreErrors = false;
            if ($handlerClassname[0] == '@') {
                $ignoreErrors = true;
                $handlerClassname = trim(substr($handlerClassname, 1));
            }

            if (!class_exists($handlerClassname) && strpos($handlerClassname, 'Est_Handler') === 0) {
                $handlerClassname = str_replace('Est_Handler', 'Zettr\\Handler', $handlerClassname);
                $handlerClassname = str_replace('_', '\\', $handlerClassname);
            }

            if (!class_exists($handlerClassname)) {
                $handlerClassname = 'Zettr\\Handler\\'.trim($handlerClassname, '\\');
            }

            if (!class_exists($handlerClassname)) {
                throw new \Zettr\Exception\CheckFailed(sprintf('Could not find handler class "%s"', $handlerClassname));
            }


            // resolve loops in param1, param2, param3 using {{...|...|...}}
            $values = array();
            for ($i=1; $i<=3; $i++) {
                $value = trim($row[$i]);

                $values[$i] = array($value);

                for ($loopCounter=0; $loopCounter<4; $loopCounter++) { // replace up to 4 {{...}} constructs in the same value
                    foreach ($values[$i] as $index => $originalValue) {
                        $matches = array();
                        if (preg_match('/{{(.*?)}}/', $originalValue, $matches)) {
                            $tmp = Div::trimExplode('|', $matches[1], false);
                            unset($values[$i][$index]);
                            foreach ($tmp as $v) {
                                $values[$i][] = preg_replace('/{{(.*?)}}/', $v, $originalValue, 1);
                            }
                        }
                    }
                }
            }

            foreach ($values[1] as $param1) {
                foreach ($values[2] as $param2) {
                    foreach ($values[3] as $param3) {

                        $handler = new $handlerClassname(); /* @var $handler \Zettr\Handler\HandlerInterface */
                        if (!$handler instanceof \Zettr\Handler\HandlerInterface) {
                            throw new \Zettr\Exception\CheckFailed(sprintf('Handler of class "%s" does not implement \\Zettr\\Handler\\HandlerInterface', $handlerClassname));
                        }

                        $handler->setParam1($param1);
                        $handler->setParam2($param2);
                        $handler->setParam3($param3);

                        $handler->setIgnoreErrors($ignoreErrors);

                        $value = $this->getValueFromRow(
                            $row,
                            $environment,
                            $defaultEnvironment,
                            $handler
                        );
                        if (strtolower(trim($value)) == '--empty--') {
                            $value = '';
                        }

                        // set value
                        $handler->setValue($value);

                        $handler->register();

                        $this->addHandler($handler);
                    }
                }
            }

        }
    }

    /**
     * Get tags for given row
     *
     * @param array $row
     * @return array
     */
    protected function getGroupsForRow(array $row) {
        $tagsColumnIndex = $this->getColumnIndexForEnvironment('GROUPS', true);
        return $tagsColumnIndex && array_key_exists($tagsColumnIndex, $row) ? Div::trimExplode(',', $row[$tagsColumnIndex]) : array();
    }

    /**
     * Get column index for environment
     *
     * @param $environment
     * @param bool $checkOnly if set false will be returned instead of an exception thrown
     * @throws \Exception
     * @return mixed
     */
    protected function getColumnIndexForEnvironment($environment, $checkOnly=false) {
        $columnIndex = array_search($environment, $this->labels);
        if ($columnIndex === false) {
            if ($checkOnly) {
                return false;
            } else {
                throw new \Exception('Could not find environment '.$environment.' in csv file');
            }
        }
        if ($columnIndex <= 3) { // those are reserved for handler class, param1-3
            if ($checkOnly) {
                return false;
            } else {
                throw new \Exception('Environment cannot be defined in one of the first four columns');
            }
        }
        return $columnIndex;
    }

    /**
     * Get value from row
     *
     * @param array $row
     * @param string $environment
     * @param string $fallbackEnvironment
     * @param Handler\AbstractHandler $handler
     * @throws \Exception
     * @return string
     */
    private function getValueFromRow(array $row, $environment, $fallbackEnvironment, Handler\AbstractHandler $handler=null) {
        $value              = null;
        $defaultColumnIndex = $this->getColumnIndexForEnvironment($fallbackEnvironment, true);
        $envColumnIndex = $this->getColumnIndexForEnvironment($environment);
        if (array_key_exists($envColumnIndex, $row)) {
            $value = $row[$envColumnIndex];
            if ($value == '--empty--') {
                $value = '';
            } elseif (preg_match('/###REF:([^#]*)###/', $value, $matches)) {
                $value = $this->getValueFromRow($row, $matches[1], $fallbackEnvironment);
            } elseif ($value == '') {
                if ($defaultColumnIndex !== FALSE) {
                    $value = $row[$defaultColumnIndex];
                }
            }
        } else {
            if ($defaultColumnIndex !== FALSE && array_key_exists($defaultColumnIndex, $row)) {
                $value = $row[$defaultColumnIndex];
            }
        }

        if (!is_null($handler)) {
            $value = str_replace('###PARAM1###', $handler->getParam1(), $value);
            $value = str_replace('###PARAM2###', $handler->getParam2(), $value);
            $value = str_replace('###PARAM3###', $handler->getParam3(), $value);
        }

        while (preg_match('/###VAR:([^#]*)###/', $value, $matches)) {
            $var = VariableStorage::get($matches[1]);
            if ($var === false) {
                throw new \Exception('Variable "' . $matches[1] . '" is not set');
            }
            $value = preg_replace('/###VAR:([^#]*)###/', $var, $value, 1);
        }

        while (preg_match('/###FILE:([^#]*)###/', $value, $matches)) {
            $fileName = $matches[1];
            if (!file_exists($fileName)) {
                throw new \Exception('File "' . $fileName . '" does not exist');
            }
            $content = trim(file_get_contents($fileName));
            $value = preg_replace('/###FILE:([^#]*)###/', $content, $value, 1);
        }

        $value = str_replace('###ENVIRONMENT###', $environment, $value);
        $value = str_replace('###CWD###', getcwd(), $value);

        return $this->replaceWithEnvironmentVariables($value);
    }

    /**
     * Replaces this pattern ###ENV:TEST### with the environment variable
     * @param $string
     * @return string
     * @throws \Exception
     */
    protected function replaceWithEnvironmentVariables($string) {
        $matches = array();
        preg_match_all('/###ENV:([^#:]+)(:([^#]+))?###/', $string, $matches, PREG_PATTERN_ORDER);
        if (!is_array($matches) || !is_array($matches[0])) {
            return $string;
        }
        foreach ($matches[0] as $index => $completeMatch) {
            $value = getenv($matches[1][$index]);
            if ($value === false) {
                // fallback if set
                if (isset($matches[3][$index])) {
                    $value = $matches[3][$index];
                } else {
                    throw new \Exception('Expected an environment variable ' . $matches[1][$index] . ' is not set');
                }
            }
            $string = str_replace($completeMatch, $value, $string);
        }

        return $string;
    }

    /**
     * @param Handler\HandlerInterface $handler
     * @throws \Exception
     */
    public function addHandler(Handler\HandlerInterface $handler) {
        $hash = $this->getHandlerHash($handler);
        if (isset($this->handlers[$hash])) {
            throw new \Exception('Handler with these parameters already exists. Cannot add: ' . $handler->getLabel());
        }
        $this->handlers[$hash] = $handler;
    }

    /**
     * Get handler
     *
     * @param $handlerClassname
     * @param $p1
     * @param $p2
     * @param $p3
     * @return Handler\HandlerInterface|bool
     */
    public function getHandler($handlerClassname, $p1, $p2, $p3) {
        if (isset($this->handlers[$this->getHandlerHashByValues($handlerClassname, $p1, $p2, $p3)])) {
            return $this->handlers[$this->getHandlerHashByValues($handlerClassname, $p1, $p2, $p3)];
        } else {
            return false;
        }
    }

    /**
     * Get Handler hash
     *
     * @param Handler\HandlerInterface $handler
     * @return string
     */
    protected function getHandlerHash(Handler\HandlerInterface $handler) {
        return $this->getHandlerHashByValues(
            get_class($handler),
            $handler->getParam1(),
            $handler->getParam2(),
            $handler->getParam3()
        );
    }

    /**
     * Get handler hash by values
     *
     * @param $handlerClassname
     * @param $p1
     * @param $p2
     * @param $p3
     * @return string
     */
    protected function getHandlerHashByValues($handlerClassname, $p1, $p2, $p3) {
        return md5($handlerClassname.$p1.$p2.$p3);
    }

    public function rewind(): void {
        reset($this->handlers);
    }

    public function current(): mixed {
        return current($this->handlers);
    }

    public function key(): mixed {
        return key($this->handlers);
    }

    public function next(): void {
        next($this->handlers);
    }

    public function valid(): bool {
        $key = key($this->handlers);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }


}
