#!/usr/bin/env python

#Um 1:00 nachts, wenn es garantiert dunkel ist, trage den letzten Eintrag in die daily / weekly Dateien ein


import time
import sys
import fnmatch
import os

default_log_dir  = "../../var/www/logfiles"
last_log_filename =  "../../var/tmp/last_log.txt"
weekly_log_filename = "%s/sunezy_weekly_log.txt" % default_log_dir
#sunezy_log  = "%s/sunezy_log.csv" % default_log_dir
#temperatur_log = "%s/temperatures_log.csv" % default_log_dir
#luftdruck_log  = "%s/luftdruck_log.csv" % default_log_dir

#Lese Kommandozeilenparameter
argc = len(sys.argv)
for i in range (argc):
    if i > 0: 
        weekly_log_filename = sys.argv[i]


f=open(last_log_filename,'r')
hdr_last =  f.readline()
data_last = f.readline()
f.close()

#extract data from last entry in last_log.txt
new_dict = dict( zip(hdr_last.split(), data_last.split()) )
new_date = new_dict['Datum']
new_time = new_dict['Zeit']
new_Etotal = new_dict['E-Total']
new_hTotal = new_dict['h-Total']

#check if destination file exists
is_new_file = True
if os.path.exists(weekly_log_filename):
    is_new_file = False


f= open(weekly_log_filename,"a")
if is_new_file:
	newhdr = "%-10s %-8s %7s %7s %7s %7s\n" % ("Datum","Zeit","h-Total","E-Total","h-Delta","E-Delta")
	f.write(newhdr)
	data = "%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal, '0', '0')

else: # get last line to calculate E-delta
	f.close()
	f = open(weekly_log_filename,'r')
	hdr = f.readline()
	for lines in f:
		ln = lines
	f.close()
    
	last_dict = dict(zip(hdr.split(),ln.split()))
	last_Etotal = last_dict['E-Total']
	last_hTotal = last_dict['h-Total']
	hTotal_Delta = float(new_hTotal) - float(last_hTotal)
	Etotal_Delta = float(new_Etotal) - float(last_Etotal)
	f= open(weekly_log_filename,"a")
	data = "%-10s %-8s %7s %7s %7s %7s\n" % (new_date, new_time, new_hTotal, new_Etotal, str(hTotal_Delta), str(Etotal_Delta))
	
f.write(data)
f.close()
print"line added to %s" % weekly_log_filename

'''
#archive log of this week, name is WW_Year.csv
f = open(sunezy_log, 'r')
# read first line and extract ww and year
dta = f.readline().split()
f.close()
#print dta

log_week = int(dta[0]) + 1
#print log_week

log_date = dta[2].split('.')
log_year = log_date[2]

#copy sunezy_log to new archive file
if log_week < 10:	
    archive_filename = "%s/0%s_%s_log.csv" % (default_log_dir, log_week, log_year)
else:
    archive_filename = "%s/%s_%s_log.csv" % (default_log_dir, log_week, log_year) 
print archive_filename
cmd = "mv %s %s" % (sunezy_log, archive_filename)
print cmd
os.popen(cmd)

#copy temperatur_log to new archive file
if log_week < 10:	
    archive_filename = "%s/0%s_%s_ds18b20_log.csv" % (default_log_dir, log_week, log_year)
else:
    archive_filename = "%s/%s_%s_ds18b20_log.csv" % (default_log_dir, log_week, log_year) 
print archive_filename
cmd = "mv %s %s" % (temperatur_log, archive_filename)
print cmd
os.popen(cmd)

if log_week < 10:	
    archive_filename = "%s/0%s_%s_bmp180_log.csv" % (default_log_dir, log_week, log_year)
else:
    archive_filename = "%s/%s_%s_bmp180_log.csv" % (default_log_dir, log_week, log_year) 
print archive_filename
#copy luftdruck_log to new archive file
cmd = "mv %s %s" % (luftdruck_log, archive_filename)
print cmd
os.popen(cmd)
'''

