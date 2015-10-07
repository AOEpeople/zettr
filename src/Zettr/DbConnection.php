<?php

namespace Zettr;

/**
 * Database Connection
 *
 * @author Fabrizio Branca
 */
class DbConnection {

    static $connections = array();

    public static function getConnection(array $dbParameters) {
        foreach (array('host', 'database', 'username' /*, 'password' */) as $key) {
            if (!isset($dbParameters[$key]) || empty($dbParameters[$key])) {
                throw new \Exception(sprintf('No "%s" found in database connection parameters', $key));
            }
        }
        ksort($dbParameters);
        $key = implode('|', $dbParameters);
        if (!isset(self::$connections[$key])) {
            $pdo = new \PDO(
                "mysql:host={$dbParameters['host']};dbname={$dbParameters['database']};charset=utf8",
                $dbParameters['username'],
                $dbParameters['password'],
                array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
            );
            if (!$pdo->beginTransaction()) {
                throw new \Exception('Error while starting transaction for '.$key);
            }
            self::$connections[$key] = $pdo;
        }
        return self::$connections[$key];
    }

    public static function commitAllTransactions() {
        foreach (self::$connections as $key => $pdo) { /* @var $pdo \PDO */
            if (!$pdo->commit()) {
                throw new \Exception('Error while committing transaction for '.$key);
            }
        }
    }

}