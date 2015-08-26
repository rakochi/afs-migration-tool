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
  ++$UsersAFSObj->numFoldersUploaded;
  $logline = date('Y-m-d H:i:s') . " User's AFS Path: " . $UsersAFSObj->afsPath . "\n"; 
  $logline = date('Y-m-d H:i:s') . " Our root folder ID: " . $UsersAFSObj->folderList[$UsersAFSObj->afsPath] . "\n"; 
  fwrite($configObj->logFile, $logline);
  
  foreach ($UsersAFSObj->folderList as $key => $value) 
  {

    // Avoid creating a folder for the root directory 
    if (strcmp($key, $UsersAFSObj->afsPath) == 0) 
    { 
      continue; 
    } 

    // Make sure the folder still exists in AFS  
    if (!file_exists($key)) 
    { 
      continue; 
    } 

    $parentFolderID = $UsersAFSObj->folderList[getParentFolder($key)];
    $logline = date('Y-m-d H:i:s') . " Parent folder name: " . getParentFolder($key) . "\n"; 
    $logline = $logline . date('Y-m-d H:i:s') . " Parent folder ID: " . $parentFolderID . "\n"; 
    fwrite($configObj->logFile, $logline);

    // See if the access token is about to expire
    if ($client->isAccessTokenExpired())
    {
      //Trade access token for refresh token
      if ($client->refreshToken($configObj->refreshToken) == null)
      {
        $logline = date('Y-m-d H:i:s') . ": Using refresh token, access token granted. \n"; 
        fwrite($configObj->logFile, $logline);
      }
      else
      {
        $logline = date('Y-m-d H:i:s') . ": Unable to obtain access token. \n"; 
        fwrite($configObj->logFile, $logline);
      }
    }

    //Create folder 
    $logline = date('Y-m-d H:i:s') . " The folder name: " . getFileName($key) . "\n"; 
    $folder = createFolder($drive_service, getFileName($key), "", $parentFolderID, $configObj);

    if ($folder)
    {
      //If creation worked, store folder ID for the file uploads
      ++$UsersAFSObj->numFoldersUploaded;
      $UsersAFSObj->folderList[$key] = $folder->getID();
      $logline = $logline . date('Y-m-d H:i:s') . " Success! The ID of this new folder is: " . $folder->getID()  . "\n"; 
      fwrite($configObj->logFile, $logline); 
    }
    else
    {
      $logline = $logline . date('Y-m-d H:i:s') . " The following folder could not be created: " . $folder->getID()  . "\n"; 
      fwrite($configObj->logFile, $logline); 
    }
  }
}

function doUpload(&$drive_service, &$client, &$file, &$parentFolderID, &$configObj)
{
  //Chunk size is in bytes
  //Currently, resumable upload uses chunks of 1000 bytes 
  $chunk_size = 1000; 
  //doUpload with exponential backoff, five tries
  for ($n = 0; $n < 5; ++$n) 
  {
    try 
    {
      //Choose between media and resumable depending on file size 
      if ($file->size > $chunk_size)
      {
        insertFileResumable($drive_service, &$client, getFileName($file->path), "", $parentFolderID, $file->type, $file->path, $configObj);
      }
      else
      {
        insertFileMedia($drive_service, getFileName($file->path), "", $parentFolderID, $file->type, $file->path, $configObj);
      }
      //If upload succeeded, return null
      return;
    } 
    catch (Google_Exception $e) 
    {
      if ($e->getCode() == 403 || $e->getCode() == 503) 
      {
        $logline = date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n"; 
        $logline = $logline . date('Y-m-d H:i:s'). "Retrying... \n";
        fwrite($configObj->logFile, $logline);
        // Apply exponential backoff.
        usleep((1 << $n) * 1000000 + rand(0, 1000000));
      }
    } 
    catch (Exception $e)
    {
        $logline = date('Y-m-d H:i:s'). ": Unable to upload file.\n";
        $logline = $logline . "Reason: " . $e->getCode() . " : " . $e->getMessage() . "\n";
        fwrite($configObj->logFile, $logline);
        //If upload failed because of unrecognized error, return the file
        return $file;
    }
  }
  //If upload failed, return the file
  return $file;
}

