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
  <title>Energy monitor</title>
 </head>
 <body>


<H2>Energy consumption monitor</H2>
<li>
   <a href="powmonprev24h.php">Energy consumption during last 24h</a>
</li>
<li>
   <a href="powmonprevday.php">Energy consumption during last day</a>
</li>
<li>
   <a href="powmonprev30d.php">Energy consumption during last 30 days</a>
</li>
<li>
   <a href="powmon2017-year.php">Energy consumption during 2017</a>
</li>
<li>
   <a href="powmon2018-year.php">Energy consumption during 2018</a>
</li>
<H2>Electricity monitor</H2>
<li>
   <a href="emonitor.php">Current and power monitor</a>
</li>

<?php
    $countertxt1 = "Total kWh"; /* Counter 1 */
    $countertxt2 = "Heatpump kWh"; /* Counter 2 */
    $countertxt3 = "Spa kWh"; /* Counter 3 */
    $countertxt4 = "Other kWh"; /* Counter 1 - (Counter 2 + Counter 3) */

    $totcntr1 = 0.0;
    $totcntr2 = 0.0;
    $totcntr3 = 0.0;
    $totcntr4 = 0.0;

    $db = new SQLite3('/var/db/powermon.db');
    if (!$db)
    {
        exit($error);
    }

    $db->busyTimeout(5000);

    $statement = $db->prepare('SELECT * FROM Ecounters WHERE 1');
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

    $statement = $db->prepare('SELECT * FROM Lecounters WHERE 1');
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
        $letimestamp = $row[1];
        $totcntr1 = $row[2] / 1000;
        $totcntr2 = $row[3] / 1000;
        $totcntr3 = $row[4] / 1000;
        $totcntr4 = $totcntr1 - ($totcntr2 + $totcntr3);
    }

    header("refresh: 40;");

    $nowdate = date_create();
    $startdate = date_create();
    date_timestamp_set($startdate, $starttimestamp);

    $stampdate = date_create();
    date_timestamp_set($stampdate, $letimestamp);

    echo sprintf("<H4>Energy measurement</H4>\n");
    echo "Last measurement: ";
    echo ($stampdate->format('Y-m-d H:i'));
    echo "<br>Total kWh measured since: ";
    echo ($startdate->format('Y-m-d H:i'));

    $dinterval = date_diff($nowdate, $stampdate);
    $dint = $dinterval->days*86400 + $dinterval->h*3600 + $dinterval->i*60 + $dinterval->s;
    if ($dint > 60)
    {
        echo "<br>Is seems that the data collection has stopped.";
        echo "<br>Time is now: ";
        echo ($nowdate->format('Y-m-d H:i'));
    }

    echo "<table>";
    echo "<tr>";
    echo sprintf("<th>%s</th>", $countertxt1);
    echo sprintf("<th>%s</th>", $countertxt2);
    echo sprintf("<th>%s</th>", $countertxt3);
    echo sprintf("<th>%s</th>", $countertxt4);
    echo  "</tr>";
    echo  "<tr>";
    echo sprintf("<td>%01.2f</td>", $totcntr1);
    echo sprintf("<td>%01.2f</td>", $totcntr2);
    echo sprintf("<td>%01.2f</td>", $totcntr3);
    echo sprintf("<td>%01.2f</td>", $totcntr4);
    echo "</tr>";
    echo "</table>";

?>

<H2>Documentation</H2>
<li>
   <a href="http://192.168.1.20/MediaWiki/index.php?title=Energy_monitor_on_Raspberry_Pi#Energy_monitor_and_web_interface">Energy monitor on Raspberry Pi</a>
</li>
<br>
<li>
   <a href="phpliteadmin.php">Database administration</a>
</li>

 </body>
</html>
