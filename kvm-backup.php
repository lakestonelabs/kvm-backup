#!/usr/bin/php
<?php

/* 
 * Copyright Mike Lee 2017
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

$home_dir = __DIR__;
$date = date("Y-m-d");
$time = date("G:i:s");


/*
 * Users can change stuff in this file.
 */
//$config_file = $home_dir."/config.php";
//require_once $config_file;

/*
 * This script uses the below file to write certain stuff to it.  Don't
 * modify or change this file.
 * 
 * Vars in the $system_file are:
 * $previously_used_backup_disk  // The disk name from $backup_disk_array that was previously used for backup. 
 * 
 */
$sytem_file = $home_dir."/system.php";
require_once $sytem_file;

$source_mount = "/ssd-raid-10";
$snapshot_name = "ssd_vmimages_snapshot";
$source_lg = "VgSsdRaid10";
$lvm_root = "/dev/".$source_lg;
$source_lvm = "VmImagesLv1";
$snapshot_source = $lvm_root."/".$source_lvm;
$snapshot_mount_point = "/mnt/snapshots/".$snapshot_name;
$source_size = 0;
$snapshot_size = 50;  // Size in GB of how big of source LVM changes we can accomodate.


$backup_root = "/mnt/ButteBackup";
/*
 * Make our backup path.
 * 
 * The indicies will be used to determine what disk to use for the current day
 * and the value of the index will be used to determine what disk to use on the
 * next backup.
 */
$backup_disks_array = array("RedDisk1" => "BlueDisk1", "BlueDisk1" => "RedDisk1");
$backup_relative = "backups";
$backup_path = null;  // This is to be dynamically built in the script depending on what backup USB disk we are using.
$backup_mount_point = null;
$backup_disk_free_space = 0;


/*
 * First determine what backup disk to use and if it's available.
 * 
 * TODO:  If no backup dest. exists, attempt to mount it.
 */
if(isset($previously_used_backup_disk))
{
    $backup_mount_point = $backup_root."/".$backup_disks_array[$previously_used_backup_disk];
    $backup_path = $backup_mount_point."/".$backup_relative."/".$date."/".$source_lg."-".$source_lvm;
}
else
{
    /*
     * Since the one we are looking for is not available just use whatever 
     * backup disk is availble.
     */
    $output_array = null;
    $output_array = runLocalCommand("df -Th | grep ".$backup_root." | awk '{print \$7 }'", true);
    if($output_array["return_value"] == 0)
    {
        $backup_mount_point = $output_array["output"][0];
        /*
         * The dest backup dir contains the Logical Group and Logical Volume name.
         * so that past backups can show where it came from (the source).
         */
        $backup_path = $backup_mount_point."/".$backup_relative."/".$date."/".$source_lg."-".$source_lvm;
    }
    else
    {
        echo "CRITICAL  No backup mount point coule be found.  Terminating backup now.\n";
        exit(1);
    }
}



/*
 * Delete any old backup directories on this backup mount point.
 * For exampe, it will search for directories in /mnt/ButteBackup/BlueDisk1/backups
 * for dirs that match patter xx-xx-xxxx for the dir name.
 */

//$output_array = null;
//$output_array = runLocalCommand("find ".$backup_mount_point."/".$backup_relative." -maxdepth 1 -type d -regextype posix-egrep -regex '.*/[0-9]{2}-[0-9]{2}-[0-9]{4}$' -print", true);
/*if($output_array["return_value"] == 0)
{
    $source_size = convert_to_bytes($output_array["output"][0]);
    if(!$source_size)
    {
        echo "CRITICAL Could not convert source size to bytes.  Terminating backup now.\n";
        exit(1);
    }
}
else
{
    echo "CRITICAL  Failed to determine size for source:$source_mount/libvirt  Terminating backup now.\n";
    exit(1);
}*/




// Remove any old backups.
// Find backup dirs older than the last backup and remove them to make room for this latest backup.
$output_array = null;
$output_array = runLocalCommand("find ".$backup_mount_point."/".$backup_relative." -maxdepth 1 -regextype posix-egrep -regex '.*/[0-9]{4}-[0-9]{2}-[0-9]{2}$' -newerct \"".$last_backup_date." ".$last_backup_start_time."\" ! -newerct \"".strtotime("+1 minutes", strtotime($last_backup_date." ".$last_backup_start_time))."\" -print", true);
echo "I'm going to delete these old dirs:\n";
var_dump($output_array[1]);
exit(0);

