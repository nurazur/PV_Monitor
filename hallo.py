#!/usr/bin/env python

import pv
import serial
import time
import sys
import os

pv.debug()

#default filename
filename = '../../var/www/sunezy_log.csv'
filename2 = '../../var/www/logfiles/sunezy_log.csv'
#Lese Kommandozeilenparameter
argc = len(sys.argv)
for i in range (argc):
    if sys.argv[i] == '-f' and (i+1) < argc: 
        filename = sys.argv[i+1]

#if pv._DEBUG:
print filename

#Lese Raspberry Pi Temperatur
raspi_temp = os.popen('vcgencmd measure_temp').readline()
print "Raspberry CPU Temperatur: " + raspi_temp[5:]


#Oeffne Seriellen Port
port = serial.Serial('/dev/ttyAMA0',9600, timeout=0.2)

# timeout?
if not port.isOpen():
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

status = ezy.status(status_layout)

for field in status:
    print "%-10s: %s" % field

dstat = dict (status)
print "Power to Grid: %s W" %dstat['Pac']

os.environ['TZ'] = 'Europe/Paris'
time.tzset()

t = time.strftime('%W %a %d.%m.%Y %H:%M:%S', time.localtime(time.time()))
Pac = str(dstat['Pac'])
Vac = str(dstat['Vac'])
Fac = str(dstat['Fac'])
Vpv = str(dstat['Vpv'])
hTotal = str(dstat['h-Total'])
ETotal = str(dstat['E-Total'])
Mode = str(dstat['Mode'])
Err = str(dstat['Error'])
TempInv = str(dstat['Temp-inv'])

#Statuszeile komponieren
hdr = "%2s %3s %-10s %-8s %4s %6s %6s %6s %7s %7s %4s %4s %8s %8s" % ("WW","Tag","Datum","Zeit","Pac","Vac","Fac","Vpv","h-Total","E-Total","Mode","Err","Temp-Inv","Temp-RPi")
print hdr
sts = "%s %4s %6s %6s %6s %7s %7s %4s %4s %8s %8s\n" % (t,Pac,Vac,Fac,Vpv,hTotal,ETotal,Mode,Err,TempInv,raspi_temp[5:-3])
print sts

#Gelesene Daten in Datei eintragen
#f=open(filename, 'a')
#f.write (sts)
#f.close()

#Temporaer: trage Kopie unter ./logfiles ein
#os.popen('cp '.filename.' '.filename2)

#write last entry into a separate log file so we have the latest status always available. 
#we write it with a header so we can change the format whenever we want 
#f=open("last_log.txt",'w')
#f.write(hdr + '\n')
#f.write(sts)
#f.close()

#copy to www directory
#os.popen('cp last_log.txt ../../var/www')
#os.popen('cp last_log.txt ../../var/www/logfiles')
