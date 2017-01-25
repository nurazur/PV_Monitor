#! /usr/bin/python

#Um 1:00 nachts, wenn es garantiert dunkel ist, trage den letzten Eintrag von gestern in die daily Datei ein

import datetime
import time
import sys
import fnmatch
import os
import subprocess

def get_log_filename_of_the_week(verzeichnis):
    th_week= datetime.date.today().isocalendar()[1]
    th_year= datetime.date.today().isocalendar()[0]
    filename = '%s%02i_%s_log.csv' % ( verzeichnis, th_week, th_year )
    return filename

def get_log_filename_of_last_week(verzeichnis):
    time_lastweek = time.time() - 7 * 24 * 3600
    dt = datetime.date.fromtimestamp(time_lastweek)
    week = dt.isocalendar()[1]
    year = dt.isocalendar()[0]
    filename = '%s%02i_%s_log.csv' % ( verzeichnis, week, year )
    return filename

def get_log_filename_of_yesterday(verzeichnis):
    time_yesterday = time.time() - 24 * 3600
    dt = datetime.date.fromtimestamp(time_yesterday)
    week = dt.isocalendar()[1]
    year = dt.isocalendar()[0]
    filename = '%s%02i_%s_log.csv' % ( verzeichnis, week, year )
    return filename

def get_header_and_last_line_of_file(filename):
    f = open(filename,'r')
    hdr = f.readline()
    for lines in f:
        ln = lines
    f.close()
    return hdr, ln

def get_header_and_last_line_of_file_as_dict(filename):
    f = open(filename,'r')
    hdr = f.readline()
    for lines in f:
        ln = lines
    f.close()
    return dict(zip(hdr.split(),ln.split()))
    
#default filenames
verzeichnis = '../../var/www/logfiles/'
nas_dir = '/var/nas/var/www/logfiles/'

daily_log_filename = "%ssunezy_daily_log.txt" % verzeichnis
weekly_log_filename = "%ssunezy_weekly_log.txt" % verzeichnis

#NAS file names
nas_daily_log_filename = "%ssunezy_daily_log.txt" % nas_dir
nas_weekly_log_filename = "%ssunezy_weekly_log.txt" % nas_dir

#Lese Kommandozeilenparameter
argc = len(sys.argv)
for i in range (argc):
    if i > 0: 
        daily_log_filename = sys.argv[i]


#instead opening the last_log file which is in a ram disk, fetch last entry made yesterday from SD card
fn = get_log_filename_of_yesterday(verzeichnis)
if os.path.exists(fn):
    f = open(fn,'r')
    for lines in f:
        data_last = lines
    f.close()
    hdr_last = "%2s %3s %-10s %-8s %4s %6s %6s %6s %7s %7s %4s %4s %8s %8s %7s %7s %8s" % ("WW","Tag","Datum","Zeit","Pac","Vac","Fac","Vpv","h-Total","E-Total","Mode","Err","Temp-Inv","Temp-RPi","Pac_max","Pac_min","Fan_stat")
else:
    print "%s does not exist" % fn
    #open the last_log file    
    try:
        f = open(last_log_filename, 'r')
        hdr_last =  f.readline()
        data_last = f.readline()
        f.close()
        print "from last_log file:"
        print hdr_last
        print data_last
    except IOError:
        hdr_last =''       
        data_last=''

        
#extract data from last entry 
new_dict = dict( zip(hdr_last.split(), data_last.split()) )
new_date = new_dict['Datum']
new_time = new_dict['Zeit']
new_Etotal = new_dict['E-Total']
new_hTotal = new_dict['h-Total']

#check if destination file exists
is_new_file = not os.path.exists(daily_log_filename)
if is_new_file:
    f= open(daily_log_filename,"a")
    f.write("%-10s %-8s %7s %7s %7s %7s\n" % ("Datum","Zeit","h-Total","E-Total","h-Delta","E-Delta"))
    f.write("%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal, '0', '0'))
    f.close()
else: # get last line to calculate E-delta
    last_dict = get_header_and_last_line_of_file_as_dict(daily_log_filename)
    last_Etotal = last_dict['E-Total']
    last_hTotal = last_dict['h-Total']
    hTotal_Delta = "%.1f" % (float(new_hTotal) - float(last_hTotal))
    Etotal_Delta = "%.1f" % (float(new_Etotal) - float(last_Etotal))
    f= open(daily_log_filename,"a")
    f.write("%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal, hTotal_Delta, Etotal_Delta))
    f.close()
print"line added to %s" % daily_log_filename

#
# copy daily_log_file to nas
# path is silly: /var/nas/var/www/logfiles
#
nas_exists    = os.path.exists(nas_dir)
if nas_exists:
    subprocess.call(["cp", "-p", daily_log_filename, nas_daily_log_filename])

# its a monday morning? then store weekly log!
day=datetime.date.today().isoweekday()
if day == 1:
    #check if destination file exists
    is_new_file = not os.path.exists(weekly_log_filename)
    if is_new_file:
        f = open(weekly_log_filename,"a")
        f.write("%-10s %-8s %7s %7s %7s %7s\n" % ("Datum", "Zeit",     "h-Total",  "E-Total",  "h-Delta","E-Delta"))
        f.write("%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal,       '0',       '0'))
        f.close()
    else: # get last line to calculate E-delta
        last_dict = get_header_and_last_line_of_file_as_dict(weekly_log_filename)
        last_Etotal = last_dict['E-Total']
        last_hTotal = last_dict['h-Total']
        hTotal_Delta = "%.1f" % (float(new_hTotal) - float(last_hTotal))
        Etotal_Delta = "%.1f" % (float(new_Etotal) - float(last_Etotal))
        f= open(weekly_log_filename,"a")
        f.write("%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal, hTotal_Delta, Etotal_Delta))
        f.close()
    print"line added to %s" % weekly_log_filename
    # copy weekly_log_file to nas
    if nas_exists:
        subprocess.call(["cp", "-p", weekly_log_filename, nas_weekly_log_filename])
