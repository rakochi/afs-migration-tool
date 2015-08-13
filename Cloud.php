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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>AFS to Cloud Tool</title>
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

        #submit-button {
            display:inline-block;
            margin:2em 0 0 0;
            padding:10px 2em 10px 2em;
            border:0;
            background:#00274C;
            color:#ffffff;
            -webkit-appearance:none;
            text-shadow: 0 1px rgba(0,0,0,0.1);
            -webkit-border-radius:4px;
            border-radius:4px;
            font-size:inherit;
            font-weight:bold;
            text-align:center;
            text-decoration:none;
            white-space:nowrap;
            -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.1);
            box-shadow: 0 1px 1px rgba(0,0,0,.1);
            cursor:default;
        }

        #submit-button:hover {
            -webkit-box-shadow: 0 1px 1px rgba(0,0,0,0.2);
            box-shadow: 0 1px 1px rgba(0,0,0,0.2);
            background:#567daa;
        }

        .cloud-selector input {
            margin:0;padding:0;
            -webkit-appearance:none;
               -moz-appearance:none;
                    appearance:none;
        }

        .cloud-selector-2 input {
            position:absolute;
            z-index:999;
        }

        .drive{background-image:url('DriveLogo.jpg');}
        .box{background-image:url('BoxLogo.png');}

        .cloud-selector-2 input:active +.cloud-provider, .cloud-selector input:active +.cloud-provider {opacity: 0.9;}
        .cloud-selector-2 input:checked +.cloud-provider, .cloud-selector input:checked +.cloud-provider {
            -webkit-filter: none;
               -moz-filter: none;
                    filter: none;
        }
        .cloud-provider {
            cursor:pointer;
            background-size:contain;
            background-repeat:no-repeat;
            display:inline-block;
            width:130px;height:130px;
            -webkit-transition: all 100ms ease-in;
               -moz-transition: all 100ms ease-in;
                    transition: all 100ms ease-in;
            -webkit-filter: brightness(1.8) grayscale(1) opacity(0.7);
               -moz-filter: brightness(1.8) grayscale(1) opacity(0.7);
                    filter: brightness(1.8) grayscale(1) opacity(0.7);
        }
        .cloud-provider:hover {
            -webkit-filter: brightness(1.2) grayscale(0.5) opacity(0.9);
               -moz-filter: brightness(1.2) grayscale(0.5) opacity(0.9);
                    filter: brightness(1.2) grayscale(0.5) opacity(0.9);
        }

        a:link{color:#0000FF}
        a:visited{color:#0000FF}
        a{color:#444;text-decoration:none;}
        p{margin-bottom:.3em;}
        * { font-family:monospace; }
        .cloud-selector-2 input{ margin: 5px 0 0 12px; }
        .cloud-selector-2 label{ margin-left: 7px; }
        span.cc{ color:#6d84b4 }
    </style>
</head>

<body>
    <div id="itsBanner">
        <a href="index.html">
            <img src="site_banner.png" alt="AFS Migration Tool"/>
        </a>
    </div>

    <h2>AFS to Cloud File Transfer Tool</h2>

    <div id="text">
        <p>This tool allows you to upload the files from your University AFS space to either <a target="_blank" href="https://drive.google.com/a/umich.edu/">Google Drive</a> or <a target="_blank" href="https://umich.box.com/">Box</a>. While your access to AFS ends shortly after you graduate, your University-affiliated Google and Box accounts will remain active indefinitely. Below, please select which of the two services you'd like to upload your files to and click submit.</p>

    <form action="list_files.php" method="post" onsubmit="return ray.ajax()">

        <div class="cloud-selector-2">
        <script type="text/javascript">
            var ray={
                ajax:function(st) {
                    this.show('load');
                },
                show:function(el) {
                    this.getID(el).style.display='';
                },
                getID:function(el) {
                    return document.getElementById(el);
                }
            }
        </script>

        <style type="text/css">
            #load {
                position:absolute;
                z-index:1;
                border:3px double #999;
                background:#f7f7f7;
                width:200px;
                height:50px;
                margin-top:-60px;
                margin-left:-60px;
                top:80%;
                left:51%;
                text-align:center;
                line-height:50px;
                font-family:"Trebuchet MS", verdana, arial,tahoma;
                font-size:12pt;
            }
        </style>

        <div id="load" style="display:none;">Loading...  Please wait</div>

        <p><br>
        <center>

            <input id="drive" type="radio" name="cloud_choice" value="drive" required/>
            <label class="cloud-provider drive" for="drive"></label>

            <input id="box" type="radio" name="cloud_choice" value="box" required/>
            <label class="cloud-provider box" for="box"></label>

            <br><br><br>
            <input type="submit" id="submit-button" value="Submit"/>
            
        </center>
        </p>

    </div>
    </form>
</body>

</html>
