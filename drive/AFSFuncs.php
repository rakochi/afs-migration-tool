<?php
/* Copyright 2015 Adrian Rakochi, The University of Michigan

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License. */

// Change directory to user's AFS home space

// Get current user's uniqname 
// $uniqname = $_SERVER["REMOTE_USER"]; 

function getUserDir($uniqname)
{
  echo "getUserDir";
  $afsPath = "/afs/umich.edu/user/{$uniqname[0]}/{$uniqname[1]}/{$uniqname}";
  echo $afsPath;
  if (!chdir($afsPath)) 
  {
        exit("Could not access user's home directory");
  }
  return $afsPath;
}

// Call PHP script to send the notification email
//include 'notification_email.php';
//echo "The script has finished running!";

// Function that filters out empty AFSFile objects
function filterEmptyObjects($inputFile) 
{
        return !($inputFile->path == null);
}


/* Given a file path, returns the path of the parent folder 
   E.g. '/afs/umich.edu/u/n/uniqname/folder/file' would return 
           '/afs/umich.edu/u/n/uniqname/folder' */ 
function getParentFolder($path) 
{ 
    return substr($path, 0, strrpos($path, "/")); 
} 

/* Given a file path, returns the file or end folder's name 
   E.g. '/afs/umich.edu/u/n/uniqname/folder/file' would return 
           'file' */ 
function getFileName($path) 
{
    return substr($path, strrpos($path, "/") + 1); 
} 

?>
