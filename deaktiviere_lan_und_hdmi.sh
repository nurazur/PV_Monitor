#!/bin/bash

sudo /opt/vc/bin/tvservice -o
sudo su
echo 1-1.1:1.0 > /sys/bus/usb/drivers/smsc95xx/unbind
su pi
