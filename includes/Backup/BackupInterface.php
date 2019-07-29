<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BackupInterface
 *
 * @author mlee
 */
interface BackupInterface
{
    public function run();
    public function isDue();
    public function getStatus();
}
