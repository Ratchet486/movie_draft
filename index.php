<?php
	include "db.php";
	
	// first get the percentages
	$sql = connect();
	$team = isset($_GET['team']) ? $sql->real_escape_string($_GET['team']) : "";
	$result = $sql->query("CALL GetPercentagesTeam('$team')");

	$percentages = array();
	while ($a = $result->fetch_array(MYSQLI_ASSOC))
	{
		$percentages[$a['Movie']] = $a;
	}
	$result->free();
	$sql->close();
	
	// now the most recent revenues for each movie
	$sql = connect();
	$grs = $sql->query("CALL GetNewestGross()");
	
	$grosses = array();
	while ($a = $grs->fetch_array(MYSQLI_ASSOC))
	{
		$grosses[$a['Movie']] = $a['Gross'];
	}
	$grs->free();
	$sql->close();
	
	// and get full names
	$sql = connect();
	$nam = $sql->query("SELECT * FROM `short_names`");
	
	$longNames = array();
	while ($a = $nam->fetch_array(MYSQLI_ASSOC))
	{
		$longNames[$a['short_name']] = $a['long_name'];
	}
	$nam->free();
	$sql->close();
	
	$results = array();
	$resultsByMovie = array();

	foreach ($percentages as &$pct)
	{
		$movie = $pct['Movie'];
		if (!empty($grosses[$movie]))
		{
			$total = $grosses[$movie];
			
			while (($p = current($pct)) !== false)
			{
				if (key($pct) != "Movie")
				{
					$results[key($pct)][$movie] = $p * $total;
					$resultsByMovie[$movie][key($pct)] = $p * $total;
				}
				next($pct);
			}
		}
	}
	
	$sums = array();
	$colHeaders = "";
	$colModels = "";
	$colModelsShares = "";
	while ($r = current($results))
	{
		$sums[key($results)] = array_sum($r);
		next($results);
	}
	
	reset($results);
	arsort($sums);
	$sumsDisplay = $sums;
	
	// get the column headers and models
	while ($r = current($sumsDisplay))
	{
		$sumsDisplay[key($sumsDisplay)] = sprintf('<a href="#" onclick="popupDetails(&quot;breakdown.php?contestant=%s&amp;data=%s&quot;);return false;">%s</a>', $longNames[key($sumsDisplay)], base64_encode(json_encode($results[key($sumsDisplay)])), convertToCurrency($sumsDisplay[key($sumsDisplay)],3));
		$colHeaders .= sprintf("'%s', ", key($sumsDisplay));
		$colModels .= sprintf("{name:'%s', align:'right', index:'%s', width:45, sorttype:sortMoney},\r\n", key($sumsDisplay), key($sumsDisplay));
		$colModelsShares .= sprintf("{name:'%s', align:'right', index:'%s', width:45, sorttype:'int'},\r\n", key($sumsDisplay), key($sumsDisplay));
		next($sumsDisplay);
	}
	
	$sumsDisplay['movie'] = "<a href=\"#\" onclick=\"popupDetails('details.php');return false;\">Total</a>";

	// now get the shares purchased by everyone
	$sql = connect();
	$sh = $sql->query("CALL GetSharesTeam('$team')");

	$shares = array();
	$totalSharesByMovie = array();
	$sharesToOutput = array();
	while ($a = $sh->fetch_array(MYSQLI_ASSOC))
	{
		$shares[] = $a;
	}
	$sh->free();
	$sql->close();
	
	$i = 0;
	foreach ($shares as $s)
	{
		if (!is_null($s['Movie']))
		{
			$row = array();
			$row['id'] = ++$i;
			$row['movie'] = $s['Movie'];
			$row['total'] = $s['Total'];
			$row['releasedate'] = date("m/d", strtotime($s['ReleaseDate']));
			$releasedates[$s['Movie']] = date("m/d",strtotime($s['ReleaseDate']));
			$totalSharesByMovie[$s['Movie']] = $s['Total'];
			while (($r = current($s)) !== false)
			{
				if (key($s) != "Movie" && key($s) != "Total" && key($s) != "ReleaseDate")
					$row[key($s)] = $r;
				next($s);
			}
			$sharesToOutput[] = $row;
		}
	}

	$sharesOutput = json_encode($sharesToOutput);
	
	// format the output
	$output = "";
	$i=0;
	
	
	while ($m = current($resultsByMovie))
	{
		if (!empty($output))
			$output .= ",";
		$output .= sprintf("{'id':%d, 'movie':\"%s\", 'releasedate':'%s'", ++$i, key($resultsByMovie), $releasedates[key($resultsByMovie)]);
		$total = 0;
		while (($s = current($m)) !== false)
		{
			$total += $s;
			$output .= sprintf(",'%s':'%s'", key($m), convertToCurrency($s));
			next($m);
		}

		$value = $total / $totalSharesByMovie[key($resultsByMovie)];
		
		$output .= sprintf(", 'total':'%s'", convertToCurrency($total));
		$output .= sprintf(", 'value':'%s'}", convertToCurrency($value));
		next($resultsByMovie);
	}
	
	$output = "[$output]";
	$sumsOutput = json_encode($sumsDisplay);
	$standings = array();
	$i = 0;

	foreach ($sums as $contestant => $score)
	{
		if (empty($longNames[$contestant])) continue;
		$a['rank'] = ++$i;
		$a['player'] = $longNames[$contestant];
		$a['revenue'] = "\$" . number_format($score,2);
		$standings[] = $a;
	}
	
	$standingsOutput = json_encode($standings);
	
	function convertToCurrency($a, $figures=3)
	{
		$ret = "";
		$figures--;
		if ($a >= 1000000000)
		{
			$ret = round($a/1000000000, ceil($figures - log10($a/1000000000))).'b';
		}
		else if ($a >= 1000000)
		{
			$ret = round($a/1000000, ceil($figures - log10($a/1000000))).'m';
		}
		else if ($a >= 1000)
		{
			$ret = round($a/1000, ceil($figures - log10($a/1000))).'k';
		}
		else
			return "";
		return "$ret";
	}
	
	/*
	function convertToCurrency($a, $figures=3)
	{
		return "\$".number_format($a,0)
	}
	*/

