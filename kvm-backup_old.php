#!/usr/bin/php
<?php

/* 
 * Copyright Mike Lee 2015
 * 
 * This file is part of kvm-backup.

    kvm-backup is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    kvm-backup is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with kvm-backup.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */


require_once __DIR__.'/kvm_backup_config.php';  // Include our info to determine where to backup to.

//////  User configurable vars. /////////////////
$qemu_config_dir = "/etc/libvirt/qemu";

//////  End of user-configurable vars. //////////



/////// DON'T EDIT ANYTHING BELOW THIS LINE.  ///////
$qemu_backup_dirname = "qemu_configs";
$disk_images_backup_dirname = "disk_images";

$vm_listing_array = runVirshCommand("/usr/bin/virsh list --all", true);

//var_dump($vm_listing_array);
if($vm_listing_array["return_value"] == 0)
{
    // Now let's start backing things up.
    $calculated_backup_dir = prerequisites();
            
    foreach($vm_listing_array["output"] as $this_vm)
    {
        $is_shut_off = false;
        // First we need to shutdown the VM if it's running.
        if($this_vm["state"] == "running")
        {
            echo "Attempting to shutdown VM: ".$this_vm["name"]." to back it up....\n";
            $output_array = runVirshCommand("/usr/bin/virsh shutdown ".$this_vm["id"], false);
            if($output_array["return_value"] != 0 || preg_match("/is being shutdown/", $output_array["output"][0]) !== 1)
            {
                echo "Failed to shutdown VM: ".$this_vm["name"].".  Output was ".$output_array["output"][0]."\n";
            }
            else
            {
                if(wait_for_vm_shutdown($this_vm["name"], 600)) // Wait for 10 minutes for the VM to gracefully shutdown.
                {
                    echo "Successfully shutdown VM: ".$this_vm["name"]."\n";
                    $is_shut_off = true;
                }
                else
                {
                    echo "VM: ".$this_vm["name"]." took too long to shutdown.  It may still be running.  Will not backup this VM.\n";
                }
            }
        }
        else if($this_vm["state"] == "shut off")
        {
            $is_shut_off = true;
            
        }
        
        if($is_shut_off)
        {
            if(!$calculated_backup_dir)
            {
                echo "Failed to perform prerequisites.  Quiting now.\n";
                exit(1);
            }
            
            /*
             * First let's backup the qemu configuration files for all VMs.
             */
            if(backup_qemu_config_files($qemu_config_dir, $calculated_backup_dir."/".$qemu_backup_dirname))
            {
                echo "Successfully backed-up the QEMU config files.\n";
            }
            else
            {
                echo "Failed to backup the QEMU config files.\n";
            }
            
            // Get a list of the disk images for each VM.
            echo "Getting disk images info for VM: ".$this_vm["name"]."\n";
            $disk_list_array = runVirshCommand("virsh domblklist ".$this_vm["name"], true);
            
            if($disk_list_array["return_value"] == 0)
            {
                $disk_loop_count = 1;
                foreach($disk_list_array["output"] as $this_disk)
                {
                    if(preg_match("/\.qcow2|raw$/", $this_disk["source"]) === 1)
                    {
                        
                        $dir_name = dirname($this_disk["source"]);
                        $filename = basename($this_disk["source"]);
                        echo "\tBacking-up disk image filename: ".$filename."\n";
                        
                        $time_before = time();
                        if(backup_disk_image($dir_name, $filename, $calculated_backup_dir, $this_vm["name"]))
                        {
                            $time_after = time();
                            $backup_diration_hours = round((($time_after-$time_before)/3600));
                            echo "\tSuccessfully backed up disk image filename: ".$filename." for VM: ".$this_vm["name"].".  It took ".$backup_diration_hours." hours.\n";
                        }
                        else
                        {
                            echo "\tFailed to backup disk: ".$this_disk["source"]."\n";
                        }
                    }
                }
            }
            else
            {
                echo "Failed to retrieve disk image information for VM: ".$this_vm["name"]."\n";
            }
            
            // Only turn on VMs that were originally running before the backup.
            if($this_vm["state"] == "running")
            {
                // Now that each disk for this VM has been processed.  Turn on the VM.
                $output_array = runVirshCommand("virsh start ".$this_vm["name"]);
                if($output_array["return_value"] == 0)
                {
                    echo "\tSuccessfully started VM: ".$this_vm["name"]."\n";
                }
                else
                {
                    echo "\tFailed to start VM: ".$this_vm["name"]." after backup operation.\n";
                }
            }
        }
        
        /*
         * Finally unmount any network drives.
         */
        if($backup_dest_type == "cifs")
        {
            $output_array = runLocalCommand("umount ".$calculated_backup_dir, true);
            if($output_array["return_value"] == 0)
            {
                echo "Successfully umounted remote network drive.\n";
            }
            else
            {
                echo "CRITICAL  Failed to unmount network drive mount point: ".$calculated_backup_dir."\n";
            }
        }
    }
}
else
{
    $error_message = null;
    if(!empty($vm_listing_array["output"]))
    {
        if(is_array($vm_listing_array["output"]))
        {
            $error_message = implode(" ", $vm_listing_array["output"]);
        }
        else
        {
            $error_message = $vm_listing_array["output"];
        }
    }
    echo "Virsh command returned error code: ".$vm_listing_array["return_value"]." with an error of: ".$error_message."\n";
}


