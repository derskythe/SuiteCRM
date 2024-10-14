<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

/*********************************************************************************
 * Description: This file handles the Data base functionality for the application.
 * It acts as the DB abstraction layer for the application. It depends on helper classes
 * which generate the necessary SQL. This sql is then passed to PEAR DB classes.
 * The helper class is chosen in DBManagerFactory, which is driven by 'db_type' in 'dbconfig' under config.php.
 *
 * All the functions in this class will work with any bean which implements the meta interface.
 * The passed bean is passed to helper class which uses these functions to generate correct sql.
 *
 * The meta interface has the following functions:
 * getTableName()                Returns table name of the object.
 * getFieldDefinitions()         Returns a collection of field definitions in order.
 * getFieldDefintion(name)       Return field definition for the field.
 * getFieldValue(name)           Returns the value of the field identified by name.
 *                               If the field is not set, the function will return boolean FALSE.
 * getPrimaryFieldDefinition()   Returns the field definition for primary key
 *
 * The field definition is an array with the following keys:
 *
 * name      This represents name of the field. This is a required field.
 * type      This represents type of the field. This is a required field and valid values are:
 *           �   int
 *           �   long
 *           �   varchar
 *           �   text
 *           �   date
 *           �   datetime
 *           �   double
 *           �   float
 *           �   uint
 *           �   ulong
 *           �   time
 *           �   short
 *           �   enum
 * length    This is used only when the type is varchar and denotes the length of the string.
 *           The max value is 255.
 * enumvals  This is a list of valid values for an enum separated by "|".
 *           It is used only if the type is �enum�;
 * required  This field dictates whether it is a required value.
 *           The default value is �FALSE�.
 * isPrimary This field identifies the primary key of the table.
 *           If none of the fields have this flag set to �TRUE�,
 *           the first field definition is assume to be the primary key.
 *           Default value for this field is �FALSE�.
 * default   This field sets the default value for the field definition.
 *
 *
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('include/database/MysqlManager.php');

/**
 * MySQL manager implementation for mysqli extension
 */
class MysqliManager extends MysqlManager
{
    /**
     * @see DBManager::$dbType
     */
    public $dbType = 'mysql';
    public $variant = 'PDO_MYSQL';
    public $priority = 10;
    public $label = 'LBL_MYSQLI';

    /**
     * @see DBManager::$backendFunctions
     */
    protected $backendFunctions = array(
        'free_result' => 'mysqli_free_result',
        'close' => 'mysqli_close',
        'row_count' => 'mysqli_num_rows',
        'affected_row_count' => 'mysqli_affected_rows',
    );

    /**
     * @see MysqlManager::query()
     */
    public function query($sql, $dieOnError = false, $msg = '', $suppress = false, $keepResult = false)
    {
        $result = null;
        if (is_array($sql)) {
            return $this->queryArray($sql, $dieOnError, $msg, $suppress);
        }

//        static $queryMD5 = array();
//
//        parent::countQuery($sql);
//        parent::getLog()->info('Query:' . $sql);
//        $this->checkConnection();
//        $this->query_time = microtime(true);
//        $this->lastsql = $sql;
//        if (!empty($sql)) {
//            if ($this->database instanceof PDO) {
//                try {
//                    $result = $suppress ? @mysqli_query($this->database, $sql) : mysqli_query($this->database, $sql);
//                } catch (Exception $e) {
//                    $result = false;
//                }
//                if ($result === false && !$suppress) {
//                    if (inDeveloperMode()) {
//                        parent::getLog()->debug('Mysqli_query failed, error was: ' . $this->lastDbError() . ', query was: ');
//                    }
//                    parent::getLog()->fatal('Mysqli_query failed.');
//                }
//            } else {
//                parent::getLog()->fatal('Database error: Incorrect link');
//            }
//        } else {
//            parent::getLog()->fatal('MysqliManager: Empty query');
//            $result = null;
//        }
//        $md5 = md5($sql);
//
//        if (empty($queryMD5[$md5])) {
//            $queryMD5[$md5] = true;
//        }
//
//        $this->query_time = microtime(true) - $this->query_time;
//        parent::getLog()->info('Query Execution Time:' . $this->query_time);
//        $this->dump_slow_queries($sql);
//
//        if ($keepResult) {
//            $this->lastResult = $result;
//        }
//        $this->checkError($msg . ' Query Failed: ' . $sql, $dieOnError);

        return $result;
    }

