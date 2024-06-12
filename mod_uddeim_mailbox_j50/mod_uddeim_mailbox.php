<?php
// ********************************************************************************************
// @title         udde Instant Messages (uddeIM)
// @description   Instant Messages System for Joomla 5
// @author        Stephan Slabihoud
// @copyright     © 2007-2024 Stephan Slabihoud, © 2024 v5 joomod.de
// @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
//                This program is free software: you may redistribute it and/or modify under the
//                terms of the GNU General Public License as published by the Free Software Foundation,
//                either version 3 of the License, or (at your option) any later version.
//
//                uddeIM is distributed in the hope to be useful but comes with absolutely NO WARRENTY.
//                You should have received a copy of the GNU General Public License along with this program.
//                Use at your own risk. For details, see the license at http://www.gnu.org/licenses/gpl.txt
//                Other licenses may be found in LICENSES folder.
//                Redistributing this file is only allowed when keeping the header unchanged.
// ********************************************************************************************

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

$uddeim_isadmin = 0;
if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
	require_once(JPATH_SITE.'/components/com_uddeim/uddeimlib50.php');
} else {
	global $mainframe;
	require_once($mainframe->getCfg('absolute_path').'/components/com_uddeim/uddeimlib50.php');
}

$uddpathtoadmin = uddeIMgetPath('admin');
$uddpathtouser  = uddeIMgetPath('user');
$uddpathtosite  = uddeIMgetPath('live_site');
$udddatabase 	= uddeIMgetDatabase();
$uddmosConfig_lang = uddeIMgetLang();

require_once($uddpathtoadmin."/admin.shared.php");		// before includes.php is included!
require_once($uddpathtouser.'/includes.php');
require_once($uddpathtouser.'/includes.db.php');
require_once($uddpathtouser.'/crypt.class.php');
require_once($uddpathtoadmin."/config.class.php");		// get the configuration file

$uddconfig = new uddeimconfigclass();

if(!defined('_UDDEIM_INBOX')) {
	uddeIMloadLanguage($uddpathtoadmin, $uddconfig);
}

$uddshownew		= $params->get( 'uddshownew', 1 );
$uddshowinbox	= $params->get( 'uddshowinbox', 1 );
$uddshowoutbox	= $params->get( 'uddshowoutbox', 1 );
$uddshowtrashcan= $params->get( 'uddshowtrashcan', 1 );
$uddshowarchive	= $params->get( 'uddshowarchive', 1 );
$uddshowcontacts= $params->get( 'uddshowcontacts', 1 );
$uddshowsettings= $params->get( 'uddshowsettings', 1 );
$uddshowcompose	= $params->get( 'uddshowcompose', 1 );
$uddshowicons	= $params->get( 'uddshowicons', 0 );

$usepostbox = ($uddshowinbox == 2 && $plugin=uddeIMcheckPlugin('postbox') && $uddconfig->enablepostbox) ? 1 : 0;

if ( defined( 'JPATH_ADMINISTRATOR' ) ) {	// this works in Joomla 1.5+
	if (file_exists($uddpathtouser.'/templates/'.$uddconfig->templatedir.'/css/uddemodule.css')) {
		$css = $uddpathtosite."/components/com_uddeim/templates/".$uddconfig->templatedir."/css/uddemodule.css";
		uddeIMaddCSS($css);
	} elseif(file_exists($uddpathtouser.'/templates/default/css/uddemodule.css')) {
		$css = $uddpathtosite."/components/com_uddeim/templates/default/css/uddemodule.css";
		uddeIMaddCSS($css);
	}
} else {
	if (file_exists($uddpathtouser.'/templates/'.$uddconfig->templatedir.'/css/uddemodule.css')) {
		echo '<link rel="stylesheet" href="'.$uddpathtosite.'/components/com_uddeim/templates/'.$uddconfig->templatedir.'/css/uddemodule.css" type="text/css" />';
	} elseif(file_exists($uddpathtouser.'/templates/default/css/uddemodule.css')) {
		echo '<link rel="stylesheet" href="'.$uddpathtosite.'/components/com_uddeim/templates/default/css/uddemodule.css" type="text/css" />';
	}
}

$udduserid    = uddeIMgetUserID();
$uddmygroupid = uddeIMgetGroupID();

