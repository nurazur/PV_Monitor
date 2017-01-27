<?php // content="text/plain; charset=utf-8"

// no-cache headers - complete set
// these copied from [php.net/header][1], tested myself - works
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); 

require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once( "jpgraph/jpgraph_date.php" );
require_once 'common/common.php';

$debug_file = "../debug_volt.txt";
$sensor = "";

$asked_month = get_option_month($_GET);
$asked_year =  get_option_year ($_GET);

// determine first day and its time stamp
$today = get_option_day_first($_GET, $asked_month, $asked_year);
$start_time = strtotime($today);

// determine last day and its time stamp
$stop_time = get_option_time_last($_GET, $asked_month, $asked_year, $today);
$bistag = date('d.m.Y', $stop_time);

// shall we plot nights in blue colour?
$do_sunrs = plot_show_night_colour($_GET);

// custom plot width
$stretch =  plot_width($_GET);

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

// default y-axis scale
$max = 0;
$min = -85;

$t_sunezy = array();

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

$last_date = "";
$make_gap = false;

/*
$c_week = date("W");
$c_year = date("Y");

$asked_week = $c_week;
$asked_year = $c_year;



if (!empty($post["tag"]))   // mit Argumenten aufgerufen
{ 
    $today = $post["tag"];
    $today_time = strtotime($today);
    $asked_week = date("W", $today_time); 
    $asked_year = date("Y", $today_time);
}
else
{ 
    $today = date("d.m.Y");
    $today_time = strtotime($today);
}



// find the file where the data to plot are:
$w = calc_python_week($today) + 1;
if ($w >9)
    $logfilename = "../logfiles/".$w."_".$asked_year."_log.csv";
else
    $logfilename = "../logfiles/0".$w."_".$asked_year."_log.csv";

*/
/*
if (file_exists($logfilename))
{
    $list=file($logfilename);                //lese Datei in Array $list (1 Element pro Zeile)
    $lzi = count($list);
    $i=$lzi-1;

    for ($k=0; $k<$lzi; $k++)
    {
        $zeile=splitline(' ', $list[$k]);
        if (strtotime($zeile[2]) == $today_time) break;
    }
    if ($k < $lzi)
    {
        do
        {
            $zeile=splitline(' ', $list[$k]);
            if(strtotime($zeile[2]) == $today_time)
            {
                $time[] = strtotime($zeile[3]);
                if ($zeile[13] > $max) $max = $zeile[13];
                $t_sunezy[] = $zeile[12];
                if ($zeile[12] > $max) $max = $zeile[12];
                $t_raspi[] =  rtrim($zeile[13]);
                if (count($zeile) >16)
                {
                    $fan_state[] = rtrim($zeile[16]);
                }
            }
            else
                break;
            $k++;
        } while($k < $lzi);  
    }
}   
*/

for ($k=0; $k<$lzi; $k++)
{
    $zeile=splitline(' ', $list[$k]);
    if (count($zeile) < 8 )
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
            $make_gap = true; // Insert a data hole
        }
        
        $c_time = strtotime($zeile[2]." ".$zeile[3]);
        $time[] = $c_time;
        // avoid that data points are connected from a previous day to the new day.
        if ($make_gap)
        {
            $t_sunezy[] = '';
            $t_raspi[] =  '';
            $time[] = $c_time;
            if (count($zeile) >16)
            {
                $fan_state[] = rtrim($zeile[16]);
            }
            $make_gap = false;
        }
        if ($zeile[13] > $max) $max = $zeile[13];
        $t_raspi[] = $zeile[13];
        if ($zeile[12] > $max) $max = $zeile[12];
        $t_sunezy[] =  rtrim($zeile[12]);
        if (count($zeile) >16)
        {
            $fan_state[] = rtrim($zeile[16]);
        }
    }
    else
        break;
}




// Berechne Y-Skala
$grid_res = 5;
$max  = (int)($max - ($max % $grid_res) + $grid_res);
$min = 20;

$tick_pos = array();
for ($i=$min; $i<= $max; $i+=$grid_res)
{
    $tick_pos[] = $i;
}


// Berechne x- Skala
if (count ($days_list) <=1)
{
    $mintime = $start_time + 5*3600; // could be a problem when the daylight saving time comes in!
    $maxtime = $start_time + 22*3600;
}
else
{
    $mintime = $start_time;
    $maxtime = $time[count($time)-1];
    $maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00
}



// Create array for the sunrise / sunset background colour
if ($do_sunrs)
{
    $time_ss  = array();
    $ydata_ss = array();
    generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, $time_ss, $ydata_ss);
}


// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);

// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen


$graph->SetScale('datlin',$min,$max, $mintime, $maxtime);
if (count($fan_state) >0) { $graph->SetY2Scale('lin', -2, 10);}

//Titel
$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days
if($diff_time <= 1)
    $graph->title->Set("Sunezy System Temperatur ".$today);	// Titel der Grafik
else
    $graph->title->Set("Sunezy System Temperatur ".$today." bis ".$bistag);

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

// Show grid
$graph->xgrid->Show(true);
$graph->xgrid->SetLineStyle('dashed');


//Y-Achse
$graph->yaxis->title->Set("Temperatur [deg C]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 
$graph->yaxis->SetMajTickPositions($tick_pos);

$graph->img->SetAntiAliasing(false);


// Create the linear plot
$lineplot=new LinePlot($t_sunezy, $time);
$graph->Add($lineplot);
$lineplot->SetColor("darkblue");
$lineplot->SetWeight(2); 
$lineplot->SetLegend("Sunezy");


$lineplot_raspi = new LinePlot($t_raspi, $time);
$graph->Add($lineplot_raspi);
$lineplot_raspi->SetColor('red');
$lineplot_raspi->SetWeight(2);
$lineplot_raspi->SetLegend("Raspberry");


if (count($fan_state) > 0)
{
    $lineplot_fan = new LinePlot($fan_state, $time);
     
    $graph->AddY2($lineplot_fan); 
    $lineplot_fan->SetColor('orange');
    $lineplot_fan->SetWeight(2);
    $lineplot_fan->SetLegend("Luefter Status"); 
    $lineplot_fan->SetStepStyle(true);
    $graph->y2axis->HideTicks(false,true);
    $graph->y2axis->HideFirstTicklabel();
}


$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  



//Legende
$graph->legend->Pos(0.5,0.75,"center","bottom");
$graph->legend->SetLayout(LEGEND_HOR); 

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
