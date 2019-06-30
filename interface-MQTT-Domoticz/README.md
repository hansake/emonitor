A MQTT library was installed on the energy measuring Raspberry Pi with: pip install paho-mqtt

On the Domoticz side an MQTT client was created :
MQTT Client Type: "MQTT Client Gateway with LAN interface".

The Python script power_mqtt.py sends three Wh counter values to Domoticz over MQTT.

To handle this data, three virtual devices were created with the settings:
* name: "Elmätare total", Sensor Type: counter, created as Energy counter, idx: created as 322
* name: "Elmätare värme", Sensor Type: counter, created as Energy counter, idx: created as 323
* name: "Elmätare spa", Sensor Type: counter, created as Energy counter, idx: created as 324

The Python script emon_mqtt.py sends three current values in Ampere to Domoticz over MQTT.

To handle this data, a virtual device was created with the settings:
* name: "Ström L1, L2, L3", Sensor Type: Ampere (3-phase), idx: created as 325
