<?php
	
	/* Copyright 2015 Hassan Mahmood, The University of Michigan
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
	    http://www.apache.org/licenses/LICENSE-2.0
	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License. */

	$numFolders = 0;
	$numFiles = 0;
	$totalFolders = count($folderList);
	$totalFiles = count($fileList);
	$choice = 'box';
	$failedFiles = array();
	

	// Get the client ID, client secret, and redirect uri
	$credentials = file_get_contents("/usr/local/webhosting/projects/mfile/etc/box_credentials.json");
	if (!$credentials) {
		include '../notification_email.php';
		exit();
	}

	$credentialsDecoded = json_decode($credentials, true);
	if ($credentialsDecoded == null) {
		include '../notification_email.php';
		exit();
	}

	$clientID = $credentialsDecoded['clientID'];
	$clientSecret = $credentialsDecoded['clientSecret'];
	$redirectUri = $credentialsDecoded['redirectUri'];

	// Verify that the credentials were read correctly
	if (empty($clientID) or empty($clientSecret) or empty($redirectUri)) {
		include '../notification_email.php';
		exit();
	}

	$logName = "../logs/box/" . $_SERVER["REMOTE_USER"] . " [" . date(DATE_RFC2822) . "].html";
	$stringArray = array();

	array_push($stringArray, "<pre>The auth code is: {$authCode}</pre>");

	// Create a POST request to get an access token
	$accessRequest = curl_init();
	curl_setopt_array($accessRequest, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'https://app.box.com/api/oauth2/token',
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => array(
			'grant_type' => 'authorization_code',
			'code' => $authCode,
			'client_id' => $clientID,
			'client_secret' => $clientSecret,
			'redirect_uri' => $redirectUri,
		)
	));

	// Send the request and check for a proper response
	if (! $accessResponse = curl_exec($accessRequest)) {
		include '../notification_email.php';
		exit();
	}
	curl_close($accessRequest);

	// Decode the response and make sure it didn't return an error
	$formatResponse = json_decode($accessResponse, true);

	if (!empty($formatResponse['error'])) {
		include '../notification_email.php';
		exit();
	}

	array_push($stringArray, "<pre>", print_r($formatResponse, true), "</pre>");

	// Save the tokens from the response
	$expireTime = time() + $formatResponse['expires_in'];
	$accessToken = $formatResponse['access_token'];
	$refreshToken = $formatResponse['refresh_token'];

	array_push($stringArray, "<pre>The A.T. is {$accessToken}\nThe R.T. is {$refreshToken}\nand it expires in {$formatResponse['expires_in']} seconds\n</pre>");

	if ($expireTime - time() < 100) {
		generateToken($accessToken, $refreshToken, $expireTime, $clientID, $clientSecret, $stringArray);
	}
	// Creates the root folder
	$firstID = createFile("{$_SERVER["REMOTE_USER"]}'s files from AFS", 0, null, false, $accessToken, $stringArray);
	if (is_array($firstID)) {
		// If it returned an error, handle it
		if (strcmp($firstID['code'], "item_name_in_use") == 0) {
			$date = date(DATE_RFC2822);
			$firstID = createFile("{$_SERVER["REMOTE_USER"]}'s files from AFS [{$date}]", 0, null, false, $accessToken, $stringArray);
		}
		else {
			// Create a new folder with a different name
			retryCall($firstID, "{$_SERVER["REMOTE_USER"]}'s files from AFS", 0, null, false, $accessToken, $stringArray);
		}
	}
	$folderList[$afsPath] = $firstID;

	array_push($stringArray, "The root parent id of {$afsPath} is: {$folderList[$afsPath]}", "<br>");

	// Recreate the user's AFS directory structure inside Box
	foreach ($folderList as $key => $value) {
		// Avoid creating a folder for the root directory
		if (strcmp($key, $afsPath) == 0) {
			continue;
		}
		// Make sure the folder still exists in AFS
		if (!file_exists($key)) {
			continue;
		}

		// Get a new access token if the old one is about to expire
		if ($expireTime - time() < 100) {
			$newToken = generateToken($accessToken, $refreshToken, $expireTime, $clientID, $clientSecret, $stringArray);

			// If it failed the first time, try again
			if (!$newToken) {
				sleep(5);
				$newToken = generateToken($accessToken, $refreshToken, $expireTime, $clientID, $clientSecret, $stringArray);
			}

			// If it failed again, exit
			if (!$newToken) {
				include '../notification_email.php';
				exit();
			}
		}

		$parentFolderID = $folderList[getParentFolder($key)];
		$temp = getFileName($key);
		array_push($stringArray, "Creating folder '{$temp}' from '{$key}' with parent ID: {$parentFolderID}");
		$nextID = createFile(getFileName($key), $parentFolderID, null, false, $accessToken, $stringArray);

		// If the API call returns an error, catch and handle it if possible
		if (is_array($nextID)) {
			array_push($stringArray, "Retrying this folder...", "<br><br>");
			retryCall($nextID, getFileName($key), $parentFolderID, null, false, $accessToken, $stringArray);
		}

		// If the call was successfull
		if (!is_array($nextID)) {
			$folderList[$key] = $nextID;
			$numFolders++;

			array_push($stringArray, "The ID of this new folder is: {$nextID}", "<br><br><br>");
		}
		else {
			array_push($stringArray, "Folder '" . getFileName($key) . "' NOT successfully created: {$nextID}, ", "Error: {$nextID['code']}", "<br>");
			array_push($failedFiles, $key);
		}
	}

	file_put_contents($logName, $stringArray);
	$numLines = 0;
	
	// Upload each of the user's files to Box
	foreach ($fileList as $value) {
		$numLines++;

		// Make sure the file still exists in AFS
		if (!file_exists($value->path)) {
			continue;
		}

		if ($expireTime - time() < 100) {
			$newToken = generateToken($accessToken, $refreshToken, $expireTime, $clientID, $clientSecret, $stringArray);

			// If it failed the first time, try again
			if (!$newToken) {
				sleep(5);
				$newToken = generateToken($accessToken, $refreshToken, $expireTime, $clientID, $clientSecret, $stringArray);
			}

			// If it failed again, exit
			if (!$newToken) {
				include '../notification_email.php';
				exit();
			}
		}

		$parentFolderID = $folderList[getParentFolder($value->path)];

		// If it couldn't find the parent folder's ID, skip this file
		if ($parentFolderID == null) {
			continue;
		}

		$nextID = createFile(getFileName($value->path), $parentFolderID, $value->path, true, $accessToken, $stringArray);

		// If the API call returns an error, handle and retry it if possible
		if (is_array($nextID)) {
			array_push($stringArray, "Retrying this file...", "<br><br>");

			retryCall($nextID, getFileName($value->path), $parentFolderID, $value->path, true, $accessToken, $stringArray);
		}

		// Check to see if the file upload was successfull
		if (!is_array($nextID)) {
			$numFiles++;
			array_push($stringArray, "File '" . getFileName($value->path) . "' successfully uploaded", "<br>");
		}
		else {
			array_push($stringArray, "File '" . getFileName($value->path) . "' NOT successfully uploaded, ", "Error: {$nextID['code']}", "<br>");
			array_push($failedFiles, $value->path);
		}

		if ($numLines > 100) {
			file_put_contents($logName, $stringArray, FILE_APPEND);
			$stringArray = array();
			$numLines = 0;
		}
	}

	array_push($stringArray, "<br>", "Successfully uploaded {$numFolders} folders and {$numFiles} files", "<br>");
	array_push($stringArray, "Your AFS directory contains {$totalFolders} folders and {$totalFiles} files", "<br>");

	file_put_contents($logName, $stringArray, FILE_APPEND);

	// If the transfer completed successfully, delete the log file
	if ($numFiles == $totalFiles and $numFolders == $totalFolders) {
	//	unlink($logName);
	//	unset($failedFiles);
	}

	// Send the notification email to the user
	include '../notification_email.php';

	exit();



	/* Given a file path, returns the path of the parent folder
	   E.g. '/afs/umich.edu/u/n/uniqname/folder/file' would return
	   		'/afs/umich.edu/u/n/uniqname/folder' */
	function getParentFolder($path) {
		return substr($path, 0, strrpos($path, "/"));
	}

	/* Given a file path, returns the file or end folder's name
	   E.g. '/afs/umich.edu/u/n/uniqname/folder/file' would return
	   		'file' */
	function getFileName($path) {
		return substr($path, strrpos($path, "/") + 1);
	}

	// Refreshes the access token
	function generateToken(&$accessToken, &$refreshToken, &$expireTime, $clientID, $clientSecret, &$stringArray) {
		array_push($stringArray, "Refreshing the token...<br>", "The old access token is: " . $accessToken, "<br>");
		array_push($stringArray, "The old refresh token is: " . $refreshToken, "<br>");
		array_push($stringArray, "The time left is " . ($expireTime - time()) . " seconds", "<br><br>");

		$tokenRequest = curl_init();
		curl_setopt_array($tokenRequest, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'https://app.box.com/api/oauth2/token',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken,
				'client_id' => $clientID,
				'client_secret' => $clientSecret
			)
		));

		if (! $tokenResponse = curl_exec($tokenRequest)) {
			return false;
		}
		curl_close($tokenRequest);

		$jsonResponse = json_decode($tokenResponse, true);
		if (!empty($jsonResponse['error'])) {
			return false;
		}

		$expireTime = time() + $jsonResponse['expires_in'];
		$accessToken = $jsonResponse['access_token'];
		$refreshToken = $jsonResponse['refresh_token'];

		array_push($stringArray, "The new access token is: " . $accessToken, "<br>");
		array_push($stringArray, "The new refresh token is: " . $refreshToken, "<br>");
		array_push($stringArray, "The time left is " . ($expireTime - time()) . " seconds", "<br><br>");

		return true;
	}

	/* Creates a CURL request to create a folder or upload a file with the given info
	   Returns the folder ID on success, an array containing error codes on failure */
	function createfile($fileName, $parentID, $path, $isFile, $accessToken, &$stringArray) {
		$url;
		$fields;
		
		if ($isFile) {
			// If we are uploading a file
			$url = 'https://upload.box.com/api/2.0/files/content';
			$fields = array(
				'attributes' => json_encode(array(
					"name" => $fileName,
					"parent" => array("id" => $parentID),
			                "content_created_at" => date(DATE_RFC3339, filectime($path)),
                                        "content_modified_at" => date(DATE_RFC3339, filemtime($path))
                                )),
				'file' => '@' . $path
			);
		}
		else {
			// If we are creating a folder
			$url = 'https://api.box.com/2.0/folders';
			$fields = json_encode(array(
				"name" => $fileName,
				"parent" => array("id" => $parentID)
			));
		}

		// Create a curl request with the info
		$fileRequest = curl_init();
		curl_setopt_array($fileRequest, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url,
			CURLOPT_TIMEOUT => 1800,
			CURLOPT_HTTPHEADER => array("Authorization: Bearer {$accessToken}"),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $fields
		));

		// Send the request and check for errors in the response
		if (! $fileResponse = curl_exec($fileRequest)) {
			$errorArray = array();
			$errorArray['code'] = "retry_this";
			return $errorArray;
		}
		curl_close($fileRequest);

		array_push($stringArray, "<pre>", print_r($fileResponse, true), "</pre>");

		$fileResponseArray = json_decode($fileResponse, true);
		if (isset($fileResponseArray['type'])) {
			if (strcmp($fileResponseArray['type'], "error") == 0) {
				return $fileResponseArray;
			}
		}

		if ($isFile) {
			return 1;
		}
		else {
			return $fileResponseArray['id'];
		}
	}

	// Reties an API call if there was an error thrown
	function retryCall(&$boxError, $fileName, $parentID, $path, $isFile, $accessToken, &$stringArray) {
		// Check that there is a valid error code
		if (empty($boxError['code'])) {
			return;
		}

		if (strcmp($boxError['code'], "item_name_in_use") == 0) {
			// If there is already a file/folder with this name, change the filename
			$newName = "_" . $fileName;
			$boxError = createfile($newName, $parentID, $path, $isFile, $accessToken, $stringArray);
		}
		else if (strcmp($boxError['code'], "item_name_invalid") == 0) {
			// If the name is invalid, remove any invalid characters

			// Replaces file names that are '..' or '.'
			if (strcmp($fileName, "..") == 0) {
				$fileName = "dotdot";
			}
			else if (strcmp($fileName, ".") == 0) {
				$fileName = "dot";
			}

			// Removes non-printable ascii characters
			$fileName = preg_replace('/[^[:print:]]/', '', $fileName);

			// Removes forward and backslashes
			$fileName = str_replace('/', '', $fileName);
			$fileName = stripslashes($fileName);

			// Removes leading and trailing whitespace
			$fileName = trim($fileName);

			// If we are left with an empty string, create a randomized file name
			if (empty($fileName)) {
				if ($isFile) {
					$fileName = "File " . rand();
				}
				else {
					$fileName = "Folder " . rand();
				}
			}

			$boxError = createfile($fileName, $parentID, $path, $isFile, $accessToken, $stringArray);

		}
		else if (strcmp($boxError['code'], "item_name_too_long") == 0) {
			// If the file/folder name is too long, chop off the first 255 chars
			$fileName = substr($fileName, 0, 254);
			$boxError = createfile($fileName, $parentID, $path, $isFile, $accessToken, $stringArray);
		}
		else if ((strcmp($boxError['code'], "file_size_limit_exceeded") == 0)) {
			// If the file is too big, skip it
			return;
		}
		else if (strcmp($boxError['code'], "rate_limit_exceeded") == 0) {
			// If the rate limit was exceeded, wait 10 seconds
			sleep(10);
			$boxError = createfile($fileName, $parentID, $path, $isFile, $accessToken, $stringArray);
		}
		else if (strcmp($boxError['code'], "unavailable") == 0) {
			// If the service is unavailable, wait 5 seconds
			sleep(5);
			$boxError = createfile($fileName, $parentID, $path, $isFile, $accessToken, $stringArray);
		}
		else if (strcmp($boxError['code'], "retry_this") == 0) {
			// If the curl request returned an error, retry
			sleep(5);
			$boxError = createfile($fileName, $parentID, $path, $isFile, $accessToken, $stringArray);
		}
		else {
			// If it was any other error, we can't handle it
			return;
		}

		if (!is_array($boxError)) {
			array_push($stringArray, "Retry successful!");
		}
		else {
			array_push($stringArray, "Retry failed");
		}
		array_push($stringArray, "<br><br>");
	}

?>
