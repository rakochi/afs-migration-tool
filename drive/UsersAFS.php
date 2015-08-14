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

include_once 'AFSFileClass.php';

class UsersAFS
{
 public  $fileList;  //Array of AFSFile objects created from user's AFS files
 public  $folderList;  //Array of user's folders from AFS
 public  $afsPath;  //Path to user's AFS files
 public  $numFoldersUploaded; //Numner of Folders Uploaded
 public  $numFilesUploaded; //Number of Files Uploaded
 public  $failedFiles; //Array of files that failed to upload
}

?>
