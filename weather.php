<?php
/*
 * @author Christian Bartsch <cb AT dreinulldrei DOT de>, bt43a
 * @copyright (c) Christian Bartsch, bt43a
 * @license GPL v2
 * @date 2015-10-24
 *
 * Installation:
 * 
 * 1. Copy all files from "fritzco" into a folder on your server 
 * 2. Creater weather subfolder, then create folders: font, wallpaper and icons.
 * 2. Extract OpenWeatherMap API to /lib folder (=place cmfcmf folder in /lib folder)
 * 3. Copy true type fonts arial.ttf and arialbd.ttf (Windows/Fonts) to weather/font
 * 4. Change line 56 in /lib/cmfcmf/OpenWeatherMap/Util/Unit.php to:
		$this->value = round((float)$value, 1);
 * 5. Edit config file in /weather
 */
 

require_once __DIR__ . '/config/general.config.inc.php';
require_once __DIR__ . '/lib/Cmfcmf/OpenWeatherMap.php');
require_once __DIR__ . '/config/weather.config.inc.php';
require_once __DIR__ . '/locale/weather.locale.inc.php';
require_once __DIR__ . '/lib/cipxml/cipxml.php';

use cmfcmf\OpenWeatherMap;
use cmfcmf\OpenWeatherMap\Exception as OWMException;
use cipxml\CiscoIPPhoneImageFile;
use cipxml\SoftKeyItem;

if (isset($_GET["loc"])) {
	$location = $_GET["loc"];
	$location_id = $location;
}else{
	$location = $weather_city;
	$location_id = $weather_id;
}
if (isset($_GET["id"])) {
	$location_id = $_GET["id"];
}
if (isset($_GET["lang"])) {
	$lang = $_GET["lang"];
}
if (isset($_GET["units"])) {
	$units = $_GET["units"];
}
if (isset($_GET["pos"])) {
	$position = intval($_GET["pos"]);
} else {
	$position = 0;
}
if (isset($_GET["target"])) {
	$target = $_GET["target"];
}

switch ($target) {
	case '7941' :
		$display_x = 298;
		$display_y = 144;
		$display_scale = 0.55;
		$symbol_scale = 1;
		$display_offset_x = -15;
		$display_offset_y = -13;
		$display_font = 'weather/font/arial.ttf';
		$display_color = false;
		break;
	case '7945' :
		$display_x = 298;
		$display_y = 156;
		$display_scale = 0.59;
		$symbol_scale = 1;
		$display_offset_x = -15;
		$display_offset_y = -15;
		$display_font = 'weather/font/arialbd.ttf';
		$display_color = true;
		break;
	case '7971' :
		$display_x = 298;
		$display_y = 168;
		$display_scale = 0.59;
		$symbol_scale = 1;
		$display_offset_x = -15;
		$display_offset_y = -5;
		$display_font = 'weather/font/arialbd.ttf';
		$display_color = true;
		break;
	default : // 9971
		$display_x = 498;
		$display_y = 289;
		$display_scale = 1;
		$symbol_scale = 2;
		$display_offset_x = -5;
		$display_offset_y = -10;
		$display_font = 'weather/font/arial.ttf';
		$display_color = true;
}

$display_background = $wallpaper_path . $display_x . 'x' .$display_y . '_' . $wallpaper_file;


$owm = new OpenWeatherMap();
//Werte auslesen
if ($position == 0) {
	try {
		$weather = $owm->getWeather($location_id, $units, $lang, $weather_apikey);
	} catch (Exception $e) {
		$error_abort = $e->getMessage();
	}
} else{
	try {
		$forecast = $owm->getWeatherForecast($location_id, $units, $lang, $weather_apikey, 3);
		$forecast_size = count($forecast);
		$i = 0;
		foreach($forecast as $weather) {
			if ($i == $position) {
				break;
			}
			$i++;
		}
		unset($value);
	} catch (Exception $e) {
		$error_abort = $e->getMessage();
	}
	
}

