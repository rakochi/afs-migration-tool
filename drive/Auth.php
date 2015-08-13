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

function get_stored_refreshtoken($file, $uniqname) 
{

  $tokens_list = fopen($file, 'r');
  while (! feof($tokens_list)) 
  {
    //parse csv line into array   
    $line = fgetcsv($tokens_list, 500);
    if ($line[0] == $uniqname)
    {
      return $line[1];
    }
  }
  fclose($tokens_list);
}
?>
