<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QemuStorage
 *
 * @author mlee
 */

class QemuStorage
{
    private $io_status = null,
            $device = null,
            $libvirt_device = null,
            $locked = null,
            $removable = null,
            $virtual_size = null,
            $actual_size = null,
            $filename = null,
            $dirty_flag = false;
    
    /*
     * $qemu_block_domain_info can be obtained by calling the "qemu-block"
     * command using libivirt's "qemu-monitor-command".
     */
    public function __construct(stdClass $qemu_block_domain_info_obj)
    {
        if(!$qemu_block_domain_info_obj->removable)
        {
            if(isset($qemu_block_domain_info_obj->inserted->image->format))
            {
                if($qemu_block_domain_info_obj->inserted->image->format == "qcow2")
                {
                    $this->device = $qemu_block_domain_info_obj->device;
                    $this->io_status = $qemu_block_domain_info_obj->{'io-status'};
                    $this->libvirt_device = $qemu_block_domain_info_obj->libvirt_device;
                    $this->locked = $qemu_block_domain_info_obj->locked;
                    $this->removable = $qemu_block_domain_info_obj->removable;
                    $this->virtual_size = $qemu_block_domain_info_obj->inserted->image->{'virtual-size'};
                    $this->actual_size = $qemu_block_domain_info_obj->inserted->image->{'actual-size'};
                    $this->filename = $qemu_block_domain_info_obj->inserted->image->filename;
                    $this->dirty_flag = $qemu_block_domain_info_obj->inserted->image->{'dirty-flag'};
                }
                else
                {
                    throw new Exception("Only qcow2 storage is supported.  You provided: ".$qemu_block_domain_info_obj->inserted->image->format);
                }
            }
            else
            {
                throw new Exception("No format found for device: ".$qemu_block_domain_info_obj->device);
            }
        }
        else
        {
            throw new Exception("Removable device: ".$qemu_block_domain_info_obj->device." not supported.");
        }
    }
    
    public function getName() : string
    {
        return $this->device;
    }
    
    public function getFilename(): string
    {
        return $this->filename;
    }
    
    public function getLibvirtName() : string
    {
        return $this->libvirt_device;
    }
    
    public function getIoStatus() : string
    {
        return $this->io_status;
    }
    
    public function isLocked() : bool
    {
        return $this->locked;
    }
    
    public function isRemovable() : bool
    {
        return $this->removable;
    }
    
    public function getVirtualSize() : int
    {
        return $this->virtual_size;
    }
    
    public function getActualSize() : int
    {
        return $this->actual_size;
    }
    
    public function isDirty(): bool
    {
        return $this->dirty_flag;
    }
}
