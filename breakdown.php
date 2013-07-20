<?php
	include "db.php";
	
	if (empty($_GET['data']))
		die();
	
	$data = json_decode(base64_decode($_GET['data']));
	
	if (is_null($data))
		die();
		
	$valueArr = "";
	foreach ($data as $movie => $value)
	{
		if ($value > 0)
		{
			if (!empty($valueArr))
				$valueArr .= ",\r\n";
			$valueArr .= "['$movie',$value]";
		}
	}
?>

<!doctype html>
<html>
  <head>
    <meta name="generator" content="HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title>2012 Winter Movie Contest - Breakdown</title>
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
         },
         title: {
            text: "<?php echo urldecode($_GET['contestant']) ?>"
         },
         series: [{
            data: [<?php echo $valueArr; ?>],
			type: 'pie'
         }]
      });
   });
	
</script>

<div id="container" style="width: 100%; height: 400px"></div>
  </body>
</html>
