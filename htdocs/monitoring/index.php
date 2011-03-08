<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/monitoring/index.php
 *      \ingroup    monitoring
 *      \brief      Page to setup module Monitoring
 *		\version    $Id: index.php,v 1.5 2011/03/08 23:52:19 eldy Exp $
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
dol_include_once("/monitoring/lib/monitoring.lib.php"); // We still use old writing to be compatible with old version
dol_include_once("/monitoring/class/monitoring_probes.class.php"); // We still use old writing to be compatible with old version


if (!$user->rights->monitoring->read)
accessforbidden();


$langs->load("admin");
$langs->load("monitoring@monitoring");
$langs->load("other");

$def = array();
$action=GETPOST("action");
$actionsave=GETPOST("save");
$id=GETPOST('id');

$fname = $conf->monitoring->dir_output."/".$id."/monitoring.rrd";
$fileimage[0]=$id.'/monitoring-1h.png';
$fileimage[1]=$id.'/monitoring-1d.png';
$fileimage[2]=$id.'/monitoring-1w.png';
$fileimage[3]=$id.'/monitoring-1m.png';
$fileimage[4]=$id.'/monitoring-1y.png';


/*
 * Actions
 */

// Save parameters
if ($actionsave)
{
	$error=0;
	$i=0;

	$db->begin();

	/*    if (! preg_match('|[\\\/]$|',$_POST["RRD_COMMANDLINE_TOOL"]))
	 {
	 $mesg="<div class=\"error\">".$langs->trans("ErrorRrdDataDirMustEndWithASlash")."</div>";
	 $error++;
	 }
	 */
	if (! $error)
	{
		if ($i >= 0) $i+=dolibarr_set_const($db,'MONITORING_COMMANDLINE_TOOL',trim($_POST["MONITORING_COMMANDLINE_TOOL"]),'chaine',0);

		if ($i >= 1)
		{
			$db->commit();
			$mesg = "<div class=\"ok\">".$langs->trans("SetupSaved")."</div>";
		}
		else
		{
			$db->rollback();
			$mesg=$db->lasterror();
			//header("Location: ".$_SERVER["PHP_SELF"]);
			//exit;
		}
	}
}

if ($action == 'create')
{
	$error=0;
	create_exdir($conf->monitoring->dir_output.'/'.$id);

	$step=5;
	$opts = array( "--step", $step,
           "DS:ds1:GAUGE:".($step*2).":0:100",
           "DS:ds2:GAUGE:".($step*2).":0:100",
           "RRA:AVERAGE:0.5:1:".(3600/$step),
	           "RRA:AVERAGE:0.5:".(60/$step).":1440",
	           "RRA:AVERAGE:0.5:".(3600/$step).":168",
	           "RRA:AVERAGE:0.5:".(3600/$step).":744",
	           "RRA:AVERAGE:0.5:".(86400/$step).":365",
           	   "RRA:MAX:0.5:1:".(3600/$step),
	           "RRA:MAX:0.5:".(60/$step).":1440",
	           "RRA:MAX:0.5:".(3600/$step).":168",
	           "RRA:MAX:0.5:".(3600/$step).":744",
	           "RRA:MAX:0.5:".(86400/$step).":365",
	           "RRA:MIN:0.5:1:".(3600/$step),
	           "RRA:MIN:0.5:".(60/$step).":1440",
	           "RRA:MIN:0.5:".(3600/$step).":168",
	           "RRA:MIN:0.5:".(3600/$step).":744",
	           "RRA:MIN:0.5:".(86400/$step).":365",
	);

	$ret = rrd_create($fname, $opts, count($opts));
	$resout=file_get_contents($fname.'.out');
	if (strlen($resout) < 10)
	{
		$mesg='<div class="ok">'.$langs->trans("File ".$fname.' created').'</div>';
		$action='graph';	// To rebuild graph
	}
	else
	{
		$error++;
		$err = rrd_error($fname);
		$mesg="Create error: $err\n";
	}
}

