#!/bin/bash

#Bash script written by Michael McGookey (mikemcgo)
#6/25/15
#Gets users uniqname to parse path to directory and to log in to
#the AFS system to run the rsync utility
#This file is for use on Linux, or OS X

#Get uniqname
echo Enter uniqname
read NAME

#Get current location
LOCATION=$PWD
echo Files will appear here

#Print the path to the downloads folder
echo $LOCATION

#Run the rsync command
#User will have to acknowledge they are accessing a remote server
rsync -r -p --exclude=".*/" --exclude=".*" $NAME@sftp.itd.umich.edu:/afs/umich.edu/user/${NAME:0:1}/${NAME:1:1}/$NAME/ $LOCATION/$NAME
