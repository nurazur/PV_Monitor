#!/usr/bin/env python

#Version 05.01.2016: Dateinamen auf ISO Woche umgestellt.
#Version 06.01.2017: Dateinamen auf ISO Jahr umgestellt.

import sys
import os
import time
import datetime


def sensorname(str):
	if   str == '28-0000055bb7bd':
		retstr = 'Sensor 1'
	elif str == '28-0000055b6bc5':
		retstr = 'Sensor 2'
	elif str == '28-0000055c0dbb':
		retstr = 'Sensor 3'
	elif str == '28-0000055b876b':
		retstr = 'Sensor 4'
	elif str == '28-0000055c0fc3':
		retstr = 'Sensor 5'
	elif str == '28-0000055bb20f':
		retstr = 'Sensor 6'
	elif str == '28-0000055c01bd':
		retstr = 'Sensor 7'
	elif str == '28-0000055b6fa5':
		retstr = 'Sensor 8'
	elif str == '28-0000055b3a4b':
		retstr = 'Sensor 9'
	elif str == '28-0000055bf3f6':
		retstr = 'Sensor 10'
        elif str == '28-00000543326e':
                retstr = '6er'
        elif str == '28-00000543189e':
                retstr = 'Sensor 11'
        elif str == '28-000005437180':
                retstr = 'Sensor 12'
        elif str == '28-000005433cbb':
                retstr = 'Sensor 13'
        elif str == '28-000005436d91':
                retstr = 'Aussen Nord'
	else:
		retstr = str
	return retstr

def errorlog(fn, str):
    debug_file = open(fn, 'a')
    print ('schreibe in Fehler log Datei : ' + fn)
    debug_file.write(str)
    debug_file.close()

# debug mode?
Debug = False

# get local time
os.environ['TZ'] = 'Europe/Paris'
time.tzset()
loctime = time.localtime(time.time())



standard_verzeichnis = '/var/www/logfiles/'
dbgmsg_file = './ds18b20_debug_messages.txt'

# alter, temporaerer Standard Dateiname:
filename ='%stemperatures_log.csv' %(standard_verzeichnis)


# herausfinden in welche Datei geschrieben wird (neuer Standard)
#neuer dateiname <Woche>_<Jahr>_<Geraet>_log.csv
date_now = datetime.date.fromtimestamp(time.time())
iso_week = date_now.isocalendar()[1]
iso_year = date_now.isocalendar()[0]
filename = '%s%02i_%i_ds18b20_log.csv' % ( standard_verzeichnis, iso_week, iso_year)


#Lese Kommandozeilenparameter
argc = len(sys.argv)
for i in range (argc):
    if sys.argv[i] == '-f' and (i+1) < argc: 
        filename = sys.argv[i+1]
    elif sys.argv[i] == '-d':
        Debug = True

#1-wire Geraeteliste 
#oeffnen, einlesen und schliessen
file = open('/sys/devices/w1_bus_master1/w1_master_slaves')
w1_slaves = file.readlines()
file.close()

'''
w1_slaves looks like this:
28-000005437180
28-00000543326e
28-00000543189e
28-000005436d91
'''



#oeffne log datei
if len(w1_slaves) > 0:
    logfile=open(filename,'a')
    # Zeitstring schreiben:
    t = time.strftime('\n%W,%a,%d.%m.%Y,%H:%M:%S', loctime)   
    logfile.write(t)
else:
    if Debug:
        errorlog(dbgmsg_file, t + ',keine Sensoren gefunden - beende Skript')
    sys.exit('keine Sensoren gefunden.')

if Debug:
    os.system("clear")
    print ('Datum-Uhrzeit          | degC  | Sensor ')
    print ('---------------------------------------------------')



for zeile in w1_slaves:
    w1_slave = zeile.split('\n')[0]
    for loop in range (10):   # maximum 10 trials!
        try:
            file = open('/sys/bus/w1/devices/' + str(w1_slave) + '/w1_slave', 'r')
            inhalt = file.read()
            # inhalt sieht etwa so aus:
            # 13 02 4b 46 7f ff 0d 10 e7 : crc=e7 YES
            # 13 02 4b 46 7f ff 0d 10 e7 t=33187
            file.close()
        except IOError as (errno, strerror):
            print "I/O error({0}): {1}".format(errno, strerror) 
            if Debug:
                errorlog(dbgmsg_file, "\nI/O error({0}): {1}".format(errno, strerror))
            continue
        except:
            print "Unerwarteter Fehler"
            if Debug:
                errorlog(dbgmsg_file, "\nUnerwarteter Fehler")
            continue
            
        dtempwert = -100000.0
        if "YES" in inhalt:
            tw_part1 = inhalt.split('\n')[1]
            tempwert = tw_part1.split(" ")[9]
            dtempwert= float(tempwert[2:])
            if Debug:
                if dtempwert == 85000:
                    errorlog(dbgmsg_file, t + ',' + sensorname(w1_slave) + ', Temperaturwert konnte nicht konvertiert werden. Tempwert = ' + tw_part1)
                elif dtempwert == -100000.0:
                    errorlog(dbgmsg_file, t + ',' + sensorname(w1_slave) + ', Temperaturwert konnte nicht ermittelt werden. Tempwert = ' + tw_part1)
            if dtempwert != 85000 and dtempwert != -100000:
                lg = ",%s,%4.1f" % (sensorname(w1_slave) , dtempwert/1000)
                logfile.write(lg)
                if Debug:
                    ausgabe = "%s um %s | %4.2f | %s (%i trials)" % (time.strftime("%d.%m.%Y"), time.strftime("%H:%M:%S"), dtempwert/1000, sensorname(w1_slave), loop+1)
                    print ausgabe
                break
        else:
            if Debug:
                errorlog(dbgmsg_file, t + ',' + sensorname(w1_slave) + ', CRC Fehler, ' + tw_part1)
#logfile.write("\n")
logfile.close()
	
