<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
//require_once ('jpgraph/jpgraph_line.php');
require_once ('jpgraph/jpgraph_bar.php');
require_once( "jpgraph/jpgraph_date.php" );

// function splitline is same a explode, but works like a string tokenizer (empty strings are not expanded)
function splitline($sep, $string)
{
	$tok = strtok($string, $sep);
	$arr = array();
	while ($tok !== false) 
	{
		array_push($arr, $tok);
		$tok = strtok($sep);
	}
	return $arr;
}

// Parse Weekly Log File
$list=file("../logfiles/sunezy_weekly_log.txt");                //lese Datei in Array $list (1 Element pro Zeile)
$lzi = count($list);

$header =splitline(' ', $list[0]);
$header_count = count($header);

for ($i=1; $i<$lzi; $i++)
{
    $zeile = splitline(' ', $list[$i]);
    $ydata[] = rtrim($zeile[5]);
	$datum[] = date('W.Y', strtotime($zeile[0]));
}

// Create the graph. These two calls are always required
$graph = new Graph(600,338);

// Grafik formatieren
$graph->SetMargin(40,40,20,80);  // Rahmen

$graph->SetScale('datlin'); // y-Achse autoscale
//Titel
$graph->title->Set("Energieproduktion pro Woche");	// Titel der Grafik
$graph->title->SetFont(FF_FONT2,FS_BOLD);

// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');

// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);
$graph->SetFrame(true,'darkblue',0); 

// X- Achse
$graph->xaxis->SetLabelAngle(90); 
$graph->xaxis->SetTickLabels($datum);
$graph->xaxis->SetTextLabelInterval(1);

$graph->xgrid->Show(false);

//Y-Achse
$graph->yaxis->title->Set("Produktion [kWh]");


// Create the linear plot
$lineplot=new BarPlot($ydata);
$lineplot->SetFillColor('orange@0.5');

$graph->img->SetAntiAliasing(false);

// Add the plot to the graph
$graph->Add($lineplot);

$lineplot->SetColor('#000000');
$lineplot->SetFillColor('orange@0.5');

$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

// Display the graph
$graph->Stroke();
?>
	