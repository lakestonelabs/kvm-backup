<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JsonDbWrapper
 *
 * @author mlee
 */

require_once __DIR__ . "/DbInterface.php";
require_once __DIR__ . "/../Misc.php";
require_once __DIR__."/QemuBackupDbDocument.php";

class JsonDbWrapper
{    
    private     $db_files_array = [],
                $db_files_location = null;
    
    public function __construct($db_files_location) 
    {
        if(strlen($db_files_location) > 0)
        {
            /*
             * Load all of the Json db files.
             */
            if(is_dir($db_files_location))
            {
                $this->db_files_location = rtrim($db_files_location, "/");

                // Find all of the json db files in said location.
                $dir_iterator = new RecursiveDirectoryIterator($this->db_files_location);
                foreach(new RecursiveIteratorIterator($dir_iterator) as $this_file)
                {
                    if(preg_match("/.*\.json$/", $this_file) === 1)
                    {
                        // Make the filename our index for easy searching.
                        $this->db_files_array[basename($this_file, ".json")] = json_decode(file_get_contents($this_file));
                    }
                }
                ksort($this->db_files_array);
            }
            else
            {
                if(!mkdir($db_files_location))
                {
                    throw new Exception("Failed to create db location: ".$db_files_location);
                }
            }
        }
        else
        {
            throw new Exception("Must supply a valid path for the db_files_location.");
        }
    }
    
    public function addRecord(QemuBackupDbDocument $qemu_backup_db_doc_obj)
    {
        $filename = $this->db_files_location."/".$qemu_backup_db_doc_obj->getName()."_".time();
        touch($filename);
        
        
    }
    
    public function updateRecord(QemuBackupDbDocument $qemu_backup_db_doc_obj)
    {
        ;
    }
    
    public function getLatestBackingRecord() : stdClass
    {
        if(count($this->db_files_array) > 0)
        {
            /*
             * The most recent backing file is always going to be the
             * last key in the sorted array. 
             */
            return array_key_last($this->db_files_array);
        }
        else
        {
            return false;
        }
    }
    
    public function getRecord($doc_name) : stdClass
    {
        if(array_key_exists($doc_name, $this->db_files_array))
        {
            return $this->db_files_array[$doc_name];
        }
    }
    
}
