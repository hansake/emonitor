#!/usr/bin/python

"""
        Power Monitor to MQTT interface for Domoticz
        Reads Wh counters from SQLite database
        and sends them to Domoticz over MQTT.

        Handling three Wh counters

"""

import sqlite3 as sqlite, time, os
import paho.mqtt.client as mqtt

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
    database = "/var/db//powermon.db"
    GetCurrCounters(database)
    print("Counter 1: {0} Wh, Counter 2: {1} Wh, Counter 3: {2} Wh".format(pulsecounter1, pulsecounter2, pulsecounter3))

    broker_address="IP of my MQTT broker"
    client = mqtt.Client("Ecnt1") #create new instance
    client.connect(broker_address) #connect to broker

    idx1 = 322
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx1, pulsecounter1)
    print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish

    idx2 = 323
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx2, pulsecounter2)
    print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish

    idx3 = 324
    mqttbuffer = "{ \"idx\" : %d, \"nvalue\" : 0, \"svalue\" : \"%d\" }" % (idx3, pulsecounter3)
    print(mqttbuffer)
    client.publish("domoticz/in", mqttbuffer)#publish

if __name__ == '__main__':
    main()