if (!$udduserid) {
	echo "<div id='uddeim-module' class='uddmod'>";
	echo "<p class='uddeim-module-head'>"._UDDEIM_NOTLOGGEDIN."</p>";
	echo "</div>";
	return;
}

$uddmy_gid = uddeIMgetGID((int)$udduserid);	// ARRAY(!))

// first try to find a published link
$udditem_id = uddeIMgetItemid($uddconfig);

if ($uddshowicons > 1) {
$fa_inbox   = '<i class="fas fa-file-import"></i>&hairsp;&nbsp;';
$fa_outbox  = '<i class="fas fa-file-export"></i>&nbsp;';
$fa_postbox = '<i class="far fa-comments"></i>&nbsp;';
$fa_archive = '<i class="far fa-folder-open"></i>&hairsp;&nbsp;';
$fa_trash   = '<i class="far fa-trash-can"></i>&ensp;';
$fa_contact = '<i class="far fa-address-book"></i>&ensp;';
$fa_setting = '<i class="fas fa-user-gear"></i>&nbsp;';
$fa_compose = '<i class="fas fa-user-pen"></i>&nbsp;';
$fa_newmes  = '<i class="fas fa-star"></i>&nbsp;';

} elseif ($uddshowicons) {
//$iconpath = $uddpathtosite.'/components/com_uddeim/templates/'.$uddconfig->templatedir.'/images';
$iconpath = $uddpathtosite.'/components/com_uddeim/templates/default/images';
$iconout = "<img src='".$iconpath."/menu_outbox.gif' style='vertical-align:top;' />";
$iconin = "<img src='".$iconpath."/menu_inbox.gif' style='vertical-align:top;' />";
}

$uddout = "<div id='uddeim-module' class='uddmod'>";

if ( $uddshownew ) {
	$uddsql="SELECT count(a.id) FROM `#__uddeim` AS a WHERE `a`.`delayed`=0 AND a.totrash=0 AND a.toread=0 AND a.toid=".(int)$udduserid;

	$udddatabase->setQuery($uddsql);
	$uddresult=(int)$udddatabase->loadResult();
	if ($uddresult>0) {
		$uddout .= "<p class='uddeim-module-head'>";
        if ($uddshowicons)
        $uddout .= ($fa_newmes ?? '<img src="'.$iconpath.'/new_im.gif" alt="new" title="'._UDDEMODULE_NEWMESSAGES.'">').'&nbsp;';
		$uddout .= '<span>'._UDDEMODULE_NEWMESSAGES.': <b>'.$uddresult.'</b></span>'; //_UDDEMODULE_NEWMESSAGES." ".$uddresult;
		$uddout .= "</p>";
	}
}

if ( $uddshowinbox ) {
	$uddsql="SELECT count(a.id) FROM `#__uddeim` AS a WHERE `a`.`delayed`=0 AND a.totrash=0 AND archived=0 AND a.toid=".(int)$udduserid;

	$udddatabase->setQuery($uddsql);
	$uddresultin=(int)$udddatabase->loadResult();

    if (!$usepostbox){
	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_inbox ?? "<img src='".$iconpath."/menu_inbox.gif' alt='"._UDDEIM_INBOX."' />") : ''; 
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=inbox".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_INBOX.'">';
	$uddout .= _UDDEIM_INBOX.": ".$uddresultin;
	$uddout .= '</a>';
	$uddout .= "</p>";
}
}

if ( $uddshowoutbox || $usepostbox ) {
	$uddsql="SELECT count(a.id) FROM `#__uddeim` AS a WHERE a.totrashoutbox=0 AND ((a.systemmessage IS NULL) OR (a.systemmessage='')) AND a.fromid=".(int)$udduserid;

	$udddatabase->setQuery($uddsql);
	$uddresultout=(int)$udddatabase->loadResult();

    if (!$usepostbox){
	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_outbox ?? "<img src='".$iconpath."/menu_outbox.gif' alt='"._UDDEIM_OUTBOX."' />") : '';
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=outbox".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_OUTBOX.'">';
	$uddout .= _UDDEIM_OUTBOX.": ".$uddresultout;
	$uddout .= '</a>';
	$uddout .= "</p>";
}
}

