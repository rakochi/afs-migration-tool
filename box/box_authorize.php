<?php if (!isset($_SESSION)) { session_start(); } 

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

?>
<html>
 <head>
  <title>Log in to Box</title>
 </head>
 <body>
	<?php
	
	// Get the client ID and redirect uri
	$credentials = file_get_contents(__DIR__ . "/../../Private/box_credentials.json");
	if (!$credentials) {
		$_SESSION['error'] = "[1] Could not load login screen. Please <a href='index.html'>try again</a>";
		include __DIR__ . '/../error_page.php';
		exit();
	}

	$credentialsDecoded = json_decode($credentials, true);
	if ($credentialsDecoded == null) {
		$_SESSION['error'] = "[2] Could not load login screen. Please <a href='index.html'>try again</a>";
		include __DIR__ . '/../error_page.php';
		exit();
	}

	$clientID = $credentialsDecoded['clientID'];
	$redirectUri = $credentialsDecoded['redirectUri'];
	$user_email = $_SERVER["REMOTE_USER"] . "@umich.edu";

	// Verify that the credentials were read correctly
	if (empty($clientID) or empty($redirectUri)) {
		$_SESSION['error'] = "[3] Could not load login screen. Please <a href='index.html'>try again</a>";
		include __DIR__ . '/../error_page.php';
		exit();
	}

	// Generate a secure CSRF token and save it in a session
	$token = rand();
	$_SESSION['CSRF_token'] = $token;

	// Create a URL containing the above information
	$httpString = "https://app.box.com/api/oauth2/authorize" . 
		"?response_type=code&client_id={$clientID}&redirect_uri={$redirectUri}" . 
		"&state={$token}&box_login={$user_email}";

	// Generate a GET request to authorize the user
	$curlRequest = curl_init();
	curl_setopt($curlRequest, CURLOPT_URL, $httpString);

	// Send the request and check for a proper response
	if (! $response = curl_exec($curlRequest)) {
		$_SESSION['error'] = "[4] Could not load login screen. Please <a href='index.html'>try again</a>";
		include __DIR__ . '/../error_page.php';
		exit();
	}
	curl_close($curlRequest);

	?>
 </body>
</html>
