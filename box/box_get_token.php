<?php

	if (!isset($_SESSION)) {
		session_start();
	}

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

	set_time_limit(0);
	ignore_user_abort(true);

	// Data structure to hold file information
	class AFSFile {
 		public $path; // Path to the file
 		public $type; // Mimetype of the file, null for folders
 		public $size; // File size in bytes
 	}

	// Make sure the response didn't return an error
	if (isset($_GET['error'])) {
		$_SESSION['error'] = "You must grant access for this tool to work. " . 
			"Please <a href='index.html'>try again</a>.";
		header("Location: https://mfile-test.www.umich.edu/afsmigrator/error_page.php");
		exit();
	}

	// Read in results from authorization request
	$authCode = $_GET['code'];

	// Save session variables to local variables
	$folderList = $_SESSION['folderList'];
	$fileList = $_SESSION['fileList'];
 	$afsPath = $_SESSION['afsPath'];

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
	
	// End the session now that we no longer need it
	session_destroy();

	// Begin the file transfer
	include 'box_transfer_files.php';

	exit();

?>