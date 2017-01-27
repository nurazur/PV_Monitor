<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
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


/** parameters:
* tage   Anzahl der Tage die angezeigt werden sollen, gezaehlt von heute zurueck. Wenn der Parameter fehlt oder < 1 ist, wird alles angezeigt
* monat  Monat der angezeigt werden soll. Default: 0 (parameter wird ignoriert)
* sl_avg Anzahl der zurueckliegenden Tage aus denen der gleitende Mittelwert berechnet wird. Wenn sl_avg < 1, dann wird der Mittelwert nicht erzeugt. Default: 10 Tage
* bar    Bar plot kann ausgeblendet werden wenn dieser Wert < 0 ist.
* von    Datum ab dem geplottet werden soll. Wenn weggelassen oder leer, wird 1.1.2000 angenommen.
* bis    Datum bis zu dem geplottet werden soll. Wenn wegelassen oder leer, wird "heute" angenommen
*/

$post = array();
if ($_GET)
{
    foreach ($_GET as $varname => $varvalue)
    {
        if (!empty($varvalue)) 
        {
            $post[$varname] = $varvalue;
        }
    }
}




// Open Daily Log File
$list=file("../logfiles/sunezy_daily_log.txt");                //lese Datei in Array $list (1 Element pro Zeile)
$lzi = count($list);

// extract header - wird nicht benutzt
/*
$header =splitline(' ', $list[0]);
$header_count = count($header);
*/

// extract "tage"
$num_days = 1;
if (!empty($post["tage"]))
{
	$num_days = $lzi - $post["tage"];
	if ($num_days <1 ) $num_days = 1;
}


// extract "sl_avg"
$sliding_avg = 10;
if (!empty($post["sl_avg"]))
{
	$sliding_avg = $post["sl_avg"];
}

// extract "monat"
$monat = 0;
if (!empty($post["monat"]))
{
	$monat = $post["monat"];
}
if ($monat < 1 || $monat > 12) $monat =0;

// extract "bar"
$do_bar_plot =1;
if (!empty($post["bar"]))
{
	$do_bar_plot = $post["bar"];
}


// extract "von" (Datum)
$von_datum = strtotime("1.1.2000");
if (!empty($post["von"]))
{
	$von_datum = strtotime($post["von"]);
}

// extract "bis" (Datum)
$bis_datum = time();
if (!empty($post["bis"]))
{
	$bis_datum = strtotime($post["bis"]);
}


// Parse log file
for ($i=$num_days; $i<$lzi; $i++)
{
    $zeile = splitline(' ', $list[$i]);
    
	
	$timestamp = strtotime($zeile[0]);
	$datearray = getdate($timestamp);
	if ($monat >0)
	{
		$m = $datearray['mon'];
		if ($m != $monat) continue;
	}
	if($timestamp < $von_datum) continue;
	if($timestamp > $bis_datum) continue;
	
	$datum[] = date('d.m', $timestamp);
	$ydata[] = rtrim($zeile[5]);
	if ($sliding_avg > 0)
	{
		$avg =0;
		if ($i < $sliding_avg)
		{
			for ($j=1; $j <=$i; $j++)
			{
				$zeile = splitline(' ', $list[$j]);
				$avg += rtrim($zeile[5]);
			}
			$avg = $avg / $i;
		}
		else
		{
			for ($j=$i-$sliding_avg+1; $j <= $i; $j++)
			{
				if ($j < 1) continue;
				$zeile = splitline(' ', $list[$j]);
				$avg  += rtrim($zeile[5]);
			}
			$avg /= $sliding_avg;
		}
		$ydata_avg[] = $avg;
	}
	
}


// Create the graph. These two calls are always required
$graph = new Graph(960,540);
//$graph->SetFrame(true,'black',0);


// Grafik formatieren
$graph->SetMargin(40,40,20,80);  // Rahmen
$graph->SetScale('datlin',0,20); //  autoscale

//Titel
$graph->title->Set("Energieproduktion pro Tag");	// Titel der Grafik
$graph->title->SetFont(FF_FONT2,FS_BOLD);

// weisser Hintergrund
$graph->ygrid->SetFill(true,'#FFFFFF@0.5','#FFFFFF@0.5');

// Gradient fill
$graph->SetBackgroundGradient('#FFFFA0', '#FFFFFF', GRAD_HOR, BGRAD_PLOT);
$graph->SetFrame(true,'darkblue',0); 

// X- Achse
//$graph->xaxis->title->Set("Tag");
$graph->xaxis->SetLabelAngle(90); 
$graph->xaxis->SetTickLabels($datum);
//$graph->xaxis->SetLabelFormatString('d, m', true); // d,M / d,m,y / d,m,Y / H:i:s
$graph->xaxis->SetTextLabelInterval(1);

//$graph->xaxis->scale->ticks->Set(1); 
$graph->xgrid->Show(false);
//$graph->xgrid->SetLineStyle('dashed');

//Y-Achse
$graph->yaxis->title->Set("Produktion [kWh]");


$graph->img->SetAntiAliasing(false);

// Add the plot to the graph


if($do_bar_plot >0)
{
	$barplot=new BarPlot($ydata);
	$graph->Add($barplot);
	$barplot->SetColor("darkblue");
	//$barplot->SetFillColor('lightblue');
}


// Add sliding average plot
if ($sliding_avg > 0)
{
	$lineplot=new LinePlot($ydata_avg);
	$lineplot->SetStepStyle(); 
	//$lineplot->SetColor('#000000');
	$graph->Add($lineplot);

	$lineplot->SetColor('orange');
	//$lineplot->SetFillColor('orange@0.6');
	//$lineplot->SetWeight(2); 
	//$lineplot->mark->SetType(MARK_UTRIANGLE);
	//$lineplot->mark->SetColor('darkblue');
	//$lineplot->mark->SetFillColor('blue');
}

$graph->yaxis->SetWeight(2);
$graph->xaxis->SetWeight(2);  

// Display the graph
$graph->Stroke();
?>
	