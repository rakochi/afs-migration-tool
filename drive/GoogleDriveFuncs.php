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

include_once 'Auth.php';
include_once 'AFSFuncs.php';
include_once 'AFSFileClass.php';
include_once 'ConfigItems.php';
include_once 'UsersAFS.php';

//Functions used in the script to upload files to Google Drive

function createFolders(&$drive_service, &$client, &$configObj, &$UsersAFSObj) 
{
  // Recreate the user's AFS directory structure inside Box
  
  $afsFilesFolder = createFolder($drive_service, "AFS Migration Files", "Home for migrated files", "root", $configObj);
  $UsersAFSObj->folderList[$UsersAFSObj->afsPath] = $afsFilesFolder->getID();
  $logline = date('Y-m-d H:i:s') . " User's AFS Path: " . $UsersAFSObj->afsPath . "\n"; 
  $logline = date('Y-m-d H:i:s') . " Our root folder ID: " . $UsersAFSObj->folderList[$UsersAFSObj->afsPath] . "\n"; 
  fwrite($configObj->logFile, $logline);

  $numFolders = 0;
  
  foreach ($UsersAFSObj->folderList as $key => $value) 
  {

     // Avoid creating a folder for the root directory 
     if (strcmp($key, $UsersAFSObj->afsPath) == 0) { 
     continue; 
     } 

     // Make sure the folder still exists in AFS  
    if (!file_exists($key)) { 
    continue; 
    } 
    
     // See if the access token is about to expire
    //will need to add this in
    
    $parentFolderID = $UsersAFSObj->folderList[getParentFolder($key)];
    $logline = date('Y-m-d H:i:s') . " Parent folder name: " . getParentFolder($key) . "\n"; 
    $logline = $logline . date('Y-m-d H:i:s') . " Parent folder ID: " . $parentFolderID . "\n"; 
    fwrite($configObj->logFile, $logline);

    if ($client->isAccessTokenExpired())
     {
        //Make sure we can read refresh token from .csv
        if (get_stored_refreshtoken($configObj->refreshTokenFilename, $configObj->uniqname) == null)
        {
          die("Refresh Token could not be loaded.  Try revoking access for this script and try again.");
        }
        //Trade access token for refresh token
        $client->refreshToken(get_stored_refreshtoken($configObj->refreshTokenFilename, $configObj->uniqname));
        $trade_string = "token exhange occured: " . $configObj->uniqname . " " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($configObj->refreshTokenFilename, $trade_string, FILE_APPEND | LOCK_EX);
     }
 
   //Create folder 
    $folder = createFolder($drive_service, getFileName($key), "", $parentFolderID, $configObj);
    $nextID = $folder->getID(); 

    if (!is_array($nextID)) 
    {
       $UsersAFSObj->folderList[$key] = $nextID;
       $numFolders++;
       
       $logline = date('Y-m-d H:i:s') . " The folder name: " . getFileName($key) . "\n"; 
       $logline = $logline . date('Y-m-d H:i:s') . " The ID of this new folder is: " . $nextID . "\n"; 
       fwrite($configObj->logFile, $logline);
    }
    else
    {
      $logline = date('Y-m-d H:i:s') . " There was an error creating this folder \n"; 
      fwrite($configObj->logFile, $logline);
    }
  }

}

function doUpload(&$drive_service, &$client, &$file, &$parentFolderID, &$configObj)
{
  //Chunk size is in bytes
  //Currently, resumable upload uses chunks of 1000 bytes 
  $chunk_size = 1000; 

  //Choose between media and resumable depending on file size 
   if ($file->size > $chunk_size)
   {
     insertFileResumable($drive_service, &$client, getFileName($file->path), "", $parentFolderID, $file->type, $file->path, $configObj);
   }
   else
   {
     insertFileMedia($drive_service, getFileName($file->path), "", $parentFolderID, $file->type, $file->path, $configObj);
   } 
}

