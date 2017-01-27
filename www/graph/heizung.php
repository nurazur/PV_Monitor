<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once( "jpgraph/jpgraph_date.php" );
require_once 'common/common.php';

$php_time_start = microtime_float();
$debug_file = "/var/www/graph/debug/ds18b20_debug.txt";

$sensor = "ds18b20";


$asked_month = get_option_month($_GET);
$asked_year =  get_option_year ($_GET);

// determine first day and its time stamp
$today = get_option_day_first($_GET, $asked_month, $asked_year);
$start_time = strtotime($today);

// determine last day and its time stamp
$stop_time = get_option_time_last($_GET, $asked_month, $asked_year, $today);
$bistag = date('d.m.Y', $stop_time);

// shall we plot nights in blue volour?
$do_sunrs = plot_show_night_colour($_GET);

// custom plot width
$stretch =  plot_width($_GET);


// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

// x-Axis array
$time = array();
$time_2 = array();

// list of days in the time period requested, in time stamp format
$days_list = array();

// default y-axis scale
$max = 1;
$min =0;

$t_6er = array();
$t_aussen_n = array();
$t_gang = array();
//Indices:
/* 01,Mon,05.01.2015,05:31:02,1028.80,1006.99,21.7
0 = ww-1
1= Wochentag
2= Datum dd.mm.yyyy
3= Uhrzeit hh:mm:ss
4= Luftdruck relativ zu normal Null
5= Luftdruck absolut
6= Temperatur bei Messung
*/

$last_date = "";
for ($k=0; $k<$lzi; $k++)
{
    $zeile=splitline(',', $list[$k]);
    if (count($zeile) < 6 )
    {
        continue; // toleriere Leerzeilen
    }
    $ts = strtotime($zeile[2]);
    if ($ts < $start_time) 
    {
        continue;
    }
    else if ($ts < $stop_time)
    {
        if ($zeile[2] != $last_date)
        {
            $days_list[] = $ts;
            $last_date =   $zeile[2];
        }
        
        $c_time = strtotime($zeile[2]." ".$zeile[3]);
        $time[] = $c_time;
        for ($j=4; $j< count($zeile); $j+=2)  // fuer alle Eintraege - Suche nach den richtigen Sensoren
        {
            if ($zeile[$j] =="Sensor 12")
            {
                $v = $j + 1;
                if ($zeile[$v] > $max) $max = $zeile[$v];
                $t_6er[] = $zeile[$v];
            }
            else if ($zeile[$j] == "Sensor 11")
            {
                $v = $j + 1;
                if ($zeile[$v] > $max) $max = $zeile[$v];
                $t_aussen_n[] =  rtrim($zeile[$v]);
            }
            else if ($zeile[$j] == "Sensor 2")
            {
                $v = $j + 1;
                if ($zeile[$v] > $max) $max = $zeile[$v];
                $t_gang[] =  rtrim($zeile[$v]);
                $time_2[] =  $c_time;
            }
        }
    }
    else
        break;
}



$max = floor(($max+5)/5.0)*5 ;   // round up
// errorlog($debug_file, "max=$max, min=$min");
// Berechne Y-Skala
$mintime = $start_time;
$maxtime = $time[count($time)-1];
$maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00

// Create array for the sunrise / sunset background colour
$time_ss  = array();
$ydata_ss = array();
generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, $time_ss, $ydata_ss);



// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);

// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen

$graph->SetScale('datlin',$min, $max, $mintime, $maxtime); 
//$graph->SetY2Scale('lin', 220, 250); 

$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days
if($diff_time <= 1)

//Titel
    $graph->title->Set("Temperaturverlauf ".$today);	// Titel der Grafik
else
    $graph->title->Set("Temperaturverlauf ".$today." bis ".$bistag);

$graph->title->SetFont(FF_FONT2,FS_BOLD);

// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');

// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);
//$graph->SetFrame(true,'darkblue',1); 

// X- Achse
if ($diff_time >= 5 ) // mehr als 5 Tage: x-Achse zeigt nur den tag an
{
    $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
    $graph->xaxis->SetLabelFormatString('d.M', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->scale->ticks->Set(86400);
}
else if ($diff_time < 5 && $diff_time > 2) // zwischen 2 und 5 Tagen: x-Achse zeigt Tag und Uhrzeit
{
    $graph->xaxis->scale->SetTimeAlign(DAYADJ_1,DAYADJ_1); 
    $graph->xaxis->SetLabelFormatString('d.m H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->scale->ticks->Set(43200);
}
else
{
    $graph->xaxis->scale->SetTimeAlign(HOURADJ_1,HOURADJ_1); // 1-2 Tag(e): nur Uhrzeit anzeigen
    $graph->xaxis->SetLabelFormatString('H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
    $graph->xaxis->SetTextLabelInterval(1);
}
//$graph->xaxis->scale->SetTimeAlign(HOURADJ_1,HOURADJ_1); 
$graph->xaxis->SetLabelAngle(45); 
$graph->xaxis->SetTickLabels($time);
//$graph->xaxis->SetLabelFormatString('H:i', true); // d,M / d,m,y / d,m,Y / H:i:s
//$graph->xaxis->SetTextLabelInterval(1);
$graph->xaxis->HideFirstTicklabel() ;

$graph->xgrid->Show(true);
$graph->xgrid->SetLineStyle('dashed');

//Y-Achse
$graph->yaxis->title->Set("Temperatur [deg C]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 

// Create the linear plot
$lineplot=new LinePlot($t_6er, $time);

//$lineplot->SetColor('#000000');
$graph->img->SetAntiAliasing(false);

// Add the plot to the graph
$graph->Add($lineplot);
$lineplot->SetColor("red");
$lineplot->SetWeight(2); 
$lineplot->SetLegend("Vorlauf");

//$lineplot->mark->SetType(MARK_UTRIANGLE);
//$lineplot->mark->SetColor('darkblue');
//$lineplot->mark->SetFillColor('blue');


$lineplot_aussen_n = new LinePlot($t_aussen_n, $time);
$graph->Add($lineplot_aussen_n);
//$graph->AddY2($lineplot_aussen_n); 
$lineplot_aussen_n->SetColor('darkblue');
$lineplot_aussen_n->SetWeight(2);
$lineplot_aussen_n->SetLegend("Ruecklauf");
 
$lineplot_gang = new LinePlot($t_gang, $time_2);
$graph->Add($lineplot_gang);
//$graph->AddY2($lineplot_aussen_n); 
$lineplot_gang->SetColor('orange');
$lineplot_gang->SetWeight(2);
$lineplot_gang->SetLegend("Gang");
 
$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

//Legende
$graph->legend->Pos(0.5,0.75,"center","bottom");
$graph->legend->SetLayout(LEGEND_HOR); 

// plot sunrise / sunset time zones
if ($do_sunrs)
{
    $lineplot_ss = new LinePlot($ydata_ss, $time_ss);
    $lineplot_ss->SetFillColor('blue@0.8'); 
    $graph->Add($lineplot_ss);
    $lineplot_ss->SetColor('#FFFFA0@0.95');
}

//measure time
$php_time_end = microtime_float();
$php_runtime = $php_time_end - $php_time_start;
errorlog($debug_file, "runtime before plot: ".$php_runtime."s");

// Display the graph
$graph->Stroke();

//measure total time elapsed
$php_time_end = microtime_float();
$php_runtime = $php_time_end - $php_time_start;
errorlog($debug_file, $php_runtime."s\n");
?>
