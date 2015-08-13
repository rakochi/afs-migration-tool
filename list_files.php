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

	session_start();

	set_time_limit(0);

	// Get current user's uniqname
	$uniqname = $_SERVER["REMOTE_USER"];

	// Make sure the session error variable is empty
	if (isset($_SESSION['error'])) {
		unset($_SESSION['error']);
	}

	// Check if uniqname is valid
	if (empty($uniqname) or $uniqname == "") {
		$_SESSION['error'] = "Could not determine uniqname";
	}

	// Change directory to user's AFS home space
	$afsPath = "/afs/umich.edu/user/{$uniqname[0]}/{$uniqname[1]}/{$uniqname}";
	if (!chdir($afsPath)) {
		if (empty($_SESSION['error'])) {
			$_SESSION['error'] = "Could not access user's home directory";
		}
	}

	// Checks which cloud service they selected
	$choice = $_POST['cloud_choice'];

	// Data structure to hold file information
	class AFSFile {
		public $path; // Path to the file
	    public $type; // Mimetype of the file, null for folders
	    public $size; // File size in bytes
	}

	// Create a list of all the files in the user's AFS space
	$numberOfFiles = 0;
	$folderList = array();
	$fileList = array_filter(scanAFSDirectory($afsPath, true, $folderList, $numberOfFiles), "filterEmptyObjects");

	if (count($fileList) == 0 and count($folderList) == 0) {
		if (empty($_SESSION['error'])) {
			$_SESSION['error'] = "Could not detect any files in your AFS directory";
		}
	}

	if ($numberOfFiles > 10000) {
		$_SESSION['error'] = "You have too many files in your AFS directory. " . 
			"This tool can upload a maximum of 10,000 files." . "<br>" .
			"				Consider using our <a href='index.html'>AFS-to-local computer</a> tool instead.";
	}

	// If an error was detected, go to the error page
	if (!empty($_SESSION['error'])) {
		include 'error_page.php';
		exit();
	}

	// Save the arrays to session variables
	$_SESSION['folderList'] = $folderList;
	$_SESSION['fileList'] = $fileList;
	$_SESSION['afsPath'] = $afsPath;

	// Call the proper API script
	if (strcmp($choice, "drive") == 0) {
	        $drive_url = "https://" . $_SERVER['HTTP_HOST'] . "/afsmigrator/drive/upload_to_drive.php";
                header("Location: " . $drive_url);
        }
	else if (strcmp($choice, "box") == 0) {
		include 'box/box_authorize.php';
	}
	else {
		$_SESSION['error'] = "Could not detect user's Cloud service choice." 
			. " Please restart your web browser and try again.";
		include 'error_page.php';
		exit();
	}


	// Function that filters out empty AFSFile objects
	function filterEmptyObjects($inputFile) {
		return !($inputFile->path == null);
	}

	// Function that recursively searches a directory
	function scanAFSDirectory($inputDir, $isRoot, &$folderList, &$numberOfFiles) {
		$outputArray[] = new AFSFile();
		$currentDirList = array_diff(scandir($inputDir), array('..','.'));

		if ($isRoot) {
			// Filters out dotfiles from the root directory
			$currentDirList = preg_grep("/^[^.].*$/", $currentDirList);
		}

		foreach ($currentDirList as $key => $value) {

			// Filters out symbolic links to avoid redirect loops
			if (is_link($inputDir . DIRECTORY_SEPARATOR . $value)) {
				continue;
			}

			// Add this file's information to the array
			$currentFile = new AFSFile();
			$currentFile->path = $inputDir . DIRECTORY_SEPARATOR . $value;
			$currentFile->size = filesize($inputDir . DIRECTORY_SEPARATOR . $value);

			if (!is_dir($inputDir . DIRECTORY_SEPARATOR . $value)) {

				// Make sure they don't have too many files
				$numberOfFiles++;
				if ($numberOfFiles > 10000) {
					return array();
				}

				// Get the mimetype of the file
				$fileType = finfo_open(FILEINFO_MIME_TYPE);
				$currentFile->type = finfo_file($fileType, $inputDir . DIRECTORY_SEPARATOR . $value);
				finfo_close($fileType);

				array_push($outputArray, $currentFile);
			}
			else {
				$folderList[$inputDir . DIRECTORY_SEPARATOR . $value] = null;

				// If it is a directory, call the function recursively
				$outputArray = array_merge($outputArray, 
					scanAFSDirectory($inputDir . DIRECTORY_SEPARATOR . $value, false, $folderList, $numberOfFiles));
			}
		}
			
		return $outputArray;
	}

?>
