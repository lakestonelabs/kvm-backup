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

#$backup_dest_type = "cifs";
#$backup_location = "//192.168.0.110/vm_backups";
#$backup_username = "backup";
#$backup_password = "backup_password";
#$backup_domain = "recovery";

$backup_dest_type = "disk";
$backup_location = null; // The prerequisites() function will build fill-in this variable.