if ($usepostbox){
    $uddout .= "<p class='uddeim-module-body'>";
    $uddout .= $uddshowicons ? ($fa_postbox ?? "<img src='".$iconpath."/menu_postbox.gif' alt='"._UDDEIM_POSTBOX."' style='margin-left:-4px;' /> ") : ""; 
    $uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=postbox".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_POSTBOX.'">';
    $uddout .= _UDDEIM_POSTBOX."<span style='display: block;font-variant-position:super;text-align:center;margin-bottom:-8px;'>".($uddshowicons==1 ? $iconin : '&darr;')."&thinsp;".$uddresultin."&ensp;".($uddshowicons==1 ? $iconout : '&uarr;')."&thinsp;".$uddresultout."</span>";
    $uddout .= '</a>';
	$uddout .= "</p>";
}

if ( $uddshowtrashcan ) {
	$uddrightnow=uddetime((int)$uddconfig->timezone);
	$uddoffset=((float)$uddconfig->TrashLifespan) * 86400;
	$uddtimeframe=$uddrightnow-$uddoffset;

	$uddsql="SELECT count(id) FROM `#__uddeim` WHERE (totrashdate>=".$uddtimeframe." AND toid=".(int)$udduserid." AND totrash=1) OR (totrashdateoutbox>=".$uddtimeframe." AND fromid=".(int)$udduserid." AND totrashoutbox=1 AND toid<>".(int)$udduserid." AND ((systemmessage IS NULL) OR (systemmessage='')))";

	$udddatabase->setQuery($uddsql);
	$uddresult=(int)$udddatabase->loadResult();

	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_trash ?? "<img src='".$iconpath."/menu_trashcan.gif' alt='"._UDDEIM_TRASHCAN."' /> ") : ""; 
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=trashcan".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_TRASHCAN.'">';
	$uddout .= _UDDEIM_TRASHCAN.": ".$uddresult;
	$uddout .= '</a>';
	$uddout .= "</p>";
}

if ( $uddshowarchive && $uddconfig->allowarchive) {
	$uddsql="SELECT count(a.id) FROM `#__uddeim` AS a WHERE a.totrash=0 AND archived=1 AND a.toid=".(int)$udduserid;

	$udddatabase->setQuery($uddsql);
	$uddresult=(int)$udddatabase->loadResult();

	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_archive ?? "<img src='".$iconpath."/menu_archive.gif' alt='"._UDDEIM_ARCHIVE."' /> ") : ""; 
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=archive".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_ARCHIVE.'">';
	$uddout .= _UDDEIM_ARCHIVE.": ".$uddresult;
	$uddout .= '</a>';
	$uddout .= "</p>";
}

if( ($uddconfig->enablelists==1) ||                                                            //war $config
	($uddconfig->enablelists==2 && (uddeIMisSpecial($uddmy_gid) || uddeIMisSpecial2($uddmy_gid, $uddconfig))) ||
	($uddconfig->enablelists==3 && (uddeIMisAdmin($uddmy_gid) || uddeIMisAdmin2($uddmy_gid, $uddconfig))) ) {
	// ok contact lists are enabled
	if ( $uddshowcontacts ) {
		$uddout .= "<p class='uddeim-module-body'>";
		$uddout .= $uddshowicons ? ($fa_contact ?? "<img src='".$iconpath."/menu_book.gif' alt='"._UDDEIM_LISTS."' /> ") : "";
		$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=showlists".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_LISTS.'">';
		$uddout .= _UDDEIM_LISTS;
		$uddout .= '</a>';
		$uddout .= "</p>";
	}
}

if ( $uddshowsettings ) {
	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_setting ?? "<img src='".$iconpath."/menu_settings.gif' alt='"._UDDEIM_SETTINGS."' /> ") : ""; 
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=settings".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_SETTINGS.'">';
	$uddout .= _UDDEIM_SETTINGS;
	$uddout .= '</a>';
	$uddout .= "</p>";
}

if ( $uddshowcompose ) {
	$uddout .= "<p class='uddeim-module-body'>";
	$uddout .= $uddshowicons ? ($fa_compose ?? "<img src='".$iconpath."/menu_new.gif' alt='"._UDDEIM_COMPOSE."' /> ") : "";  
	$uddout .= '<a href="'.uddeIMsefRelToAbs( "index.php?option=com_uddeim&task=new".($udditem_id ? "&Itemid=".$udditem_id : "") ).'" title="'._UDDEIM_COMPOSE.'">';
	$uddout .= _UDDEIM_COMPOSE;
	$uddout .= '</a>';
	$uddout .= "</p>";
}

$uddout .= "</div>";

echo $uddout;