$old_backups = $backup_mount_point."/".$backup_relative."/*";
$files = glob($old_backups);
$now_date_obj = new DateTime(date("Y-m-d", time()));

foreach($files as $this_file)
{
    /*
     * Including check for "/mnt" in the dir path so that we don't 
     * accidentaly delete all of the files on our server.  This will
     * minimize any potential fallout.
     */
    if(is_dir($this_file) && preg_match("/^\/mnt\//", $this_file) === 1)
    {
        $this_dir_date_obj = new DateTime(date("Y-m-d", filemtime($this_file)));
        
        if($now_date_obj > $this_dir_date_obj)
        {
            echo "Deleting old backup dir: ".$this_file."... ";
            $output_array = runLocalCommand("rm -rf ".$this_file, true);
            if($output_array["return_value"] == 0)
            {
                echo "Ok";
            }
            else
            {
                echo "CRITICAL  Failed to delete old backup dir: $this_file .  Terminating backup now.\n";
                exit(1);
            }
        }
    }
}

exit(0);  // REMOVE AFTER DEBUG.



// Determine the size of the source.
$output_array = null;
$output_array = runLocalCommand("du -hcs ".$source_mount."/libvirt | awk '{ print \$1 }'", true);
if($output_array["return_value"] == 0)
{
    $source_size = convert_to_bytes($output_array["output"][1]);
    if(!$source_size)
    {
        echo "CRITICAL Could not convert source size to bytes.  Terminating backup now.\n";
        exit(1);
    }
}
else
{
    echo "CRITICAL  Failed to determine size for source:$source_mount/libvirt  Terminating backup now.\n";
    exit(1);
}


// Determine the size of our backup destination.
$output_array = null;
$output_array = runLocalCommand("df -Th ".$backup_mount_point." | awk '{ print \$5 }'", true);
if($output_array["return_value"] == 0)
{
    $dest_size = convert_to_bytes($output_array["output"][1]);
    if(!$dest_size)
    {
        echo "CRITICAL Failed to determine destination size.  Terminating backup now.\n";
        exit(1);
    }
}
else
{
    echo "CRITICAL  Failed to determine size for source:$source_mount/libvirt  Terminating backup now.\n";
    exit(1);
}

// Determine if our destination is big enough to perform the backup.
if($dest_size < $source_size)
{
    echo "CRITICAL:  Not enough free disk space on:".$backup_mount_point." for source:".$snapshot_mount_point.".  Terminating backup now.\n";
    exit(1);
}


// Make sure our backup dir exists.
if(!is_dir($backup_path))
{
    if(!mkdir($backup_path, 0775, true))
    {
        echo "CRITICAL:  Failed to create backup dir:".$backup_path.".  Terminating backup now.\n";
        exit(1);
    }
}


/*
 * Check to make sure there is not an existing snapshot.  If so, remove it. 
 */
$output_array = null;
$output_array = runLocalCommand("lvs ".$lvm_root." | grep ".$snapshot_name, true);
if($output_array["return_value"] == 0)
{
    echo "ALERT:  A previous snapshot LV already exists.  Attempting to remove it...";
    /*
     * A previous snapshot already exists.  We need to remove it.  But first
     * make sure that the previous snapshot is not already mouted.
     */
    runLocalCommand("umount ".$snapshot_mount_point, true);
    
    // Remove the previously-created snapshot logical volume.
    $output_array = null;
    $output_array = runLocalCommand("lvremove -f ".$lvm_root."/".$snapshot_name, true);
    if($output_array["return_value"] == 0)
    {
        echo "Ok\n";
    }
    else 
    {
        echo "\nCRITICAL:  Failed to remove old snapshot Logical Volume: $lvm_root/$snapshot_name .  Terminating backup now.\n";
        exit(1);
    }
}


// Create a LVM snapshot of the source LVM.
$output_array = null;
$output_array = runLocalCommand("lvcreate -s -n ".$snapshot_name." -L ".$snapshot_size."G ".$snapshot_source, true);
if($output_array["return_value"] == 0)
{
    echo "Snapshot ".$snapshot_name." successfully created.\n";
}
else
{
    echo "CRITICAL:  Failed to create snapshot '$snapshot_name'.  Terminating backup now.\n";
    exit(1);
}

