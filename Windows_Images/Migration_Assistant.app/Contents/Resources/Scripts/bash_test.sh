#!/bin/bash

#Bash script written by Michael McGookey (mikemcgo)
#6/10/15
#Gets users uniqname to parse out path to directory and to log in to
#the AFS system to run the scp (secure copy) command
#This file is for use on OS X

#Get uniqname
echo Enter uniqname
read NAME

#Get current location
LOCATION=$PWD
echo Files will appear here

#Set location to the users Downloads folder
LOCATION=${LOCATION/Migration_Assistant.app/Contents/Resources/Scripts}
LOCATION=$LOCATION/Downloads

#Print the path to the downloads folder
echo $LOCATION

#Run the scp command
#User will have to acknowledge they are accessing a remote server
rsync -r -p --exclude=".*/" --exclude=".*" $NAME@sftp.itd.umich.edu:/afs/umich.edu/user/${NAME:0:1}/${NAME:1:1}/$NAME/ $LOCATION/$NAME
