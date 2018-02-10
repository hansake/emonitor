#!/usr/bin/python

"""
	Read power meter over MODBUS and put values in SQLite database.

"""

import sqlite3 as sqlite, time, os, sys

import pymodbus
import serial
from pymodbus.pdu import ModbusRequest
from pymodbus.client.sync import ModbusSerialClient as ModbusClient #initialize a serial RTU client instance
from pymodbus.transaction import ModbusRtuFramer

import logging
logging.basicConfig()
log = logging.getLogger()
#log.setLevel(logging.DEBUG)
log.setLevel(logging.WARN)

printvalues = False

# Registers
register200 = 0
register201 = 0
register202 = 0
register203 = 0
register204 = 0
register205 = 0
register206 = 0
register207 = 0
register208 = 0
register209 = 0
register210 = 0
register211 = 0
register275 = 0
register600 = 0
register601 = 0
register602 = 0
register603 = 0

# Database routines

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

def InsertRegisters(db_file, timestamp):

    # Registers
    global register200
    global register201
    global register202
    global register203
    global register204
    global register205
    global register206
    global register207
    global register208
    global register209
    global register210
    global register211
    global register275
    global register600
    global register601
    global register602
    global register603

    try:
            dbconn=sqlite.connect(db_file)
            dbdata=dbconn.cursor()
            dbdata.execute("INSERT INTO Eregisters VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                (timestamp,
                 register200,
                 register201,
                 register202,
                 register203,
                 register204,
                 register205,
                 register206,
                 register207,
                 register208,
                 register209,
                 register210,
                 register211,
                 register275,
                 register600,
                 register601,
                 register602,
                 register603))
            dbconn.commit()
    except sqlite.Error,e:
            print e
            if dbconn:
                dbconn.rollback()
    finally:
            if dbconn:
                dbconn.close()


def get_modbus_values(parameter):
    """ get values from modbus """

    # Registers
    global register200
    global register201
    global register202
    global register203
    global register204
    global register205
    global register206
    global register207
    global register208
    global register209
    global register210
    global register211
    global register275
    global register600
    global register601
    global register602
    global register603

    # Get values from MODBUS
    client= ModbusClient(method='rtu', port='/dev/ttyUSB0',stopbits=1, bytesize=8, parity='N', baudrate=38400)
    #Connect to the serial modbus server
    connection = client.connect()
    #print connection
    # Read registers 200 - 211
    result= client.read_holding_registers(200, 12, unit=1)
    regs = result.registers
    register200 = regs[0]
    register201 = regs[1]
    register202 = regs[2]
    register203 = regs[3]
    register204 = regs[4]
    register205 = regs[5]
    register206 = regs[6]
    register207 = regs[7]
    register208 = regs[8]
    register209 = regs[9]
    register210 = regs[10]
    register211 = regs[11]
    if printvalues == True:
        print "Voltage L1-N  {0:6.1f}V, L2-N  {1:6.1f}V, L3-N  {2:6.1f}V".format(float(regs[0])/10, float(regs[1])/10, float(regs[2])/10)
        print "Voltage L1-L2 {0:6.1f}V, L2-L3 {1:6.1f}V, L3-L1 {2:6.1f}V".format(float(regs[3])/10, float(regs[4])/10, float(regs[5])/10)
        print "Current L1    {0:6.2f}A, L2    {1:6.2f}A, L3    {2:6.2f}A".format(float(regs[6])/100, float(regs[7])/100, float(regs[8])/100)
        print "Real Power L1 {0:6.0f}W, L2    {1:6.0f}W, L3    {2:6.0f}W".format(float(regs[9]), float(regs[10]), float(regs[11]))
    # Read register 275
    result= client.read_holding_registers(275, 1, unit=1)
    regs = result.registers
    register275 = regs[0]
    if printvalues == True:
        print "Frequency L1  {0:6.2f}Hz".format(float(regs[0])/100)
    # Read registers 600 - 603
    result= client.read_holding_registers(600, 4, unit=1)
    regs = result.registers
    register600 = regs[0]
    register601 = regs[1]
    register602 = regs[2]
    register603 = regs[3]
    if printvalues == True:
        print "Current transformer primary  {0:6d}, secondary  {1:6d}".format(regs[0], regs[1])
        print "Voltage transformer primary  {0:6d}, secondary  {1:6d}".format(regs[2], regs[3])
    #Closes the underlying socket connection
    client.close()

def main():
    global printvalues
    # check arguments
    # print 'Number of arguments:', len(sys.argv), 'arguments.'
    # print 'Argument List:', str(sys.argv)
    argument = 'noprint'
    arguments = len(sys.argv)
    if arguments > 1:
        argument = str(sys.argv[1])
    if argument == 'print':
        printvalues = True 
        print "print values"

    # open database
    database = "/var/db/emon.db"

    sql_create_eregisters_table = """CREATE TABLE IF NOT EXISTS Eregisters (
                                    timestamp INTEGER PRIMARY KEY,
                                    mregister200 INTEGER,
                                    mregister201 INTEGER,
                                    mregister202 INTEGER,
                                    mregister203 INTEGER,
                                    mregister204 INTEGER,
                                    mregister205 INTEGER,
                                    mregister206 INTEGER,
                                    mregister207 INTEGER,
                                    mregister208 INTEGER,
                                    mregister209 INTEGER,
                                    mregister210 INTEGER,
                                    mregister211 INTEGER,
                                    mregister275 INTEGER,
                                    mregister600 INTEGER,
                                    mregister601 INTEGER,
                                    mregister602 INTEGER,
                                    mregister603 INTEGER
                                );"""

    conn = create_connection(database)
    if conn is not None:
        # create meter registers table
        create_table(conn, sql_create_eregisters_table)
        conn.close()
    else:
        print("Error! cannot create the database connection.")

    while True:
        if printvalues == True:
            print("collect values over modbus")
        get_modbus_values("start")
        if printvalues == True:
            print("insert values into database")
        timenow = int(time.time())
        InsertRegisters(database, timenow)
        time.sleep(20)

if __name__ == '__main__':
    main()

