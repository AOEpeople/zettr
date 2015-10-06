<?php

namespace Zettr\Handler;

/**
 * Abstract database handler class
 *
 * @author Fabrizio Branca
 * @since 2012-09-20
 */
abstract class AbstractDatabase extends AbstractHandler {

    /**
     * @var \PDO
     */
    protected $dbConnection;

    /**
     * Get database connection parameter
     *
     * Expected keys:
     * - host
     * - database
     * - username
     * - password
     *
     * @return array
     */
    abstract protected function _getDatabaseConnectionParameters();

    /**
     * Get database connection
     *
     * @return \PDO
     * @throws \Exception
     */
    protected function getDbConnection() {
        if (is_null($this->dbConnection)) {
            $dbParameters = $this->_getDatabaseConnectionParameters();
            if (!is_array($dbParameters)) {
                throw new \Exception('No valid database connection parameters found');
            }
            foreach (array('host', 'database', 'username' /*, 'password' */) as $key) {
                if (!isset($dbParameters[$key]) || empty($dbParameters[$key])) {
                    throw new \Exception(sprintf('No "%s" found in database connection parameters', $key));
                }
            }
            $this->dbConnection = new \PDO(
                "mysql:host={$dbParameters['host']};dbname={$dbParameters['database']};charset=utf8",
                $dbParameters['username'],
                $dbParameters['password'],
                array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
            );
        }
        return $this->dbConnection;
    }

    /**
     * Close database connection
     *
     * @return void
     */
    protected function destroyDb(){
        unset($this->dbConnection);
    }

}
