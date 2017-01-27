<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once( "jpgraph/jpgraph_date.php" );

require_once 'common/common.php';



$php_time_start = microtime_float();
$debug_file = "../debug_bmp180.txt";

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
$list = generate_list_sensor($start_time, $stop_time);
$lzi = count($list);

// Y-axis boundaries
$max =0;
$min  = 2000;

// arrays
// pressure normalised to sea level
$pressure_nn = array();
// x-Axis array
$time = array();
// list of days in the time period requested, in time stamp format
$days_list = array();


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
        $pressure_nn_corrected[] = get_corrected_pressure($zeile[4], $c_time);// correct the 12h periodic pressure change
        $pressure_nn[] = $zeile[4];
        if ($zeile[4] > $max) $max = $zeile[4];
        if ($zeile[4] < $min) $min = $zeile[4];
        $t_mess[] =  rtrim($zeile[6]);

        $press_len = count($pressure_nn_corrected);
        if ( $press_len >= 3)
        {
            $tendency_1h[] = $pressure_nn_corrected[$press_len-1] - ($pressure_nn_corrected[$press_len-3] + $pressure_nn_corrected[$press_len-2])/2;
        }
        else if ($k >=2)
        {
            //
            $z_back2 = splitline(',', $list[$k-2]);
            $z_back1 = splitline(',', $list[$k-1]);
            $p1 = get_corrected_pressure($z_back1[4], strtotime($z_back1[3]) );
            $p2 = get_corrected_pressure($z_back2[4], strtotime($z_back2[3]) );
        
            $tendency_1h[] = $pressure_nn_corrected[$press_len -1] -($p1+$p1)/2;
        }
        else
            $tendency_1h[] =0; // temporarily. later look in last week's file.

        // calculate 3 hour tendency
        if ( $press_len > 6)
        {
            $tendency_3h[] = $pressure_nn_corrected[$press_len-1] - $pressure_nn_corrected[$press_len-7];
        }
        else if ($k >=6)
        {
            $z_back1 = splitline(',', $list[$k-6]);
            $p1 = get_corrected_pressure($z_back1[4], strtotime($z_back1[3]) );
            $tendency_3h[] = $pressure_nn_corrected[$press_len -1] - $p1;
        }
        else
            $tendency_3h[] =0; 
    }
    else
        break;
}



// Berechne Tendenz: -1 = fallend 0= gleich bleibend 1= steigend
$tendency = array();
for ($i=0; $i< count($tendency_1h); $i++)
{
    if (($tendency_1h[$i] > 0.33 && $tendency_3h[$i] > 1.33)  || $tendency_3h[$i] > 1.66 ) 
        $tend = 1; 
    else if (($tendency_1h[$i] < -0.33 && $tendency_3h[$i] < -1.33)  || $tendency_3h[$i] < -1.66)
        $tend = -1;
    else
        $tend = 0;
       
    $cnt = count($tendency)-1;
    
    if ($cnt >=0)
    {
        //if ($tendency[$cnt] == 1 && $tendency_1h[$i] >= 0.0 && $tendency_3h[$i] > 0.0)
        if ($tendency[$cnt] == 1 && $tendency_3h[$i] > 0.0)
        {
            $tend = 1;
        }
        //else if ($tendency[$cnt] == -1 && $tendency_1h[$i] <= 0.0 && $tendency_3h[$i] < 0.0)
        else if ($tendency[$cnt] == -1  && $tendency_3h[$i] < 0.0)
        {
            $tend = -1;
        }
    }
    
    if ($cnt >=0 && $tend != $tendency[$cnt])
    {
        $time_t[] = $time[$i];
        $tendency[] = $tendency[$cnt];
    }

    $time_t[] = $time[$i];
    $tendency[] = $tend;
}



// Berechne Y-Skala
$mintime = $start_time;
$maxtime = $time[count($time)-1];
$maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00

// Range / Spanne
$delta = ($max - $min);
   
// center:
$center = ($max + $min) / 2.0;
$center = floor(($center + 2.5)/5.0) * 5;
    

// preferred y-axis range and max/min
if($min >995 && $max <= 1035)
{
    // want to center around 1015
    $min= 990;
    $max = 1035;
}

