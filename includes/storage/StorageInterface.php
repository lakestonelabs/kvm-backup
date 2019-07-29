<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author mlee
 */
interface StorageInterface 
{
    public function mount();
    public function umount();
    public function activate();
    public function deActivate();
    public function getFreeSpace();
    public function getUsedSpace();
    public function getBusType();
    public function getFormatType();
    public function getUuid();
    public function isRemovable();
}
