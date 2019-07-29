<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Storage
 *
 * @author mlee
 */
require_once __DIR__ . '/StorageInterface.php';

class Storage implements StorageInterface
{

    protected $label = null,
            $mount_point = null,
            $uuid = null,
            $device = null,
            $parent = null,
            $device_scan_obj = null,
            $spinning = false,
            $vendor = null,
            $revision = null,
            $model = null,
            $serial_number = null,
            $size = null,
            $parent_size = null,
            $state = null,
            $mount_device = null, /* Sometimes the device and mount device
             * can differ if other layers are added
             * to the device such as encryption or 
             * LVM, etc. If there are added layers then
             * we can't simply mount the device since
             * that's not correct, we need to mount
             * the upper layers of the device, such as
             * LVM volume, etc.       
             */
            $removable = false,
            $bus_type = null,
            $bus_types_supported = array("sata", "usb"),
            $format_type = null,
            $uuid_path_prefix = "/dev/disk/by-uuid",
            $device_types_map = array("disk" => ["prefix" => "/dev"], "part" => ["prefix" => "/dev"]);
    private $scan_count = 0;

    public function __construct($uuid)
    {
        include_once __DIR__ . "/../../conf/virtbak_conf.php";

        $this->uuid = $uuid;

        // De-reference the UUID to the actual device.
        $result_array = Misc::runLocalCommand("blkid -U " . $this->uuid, true);
        if ($result_array["return_value"] === 0 && strlen($result_array["output"]) > 0)
        {
            $this->device = $result_array["output"];
            /*
             * Initially the mount_device and device are the same, but we will 
             * allow extending classes to override this value with the upper-layered
             * device, such as LVM, etc.
             */
            $this->mount_device = $this->device;

            /*
             * Now that we have the device (partition actually), we want to get the parent
             * physical device so that we can interogate it.
             */
            $dev_stripped = preg_replace("/\/dev\//", "", $this->device);
            $parent_path = readlink("/sys/class/block/" . $dev_stripped);
            preg_match("/.*\/(\w+)\/" . $dev_stripped . "$/", $parent_path, $matches);
            $this->parent = "/dev/" . $matches[1];

            // Scan the device to get all of it's current children.
            $this->scanDevice();
        } 
        else
        {
            throw new Exception("Failed to lookup device based on UUID: " . $this->uuid.".  Is it attached to the system?");
        }
    }
    
