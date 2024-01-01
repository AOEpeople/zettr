<?php

namespace Zettr\Handler\Magento;

use Zettr\Message;

/**
 * Abstract Magento database handler class
 *
 * @author Dmytro Zavalkin
 * @author Fabrizio Branca
 */
abstract class AbstractDatabase extends \Zettr\Handler\AbstractDatabase
{
    /**
     * Actions to apply on row
     *
     * @var string
     */
    const ACTION_NO_ACTION = 0;
    const ACTION_INSERT = 1;
    const ACTION_UPDATE = 2;
    const ACTION_DELETE = 3;

    /**
     * Table prefix
     *
     * @var string
     */
    protected $_tablePrefix = '';

    /**
     * Read database connection parameters from local.xml file
     *
     * @return array
     * @throws \Exception
     */
    protected function _getDatabaseConnectionParameters()
    {
        $localXmlFile = 'app/etc/local.xml';
        $configPhpFile = 'app/etc/env.php';

        if (is_file($localXmlFile)) {
            $config = simplexml_load_file($localXmlFile);
            if ($config === false) {
                throw new \Exception(sprintf('Could not load xml file "%s"', $localXmlFile));
            }

            $this->_tablePrefix = (string)$config->global->resources->db->table_prefix;

            return array(
                'host' => (string)$config->global->resources->default_setup->connection->host,
                'database' => (string)$config->global->resources->default_setup->connection->dbname,
                'username' => (string)$config->global->resources->default_setup->connection->username,
                'password' => (string)$config->global->resources->default_setup->connection->password
            );
        } elseif (is_file($configPhpFile)) {
            $config = include($configPhpFile);
            if (!is_array($config)) {
                throw new \Exception(sprintf('Could not load php file "%s"', $configPhpFile));
            }
            return array(
                'host' => $config['db']['connection']['default']['host'],
                'database' => $config['db']['connection']['default']['dbname'],
                'username' => $config['db']['connection']['default']['username'],
                'password' => $config['db']['connection']['default']['password']
            );
        }

        throw new \Exception('No valid configuration found.');
    }

    /**
     * Check if at least one of the paramters contains a wildcard
     *
     * @param array $parameters
     * @return bool
     */
    protected function _containsPlaceholder(array $parameters)
    {
        foreach ($parameters as $value) {
            if (strpos($value, '%') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Look up store id for a given store code
     *
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    protected function _getStoreIdFromCode($code)
    {
        $results = $this->_getAllRows(
            "SELECT `store_id` FROM `{$this->_tablePrefix}core_store` WHERE `code` LIKE :code",
            array(':code' => $code)
        );

        if (count($results) === 0) {
            throw new \Exception("Could not find a store for code '$code'");
        } elseif (count($results) > 1) {
            throw new \Exception("Found more than once store for code '$code'");
        }

        $result = end($results);

        return $result['store_id'];
    }

    /**
     * Look up website id for a given website code
     *
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    protected function _getWebsiteIdFromCode($code)
    {
        $results = $this->_getAllRows(
            "SELECT `website_id` FROM `{$this->_tablePrefix}core_website` WHERE `code` LIKE :code",
            array(':code' => $code)
        );
        if (count($results) === 0) {
            throw new \Exception("Could not find a website for code '$code'");
        } elseif (count($results) > 1) {
            throw new \Exception("Found more than once website for code '$code'");
        }
        $result = end($results);
        return $result['website_id'];
    }

    /**
     * Fetch entity type id by a given entity type code
     *
     * @param string $code Entity type code
     * @return mixed
     * @throws \Exception
     */
    protected function _getEntityTypeFromCode($code)
    {
        $results = $this->_getAllRows(
            "SELECT `entity_type_id` FROM `{$this->_tablePrefix}eav_entity_type` WHERE `entity_type_code` LIKE :code",
            array(':code' => $code)
        );
        if (count($results) === 0) {
            throw new \Exception("Could not find an entity type with code '$code'");
        } elseif (count($results) > 1) {
            throw new \Exception("Found more than one entity type with code '$code'");
        }

        $result = end($results);

        return $result['entity_type_id'];
    }

    /**
     * @param string $table
     * @throws \Exception
     */
    protected function _checkIfTableExists($table)
    {
        $result = $this->getDbConnection()
                       ->query("SHOW TABLES LIKE \"{$this->_tablePrefix}{$table}\"");
        if ($result->rowCount() == 0) {
            throw new \Exception("Table \"{$this->_tablePrefix}{$table}\" doesn't exist");
        }
    }

    /**
     * Output constructed csv
     *
     * @param string $query
     * @param array $sqlParameters
     * @throws \Exception
     * @return string
     */
    protected function _outputQuery($query, array $sqlParameters)
    {
        $rows = $this->_getAllRows($query, $sqlParameters);

        $buffer = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            array_unshift($row, get_class($this));
            fputcsv($buffer, $row);
        }
        rewind($buffer);
        $output = stream_get_contents($buffer);
        fclose($buffer);

        return $output;
    }

    /**
     * Get first row query
     *
     * @param string $query
     * @param array $sqlParameters
     * @return mixed
     */
    protected function _getFirstRow($query, array $sqlParameters)
    {
        $statement = $this->getDbConnection()->prepare($query);
        $statement->execute($sqlParameters);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);

        return $statement->fetch();
    }

    /**
     * Get all rows
     *
     * @param string $query
     * @param array $sqlParameters
     * @return mixed
     */
    protected function _getAllRows($query, array $sqlParameters)
    {
        $statement = $this->getDbConnection()->prepare($query);
        $statement->execute($sqlParameters);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);

        return $statement->fetchAll();
    }

