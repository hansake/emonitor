#!/usr/bin/python

"""
	emon_mqtt_ha.py

	Electricity Monitor to MQTT interface for Home Assistant
	Reads Ampere meters from SQLite database
	and sends them to Home Assistant over MQTT.

	Creates the devices in Home Assistant.

	Handling three phase Ampere meters.

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
discoveryTopic1 = "homeassistant/sensor/current_l1/current/config"
stateTopic1 = "home/current_l1/state"
discoveryTopic2 = "homeassistant/sensor/current_l2/current/config"
stateTopic2 = "home/current_l2/state"
discoveryTopic3 = "homeassistant/sensor/current_l3/current/config"
stateTopic3 = "home/current_l3/state"

# The Ampere meters
ampvalue1 = 0
ampvalue2 = 0
ampvalue3 = 0

def GetCurrValues(db_file):
    global ampvalue1
    global ampvalue2
    global ampvalue3
    try:
        dbconn=sqlite.connect(db_file)
        dbdata=dbconn.cursor()
        dbdata.execute("SELECT * FROM Eregisters WHERE timestamp = (SELECT MAX(timestamp) FROM Eregisters)")
    except sqlite.Error,e:
        print e
    row = dbdata.fetchone()
    ampvalue1 = row[7]
    ampvalue2 = row[8]
    ampvalue3 = row[9]
    if dbconn:
        dbconn.close()

def main():
    database = "/var/db/emon.db"
    GetCurrValues(database)
    if Debug > 0:
        print("Value 1: {0}/100 A, Value 2: {1}/100 A, Value 3: {2}/100 A".format(ampvalue1, ampvalue2, ampvalue3))
    if Debug != 2: 
        client = mqtt.Client("current meters") #create new instance
        client.username_pw_set(mqttUser, mqttPassword)
        client.connect(broker_address, 1883)

    # Create current meter sensor L1
    mqttbuffer = "{\"name\":\"Current meter L1\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"current_meter_l1\",\
      \"unit_of_meas\":\"A\",\
      \"dev_cla\":\"current\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.current|default(0) }}\"}"\
      % stateTopic1
    if Debug > 0: 
        print(discoveryTopic1)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic1, mqttbuffer)
    time.sleep(1)

    # Create current meter sensor L2
    mqttbuffer = "{\"name\":\"Current meter L2\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"current_meter_l2\",\
      \"unit_of_meas\":\"A\",\
      \"dev_cla\":\"current\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.current|default(0) }}\"}"\
      % stateTopic2
    if Debug > 0: 
        print(discoveryTopic2)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic2, mqttbuffer)
    time.sleep(1)

    # Create current meter sensor L3
    mqttbuffer = "{\"name\":\"Current meter L3\",\
      \"stat_t\":\"%s\",\
      \"unique_id\":\"current_meter_l3\",\
      \"unit_of_meas\":\"A\",\
      \"dev_cla\":\"current\",\
      \"frc_upd\":true,\
      \"val_tpl\":\"{{ value_json.current|default(0) }}\"}"\
      % stateTopic3
    if Debug > 0: 
        print(discoveryTopic3)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(discoveryTopic3, mqttbuffer)
    time.sleep(1)

    # Format and publish current meter L1
    fvalue1 = float (ampvalue1) / 100
    mqttbuffer = "{ \"current\" : \"%06.2f\" }" % fvalue1
    if Debug > 0: 
        print(stateTopic1)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic1, mqttbuffer)
    time.sleep(1)

    # Format and publish current meter L2
    fvalue2 = float (ampvalue2) / 100
    mqttbuffer = "{ \"current\" : \"%06.2f\" }" % fvalue2
    if Debug > 0: 
        print(stateTopic2)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic2, mqttbuffer)
    time.sleep(1)

    # Format and publish current meter L3
    fvalue3 = float (ampvalue3) / 100
    mqttbuffer = "{ \"current\" : \"%06.2f\" }" % fvalue3
    if Debug > 0: 
        print(stateTopic3)
        print(mqttbuffer)
    if Debug != 2: 
        client.publish(stateTopic3, mqttbuffer)
    time.sleep(1)

if __name__ == '__main__':
    main()

