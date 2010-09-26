<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr <phoenixx@upb.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_t3lib.'class.t3lib_diff.php');
require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
require_once(t3lib_extMgm::extPath('fsmi_protocols').'api/class.tx_fsmiprotocols_div.php');

/**
 * Plugin 'Show protocols' for the 'fsmi_protocols' extension.
 *
 * @author	Andreas Cord-Landwehr <phoenixx@upb.de>
 * @package	TYPO3
 * @subpackage	tx_fsmiprotocols
 */
class tx_fsmiprotocols_pi2 extends tslib_pibase {
	var $prefixId      = 'tx_fsmiprotocols_pi2';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_fsmiprotocols_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'fsmi_protocols';	// The extension key.
	var $pi_checkCHash = true;
	var $pi_USER_INT_obj = 1;

	var $cmnd_list 	= 0;
	var $cmnd_edit 	= 1;
	var $cmnd_new 	= 2;

	var $storePidNew 	= 0;
	var $committee 		= 0;

	/**
	 * Main method of your PlugIn
	 *
	 * @param	string		$content: The content of the PlugIn
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	The content that should be displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		$this->pi_USER_INT_obj = 1;
		$this->cache = 0;	// TODO
		$this->pi_checkCHash = TRUE;

		$this->committee = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'uidCommittee'));
		$committeeDB = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $this->committee);
		$this->storePidNew = $committeeDB['storage_pid'];

		$content = '';

		switch (t3lib_div::_GET('command')) {
			case $this->cmnd_list: {
				$content .= $this->listAll($this->committee);
				break;
			}
			case $this->cmnd_edit: {
				$content .= $this->editProtocol(t3lib_div::_GET('protocol'));
				break;
			}
			case $this->cmnd_new: {
				if (!t3lib_div::_POST('protocol_new'))
					$content .= $this->newProtocol($this->committee);
				else
					$content .= $this->listAll($this->committee);
				break;
			}
			default: $content .= $this->listAll($this->committee);
		}

		return $content; //$this->pi_wrapInBaseClass($content);

	}

	function listAll ($committee) {
		$content = '';
		$protocolPost = t3lib_div::_POST($this->extKey);
		$committeeDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $committee);

		// test if there are post variables for new protocol
		if (t3lib_div::_POST('protocol_new')) {
			$content .= $this->DBNewProtocol();

			$message = '[FSWWW] Neues Protokoll '.$protocolPost['meeting_date'].chr(10).$protocolPost['protocol'];
			$this->cObj->sendNotifyEmail($message, 'fsmi@upb.de', $cc, $email_from, $email_fromName='', $replyTo='');
		}

		// test if there are post variables for update
		if (t3lib_div::_POST('protocol_changes') && $protocolPost['protocol_uid']) {
			$protocolDB = t3lib_BEfunc::getRecord('tx_fsmiprotocols_list', intval($protocolPost['protocol_uid']));

			// update database
			$content .= $this->DBUpdateProtocolData(intval($protocolPost['protocol_uid']));

			// create diff, create htmlmail
			$t3diff = t3lib_div::makeInstance('t3lib_diff');
			$t3htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');

			$userID = intval($GLOBALS['TSFE']->fe_user->user['uid']);
			$userDATA = t3lib_BEfunc::getRecord("fe_users",$userID);

			// send mail
			$t3htmlmail->start();
			$t3htmlmail->recipient = 'fsmi@uni-paderborn.de';
			$t3htmlmail->subject = ('Geändertes Protokoll '.$protocolPost['meeting_date']);
			$t3htmlmail->from_email = 'fsmi@uni-paderborn.de';
			$t3htmlmail->from_name = 'Fachschaft Mathematik/Informatik';
			$t3htmlmail->setHTML($t3htmlmail->encodeMsg(
				$this->printHTMLMailHeader('Geändertes Protokoll '.$protocolPost['meeting_date'], $protocolDB['t3ver_count']+1).
				'<div>Geändert von: '.($userDATA['name']!=''?$userDATA['name']:$userDATA['username']).'</div>'.
				'<pre>'.nl2br($t3diff->makeDiffDisplay($protocolPost['protocol'],$protocolDB['protocol'])).'</pre>'.
				'<h2>Das Protokoll</h2>'.
				nl2br($protocolPost['protocol']).
				$this->printHTMLMailEnding())
			);
			$t3htmlmail->setHeaders();
			if ($t3htmlmail->send('fsmi@uni-paderborn.de'))
				$content .=
				'<div class="typo3-message message-notice">
					<div class="message-header">Änderungsmail verschickt!</div>
					<div class="message-body">Eine Mail mit den Protokolländerungen wurden erfolgreich verschickt.</div>
				</div>';

		}



		$content .= '<h2>Neues Protokoll hinzufügen</h2>';
		$content .= '<p>Jetzt ein '.$this->pi_linkTP('neues Protokoll schreiben',
								array (	'command' => $this->cmnd_new)
								).'</p>';

		$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmiprotocols_list
												WHERE
													tx_fsmiprotocols_list.committee = '.intval($committee).'
													AND tx_fsmiprotocols_list.deleted=0
													AND tx_fsmiprotocols_list.pid > 0
												 ORDER BY meeting_date DESC');

		$content .= '<h2>Protokolle</h2>';

		$content .= '<table>';
		$content .= '<tr><th></th><th><strong>Datum</strong></th>';
		if ($committeeDATA['disclosure'] == tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS)
			$content .= '<th><strong>Korrektur A</strong></th><th><strong>Korrektur B</strong></th>';
		$content .= '<tr>';

		// table content
		$rowcount = 0;
		while($res && $row = mysql_fetch_assoc($res)) {
			$content .= '<tr style="';
			if ($rowcount++ % 2)
				$content .= 'odd';
			else
				$content .= 'even';
			$content .= '">';

			// show state
			$content .= '<td>';
			if ($row['hidden']==1)
				$content .= tx_fsmiprotocols_div::printImageProtocolState(tx_fsmiprotocols_div::kIMAGE_HIDDEN);
			else {
				if ($committeeDATA['disclosure'] == tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS
					  && !($row['reviewer_a']  && $row['reviewer_b']) )
					$content .= tx_fsmiprotocols_div::printImageProtocolState(tx_fsmiprotocols_div::kIMAGE_REVIEW_MISSING);
				else
					$content .= tx_fsmiprotocols_div::printImageProtocolState(tx_fsmiprotocols_div::kIMAGE_PUBLISHED);
			}
			$content .= '</td>';

			$content .= '<td>'.
				$this->pi_linkTP(strftime('%d. %m. %Y (%A)',$row['meeting_date']),
								array ('protocol' => $row['uid'],
										'command' => $this->cmnd_edit)
								).
						'</td>';
			// depending on disclosure, what to print
			if ($committeeDATA['disclosure'] == tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS) {
				if ($row['reviewer_a']==0)
					$content .= '<td>---</td>';
				else {
					$reviewerA = t3lib_BEfunc::getRecord('fe_users', $row["reviewer_a"]);
					$content .= '<td>'.$reviewerA['username'].'</td>';
				}
				if ($row['reviewer_b']==0)
					$content .= '<td>---</td>';
				else {
					$reviewerB = t3lib_BEfunc::getRecord('fe_users', $row["reviewer_b"]);
					$content .= '<td>'.$reviewerB['username'].'</td>';
				}
			}
			$content .= '</tr>';
		}

		$content .= '</table>';

		return $content;
	}

	function DBNewProtocol () {
		$protocolPost = t3lib_div::_POST($this->extKey);

		// setup checkbox for hidden
		if (isset($protocolPost['hidden']))
			$protocolPost['hidden'] = 1;
		else
			$protocolPost['hidden'] = 0;

		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(	'tx_fsmiprotocols_list',
												array (	'pid' 			=> $this->storePidNew,
														'hidden' 		=> intval($protocolPost['hidden']),
														'crdate' 		=> time(),
														'tstamp' 		=> time(),
														't3ver_count'	=> '1',													// first version
														't3ver_state'	=> '1',													// online version
														'meeting_date' 	=> strtotime($protocolPost['meeting_date']),
														'protocol' 		=> wordwrap($protocolPost['protocol'],120),
														'reviewer_a' 	=> intval($protocolPost['reviewer_a']),
														'reviewer_b' 	=> intval($protocolPost['reviewer_b']),
														'committee'		=> intval($this->committee)
												));
		if ($res)
			return '<div class="typo3-message message-ok">
					<div class="message-header">Protokoll erstellt!</div>
					<div class="message-body">Es wurde ein neues Protokoll gespeichert.</div>
				</div>';
		else
			return '<div class="typo3-message message-error">
					<div class="message-header">Protokoll wurde nicht geändert!</div>
					<div class="message-body">Ein Datenbankfehler ist aufgetreten.</div>
				</div>';
	}

	function DBUpdateProtocolData ($protocol) {
		$protocolPost = t3lib_div::_POST($this->extKey);
		$protocolDB = t3lib_BEfunc::getRecord('tx_fsmiprotocols_list', $protocol);

		// setup checkbox for hidden
		if (isset($protocolPost['hidden']))
			$protocolPost['hidden'] = 1;
		else
			$protocolPost['hidden'] = 0;

		// create backup of old protocol
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(	'tx_fsmiprotocols_list',
												array (	'pid' 			=> '-1',
														'hidden'		=> $protocolDB['hidden'],
														'deleted'		=> $protocolDB['deleted'],
														'crdate' 		=> $protocolDB['crdate'],
														'tstamp' 		=> time(),
														't3ver_oid'		=> $protocolDB['uid'],			// points to online version
														't3_origuid'	=> $protocolDB['uid'],
														't3ver_tstamp'	=> $protocolDB['tstamp'],
														't3ver_state'	=> $protocolDB['t3ver_state'],
														't3ver_count'	=> $protocolDB['t3ver_count'],	// counts versions
														'type'			=> $protocolDB['type'],
														'meeting_date'	=> $protocolDB['meeting_date'],
														'meeting_room'	=> $protocolDB['meeting_room'],
														'meeting_time'	=> $protocolDB['meeting_time'],
														'agenda'		=> $protocolDB['agenda'],
														'sticky_date'	=> $protocolDB['sticky_date'],
														'protocol'		=> $protocolDB['protocol'],
														'protocol_pdf'	=> $protocolDB['protocol_pdf'],
														'documents'		=> $protocolDB['documents'],
														'resolutions'	=> $protocolDB['resolutions'],
														'committee'		=> $protocolDB['committee'],
														'not_admitted'	=> $protocolDB['not_admitted'],
														'reviewer_a'	=> $protocolDB['reviewer_a'],
														'reviewer_b'	=> $protocolDB['reviewer_b'],
														'protocol_name'	=> $protocolDB['protocol_name'],
												));

		// TODO check at this point if saving was ok!
		// next save new version
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(	'tx_fsmiprotocols_list',
												'uid = '.$protocolPost['protocol_uid'],
												array (
														'hidden'		=> intval($protocolPost['hidden']),
														'tstamp' 		=> time(),
														't3ver_count' 	=> $protocolDB['t3ver_count'] + 1,
														't3ver_state'	=> 0,
														'meeting_date' 	=> strtotime($protocolPost['meeting_date']),
														'protocol' 		=> $protocolPost['protocol'], //TODO this could be an SQL inclusion!
														'reviewer_a' 	=> intval($protocolPost['reviewer_a']),
														'reviewer_b' 	=> intval($protocolPost['reviewer_b']),
												));
		if ($res) return '<div class="typo3-message message-ok">
					<div class="message-header">Protokoll geändert!</div>
					<div class="message-body">Das Protokoll wurde geändert.</div>
				</div>';
		else
			return '<div class="typo3-message message-error">
					<div class="message-header">Protokoll wurde nicht geändert!</div>
					<div class="message-body">Ein Datenbankfehler ist aufgetreten.</div>
				</div>';
	}

	function newProtocol($committeeUid) {
		$content = '';
		$protocolPost = t3lib_div::_POST($this->extKey);

		$content .= '<h2>Neues Protokoll</h2>';
		$content .= '<div><i>'.$this->pi_linkTP('zurück zur Übersicht').'</i></div>';

		$content .= '<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id).
    		'" name="tx_myext" method="post">
			<table>
 			<tr><td><strong>Datum:</strong> <input type="text" name="'.$this->extKey.'[meeting_date]" value="'.strftime('%Y-%m-%d',mktime()).'"></td></tr>';
		$content .= '<tr><td>'.
 			'<textarea name="'.$this->extKey.'[protocol]" cols="120" rows="30"></textarea></td></tr></table>';

		$content .= '<h3>Veröffentlichung</h3>';

		// initial disclosure settings
		$committeeDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $committeeUid);
		$content .= '<table>';
		if ($committeeDATA['disclosure']==tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS) {
			// reviewer A
			$content .= '<tr><td>Initialen Korrektor A
				<select size="1" name="'.$this->extKey.'[reviewer_a]">
				<option> --- </option>';
			$content .= '</select></td></tr>';

			// reviewer B
			$content .= '<tr><td>Initialien Korrektor B
				<select size="1" name="'.$this->extKey.'[reviewer_b]">
				<option> --- </option>';
			$content .= '</select></td></tr>';
			$content .= '<tr><td>Protokoll verstecken (unabhängig von Korrektoren) <input type="checkbox" name="'.$this->extKey.'[hidden]" value="hidden" /></td></tr>';
		}
		else
			$content .= '<tr><td>Protokoll verstecken <input type="checkbox" name="'.$this->extKey.'[hidden]" value="hidden" /></td></tr>';


 		$content .= '<tr><td><input type="submit" name="protocol_new" value="Protokoll hinzufügen"></td></tr></table>
			</form>';

		return $content;
	}


	function editProtocol($protocolUid) {
		$content = '';
		$protocolUid = intval($protocolUid);
		$protocolPost = t3lib_div::_POST($this->extKey);

		// get protocol data from database
		$protocolDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_list', $protocolUid);
		$committeeDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $protocolDATA['committee']);

		$content .= '<h2>Protokoll editieren</h2>';
		$content .= '<div><i>'.$this->pi_linkTP('zurück zur Übersicht').'</i></div>';

		$content .= '<form action="index.php?id='.$GLOBALS['TSFE']->id.
			'" name="tx_myext" method="post">
			<input type="hidden" name="'.$this->extKey.'[protocol_uid]" value="'.$protocolUid.'">
			<table>
 			<tr><td><input type="text" name="'.$this->extKey.'[meeting_date]" value="'.strftime('%Y-%m-%d',$protocolDATA['meeting_date']).'"></td><tr>';
		$content .= '<tr><td>'.
 			'<textarea name="'.$this->extKey.'[protocol]" cols="120" rows="30">'.$protocolDATA['protocol'].'</textarea></td></tr>';
		$content .= '</table>';

		// disclosure
		$content .= '<h3>Veröffentlichung</h3><table>';
		if ($committeeDATA['disclosure']==tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS) {
			// reviewer A
			$content .= '<tr><td>Korrektor A
				<select size="1" name="'.$this->extKey.'[reviewer_a]">
				<option> --- </option>';
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM fe_users
													WHERE
														deleted=0
													ORDER BY name');

			$row = null;
			while($res && $row = mysql_fetch_assoc($res)) {
				if ($row['uid']==$protocolDATA['reviewer_a'])
					$content .= '<option selected="selected" value="'.$row['uid'].'">'.$row['username'].'</option>';
				else
					$content .= '<option value="'.$row['uid'].'">'.$row['username'].'</option>';
			}
			$content .= '</select></td></tr>';

			// reviewer B
			$content .= '<tr><td>Korrektor B
				<select size="1" name="'.$this->extKey.'[reviewer_b]">
				<option> --- </option>';
			$res = $GLOBALS['TYPO3_DB']->sql_query('SELECT *
													FROM fe_users
													WHERE
														deleted=0
													ORDER BY name');

			while($res && $row = mysql_fetch_assoc($res)) {
				if ($row['uid']==$protocolDATA['reviewer_b'])
					$content .= '<option selected="selected" value="'.$row['uid'].'">'.$row['username'].'</option>';
				else
					$content .= '<option value="'.$row['uid'].'">'.$row['username'].'</option>';
			}
			$content .= '</select></td></tr>';
		}
		if ($protocolDATA['hidden']==1)
			$content .= '<tr><td>Protokoll verstecken <input type="checkbox" name="'.$this->extKey.'[hidden]" value="hidden" checked="checked" /></td></tr>';
		else
			$content .= '<tr><td>Protokoll verstecken <input type="checkbox" name="'.$this->extKey.'[hidden]" value="hidden" /></td></tr>';

 		$content .= '<tr><td><input type="submit" name="protocol_changes" value="Protokoll ändern"></td></tr></table>
			</form>';

		return $content;
	}


	function printHTMLMailHeader ($title, $revision) {
		$header =
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>'.$title.'</title>
</head>
<body>

<style type="text/css"><!--
#msg dl.meta { border: 1px #006 solid; background: #369; padding: 6px; color: #fff; }
#msg dl.meta dt { float: left; width: 6em; font-weight: bold; }
#msg dt:after { content:&aps:&aps;}
#msg dl, #msg dt, #msg ul, #msg li, #header, #footer, #logmsg { font-family: verdana,arial,helvetica,sans-serif; font-size: 10pt; }
#msg dl a { font-weight: bold}
#msg dl a:link { color:#fc3; }
#msg dl a:active { color:#ff0; }
#msg dl a:visited { color:#cc6; }
h3 { font-family: verdana,arial,helvetica,sans-serif; font-size: 10pt; font-weight: bold; }
#msg pre { overflow: auto; background: #ffc; border: 1px #fa0 solid; padding: 6px; }
#logmsg { background: #ffc; border: 1px #fa0 solid; padding: 1em 1em 0 1em; }
#logmsg p, #logmsg pre, #logmsg blockquote { margin: 0 0 1em 0; }
#logmsg p, #logmsg li, #logmsg dt, #logmsg dd { line-height: 14pt; }
#logmsg h1, #logmsg h2, #logmsg h3, #logmsg h4, #logmsg h5, #logmsg h6 { margin: .5em 0; }
#logmsg h1:first-child, #logmsg h2:first-child, #logmsg h3:first-child, #logmsg h4:first-child, #logmsg h5:first-child, #logmsg h6:first-child { margin-top: 0; }
#logmsg ul, #logmsg ol { padding: 0; list-style-position: inside; margin: 0 0 0 1em; }
#logmsg ul { text-indent: -1em; padding-left: 1em; }#logmsg ol { text-indent: -1.5em; padding-left: 1.5em; }
#logmsg > ul, #logmsg > ol { margin: 0 0 1em 0; }
#logmsg pre { background: #eee; padding: 1em; }
#logmsg blockquote { border: 1px solid #fa0; border-left-width: 10px; padding: 1em 1em 0 1em; background: white;}
#logmsg dl { margin: 0; }
#logmsg dt { font-weight: bold; }
#logmsg dd { margin: 0; padding: 0 0 0.5em 0; }
#logmsg dd:before { content:&aps\00bb&aps;}
#logmsg table { border-spacing: 0px; border-collapse: collapse; border-top: 4px solid #fa0; border-bottom: 1px solid #fa0; background: #fff; }
#logmsg table th { text-align: left; font-weight: normal; padding: 0.2em 0.5em; border-top: 1px dotted #fa0; }
#logmsg table td { text-align: right; border-top: 1px dotted #fa0; padding: 0.2em 0.5em; }
#logmsg table thead th { text-align: center; border-bottom: 1px solid #fa0; }
#logmsg table th.Corner { text-align: left; }
#logmsg hr { border: none 0; border-top: 2px dashed #fa0; height: 1px; }
#header, #footer { color: #fff; background: #636; border: 1px #300 solid; padding: 6px; }
.diff-g { background-color: #ff0000; text-decoration: line-through; }
.diff-r { background-color: #0f0; }
--></style>
<div id="msg">
<dl class="meta">
<dt>Version</dt> <dd>'.$revision.'</dd>
<dt>Datum</dt> <dd>'.strftime('%Y-%m-%d',time()).'</dd>
</dl>';

		return $header;
	}

	function printHTMLMailEnding () {
		$ending =
'</div>
</body>
</html>';
		return $ending;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/pi2/class.tx_fsmiprotocols_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/pi2/class.tx_fsmiprotocols_pi2.php']);
}

?>
