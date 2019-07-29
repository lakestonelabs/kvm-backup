<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DbInterface
 *
 * @author mlee
 */

/*
 * NOTE:  Classes that implement this interface should never return a RedBeanPHP bean. 
 * 
 */
interface DbInterface 
{    
    public function delete($bean);
    
    
    public function findOne($table, $sql = null, $bindings = null);
    public function find($table, $sql = null, $bindings = null);
    public function runMiscQuery($sql, $bindings = null);
    
    public function store($bean_or_array_of_beans);
    public function trash($bean);
    
    public function close();
   
    
}
