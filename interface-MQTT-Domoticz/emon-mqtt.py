#!/usr/bin/python

"""
        Electricity Monitor to MQTT interface for Domoticz
        Reads Ampere meters from SQLite database
        and sends them to Domoticz over MQTT.

        Handling three phase Ampere meters

"""

import sqlite3 as sqlite, time, os
import paho.mqtt.client as mqtt

# Debug flag for printing, 0: false, 1: true
Debug = 0

# Configuration for MQTT broker and Domoticz
broker_address="192.168.1.75"
# The device indexes are assigned by Domoticz and must be changed here
idx = 143

# The Ampere meters
value1 = 0
value2 = 0
value3 = 0

def GetCurrValues(db_file):
    global value1
    global value2
    global value3
    try:
        dbconn=sqlite.connect(db_file)
        dbdata=dbconn.cursor()
        dbdata.execute("SELECT * FROM Eregisters WHERE timestamp = (SELECT MAX(timestamp) FROM Eregisters)")
    except sqlite.Error,e:
        print e
    row = dbdata.fetchone()
    value1 = row[7]
    value2 = row[8]
    value3 = row[9]
    if dbconn:
        dbconn.close()

def main():
    database = "/var/db/emon.db"
    GetCurrValues(database)
    if Debug == 1:
        print("Value 1: {0}/100 A, Value 2: {1}/100 A, Value 3: {2}/100 A".format(value1, value2, value3))

    client = mqtt.Client("Evalue1") #create new instance
    client.connect(broker_address) #connect to broker

    # Format message with values and publish
    fvalue1 = float (value1) / 100
    fvalue2 = float (value2) / 100
    fvalue3 = float (value3) / 100
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%06.2f;%06.2f;%06.2f\" }" % (idx, fvalue1, fvalue2, fvalue3)
    if Debug == 1:
        print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)

if __name__ == '__main__':
    main()
