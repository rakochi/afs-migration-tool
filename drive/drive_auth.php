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


require_once '../google-api-php-client/src/Google/autoload.php';
include_once 'Auth.php';

session_start();

$filename = "/afs/umich.edu/group/m/mafstool/Private/refresh.csv";

$client = new Google_Client();
$client->setAuthConfigFile('../../Private/drive_credentials.json');
$client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . '/afsmigrator/drive/drive_auth.php');
$client->addScope(Google_Service_Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('select_account');

//If user denies authorization
if (isset($_GET['error']))
{
  //Redirect user to error page
  $_SESSION['error'] = "You must allow this tool to access Drive in one of your gmail accounts for this tool to work. " . 
                       "Please <a href='Cloud.php'>try again</a>."; 
  header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . '/afsmigrator/error_page.php');
}
//If user accepts, but authorization has not yet occurred
else if (! isset($_GET['code'])) 
{
  //Create and send auth url
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} 
//User has authorized the app
else 
{
  try
  {
      //Trade authorization code for access and refresh token
      $client->authenticate($_GET['code']);
      //Save refresh token to .csv
      $refresh_token = $client->getRefreshToken();
      if (!$refresh_token)
      {
        die("No refresh token generated.  Please contact support."); 
      }
      $refresh_string =  $_SESSION['uniqname'] . ", " . $refresh_token . "\n";
      file_put_contents($filename, $refresh_string, FILE_APPEND | LOCK_EX);
      //Check that save was successful
      if (get_stored_refreshtoken($filename, $_SESSION['uniqname']) == null)
      {
        die("Refresh Token could not be saved.  Try again.");
      }
    //Store access token as session variable and redirect to main script
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/afsmigrator/drive/upload_to_drive.php';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  
  }
  catch (Exception $e)
  {
    echo "An error occurred: " . $e->getMessage();
  } 
}

?>

