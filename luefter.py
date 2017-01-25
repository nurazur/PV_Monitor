#!/usr/bin/env python
import sys
from time import sleep
import wiringpi2 as wpi

argc=len(sys.argv)
#print argc

#for i in range(argc):
 #   print sys.argv[i]



wpi.wiringPiSetupGpio()
wpi.pwmWrite(18,0)
wpi.pinMode(18,1)
wpi.pinMode(18,2)


wpi.pwmSetMode(0)
wpi.pwmSetClock(320)
range =1200
wpi.pwmSetRange(range)

dc = 0.0
if argc > 1:
    dc = float(sys.argv[1])

dc = int (dc * range / 100)

print dc

if dc > 0: 
    wpi.pwmWrite(18,range)
    sleep(2)
wpi.pwmWrite(18,dc)
