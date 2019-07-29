<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QemuBackup
 *
 * @author mlee
 */

require_once __DIR__."/Backup.php";
require_once __DIR__."/../storage/vm/qemu/QemuStorage.php";

class QemuBackup extends Backup
{
    
    protected   $qemu_storage_array = null;
    
    public function __construct(RedBeanPHP\OODBBean $backup_bean, Storage $backup_storage_obj, LibVirt $libvirt_obj)
    {
        try
        {
            parent::__construct($backup_bean, $backup_storage_obj, $libvirt_obj);
            
            /*
            * Get all of the devices and their info for each Qemu storage device 
            * associated with this domain.
            */ 
            $this->qemu_storage_array = $this->libvirt_obj->getQemuStorageInfo($this->domain_bean->name);
        }
        catch (Exception $e)
        {
            throw $e;
        }
        
        /*
         * ToDos:
         * 1.) For each vdisk for this domain, determine if the vdisk has a bitmap.
         *     If it doesn't create one.  If it does then clear it.  This is only
         *     for full backups.
         */
    }
    
    public function run()
    {
        /*
         * ToDo: build json backup and call Libvirt::executeQemuCommand() to
         * start the backup. 
         */
        /*
         * if(qemu_command_return_code === 0
         * status =  "running"
         * else status = "failed"
         */
    }
    
    public function getStatus(): boolean
    {
        parent::getStatus();
        
        /*
         * Use qemu-monitor-command to run commands and check the status
         * of the backup of the domain's storage device.
         */
    }
}