if ($action == 'update')
{
	$error=0;
	$val1=rand(0,100);
	$val2=25;
	$ret = rrd_update($fname, "N:$val1:$val2");

	if( $ret > 0)
	{
		$mesg='<div class="ok">'.$langs->trans("File ".$fname.' completed with random values '.$val1.' for graph 1 and '.$val2.' for graph 2').'</div>';
	}
	else
	{
		$error++;
		$err = rrd_error($fname);
		$mesg="Update error: $err\n";
	}
}

if ($action == 'graph')
{
	$error=0;
	$mesg='';

	$newfname=preg_replace('/^[a-z]:/i','',$fname);	// Removed C:, D: for windows path to avoid error in def string

	// Hour graph
	$opts = array(
			'--start','-1h',
			"--vertical-label=ms",
           "DEF:ds1=\"".$newfname."\":ds1:AVERAGE",
           "DEF:ds2=\"".$newfname."\":ds2:AVERAGE",
		   "LINE1:ds1#0000FF:Graph1",
		   "LINE1:ds2#FF0000:Errors",
 		   "CDEF:cdef1=ds1,1,*",
           "CDEF:cdef2=ds2,1,*",
	       'COMMENT:\\\n ',
	       "GPRINT:cdef1:MIN:Minval1%8.2lf ",
	       "GPRINT:cdef1:AVERAGE:Avgval1%8.2lf ",
		   "GPRINT:cdef1:MAX:Maxval1%8.2lf ",
		   'COMMENT:\\\n ',
		   "GPRINT:cdef2:MIN:Minval2%8.2lf ",
	       "GPRINT:cdef2:AVERAGE:Avgval2%8.2lf ",
		   "GPRINT:cdef2:MAX:Maxval2%8.2lf ",
	       'COMMENT:\\\n '
	       );
	       $ret = rrd_graph($conf->monitoring->dir_output.'/'.$fileimage[0], $opts, count($opts));
	       $resout=file_get_contents($conf->monitoring->dir_output.'/'.$fileimage[0].'.out');
	       if (strlen($resout) < 10)
	       {
	       	$mesg.='<div class="ok">'.$langs->trans("File ".$fileimage[0].' created').'</div>';
	       }
	       else
	       {
	       	$error++;
	       	$err = rrd_error($conf->monitoring->dir_output.'/'.$fileimage[0]);
	       	$mesg.="Graph error: $err\n";
	       }

	       // Day graph
	       $opts = array(
			'--start','-1d',
			"--vertical-label=ms",
           "DEF:ds1=\"".$newfname."\":ds1:AVERAGE",
           "DEF:ds2=\"".$newfname."\":ds2:AVERAGE",
		   "LINE1:ds1#0000FF:Graph1",
		   "LINE1:ds2#FF0000:Errors",
 		   "CDEF:cdef1=ds1,1,*",
           "CDEF:cdef2=ds2,1,*",
	       'COMMENT:\\\n ',
	       "GPRINT:cdef1:MIN:Minval1%8.2lf ",
	       "GPRINT:cdef1:AVERAGE:Avgval1%8.2lf ",
		   "GPRINT:cdef1:MAX:Maxval1%8.2lf ",
		   'COMMENT:\\\n ',
		   "GPRINT:cdef2:MIN:Minval2%8.2lf ",
	       "GPRINT:cdef2:AVERAGE:Avgval2%8.2lf ",
		   "GPRINT:cdef2:MAX:Maxval2%8.2lf ",
	       'COMMENT:\\\n '
	       );
	       $ret = rrd_graph($conf->monitoring->dir_output.'/'.$fileimage[1], $opts, count($opts));
	       $resout=file_get_contents($conf->monitoring->dir_output.'/'.$fileimage[1].'.out');
	       if (strlen($resout) < 10)
	       {
	       	$mesg.='<div class="ok">'.$langs->trans("File ".$fileimage[1].' created').'</div>';
	       }
	       else
	       {
	       	$error++;
	       	$err = rrd_error($conf->monitoring->dir_output.'/'.$fileimage[1]);
	       	$mesg.="Graph error: $err\n";
	       }

	       // Week graph
	       $opts = array(
			'--start','-1w',
			"--vertical-label=ms",
           "DEF:ds1=\"".$newfname."\":ds1:AVERAGE",
           "DEF:ds2=\"".$newfname."\":ds2:AVERAGE",
		   "LINE1:ds1#0000FF:Graph1",
		   "LINE1:ds2#FF0000:Errors",
 		   "CDEF:cdef1=ds1,1,*",
           "CDEF:cdef2=ds2,1,*",
	       'COMMENT:\\\n ',
	       "GPRINT:cdef1:MIN:Minval1%8.2lf ",
	       "GPRINT:cdef1:AVERAGE:Avgval1%8.2lf ",
		   "GPRINT:cdef1:MAX:Maxval1%8.2lf ",
		   'COMMENT:\\\n ',
		   "GPRINT:cdef2:MIN:Minval2%8.2lf ",
           "GPRINT:cdef2:AVERAGE:Avgval2%8.2lf ",
		   "GPRINT:cdef2:MAX:Maxval2%8.2lf ",
	       'COMMENT:\\\n '
	       );
	       $ret = rrd_graph($conf->monitoring->dir_output.'/'.$fileimage[2], $opts, count($opts));
	       $resout=file_get_contents($conf->monitoring->dir_output.'/'.$fileimage[2].'.out');
	       if (strlen($resout) < 10)
	       {
	       	$mesg.='<div class="ok">'.$langs->trans("File ".$fileimage[2].' created').'</div>';
	       }
	       else
	       {
	       	$error++;
	       	$err = rrd_error($conf->monitoring->dir_output.'/'.$fileimage[2]);
	       	$mesg.="Graph error: $err\n";
	       }

	       // Month graph
	       $opts = array(
			'--start','-1m',
			"--vertical-label=ms",
           "DEF:ds1=\"".$newfname."\":ds1:AVERAGE",
           "DEF:ds2=\"".$newfname."\":ds2:AVERAGE",
 		   "LINE1:ds1#0000FF:Graph1",
		   "LINE1:ds2#FF0000:Errors",
 		   "CDEF:cdef1=ds1,1,*",
           "CDEF:cdef2=ds2,1,*",
	       'COMMENT:\\\n ',
	       "GPRINT:cdef1:MIN:Minval1%8.2lf ",
	       "GPRINT:cdef1:AVERAGE:Avgval1%8.2lf ",
		   "GPRINT:cdef1:MAX:Maxval1%8.2lf ",
		   'COMMENT:\\\n ',
		   "GPRINT:cdef2:MIN:Minval2%8.2lf ",
	       "GPRINT:cdef2:AVERAGE:Avgval2%8.2lf ",
		   "GPRINT:cdef2:MAX:Maxval2%8.2lf ",
	       'COMMENT:\\\n '
	       );
	       $ret = rrd_graph($conf->monitoring->dir_output.'/'.$fileimage[3], $opts, count($opts));
	       $resout=file_get_contents($conf->monitoring->dir_output.'/'.$fileimage[3].'.out');
	       if (strlen($resout) < 10)
	       {
	       	$mesg.='<div class="ok">'.$langs->trans("File ".$fileimage[3].' created').'</div>';
	       }
	       else
	       {
	       	$error++;
	       	$err = rrd_error($conf->monitoring->dir_output.'/'.$fileimage[3]);
	       	$mesg.="Graph error: $err\n";
	       }

	       // Year graph
	       $opts = array(
			'--start','-1y',
			"--vertical-label=ms",
           "DEF:ds1=\"".$newfname."\":ds1:AVERAGE",
           "DEF:ds2=\"".$newfname."\":ds2:AVERAGE",
		   "LINE1:ds1#0000FF:Graph1",
		   "LINE1:ds2#FF0000:Errors",
 		   "CDEF:cdef1=ds1,1,*",
           "CDEF:cdef2=ds2,1,*",
	       'COMMENT:\\\n ',
	       "GPRINT:cdef1:MIN:Minval1%8.2lf ",
	       "GPRINT:cdef1:AVERAGE:Avgval1%8.2lf ",
		   "GPRINT:cdef1:MAX:Maxval1%8.2lf ",
		   'COMMENT:\\\n ',
		   "GPRINT:cdef2:MIN:Minval2%8.2lf ",
	       "GPRINT:cdef2:AVERAGE:Avgval2%8.2lf ",
		   "GPRINT:cdef2:MAX:Maxval2%8.2lf ",
	       'COMMENT:\\\n '
	       );
	       $ret = rrd_graph($conf->monitoring->dir_output.'/'.$fileimage[4], $opts, count($opts));
	       $resout=file_get_contents($conf->monitoring->dir_output.'/'.$fileimage[4].'.out');
	       if (strlen($resout) < 10)
	       {
	       	$mesg.='<div class="ok">'.$langs->trans("File ".$fileimage[4].' created').'</div>';
	       }
	       else
	       {
	       	$error++;
	       	$err = rrd_error($conf->monitoring->dir_output.'/'.$fileimage[4]);
	       	$mesg.="Graph error: $err\n";
	       }


	       if (! $error) $mesg='';
}



