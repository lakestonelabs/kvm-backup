<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QemuBackupDbDocument
 *
 * @author mlee
 */

require_once __DIR__."/../storage/vm/qemu/QemuStorage.php";

class QemuBackupDbDocument
{
    private $qemu_storage_obj = null,
            $backup_storage_obj = null,
            $name = null,
            $storage_uuid = null,
            $backup_type = null,
            $start = null,
            $end = null,
            $location = null,
            $backing = null,
            $status = null;
    
    public function __construct(QemuStorage $qemu_storage_obj, $backup_storage_obj)
    {
        if(is_object($backup_storage_obj))
        {
            $this->qemu_storage_obj = $qemu_storage_obj;
            $this->backup_storage_obj = $backup_storage_obj;
        }
        $this->name = $this->qemu_storage_obj->getName();
        $this->storage_uuid = $this->backup_storage_obj->getUuid();
    }
    
    public function setBackupType(string $backup_type)
    {
        $this->backup_type = trim($backup_type);
    }
    
    public function setStartTime(DateTimeImmutable $backup_start_time)
    {
       ; 
    }
    
    public function setEndTime(DateTimeImmutable $backup_end_time)
    {
        ;
    }
    
    public function setLocation(string $location)
    {
        $this->location = $location;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getStorageUuid()
    {
        return $this->storage_uuid;
    }
    
    public function getBackupType()
    {
        return $this->backup_type;
    }
    
    public function getStartTime()
    {
        return $this->start;
    }
    
    public function getEndTime()
    {
        return $this->end;
    }
    
    public function getLocation()
    {
        return $this->location;
    }
    
    public function getBacking()
    {
        return $this->backing;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
}
