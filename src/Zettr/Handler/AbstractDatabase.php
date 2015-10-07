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
            $this->dbConnection = \Zettr\DbConnection::getConnection($dbParameters);
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
