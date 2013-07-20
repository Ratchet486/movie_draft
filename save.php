<?php
	include "db.php";
	
	$data = json_decode(stripslashes($_POST['data']), true);
	$contestant = $data['NAME'];

	if (!$contestant)
		die("No name");
	
	$sql = connect();
	
	$sql->query(sprintf('DELETE FROM `shares` WHERE `Contestant`="%s"', $contestant));
	$sum = 0;
	while (list($movie, $shares) = each($data))
	{
		if ($movie != "NAME")
		{
			if ($shares < 0 || $shares > 100)
				die("Bad value: $shares");
			$query .= sprintf('INSERT INTO shares (Contestant,Movie,Shares) VALUES("%s","%s",%d);'."\n", $contestant, $movie, $shares);
			$sum += $shares;
		}
	}

	if ($sum <= 100)
	{
		if ($sql->multi_query($query))
		{
			header("Location: index.htm?saved");
		}
		else
		{
			echo $sql->error;
		}
	}
?>