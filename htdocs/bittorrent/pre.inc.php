<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * Licensed under the GNU GPL v3 or higher (See file gpl-3.0.html)
 */

/**
		\file 		htdocs/bittorrent/pre.inc.php
		\ingroup    bittorrent
		\brief      File to manage left menu for bittorrent module
		\version    $Id: pre.inc.php,v 1.1 2009/02/23 22:54:51 eldy Exp $
*/

$res=@include("../main.inc.php");
if (! $res) @include("../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only

global $website_url;
require_once ("./config.php");


$user->getrights('bittorrent');


function llxHeader($head = "", $title="", $help_url='')
{
	global $conf,$langs;
	$langs->load("other");

	top_menu($head, $title);

	$menu = new Menu();

	//$menu->add(DOL_URL_ROOT."/awstats/index.php?mainmenu=awstats&idmenu=".$_SESSION["idmenu"], $langs->trans("AWStats"));

	left_menu($menu->liste, $help_url);
}
?>