// if this don't work, try to keep preferred range with different max / min values
else if ($delta < 45) 
{
    $delta = 45;
    if ($min < 995)
    {
        $min = floor(($min-2.5)/5.0)*5 ; // round down
        $max = $min + 45;
    }
    else if ($max > 1035)
    {
        $max = floor(($max+5)/5.0)*5 ;   // round up
        $min = $max - 45;
    }

}
else
{
    $max = floor(($max+5)/5.0)*5 ;   // round up
    $min = floor(($min-2.5)/5.0)*5 ; // round down
}



$time_ss  = array();
$ydata_ss = array();
generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, $time_ss, $ydata_ss);


$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days

// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);
    
// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen
$graph->SetScale('datlin',$min,$max, $mintime, $maxtime); 

if(isset($_GET["show_t"]))
{
    $graph->SetY2Scale('lin', 10, 40);
}
else
{
    $graph->SetY2Scale('lin', -2.5, 2);
}
//Titel
if($diff_time <= 1)
    $graph->title->Set("Luftdruck hPa ".$today);	// Titel der Grafik
else
    $graph->title->Set("Luftdruck hPa ".$today." bis ".$bistag);
    
$graph->title->SetFont(FF_FONT2,FS_BOLD);

// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');

// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);


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
$graph->xaxis->SetLabelAngle(45); 
$graph->xaxis->SetTickLabels($time);
$graph->xaxis->HideFirstTicklabel() ;

$graph->xgrid->Show(true);
$graph->xgrid->SetLineStyle('dashed');

//Y-Achse
$graph->yaxis->title->Set("Luftdruck normalisiert [hPa]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 

// Create the linear plot
$lineplot=new LinePlot($pressure_nn, $time);

//$lineplot->SetColor('#000000');
$graph->img->SetAntiAliasing(false);

// Add the plot to the graph

//$graph->Add($lineplot);
$lineplot->SetColor("darkblue");
$lineplot->SetWeight(3); 
$lineplot->SetLegend("Luftdruck");

//$lineplot->mark->SetType(MARK_UTRIANGLE);
//$lineplot->mark->SetColor('darkblue');
//$lineplot->mark->SetFillColor('blue');

// Create the linear plot

// for debugging only
$lineplot_c=new LinePlot($pressure_nn_corrected , $time);
$graph->Add($lineplot_c);
//$lineplot_c->SetColor("cadetblue1");
$lineplot_c->SetColor("darkblue");
$lineplot_c->SetWeight(2); 
$lineplot_c->SetLegend("Luftdruck (corr.)");


if(isset($_GET["show_t"]))
{
    //$graph->SetY2Scale('lin', 10, 40);
    $lineplot_aussen_n = new LinePlot($t_mess, $time);
    //$graph->Add($lineplot_aussen_n);
    $graph->AddY2($lineplot_aussen_n); 
    $lineplot_aussen_n->SetColor('red');
    $lineplot_aussen_n->SetWeight(2);
    $lineplot_aussen_n->SetLegend("T bei Messung [deg C]");
}
else
{
    $lineplot_tendency_1h = new LinePlot($tendency_1h, $time);
    $graph->AddY2($lineplot_tendency_1h); 
    $lineplot_tendency_1h->SetColor('red');
    $lineplot_tendency_1h->SetWeight(1);
    $lineplot_tendency_1h->SetLegend("Tendenz (1 Stunde)");
    
    $lineplot_tendency_3h = new LinePlot($tendency_3h, $time);
    $graph->AddY2($lineplot_tendency_3h); 
    $lineplot_tendency_3h->SetColor('green');
    $lineplot_tendency_3h->SetWeight(1);
    $lineplot_tendency_3h->SetLegend("Tendenz (3 Stunden)");
    
    $lineplot_tendency = new LinePlot($tendency, $time_t);
    $graph->AddY2($lineplot_tendency); 
    $lineplot_tendency->SetColor('orange');
    $lineplot_tendency->SetWeight(3);
    $lineplot_tendency->SetLegend("Tendenz");
}

$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

//Legende
$graph->legend->Pos(0.5,0.75,"center","bottom");
$graph->legend->SetLayout(LEGEND_HOR); 

if ($do_sunrs)
{
    // plot sunrise / sunset time zones
    $lineplot_ss = new LinePlot($ydata_ss, $time_ss);
    $lineplot_ss->SetFillColor('blue@0.8'); 
    $graph->Add($lineplot_ss);
    $lineplot_ss->SetColor('#FFFFA0@0.95');
}

// Display the graph
$graph->Stroke();


//measure time elapsed
$php_time_end = microtime_float();
$php_runtime = $php_time_end - $php_time_start;

//errorlog("../runtime.txt", $php_runtime."s\n");

?>
