<!DOCTYPE HTML>
<html>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
}

td, th {
    border: 2px solid black;
    text-align: left;
    padding: 8px;
    width: 150px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
 <head>
  <title>Electricity monitor</title>
 </head>
 <body>

<?php

    $registertxt200 = "Voltage L1"; /* Register 200, voltage L1 */
    $registertxt201 = "Voltage L2"; /* Register 201, voltage L2 */
    $registertxt202 = "Voltage L3"; /* Register 202, voltage L3 */

    $registertxt203 = "Voltage L1-L2"; /* Register 203, voltage L1-L2 */
    $registertxt204 = "Voltage L2-L3"; /* Register 204, voltage L2-L3 */
    $registertxt205 = "Voltage L3-L1"; /* Register 205, voltage L3-L1 */

    $registertxt206 = "Current L1"; /* Register 206, current L1 */
    $registertxt207 = "Current L2"; /* Register 207, current L2 */
    $registertxt208 = "Current L3"; /* Register 208, current L3 */

    $registertxt209 = "Power L1"; /* Register 209, power L1 */
    $registertxt210 = "Power L2"; /* Register 210, power L2 */
    $registertxt211 = "Power L3"; /* Register 211, power L3 */

    $registertxt275 = "Frequency"; /* Register 275 */

    $register200 = 0.0;
    $register201 = 0.0;
    $register202 = 0.0;

    $register203 = 0.0;
    $register204 = 0.0;
    $register205 = 0.0;

    $register206 = 0.0;
    $register207 = 0.0;
    $register208 = 0.0;

    $register209 = 0.0;
    $register210 = 0.0;
    $register211 = 0.0;

    $register275 = 0.0;

    $db = new SQLite3('/var/db/emon.db');
    if (!$db)
    {
        exit($error);
    }

    $db->busyTimeout(5000);

    /* Read last record */
    $statement = $db->prepare('SELECT * FROM Eregisters WHERE timestamp = (SELECT MAX(timestamp) FROM Eregisters)');
    if (!$statement)
    {
        exit("Cannot prepare SELECT statement.");
    }
    $results = $statement->execute();
    if (!$results)
    {
        exit("Cannot execute SELECT statement.");
    }

    if ($row = $results->fetchArray())
    {
        $starttimestamp = $row[0];
    }

    header("refresh: 40;");

    $nowdate = date_create();
    $startdate = date_create();
    date_timestamp_set($startdate, $starttimestamp);

    echo sprintf("<H2>Electricity monitor</H2>\n");
    echo "<br>Measured at: ";
    echo ($startdate->format('Y-m-d H:i'));
    echo ".";
    $dinterval = date_diff($nowdate, $startdate);
    $dint = $dinterval->days*86400 + $dinterval->h*3600 + $dinterval->i*60 + $dinterval->s;
    if ($dint > 60)
    {
        echo "<br>Is seems that the data collection has stopped.";
        echo "<br>Time is now: ";
        echo ($nowdate->format('Y-m-d H:i'));
    }

    $register200 = $row[1];
    $register201 = $row[2];
    $register202 = $row[3];

    $register203 = $row[4];
    $register204 = $row[5];
    $register205 = $row[6];

    $register206 = $row[7];
    $register207 = $row[8];
    $register208 = $row[9];

    $register209 = $row[10];
    $register210 = $row[11];
    $register211 = $row[12];

    $register275 = $row[13];

    $register600 = $row[14];
    $register601 = $row[15];

    $currentdivider = $register600 / $register601;

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", $registertxt206);
    echo sprintf("<th>%s</th>", $registertxt207);
    echo sprintf("<th>%s</th>", $registertxt208);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.2f A</td>", ($register206 / ($currentdivider * 10)));
    echo sprintf("<td>%01.2f A</td>", ($register207 / ($currentdivider * 10)));
    echo sprintf("<td>%01.2f A</td>", ($register208 / ($currentdivider * 10)));
    echo "</tr>";
    echo "</table>";

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", $registertxt200);
    echo sprintf("<th>%s</th>", $registertxt201);
    echo sprintf("<th>%s</th>", $registertxt202);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.1f V</td>", ($register200 / 10));
    echo sprintf("<td>%01.1f V</td>", ($register201 / 10));
    echo sprintf("<td>%01.1f V</td>", ($register202 / 10));
    echo "</tr>";
    echo "</table>";

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", $registertxt203);
    echo sprintf("<th>%s</th>", $registertxt204);
    echo sprintf("<th>%s</th>", $registertxt205);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.1f V</td>", ($register203 / 10));
    echo sprintf("<td>%01.1f V</td>", ($register204 / 10));
    echo sprintf("<td>%01.1f V</td>", ($register205 / 10));
    echo "</tr>";
    echo "</table>";

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", $registertxt209);
    echo sprintf("<th>%s</th>", $registertxt210);
    echo sprintf("<th>%s</th>", $registertxt211);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.0f W</td>", $register209);
    echo sprintf("<td>%01.0f W</td>", $register210);
    echo sprintf("<td>%01.0f W</td>", $register211);
    echo "</tr>";
    echo "</table>";

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", "Total Power");
    echo sprintf("<th>%s</th>", $registertxt275);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.0f W</td>", $register209 + $register210 + $register211);
    echo sprintf("<td>%01.2f Hz</td>", ($register275 / 100));
    echo "</tr>";
    echo "</table>";


?>

<H4>Graphs</H4>
<li>
   <a href="http:/currprev24h.php">Current during last 24 hours</a>
</li>
<li>
   <a href="http:/currprev30d.php">Current during last 30 days</a>
</li>
<li>
   <a href="http:/wattprev24h.php">Watt during last 24 hours</a>
</li>
<li>
   <a href="http:/wattprevsum24h.php">Watt including total during last 24 hours</a>
</li>
<li>
   <a href="http:/wattprev30d.php">Watt during last 30 days</a>
</li>
<H4>Documentation</H4>
<li>
   <a href="http://192.168.1.20/MediaWiki/index.php?title=Energy_monitor_with_Modbus_on_Raspberry_Pi">Electricity monitor on Raspberry Pi</a>
</li>
<br>
<li>
   <a href="http:/phpliteadmin.php">Database administration</a>
</li>

 </body>
</html>
