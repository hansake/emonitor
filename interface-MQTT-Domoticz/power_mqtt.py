#!/usr/bin/python

"""
        Power Monitor to MQTT interface for Domoticz
        Reads Wh counters from SQLite database
        and sends them to Domoticz over MQTT.

        Handling three Wh counters

"""

import sqlite3 as sqlite, time, os
import paho.mqtt.client as mqtt

# Debug flag for printing, 0: false, 1: true
Debug = 0

# Configuration for MQTT broker and Domoticz
broker_address="192.168.1.75"
# The device indexes are assigned by Domoticz and must be changed here
idx1 = 140
idx2 = 141
idx3 = 142

# The Wh pulse counters
pulsecounter1 = 0
pulsecounter2 = 0
pulsecounter3 = 0

def GetCurrCounters(db_file):
    global pulsecounter1
    global pulsecounter2
    global pulsecounter3
    try:
        dbconn=sqlite.connect(db_file)
        dbdata=dbconn.cursor()
        dbdata.execute("SELECT * FROM Lecounters")
    except sqlite.Error,e:
        print e
    row = dbdata.fetchone()
    pulsecounter1 = row[2]
    pulsecounter2 = row[3]
    pulsecounter3 = row[4]
    if dbconn:
        dbconn.close()

def main():
    database = "/var/db/powermon.db"
    GetCurrCounters(database)

    if Debug == 1:
        print("Counter 1: {0} Wh, Counter 2: {1} Wh, Counter 3: {2} Wh".format(pulsecounter1, pulsecounter2, pulsecounter3))

    client = mqtt.Client("Ecnt1") #create new instance
    client.connect(broker_address) #connect to broker

    # Format and publish first counter
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx1, pulsecounter1)
    if Debug == 1:
        print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)
    time.sleep(1) # wait a while between messages

    # Format and publish second counter
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx2, pulsecounter2)
    if Debug == 1:
        print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish
    time.sleep(1) # wait a while between messages

    # Format and publish third counter
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx3, pulsecounter3)
    if Debug == 1:
        print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish

if __name__ == '__main__':
    main()
