#!/usr/bin/env python

import serial
import time

#Oeffne Seriellen Port
port = serial.Serial('/dev/ttyAMA0',9600, timeout=0.2)

# timeout?
if not port.isOpen():
    print "cannot connect to Sunezy."
    sys.exit(1)

print "Port opened."

"""
THIS IS THE BEGINNERS WAY OF SENDING A STRING OF ASCII CODES TO THE DEVICE
AND RECEIVING THE SERIAL NUMBER AS A HUMAN READABLE STRING.
"""


#Reset
'''
<sync> <src> <dst> <cmd> <len> <payload> <checksum>
  2B    2B    2B    2B    1B     len B       2B
 aaaa  0100  0000  0004   00                 0159
'''
SYNC = chr(0xaa) + chr(0xaa)
SRC  = chr(0x01) + chr(0x00)
DST =  chr(0x00) + chr(0x0)
cmd =  chr(0x00) + chr(0x04)
leng = chr(0x00)
chksum = chr(0x01) + chr(0x59)

send = SYNC + SRC + DST + cmd + leng + chksum
print "Reset - % i Bytes written." % port.write(send)

time.sleep(0.2)
#Query serial number 
"""
<sync> <src> <dst> <cmd> <len> <payload> <checksum>
  2B    2B    2B    2B    1B     len B       2B
 aaaa  0100  0000  0000   00                0155
"""

cmd =  chr(0x00) + chr(0x00)
leng = chr(0x00)
chksum = chr(0x01) + chr(0x55)

send = SYNC + SRC + DST + cmd + leng + chksum
print "Query - %i Bytes written." % port.write(send)

time.sleep(.2)
raw_line =""
if port.inWaiting() > 0:
    raw_line = port.read(port.inWaiting())


if len(raw_line) > 10:
    sn = raw_line[9:-2]
    print "Serial Number: %s" % sn
else: 
    print len(raw_line)

    
    
'''
PYTHON PROVIDES SOME POWERFUL METHODS TO SIMPLIFY THE STRING OPERATIONS WITH ASCII CODES
'''

import struct

SYNC = 0xaaaa
SRC =  0x0100
DST =  0x0000

CMD_RST = 0x0004  #Reset
CMD_DSC = 0x0000  #Discover -  query serial number

data = struct.pack('!HHHHB', SYNC, SRC, DST, CMD_RST, 0x00)
checksum = struct.pack('!H', sum(map(ord, data))) # sum over the entire data string without the checksum itself
data = data + checksum

if port.write(data) is len(data):
    print"--> ", data.encode('hex_codec')

data = struct.pack('!HHHHB', SYNC, SRC, DST, CMD_DSC, 0x00)
checksum = struct.pack('!H', sum(map(ord, data))) 
data = data + checksum

if port.write(data) is len(data):
    print"--> ", data.encode('hex_codec')

time.sleep(.2)

in_data =""
if port.inWaiting() > 0:
    in_data = port.read(port.inWaiting())
    print "<-- ", in_data.encode('hex_codec')


if len(in_data) > 10:
    sn = in_data[9:-2]
    print "Serial Number: %s" % sn
else: 
    print "Bytes received: %i" % len(in_data)


port.close()
