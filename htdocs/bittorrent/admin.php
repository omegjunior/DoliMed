<?php

include("./pre.inc.php");
require ("funcsv2.php");

$tracker_url = $website_url . substr($_SERVER['REQUEST_URI'], 0, -15) . "announce.php";

llxHeader('','BitTorrent',$website_url.'/bittorrent/docs/help.html');

$form=new Form($db);

print_fiche_titre('River BitTorrent admin page');

?>
<br>

<table class="border" width="100%"><tr><td valign="top">
<a href="newtorrents.php"><img src="images/add.png" border="0" class="icon" alt="Add Torrent" title="Add Torrent" /></a><a href="newtorrents.php">Add Torrent to tracker database</a><br>
<a href="batch_upload.php"><img src="images/batch_upload.png" border="0" class="icon" alt="Batch Upload Torrents" title="Batch Upload Torrents" /></a><a href="batch_upload.php">Add Torrents to Tracker database (batch mode)</a><br>
<a href="edit_database.php"><img src="images/database.png" border="0" class="icon" alt="Edit Torrent in Database" title="Edit Torrent in Database" /></a><a href="edit_database.php">Edit Torrent Already in Database</a><br>
<a href="deleter.php"><img src="images/delete.png" border="0" class="icon" alt="Delete Torrent" title="Delete Torrent" /></a><a href="deleter.php">Delete Torrent from Tracker Database</a><br>
</td></tr></table>
<br>
<table class="border" width="100%"><tr><td valign="top">
<a href="index.php"><img src="images/download.png" border="0" class="icon" alt="Tracker Statistics" title="Tracker Statistics" /></a><a href="index.php">Show current Tracker Statistics</a><br>
<a href="uploadstats.php"><img src="images/download.png" border="0" class="icon" alt="Upload Statistics" title="Upload Statistics" /></a><a href="uploadstats.php">Show upload Statistics</a><br>
<a href="statistics.php"><img src="images/userstats.png" border="0" class="icon" alt="User Statistics" title="User Statistics" /></a><a href="statistics.php">Detailed User Statistics from Tracker</a><br>
<a href="sanity.php"><img src="images/check.png" border="0" class="icon" alt="Check for Expired Peers" title="Check for Expired Peers" /></a><a href="sanity.php">Check Tracker for Expired Peers</a><br>
</td></tr></table>
<br>
<table class="border" width="100%"><tr><td valign="top">
<a href="DumpTorrentCGI.php"><img src="images/torrent.png" border="0" class="icon" alt="Show Information on Torrent" title="Show Information on Torrent" /></a><a href="DumpTorrentCGI.php">Show Information on a Torrent File</a><br>
<a href="editconfig.php"><img src="images/edit.png" border="0" class="icon" alt="View Config File" title="View Config File" /></a><a href="editconfig.php">View Configuration Settings</a><br>
</td></tr></table>

<?php
//Check for install.php file, security risk if still available
if (file_exists("install.php"))
{
	echo errorMessage() . "Your install.php file has NOT been deleted.  This is a security risk, please delete it immediately.</p>\n";
}

llxFooter('$Date: 2009/03/03 19:39:21 $ - $Revision: 1.4 $');
?>