    /**
     * Process delete query
     *
     * @param string $query
     * @param array $sqlParameters
     * @throws \Exception
     */
    protected function _processDelete($query, array $sqlParameters)
    {
        $pdoStatement = $this->getDbConnection()->prepare($query);
        $result       = $pdoStatement->execute($sqlParameters);

        if ($result === false) {
            throw new \Exception('Error while deleting rows');
        }

        $rowCount = $pdoStatement->rowCount();
        if ($rowCount > 0) {
            $this->addMessage(new Message(sprintf('Deleted "%s" row(s)', $rowCount)));
        } else {
            $this->addMessage(new Message('No rows deleted.', Message::SKIPPED));
        }
    }

    /**
     * Process insert query
     *
     * @param string $query
     * @param array $sqlParameters
     * @throws \Exception
     */
    protected function _processInsert($query, array $sqlParameters)
    {
        $pdoStatement = $this->getDbConnection()->prepare($query);
        $result       = $pdoStatement->execute($sqlParameters);

        if ($result === false) {
            $info = $pdoStatement->errorInfo();
            $code = $pdoStatement->errorCode();
            throw new \Exception("Error while updating value (Info: $info, Code: $code)");
        }

        $this->addMessage(new Message(sprintf('Inserted new value "%s"', $this->value)));
    }

    /**
     * Process update query
     *
     * @param string $query
     * @param array $sqlParameters
     * @param string $oldValue
     * @throws \Exception
     */
    protected function _processUpdate($query, array $sqlParameters, $oldValue=null, $addMessage=true)
    {
        $pdoStatement = $this->getDbConnection()->prepare($query);
        $result       = $pdoStatement->execute($sqlParameters);

        if ($result === false) {
            $info = $pdoStatement->errorInfo();
            $code = $pdoStatement->errorCode();
            throw new \Exception("Error while updating value (Info: $info, Code: $code)");
        }

        $rowCount = $pdoStatement->rowCount();

        if ($addMessage) {
            if (!is_null($oldValue)) {
                $this->addMessage(new Message(sprintf('Updated value from "%s" to "%s" (%s row(s) affected)', $oldValue, $this->value, $rowCount)));
            } else {
                $this->addMessage(new Message(sprintf('Updated value to "%s" (%s row(s) affected)', $this->value, $rowCount)));
            }
        }
    }
}
