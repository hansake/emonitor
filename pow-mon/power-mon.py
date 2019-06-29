#!/usr/bin/python

"""
	Power Monitor
	Logs power consumption to an SQLite database, based on the number
	of S0 pulses from an electricity meter.

	Handling three S0 pulse inputs

"""

import RPi.GPIO as GPIO, sqlite3 as sqlite, time, os

pulsecounter1 = 0
pulsecounter2 = 0
pulsecounter3 = 0


def create_connection(db_file):
    """ create a database connection to the SQLite database
        specified by db_file
    :param db_file: database file
    :return: Connection object or None
    """
    try:
        conn = sqlite.connect(db_file)
        return conn
    except sqlite.Error as e:
        print(e)
 
    return None

def create_table(conn, create_table_sql):
    """ create a table from the create_table_sql statement
    :param conn: Connection object
    :param create_table_sql: a CREATE TABLE statement
    :return:
    """
    try:
        c = conn.cursor()
        c.execute(create_table_sql)
    except sqlite.Error as e:
        print(e)

def InsertCounters(db_file, timestamp, cntr1, cntr2, cntr3):
	try:
		dbconn=sqlite.connect(db_file)
		dbdata=dbconn.cursor()
		dbdata.execute("INSERT INTO Ecounters VALUES(?, ?, ?, ?)", (timestamp, cntr1, cntr2, cntr3))
		dbconn.commit()
	except sqlite.Error,e:
		print e
		if dbconn:
			dbconn.rollback()
	finally:
		if dbconn:
			dbconn.close()

def UpdateCurrCounters(db_file, timestamp, cntr1, cntr2, cntr3):
	try:
		dbconn=sqlite.connect(db_file)
		dbdata=dbconn.cursor()
                dbdata.execute("UPDATE Lecounters SET timestamp=?, lecounter1=?, lecounter2=?, lecounter3=? WHERE id=1", (timestamp, cntr1, cntr2, cntr3))
		dbconn.commit()
	except sqlite.Error,e:
		print e
		if dbconn:
			dbconn.rollback()
	finally:
		if dbconn:
			dbconn.close()

def InitCurrCounters(db_file, timestamp):
	try:
		dbconn=sqlite.connect(db_file)
		dbdata=dbconn.cursor()
                dbdata.execute("INSERT INTO Lecounters VALUES(?, ?, ?, ?, ?)", (1, timestamp, 0, 0, 0))
		dbconn.commit()
	except sqlite.Error,e:
#		print e
#                print("Opening existing database: {0}".format(db_file))
		if dbconn:
			dbconn.rollback()
	finally:
		if dbconn:
			dbconn.close()

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

def pulse1_callback(pin):
        global pulsecounter1
#        print("pulse on pin: {0}".format(pin))
        pulsecounter1+=1

def pulse2_callback(pin):
        global pulsecounter2
#        print("pulse on pin: {0}".format(pin))
        pulsecounter2+=1

def pulse3_callback(pin):
        global pulsecounter3
#        print("pulse on pin: {0}".format(pin))
        pulsecounter3+=1

def main():
    GPIO.setmode(GPIO.BCM)

    pulsepin1 = 18
    GPIO.setup(pulsepin1, GPIO.IN)

    pulsepin2 = 23
    GPIO.setup(pulsepin2, GPIO.IN)

    pulsepin3 = 24
    GPIO.setup(pulsepin3, GPIO.IN)

    GPIO.add_event_detect(pulsepin1, GPIO.RISING, callback=pulse1_callback)
    GPIO.add_event_detect(pulsepin2, GPIO.RISING, callback=pulse2_callback)
    GPIO.add_event_detect(pulsepin3, GPIO.RISING, callback=pulse3_callback)

    database = "/var/db/powermon.db"

    sql_create_ecounters_table = """CREATE TABLE IF NOT EXISTS Ecounters (
                                    timestamp INTEGER PRIMARY KEY,
                                    ecounter1 INTEGER,
                                    ecounter2 INTEGER,
                                    ecounter3 INTEGER
                                );"""
 
    sql_create_lecounters_table = """CREATE TABLE IF NOT EXISTS Lecounters (
                                    id INTEGER PRIMARY KEY,
                                    timestamp INTEGER,
                                    lecounter1 INTEGER,
                                    lecounter2 INTEGER,
                                    lecounter3 INTEGER
                                );"""
 
    # create a database connection
    conn = create_connection(database)
    if conn is not None:
        # create energy counters table
        create_table(conn, sql_create_ecounters_table)
        # create latest energy counters table
        create_table(conn, sql_create_lecounters_table)
        conn.close()
    else:
        print("Error! cannot create the database connection.")

    timenow = int(time.time())
    InitCurrCounters(database, timenow)

    GetCurrCounters(database)

    while True:
#        print("Energy: 1 S0: {0} Wh, 2 S0: {1} Wh, 3 S0: {2} Wh".format(pulsecounter1, pulsecounter2, pulsecounter3))
        timenow = int(time.time())
        InsertCounters(database, timenow, pulsecounter1, pulsecounter2, pulsecounter3)
        timenow = int(time.time())
        UpdateCurrCounters(database, timenow, pulsecounter1, pulsecounter2, pulsecounter3)
        time.sleep(20)
    
if __name__ == '__main__':
    main()