?>

<!doctype html>
<html>
  <head>
    <meta name="generator" content="HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title>2013 Summer Movie Contest</title>
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/south-street/jquery-ui.css" type="text/css" media="all" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/ui.jqgrid.css" />
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/jquery-ui.min.js"></script>
	<script src="js/i18n/grid.locale-en.js" type="text/javascript"></script>
	<script src="js/jquery.jqGrid.min.js" type="text/javascript"></script>
	
		
	<style type="text/css">
		
		th,td
		{
			font-size:14px;
		}
	</style>
	
	
  </head>
  <body>
<script type="text/javascript">

function sortMoney(cell)
{
	var base = parseFloat(cell) || 0;
	if (cell.indexOf("b") > 0)
		return base * 1000000000;
	if (cell.indexOf("m") > 0)
		return base * 1000000;
	if (cell.indexOf("k") > 0)
		return base * 1000;
		
	return base;
}

function linkCells()
{
	var cells = $("#list tr>td[aria-describedby='list_movie']");
	for (var i = 0; i < cells.length; i++)
	{
		var movie = cells.eq(i).parent().children().eq(0).html();
		var v = cells.eq(i).html();
		if (v != "")
		{
			v = "<a href=\"#\" onclick=\"popupDetails(&quot;details.php?movie=" + movie + "&quot;);return false;\">" + v + "</a>";
			cells.eq(i).html(v);
		}
	}
}