// Make sure the mount point exists.
if(!is_dir($snapshot_mount_point))
{
    if(!mkdir($snapshot_mount_point, 0775, true))
    {
        echo "CRITICAL:  Failed to create mount point for snapshot LVM.  Can't continue with backup.\n";
        exit(1);
    }
}

// Now mount our snapshot LVM so we can backup its contents.
$output_array = null;
$output_array = runLocalCommand("mount ".$lvm_root."/".$snapshot_name." ".$snapshot_mount_point."/", true);
if($output_array["return_value"] == 0)
{
    echo "Successfully mounted snapshot:".$lvm_root."/".$snapshot_name." to mount point:".$snapshot_mount_point.".\n";
}
else
{
    echo "CRITICAL:  Failed to mount snapshot ".$lvm_root."/".$snapshot_name." to mountpoint:".$snapshot_mount_point.".\n";
    exit(1);
}



// Change to our backup source so we don't append the complete path to our tar path.
if(!chdir($snapshot_mount_point))
{
    echo "CRITICAL:  Failed to chdir to LVM mountpoint:".$snapshot_mount_point.".  Does it exist?  Can't continue with backup.\n";
    exit(1);
}


// Now backup the mounted snapshot to the backup path.
echo "Starting backup of:".$snapshot_mount_point." to backup path:".$backup_path.".  This may take many hours depending upon size.\n";
$backup_start_time = time();

$output_array = null;
$output_array = runLocalCommand("tar -cf - libvirt | tar -xf - -C ".$backup_path."/", true);
$backup_end_time = time();
if($output_array["return_value"] == 0)
{
    $backup_total_time_hours = round((($backup_end_time - $backup_start_time)/3600), 2);
    echo "Backup was successfull.  It took ".$backup_total_time_hours." hours.\n";
}
else
{
    echo "CRITICAL:  Failed to complete the backup.  Errors must have been reported by tar.\n";
    exit(1);
}



// Change back to our home dir so we can unmount the snapshot mount point.
if(!chdir($home_dir))
{
    echo "CRITICAL:  Failed to change to home dir: $home_dir.  We won't be able unmount our snapshot mount point.\n";
    exit(1);
}

// Unmount the snapshot mount point so we can then remove the LVM snapshot.
$output_array = null;
$output_array = runLocalCommand("umount ".$snapshot_mount_point, true);
if($output_array["return_value"] == 0)
{
    echo "Successfully unmounted the snapshot mount point: ".$snapshot_mount_point."\n";
}
else
{
    echo "CRITICAL:  Failed to unmount snapshot mount point".$snapshot_mount_point.".  LVM snapshot won't be able to be removed.\n";
    exit(1);
}


// Remove the LVM snapshot.
$output_array = null;
$output_array = runLocalCommand("lvremove -f ".$lvm_root."/".$snapshot_name, true);
if($output_array["return_value"] == 0)
{
    echo "Successfully removed the LVM snapshot after backup: ".$lvm_root."/".$snapshot_name."\n";
}
else
{
    echo "CRITICAL:  Failed to remove the LVM snapshot".$lvm_root."/".$snapshot_name." after backup.\n";
    exit(1);
}

echo "Backup successfull\n";


/*
 * Now we need to write certain things to the system file.
 * For instance we need to record which backup disk we last used so that we
 * can pick the other one for the next time the backup is ran. 
 */
$previously_used_backup_disk = trim(basename($backup_mount_point), "/");
$file_write_size = file_put_contents($home_dir."/system.php", "<?php\n\n\$previously_used_backup_disk = \"$previously_used_backup_disk\";"
                                                               . "\n\$last_backup_date = \"$date\";"
                                                               . "\n\$last_backup_start_time = \"$time\";");
if($file_write_size === false)
{
    echo "CRITICAL:  Failed to write to system file.  Next backup job will not try and write to the correct backup drive.\n";
    exit(1);
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






function convert_to_bytes($size)
{
    $units = array('K' => 10, 'M' => 20, 'G' => 30, 'T' => 40);
    foreach($units as $this_unit_letter => $this_unit_power)
    {
        if(preg_match("/$this_unit_letter\$/i", $size))
        {
            $number = rtrim($size, $this_unit_letter);
            $bytes = ($number * pow(2, $this_unit_power));
            return (int) round($bytes);
        }
    }
    return false;
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

