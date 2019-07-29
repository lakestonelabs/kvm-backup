<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 
 * @author Mike Lee
 */

require_once __DIR__ . "/DbInterface.php";

class DbAbstract implements DbInterface
{
    private static      $host_address = null,
                        $username = null,
                        $password = null,
                        $database_name = null,
                        $available_db_wrappers = null,
                        $wrapper_class_name = null,
                        $instance = null,
                        $db_wrapper_object = null;
    
    /*
     * Constuctor is declared protected so no accidental outside instances can 
     * be created.  Only the connect() method should call the constructor.  This
     * class uses the Singleton pattern implementation.
     */
    protected function __construct($wrapper_class_name, $host_address, $username, $password, $database_name) 
    {
        self::$wrapper_class_name = trim($wrapper_class_name);
        
        // Get all of the files and their paths with the below filename pattern.
        self::$available_db_wrappers = glob(__DIR__ . "/*DbWrapper.php");
        //var_dump(self::$available_db_wrappers);
        // Remove the path of the Wrapper files and only get the name to include.
        for($i = 0; $i < sizeof(self::$available_db_wrappers); $i++)
        {
            self::$available_db_wrappers[$i] = basename(self::$available_db_wrappers[$i]);
        }
        
        if(in_array(self::$wrapper_class_name . ".php", self::$available_db_wrappers))
        {
            include_once self::$available_db_wrappers[array_search(self::$wrapper_class_name . ".php", self::$available_db_wrappers)];
            
            self::$host_address = trim($host_address);
            self::$username = trim($username);
            self::$password = trim($password);
            self::$database_name = trim($database_name);
            
            self::$db_wrapper_object = new self::$wrapper_class_name(self::$host_address, self::$username, self::$password, self::$database_name);
        }
        else
        {
            throw new Exception(__METHOD__."FATAL ERROR:  Unknown/Unavailabe DBWrapper class \"self::$wrapper_class_name\".  Was this class implemented?");
        }
        
        
    }
    
    /*
     * The below method allows us to transform this class into a Singleton.
     * $wrapper_name is the name of the wrapper class, minus the .php file extension
     */
    public static function connect($wrapper_class_name, $host_address, $username, $password, $database_name)
    {
        // DON'T USE static::$instance below.
        if(self::$instance === null)
        {
            try
            {
                self::$instance = new DbAbstract($wrapper_class_name, $host_address, $username, $password, $database_name);
                if(is_object(self::$instance))
                {
                    return true;
                }
                else
                {
                    throw new Exception(__METHOD__.": Failed to create a new database connection.");
                }
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
        else
        {
            return true;
        }
        //return self::$instance;
    }
    
    protected static function execQuery($method, $method_args_array)
    {
        $retry_count = 0;
        $max_retrys = 3;
        while($retry_count < $max_retrys)
        {
            try
            {
                if(empty($method_args_array))
                {
                    return self::$db_wrapper_object->$method();
                }
                else
                {
                    //echo "---- METHOD ARGS STRING: $method_args_string\n";
                    return call_user_func_array(array(self::$db_wrapper_object, $method), $method_args_array);
                    //return self::$db_wrapper_object->$method($method_args_string);
                }
            } 
            catch (Exception $e) 
            {
                /*
                 * DB sever connection has gone away.  Most likely a process was forked and the child
                 * processes ended, therefore killing the child's inherited db connection and therefore
                 * the parent's db connection along with it.  Therefore, we need to start a new db 
                 * connection, hence the while loop.
                 */
                if(preg_match("/SQLSTATE\[HY000\]/", $e->getMessage()) === 1)
                {
                    echo "ERROR: While trying to execute method:".$method."\n";
                    echo "*** SQL CONNECTION CLOSED. ATTEMPTING TO RECONNECT ***\n";
                    
                    // Erase our db connection instance.
                    self::$instance = null;
                    
                    // Attempt to reconnect to the database.
                    self::connect(self::$wrapper_class_name, self::$host_address, self::$username, self::$password, self::$database_name);
                    var_dump($return);
                    $retry_count++;
                }
                else
                {
                    throw $e;
                }
            }
        }
        $backtrace_array = debug_backtrace();
        $called_from = $backtrace_array[0]["file"];
        throw new Exception(__METHOD__.": CRITICAL:  Could not reconnect to db server after ".$max_retrys." attempts.  Original query was intiated by file: ".$called_from);
    }
    
    
    public static function createBeans($type, $amount)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function delete($bean)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function findOne($table, $sql= null, $bindings = null)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function find($table, $sql = null, $bindings = null)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function runMiscQuery($sql, $bindings = null)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function store($bean_or_array_of_beans)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function trash($bean)
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function pingDb()
    {
        return call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
    }
    
    public static function close()
    {
        try
        {
            call_user_func_array(array("DbAbstract", "execQuery"), array(__FUNCTION__, func_get_args()));
            // Erase our db connection instance.
            self::$instance = null;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }   
   
}
