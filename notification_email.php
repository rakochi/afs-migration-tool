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

	// Check which cloud service they uploaded to
	if ($choice == 'drive') {
		$cloudProvider = "Google Drive";
		$cloudURL = "https://drive.google.com/a/umich.edu/";
	}
	else if ($choice == 'box') {
		$cloudProvider = "Box";
		$cloudURL = "https://umich.box.com";
	}
	else {
		$cloudProvider = "";
		$cloudURL = "";
	}

	// Send a different message depending on whether the transfer succeeded or not
	if ($numFiles == $totalFiles and $numFolders == $totalFolders) {
		$message = "Your files have all been uploaded to {$cloudProvider} successfully!\r\n" .
			"We uploaded {$numFolders} folders and {$numFiles} files.\r\n" .
			"You can access your files by logging in to the {$cloudProvider} website at {$cloudURL}.\r\n" .
			"Please do not reply to this email.\r\n";
	}
	else {
		$message = "Unfortunately, we were unable to upload all of your files to {$cloudProvider}.\r\n" .
			"We successfully uploaded {$numFolders} out of {$totalFolders} folders and {$numFiles} out of {$totalFiles} files.\r\n" .
			"You can still access all of the original files through mFile at mfile.umich.edu\r\n" .
			"You can access the uploaded files by logging in to the {$cloudProvider} website at {$cloudURL}.\r\n" .
			"Please do not reply to this email.\r\n";
	}

	if ($numFiles == 0 and $numFolders == 0) {
		$message = "Unfortunately, we were unable to upload any of your files to {$cloudProvider}.\r\n" .
			"You can still access all of your files through mFile at mfile.umich.edu\r\n" .
			"Please do not reply to this email.\r\n";
	}

	// Get the user's email address
	$email = $_SERVER["REMOTE_USER"] . "@umich.edu";

	// Set the sent from field in the message
	$headers = 'From: AFS Transfer Tool <miafstool-noreply@umich.edu>' . "\r\n";

	// Send the email
	mail($email, 'Your AFS Migration Request', $message, $headers);

?>