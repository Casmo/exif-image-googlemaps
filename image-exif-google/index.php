<?php
/**
 * This file reads all images from the images/ folder with exif and makes an useable google maps polyline.
 * I hoverever recommend to put all the locations in a database or in a array so you won't load all the
 * images with each request.
 * @author Mathieu de Ruiter <http://www.fellicht.nl>
 * 
 * PHP 4 >= 4.2.0, PHP 5
 * php_exif module needs to be enabled.
 * 
 * USE AT OWN RISK.
 */

// Folder for the images
$folderDirectory = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
$googleKey = 'googleKeyHere';

$polylines = array();
$images = array();
if ($handle = opendir($folderDirectory)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && is_file($folderDirectory . $entry)) {
        	$currentFile = $folderDirectory . $entry;
        	if(preg_match('/(.jp(e)?g)$/', $entry)) {
        		$exif_data = exif_read_data($folderDirectory . $entry);
        		if(isset($exif_data['DateTime']) && isset($exif_data['GPSLatitude'])) {
        			$sortableDate = date('YmdHis', strtotime($exif_data['DateTime']));
        			$images[$sortableDate]['lat'] = getGps($exif_data["GPSLatitude"], $exif_data['GPSLatitudeRef']);
        			$images[$sortableDate]['lon'] = getGps($exif_data["GPSLongitude"], $exif_data['GPSLongitudeRef']);
        		}
        	}
        	
        }
    }
    closedir($handle);
}

/**
 *  @see http://stackoverflow.com/questions/2526304/php-extract-gps-exif-data
 */
function getGps($exifCoord, $hemi) {

    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

}

function gps2Num($coordPart) {

    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}
?>

<!DOCTYPE HTML>
<html lang="en-us">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>My Travel Route so far!</title>
	<meta name="author" content="Fellicht.nl - Mathieu" />
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $googleKey; ?>&sensor=true"></script>
</head>
<body>
<h1>My Travel Route so far!</h1>
<div id="map" style="width: 800px; height: 600px;">No data found.</div>
<?php
if(isset($images) && !empty($images)) {
?>
<script>
var map;
function initialize() {
	var myLatLng = new google.maps.LatLng(0, 0);
	var mapOptions = {
		zoom: 1,
		center: myLatLng,
		mapTypeId: google.maps.MapTypeId.TERRAIN,
		streetViewControl: false
	};	  
	map = new google.maps.Map(document.getElementById("map"), mapOptions);
	var cycleCoordinates = [
<?php
$check = false;
foreach($images as $dateTime => $latLon) {
	if($check == true) {
		echo ',';
	}
	echo 'new google.maps.LatLng('. $latLon['lat'] .','. $latLon['lon'] .')';
	$check = true;
}
?>
	];
	var cyclePath = new google.maps.Polyline({
		path: cycleCoordinates,
		strokeColor: "#7e5923",
		strokeOpacity: 0.7,
		strokeWeight: 3
	});
	cyclePath.setMap(map);
}
initialize();
</script>
<?php 
}
?>
</body>
</html>