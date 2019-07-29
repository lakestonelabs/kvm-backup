<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RemovableStorage
 *
 * @author mlee
 */

require_once __DIR__.'/Storage.php';

class RemovableStorage extends Storage
{    
    public function __construct($uuid)
    {
        parent::__construct($uuid);
    }
    
    public function activate()
    {
        parent::activate();
        return true;
    }
    
    public function deActivate()
    {
        parent::deActivate();
        return true;
    }
}
