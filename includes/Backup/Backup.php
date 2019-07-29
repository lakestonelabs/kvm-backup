f<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Backup
 *
 * @author mlee
 */

require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__.'/Misc.php';
require_once __DIR__.'/storage/Storage.php';
require_once __DIR__."/db/DbAbstract.php";
require_once __DIR__.'/../libvirt/LibVirt.php';


/*
 * By default we backup all devices associated with a domain/VM.
 */
class Backup
{
    protected   $backup_types_array = array("full", "incremental"),
                $domain_name = null,
                $backup_bean = null,
                $domain_bean = null,
                $schedule_bean = null,
                $backup_storage_obj = null,
                $status = null,
                $libvirt_obj = null,
                $cron_obj = null;


    public function __construct(RedBeanPHP\OODBBean $backup_bean, Storage $backup_storage_obj, LibVirt $libvirt_obj)
    {
        
        if(!in_array($bean->type, $this->backup_types_array))
        {
            throw new Exception("Backup type of: ".$bean." is unsupported");
        }
        
        $this->backup_bean = $backup_bean;
        $this->domain_bean = $this->backup_bean->domain;
        $this->schedule_bean = $backup_bean->schedule;
        $this->backup_type = trim($backup_type);
        $this->backup_storage_obj = $backup_storage_obj;
        $this->libvirt_obj = $libvirt_obj;
        
        require __DIR__."/../../conf/db_config.php";
        DbAbstract::connect("MariaDbWrapper", $db_host, $db_user, $db_password, $db_name);
        
        /*
         * Verify the domain passed actually exists.
         */
        if(!$this->libvirt_obj->domainExists($this->domain_bean->name))
        {
            throw new Exception("Can't backup domain that does not exist: ".$this->domain_name);
        }
        
        try
        {
            $this->cron_obj = Cron\CronExpression::factory($this->schedule_bean->cron_expression);
        } 
        catch (Exception $ex) 
        {
            throw $ex;
        }
        
    }
    
    public function run()
    {
        return true;
    }
    
    public function isDue()
    {
        return $this->cron_obj->isDue();
    }
    
    public function getStatus()
    {
        return $this->status;
    }
}
