<html><head><title>sunezy</title>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script type="text/javascript">
    $("#ajaxloadlink").click(function()
    {
         $("#ajaxcontent").load("ajax.php");
    });
    
    function fill_the_arrays()
    {
        $.ajax(
        {
            type: "POST",
            url: "ajax_1.php",
            cache: false,
            dataType: "json",
            success: function(data){
                $("#SonnenAufgang").html(data.SonnenAufgang);
                $("#SonnenUntergang").html(data.SonnenUntergang);
                $("#SummeHeute").html(data.SummeHeute);
                $("#LastLog").html(data.LastLog);
            }
        });
    }
</script>
<script type="text/javascript">
    function NeuerVerlauf(anz_tage)
    {
		var dt = document.forms["Eingabe"].neues_datum.value.split(".");
		var datum = new Date(dt[2], dt[1]-1, dt[0]); // Monate werden in Javascript von 0 bis 11 gezaehlt
		var dts = datum.getTime() + anz_tage* 24*3600*1000; // mache Datum einen Tag vorher oder spaeter
  		var newdate = new Date(dts);
		var cjour= newdate.getDate()+ '.' + (newdate.getMonth()+1) + '.' + newdate.getFullYear() ;

		document.forms["Eingabe"].neues_datum.value = cjour;
		
		if(self.document.getElementsByName("plot1")[0].checked ==true)
		{	
            var max
            var min
            var avg
            if (self.document.getElementsByName("maxplot")[0].checked ==true)
            {
                max = '&max=1'
            }
            else
            {
                max = '&max=0'
            }
            if (self.document.getElementsByName("avgplot")[0].checked ==true)
            {
                avg = '&avg=1'
            }
            else
            {
                avg = '&avg=0'
            }
            if (self.document.getElementsByName("minplot")[0].checked ==true)
            {
                min = '&min=1'
            }
            else
            {
                min = '&min=0'
            }
			self.document.getElementById("verlauf").src = './graph/sunezy_today_plot.php?tag=' + cjour + max + avg + min + '&' + new Date().getTime();
			self.document.getElementById("verlauf").style.visibility="visible";
			self.document.getElementById("leistung_zeile").style.visibility="visible"; 
		}
		else
		{
			self.document.getElementById("verlauf").src = "space.gif";
            //self.document.getElementById("verlauf").src = "";
			self.document.getElementById("verlauf").style.visibility="hidden";
			self.document.getElementById("leistung_zeile").style.visibility="collapse";
		}
		
		if(self.document.getElementsByName("plot2")[0].checked ==true)
		{		
			self.document.getElementById("temperatures").src = './graph/sunezy_today_temp.php?tag=' + cjour + '#' + new Date().getTime();
			self.document.getElementById("temperatures").style.visibility="visible";
			self.document.getElementById("temperatur_zeile").style.visibility="visible";
		}
        else
		{
			self.document.getElementById("temperatures").src = 'space.gif';
			self.document.getElementById("temperatures").style.visibility="hidden";
			self.document.getElementById("temperatur_zeile").style.visibility="collapse";
		}

        if(self.document.getElementsByName("plot3")[0].checked ==true)
		{		
			self.document.getElementById("voltages").src = './graph/sunezy_today_volt.php?tag=' + '#' + new Date().getTime();
			self.document.getElementById("voltages").style.visibility="visible";
			self.document.getElementById("spannung_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("voltages").src = 'space.gif';
			self.document.getElementById("voltages").style.visibility="hidden";
			self.document.getElementById("spannung_zeile").style.visibility="collapse";
		}
        
        if(self.document.getElementsByName("plot4")[0].checked ==true)
		{		
			self.document.getElementById("sensors").src = './graph/ds18b20_plot.php?tag=' + cjour  + '&' + new Date().getTime();
			self.document.getElementById("sensors").style.visibility="visible";
			self.document.getElementById("sensor_zeile").style.visibility="visible";
		}
		else
		{
			self.document.getElementById("sensors").src = 'space.gif';
			self.document.getElementById("sensors").style.visibility="hidden";
			self.document.getElementById("sensor_zeile").style.visibility="collapse";
		}
        return;
    }

    function energy_per_time(span)
    {
        if (span == 1)
        {
            self.document.getElementById("production_over_time").src = './graph/daily_plot.php' ;
        }
        else if (span ==7)
        {
            self.document.getElementById("production_over_time").src = './graph/weekly_plot.php' ;
        }
    }
	
	function energy_per_day ( plottype, tage, von, bis, monat, do_sl_avg, sl_avg, do_bar )
	{
		var str_sl_avg = "";
		var bars =""
		var str_cmdline = './graph/daily_plot.php?'
		
		if (do_sl_avg)
		{
			str_sl_avg = 'sl_avg=' + sl_avg;			
		}
		else
		{
			str_sl_avg ='sl_avg=-1'
		}
		
		if (!do_bar)
		{
			bars='&bar=-1';
		}
		var str_cmdline = './graph/daily_plot.php?' + str_sl_avg + bars;
		
		if(plottype ==0) // tage
		{
			self.document.getElementById("production_over_time").src = str_cmdline + '&tage=' + tage;
		}
		else if  (plottype == 1) //von bis
		{
			self.document.getElementById("production_over_time").src = str_cmdline + '&von=' + von +'&bis=' + bis;
			//alert(str_cmdline + '&von=' + von +'&bis=' + bis);
		}
		else if (plottype == 2) // param1 = monat
		{
			self.document.getElementById("production_over_time").src = str_cmdline + '&monat=' + monat
		}
		else 
		{
		}
	}
    var TimerId;
    function reload_timer()
    {
        var jetzt = new Date();
        var jetzt_t = jetzt.getTime();
        var time_span = 300*1000;
        var interv = time_span  - (jetzt_t % time_span) +10000; // time from now to the next 5 minutes boundary plus 10 seconds
        
        //alert("Next reload in " + (interv/1000) + " s");
        TimerId = window.setInterval ("reload_now()", interv);
        return;
    }
    function reload_now()
    {
        window.clearInterval(TimerId);
        //alert("Reload page now");
        //window.location.reload();
        var jetzt = new Date();
        
        //self.document.getElementById("woche").innerHTML = jetzt.toDateString() + " " + jetzt.toTimeString();
        //self.document.getElementById("woche").innerHTML = <?php print("Woche" .date("W d-m-Y G:i:s  "));?>;
        $("#woche").load("ajax.php");
        fill_the_arrays();
        NeuerVerlauf(0);
        reload_timer();
    }
    
    
	
	function ChoiceDailyPlot (index)
{
	if (index == 0) // Tage zurueck
	{
		self.document.getElementById("TageZurueck").style.visibility="visible";
		self.document.getElementById("TageZurueck").style.display="inline";
		
		//self.document.getElementById("Zeitraum").style.visibility="hidden";
		self.document.getElementById("Zeitraum").style.display="none";
		
		//self.document.getElementById("EinMonat").style.visibility="hidden";
		self.document.getElementById("EinMonat").style.display="none";
	}
	else if (index == 1)
	{
		//self.document.getElementById("TageZurueck").style.visibility="hidden";
		self.document.getElementById("TageZurueck").style.display="none";
		
		self.document.getElementById("Zeitraum").style.visibility="visible";
		self.document.getElementById("Zeitraum").style.display="inline";
		
		//self.document.getElementById("EinMonat").style.dvisibility="hidden";
		self.document.getElementById("EinMonat").style.display="none";
	}
	else if (index == 2)
	{
		//self.document.getElementById("TageZurueck").style.visibility="hidden";
		self.document.getElementById("TageZurueck").style.display="none";
		
		//self.document.getElementById("Zeitraum").style.visibility="hidden";
		self.document.getElementById("Zeitraum").style.display="none";
		
		self.document.getElementById("EinMonat").style.visibility="visible";
		self.document.getElementById("EinMonat").style.display="inline";
	}
}

	
    
</script>
</head><body>

<?php // content="text/plain; charset=utf-8"


// split a line into an array. Aufeinanderfolgende tokens werden als ein einziger interpretiert.
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


$filename_daily_log = "./logfiles/sunezy_daily_log.txt";
$filename_last_log =  "../tmp/last_log.txt";

//$t = strtotime(date("d-m-Y"));
?>


<table border=2  rules=cols cellpadding=3 cellspacing=0 bgcolor = #FFFFE0>
<tr><td colspan =3 align =center ><h1>Sunezy Solaranlage</h1></td></tr>

<tr><td>Datum:</td>  <td id ="woche"></td>   <td rowspan =5 valign=top>
<script type="text/javascript"> $("#woche").load("ajax.php");</script>

<div id="cont_3e601318c90fec2d2d8c738e03652109">
  <span id="h_3e601318c90fec2d2d8c738e03652109"><a id="a_3e601318c90fec2d2d8c738e03652109" href="http://www.daswetter.com/wetter_Vallauris-Europa-Frankreich-Alpes+Maritimes--1-25753.html" target="_blank" style="color:#808080;font-family:Helvetica;font-size:14px;">Wetter Vallauris</a></span>
  <script type="text/javascript" src="http://www.daswetter.com/wid_loader/3e601318c90fec2d2d8c738e03652109"></script>
</div>
</td></tr>
<tr><td>Sonnenaufgang:  </td> <td id = "SonnenAufgang">    </td> </tr>
<tr><td>Sonnenuntergang:</td> <td id = "SonnenUntergang">  </td> </tr>
<tr><td>Produktion:     </td> <td id = "SummeHeute">       </td> </tr>
<?php
//$dst_offset = date_offset_get(new DateTime) /3600; // offset from UTC of today , normal European time + DST offset

//print("</td></tr>");
//print ("<tr><td>Sonnenaufgang:</td>   <td id =\"SonnenAufgang\>"    .date_sunrise($t, SUNFUNCS_RET_STRING, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset)."</td> </tr>");
//print ("<tr><td>Sonnenuntergang:</td> <td id = \"SonnenUntergang\">".date_sunset( $t, SUNFUNCS_RET_STRING, ini_get("date.default_latitude"), ini_get("date.default_longitude"), ini_get("date.sunrise_zenith"), $dst_offset)."</td>  </tr>");
//print ("<tr><td>Sonnenaufgang:</td>   <td id = \"SonnenAufgang\">    </td> </tr>");
//print ("<tr><td>Sonnenuntergang:</td> <td id = \"SonnenUntergang\">  </td> </tr>");

//print ("<tr><td>Produktion: </td><td>");

// Parse the last log
/*
$list0=file($filename_last_log);   //lese Datei in Array $list0 (1 Element pro Zeile)
$lzi0 = count($list0);
if ($lzi0 > 1)
{
	$zeile = splitline(' ', $list0[$lzi0-1]);
	$zaehler_heute = $zeile[9];
}
*/
// Parse Daily Log File
/*
$list=file($filename_daily_log);   //lese Datei in Array $list (1 Element pro Zeile)
$lzi = count($list);
if ($lzi > 1)
{
	$zeile = splitline(' ', $list[$lzi-1]);
	$zaehler_gestern = $zeile[3];
	$summe_heute = $zaehler_heute - $zaehler_gestern;
	//printf ("%.1f kWh", $summe_heute) ;
}
*/
//print("</td> </tr>");

?>


<tr>
<td valign = "top"><form name="Eingabe">
&nbsp<br>
<input type="checkbox" name="plot1" value="leistung" checked="checked">Leistung<br>
<table>
<tr>
<td> <input type="checkbox" name="maxplot" value ="max"> Max.</td>
<td> <input type="checkbox" name="avgplot" value ="avg" checked="checked"> Avg.</td>
<td> <input type="checkbox" name="minplot" value ="min"> Min.</td>
</tr>
</table>
<input type="checkbox" name="plot2" value="temperaturen" >Temperaturen&nbsp<br>
<input type="checkbox" name="plot4" value="temperaturen" >Sensoren<br>
<input type="checkbox" name="plot3" value="temperaturen" >Spannung<br><br>
&nbspDatum:&nbsp<br>
&nbsp<input type="text" name="neues_datum" size ="11" value =<?php echo date('d.m.Y')?> ><br>

<br>Navigation:<br>
<input type="button" value="<<" onclick="NeuerVerlauf(-7)">
<input type="button" value=" < " onclick="NeuerVerlauf(-1)">
<input type="button" value=" > " onclick="NeuerVerlauf(1)">
<input type="button" value=">>" onclick="NeuerVerlauf(7)">
<br>
<input type="button" value="        OK        " size = "10" onclick="NeuerVerlauf(0)">
</form></td> 

<td>
<table>
<tr id="leistung_zeile" style="visibility:visible"><td valign = "top"><img id="verlauf" src = "./graph/sunezy_today_plot.php"></img></td></tr>
<tr id="temperatur_zeile" style="visibility:visible"><td><img id="temperatures" src = "./space.gif" style="visibility:visible"></img></td></tr>
<tr id="sensor_zeile" style="visibility:visible"><td><img id="sensors" src = "./space.gif" style="visibility:visible"></img></td></tr>
<tr id="spannung_zeile" style="visibility:visible"><td><img id="voltages" src = "./space.gif" style="visibility:visible"></img></td></tr>
</table>
</td>

</tr>
</table>
<br>
<a href="javascript: window.location.reload()">Reload</a>
<br>
<div id="LastLog"></div>
<?php
/*
$header =splitline(' ', $list0[0]);
$header_count = count($header);
print("<table border=1>");
print("<tr><td colspan=".$header_count.">Letzte Aufzeichnung</td>");
for ($i=0; $i<count($list0); $i++)
{
	$zeile = splitline (' ', $list0[$i]);
	print("<tr>");
	for ($j=0; $j<count($zeile); $j++)
	{
		print("<td>".$zeile[$j]."</td>");
	}
	print("</tr>");
}
print("</table><br>");
*/

$list=file($filename_daily_log);   //lese Datei in Array $list (1 Element pro Zeile)
$lzi = count($list);
// Tabelle mit Daten der letzten 7 Tage ausgeben
$weekday = date ("w"); //Numerischer Tag einer Woche Montag = 1, Sonnatag=0
print("<table border=1 cellpadding=2 cellspacing=0>");
$head =explode(' ', $list[0]);
$head_count = count($head);
print("<tr><td colspan=".$head_count.">Letzte Woche</td>");

// output header
$zeile=explode(' ', $list[0]);
print("<tr>");
print("<td colspan=2>".$zeile[0]."</td>\n");
for ($j=1; $j<count($zeile); $j++)
{
	if (strlen($zeile[$j]) > 0) print("<td>".$zeile[$j]."</td>\n");
}
print("</tr>");


// list daily production
$sum_etot =0.0;
$sum_hours =0;
$do_count_it = false;
$week2date =0.0;

for ($i=$lzi-7; $i<$lzi; $i++)
{
	if ($i >0)
	{
		$zeile = splitline(' ', $list[$i]);
		$sum_etot += $zeile[5];
		$sum_hours += $zeile[4];
		$dat = explode('.', $zeile[0]);
		$Tag = date("D", mktime(0, 0, 0, $dat[1], $dat[0], $dat[2]));
		if ($weekday != 1) // heute ist nicht Montag
		{
			if (strpos($Tag,"Mon") !== false)
				$do_count_it = true;
		}
 
        if ($do_count_it)
        {
            $week2date += $zeile[5];
        }        
		array_unshift($zeile, $Tag);
		print("<tr>\n");
		for ($j=0; $j<count($zeile); $j++)
		{
			print("<td align=right>".$zeile[$j]."</td>\n");
		}
		print("</tr>");
	}
}

printf("<tr><td colspan= %u>Summe Produktion:</td>", count($zeile)-2);
printf("<td align=right>%.1f</td><td align=right>%.1f</td></tr>", $sum_hours, $sum_etot);
printf("<tr><td colspan= %u>Summe diese Woche:</td>", count($zeile)-1);
//printf("<td align=right>%.1f</td></td></tr>", $week2date + $summe_heute);
printf("<td align=right>%.1f</td></td></tr>", $week2date);
print("</table>");
?>

<script type="text/javascript"> fill_the_arrays();</script>

<br> <br>
<form  valign = "top" name="Eingabe2" >
<table border=1 cellpadding="3" cellspacing="0">

<tr><td valign = "top" colspan="2">Tagesproduktion: </td></tr>

<tr>
	<td valign ="top">
		<select name="Auswahl1" size="" onchange="ChoiceDailyPlot(this.form.Auswahl1.selectedIndex)" style="border:0px">
		<option selected>Tage</option><option>Zeitraum</option><option>Monat</option></select>
	</td>

	<td>
		<table border=0 cellpadding=0 cellspacing=0 valign="top" >
		<tr><td id = "TageZurueck" style="display:inline">
		<input type="text" name = "Tage" size ="10" value="30" style="border:0px" >
		</td></tr>

		<tr><td id="Zeitraum" style="display:none">
		<table  border=0 cellpadding="1" cellspacing="0">
			<tr><td>von:</td><td><input type="text" name = "Von" size ="9" style="border:0px" value=<?php echo date('1.1.Y')?>></td></tr>
			<tr><td>bis:</td><td><input type="text" name = "Bis" size ="9" style="border:0px" value=<?php echo date('d.m.Y')?> ></td></tr>
		</table>
		</td></tr>

		<tr><td id ="EinMonat" style="display:none">
		
		<input type="text" name = "Monat" size ="10" style="border:0px" value=<?php echo date('m')?>>
		</td>
		</tr>
		</table>
		
	</td>
</tr>
<tr>
	<td><input type="checkbox" name="do_sl_avg" value ="">Gleitender Mittelwert:</td>
	<td><input type="text" name = "sl_avg" size ="8" style="border:0px" value="10"></td>
</tr>
<tr>
	<td colspan = 2><input type="checkbox" name="do_balken" value ="" checked="checked">Balkendiagramm</td>
</tr>
<tr>
	<td colspan ="2" align="left">
		<input type="button"  value="Anzeigen" onclick="energy_per_day(this.form.Auswahl1.selectedIndex, this.form.Tage.value, this.form.Von.value, this.form.Bis.value, this.form.Monat.value, this.form.do_sl_avg.checked, this.form.sl_avg.value, this.form.do_balken.checked)">
	</td>
</tr>
<tr><td style="border:0px" ><br><br>Wochenproduktion:</td></tr>
<tr>	
		<td colspan ="2" align ="left"><input type="button"  value="Anzeigen" onclick="energy_per_time(7)"></td>
</tr>
</table>
</form>
<img id="production_over_time" src = "./space.gif"></img>

<?php
$temp_sensor_count=file("/sys/devices/w1_bus_master1/w1_master_slave_count");   //lese Datei in Array $list0 (1 Element pro Zeile)
if ($temp_sensor_count[0] > 0)
{
    $temp_sensor_list = file("/sys/devices/w1_bus_master1/w1_master_slaves");
    print ("<table border=1><tr><td colspan = 2>Temperaturen</td></tr>");
    for ($i=0; $i< count($temp_sensor_list); $i++)
    {
        $tempsensor = trim($temp_sensor_list[$i]);
        $t_sensor_filename = "/sys/devices/w1_bus_master1/" . $tempsensor . "/w1_slave";
        $temp_sensor_file = file($t_sensor_filename);
        
        
        if (count($temp_sensor_file) > 1)
        {
            
            $zeile = splitline(' ', $temp_sensor_file[1]);
            $tarr = splitline('=', $zeile[9]);
            $t = $tarr[1]/1000.0;
            
            if (strpos($tempsensor,"28-00000543326e") !== false)
            {
                printf ("<tr><td>6er</td><td>%.1f degC</td></tr>", $t);
            }
            else if (strpos($tempsensor,"28-000005436d91") !== false)
            {
                printf ("<tr><td>Aussen Nord</td><td>%.1f degC</td></tr>", $t);
            }
           
			else if (strpos($tempsensor,"28-000005437180") !== false)
            {
                printf ("<tr><td>Heizung Vorlauf</td><td>%.1f degC</td></tr>", $t);
            }
			else if (strpos($tempsensor,"28-00000543189e") !== false)
            {
                printf ("<tr><td>Heizung Ruecklauf</td><td>%.1f degC</td></tr>", $t);
            }
            else if (strpos($tempsensor,"28-0000055b6bc5") !== false)
            {
                printf ("<tr><td>Gang Thermostat</td><td>%.1f degC</td></tr>", $t);
            }
            else
            {
                printf ("<tr><td>".$tempsensor . "</td><td>%.1f degC</td></tr>", $t);
            }
        }
    }
    print("</table><br>");
}
print("<a href=\"./graph/sensoren.php\">Zur DS18B20 Sensoren Seite</a><br>");

$wlan0 = exec("/sbin/iwconfig wlan0 | grep 'Link Quality'");
$wlan = splitline('=/ ', $wlan0);

if (count($wlan) > 11)
{
    print("<table border=1><tr><td colspan=2>WLAN Status</td></tr>");
    for ($i=0; $i<12; $i+=4)
    {
        //print($wlan[$i]."<br>");
        print("<tr><td>".$wlan[$i]." ".$wlan[$i+1]."</td><td>".$wlan[$i+2]."</td></tr>");
    }
    print("</table>");   
}
/*
if (count($wlan) > 2)
{
    $link_qual_str = $wlan[0];
    $zw = explode(' ', $wlan[1]);
    $link_qual_val =$zw[0];
    $signal_level_str = $zw[2].' '.$zw[3]; // there are 2 white spaces in-between!
    
    $zw = explode(' ', $wlan[2]);
    $signal_level_val = explode('/',$zw[0])[0];
    $noise_level_str = $zw[2].' '.$zw[3];
    $noise_level_val = $wlan[3];
    
    print("<table border=1><tr><td colspan=2>WLAN Status</td></tr>");
    print("<tr><td>".$link_qual_str."</td><td>".$link_qual_val."</td></tr>");
    print("<tr><td>".$signal_level_str."</td><td>".$signal_level_val."</td></tr>");
    print("<tr><td>".$noise_level_str."</td><td>".$noise_level_val."</td></tr>");
    print("</table>");
}
*/


?>
<a href="javascript: window.location.reload()">Reload</a>
<script language="javascript" type="text/javascript"> reload_timer(); </script>
</body>
</html>