    /**
     * Returns the number of rows affected by the last query
     *
     * @return int
     */
    public function getAffectedRowCount($result)
    {
        if ($result instanceof PDOStatement) {
            return $result->rowCount();
        }

        return 0;
    }

    /**
     * Returns the number of rows returned by the result
     *
     * This function can't be reliably implemented on most DB, do not use it.
     * @abstract
     * @param resource $result
     * @return int
     * @deprecated
     */
    public function getRowCount($result)
    {
        return $this->getAffectedRowCount($result);
    }


    /**
     * Disconnects from the database
     *
     * Also handles any cleanup needed
     */
    public function disconnect()
    {
        parent::getLog()->debug('Calling MySQLi::disconnect()');
        if (!isset($this->database) || !($this->database instanceof PDO)) {
            $this->database = null;
        }
    }

    /**
     * @see DBManager::freeDbResult()
     */
    protected function freeDbResult($result)
    {
        if ($result instanceof PDOStatement) {
            return $result->closeCursor();
        }
    }

    /**
     * @see DBManager::getFieldsArray()
     */
    public function getFieldsArray($result, $make_lower_case = false)
    {
        $field_array = array();

        if (!isset($result) || empty($result)) {
            return 0;
        }

        $i = 0;
        while ($i < mysqli_num_fields($result)) {
            $meta = mysqli_fetch_field_direct($result, $i);
            if (!$meta) {
                return 0;
            }

            if ($make_lower_case == true) {
                $meta->name = strtolower($meta->name);
            }

            $field_array[] = $meta->name;

            $i++;
        }

        return $field_array;
    }

    /**
     * @see DBManager::fetchRow()
     */
    public function fetchRow($result)
    {
        if (empty($result)) {
            return false;
        }

        $row = mysqli_fetch_assoc($result);
        if ($row == null) {
            $row = false;
        } //Make sure MySQLi driver results are consistent with other database drivers

        return $row;
    }

    /**
     * @see DBManager::quote()
     */
    public function quote($string)
    {
        parent::getLog()->warn(sprintf('No quote on value: %s', $string));
        return $string;
    }

    protected function getCharsetInfo()
    {
        $charset = $this->getOption('charset');
        if (empty($charset)) {
            $charset = parent::getDefaultCharset();
        }

        return $charset;
    }


