<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BackupScheduler
 *
 * @author mlee
 */

require_once __DIR__."/../Backup.php";

class BackupScheduler
{
    private $schedule_array = [];
    
    public function __construct()
    {
        ;
    }
    
    public function addBackup(Backup $backup_obj)
    {
        try
        {
            if($this->computeConflicts($backup_obj))
            {
                $this->schedule_array[$schedule_obj->getName()] = $schedule_obj;
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
   
    public function removeSchedule(string $schedule_name)
    {
        if(isset($this->schedule_array[$schedule_name]))
        {
            unset($this->schedule_array[$schedule_name]);
            return true;
        }
        else
        {
            throw new Exception("No such schedule exists with the name:".$schedule_name);
        }
    }
    
    public function runPendingJobs()
    {
        foreach($this->schedule_array as $this_schedule_name => $this_schedule_obj)
        {
            if($this_schedule_obj->isDue())
            {
                $this_schedule_obj->run();
            }
        }
    }
    
    /*
     * Go through all of the schedules and determine if there
     * are any conflicts.
     */
    private function computeConflicts($chedule_obj)
    {
        
    }
}
