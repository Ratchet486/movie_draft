<?php
	include "db.php";
	
	error_reporting(E_ERROR);
	$http = curl_init();
	$urls = array("http://boxofficemojo.com/seasonal/?page=1&view=releasedate&yr=2013&season=Summer&sort=open&order=DESC&p=.htm"
	,"http://boxofficemojo.com/seasonal/?page=2&view=releasedate&yr=2013&season=Summer&sort=open&order=DESC&p=.htm"
	,"http://boxofficemojo.com/seasonal/?page=3&view=releasedate&yr=2013&season=Summer&sort=open&order=DESC&p=.htm"
	);
	
	$sql = connect();
	$sql->query("DELETE FROM `earnings` WHERE `OnDate`=CURDATE()");
	
	$NOW = new DateTime('now', new DateTimeZone("America/New_York"));
	$START_DATE = new DateTime("2013-05-02 00:00:00", new DateTimeZone("America/New_York"));
	$END_DATE = new DateTime("2013-08-09 00:00:00", new DateTimeZone("America/New_York"));
	$SEASON_END_DATE = clone $END_DATE;
	$SEASON_END_DATE->modify("+28 day");

	echo "Now: " . $NOW->format(DateTime::RFC1123) . "<br/>";
	echo "First Movie Released: " . $START_DATE->format(DateTime::RFC1123) . "<br/>";
	echo "Last Movie Released: " . $END_DATE->format(DateTime::RFC1123) . "<br/>";
	echo "Season Ends: " . $SEASON_END_DATE->format(DateTime::RFC1123) . "<br/>";
	
	if ($NOW >= $SEASON_END_DATE)
		$urls = array();
	
	foreach ($urls as $u)
	{
		curl_setopt($http, CURLOPT_URL, $u);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($http);
		
		$doc = DOMDocument::loadHTML($ret);
		if ($doc)
		{
			$xpath = new DOMXPath($doc);
			$context = $xpath->query("//form[@name='MojoDropDown1']/ancestor::table[2]/tr");
			for ($i=1; $i < $context->length; $i++)
			{
				$row = $context->item($i);
				$movie = array(
					"name"  => $xpath->query("td[3]", $row)->item(0)->textContent,
					"gross" => intval(str_replace(array('$',','), "", $xpath->query("td[5]", $row)->item(0)->textContent)),
					"theaters" => intval(str_replace(",","",$xpath->query("td[6]", $row)->item(0)->textContent)),
					"open" => $xpath->query("td[9]", $row)->item(0)->textContent
				);
				
				$d = new DateTime();
				try
				{
					$d = new DateTime($movie["open"]);
				}
				catch(Exception $e)
				{
					continue;
				}
				
				// remap/reject movies here
				if ($movie['name'] == "The Great Gatsby (2013)")
					$movie['name'] = "The Great Gatsby";
					
				if ($movie['name'] == "Fast & Furious 6")
					$movie['name'] = "Fast and Furious 6";	

				if ($movie['name'] == "The Lone Ranger")
					$movie['name'] = "Lone Ranger";	
				
				if ($movie['name'] == "Tyler Perry Presents Peeples")
					continue;
					
				if ($movie['name'] == "The Purge")
					continue;
					
				if ($movie['name'] == "Before Midnight")
					continue;
					
				if ($movie['name'] == "The Bling Ring")
					continue;
					
				if ($movie['name'] == "Kevin Hart: Let Me Explain")
					continue;
					
				if ($movie['name'] == "The Kings of Summer")
					continue;
					
				if ($movie['name'] == "The Conjuring")
					continue;
					
				if ($movie['name'] == "The Way, Way Back")
					continue;

				if ($movie['name'] == "Fruitvale Station")
					continue;
				
				if ($d >= $START_DATE && $d < $END_DATE && ($movie['theaters'] >= 600))
				{
					$query = sprintf("INSERT INTO `earnings` VALUES(\"%s\", %d, \"%s\")", $movie['name'], $movie['gross'], $NOW->format("Y-m-d"));
					$sql->query($query);
					echo $query . "<br/>";
				}
			}
		}
	}
	echo "OK";
	
?>