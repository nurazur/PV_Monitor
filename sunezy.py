#!/usr/bin/env python

#Version 05.01.2016 : dateiname wird nach ISO Wochennummer berechnet (bisher python style+ ww01 ist ab dem 1. Montag im Jahr)
#Version 27.06.2016 : move lasy_log to a tmpfs, reduces amount of writes to SD card
#Version  5. 1.2017 : use absolute paths for files
#Version  6. 1.2017 : use iso calendar year

import pv
import serial
import time, datetime
import sys
import os
import subprocess

#pv.debug()

# get local time
os.environ['TZ'] = 'Europe/Paris'
time.tzset()
loctime = time.localtime(time.time())

#default filenames
verzeichnis = '/var/www/logfiles/'
filename_last_log = '/var/tmp/last_log.txt'

# alter standard Dateiname
filename = '/var/www/logfiles/sunezy_log.csv'

#neuer dateiname <Woche>_<Jahr>_<Geraet>_log.csv
date_now = datetime.date.fromtimestamp(time.time())
iso_week = date_now.isocalendar()[1]
iso_year = date_now.isocalendar()[0]
filename = '%s%02i_%i_log.csv' % ( verzeichnis, iso_week, iso_year)

#if th_week > 9:
#    filename = '%s%i_%s' % ( verzeichnis, th_week, time.strftime('%Y_log.csv', loctime) )
#else:
#    filename = '%s0%i_%s' % ( verzeichnis, th_week, time.strftime('%Y_log.csv', loctime) )

#Lese Kommandozeilenparameter
argc = len(sys.argv)
for i in range (argc):
    if sys.argv[i] == '-f' and (i+1) < argc: 
        filename = sys.argv[i+1]

if pv._DEBUG:
    print filename

#Lese Raspberry Pi Temperatur
raspi_temp = os.popen('vcgencmd measure_temp').readline() #temp=49.2'C
if pv._DEBUG:
	print "Raspberry CPU Temperatur: " + raspi_temp[5:]


#Oeffne Seriellen Port
port = serial.Serial('/dev/ttyAMA0',9600, timeout=0.2)

# timeout?
if not port.isOpen():
	if pv._DEBUG:
		print "cannot connect to Sunezy."
	
	sys.exit(1)

#Create Inverter object
from pv import cms
ezy= cms.Inverter(port)

#Reset Inverter
ezy.reset()

#Frage Seriennummer
sn = ezy.discover()
if sn is None:
    print "Sunezy not connected"
    sys.exit(1)
if pv._DEBUG:
    print "Seriennummer: ", sn

#Inverter registrieren
ok = ezy.register(sn)
if not ok:
    print "Sunezy registration failed"
    sys.exit(1)

#Versionsabfrage
version = ezy.version()
print "Sunezy Version: ",version

param_layout=ezy.param_layout()
if pv._DEBUG:
    print "Parameter Layout: ", param_layout

parameters = ezy.parameters(param_layout)
if not parameters:
    print "Kann Parameter nicht interpretieren"
else:
    for field in parameters:
        print "%-10s: %s" % field

status_layout = ezy.status_layout()
if pv._DEBUG:
    print "Status Layout: ", status_layout

## 10 measurements every 30s, so total run time should be approx. 4:30 min. 
Pac=0
Pac_max=0
Pac_min=4000

num_iterations=0
for i in range (10):
	if not port.isOpen():
		break
	num_iterations += 1
	status = ezy.status(status_layout)

	#for field in status:
		#print "%-10s: %s" % field

	dstat = dict (status)

	Pac_now = dstat['Pac']
	Pac += Pac_now
	if Pac_now < Pac_min:
		Pac_min= Pac_now
		
	if Pac_now > Pac_max:
		Pac_max = Pac_now
		
	t = time.strftime('%H:%M:%S', time.localtime(time.time()))	
	if pv._DEBUG:
		print "Power to Grid current: %s W, max: %s, min: %s, time: %s" % (Pac_now, Pac_max, Pac_min, t)
	if num_iterations < 10:
		time.sleep(29.8)

if i == 0:
	sys.exit(1)
	
Pac = str(Pac / (num_iterations))	
Vac = str(dstat['Vac'])
Fac = str(dstat['Fac'])
Vpv = str(dstat['Vpv'])
hTotal = str(dstat['h-Total'])
ETotal = str(dstat['E-Total'])
Mode = str(dstat['Mode'])
Err = str(dstat['Error'])
TempInv = str(dstat['Temp-inv'])


######################################
##  Luefter Steuerung                   ##
######################################

last_fan_state = -1 # setze unbekannten Luefter Status

#read status of fan from last_log file:
try:
    f = open(filename_last_log, 'r')
    thefile_last_log = f.readlines()
    f.close()
    if len(thefile_last_log) > 1:
        last_log = dict( zip( thefile_last_log[0].split(),thefile_last_log[1].split()))# hopefully
        if 'Fan_stat' in last_log:
            last_fan_state = int(last_log['Fan_stat'])
except IOError:
    pass

hysterese = 1.0
t1 = 44
max_fan_state = 8  # number of steps.since we do a 5 min update, steps should be approx 10%
fan_step = 9       #steps in %
fan_threshold = 28 # min % at which the fan can operate

t = float(TempInv)

fan_state = last_fan_state
# find new state depending on temperature
if t < t1-hysterese:
    fan_state = fan_state - 1
if t > t1+hysterese:
    fan_state = fan_state + 1
# clip to a number between 0 and 8
if fan_state < 0:
    fan_state = 0
if fan_state > max_fan_state:
    fan_state = max_fan_state
    
if last_fan_state != fan_state:
    if fan_state < 1:
        subprocess.Popen(['sudo', './luefter.py']) # turn off for fan_state 0; turn on at fan_threshold % at fan_state 1
    else:
        v = fan_state * fan_step + fan_threshold    
        subprocess.Popen(['sudo', './luefter.py', str(v)])

##################################
##    ende luftersteuerung
##################################



# compose time string for log file
t = time.strftime('%W %a %d.%m.%Y %H:%M:%S', loctime)

#Statuszeile komponieren
hdr = "%2s %3s %-10s %-8s %4s %6s %6s %6s %7s %7s %4s %4s %8s %8s %7s %7s %8s" % ("WW","Tag","Datum","Zeit","Pac","Vac","Fac","Vpv","h-Total","E-Total","Mode","Err","Temp-Inv","Temp-RPi","Pac_max","Pac_min","Fan_stat")
print hdr
sts = "%s %4s %6s %6s %6s %7s %7s %4s %4s %8s %8s %7s %7s %8s\n" % (t,Pac,Vac,Fac,Vpv,hTotal,ETotal,Mode,Err,TempInv,raspi_temp[5:-3],Pac_max,Pac_min,fan_state)
print sts

#Gelesene Daten in Datei eintragen 
f=open(filename, 'a')
f.write (sts)
f.close()

#write last entry into a separate log file so we have the latest status always available. 
#we write it with a header so we can change the format whenever we want 
f=open(filename_last_log,'w')
f.write(hdr + '\n')
f.write(sts)
f.close()

