<!DOCTYPE html>
<html>
<header><title>Google Drive AFS Migrator</title></header>
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

set_time_limit(0);
ignore_user_abort(true);

require_once '../google-api-php-client/src/Google/autoload.php';
include_once 'AFSFileClass.php';
include_once 'ConfigItems.php';
include_once 'UsersAFS.php';
require_once 'GoogleDriveFuncs.php';

session_start();

$client = new Google_Client();
$client->setAuthConfigFile('../../Private/drive_credentials.json');
$client->addScope(Google_Service_Drive::DRIVE);
$client->setAccessType('offline');

//Need to have uniqname available in sessions for the bounce to the authorization script
$_SESSION["uniqname"] = $_SERVER["REMOTE_USER"];

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  try
  {

    $client->setAccessToken($_SESSION['access_token']);

    if ($client->isAccessTokenExpired())
    { 
      $redirect_uri = 'https://mfile-test.www.umich.edu/afsmigrator/drive/drive_auth.php';
      header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));    
    }

    $logfileName = "../logs/drive/" . $_SESSION["uniqname"] . "-" . date('Y-m-d H:i:s');
    $myLogFile = fopen($logfileName, "w") or die("Unable to create logfile.");
    $logline = date('Y-m-d H:i:s') . ": Access token obtained" . "\n"; 
    fwrite($myLogFile, $logline);

    $configObj = new ConfigItems;
    $configObj->logFile = $myLogFile;
    $configObj->refreshTokenFilename = "../../Private/refresh.csv";
    $configObj->uniqname = $_SERVER["REMOTE_USER"];

    $logline = date('Y-m-d H:i:s') . ": configObj initialized" . "\n"; 
    fwrite($configObj->logFile, $logline);

    $drive_service = new Google_Service_Drive($client);

    $UsersAFSObj = new UsersAFS;
    $UsersAFSObj->folderList = $_SESSION['folderList'];
    $UsersAFSObj->fileList = $_SESSION['fileList'];
    $UsersAFSObj->afsPath = $_SESSION['afsPath'];
    $UsersAFSObj->failedFolders = array();
    $UsersAFSObj->failedfiles = array();

    //Unset session variables to save space on server
    unset($_SESSION['folderList']);
    unset($_SESSION['fileList']);
    unset($_SESSION['afsPath']);

    /* Closes browser connection, displays the file on the include line,
       and runs the last two lines after flush */

    // Buffer the upcoming output
    ob_start(); 

    include '../request_submitted.html';

    // Get the size of the output
    $outputSize = ob_get_length();

    // Send telling the browser to close the connection
    header("Content-Encoding: none\r\n");
    header("Content-Length: $outputSize");
    header("Connection: close\r\n");

    // Flush all output
    ob_end_flush();
    ob_flush();
    flush();

    //Creating folders and uploading files in the background
    createFolders($drive_service, $client, $configObj, $UsersAFSObj);
    uploadFiles($drive_service, $client, $configObj, $UsersAFSObj);

    $logline = date('Y-m-d H:i:s') . ": upload complete! \n"; 
    fwrite($configObj->logFile, $logline);
    }
    catch (Exception $e)
    {
    echo "An error occurred: " . $e->getMessage();
    } 

}
else {
  $redirect_uri = 'https://mfile-test.www.umich.edu/afsmigrator/drive/drive_auth.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

?>
</body>
</html>
