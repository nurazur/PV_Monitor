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

$debug_file = "../debug_sunezy.txt";
$sensor = "";

$asked_month = get_option_month($_GET);
$asked_year =  get_option_year ($_GET);

// determine first day and its time stamp
$today = get_option_day_first($_GET, $asked_month, $asked_year);
$start_time = strtotime($today);

// determine last day and its time stamp
$stop_time = get_option_time_last($_GET, $asked_month, $asked_year, $today);
$bistag = date('d.m.Y', $stop_time);

// custom plot width
$stretch =  plot_width($_GET);

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

// default y-axis scale
$max = 0;
$min = 0;

$v_sunezy = array();

$last_date = "";
$make_gap = false;

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
            $ydata[] =  '';
            $ydata_max[] = '';
            $ydata_min[] =  '';
            $time[] = $c_time;
            $make_gap = false;
        }
        if ($zeile[4] > $max) $max = $zeile[4];
        $ydata[] = $zeile[4]; 
        if (count($zeile) > 14)
        {
            $ydata_max[] = $zeile[14];
            $ydata_min[] = rtrim($zeile[15]);
            if ($zeile[14] > $max) $max = $zeile[14];
        }
    }
    else
        break;
}


// Berechne Y-Skala
$max  = $max - ($max % 200) + 200;
$tick_pos = array();
for ($i=0; $i<= $max; $i+=200)
{
    $tick_pos[] = $i;
}


// Berechne x- Skala
if (count ($days_list) <=1)
{
    $mintime = strtotime("+5 hours",  $start_time); // should be save for daylight saving time!
    $maxtime = strtotime("+22 hours", $start_time);
}
else
{
    $mintime = $start_time;
    $maxtime = $time[count($time)-1];
    $maxtime =  strtotime( "+1 day", strtotime(date('d.m.Y', $maxtime))); // its the next day 00:00
}


// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);


// Grafik formatieren
$graph->SetMargin(60,40,20,50);  // Rahmen
$graph->SetScale('datlin',0,$max, $mintime, $maxtime); 


//Titel
$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days

if($diff_time <= 1)
    $graph->title->Set("Leistungsverlauf ".$today);	// Titel der Grafik
else
    $graph->title->Set("Leistungsverlauf ".$today." bis ".$bistag);

    $graph->title->SetFont(FF_FONT2,FS_BOLD);


// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');


// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);

// X- Achse
graph_scale_xaxis($graph, $time, $diff_time); // same for all sunezy_xxx plots!


//Y-Achse
$graph->yaxis->title->Set("Leistung Pac [Watt]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 
$graph->yaxis->SetMajTickPositions($tick_pos);
$graph->yaxis->SetWeight(2);
$graph->img->SetAntiAliasing(false);

// Create the linear plot
$do_avg = true;

if ( isset($_GET["avg"]) && empty($_GET["avg"]) ) 
{
    $do_avg = false;
} 

if ($do_avg)
{
    $lineplot=new LinePlot($ydata, $time);
    $graph->Add($lineplot);
    $lineplot->SetColor("darkblue");
    $lineplot->SetWeight(1); 
    $lineplot->mark->SetType(MARK_UTRIANGLE);
    $lineplot->mark->SetColor('darkblue');
    $lineplot->mark->SetFillColor('blue');
}

if (count ($ydata_max) >0 && !empty($_GET["max"]))
{
    $lineplot_max=new LinePlot($ydata_max, $time);
    $graph->Add($lineplot_max);
    $lineplot_max->SetColor('red');
    $lineplot_max->SetWeight(1); 
}


if (count ($ydata_min) >0 && !empty($_GET["min"]))
{
    $lineplot_min=new LinePlot($ydata_min, $time);
    $graph->Add($lineplot_min);
    $lineplot_min->SetColor('red');
    $lineplot_min->SetWeight(1); 
}

// Create array for the sunrise / sunset background colour
if (plot_show_night_colour($_GET))
{
    $time_ss  = array();
    $ydata_ss = array();
    generate_sunrise_sunset_data($mintime,$maxtime,$min,$max, $days_list, $time_ss, $ydata_ss);
    $lineplot_ss = new LinePlot($ydata_ss, $time_ss);
    $lineplot_ss->SetFillColor('blue@0.8'); 
    $graph->Add($lineplot_ss);
    $lineplot_ss->SetColor('#FFFFA0@0.95');
}

// Display the graph
$graph->Stroke();

?>