$(function(){ 
  $("#list").jqGrid({
    datatype: 'local',
    colNames:['Movie', 'Rel.', <?php echo $colHeaders; ?> 'Total', 'Value'],
    colModel :[ 
	  {name:'movie', index:'movie', width:150},
	  {name:'releasedate', index:'releasedate', width:40},
	  <?php echo $colModels; ?>
      {name:'total', align:'right', index:'total', sorttype:sortMoney, width:50}, 
	  {name:'value', align:'right', index:'value', sorttype:sortMoney, width:50}
    ],
    rowNum:100,
	data: <?php echo $output; ?>,
    sortname: 'releasedate',
    sortorder: 'asc',
    viewrecords: true,
    gridview: true,
	footerrow : true,
	userData : <?php echo $sumsOutput; ?>,
	userDataOnFooter : true,	
    caption: 'Scoreboard',
	height:'auto',
	gridComplete: linkCells
  }); 
  
    $("#shares").jqGrid({
    datatype: 'local',
    colNames:['Movie', 'Rel.', <?php echo $colHeaders; ?> 'Total'],
    colModel :[ 
	  {name:'movie', index:'movie', width:150},
	  {name:'releasedate', index:'releasedate', width:40},
	  <?php echo $colModelsShares; ?>
      {name:'total', align:'right', index:'total', sorttype:'int', width:50} 
    ],
    rowNum:100,
	data: <?php echo $sharesOutput; ?>,
    sortname: 'releasedate',
    sortorder: 'asc',
	height:'auto',
    viewrecords: true,
    gridview: true,
    caption: 'Share Distribution'
  });

    $("#tblStandings").jqGrid({
    datatype: 'local',
    colNames:['Rank', 'Player', 'Revenue'],
    colModel :[ 
	  {name:'rank', index:'rank', sorttype:'int', width:50},
	  {name:'player', index:'player', width:200},
      {name:'revenue', align:'right', index:'revenue', width:200} 
    ],	
    rowNum:30,
	data: <?php echo $standingsOutput; ?>,
    sortname: 'rank',
    sortorder: 'asc',
	height:'auto',
    viewrecords: true,
    gridview: true,
    caption: 'Standings'
  });
  
}); 

function popupDetails(url)
{
	$("#iframeDialog").attr("src", "about:blank");
	$("#iframeDialog").attr("src", url);
	$("#dialog-modal").dialog("open");
	return false;
}

</script>

<h1 style="text-align:center;font-family:'Segoe UI','Helvetica','Arial'">2013 Summer Movie Contest</h1>
<h2 style="text-align:center;font-family:'Segoe UI','Helvetica','Arial'">
	View:
	<?php if (!empty($team)) {?>
	[ <a href="index.php">All</a> ] 
	<?php } ?>
	<?php if ($team != "Friends") {?>
	[ <a href="index.php?team=Friends">Friends</a> ] 
	<?php } ?>
	<?php if ($team != "Work") {?>
	[ <a href="index.php?team=Work">Work</a> ] 
	<?php } ?>

</h2>
<center><div id="standings"><table id="tblStandings"><tr><td/></tr></table></div>

<h3 style="text-align:center;font-family:'Segoe UI','Helvetica','Arial'">
	<?php 
	if ($team == "Friends" || $team == "Work") {
		echo "[ <a href=\"#\" onclick=\"return popupDetails('weekly.php?team=$team');\">Weekly Totals</a> ] ";
		echo "[ <a href=\"#\" onclick=\"return popupDetails('weekly.php?rank&team=$team');\">Weekly Rankings</a> ] ";
	}
	else
	{
		echo "[ <a href=\"#\" onclick=\"return popupDetails('weekly.php');\">Weekly Totals</a> ] ";
		echo "[ <a href=\"#\" onclick=\"return popupDetails('weekly.php?rank')\">Weekly Rankings</a> ] ";
	}
	?>
</h3>

<hr/><table id="list"><tr><td/></tr></table><br/><hr/><br/><table id="shares"><tr><td/></tr></table></center>
 	<script type="text/javascript">
 	    $(function () {
 	        $("#dialog-modal").dialog({
 	            height: 500,
 	            width: 900,
 	            modal: true,
 	            autoOpen: false,
 	            resizable: false,
 	            close: function () { $("#iframeDialog").attr("src", "about:blank"); }
 	        });
 	    });
	</script>
    <div id="dialog-modal" title="More Information">
	    <iframe id="iframeDialog" src="about:blank" frameborder="0" width="860" height="420"></iframe>
    </div>
	<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-347103-3']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
  <p style="text-align:center;"><a href="https://github.com/snickroger/movie_draft" target="_blank"><img src="img/github.png" border=0 ></a></p>
  </body>
</html>