if (isset($_GET["png"])) {
	// start PNG output
		
	header('Content-Type: image/png');
	
	$img=imagecreatetruecolor($display_x,$display_y);
	
	if ($display_color) {
		//Farbe für Schrift & Rahmen festlegen
		$white = imagecolorallocate($img, 255, 255, 255);
		$grey1 = imagecolorallocate($img, 220, 220, 220);
		$grey2 = imagecolorallocate($img, 150, 150, 150);
		$black = imagecolorallocate($img, 50, 50, 50);
		$img_tmp = imagecreatefrompng($display_background);
		imagecopyresampled($img,$img_tmp,0,0,0,0, $display_x,$display_y,$display_x,$display_y);
		imagedestroy($img_tmp);
	} else {
		//Farbe für Schrift & Rahmen festlegen
		$white = imagecolorallocate($img, 0, 0, 0);
		$grey1 = imagecolorallocate($img, 35, 35, 35);
		$grey2 = imagecolorallocate($img, 150, 150, 150);
		$black = imagecolorallocate($img, 255, 255, 255);
		imagefill ($img, 0, 0, $black);
	}
	
	if (!isset($error_abort)) {
	
		// $symbol = imagecreatefrompng("http://openweathermap.org/img/w/". $weather->weather->icon .".png"); //Symbol aus Internet verwenden
		$symbol = imagecreatefrompng("weather/icons/".$weather->weather->icon . ".png"); //eigene Symbole verwenden
	
		$symbol_x = (imagesx($symbol) * $symbol_scale);
		$symbol_y = (imagesy($symbol) * $symbol_scale);
		//Wetter Symbol einfügen
		if (!$display_color) {
			imagefilter($symbol, IMG_FILTER_NEGATE);
			imagefilter($symbol, IMG_FILTER_GRAYSCALE);
		}
		imagecopyresampled($img, $symbol, (335 * $display_scale + $display_offset_x), (35 * $display_scale + $display_offset_y), 0, 0, $symbol_x, $symbol_y, imagesx($symbol), imagesy($symbol));
		//Werte in background schreiben
		ImageTTFText ($img, (33 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (85 * $display_scale + $display_offset_y +1), $black, $display_font, $weather->temperature->now);
		ImageTTFText ($img, (33 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (85 * $display_scale + $display_offset_y), $white, $display_font, $weather->temperature->now);
		
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (110 * $display_scale + $display_offset_y +1), $black, $display_font, WEATHER_TEMP_MIN . ' ' . $weather->temperature->min . ' / ' . WEATHER_TEMP_MAX . ' ' . $weather->temperature->max);
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (110 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_TEMP_MIN . ' ' . $weather->temperature->min . ' / ' . WEATHER_TEMP_MAX . ' ' . $weather->temperature->max);
		
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (155 * $display_scale + $display_offset_y+1), $black, $display_font, WEATHER_COLUMN_PRESSURE . ' ' . $weather->pressure);
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (155 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_COLUMN_PRESSURE . ' ' . $weather->pressure);
		
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (180 * $display_scale + $display_offset_y +1), $black, $display_font, WEATHER_COLUMN_HUMIDITY . ' ' . $weather->humidity);
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (180 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_COLUMN_HUMIDITY . ' ' . $weather->humidity);
		
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (205 * $display_scale + $display_offset_y +1), $black, $display_font, WEATHER_COLUMN_WIND . ' ' . $weather->wind->speed ." @ ". $weather->wind->direction);
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (205 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_COLUMN_WIND . ' ' . $weather->wind->speed ." @ ". $weather->wind->direction);
		
		//richtige Position für die weatherdescription berechnen
		$xpos_tmp = imageftbbox((13 * $display_scale + 1), 0, $display_font, $weather->weather->description, array("linespacing" => 1));
		$xpos_tmp = round (($display_x - $xpos_tmp[4])-15);
		if ($xpos_tmp > (330 * $display_scale + $display_offset_x)) {
			$xpos_tmp = 330 * $display_scale + $display_offset_x;
		}
		ImageTTFText ($img, (13 * $display_scale + 1), 0, ($xpos_tmp +1), (140 * $display_scale + $display_offset_y +1), $black, $display_font, $weather->weather->description);
		ImageTTFText ($img, (13 * $display_scale + 1), 0, $xpos_tmp, (140 * $display_scale + $display_offset_y), $white, $display_font, $weather->weather->description);
		if ($position == 0) {
			ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (255 * $display_scale + $display_offset_y +1), $black, $display_font, WEATHER_COLUMN_SUNRISE . ' ' . ($weather->sun->rise->format('H')+ $gmt_offset) . ":". $weather->sun->rise->format('i') . '      ' . WEATHER_COLUMN_SUNSET . ' '. ($weather->sun->set->format('H')+ $gmt_offset) .":" . $weather->sun->set->format('i'));		
			ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (255 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_COLUMN_SUNRISE . ' ' . ($weather->sun->rise->format('H')+ $gmt_offset) . ":". $weather->sun->rise->format('i') . '      ' . WEATHER_COLUMN_SUNSET . ' '. ($weather->sun->set->format('H')+ $gmt_offset) .":" . $weather->sun->set->format('i'));
		} else{
			ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (255 * $display_scale + $display_offset_y +1), $black, $display_font, WEATHER_COLUMN_FORECAST_FROM . ' ' . $weather->time->from->format('H:i') . ' ' . WEATHER_COLUMN_FORECAST_TO . ' ' .  $weather->time->to->format('H:i') . ' ' . WEATHER_SYMBOL_TIME);
			ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (255 * $display_scale + $display_offset_y), $white, $display_font, WEATHER_COLUMN_FORECAST_FROM . ' ' . $weather->time->from->format('H:i') . ' ' . WEATHER_COLUMN_FORECAST_TO . ' ' .  $weather->time->to->format('H:i') . ' ' . WEATHER_SYMBOL_TIME);
		}
		
		$xpos_tmp = imageftbbox((8 * $display_scale + 1), 0, $display_font, date("H:i:s"), array("linespacing" => 1));
		$xpos_tmp = round (($display_x - $xpos_tmp[4])-5);		
		ImageTTFText ($img, (8 * $display_scale + 1), 0, $xpos_tmp, (280 * $display_scale + $display_offset_y), $white, $display_font, date("H:i:s"));
	
	} else {
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x +1), (50 * $display_scale + $display_offset_y +1), $black, $display_font, wordwrap($error_abort, 35, "\n", true));
		ImageTTFText ($img, (13 * $display_scale + 1), 0, (50 * $display_scale + $display_offset_x), (50 * $display_scale + $display_offset_y), $white, $display_font, wordwrap($error_abort, 35, "\n", true));
	
	}
	
	imagerectangle($img, 0, 0, $display_x-2, $display_y-2, $grey1);
	imagerectangle($img, 1, 1, $display_x-1, $display_y-1, $grey2);
	
	if (!$display_color) {
		imagetruecolortopalette($img, true, 4);
	}
	imagepng($img);
	imagedestroy($img);	

} else {

	// show XML output
	
	if (!isset($error_abort)) {
	
		if ($position == 0) {
		$menu = new CiscoIpPhoneImageFile(WEATHER_TITLE, $location . ' ' . WEATHER_TEXT_NOW, "http://". $_SERVER["SERVER_NAME"] .  $_SERVER["PHP_SELF"] . "?png&loc=" . $location ."&id=" . $location_id . "&lang=" . $lang . "&units=" . $units . "&target=" . $target . "&pos=" . $position);
		$menu->addSoftKeyItem(new SoftKeyItem(WEATHER_BUTTON_REFRESH, 1, 'SoftKey:Update'));
		} else {
		$menu = new CiscoIpPhoneImageFile(WEATHER_TITLE, $location . ' ' . WEATHER_TEXT_AT . ' ' . $weather->time->day->format('d.m.Y'), "http://". $_SERVER["SERVER_NAME"] .  $_SERVER["PHP_SELF"] . "?png&loc=" . $location . "&id=" . $location_id . "&lang=" . $lang . "&units=" . $units . "&target=" . $target . "&pos=" . $position);
		$menu->addSoftKeyItem(new SoftKeyItem(WEATHER_BUTTON_BACK, 1, "http://". $_SERVER["SERVER_NAME"] .  $_SERVER["PHP_SELF"] . "?loc=" . $location . "&id=" . $location_id . "&lang=" . $lang . "&units=" . $units . "&target=" . $target . "&pos=" . ($position-1)));
		}
	
		if ($position < 41) {
			if ($position == 0) {
				$button_temp = WEATHER_COLUMN_FORECAST;
			} else {
				$button_temp = WEATHER_BUTTON_NEXT;
			}
			$menu->addSoftKeyItem(new SoftKeyItem($button_temp, 2, "http://". $_SERVER["SERVER_NAME"] .  $_SERVER["PHP_SELF"] . "?loc=" . $location . "&id=" . $location_id . "&lang=" . $lang . "&units=" . $units . "&target=" . $target . "&pos=" . ($position+1)));
		}
	
	} else {
	
		$menu = new CiscoIpPhoneImageFile(WEATHER_TITLE, 'ERROR' , "http://". $_SERVER["SERVER_NAME"] .  $_SERVER["PHP_SELF"] . "?png&loc=" . $location ."&id=" . $location_id . "&lang=" . $lang . "&units=" . $units . "&target=" . $target . "&pos=" . $position);
	  	
	}
	
	$menu->addSoftKeyItem(new SoftKeyItem(WEATHER_BUTTON_EXIT, 3, 'Init:Services'));

	header("Content-type: text/xml");
	header("Refresh: $weather_refresh;");
	echo $menu;

}

?>
