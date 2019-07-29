<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LvmStorage
 *
 * @author mlee
 */

require_once __DIR__.'/EncryptedUsbRemovableStorage.php';

class LvmEncryptedUsbRemovableStorage extends EncryptedUsbRemovableStorage
{
    private $prefix = "/dev/mapper",
            $type = "lvm"; 
    
    public function __construct($uuid, $passphrase)
    {
        /*
         * Remember each extending class of the Storage class we need to
         * do the following in order:
         * 
         * 1.) Call parent.
         * 2.) updateDeviceTypeMap()
         * 3.) scanDevice()
         * 4.) activate()
         */
        parent::__construct($uuid, $passphrase);
        
        // Need to update our mappings before attempting to activate the deivce.
        $this->updateDeviceTypeMap($this->type, ["prefix" => $this->prefix]);
        
        /*
        * Need to still scan the device even if it's setup to build
        * our device and children tree.
        */
        $this->scanDevice();
        
        /*
         * Wwe need to initialize any VGs or LVs.
         */
        try
        {
            $this->activate();
        } 
        catch (Exception $e) 
        {
            throw $e;
        }        
        
        
    }
    
    public function activate() : bool
    {
        if(parent::activate())
        {
            $return_array = Misc::runLocalCommand("vgchange -ay", true);
            if($return_array["return_value"] === 0)
            {
                return true;
            }
            else
            {
                throw new Exception("Error occurred when running 'vgchange -ay'");
            }
        }
    }
    
    public function deActivate()
    {
        $device_path =  $this->device_types_map[$this->type]["prefix"]."/".$this->device_types_map[$this->type]["name"];
        // Deactive the LVM.
        $return_array = Misc::runLocalCommand("lvchange -an ".$device_path, true);
        if($return_array["return_value"] === 0)
        {
            try
            {
                if(parent::deActivate())
                {
                    return true;
                }
            } 
            catch (Exception $e) 
            {
                throw $e;
            }
        }
        else
        {
            throw new Exception("Failed to deactive LVM device: ".$device_path);
        }
    }
        
}