exit(0);



function backup_qemu_config_files($source_dir_path, $dest_dir_path)
{    
    $source_dir_path = trim($source_dir_path);
    $dest_dir_path = trim($dest_dir_path);
    
    if(is_dir($source_dir_path))
    {
        if(is_dir($dest_dir_path))
        {
            if(chdir($source_dir_path))
            {
                $output_array = runLocalCommand("tar -cf - * | tar -xf - -C ".$dest_dir_path."/", true);
                if($output_array["return_value"] == 0)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            echo "FATAL ERROR: Dest QEMU path: ".$dest_dir_path." does not exist.  Please fix.\n";
            return false;
        }
    }
    else
    {
        echo "FATAL ERROR:  Source QEMU path: ".$source_dir_path." does not exist.  Please fix.\n";
        return false;
    }
}


function backup_disk_image($source_dirname, $source_filename, $dest_dir_path, $vm_name)
{
    global  $backup_dest_type,
            $disk_images_backup_dirname,
            $backup_username,
            $backup_password;
    
    $source_dirname = trim($source_dirname);
    $source_filename = trim($source_filename);
    $dest_dir_path = trim($dest_dir_path);
    $vm_name = trim($vm_name);
    
    $dest_dir_path = $dest_dir_path."/".$disk_images_backup_dirname."/".$vm_name;
    if(!is_dir($dest_dir_path))
    {
        if(!mkdir($dest_dir_path, 0750, true))
        {
            echo "FATAL ERROR:  Failed to created backup destination directory: ".$dest_dir_path;
            return false;
        }
    }
    
    $return_var = copy($source_dirname."/".$source_filename, $dest_dir_path."/".$source_filename);
    return $return_var;
}





function wait_for_vm_shutdown($domain_name, $max_wait_seconds)
{
    $sleep_interval = 5;
    $loop_count = 1;
    while(true)
    {
        $output_array = runVirshCommand("/usr/bin/virsh domstate $domain_name", false);
        if($output_array["output"][0] == "shut off")
        {
            return true;
        }
        else
        {
            sleep($sleep_interval);
            if(($loop_count*$sleep_interval)>=$max_wait_seconds)
            {
                return false;
            }
        }
        $loop_count++;
    }
}






function runVirshCommand($command, $uses_header = false)
{
    $output_array = array();
    
    $output = runLocalCommand($command, true);
    
    if($output["return_value"] != 0)
    {
        return $output;
    }
    else
    {
        /*
         * Now strip the column headings line and dashed line from the output.
         */
        $listing_started = false;
        $previous_line = null;
        $header_array = array();
        foreach($output["output"] as $this_output_line)
        {
            if($uses_header)
            {
                if(preg_match("/^-{2,}.*$/", $this_output_line) === 1)
                {
                    if(empty($header_array))  // We only build the header once.
                    {
                        // Process the header which was most likely on the previous line.
                        $header_array = preg_split("/\s{2,}/", $previous_line);
                        $header_array_size = sizeof($header_array);
                        for($i = 0; $i < $header_array_size; $i++)
                        {
                            $header_array[$i] = strtolower(trim($header_array[$i]));
                        }
                    }
                    $listing_started = true;
                    continue;
                }
            }

            if($listing_started || !$uses_header)
            {
                if(preg_match("/^$/", $this_output_line) === 1)
                {
                    // We don't care about blank lines.
                    continue;
                }
                else
                {
                    if($uses_header)
                    {
                        // Parse the line into elements based on any number of spaces.
                        $line_array = preg_split("/\s{2,}/", $this_output_line);

                        // Now lets get rid of any whitespace around our data and use the header to format the data.
                        $line_array_size = sizeof($line_array);
                        $trim_line_array = array();

                        for($i = 0; $i < $line_array_size; $i++)
                        {
                            $trim_line_array[$header_array[$i]] = trim($line_array[$i]);
                        }
                        $output_array[] = $trim_line_array;
                    }
                    else
                    {
                        $output_array[] = $this_output_line;
                    }
                }
            }
            $previous_line = $this_output_line;  // We mainly use this to grab the header after the dashed line is found.
        }
    }
    return array("output" => $output_array, "return_value" => $output["return_value"]);
}


function runLocalCommand($command, $debug = false)
{
    /*
     * ToDo:  Need to check that we are only being called by authorized Classes.
     */
    $return_value = null;
    $output = null;

    exec($command, $output, $return_value);

    if($return_value == 0)
    {
        if(sizeof($output) == 1)
        {
            if($debug)
            {
                return array("output" => $output[0], "return_value" => $return_value);
            }
            else
            {
                return $output[0];
            }
        }
        else if(sizeof($output) > 1)
        {
            if($debug)
            {
                return array("output" => $output, "return_value" => $return_value);
            }
            else
            {
                return $output;
            }
        }
        else
        {
            if($debug)
            {
                return array("output" => $output, "return_value" => $return_value);
            }
            else
            {
                return $output;
            }
        }
    }
    else if($return_value == 255)
    {  
        return array("output" => "ERROR running command: " . $command, "return_value" => $return_value);
    }
    else if ($return_value > 0)
    {
        if(sizeof($output) > 1)
        {
            // Got an error from command.
            if($debug)
            {
                return array("output" => $output, "return_value" => $return_value);
            }
            else
            {
                return $return_value;
            }
        }
        else
        {
            // Got an error from command.
            if($debug)
            {
                if(isset($output[0]))
                {
                    return array("output" => $output[0], "return_value" => $return_value);
                }
                else
                {
                    return array("output" => null, "return_value" => $return_value);
                }
            }
            else
            {
                return $return_value;
            }
        }
    }
}

function prerequisites()
{
    global  $backup_dest_type,
            $backup_location,
            $backup_username,
            $backup_password,
            $qemu_backup_dirname,
            $disk_images_backup_dirname,
            $backup_domain;
    
    $date = date("Y-m-d");
    
    
    
    if($backup_dest_type == "disk")
    {
        $output_array = runLocalCommand("ls -d /media/*/*Backup*", true);
        if($output_array["return_value"] == 0)
        {
            if(!is_array($output_array["output"]) && !empty($output_array["output"]))
            {
                $backup_location = $output_array["output"];
            }
            else
            {
                echo "FATAL ERROR: Too many external backup media disks exist.  Can't determine which one to use.  No backups performed.\n";
                return false;
            }
        }
        else
        {
            $error_string = null;
            if(is_array($output_array["output"]))
            {
                $error_string = implode(" ", $output_array["output"]);
            }
            else
            {
                $error_string = $output_array["output"];
            }
            echo "FATAL ERROR:  No external backup media disks mounted or exist.  Error was: ".$error_string."\n";
            return false;
        }
        
        $backup_location = $backup_location."/kvm-backup/".$date;
        
        if(!is_dir($backup_location))
        {
            if(!mkdir($backup_location, 0750, true))
            {
                echo "FATAL ERROR:  Failed to create backup directory: ".$backup_location.".\n";
                return false;
            }
        }
        
        if(!is_dir($backup_location."/".$qemu_backup_dirname))
        {
            if(!mkdir($backup_location."/".$qemu_backup_dirname, 0750))
            {
                echo "FATAL ERROR: Failed to create QEMU config backup dir: ".$qemu_backup_dirname."\n";
                return false;
            }
        }

        if(!is_dir($backup_location."/".$disk_images_backup_dirname))
        {
            if(!mkdir($backup_location."/".$disk_images_backup_dirname, 0750))
            {
                echo "FATAL ERROR: Failed to create backup directory for disk images: ".$disk_images_backup_dirname."\n";
                return false;
            }
        }
        
        return $backup_location;        
    }
    else if($backup_dest_type == "cifs")
    {
        /*
         * First get the user id and username for which we are being run as.
         */
        $uid = posix_geteuid();
        $user_info_array = posix_getpwuid($uid);
        $username = $user_info_array["name"];
        
        /*
         * Make sure the mount directory exists for the cifs share.
         */
        $mount_point = "/media/".$username."/cifs/kvm-backup/".$date;
        if(!is_dir($mount_point))
        {
            if(!mkdir($mount_point, 0750, true))
            {
                echo "FATAL ERROR:  Could not create directory mount point: ".$mount_point."\n";
                return false;
            }
        }
        
        /*
         * Now that we know our mount point exists, let's mount the network drive 
         * to the mount point.
         */
        $output_array = runLocalCommand("mount -t cifs ".$backup_location." ".$mount_point." -o username=".$backup_username.",password=".$backup_password.",domain=".$backup_domain, true);
        
        if($output_array["return_value"] == 0)
        {
            /*
             * Create our other subdirs for the qemu config files and the disk images.
             */
            if(!is_dir($mount_point."/".$qemu_backup_dirname))
            {
                if(!mkdir($mount_point."/".$qemu_backup_dirname, 0750))
                {
                    echo "FATAL ERROR: Failed to create QEMU config backup dir: ".$qemu_backup_dirname."\n";
                    return false;
                }
            }

            if(!is_dir($mount_point."/".$disk_images_backup_dirname))
            {
                if(!mkdir($mount_point."/".$disk_images_backup_dirname, 0750))
                {
                    echo "FATAL ERROR: Failed to create backup directory for disk images: ".$disk_images_backup_dirname."\n";
                    return false;
                }
            }
            return $mount_point;
        }
        else
        {
            $error_string = null;
            if(is_array($output_array["output"]))
            {
                $error_string = implode(" ", $output_array["output"]);
            }
            else
            {
                $error_string = $output_array["output"];
            }
            echo "FATAL ERROR:  Count not mount ".$backup_location." to ".$mount_point." .  Command error was: ".$error_string."\n";
            return false;
        }
        
    }
    else
    {
        
    }
}

