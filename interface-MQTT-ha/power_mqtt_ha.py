#!/usr/bin/python

"""
	power_mqtt_ha.py

	Power Monitor to MQTT interface for Home Assistant
	Reads Wh counters from SQLite database
	and sends them to Home Assistant over MQTT.

	Creates the devices in Home Assistant.

	Handling three Wh counters 

"""

import sqlite3 as sqlite, time, os
import paho.mqtt.client as mqtt

# Debug flag for printing, 0: false, 1: true, 2: print but do not publish
Debug = 0

# Configuration for MQTT broker
broker_address = "192.168.42.59"
mqttUser = "mqtt_user"
mqttPassword = "use_mqtt"

# MQTT topics
discoveryTopic1 = "homeassistant/sensor/energy_kw_1/energy/config"
stateTopic1 = "home/energy_kw_1/state"
discoveryTopic2 = "homeassistant/sensor/energy_kw_2/energy/config"
stateTopic2 = "home/energy_kw_2/state"
discoveryTopic3 = "homeassistant/sensor/energy_kw_3/energy/config"
stateTopic3 = "home/energy_kw_3/state"

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

    if Debug > 0: 
        print("Counter 1: {0} Wh, Counter 2: {1} Wh, Counter 3: {2} Wh".format(pulsecounter1, pulsecounter2, pulsecounter3))

    if Debug != 2: 
        client = mqtt.Client("energy meters") #create new instance
        client.username_pw_set(mqttUser, mqttPassword)
        client.connect(broker_address, 1883)

    # Create energy meter sensor 1
    mqttbuffer = "{\"name\":\"Energy meter 1\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"energy_meter_1\",\
      \"unit_of_meas\":\"kWh\",\
      \"dev_cla\":\"energy\",\
      \"stat_cla\":\"total\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.energy|default(0) }}\"}"\
      % stateTopic1
    if Debug > 0: 
        print(discoveryTopic1)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic1, mqttbuffer)
    time.sleep(1)

    # Create energy meter sensor 2
    mqttbuffer = "{\"name\":\"Energy meter 2\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"energy_meter_2\",\
      \"unit_of_meas\":\"kWh\",\
      \"dev_cla\":\"energy\",\
      \"stat_cla\":\"total\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.energy|default(0) }}\"}"\
      % stateTopic2
    if Debug > 0: 
        print(discoveryTopic2)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic2, mqttbuffer)
    time.sleep(1)

    # Create energy meter sensor 3
    mqttbuffer = "{\"name\":\"Energy meter 3\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"energy_meter_3\",\
      \"unit_of_meas\":\"kWh\",\
      \"dev_cla\":\"energy\",\
      \"stat_cla\":\"total\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.energy|default(0) }}\"}"\
      % stateTopic3
    if Debug > 0: 
        print(discoveryTopic3)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic3, mqttbuffer)
    time.sleep(1)

    # Format and publish energy counter 1
    mqttbuffer = "{ \"energy\" : \"%d\" }" % (pulsecounter1 / 1000)
    if Debug > 0: 
        print(stateTopic1)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic1, mqttbuffer)
    time.sleep(1)

    # Format and publish energy counter 2
    mqttbuffer = "{ \"energy\" : \"%d\" }" % (pulsecounter2 / 1000)
    if Debug > 0: 
        print(stateTopic2)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic2, mqttbuffer)
    time.sleep(1)

    # Format and publish energy counter 3
    mqttbuffer = "{ \"energy\" : \"%d\" }" % (pulsecounter3 / 1000)
    if Debug > 0: 
        print(stateTopic3)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic3, mqttbuffer)
    time.sleep(1)

if __name__ == '__main__':
    main()
