<html><head><title>heizung</title>
<style type="text/css">
form { padding:20px; border:6px solid #ddd; align-items:center; width:40%;}
td, input, select, textarea, h1 { font-size:400%; font-family:Verdana,sans-serif; font-weight:bold;  text-align: center;}
h2 {font-size:200%; font-family:Verdana,sans-serif; font-weight:normal;  text-align: center;}
input, select, textarea { color:#00c; }
.Bereich, .Feld { background-color:#ffa; width:300px; border:6px solid #ddd; }
.Auswahl { background-color:#dff; width:300px; border:6px solid #ddd; }
.Check, .Radio { background-color:#ddff; border:1px solid #ddd; }
.Button { background-color:#aaa; color:#fff; border:6px solid #ddd;}
</style>

<script type="text/javascript">
    function bg_col_green(elm)
    {
        self.document.getElementsByName("on")[elm].style.backgroundColor = "#4b4";
    }
    function button_width(breite)
    {
        self.document.getElementsByName("on")[0].style.width = breite + "%";
        self.document.getElementsByName("on")[1].style.width = breite + "%";
        self.document.getElementsByName("on")[2].style.width = breite + "%";
    }
    function button_height(hoehe)
    {
        self.document.getElementsByName("on")[0].style.height = hoehe + "%";
        self.document.getElementsByName("on")[1].style.height = hoehe + "%";
        self.document.getElementsByName("on")[2].style.height = hoehe + "%";
    }
    
    function button_height_px(hoehe)
    {
        self.document.getElementsByName("on")[0].style.height = hoehe + "px";
        self.document.getElementsByName("on")[1].style.height = hoehe + "px";
        self.document.getElementsByName("on")[2].style.height = hoehe + "px";
    }
</script>

</head>
<body>
<div>
<h1>Heizungssteuerung</h1>
</div>

<?php
function gpio_wert($GPIO)
{
    $status = file_get_contents("/sys/devices/virtual/gpio/gpio".$GPIO."/value");
    return $status;
}

function gpio_set($GPIO, $val='1')
{
    $cmd = "echo \"".$val."\" > /sys/class/gpio/gpio".$GPIO."/value";
    shell_exec($cmd);
    //print ($cmd);
}

function gpio_init($GPIO, $dir="out")
{
    if (!file_exists("/sys/class/gpio/gpio".$GPIO)) 
    {
        file_put_contents("/sys/class/gpio/export", $GPIO);
        usleep(200000); //Programm-Verzoegerung in Mikrosekunden: 0.2s
        file_put_contents("/sys/class/gpio/gpio".$GPIO."/direction", $dir);
    }
}


function find_mobile_browser()
{
  if(preg_match('/(alcatel|android|blackberry|benq|cell|elaine|htc|iemobile|iphone|ipad|ipaq|ipod|j2me|java|midp|mini|mobi|motorola|nokia|palm|panasonic|philips|phone|sagem|samsung|sharp|smartphone|sony|symbian|t-mobile|up\.browser|up\.link|vodafone|wap|wireless|xda|zte)/i', $_SERVER['HTTP_USER_AGENT']))
  {
    return true;
  }
  else
  {
  return false;
  }
}

$heizung_an = 17;
$thermostat_disable = 24;
$thermostat_sens = 27;

/*
if (!file_exists("/sys/class/gpio/gpio17")) {
    file_put_contents("/sys/class/gpio/export", "17");
    usleep(200000); //Programm-Verzoegerung in Mikrosekunden: 0.2s
    file_put_contents("/sys/class/gpio/gpio17/direction", "out");
}

if (!file_exists("/sys/class/gpio/gpio24")) {
    file_put_contents("/sys/class/gpio/export", "17");
    usleep(200000); //Programm-Verzoegerung in Mikrosekunden: 0.2s
    file_put_contents("/sys/class/gpio/gpio2/direction", "out");
}
*/

gpio_init($heizung_an);
gpio_init($thermostat_disable);
gpio_init($thermostat_sens, "in");

$rpi1 = gpio_wert($heizung_an);
$rpi2 = gpio_wert($thermostat_disable);
$thermostat_status =gpio_wert($thermostat_sens);

?>
<div align = 'center'>
<form method="get" action="index.php">
<input type="submit" class="Button" value="AN" name="on">
<br><br>
<input type="submit" class="Button" value="AUS" name="on">
<br><br>
<input type="submit" class="Button" value="AUTO" name="on">
</form>
</div>

<script language="javascript" type="text/javascript">
<?php
if (find_mobile_browser())
{
    print ("button_width('100');");
    print ("button_height('25');");
}
else 
    print ("button_width('100');");
    print ("button_height('15');");
?>
</script>

<?php
if(isset($_GET['on']))
{
    //echo shell_exec("ping -c 1 ".$_GET['host']."");
    //echo "Schalte Heizung Ein:";
    if ($_GET['on'] =="AN")
        gpio_set($heizung_an, '1');
    else if ($_GET['on'] =="AUS")
    {
        gpio_set($heizung_an, '0');
        gpio_set($thermostat_disable, '1');
    }
    else if ($_GET['on'] =="AUTO")
    {
        gpio_set($heizung_an, '0');
        gpio_set($thermostat_disable, '0');
    }
    $rpi1 = gpio_wert($heizung_an);
    $rpi2 = gpio_wert($thermostat_disable);
    $thermostat_status =gpio_wert($thermostat_sens);
}


//echo "RPI1 (Heizung AN) ist ".$rpi1;
$heizung_modus ="";
$heizung_elt = -1;
if ($rpi1 == 0 && $rpi2 == 0)
{
    $heizung_modus = "Auto";
    $heizung_elt = 2;
}
else if ($rpi1 ==1)
{
    $heizung_modus = "An";
    $heizung_elt = 0;
}
else if ($rpi1 ==0 && $rpi2 ==1)
{
    $heizung_modus = "Aus";
    $heizung_elt = 1;
}
else
    $heizung_modus = "unbekannt";
    
//echo "Heizung Modus ist: ".$heizung_modus;

print "<br><h2>";

$th_an ="";
if ($thermostat_status == 0)
    $th_an = "aus";
else if ($thermostat_status == 1)
    $th_an = "an";
else 
    $th_an = "unbekannt";
    

if ($rpi2 == 0)
    echo "Thermostat ist:    ".$th_an;
else if ($rpi2 == 1)
    echo "Thermostat ist deaktiviert";
else
    echo "Thermostat Status unbekannt.";
?>
</h2>
<script language="javascript" type="text/javascript">
<?php 
    if ($heizung_elt >=0)           
        printf ("bg_col_green(%s);", $heizung_elt);
?>
</script>
</body> 
</html>