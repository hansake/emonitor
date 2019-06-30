#!/usr/bin/python

"""
        Electricity Monitor to MQTT interface for Domoticz
        Reads Ampere meters from SQLite database
        and sends them to Domoticz over MQTT.

        Handling three phase Ampere meters

"""

import sqlite3 as sqlite, time, os
import paho.mqtt.client as mqtt

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
#    print("Value 1: {0}/100 A, Value 2: {1}/100 A, Value 3: {2}/100 A".format(value1, value2, value3))

    broker_address="IP address of my MQTT broker"
    client = mqtt.Client("Evalue1") #create new instance
    client.connect(broker_address) #connect to broker

    idx = 325
    fvalue1 = float (value1) / 100
    fvalue2 = float (value2) / 100
    fvalue3 = float (value3) / 100
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%06.2f;%06.2f;%06.2f\" }" % (idx, fvalue1, fvalue2, fvalue3)
#    print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish

if __name__ == '__main__':
    main()
