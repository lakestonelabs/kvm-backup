<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UsbRemovableStorage
 *
 * @author mlee
 */

require_once __DIR__.'/RemovableStorage.php';

class UsbRemovableStorage extends RemovableStorage
{
    private $prefix = "/dev/mapper",
            $type = "";
   
    public function __construct($uuid) 
    {
        /*
         * Remember each extending class of the Storage class we need to
         * do the following in order:
         * 
         * 1.) Call parent.
         * 2.) updateDeviceTypeMap() NOT NEEDED FOR THIS CLASS.  ALREADY HANDLED BY BASE CLASS.
         * 3.) scanDevice()
         * 4.) activate()
         */
        
        parent::__construct($uuid);
    }
    
    public function activate()
    {
        parent::activate();
        return true;
    }


    public function deActivate()
    {
        /*
         * TODO: Need to do any USB-related stuff to deactive the device.
         * Don't know if we want to, such as using "udisksctl power-off" as 
         * this will power-down the port that the usb device is connected 
         * to.  Food for thought.
         */
        parent::deActivate();
        return true;
    }
}