function uploadFiles(&$drive_service, &$client, &$configObj, &$UsersAFSObj) 
{

   foreach ($UsersAFSObj->fileList as $value) { 
        $logline = date('Y-m-d H:i:s') . " File path: " . $value->path . "\n"; 
        fwrite($configObj->logFile, $logline);
        // Make sure the file still exists in AFS 
        if (!file_exists($value->path)) { 
            continue; 
        } 

        //check if token expired
        //insert code here
 
        $parentFolderID = $UsersAFSObj->folderList[getParentFolder($value->path)]; 
        $logline = date('Y-m-d H:i:s') . " Parent folder name: " . $value->path . "\n"; 
        $logline = $logline . date('Y-m-d H:i:s') . " Parent folder ID: " . $parentFolderID  . "\n"; 
        fwrite($configObj->logFile, $logline);

        // If it couldn't find the parent folder's ID, skip this file 
        if ($parentFolderID == null) { 
            continue; 
        } 


       if ($client->isAccessTokenExpired())
       {
        //Make sure we can read refresh token from .csv
        if (get_stored_refreshtoken($configObj->refreshTokenFilename, $configObj->uniqname) == null)
        {
          die("Refresh Token could not be loaded.  Try revoking access for this script and try again.");
        }
        //Trade access token for refresh token
        $client->refreshToken(get_stored_refreshtoken($configObj->refreshTokenFilename, $configObj->uniqname));
        $trade_string = "token exhange occured: " . $configObj->uniqname . " " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($configObj->refreshTokenFilename, $trade_string, FILE_APPEND | LOCK_EX);
       }
        
        //decides whether to do media or resumable upload and uploads file 
        //1000 bytes is currently hardcoded as chunk size for resumable upload function
        //make a config file for the chunk size?
         $logline = date('Y-m-d H:i:s') . " Doing upload..." . "\n"; 
         fwrite($configObj->logFile, $logline);
         doUpload($drive_service, $client, $value, $parentFolderID, $configObj);
 
        // If the API call returns an error, handle and retry it if possible 

        // Check to see if the file upload was successfull  
   }
} 

function insertFileMedia($service, $title, $description, $parentId, $mimeType, $filename, &$configObj) {
  $file = new Google_Service_Drive_DriveFile();
  $file->setTitle($title);
  $file->setDescription($description);
  $file->setMimeType($mimeType);
    
  // Set the parent folder.
  if ($parentId != null) {
    $parent = new Google_Service_Drive_ParentReference();
    $parent->setId($parentId);
    $file->setParents(array($parent));
  }

  try {
    $data = file_get_contents($filename);

    $createdFile = $service->files->insert($file, array(
      'data' => $data,
      'mimeType' => $mimeType,
      'uploadType' => 'media',
    ));
    
    // Uncomment the following line to print the File ID
    // print 'File ID: %s' % $createdFile->getId();

    return;
  } catch (Exception $e) {
    $logline = date('Y-m-d H:i:s') . " An error occurred: " . $e->getMessage() . "\n"; 
    fwrite($configObj->logFile, $logline);
  } 
}


function insertFileResumable($service, &$client, $title, $description, $parentId, $mimeType, $filepath, &$configObj) {

$file = new Google_Service_Drive_DriveFile();
$file->setTitle($title);
$file->setDescription($description);
$file->setMimeType($mimeType);
$chunkSizeBytes = 1 * 1024 * 1024;

if ($parentId != null) {
  $parent = new Google_Service_Drive_ParentReference();
  $parent->setId($parentId);
  $file->setParents(array($parent));
}

try
{
  // Call the API with the media upload, defer so it doesn't immediately return.
  $client->setDefer(true);
  $request = $service->files->insert($file);

  // Create a media file upload to represent our upload process.
  $media = new Google_Http_MediaFileUpload(
    $client,
    $request,
    $mimeType, 
    file_get_contents($filepath),
    true,
    $chunkSizeBytes
  );
  $media->setFileSize(filesize($filepath));

  // Upload the various chunks. $status will be false until the process is
  // complete.
  $status = false;
  $handle = fopen($filepath, "rb");
  while (!$status && !feof($handle)) {
    $chunk = fread($handle, $chunkSizeBytes);
    $status = $media->nextChunk($chunk);
   }

  // The final value of $status will be the data from the API for the object
  // that has been uploaded.
  $result = false;
  if($status != false) {
    $result = $status;
  }

  fclose($handle);
  // Reset to the client to execute requests immediately in the future.
  $client->setDefer(false);
}
catch (Exception $e)
{
  $logline = date('Y-m-d H:i:s') . " An error occurred: " . $e->getMessage() . "\n"; 
  fwrite($configObj->logFile, $logline);
}

}

function createFolder($service, $title, $description, $parentId = "root", &$configObj) {
  $file = new Google_Service_Drive_DriveFile();
  $file->setTitle($title);
  $file->setDescription($description);
  $file->setMimeType("application/vnd.google-apps.folder");

  // Set the parent folder.
  if ($parentId != null) 
  {
    $parent = new Google_Service_Drive_ParentReference();
    $parent->setId($parentId);
    $file->setParents(array($parent));
  }

  try {
//    $data = file_get_contents($filename);

    $createdFile = $service->files->insert($file, array());

    // Uncomment the following line to print the File ID
    // print 'File ID: %s' % $createdFile->getId();

    return $createdFile;
  } catch (Exception $e) {
    $logline = date('Y-m-d H:i:s') . " An error occurred: " . $e->getMessage() . "\n"; 
    fwrite($configObj->logFile, $logline);
  }
}
?> 
