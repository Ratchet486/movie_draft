<?php
	include "db.php";
	
	$movie = "";
	if (!empty($_GET['movie']))
		$movie = urldecode($_GET['movie']);
	
	$sql = connect();
	$movie = $sql->real_escape_string($movie);
	
	if (!empty($movie))
		$query = "SELECT Movie, Gross, YEAR(OnDate) AS 'Year', MONTH(OnDate) AS 'Month', DAY(OnDate) AS 'Day' FROM `earnings` WHERE Movie LIKE \"$movie\" ORDER BY `OnDate` ASC";
	else
	{
		$query = "SELECT Movie, Gross, YEAR(OnDate) AS 'Year', MONTH(OnDate) AS 'Month', DAY(OnDate) AS 'Day' FROM `earnings` WHERE Movie IN (SELECT DISTINCT `Movie` FROM `shares`) ORDER BY `Movie`,`OnDate` ASC";
		$movie = "All Movies";
	}
	$result = $sql->query($query);
	echo $sql->error;
	$movieArr = array();
	$currMovie = "";
	$toAdd = array();
	while ($a = $result->fetch_array())
	{
		if ($a['Movie'] != $currMovie)
		{
			if (!empty($toAdd))
				$movieArr[] = $toAdd;
			$toAdd = array();
			$toAdd['name'] = $a['Movie'];
			$toAdd['data'][] = sprintf("[Date.UTC(%d,%d,%d), %d]", $a['Year'], $a['Month']-1, $a['Day']-1, 0);
			$currMovie = $a['Movie'];
		}
		
		$toAdd['data'][] = sprintf("[Date.UTC(%d,%d,%d), %d]", $a['Year'], $a['Month']-1, $a['Day'], $a['Gross']);
	}
	
	if (!empty($toAdd))
		$movieArr[] = $toAdd;
		
	$output = "[";
	foreach($movieArr as $ta)
	{
		if ($output != "[")
			$output .= ",\r\n";
		$output .= sprintf("{'name':'%s', 'data':%s}", str_replace("'","\'", $ta['name']), str_replace('"','',json_encode($ta['data'])));
	}
		
	$output .= "]";
	
?>

<!doctype html>
<html>
  <head>
    <meta name="generator" content="HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title>2012 Winter Movie Contest - Details</title>
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/south-street/jquery-ui.css" type="text/css" media="all" />
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<script src="js/highcharts.js" type="text/javascript"></script>

  </head>
  <body>
<script type="text/javascript">
var chart1; // globally available
$(document).ready(function() {
      chart1 = new Highcharts.Chart({
         chart: {
            renderTo: 'container',
            type: 'spline'
         },
         title: {
            text: "<?php echo $movie ?>"
         },
         xAxis: {
            type: 'datetime'
         },
         yAxis: {
            title: {
               text: 'Revenue (USD)',
            },
			min: 0
         },
         series: <?php echo $output; ?>
		 
      });
   });
	
</script>

<div id="container" style="width: 100%; height: 400px"></div>
  </body>
</html>
