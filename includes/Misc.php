<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


Class Misc
{
    
    public static function bytesFromString(string $size_string)
    {
        $regex = "^(\d+\.\d+)([b,B,m,M,g,G,t,T,p,P])$";
        $multipier = null;

        $size_array = ["b", "k", "m", "g", "t", "p"];
        $size = 0;
        $power = 1;

        if(preg_match("/".$regex."/", $size_string, $matches) === 1)
        {
            $multipier = $matches[1];
            foreach($size_array as $this_size)
            {
                if(strtolower($matches[2]) == $this_size)
                {
                    return ($multipier * $power);

                }
                else
                {
                    $power = ($power * 1024);
                }
            }
        }
        else
        {
            throw new Exception("Size string: ".$size_string." is an invalid format.");
        }
    }
    
    public static function date_to_timestamp($date_string = null, $date_interval_add = null, $date_interval_subtract = null)
    {
        $date_object = null;
        try
        {
            if(empty($date_string))
            {
                $date_object = new DateTime();
            }
            else
            {
                $date_object = new DateTime($date_string);
            }

            if(!empty($date_interval_add))
            {
                $date_object->add(new DateInterval($date_interval_add));
            }
            else if(!empty($date_interval_subtract))
            {
                $date_object->sub(new DateInterval($date_interval_subtract));
            }

            $microseconds = substr($date_object->format("u"), 0, 3);
            return $date_object->getTimestamp() . $microseconds;
        }
        catch (Exception $e)
        {
            echo $e->getMessage()."\n";
            return false;
        }
    }
    
    public static function checkIfSudoUser($user_id_number)
    {
        $uid = trim($user_id_number);
        $uid_info_array = posix_getpwuid($uid);
        $username = $uid_info_array["name"];
        $command = "sudo -l -U $username";
        $return_value = null;
        $output = null;
        
        exec($command, $output, $return_value);
        
        if((int) $return_value === 0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
        
    }
    
    
    public static function isGzipped($filename)
    {
        if(preg_match("/\.gz$/", $filename) === 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    
    /*
     * Returns complete path of compressed file upon success or throws
     * Exception upon errror/fail.
     */
    public static function gzipFile($full_path_to_file, $overwrite_existing = true, $keep_original_file = false, $gunzip = false)
    {
        if(!file_exists($full_path_to_file))
        {
            throw new Exception($full_path_to_file." does not exist.  No compression will be performed.");
        }
        
        $gzip_options = null;
        
        if($overwrite_existing)
        {
            $gzip_options .= "-f";
        } 
        
        if($keep_original_file)
        {
            $gzip_options .= " -k";  // Don't for get the space at the beginning.
        }

        // Name of the gz file we're creating
        $compressed_file = $full_path_to_file.".gz";
        
        $command = "gzip";
        if($gunzip)
        {
            $command = "gunzip";
        }

        $return_array = Misc::runLocalCommand($command." ".$gzip_options." ".$full_path_to_file, true);
        if($return_array["return_value"] > 0)
        {
            if(is_array($return_array["output"]))
            {
                $return_error = null;
                $return_error = implode(" ", $return_array["output"]);
            }
            else
            {
                $return_error = $return_array["output"];
            }
            throw new Exception($return_error);
        }
        return $compressed_file;
    }
    
    
    
    public static function runLocalCommand($command, $debug = false)
    {
        $return_value = null;
        $output = null;
        
        if(!empty($command))
        {
            exec($command, $output, $return_value);
        }
        
        // Make sure the $return_value is an integer.
        $return_value = (int) $return_value;
        
        if($return_value === 0)
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
        else if($return_value === 255)
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
    
    public static function castBoolToString($boolean_variable)
    {
        if($boolean_variable === true || $boolean_variable === 1 || $boolean_variable === "true")
        {
            return "true";
        }
        else if ($boolean_variable === false || $boolean_variable === 0 || $boolean_variable === "false")
        {
            return "false";
        }
        else
        {
            return false;
        }
    }
    
    public static function castStringToBool($string_true_or_false)
    {
        if(is_string($string_true_or_false) && ($string_true_or_false == "true" || $string_true_or_false == "false"))
        {
            if($string_true_or_false == "true")
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            throw new Exception(__METHOD__.": Paramter must be a string and must be either 'true' or 'false'");
        }
    }
    
    public static function testTcpHostPort($ip_address, $port_number, $timout = 15)
    {
        // Use @ in front of fsockopen to suppress any errors.  I.E. if host is down and can't connect.
        $file = @fsockopen($ip_address, $port_number, $errno, $errstr, $timout);
        if (!$file)
        {
            // Site is down
            return false;
        }
        else 
        {
            fclose($file);
            return true;
        }
    }
    
    public static function getRemoteFileStats($full_path_to_remote_file, $os_type, $ssh_username, $ssh_priv_key_location, $ssh_pub_key_location, $ssh_host, $ssh_port, $ssh_options_array = null, $count_lines = false)
    {
        /*
         * For right now all we care about is the filesize, and potentially the line count (if passed to function).
         */
        $remote_file_stats_array = array("size" => 0, "line_count" => 0);
        $command = null;
       
        $full_path_to_remote_file = trim($full_path_to_remote_file);
        $command = "ls -l ".$full_path_to_remote_file." | awk '{ print \\$5 }'";  // Yes we are using doubl backslashes.  It's needed to work properly.
        
        $return_array = self::runSshCommand($ssh_username, $ssh_priv_key_location, $ssh_pub_key_location, $ssh_host, $ssh_port, $command, $ssh_options_array, true);
        
        if($return_array["return_value"] == 0)
        {
            if(!empty($return_array["output"]) && is_numeric($return_array["output"]))
            {
                $remote_file_stats_array["size"] = (int) $return_array["output"];
            }
        }
        else
        {
            throw new Exception("Failed to stat filesize for remote file: $full_path_to_remote_file", ONVOY_FILE_STAT_FAIL);
        }

        // Now get the line count of the file (if defined to do so by passed parameter).
        if($count_lines)
        {
            $command = null;
            
            $filename = basename($full_path_to_remote_file);
            if(preg_match("/\.gz$/", $filename) === 1)
            {
                /*
                 * The file is compressed.  Therefore uncompress it on the fly
                 * with zcat and send it through 'wc'.
                 */
                
                if($os_type == "sunos")
                {
                    $command = "gzcat ".$full_path_to_remote_file." | wc -l";
                }
                else
                {
                    $command = "zcat ".$full_path_to_remote_file." | wc -l";
                }
            }
            else
            {
                $command = "wc -l ".$full_path_to_remote_file." | awk '{ print \\$1 }'";  // Yes we are using doubl backslashes.  It's needed to work properly.
            }
            
            $return_array = self::runSshCommand($ssh_username, $ssh_priv_key_location, $ssh_pub_key_location, $ssh_host, $ssh_port, $command, $ssh_options_array, true);
            
            if($return_array["return_value"] == 0)
            {
                if(!empty($return_array["output"]) && is_numeric($return_array["output"]))
                {
                    $remote_file_stats_array["line_count"] = (int) trim($return_array["output"]);
                }
            }
            else
            {
                throw new Exception("Failed to stat line count for remote file: $full_path_to_remote_file", ONVOY_FILE_STAT_FAIL);
            }
        }        
        return $remote_file_stats_array;        
    }
    
    
    
    /*
     * Added the tty option since some remote commands, such as sudo, require a tty terminal to operate.
     * 
     * NOTE when the calling function tests the return value, it should use == and not === .
     */
    public static function runSshCommand($ssh_username, $ssh_priv_key_location, $ssh_pub_key_location, $ssh_host, $ssh_port, $command, $ssh_options_array = null, $debug = false, $tty = null)
    {
        $always_ssh_options = array("BatchMode=yes", // Never prompt for a password.
                                    "ServerAliveInterval=15",  // Defaults to 300 when using BatchMode.  Overide default here to 15 seconds.
                                    "ServerAliveCountMax=3",  // How many times for ServerAliveInterval fails before disconnecting the session.
                                    "StrictHostKeyChecking=no", // Auto add client's fingerprint to known_hosts file.  Never ask.
                                    "UserKnownHostsFile=/dev/null");  // Send the client's fingerprint to a black hole.  We don't want to maintain a ssh known_hosts file.
        
        $always_ssh_options_string = null;
        foreach($always_ssh_options as $this_option)
        {
            $always_ssh_options_string .= " -o $this_option";
        }
        
        if(self::testTcpHostPort($ssh_host, $ssh_port))
        {
            $output = null;  // The array that will hold the lines of output.
            $return_value = null;
            $ssh_options_string = null;

            $ssh_switches = null;
            if(!empty($tty) && $tty = true)
            {
                $ssh_switches .= " -t ";
            }

            if(!empty($ssh_options_array))
            {
                if(is_string($ssh_options_array))
                {
                    $ssh_options_string = trim($ssh_options_array);
                }
                else if(is_array($ssh_options_array))
                {
                    foreach($ssh_options_array as $this_ssh_option)
                    { 
                        $ssh_options_string .=  ' -o ' . $this_ssh_option;                
                    }
                }
            }
            
            $ssh_options_string .= $always_ssh_options_string; // Add our mandatory ssh options to the options string.
            
            $command = 'ssh -q -i ' . $ssh_priv_key_location . " -p $ssh_port " . $ssh_switches . " ".$ssh_options_string . ' ' . $ssh_username . '@' . $ssh_host . " \"" . $command . "\"";
            
            exec($command, $output, $return_value);
            $return_value = (int) $return_value;

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
                    // Command executed successfully but no output was returned.
                    if($debug)
                    {
                        return array("output" => null, "return_value" => $return_value);
                    }
                    else
                    {
                        return null;
                    }
                }
            }
            else if($return_value == 255 || $return_value < 0)
            {  
                return array("output" => "ERROR running SSH command: " . $command, "return_value" => $return_value);
            }
            else if ($return_value > 0)
            {
                if(sizeof($output) > 1)
                {
                    // Got an error from the remote command.
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
                    // Got an error from the remote command.
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
            else
            {
                throw new Exception(__METHOD__.": ERROR:  Remote ssh command for host:(".$ssh_host.") did not have a return value.  How could this be?  Command was:".$command);
            }
        }
        else
        {
            throw new Exception("Could not connect to host: $ssh_host on port: $ssh_port");
        }
    }
    
      
    public static function getFileInfo($full_path_to_file)
    {
        $file_info_array = null;
        
        $php_file_stats_array = stat($full_path_to_file);
        $file_type_output = self::runLocalCommand("file $full_path_to_file");
        
        $file_type_output_temp_array = explode(":", $file_type_output);
        $file_type_output_temp_array = explode(",", $file_type_output_temp_array[1]);
        $compression_info_temp_array = explode(" ", trim($file_type_output_temp_array[0]));
        
        if(!empty($compression_info_temp_array))
        {
            if(isset($compression_info_temp_array[0]) && isset($compression_info_temp_array[1]))
            {
                if($compression_info_temp_array[1] == "compressed")
                {
                    $file_info_array["type"] = $compression_info_temp_array[1];
                    $file_info_array["compression_type"] = $compression_info_temp_array[0];
                }
                else
                {
                    $file_info_array["type"] = $compression_info_temp_array[1];
                    $file_info_array["compression_type"] = "none";
                }
            }
            else
            {
                $file_info_array["type"] = "uncompressed";
                $file_info_array["compression_type"] = "none";
            }
        }
        else
        {
            $file_info_array["type"] = "uncompressed";
            $file_info_array["compression_type"] = "none";
        }
        
        return array_merge($php_file_stats_array, $file_info_array);
        
    }
    
    
    public static function getLocalPidsOfCommand($command_string)
    {
        $command_string = trim($command_string);
        $return_result_array = self::runLocalCommand("ps -ef | grep '".$command_string."' | awk '{print \$2}'", true);
        
        if($return_result_array["return_value"] == 0)
        {
            if(!empty($return_result_array["output"]))
            {
                foreach($return_result_array["output"] as $this_pid)
                {
                    if(!is_numeric($this_pid))
                    {
                        // We got a PID that was not a number.
                        return false;
                    }
                }
                // If we made it this far then return the array of PIDs.
                return $return_result_array["output"];
            }
            else
            {
                // Command was successfully ran but no PIDs were found.
                return false;
            }
        }
        else
        {
            return false;
        }
    } 
    
    
    
    
    /*
     * Gets the last line of a file.  This new version of tailFile now supports
     * gzipped files transparently.
     */
    public static function tailFile($file)
    {
        $fp = null;
        
        // Determine what type of file this is so we can use the correct function to open it.
        $mime_type = mime_content_type($file);
        
        /*
         * Open the file depending upon what type it is.
         */
        if($mime_type === "application/x-gzip")
        {
            $fp = gzopen($file, "r");
        }
        else if($mime_type = "text/plain")
        {
            $fp = fopen($file, "r");
        }
        else
        {
            return false;
        }
        
        $continue_reading = true;
        $last_read_line = null;
        while($continue_reading)
        {
            if($mime_type === "application/x-gzip")
            {
                $data = gzread($fp, 8192);
            }
            else
            {
                $data = fread($fp, 8192);
            }            
            
            if(!empty($data))
            {
                $last_read_line = $data;
            }
            else
            {
                $continue_reading = false;
            }
        }

        // Split our data into an array based on line returns.
        $last_line_array = explode("\n", $last_read_line);
        
        // Close our file pointer.
        if($mime_type === "application/x-gzip")
        {          
            gzclose($fp);
        }
        else
        {
            fclose($fp);
        }
        
        return $last_line_array[(sizeof($last_line_array)-2)];
    }
    
    
    /*
     * If $replace_with_line is null then the found line that matches $fine_line
     * will not be written to the file.
     */
    public static function replaceLineInFile($file, $find_line, $replace_with_line = null)
    {
        if(!file_exists($file) || empty($find_line))
        {
            return false;
        }
        
        $temp_file = __DIR__."/.replace_temp";        
        $is_gzipped = false;
        
        $mime_type = mime_content_type($file);
        
        // Determine if the file is gzipped.  Not just by the file extension.
        if($mime_type === "application/x-gzip")
        {
            $is_gzipped = true;
        }
        
        if($is_gzipped)
        {
            $fp = gzopen($file, "r");
            $wfp = gzopen($temp_file, "w");
        }
        else
        {
            $fp = fopen($file, "r");
            $wfp = fopen($temp_file, "w");
        }
        


        $continue_reading = true;
        $surplus_data = null;
        while($continue_reading)
        {
            $data = gzread($fp, 8192);
            if(!empty($data))
            {
                // Find the last occurance of a newline.
                $last_newline_pos = strripos($data, PHP_EOL);

                if($last_newline_pos === false)
                {
                    // No newline was found within data.  Append this to our surplus data for the next loop iteration.
                    $surplus_data .= $data;
                    continue;
                }
                else
                {
                    // We have data with line returns.  Start processing.

                    // Get the part of the string that has newlines.  The remainder is surplus data (a partial line).
                    $stream_data_newlines = substr($data, 0, $last_newline_pos);  // Get the string that ends with a newline, but don't include the newline.

                    // Tokenize the string by newline and create an array entry for each complete line.
                    $stream_data_line_array = explode(PHP_EOL, $stream_data_newlines); // Build an array of complete lines.
                    if(!empty($surplus_data))
                    {
                        /*
                         * If we have surplus data from the previous loop iteration (string data not ending in a newline), 
                         * then this surplus data belongs to the beginning of the first newline string.
                         */
                        $stream_data_line_array[0] = $surplus_data.$stream_data_line_array[0];

                        // Reset our surplus buffer since we found a home for it.  VERY IMPORTANT!
                        $surplus_data = null;
                    }

                    /*
                     * Store the surplus data from this stream read. (data that did not have a newline at its end) so that 
                     * we can add it to the start of the data on the next readStreamData() call.
                     * Store any data at the end of the string that does not end in newline.
                     */
                    $surplus_data = substr($data, ($last_newline_pos + 1), (strlen($data) - ($last_newline_pos + 1)));
                }

                // Return any line data we received form our reading of the SshStream.
                if(sizeof($stream_data_line_array) > 0)
                {
                    foreach($stream_data_line_array as $this_line)
                    {
                        if($this_line == $find_line)
                        {
                            $this_line = $replace_with_line;
                        }
                        
                        /*
                         * If replace_with_line is null, then the matched line
                         * will not be written to the new version of the file.
                         */
                        if($this_line !== null)
                        {
                            if($is_gzipped)
                            {
                                gzwrite($wfp, $this_line.PHP_EOL);
                            }
                            else
                            {
                                fwrite($wfp, $this_line.PHP_EOL);
                            }
                        }
                    }
                }
            }
            else
            {
                $continue_reading = false;
            }
        }
        
        
        // Close our files.
        if($is_gzipped)
        {
            gzclose($fp);
            gzclose($wfp);
        }
        else
        {
            fclose($fp);
            fclose($wfp);
        }
        
        // Now replace the original file with the new one.  Keep the same name.
        unlink($file);
        rename($temp_file, $file);
        
        return true;
    }
    
    
    public static function convertToBytes($size)
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
    
    
    
    
    
    
    
    
} // End of Misc. Class



