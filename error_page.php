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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Request Not Submitted</title>
    <style>
        #itsBanner {
            position: absolute;
            top: 0px;
            left: 0px;
            padding: 20px;
            width: 100%;
            background: #00274c;
            white-space: nowrap;
        }
        
        html * {
            font-family: Arial !important;
        }

        h2 {
            position: relative;
            top: 75px;
        }
        
        #text {
            position: relative;
            top: 75px;
        }

        a:link{color:#0000FF}
        a:visited{color:#0000FF}

    </style>
</head>

<body>
    <div id="itsBanner">
        <a href="index.html">
            <img src="site_banner.png" alt="AFS Migration Tool"/>
        </a>
    </div>

    <h2>Unfortunately, there was a problem with your request</h2>

    <div id="text">
        <p>We were unable to process your request. There are various potential reasons for this. For example, our servers could be too busy, or it is possible that we could not access your AFS directory. Your AFS directory is accessible at <a target="_blank" href="http://mfile.umich.edu/">mFile</a>. If you would still like to use this tool, you can <a href="index.html">try again</a> later or you can contact the <a target="_blank" href="http://www.its.umich.edu/help/">Service Center</a> for help.</p>
    </div>
    <br><br><br><br>

    <?php
        if (isset($_SESSION['error'])) {
            echo "<pre><b>Error description:  " . $_SESSION['error'] . "</b></pre>";
        }
        session_destroy();
    ?>

</body>

</html>
