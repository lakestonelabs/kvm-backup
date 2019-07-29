<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MariaDbWrapper
 *
 * @author mlee
 */

require_once __DIR__ . "/DbInterface.php";
require_once __DIR__ . "/rb.php";
require_once __DIR__ . "/../Misc.php";

/*
 *
 * 
 */
class MariaDbWrapper implements DbInterface
{
    private $host_address,
            $username, 
            $password, 
            $database_name,
            $dsn = null;
    
    public function __construct($host_address, $username, $password, $database_name)
    {
        $this->host_address = trim($host_address);
        $this->username = trim($username);
        $this->password = trim($password);
        $this->database_name = trim($database_name);
        
        $this->dsn = "mysql:host=" . $this->host_address . ";dbname=" . $this->database_name;
        
        /*
         * We need to use R::addDatabase and R::selectDatabase instead of the usual
         * R::setup since some scripts use process forking.  
         * As a result child processes
         * will kill the parent's db connection when the child dies since the child 
         * inherited the parent's db connection upon forking.  Therefore, we use the
         * addDatabase method to provide a unique "key" since calling R::setup will 
         * always use 'default' as the key.  So if we used R::setup to create a new
         * db connection from the parent, an exception will be generated since we will
         * try to create another db connection with the same key ("default").
         * 
         * 
         */
        $key = sha1((string) microtime());
        R::addDatabase($key, $this->dsn, $this->username, $this->password);
        R::selectDatabase($key);
         
         
       
         /*
         * 
         * Below is the default/standard way of setting upa RedBean DB connection.
         */        
        //R::setup($this->dsn,$this->username,$this->password);
        R::freeze(true);  // Don't allow RedBean to make any db schema changes.
    }
    
    
    public function __destruct()
    {
        R::close();
    }
    
    public function close()
    {
        R::close();
    }
    
    public function createBeans($type, $amount = null)
    {
        if($amount === null)
        {
            $amount = 1;
        }
        
        if(is_int($amount))
        {
            return R::dispense(trim($type), $amount);
        }
    }
    
    public function delete($bean)
    {
        if(is_object($bean))
        {
            R::trash($bean);
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function findOne($table, $sql = null, $bindings = null)
    {
        return R::findOne($table, $sql, $bindings);
    }
    
    public function find($table, $sql = null, $bindings = null)
    {
        if(empty($bindings))
        {
            return R::find($table, $sql);
        }
        else
        {
            return R::find($table, $sql, $bindings);
        }
        
    }
    
    public function runMiscQuery($sql, $bindings = null)
    {
        if(is_array($bindings))
        {
            $return_value = R::exec($sql, $bindings);
            return $return_value;
        }
        else
        {
            $return_value = R::exec($sql);
            return $return_value;
        }
    }
    
    public function store($bean_or_array_of_beans)
    {
        $now_date = date("Y-m-d H:i:s");
        
        if(is_array($bean_or_array_of_beans) && !empty($bean_or_array_of_beans))
        {
            foreach($bean_or_array_of_beans as $this_bean_index => $this_bean)
            {
                if(isset($this_bean->date_updated))
                {
                    $this_bean->date_updated = $now_date;
                    $bean_or_array_of_beans[$this_bean_index] = $this_bean;
                }
            }
            return R::storeAll($bean_or_array_of_beans);
        }
        else if(!empty($bean_or_array_of_beans))
        {
            if(isset($bean_or_array_of_beans->date_updated))
            {
                $bean_or_array_of_beans->date_updated = $now_date;
            }
            return R::store($bean_or_array_of_beans);
        }
        else
        {
            return false;
        }
    }
    
    public function trash($bean)
    {
        return R::trash($bean);
    }
    
    public function pingDb()
    {
        try
        {
            R::exec('select 1');
            return true;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
    
    
}
