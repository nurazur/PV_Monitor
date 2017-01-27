<?php // content="text/plain; charset=utf-8"
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

// custom plot width
$stretch =  plot_width($_GET);

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

// default y-axis scale
$max = 0;
$min = 49;

$v_sunezy = array();

// Generate List of all Entries
$list = generate_list_sensor($start_time, $stop_time, $sensor);
$lzi = count($list);

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
            $v_sunezy[] = '';
            $v_grid[] =  '';
            $time[] = $c_time;
            $make_gap = false;
        }
        if ($zeile[7] > $max) $max = $zeile[7];
        $v_sunezy[] = $zeile[7];
        $v_grid[] =  rtrim($zeile[5]);
    }
    else
        break;
}


// Berechne Y-Skala
$max  = $max - ($max % 50) + 50;

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





// Create the graph. These two calls are always required
if($stretch <= 600) $stretch = 600;
$graph = new Graph($stretch,338);

// Grafik formatieren
$graph->SetMargin(60,40,0,50);  // Rahmen
$graph->SetScale('datlin',50,$max, $mintime, $maxtime); 
$graph->SetY2Scale('lin', 220, 250); 

//Titel
$diff_time = ($stop_time - $start_time) / 86400.0; // number of displayed days

if($diff_time <= 1)
    $graph->title->Set("Sunezy System Voltages ".$today);	// Titel der Grafik
else
    $graph->title->Set("Sunezy System Voltages ".$today." bis ".$bistag);

$graph->title->SetFont(FF_FONT2,FS_BOLD);


// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');


// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);

// X- Achse
graph_scale_xaxis($graph, $time, $diff_time);

//Y-Achse
$graph->yaxis->title->Set("Voltage [V rms]");
$graph->yaxis->SetTitlemargin(40); 
$graph->yaxis->SetLabelMargin(10); 
$graph->img->SetAntiAliasing(false);

// Add the plot to the graph
$lineplot=new LinePlot($v_sunezy, $time);
$graph->Add($lineplot);
$lineplot->SetColor("darkblue");
$lineplot->SetWeight(2); 
$lineplot->SetLegend("V-Sunezy");

//$lineplot->mark->SetType(MARK_UTRIANGLE);
//$lineplot->mark->SetColor('darkblue');
//$lineplot->mark->SetFillColor('blue');


$lineplov_grid = new LinePlot($v_grid, $time);
//$lineplov_grid->SetFillColor('blue@0.8'); 

//$graph->Add($lineplov_grid);
$graph->AddY2($lineplov_grid); 
$lineplov_grid->SetColor('red');
$lineplov_grid->SetWeight(2);
$lineplov_grid->SetLegend("V-grid");
 
$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

//Legende
$graph->legend->Pos(0.5,0.75,"center","bottom");
$graph->legend->SetLayout(LEGEND_HOR); 


// plot sunrise / sunset time zones
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