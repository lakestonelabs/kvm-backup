<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EncryptedUsbRemovableStorage
 *
 * @author mlee
 */

require_once __DIR__.'/UsbRemovableStorage.php';


class EncryptedUsbRemovableStorage extends UsbRemovableStorage
{
    private $prefix = "/dev/mapper",
            $type = "crypt",
            $crypt_label = null,
            $passphrase = null;
    
    public function __construct($uuid, $passphrase)
    {
        // We will use the UUID when creating the dm-crypt-luks label.
        $this->crypt_label = "crypt-".$uuid;
        $this->passphrase = $passphrase;
        
        parent::__construct($uuid);
        
        $this->updateDeviceTypeMap($this->type, ["prefix" => $this->prefix]);
        
        /*
        * Initialize dm-crypt for the device.
        */
        
        /*
         * ... isLuks will test if the partition is dm-crypt-luks capable.  It will
         * test against the partition since that's as far as the parent class(es)
         * will go.  I.E. $this->mount_device should be something like /dev/sdX1 etc.
         */
        if(!$this->isActive())
        {
            try
            {
                $this->activate();
            }
            catch(Exception $e)
            {
                throw $e;
            }
        }
    }
    
    private function isActive() : bool
    {
        $return_array = Misc::runLocalCommand("cryptsetup status ".$this->crypt_label, true);
        if(is_array($return_array["output"]))
        {
            $status = explode(" ", $return_array["output"][0])[2];
        }
        else
        {
            $status = explode(" ", $return_array["output"])[2];

        }
        if($status == "active")
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function activate(): bool
    {
        if(!$this->isActive())
        {
            $return_array = Misc::runLocalCommand("cryptsetup isLuks ".$this->mount_device, true);
            if($return_array["return_value"] === 0)
            {
                // Setup a key file to pass or papssphrase to cryptsetup.
                // We append a '/' because realpath will remove trailing slashes.
                $keyfile = realpath(__DIR__."/../../keys")."/".$this->crypt_label;
                $fp = fopen($keyfile, "w");
                if(!is_resource($fp))
                {
                    throw new Exception("Failed to open keys file at: ".$keyfile." for writting.");
                }
                // cryptsetup will fail unless there is a newline at the end of the passphrase.
                fwrite($fp, $this->passphrase."\n");
                // Clear our passphrase so that it's not accessible via the object.
                $this->passphrase = null;
                fclose($fp);

                $return_array = Misc::runLocalCommand("cryptsetup open --type luks ".$this->mount_device." ".$this->crypt_label." --key-file ".$keyfile, true);
                // Remove our keyfile so as to no leak our passphrase.
                if(!unlink($keyfile))
                {
                    throw new Exception("Unable to delete cryptsetup keyfile at: ".$keyfile.".  Private key may be accessible to others.  Please remove this file and check file permissions.");
                }
                if($return_array["return_value"] === 0 || $return_array["return_value"] === 5)
                {
                    /*
                     * For some reason cryptsetup takes a bit to do things in the 
                     * backgroup, so we sleep a little.
                     */
                    sleep(5);

                    try
                    {
                        // Further build-out our device-tree for future extending classes to utilize.
                        $this->scanDevice();
                        return true;
                    }
                    catch(Exception $e)
                    {
                        throw $e;
                    }
                }
                else
                {
                    throw new Exception("Failed to open dm-crypt luks volume on device: ".$this->device);
                }
            }
            else
            {
                throw new Exception("Can't initialze storage for device: ".$this->device.  ".  It's not a dm-crypt-luks encrypted volume.");
            }
        }
        else
        {
            return true;
        }
    }
    
    public function deActivate()
    {
        /*
         * Note, we don't call parent::umount() here because our parent which 
         * is the Storage class has its umount method set to static so that
         * extending classes can officially umount the device first.
         */
        $device_path =  $this->device_types_map[$this->type]["prefix"]."/".$this->device_types_map[$this->type]["name"];
        $return_array = Misc::runLocalCommand("cryptsetup close ".$this->crypt_label, true);
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
            throw new Exception("cryptsetup failed to close crypt volume: ".$this->crypt_label.".");
        }
    }
    
    
}
