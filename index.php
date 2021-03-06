<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<link rel="stylesheet" href="images/Envision.css" type="text/css" />
		<title>Minecraft "Adventuring Time" Achievement Helper</title>
	</head>
	<body>
	<!-- wrap starts here -->
		<div id="wrap">
			<!--header -->
			<div id="header">
				<h1 id="logo-text"><a>Minecraft "Adventuring Time" Achievement Helper</a></h1>
				<p id="slogan">Time to explore your world!</p>
			</div>
			<!-- content-wrap starts here -->
			<div id="content-wrap">
				<div id="sidebar">
					<h3>Details</h3>
					<p>
						You need to find your <tt>.minecraft</tt> folder. See the page on
						<a href="http://minecraft.gamepedia.com/.minecraft">the minecraft wiki</a>
						for more details.
					</p>
				</div>
				<div id="main">

<?php
// If there is no POST data, show the form to upload the file
if (!isset($_POST['stat']))
{
?>

                <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="stat" value="stat" />
                        <p>Upload a stats file from <tt>.minecraft/worlds/<i>[yourWorld]</i>/stats/</tt>.</p>
                        <p>There is a file per user in the directory - file names are user IDs - if you need to
                                look up your user ID, try <a href="//mcuuid.net" target="_new">mcuuid.net</a>.</p>
                        <p><input type="file" name="statFile" /></p>
                        <p><input class="button" type="submit" value="Analyse Stats" /></p>
                </form>
<?php 
} else {
	// Check the file uploaded correctly
	if (is_uploaded_file($_FILES['statFile']['tmp_name']))
	{
		$mcStats = new mcStats($_FILES['statFile']['tmp_name']);

		// Try to get the minecraft username associated with this file
		$name = $mcStats->getUserName($_FILES['statFile']['name']);
?>
		<h2>Biomes Explored <?=$name ? 'by '.$name : '' ?></h2>
		<table id="biomes">
			<tr>
				<td>
					<h3><?=count($mcStats->biomesFound) ?> biome<?=count($mcStats->biomesFound) > 1 ? 's' : '' ?> found:</h3>
					<ul>
<?php		foreach($mcStats->biomesFound AS $b):	?>
						<li><?=$b ?></li>
<?php		endforeach;	?>
					</ul>
				</td><td>
                                        <h3><?=count($mcStats->biomes) ?> still to find:</h3>
                                        <ul>
<?php           foreach($mcStats->biomes AS $b):   ?>
                                                <li><?=$b ?></li>
<?php           endforeach;     ?>
                                        </ul>
<?php		if (count($mcStats->unknownBiomes)): ?>
				</td><td>
                                        <h3><?=count($mcStats->unknownBiomes) ?> that <?=count($mcStats->unknownBiomes) == 1 ? 'doesn\'t' : 'don\'t' ?> count:</h3>
                                        <ul>
<?php			foreach($mcStats->unknownBiomes AS $b):   ?>
                                                <li><?=$b ?></li>
<?php			endforeach;     ?>
                                        </ul>
<?php		endif;	?>
                                </td>
			</tr>
		</table>

		<hr />
		<form method="get">
			<input class="button" type="submit" value="Back" />
		</form>
<?php
	} // if file uploaded successfully
} // if file uploaded or not

?>
				</div>
			<!-- content-wrap ends here -->
			</div>

			<!--footer starts here-->
			<div id="footer">
				<p>
					<a href="https://github.com/djomp/minecraft-biomes">https://github.com/djomp/minecraft-biomes</a>
				</p>
			</div>
		<!-- wrap ends here -->
		</div>
	</body>

</html>


<?php

/**
 * mcStats holds the info for processing the file and the biomes
 */
class mcStats {
	
	// URL to use to lookup usernames
	private $userLookup = 'https://sessionserver.mojang.com/session/minecraft/profile/';

	// List of Biomes required for the "Adventuring Time" achievement
	// Will also be the list of biomes left to find once the file is processed
	public $biomes = ['Beach','Birch Forest','Birch Forest Hills','Cold Beach',
		'Cold Taiga','Cold Taiga Hills','Deep Ocean','Desert','DesertHills',
		'Extreme Hills','Extreme Hills+','Forest','ForestHills','FrozenRiver',
		'Ice Mountains','Ice Plains','Jungle','JungleEdge','JungleHills',
		'Mega Taiga','Mega Taiga Hills','Mesa','Mesa Plateau','Mesa Plateau F',
		'MushroomIsland','MushroomIslandShore','Ocean','Plains','River',
		'Roofed Forest','Savanna','Savanna Plateau','Stone Beach','Swampland',
		'Taiga','TaigaHills'];

	// Arrays of biomes for output
	public $biomesFound, $unknownBiomes = [];

	private $stats;

	/**
	 * Initialise the class; get the biome information out of the file
	 * @param string $filePath Path to the uploaded file
	 */
	public function __construct($filePath)
	{
		$this->stats = json_decode(file_get_contents($filePath));
		// Process biomes
		if ($this->stats)
		{
			foreach($this->stats->{'achievement.exploreAllBiomes'}->{'progress'} AS $biome)
			{
				if (in_array($biome, $this->biomes))
				{
					// This is a biome that's required
					// Remove this biome from the list of biomes required
					unset($this->biomes[array_search($biome, $this->biomes)]);
					// and add it to the list of biomes found
					$this->biomesFound[] = $biome;
				} else {
					// Not required for the achievement - add to list of other biomes
					$this->unknownBiomes[] = $biome;
				}
			}
			sort($this->biomes);
			sort($this->biomesFound);
			sort($this->unknownBiomes);
		}
	}

	/**
	 * Get the username associated with the given UID
	 * @param string $uid User ID / filename of stats file
	 */
	public function getUserName($uid)
	{
		// Clean the file name into a uuid we can look up
		$uid = str_replace(['-','.json'],'',$uid);
		$curl = curl_init();
		curl_setopt_array($curl,
			[CURLOPT_URL => $this->userLookup.$uid,
			CURLOPT_RETURNTRANSFER => true]);
		$result = curl_exec($curl);
		$jsonResult = json_decode($result);
		if ($jsonResult && $jsonResult->{'name'})
		{
			return $jsonResult->{'name'};
		} else {
			error_log('Minecraft Biomes: looking up user "'.$uid.'" failed');
			return NULL;
		}
	}
}
