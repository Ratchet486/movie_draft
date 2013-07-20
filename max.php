<?php
	include "db.php";
	
	$sql = connect();
	$totalsResult = $sql->query("SELECT `Movie`, MAX(`Gross`) AS 'Total' FROM `earnings` GROUP BY `Movie`");
	$before = array();
	
	while ($a = $totalsResult->fetch_array())
	{
		$before[$a[0]]['Gross'] = $a[1];
	}
	
	$playerOptions = getPlayerOptions($sql, $_GET['player']);
	$playerSQL = "1";
	
	if (!empty($_GET['player']))
		$playerSQL = sprintf("`shares`.`Contestant` NOT LIKE '%s'", $sql->real_escape_string(urldecode($_GET['player'])));
	
	$teamOptions = getTeamOptions($sql, $_GET['team']);
	$teamSQL = "";
	if ($_GET['team'] == "Work")
		$teamSQL = "`teams`.`TeamName` = 'Work' AND ";
	if ($_GET['team'] == "Friends")
		$teamSQL = "`teams`.`TeamName` = 'Friends' AND ";
	
	$SQL = "SELECT `Movie`, SUM(`Shares`) FROM `shares` INNER JOIN `teams` ON `shares`.`Contestant` = `teams`.`Contestant` WHERE $teamSQL $playerSQL GROUP BY `Movie`";
	
	$sharesResult = $sql->query($SQL);
	echo $sql->error;
	
	while ($a = $sharesResult->fetch_array())
	{
		if ($before[$a[0]]['Gross'] == 0) continue;
		$before[$a[0]]['Shares'] = $a[1];
		$before[$a[0]]['Value'] = $before[$a[0]]['Gross']/$before[$a[0]]['Shares'];
	}
	
	$after = $before;
	
	$sharesRemaining = 100;
	while ($sharesRemaining > 0)
	{
		$k = getGreatestValueKey($after);
		$after[$k]['Shares']++;
		$after[$k]['Value'] = $after[$k]['Gross']/$after[$k]['Shares'];
		$sharesRemaining--;
	}
	
	$total = 0;
	foreach ($after as $movie => $arr)
	{
		$diff[$movie] = $after[$movie]['Shares'] - $before[$movie]['Shares'];
		$newPct = $diff[$movie] / ( $before[$movie]['Shares'] + $diff[$movie] );
		$total += $newPct * $before[$movie]['Gross'];
	}
	
	function getGreatestValueKey($x)
	{
		$max = 0;
		$maxKey = null;
		foreach($x as $movie => $arr)
		{
			if ($arr['Value'] > $max)
			{
				$max = $arr['Value'];
				$maxKey = $movie;
			}
		}
		
		return $maxKey;
		
	}
	
	function getPlayerOptions($sql, $a)
	{
		$ret = "";
		$optResult = $sql->query("SELECT DISTINCT `Contestant` FROM `shares` ORDER BY `Contestant`");
		$found = false;
		while ($b = $optResult->fetch_array())
		{
			if ($b[0] == urldecode($a))
			{
				$ret .= "<option selected=\"selected\">${b[0]}</option>";
				$found = true;
			}
			else
				$ret .= "<option>${b[0]}</option>";
		}
		
		if (!$found)
			$ret = "<option selected=\"selected\" value=\"\">(Any)</option>$ret";
		else
			$ret = "<option value=\"\">(Any)</option>$ret";
		
		return $ret;
	}
	
	function getTeamOptions($sql, $a)
	{
		$ret = "";
		$teams = array("All", "Friends", "Work");
		
		foreach ($teams as $t)
		{
			if ($t == urldecode($a))
				$ret .= "<option selected=\"selected\">$t</option>";
			else
				$ret .= "<option>$t</option>";
		}
		
		return $ret;
		
	}
	
?>

<html>
<head>
	<title>Max</title>
</head>
<body>
	<form method="get" target="max.php">
		<p>Player: <select name="player"><?php echo $playerOptions; ?></select></p>
		<p>Team: <select name="team"><?php echo $teamOptions; ?></select></p>
		<p><input type="submit" value = "Submit" /></p>
	</form>
	<pre><?php echo print_r($diff,true); ?></pre>
	<p>Total: $<?php echo number_format(round($total,2)); ?></p>
</body>
</html>