function uploadFiles(&$drive_service, &$client, &$configObj, &$UsersAFSObj) 
{

  foreach ($UsersAFSObj->fileList as $value) 
  { 
    $logline = date('Y-m-d H:i:s') . " File path: " . $value->path . "\n"; 
    fwrite($configObj->logFile, $logline);
    // Make sure the file still exists in AFS 
    if (!file_exists($value->path)) 
    { 
        $logline = date('Y-m-d H:i:s') . " Upload failed.  File does not exist!" . "\n"; 
        fwrite($configObj->logFile, $logline);
        continue; 
    } 
 
    $parentFolderID = $UsersAFSObj->folderList[getParentFolder($value->path)]; 
    $logline = date('Y-m-d H:i:s') . " Parent folder name: " . $value->path . "\n"; 
    $logline = $logline . date('Y-m-d H:i:s') . " Parent folder ID: " . $parentFolderID  . "\n"; 
    fwrite($configObj->logFile, $logline);

    // If it couldn't find the parent folder's ID, skip this file 
    if ($parentFolderID == null) 
    { 
        $logline = date('Y-m-d H:i:s') . " Upload failed.  No parent ID for parent folder!" . "\n"; 
        fwrite($configObj->logFile, $logline);
        continue; 
    } 


    if ($client->isAccessTokenExpired())
    {
      //Trade access token for refresh token
      if ($client->refreshToken($configObj->refreshToken) == null)
      {
        $logline = date('Y-m-d H:i:s') . ": Using refresh token, access token granted. \n"; 
        fwrite($configObj->logFile, $logline);
      }
      else
      {
        $logline = date('Y-m-d H:i:s') . ": Unable to obtain access token. \n"; 
        fwrite($configObj->logFile, $logline);
      }
    }
    
    //decides whether to do media or resumable upload and uploads file 
    //1000 bytes is currently hardcoded as chunk size for resumable upload function
    //make a config file for the chunk size?
    $logline = date('Y-m-d H:i:s') . " Doing upload..." . "\n"; 
    fwrite($configObj->logFile, $logline);

    $results = doUpload($drive_service, $client, $value, $parentFolderID, $configObj);

    if ($results)
    {
      array_push($UsersAFSObj->failedFiles, $value->path);
      $logline = date('Y-m-d H:i:s') . " Failure" . "\n"; 
      fwrite($configObj->logFile, $logline);
    }
    else
    {
      ++$UsersAFSObj->numFilesUploaded;
      $logline = date('Y-m-d H:i:s') . " Success!" . "\n"; 
      fwrite($configObj->logFile, $logline);
    }
  }
} 

function insertFileMedia(&$service, $title, $description, $parentId, $mimeType, $filename, &$configObj) 
{
  $file = new Google_Service_Drive_DriveFile();
  $file->setTitle($title);
  $file->setDescription($description);
  $file->setMimeType($mimeType);
    
  // Set the parent folder.
  if ($parentId != null) 
  {
    $parent = new Google_Service_Drive_ParentReference();
    $parent->setId($parentId);
    $file->setParents(array($parent));
  }

  $data = file_get_contents($filename);

  $createdFile = $service->files->insert($file, array(
    'data' => $data,
    'mimeType' => $mimeType,
    'uploadType' => 'media',
  ));

  return;
}


function insertFileResumable(&$service, &$client, $title, $description, $parentId, $mimeType, $filepath, &$configObj) {

  $file = new Google_Service_Drive_DriveFile();
  $file->setTitle($title);
  $file->setDescription($description);
  $file->setMimeType($mimeType);
  $chunkSizeBytes = 1 * 1024 * 1024;

  if ($parentId != null) 
  {
    $parent = new Google_Service_Drive_ParentReference();
    $parent->setId($parentId);
    $file->setParents(array($parent));
  }

  // Call the API with the media upload, defer so it doesn't immediately return.
  $client->setDefer(true);
  $request = $service->files->insert($file);

  // Create a media file upload to represent our upload process.
  $media = new Google_Http_MediaFileUpload
  (
  $client,
  $request,
  $mimeType, 
  null,
  true,
  $chunkSizeBytes
  );
  $media->setFileSize(filesize($filepath));

  // Upload the various chunks. $status will be false until the process is
  // complete.
  $status = false;
  $handle = fopen($filepath, "rb");
  while (!$status && !feof($handle)) 
  {
    for ($n = 0; $n < 5; ++$n)
    {
      try
      {
        $chunk = fread($handle, $chunkSizeBytes);
        $status = $media->nextChunk($chunk);
        break;
      }
      catch(Google_Exception $e)
      {
        if ($e->getCode() == 403 || $e->getCode() == 503)
        {
          $logline = date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n"; 
          $logline = $logline . date('Y-m-d H:i:s'). "Retrying... \n";
          fwrite($configObj->logFile, $logline);
          usleep((1 << $n) * 1000000 + rand(0, 1000000));
        }
      }
      catch(Exception $e)
      {
          $logline = date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n"; 
          fwrite($configObj->logFile, $logline);
          throw $e;
      }
    }
  }

  fclose($handle);
  // Reset to the client to execute requests immediately in the future.
  $client->setDefer(false);

  return;

}

function createFolder(&$service, $title, $description, $parentId, &$configObj) 
{

  for ($n = 0; $n < 5; ++$n) 
  {
    try 
    {
   
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

      $createdFile = $service->files->insert($file, array());
      return $createdFile;

    } 
    catch (Google_Exception $e) 
    {
      if ($e->getCode() == 403 || $e->getCode() == 503) 
      {
        $logline = date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . "\n"; 
        $logline = $logline . date('Y-m-d H:i:s'). "Retrying... \n";
        fwrite($configObj->logFile, $logline);
        // Apply exponential backoff.
        usleep((1 << $n) * 1000000 + rand(0, 1000000));
      } 
    }
    catch (Exception $e)
    {
        $logline = date('Y-m-d H:i:s'). "Unable to create folder.\n";
        $logline = $logline . "Reason: " . $e->getCode() . " : " . $e->getMessage() . "\n";
        fwrite($configObj->logFile, $logline);
        //If unable to create folder because of unrecognized error, return nothing
        return null; 
    }
  }
}
?> 
