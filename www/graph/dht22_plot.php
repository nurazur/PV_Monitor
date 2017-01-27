<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once( "jpgraph/jpgraph_date.php" );
require_once 'common/common.php';

$debug_file = "../debug_dht22.txt";
$filename = "../logfiles/dht22_log.csv";


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


$name = "17";
if ( !empty($_GET["name"]) ) $name = $_GET["name"];

// Generate List of all Entries
$list = generate_list_default($filename);
$lzi = count($list);

// x-Axis array
$time = array();

// list of days in the time period requested, in time stamp format
$days_list = array();

// default y-axis scale
$max_h = 0;
$min_h =100;

$max_t = -100;
$min_t =100;

$dht_temp = array();
$dht_hum = array();

$dht_27_temp = array();
$dht_27_hum = array();
//Indices:
/* 31.01.2015,14:00:02,17,39.50,17.1,27,39.90,16.1

0= Datum dd.mm.yyyy
1= Uhrzeit hh:mm:ss
2= Sensorname (2 + i*3)
3= Luftfeuchtigkeit (2 + i*3 + 1)
4= Temperatur (2 + i*3 +2)
*/

$last_date = "";
for ($k=0; $k<$lzi; $k++)
{
    $zeile=splitline(',', $list[$k]);
    if (count($zeile) < 5 )
    {
        continue; // toleriere Leerzeilen
    }
    
    $ts = strtotime($zeile[0]);
 
    if ($ts < $start_time) 
    {
        continue;
    }
    else if ($ts < $stop_time)
    {
        if ($zeile[0] != $last_date)
        {
            $days_list[] = $ts;
            $last_date =   $zeile[0];
        }
        
        $c_time = strtotime($zeile[0]." ".$zeile[1]);
        $time[] = $c_time;
        for ($j=2; $j< count($zeile); $j+=3)  // fuer alle Eintraege - Suche nach den richtigen Sensoren
        {
            if ($zeile[$j] == $name)
            {
                $h = $j + 1;
                if ($zeile[$h] != '-')
                {
                    if ($zeile[$h] > $max_h) $max_h = $zeile[$h];
                    if ($zeile[$h] < $min_h) $min_h = $zeile[$h];
                }
                $dht_hum[] = $zeile[$h];
                
                $t = $j + 2;
                $val = rtrim($zeile[$t]);
                if ($val != '-')
                {
                    if ($val > $max_t) $max_t = $val;
                    if ($val < $min_t) $min_t = $val;
                }
                $dht_temp[] = $val;
            }
        }
    }
    else
        break;
}


$max_h = floor(($max_h+5)/5.0)*5 ;   // round up
$min_h = floor(($min_h-2.5)/5.0)*5 ; // round down

$max_t = floor(($max_t+5)/5.0)*5 ;   // round up
$min_t = floor(($min_t-2.5)/5.0)*5 ; // round down

// Berechne x-Skala
$mintime = $start_time;
$maxtime = $time[count($time)-1];
$maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00

// Create array for the sunrise / sunset background colour
$time_ss  = array();
$ydata_ss = array();
generate_sunrise_sunset_data($mintime,$maxtime,$min_h,$max_h, $days_list, $time_ss, $ydata_ss);



// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);

// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen

$graph->SetScale('datlin',$min_h, $max_h, $mintime, $maxtime); 
$graph->SetY2Scale('lin', $min_t, $max_t); 

$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days
if($diff_time <= 1)

//Titel
    $graph->title->Set("Temperatur und Luftfeuchte ".$today);	// Titel der Grafik
else
    $graph->title->Set("Temperatur und Luftfeuchte ".$today." bis ".$bistag);

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
$graph->yaxis->title->Set("Luftfeuchte [%]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 

// Create the linear plot
$lineplot_dht17_h=new LinePlot($dht_hum, $time);

//$lineplot_dht17_h->SetColor('#000000');
$graph->img->SetAntiAliasing(false);

// Add the plot to the graph
$graph->Add($lineplot_dht17_h);
$lineplot_dht17_h->SetColor("blue");
$lineplot_dht17_h->SetWeight(2); 
$lineplot_dht17_h->SetLegend("Sensor ".$name." hum.");

//$lineplot_dht17_h->mark->SetType(MARK_UTRIANGLE);
//$lineplot_dht17_h->mark->SetColor('darkblue');
//$lineplot_dht17_h->mark->SetFillColor('blue');

$lineplot_dht_t = new LinePlot($dht_temp, $time);
$graph->AddY2($lineplot_dht_t);
$lineplot_dht_t->SetColor("orange");
$lineplot_dht_t->SetWeight(2); 
$lineplot_dht_t->SetLegend("Sensor ".$name." degC");
//$lineplot_dht_t->SetStyle('dotted');

/*
$lineplot_dht27_h = new LinePlot($dht_27_hum, $time);
$graph->Add($lineplot_dht27_h);
$lineplot_dht27_h->SetColor('darkblue');
$lineplot_dht27_h->SetWeight(2);
$lineplot_dht27_h->SetLegend("27");
 
$lineplot_dht27_t = new LinePlot($dht_27_temp, $time);
//$graph->AddY2($lineplot_dht27_t);
$lineplot_dht27_t->SetColor("blue");
$lineplot_dht27_t->SetWeight(2); 
$lineplot_dht27_t->SetLegend("27 degC");
*/
 
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

// Display the graph
$graph->Stroke();

?>
