<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LibVirt
 *
 * @author mlee
 */

require_once __DIR__.'/../Misc.php';
require_once __DIR__."/../storage/vm/qemu/QemuStorage.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LibVirt
{
    private     $libvirt_resource = null,
                $domain_resource = null,
                $url = null,
                $logger = null;
    
    protected   $domains_array = [];


    public function __construct(string $url, $read_only = false)
    {
        
        // Setup our logging facilities.
        $this->logger = new Logger("virtbak");
        $this->logger->pushHandler(new StreamHandler(__DIR__."/../../logs/virtbak.log", Logger::DEBUG));
        /*
         * Localhost only supported at the moment.  We only want a read-only
         * connection (2nd argument).
         */
        $this->url = $url;
        $this->libvirt_resource = libvirt_connect($this->url, $read_only);
        if(!is_resource($this->libvirt_resource))
        {
            throw new Exception("Failed to connect to ".$url." libvirt daemon.");
        }
        
        
        // Get our VMs and their info.
        $domains = $this->listDomains();
        foreach($domains as $this_domain)
        {
            $this->domains_array[$this_domain] = $this->getDomainInfo($this_domain);
        }
    }
    
    /*
     * Interogate everything about the domain.
     */
    protected function getDomainInfo($domain_name)
    {
        if(!empty($domain_name))
        {
            $domain_info_array = array( "disks" => array(), 
                                        "active" => null);

            $domain_name_resource = $this->getDomainResourceByName($domain_name);
            $domain_info_array["active"] = $this->isDomainActive($domain_name);

            $disk_devices_array = libvirt_domain_get_disk_devices($domain_name_resource);
            
            // Get rid of the "num" element.  Don't know why the API sets this.
            if(isset($disk_devices_array["num"]))
            {
                unset($disk_devices_array["num"]);
            }
            foreach($disk_devices_array as $this_device_disk)
            {
                // We only care about actual VM disks, not CDs (hda's, etc.).
                if($this_device_disk == "vda")
                {
                    $this_disk_block_info_array = libvirt_domain_get_block_info($domain_name_resource, $this_device_disk);
                    if(is_array($this_disk_block_info_array))
                    {
                        $domain_info_array["disks"][$this_device_disk] = $this_disk_block_info_array;
                    }
                }
            }

            return $domain_info_array;
        }
        else
        {
            return false;
        }
    }
    
    public function getUrl()
    {
        return $this->url;
    }


    public function refreshDomainInfo()
    {
        // Get our VMs and their info.
        $domains = $this->listDomains();
        foreach($domains as $this_domain)
        {
           $this->domains_array[$this_domain] = $this->getDomainInfo($this_domain);
        }
        return true;
    }
    
    public function listActiveDomains()
    {
        return libvirt_list_active_domains($this->libvirt_resource);
    }
    
    public function domainExists($domain_name)
    {
        if(array_key_exists($domain_name, $this->domains_array))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function listDomains()
    {
        return libvirt_list_domains($this->libvirt_resource);
    }
    
    public function isDomainActive($domain_name)
    {
        $domain_name = trim($domain_name);
        
        $active_domains_array = $this->listActiveDomains();
        foreach($active_domains_array as $this_active_domain)
        {
            if($this_active_domain == $domain_name)
            {
                return true;
            }
        }
        return false;
    }
    
    protected function getDomainResourceByName($domain_name)
    {
        $domain_resource = libvirt_domain_lookup_by_name($this->libvirt_resource, trim($domain_name));
        if(is_resource($domain_resource))
        {
            return $domain_resource;
        }
        else
        {
            return false;
        }
    }
    
    /*
     * Return an array of QemuStorage objects detailing the info about each storage
     * device for said domain. The qemu device name is used as the array indices.
     */
    public function getQemuStorageInfo($domain_name) : array
    {
        // Can't get storage IDs if domain is not active/online/turned-on.
        if(!$this->isDomainActive($domain_name))
        {
            throw new Exception("Can't get storage info for domain: ".$disk_name.".  It's not online.");
        }
        
        $qemu_storage_obj_array = [];
        
        /*
         * Qemu's query-block command gives us detailed info about our storage devices.
         * We will merge this info with the info from libvirt to get a complete picture
         * of our storage devices.
         */
        $command = "virsh qemu-monitor-command ".trim($domain_name)." --pretty ".'{ \"execute\": \"query-block\" }';
        $qemu_storage_array = json_decode(implode("\n", $this->executeQemuCommand($command)["output"]));
        
        // The below essentially just gets us the device names used by libvirt.
        $domain_name_resource = $this->getDomainResourceByName($domain_name);
        $disk_devices_array = libvirt_domain_get_disk_devices($domain_name_resource);
        
        // Appdend libvirt's device info to qemu's device info.
        for($i = 0; $i < count($qemu_storage_array->return); $i++)
        {
            $qemu_storage_array->return[$i]->libvirt_device = $disk_devices_array[$i];
            try
            {
                $qemu_storage_obj = new QemuStorage($qemu_storage_array->return[$i]);
            }
            catch (Exception $e)
            {
                $this->logger->addWarning("For domain: ".$domain_name.", ".$e->getMessage());
            }
            $qemu_storage_obj_array[$qemu_storage_obj->getName()] = $qemu_storage_obj; 
        }
        
        return $qemu_storage_obj_array;
    }
    
    private function executeQemuCommand($command)
    {
        return Misc::runLocalCommand($command, true);
    }
    
    public function dumpDomainsInfo()
    {
        return $this->domains_array;
    }
}