    /*
     * This method is mainly used to determine if a physical device is attatched
     * before trying to instantiage an object.
     */
    public static function isDeviceAttached(string $uuid) : bool
    {
        $result_array = Misc::runLocalCommand("blkid -U " . $uuid, true);
        if ($result_array["return_value"] === 0 && strlen($result_array["output"]) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function mount()
    {
        try
        {
            $this->calculateMountPoint($this->device_scan_obj, "/mnt");
        }
        catch (Exception $e)
        {
            throw $e;
        }
        
        /*
         * First check if this is already mounted
         */
        if (!$this->isMounted())
        {
            if (!is_dir($this->mount_point))
            {
                mkdir($this->mount_point, 0750, true);
            }
            $result_array = Misc::runLocalCommand("mount " . $this->mount_device . " " . $this->mount_point, true);

            if ($result_array["return_value"] === 0)
            {
                return true;
            } else
            {
                throw new Exception("Failed to mount device:" . $this->mount_device . " to: " . $this->mount_point . ".  Caught error message: " . $result_array["output"]);
            }
        } else
        {
            return true;  // Already mounted.
        }
    }

    public function deActivate()
    {
        return true;  // Have not decided what to do here yet.
    }

    /*
     * Needs to be static so that extending classes can call this first before
     * proceeding with their own umount logic.
     */

    public function umount()
    {
        if($this->mount_point === null)
        {
            try
            {
                $this->calculateMountPoint($this->device_scan_obj, "/mnt");
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
        
        if ($this->isMounted())
        {
            $result_array = Misc::runLocalCommand("umount " . $this->mount_point, true);

            if ($result_array["return_value"] === 0)
            {
                return true;
            } else
            {
                throw new Exception("Failed to unmount: " . $this->mount_point . ".  Caught error was:" . $result_array["output"]);
            }
        } else
        {
            return true;
        }
    }

    public function isMounted()
    {
        $return_array = Misc::runLocalCommand("mount | grep -c " . $this->mount_device, true);

        if ($return_array["return_value"] === 0 && (int) $return_array["output"] > 0)
        {
            return true;
        } else
        {
            return false;
        }
    }

    public function getFreeSpace()
    {
        return disk_free_space($this->mount_point);
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function getModel()
    {
        return $this->model;
    }
    
    public function getMountPoint()
    {
        return $this->mount_point;
    }
    
    public function getSerialNumber()
    {
        $this->serial_number;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function getParentSize()
    {
        return $this->parent_size;
    }
    
    public function getSize()
    {
        if($this->isMounted())
        {
            $size = disk_total_space($this->mount_point);
            $this->size = $size;
            return $this->size;
        }
        else
        {
            return false;
        }
    }

    public function getUsedSpace()
    {
        if($this->isMounted())
        {
            return (disk_total_space($this->mount_point) - disk_free_space($this->mount_point));
        }
        else
        {
            return false;
        }
    }

    public function isRemovable()
    {
        return $this->removable;
    }

    public function getBusType()
    {
        return $this->bus_type;
    }
    
    public function getDevicePath()
    {
        return $this->parent;
    }
    
    public function getPartitionPath()
    {
        return $this->mount_device;
    }
    
    public static function getPhysicalDevName(string $uuid) : string
    {
        $result_array = Misc::runLocalCommand("blkid -U " . $uuid, true);
        if ($result_array["return_value"] === 0 && strlen($result_array["output"]) > 0)
        {
            return $result_array["output"];
        }
        else
        {
            return false;
        }
    }
    
    public function getRevisionNumber()
    {
        return $this->revision;
    }

    public function getFormatType()
    {
        return $this->format_type;
    }
    
    public function getUuid()
    {
        return $this->uuid;
    }
    
    public function activate()
    {
        return true;
    }

    protected function scanDevice()
    {
        // Now interogate the device.
        $return_array = Misc::runLocalCommand("lsblk -Jo name,label,type,fstype,hotplug,vendor,rev,model,serial,size,state,rota,tran " . $this->parent, true);
        if ($return_array["return_value"] !== 0)
        {
            throw new Exception("Failed to get details for device: " . $this->parent);
        }

        // Transform array of strings into a single multi-line string.
        $dev_json = null;
        foreach ($return_array["output"] as $this_output)
        {
            $dev_json = $dev_json . $this_output . "\n";
        }
        $json_obj = json_decode($dev_json);

        $this->device_scan_obj = $json_obj->blockdevices[0];

        // Perform the below actions only for the top level of the device, i.e. first time scanned, i.e. this class.
        if ($this->scan_count === 0)
        {
            // Set all attributes of the disk found at the top level.
            {
                $this->spinning = true;
            }
            if ($this->device_scan_obj->hotplug == "1")
            {
                $this->removable = true;
            }


            $attributes_array = array("vendor" => "vendor",
                "revision" => "rev",
                "model" => "model",
                "serial_number" => "serial",
                "state" => "state",
                "bus_type" => "tran");
            foreach ($attributes_array as $this_attr_key => $this_attr_name)
            {
                if (isset($this->device_scan_obj->$this_attr_name))
                {
                    $this->$this_attr_key = trim($this->device_scan_obj->$this_attr_name);
                }
            }
            
            if(isset($this->device_scan_obj->size))
            {
                try
                {
                    $this->parent_size = Misc::bytesFromString($this->device_scan_obj->size);
                }
                catch (Exception $e)
                {
                    throw $e;
                }
            }
        }
        $this->scan_count++;

        $this->findDeviceChildren($this->device_scan_obj);
        return true;
    }

    private function calculateMountPoint($block_obj, $initial_mnt_point = null)
    {
        if($initial_mnt_point !== null)
        {
            $this->mount_point = $initial_mnt_point;
        }
        if ($block_obj->type !== "disk" && $block_obj->type !== "part")
        {
            $this->mount_point = $this->mount_point . "/" . $block_obj->type;
        }
        // Run recursively.
        if (isset($block_obj->children))
        {
            foreach ($block_obj->children as $this_child_obj)
            {
                /*
                 * Don't provide second parameter to our method so as to 
                 * not mangle our already set mount point.
                 */
                $this->calculateMountPoint($this_child_obj);
            }
        } 
        else
        {
            if (isset($block_obj->label) && $block_obj->label !== null)
            {
                // We don't allow spaces in our mount points.
                $this->mount_point = $this->mount_point . "/" . str_replace(" ", "_", $block_obj->label);
            } 
            else
            {
                throw new Exception("No label for device: " . $this->mount_device . ".  Can't proceed.");
            }
        }
        return true;
    }

    private function findDeviceChildren($block_obj)
    {
        /*
         * Update the mount_device for each sublayer we find.  For each
         * new level we traverse, that device now becomes the device we 
         * will mount.  Extending classes should call this further to update
         * the mount device.
         */
        if (isset($this->device_types_map[$block_obj->type]) && isset($this->device_types_map[$block_obj->type]["prefix"]))
        {
            $this->mount_device = $this->device_types_map[$block_obj->type]["prefix"] . "/" . $block_obj->name;
            $this->device_types_map[$block_obj->type]["name"] = $block_obj->name;
            if(isset($block_obj->label))
            {
                $this->label = $block_obj->label;
            }
        } else
        {
            /*
             * We will only traverse down the device tree as far as the device
             * prefixes are defined.  This is especially true if the device is
             * already mouted and the complete device tree is built.
             */
            return true;
        }

        // Run recursively.
        if (isset($block_obj->children))
        {
            foreach ($block_obj->children as $this_child_obj)
            {
                $this->findDeviceChildren($this_child_obj);
            }
        }

        return true;
    }

    protected function updateDeviceTypeMap($type, $mapping_array)
    {
        if (is_array($mapping_array))
        {
            $this->device_types_map[$type] = $mapping_array;
            return true;
        } else
        {
            return false;
        }
    }
    
    public static function exists($device_uuid)
    {
        // De-reference the UUID to the actual device.
        $result_array = Misc::runLocalCommand("blkid -U " . $device_uuid, true);
        if ($result_array["return_value"] === 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}