/**
 * View
 */

llxHeader('','Monitoring',$linktohelp);

print_fiche_titre($langs->trans("Reports"));
print '<br>';

if ($mesg) print "<br>$mesg<br>";

if (empty($id))
{
	print $langs->trans("ErrorFieldRequired",'id');
}
else
{
    $probe=new Monitoring_probes($db);
    $result=$probe->fetch($id);

    //print $langs->trans("ReportForProbeX");
    print $langs->trans("Id").': '.$probe->id.'<br>'."\n";
    print $langs->trans("Title").': '.$probe->title.'<br>'."\n";
    print $langs->trans("Url").': <a href="'.$probe->url.'">'.$probe->url.'</a><br>'."\n";
    print $langs->trans("CheckKey").': '.$probe->checkkey.'<br>'."\n";
    print $langs->trans("Frequency").': '.$probe->frequency.'<br>'."\n";
    print $langs->trans("Status").': '.$probe->status.'<br>'."\n";
    print $langs->trans("RrdFile").': '.$conf->monitoring->dir_output."/".$id.'/monitoring.rrd<br>'."\n";
    print '<br>';
    print '<hr>';

	if ($conf->global->MONITORING_COMMANDLINE_TOOL)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=graph&id='.$probe->id.'">'.$langs->trans("Refresh").'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#">'.$langs->trans("Refresh").'</a>';
	}

	print '<br><br>';

	print '<div class="float">';
	print $langs->trans("LastHour").'<br>';
	if (dol_is_file($conf->monitoring->dir_output."/".$fileimage[0])) print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=monitoring&file='.$fileimage[0].'">';
    else print 'PngFileNotAvailable<br>';
    print '</div>'."\n";
    print '<div class="float">';
    print $langs->trans("LastDay").'<br>';
	if (dol_is_file($conf->monitoring->dir_output."/".$fileimage[1])) print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=monitoring&file='.$fileimage[1].'">';
    else print 'PngFileNotAvailable<br>';
    print '</div>'."\n";
    print '<div class="float">';
	print $langs->trans("LastWeek").'<br>';
	if (dol_is_file($conf->monitoring->dir_output."/".$fileimage[2])) print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=monitoring&file='.$fileimage[2].'">';
    else print 'PngFileNotAvailable<br>';
    print '</div>'."\n";
    print '<div class="float">';
	print $langs->trans("LastMonth").'<br>';
	if (dol_is_file($conf->monitoring->dir_output."/".$fileimage[3])) print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=monitoring&file='.$fileimage[3].'">';
    else print 'PngFileNotAvailable<br>';
    print '</div>'."\n";
    print '<div class="float">';
	print $langs->trans("LastYear").'<br>';
	if (dol_is_file($conf->monitoring->dir_output."/".$fileimage[4])) print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=monitoring&file='.$fileimage[4].'">';
    else print 'PngFileNotAvailable<br>';
    print '</div>'."\n";
}


$db->close();

llxFooter('$Date: 2011/03/08 23:52:19 $ - $Revision: 1.5 $');
?>
