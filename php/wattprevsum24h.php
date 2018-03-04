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
 <head>
  <title>Watt last 24h</title>
 </head>
 <body>
 <H2>Average watt per phase and total per hour during last 24 hours</H2>
  <li>
    <a href="http:/emonitor.php">Back to main page</a>
  </li>
  <br>

<?php
    /* heavy reuse of current monitor reg206 -> reg209, reg207 -> reg210, reg208 -> reg211*/
    $hregisters = 24;
    $registertxt206 = "Watt L1"; /* Register 206 */
    $registertxt207 = "Watt L2"; /* Register 207 */
    $registertxt208 = "Watt L3"; /* Register 208 */

    $htime = array_fill(0, $hregisters, 0);
    $hregister206 = array_fill(0, $hregisters, 0);
    $hregister207 = array_fill(0, $hregisters, 0);
    $hregister208 = array_fill(0, $hregisters, 0);
    $hregistersum = array_fill(0, $hregisters, 0);
    $hhour = array_fill(0, $hregisters, 0);

    $db = new SQLite3('/var/db/emon.db');
    if (!$db)
    {
        exit($error);
    }

    $showdate = date_create();

    $indexdate = date_create('now');
    $evenh = date_format($indexdate, 'H');
    date_time_set($indexdate, $evenh, 0, 0);
    /*print($indexdate->format('Y-m-d H:i'));*/
    $db->busyTimeout(5000);

    for ($i = $hregisters - 1; 0 <= $i; $i--)
    {
        /*print("index=");
        print($i);
        print(":");
        print($indexdate->format('Y-m-d H:i'));
        print("-");*/
        $endstampindex = date_timestamp_get($indexdate);
        $hhour[$i] = $endstampindex;
        date_sub($indexdate, date_interval_create_from_date_string('1 hour'));
        /*print($indexdate->format('Y-m-d H:i'));
        print(",");*/
        $startstampindex = date_timestamp_get($indexdate);
        if ($i == 0)
        {
            date_timestamp_set($showdate, $startstampindex);
            $showhour = $showdate->format('Y-m-d H:i');
            $startdate = $showhour;
        }
        if ($i == ($hregisters - 1))
        {
            date_timestamp_set($showdate, $endstampindex);
            $showhour = $showdate->format('Y-m-d H:i');
            $enddate = $showhour;
        }
        $statement = $db->prepare('SELECT * FROM Eregisters WHERE timestamp BETWEEN :startstamp AND :endstamp'); 
        if (!$statement)
        {
            exit("Cannot prepare SELECT statement.");
        }

        $statement->bindValue(':startstamp', $startstampindex);
        $statement->bindValue(':endstamp', $endstampindex);
        $results = $statement->execute();
        if (!$results)
        {
            exit("Cannot execute SELECT statement.");
        }

        $curreg206 = 0;
        $curreg207 = 0;
        $curreg208 = 0;
        $numberrows = 0;
        while ($row = $results->fetchArray())
        {
            $numberrows += 1;
            $htime[$i] = $row[0];
            $curreg206 += $row[10];
            $curreg207 += $row[11];
            $curreg208 += $row[12];
        }
        if ($numberrows > 0)
        {
            $hregister206[$i] = $curreg206 / $numberrows;
            $hregister207[$i] = $curreg207 / $numberrows;
            $hregister208[$i] = $curreg208 / $numberrows;
            $hregistersum[$i] = ($curreg206 + $curreg207 + $curreg208) / $numberrows;
        }
    }

    header("refresh: 30;");

    /*Set parameters for width & height of graph and chart*/
    $currspacing = 250.0;
    $graphwidth = $hregisters * 32; /* 24 * 32 */
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

    $xticker = floor($graphwidth / $hregisters);

    $maxcurr206 = max($hregister206);
    $maxcurr207 = max($hregister207);
    $maxcurr208 = max($hregister208);
    $maxcurrsum = max($hregistersum);
    $maxcurr = max($maxcurr206, $maxcurr207, $maxcurr208, $maxcurrsum, ($currspacing * 4));

    $topcurr = 0;

    while ($topcurr < $maxcurr)
    {
        $topcurr += $currspacing;
    }

    $currlines = $topcurr / $currspacing;
    $yspacing = $graphheight / $currlines;
    $scalefactorcurr = $graphheight / $topcurr;

    echo "\n  <!-- Current graph total size -->\n  ";
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
    $tx = $leftgraph + 5;
    echo '<text x="'.$tx.'" y="18" font-family="sans-serif" font-size="15px">';
    echo $startdate;
    echo '</text>';
    $tx = $rightgraph - 145;
    echo '<text x="'.$tx.'" y="18" font-family="sans-serif" font-size="15px">';
    echo $enddate;
    echo '</text>';

    echo "\n   <!-- Color and name of graphs -->\n   ";
    $cy = $totalheight - 10;
    $ty = $totalheight - 5;
    echo '<line x1="50" y1="'.$cy.'" x2="100" y2="'.$cy.'" style="stroke:red;stroke-width:4"/>';
    echo '<text x="110" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $registertxt206;
    echo '</text>';
    echo "\n   ";
    echo '<line x1="250" y1="'.$cy.'" x2="300" y2="'.$cy.'" style="stroke:green;stroke-width:4"/>';
    echo '<text x="310" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $registertxt207;
    echo '</text>';
    echo "\n   ";
    echo '<line x1="450" y1="'.$cy.'" x2="500" y2="'.$cy.'" style="stroke:blue;stroke-width:4"/>';
    echo '<text x="510" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $registertxt208;
    echo '</text>';
    echo "\n   ";
    echo '<line x1="650" y1="'.$cy.'" x2="700" y2="'.$cy.'" style="stroke:yellow;stroke-width:4"/>';
    echo '<text x="710" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo 'Total';
    echo '</text>';

    echo "\n   <!-- Horisontal lines -->\n   ";
    $currline = 0;
    for ($y = $botgraph; $y > $topgraph; $y -= $yspacing)
    {
        if ($y != $botgraph)
        {
            echo '<line x1="'.$leftgraph.'" y1="'.$y.'" x2="'.$rightgraph.'" y2="'.$y.'" style="stroke:black;stroke-width:1"/>';
        }
        $tx = $rightgraph + 5;
        $ty = $y + 5;
        echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px" transform="rotate(0,'.$rightgraph.','.$ty.')">';
        $currint = (int)$currline;
        echo sprintf("%d W", $currint);
        echo '</text>';
        echo "\n   ";
        $currline += $currspacing; 
    }

    $polystringcurr206 = "";
    $polystringcurr207 = "";
    $polystringcurr208 = "";
    $polystringcurrsum = "";
    echo "<!-- Verical lines -->";
    for ($j = 0; $j < $hregisters; $j++)
    {
        $x = $leftgraph + $j * $xticker;
        echo "\n   ";
        echo '<line x1="'.$x.'" y1="'.$topgraph.'" x2="'.$x.'" y2="'.$botgraph.'" style="stroke:black;stroke-width:1"/>';
        date_timestamp_set($showdate, $hhour[$j]);
        $showhour = $showdate->format('H');
        $tx = $x + $xticker - 10;
        $ty = $botgraph + 18;
        echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
        echo $showhour;
        echo '</text>';

        $gx = $x + $xticker;

        $ycurr206 = $botgraph - $hregister206[$j]*$scalefactorcurr;
        $polystringcurr206 = $polystringcurr206." ".$gx.",".$ycurr206;

        $ycurr207 = $botgraph - $hregister207[$j]*$scalefactorcurr;
        $polystringcurr207 = $polystringcurr207." ".$gx.",".$ycurr207;

        $ycurr208 = $botgraph - $hregister208[$j]*$scalefactorcurr;
        $polystringcurr208 = $polystringcurr208." ".$gx.",".$ycurr208;

        $ycurrsum = $botgraph - ($hregister206[$j]*$scalefactorcurr
                               + $hregister207[$j]*$scalefactorcurr
                               + $hregister208[$j]*$scalefactorcurr);
        $polystringcurrsum = $polystringcurrsum." ".$gx.",".$ycurrsum;

    }
    $tx = $leftgraph - 10;
    $ty = $botgraph + 18;
    echo '<text x="'.$tx.'" y="'.$ty.'" font-family="sans-serif" font-size="15px">';
    echo $showhour;
    echo '</text>';

    echo "\n   <!-- Current graph lines -->\n   ";
    echo '<polyline points="'.$polystringcurr206.'" style="fill:none;stroke:red;stroke-width:4"/>';
    echo "\n   ";
    echo '<polyline points="'.$polystringcurr207.'" style="fill:none;stroke:green;stroke-width:4"/>';
    echo "\n   ";
    echo '<polyline points="'.$polystringcurr208.'" style="fill:none;stroke:blue;stroke-width:4"/>';
    echo "\n  ";
    echo '<polyline points="'.$polystringcurrsum.'" style="fill:none;stroke:yellow;stroke-width:4"/>';
    echo "\n  ";

    echo '</svg>';
    echo "\n";

?>
 </body>
</html>

