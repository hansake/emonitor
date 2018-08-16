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
    width: 180px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>

<?php

echo "<head>";
echo "<title>Energy during $MONTH $YEAR</title>";
echo "</head>";
echo "<body>";
echo "<H2>Energy consumption during $MONTH $YEAR</H2>";
echo "<li>";
echo '<a href="index.php">Back to main page</a>';
echo "</li>";

    $nmonth = date('m',strtotime($MONTH));
    $nyear = date('y',strtotime($YEAR));
    $hcounters = cal_days_in_month(CAL_GREGORIAN, $nmonth, $nyear);
    $countertxt1 = "Total kWh"; /* Counter 1 */
    $countertxt2 = "Heatpump kWh"; /* Counter 2 */
    $countertxt3 = "Spa kWh"; /* Counter 3 */
    $countertxt4 = "Other kWh"; /* Counter 1 - (Counter 2 + Counter 3) */

    $hdifftime = array_fill(0, $hcounters, 0);
    $hdiffcnt1 = array_fill(0, $hcounters, 0);
    $hdiffcnt2 = array_fill(0, $hcounters, 0);
    $hdiffcnt3 = array_fill(0, $hcounters, 0);
    $totcntr1 = 0.0;
    $totcntr2 = 0.0;
    $totcntr3 = 0.0;
    $totcntr4 = 0.0;

    $db = new SQLite3('/var/db/powermon.db');
    if (!$db)
    {
        exit($error);
    }

    $startdate = date_create("first day of $MONTH $YEAR");
    $startshow = $startdate->format('Y-m-d');
    $indexdate = date_create("last day of $MONTH $YEAR");
    $endshow = $indexdate->format('Y-m-d');

    $db->busyTimeout(5000);

    $whdiff1 = 0;
    $whdiff2 = 0;
    $whdiff3 = 0;
    $whdifflast1 = 0;
    $whdifflast2 = 0;
    $whdifflast3 = 0;
    $stampdate = date_create();
    for ($i = $hcounters; 0 <= $i; $i--)
    {
        $timestampindex = date_timestamp_get($indexdate);
        $statement = $db->prepare('SELECT * FROM Ecounters WHERE timestamp > :stamp');
        if (!$statement)
        {
            exit("Cannot prepare SELECT statement.");
        }

        $statement->bindValue(':stamp', $timestampindex);
        $results = $statement->execute();
        if (!$results)
        {
            exit("Cannot execute SELECT statement.");
        }

        if ($row = $results->fetchArray())
        {
            $whdiff1 = $whdifflast1 - $row[1];
            $whdifflast1 = $row[1];
            $whdiff2 = $whdifflast2 - $row[2];
            $whdifflast2 = $row[2];
            $whdiff3 = $whdifflast3 - $row[3];
            $whdifflast3 = $row[3];
            if ($i < $hcounters)
            {
                $hdifftime[$i] = $lasttimestamp;
                if ($whdiff1 < 0)
                    $whdiff1 = 0;
                $hdiffcnt1[$i] = $whdiff1;
                $totcntr1 += ($whdiff1 / 1000);
                if ($whdiff2 < 0)
                    $whdiff2 = 0;
                $hdiffcnt2[$i] = $whdiff2;
                $totcntr2 += ($whdiff2 / 1000);
                if ($whdiff3 < 0)
                    $whdiff3 = 0;
                $hdiffcnt3[$i] = $whdiff3;
                $totcntr3 += ($whdiff3 / 1000);
                $totcntr4 = $totcntr1 - ($totcntr2 + $totcntr3);
            }
        }
        date_sub($indexdate, date_interval_create_from_date_string('24 hours'));
        $lasttimestamp = $row[0];
    }

    header("refresh: 30;");

    echo sprintf("<H4>Energy consumption from %s to %s</H4>\n", $startshow, $endshow);

    $totcntr4 = $totcntr1 - ($totcntr2 + $totcntr3);
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
    echo "<br>";

    $maxwatts1 = max($hdiffcnt1);
    $maxwatts2 = max($hdiffcnt2);
    $maxwatts3 = max($hdiffcnt3);
    $maxwatts = max($maxwatts1, $maxwatts2, $maxwatts3);

    /*Set parameters for width & height of graph and chart*/
    $wattspacing = 10000;
    $graphwidth = $hcounters * 26;
    $graphheight = 500;
    $topmargin =  30;
    $botmargin = 40;
    $leftmargin = 20;
    $rightmargin = 80;
    $totalheight = $botmargin + $graphheight + $topmargin;
    $totalwidth = $leftmargin + $graphwidth + $rightmargin;
    $leftgraph = $leftmargin;
    $rightgraph = $leftmargin + $graphwidth;
    $topgraph = $topmargin;
    $botgraph = $topmargin + $graphheight;

    $xticker = floor($graphwidth / count($hdiffcnt1));

    $topwatt = 0;
    while ($topwatt < $maxwatts)
    {
        $topwatt += $wattspacing;
    }
    $wattlines = $topwatt / $wattspacing;
    $yspacing = $graphheight / $wattlines;
    $scalefactorwatts = $graphheight / $topwatt;

    echo "\n  <!-- Energy graph total size -->\n  ";
    echo '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$totalwidth.'" height="'.$totalheight.'" >';
    echo "\n   <!-- Outline of graph -->\n   ";
    echo '<line x1="'.$leftgraph.'" y1="'.$topgraph.'" x2="'.$rightgraph.'" y2="'.$topgraph.'" style="stroke:black;stroke-width:2"/>';
    echo "\n   ";
    echo '<line x1="'.$leftgraph.'" y1="'.$botgraph.'" x2="'.$rightgraph.'" y2="'.$botgraph.'" style="stroke:black;stroke-width:2"/>';
    echo "\n   ";
    $topgraphup = $topgraph - 15;
    echo '<line x1="'.$leftgraph.'" y1="'.$topgraphup.'" x2="'.$leftgraph.'" y2="'.$botgraph.'" style="stroke:black;stroke-width:2"/>';
    echo "\n   ";
    echo '<line x1="'.$rightgraph.'" y1="'.$topgraphup.'" x2="'.$rightgraph.'" y2="'.$botgraph.'" style="stroke:black;stroke-width:2"/>';

    echo "\n   <!-- Start and end date and time -->\n   ";
    $tx = $leftgraph + 10;
    echo '<text x="'.$tx.'" y="18" font-family="sans-serif" font-size="15px">';
    echo $startshow;
    echo '</text>';
    $tx = $leftgraph + 350;
    echo '<text x="'.$tx.'" y="18" font-family="sans-serif" font-size="15px">';
    echo 'kWh per day';
    echo '</text>';
    $tx = $rightgraph - 90;
    echo '<text x="'.$tx.'" y="18" font-family="sans-serif" font-size="15px">';
    echo $endshow;
    echo '</text>';

    echo "\n   <!-- Color and name of graphs -->\n   ";
    $cy = $totalheight - 10;
    $ty = $totalheight - 5;
    echo '<line x1="50" y1="'.$cy.'" x2="100" y2="'.$cy.'" style="stroke:red;stroke-width:4"/>';
    echo '<text x="110" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $countertxt1;
    echo '</text>';
    echo "\n   ";
    echo '<line x1="250" y1="'.$cy.'" x2="300" y2="'.$cy.'" style="stroke:green;stroke-width:4"/>';
    echo '<text x="310" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $countertxt2;
    echo '</text>';
    echo "\n   ";
    echo '<line x1="450" y1="'.$cy.'" x2="500" y2="'.$cy.'" style="stroke:blue;stroke-width:4"/>';
    echo '<text x="510" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $countertxt3;
    echo '</text>';
    echo '<line x1="650" y1="'.$cy.'" x2="700" y2="'.$cy.'" style="stroke:orange;stroke-width:4"/>';
    echo '<text x="710" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $countertxt4;
    echo '</text>';

    echo "\n   <!-- Horisontal lines -->\n   ";
    $wattline = 0;
    for ($y = $botgraph; $y > $topgraph; $y -= $yspacing)
    {
        if ($y != $botgraph)
        {
            echo '<line x1="'.$leftgraph.'" y1="'.$y.'" x2="'.$rightgraph.'" y2="'.$y.'" style="stroke:black;stroke-width:1"/>';
        }
        $tx = $rightgraph + 5;
        $ty = $y + 5;
        echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px" transform="rotate(0,'.$rightgraph.','.$ty.')">';
        $wattint = (int)$wattline;
        echo sprintf("%d kWh", $wattint / 1000);
        echo '</text>';
        echo "\n   ";
        $wattline += $wattspacing; 
    }

    $polystringwatts1 = "";
    $polystringwatts2 = "";
    $polystringwatts3 = "";
    $polystringwatts4 = "";
    echo "<!-- Verical lines -->";
    $showhour = 1;
    for ($j = 0; $j < count($hdiffcnt1); $j++)
    {
        $x = $leftgraph + $j * $xticker;
        echo "\n   ";
        echo '<line x1="'.$x.'" y1="'.$topgraph.'" x2="'.$x.'" y2="'.$botgraph.'" style="stroke:black;stroke-width:1"/>';
        $tx = $x + $xticker - 10;
        $ty = $botgraph + 18;
        echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
        echo $showhour;
        $showhour++;
        echo '</text>';

        $gx = $x + $xticker;

        $wattval = $hdiffcnt1[$j];
        $ywatts1 = $botgraph - $wattval*$scalefactorwatts;
        $polystringwatts1 = $polystringwatts1." ".$gx.",".$ywatts1;

        $wattval = $hdiffcnt2[$j];
        $ywatts2 = $botgraph - $wattval*$scalefactorwatts;
        $polystringwatts2 = $polystringwatts2." ".$gx.",".$ywatts2;

        $wattval = $hdiffcnt3[$j];
        $ywatts3 = $botgraph - $wattval*$scalefactorwatts;
        $polystringwatts3 = $polystringwatts3." ".$gx.",".$ywatts3;

        $wattval = ($hdiffcnt1[$j] - ($hdiffcnt2[$j] + $hdiffcnt3[$j]));
        $ywatts4 = $botgraph - $wattval*$scalefactorwatts;
        $polystringwatts4 = $polystringwatts4." ".$gx.",".$ywatts4;

    }
    $tx = $leftgraph - 10;
    $ty = $botgraph + 18;
    echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo ".";
    echo '</text>';

    echo "\n   <!-- Energy graph lines -->\n   ";
    echo '<polyline points="'.$polystringwatts4.'" style="fill:none;stroke:orange;stroke-width:4"/>';
    echo "\n  ";
    echo '<polyline points="'.$polystringwatts1.'" style="fill:none;stroke:red;stroke-width:4"/>';
    echo "\n   ";
    echo '<polyline points="'.$polystringwatts2.'" style="fill:none;stroke:green;stroke-width:4"/>';
    echo "\n   ";
    echo '<polyline points="'.$polystringwatts3.'" style="fill:none;stroke:blue;stroke-width:4"/>';
    echo "\n  ";

    echo '</svg>';
    echo "\n";

?>
 </body>
</html>
