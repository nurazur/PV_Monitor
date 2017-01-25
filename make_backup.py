#!/usr/bin/env python

#store error log on nas
# crontab job every monday morning at 00:01

import time, datetime
import os
import subprocess
import sys

def get_log_filename_of_the_week(verzeichnis, device=""):
    th_week= datetime.date.today().isocalendar()[1]
    th_year= datetime.date.today().isocalendar()[0]
    filename = '%s%02i_%s%s_log.csv' % ( verzeichnis, th_week, th_year, device)
    return filename

def get_log_filename_of_last_week(verzeichnis, device=""):
    time_lastweek = time.time() - 7 * 24 * 3600
    dt = datetime.date.fromtimestamp(time_lastweek)
    week = dt.isocalendar()[1]
    year = dt.isocalendar()[0]
    filename = '%s%02i_%s%s_log.csv' % ( verzeichnis, week, year, device)
    return filename

def get_log_filename_of_yesterday(verzeichnis, device=""):
    time_yesterday = time.time() - 24 * 3600
    dt = datetime.date.fromtimestamp(time_yesterday)
    week = dt.isocalendar()[1]
    year = dt.isocalendar()[0]
    filename = '%s%02i_%s%s_log.csv' % ( verzeichnis, week, year, device )
    return filename

def do_backup(source_dir, dest_dir, device=""):
    source_filename =      get_log_filename_of_last_week(source_dir, device)
    destination_filename = get_log_filename_of_last_week(dest_dir, device)

    source_exists = os.path.exists(source_filename)
    nas_exists    = os.path.exists(dest_dir)    
    #Copy file to NAS
    if not nas_exists: #try to mount NAS
        print "try to mount NAS drive..."
        if not subprocess.call(["sudo", "mount", "-a"]):
            nas_exists    = os.path.exists(dest_dir)  
            if nas_exists:
                print "mount successful."
        else:
            sys.exit(1)
            
    if source_exists and nas_exists:
        if not subprocess.call(["cp", "-p", source_filename, destination_filename]):
            print "file %s copied to: %s" % (source_filename , destination_filename)
        else:
            print "some error occured..."
        
            
#########################
###    Directories    ###
#########################
nas_drive =         "/var/nas/var/www/logfiles/"
source_dir =        "/var/www/logfiles/"
devices = ["", "_ds18b20", "_bmp180"]    
    
#########################
### BACKUP SUNEZY LOG ###
#########################

do_backup(source_dir, nas_drive, devices[0])
do_backup(source_dir, nas_drive, devices[1])
do_backup(source_dir, nas_drive, devices[2])
       