    /**
     * @see DBManager::connect()
     */
    public function connect(array $configOptions = null, $dieOnError = false)
    {
        global $sugar_config;

        if (is_null($configOptions)) {
            $configOptions = $sugar_config['dbconfig'];
        }

        $collation = $this->getOption('collation');
        $charset = $this->getCharsetInfo();

        if (!isset($this->database)) {
            // mysqli connector has a separate parameter for db_port..
            // We need to separate it out from the host name
            $db_host = $configOptions['db_host_name'];
            $db_name = $configOptions['db_name'];
            $db_port = isset($configOptions['db_port']) ? ($configOptions['db_port'] == '' ? '3306' : $configOptions['db_port']) : '3306';

            $pos = strpos($configOptions['db_host_name'], ':');
            if ($pos !== false) {
                $db_host = substr($configOptions['db_host_name'], 0, $pos);
                $db_port = substr($configOptions['db_host_name'], $pos + 1);
            }

            $error_msg = '';
            $dsn = '';
            try {
                $dsn = snprintf('mysql:dbname=%s;host=%s;port=%s;', $db_name, $db_host, $db_port);
                if (!empty($charset)) {
                    $dsn .= 'charset=' . $charset;
                }
                $this->database = new PDO(
                    $dsn,
                    $configOptions['db_user_name'],
                    $configOptions['db_password']);
            } catch (PDOException $e) {
                $error_msg = sprintf("%s\nDSN: %s\n%s",
                    $e->getMessage(),
                    $dsn,
                    $e->getTraceAsString());
            }
            if (!isset($this->database) || !($this->database instanceof PDO)) {
                parent::getLog()->fatal("Unable to select database {$configOptions['db_name']}: " . $error_msg);
                if ($dieOnError) {
                    sugar_die(
                        $GLOBALS['app_strings']['ERR_NO_DB'] . "\n" . $error_msg
                        ?? "Could not connect to the database. Please refer to suitecrm.log for details (2).\n" . $error_msg);
                } else {
                    return false;
                }
            }
        }

        if (!isset($this->database) || !($this->database instanceof PDO)) {
            parent::getLog()->fatal('Could not connect to the database. Please refer to suitecrm.log for details (1).');

            return false;
        }

        if (!empty($collation) && !empty($charset)) {
            if (!$this->executeNonQuery('SET NAMES :names COLLATE :collate',
                array(
                    ':names' => $charset,
                    ':collate' => $collation,
                ))) {
                return false;
            }
        }

        if (!empty($charset)) {
            if (!$this->executeNonQuery('SET NAMES :names',
                array(
                    ':names' => $charset,
                ))) {
                return false;
            }
        }

        // https://github.com/salesagility/SuiteCRM/issues/7107
        // MySQL 5.7 is stricter regarding missing values in SQL statements and makes some tests fail.
        // Remove STRICT_TRANS_TABLES from sql_mode so we get the old behaviour again.
        $this->executeNonQuery('SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode, :OLD_NAME, :NEW_NAME))',
            array(
                ':OLD_NAME' => 'STRICT_TRANS_TABLES',
                ':NEW_NAME' => '',
            ));
        $this->connectOptions = $configOptions;

        return true;
    }

    private function executeNonQuery(string $sql, array $dictionary)
    {
        if (!($this->database instanceof PDO)) {
            parent::getLog()->error("Database is not connected");
            return false;
        }
        try {
            $statement = $this->database->prepare($this->database, $sql);
            foreach ($dictionary as $key => $value) {
                $statement->bindParam($key, $value);
            }
            $this->database->exec($statement);

            return true;
        } catch (PDOException $exp) {
            parent::getLog()->fatal(sprintf(
                    'Could not prepare statement: %s SQL: %s Values: %s',
                    $exp->getMessage(),
                    $sql,
                    json_encode($dictionary))
            );
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see MysqlManager::lastDbError()
     */
    public function lastDbError()
    {
        if (!($this->database instanceof PDO)) {
            parent::getLog()->error("Database is not connected");
            return true;
        }

        return false;
    }

    public function getDbInfo()
    {
        return array(
            'MySQLi Version' => 'INVALID',
            'MySQLi Host Info' => 'INVALID',
            'MySQLi Server Info' => 'INVALID',
            'MySQLi Client Encoding' => 'INVALID',
            'MySQL Character Set Settings' => 'INVALID',
        );
    }

    /**
     * Select database
     * @param string $db_name
     */
    protected function selectDb($db_name)
    {
        return executeNonQuery('USE :DB_NAME', array(':DB_NAME' => $db_name));
    }

    /**
     * Check if this driver can be used
     * @return bool
     */
    public function valid()
    {
        return class_exists('PDO') && empty($GLOBALS['sugar_config']['mysqli_disabled']);
    }

    public function compareVarDefs($field_type, $field_len, $ignoreName = false)
    {
        return true;
    }
}
