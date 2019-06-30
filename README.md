# emonitor
Power and electricity monitor

The emonitor software collects input from three energy meters with S0 interfaces and presents the energy consumption as values and graphs. This software also collects values from a Janitza  Universal Measuring Device UMG 96S over Modbus and presents the values of current, voltage, power and frequency as well as showing of current and power in a graphical view.

The software is running on a Raspberry Pi 3 computer board.

Collecting measurements:

The input from energy meters with S0 interfaces is collected by the Python script "power-mon.py". The measurements are saved in a SQLite database. The Python script is started by the init.d script "power-mon"

The input from the Janitza  Universal Measuring Device UMG 96S over Modbus is collected by the Python script "emonitor.py". The Python script is started by the init.d script "emon". The Modbus interface is implemented with a Hjelmslund RS485 interface USB485-STISO. As this interface does not directly work with a standard Linux USB-serial interface driver there is a init.d script "usb485" that makes the nessesary setup of USB identity to support the interface.

Presenting measurements:

The presentation of measurements is done using PHP scripts. Graphics is presented using Scalable Vector Graphics (SVG).

Interface to Domoticz using MQTT:

No there is also an interface between power/electricity monitor and Domoticz using MQTT in the directory: interface-MQTT-Domoticz
The Python scripts power_mqtt.py and emon_mqtt.py may be called every minute with crontab.
