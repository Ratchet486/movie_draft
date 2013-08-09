<?php
	include "db.php";
	
	// first get the percentages
	$sql = connect();
	$team = $sql->real_escape_string($_GET['team']);
	$result = $sql->query("CALL GetPercentagesTeam('$team')");

	$percentages = array();
	while ($a = $result->fetch_array(MYSQLI_ASSOC))
	{
		$percentages[] = $a;
	}
	$result->free();
	$sql->close();

	$START_DATE = new DateTime("2013-05-05 00:00:00", new DateTimeZone("America/New_York"));
	$STOP_DATE = new DateTime("now", new DateTimeZone("America/New_York"));
	$data = array();
	
	while ($START_DATE < $STOP_DATE)
	{
		$sql = connect();
		$q = sprintf("CALL GetGross('%s')", $START_DATE->format("Y-m-d"));

		$grs = $sql->query($q);

		$grosses = array();
		while ($a = $grs->fetch_array(MYSQLI_ASSOC))
		{
			$grosses[$a['Movie']] = $a['Gross'];
		}

		$score = array();
		$pct = $percentages;
		foreach ($pct as &$p)
		{
			$p['Movie'] = isset($grosses[$p['Movie']]) ? $grosses[$p['Movie']] : 0;
			foreach ($p as $key => $gross)
			{
				if ($key != "Movie")
				{
					$p[$key] = $p[$key] * $p['Movie'];
					$score[$key] += $p[$key];
				}
			}
		}
		//get spread 
		$max = max($score);
		$min = min($score);
		$spread = $max-$min;
		
		foreach($score as $key => $scr)
		{
			
			$score[$key] = (($scr - $min)/($spread)) * 100;
		}
		
		arsort($score);
		$i = 0;
		foreach ($score as $key => $gross)
		{
			$data[$key][$START_DATE->format("U")] = isset($_GET['rank']) ? ++$i : $gross;
		}
		
		$START_DATE->modify("+7 day");
		$grs->free();
		$sql->close();
	}
	
	$output = getChartData($data);
	
	function getChartData($a)
	{
		$ret = "";
		foreach ($a as $key => $date)
		{
			if (!empty($ret))
				$ret .= ',';
			$ret .= "{name: \"$key\", data:";
			$lr = "";
			foreach ($date as $key => $total)
			{
				if (!empty($lr))
					$lr .= ',';
				$d = getdate($key);
				
				$lr .= sprintf("[Date.UTC(%d,%d,%d), %d]", $d['year'], $d['mon']-1, $d['mday'], $total);
			}
			
			$ret .= "[$lr]}";
		}
		
		return $ret;
	}
	
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
            text: "Weekly Data"
         },
         xAxis: {
            type: 'datetime'
         },
         yAxis: {
            title: {
               text: 'Revenue (USD)',
            },
			min: <?php echo isset($_GET['rank']) ? 1 : 0 ?>,
			reversed: <?php echo isset($_GET['rank']) ? "true" : "false" ?>
         },
         series: [<?php echo $output; ?>]
		 
      });
   });
	
</script>

<div id="container" style="width: 100%; height: 400px"></div>
  </body>
</html>