<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Cord-Landwehr <phoenixx@upb.de>
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
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_notifier.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_base.php');

/**
 * This class prints a table view for committee meetings of a given periode/year.
 * The view can be defined by the protected class variabel 'Display' of the parent class.
 *
 * @author	Andreas Cord-Landwehr <cola@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_meetings
 */
class tx_meetings_view_table extends tx_meetings_view_base {
	protected $Display = array ();
	var $year;
	protected $accessObj;

	function __construct () {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->LANG = t3lib_div::makeInstance('language');
		// initialization procedure for language
		$this->LLkey = $GLOBALS['TSFE']->lang;
		$this->LOCAL_LANG_loaded = 0;
		$this->pi_loadLL();
	}

	function setDisplay($Display) { $this->Display = $Display;}

	/**
	 * This Function prints one line of result table
	 * @param $protocolUID
	 * @return string
	 **/
	function printProtocolTableLine ($protocolUID, $oddLine=true) {
		$contentProtocol = '';
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);
		$this->year = tx_meetings_div::dateToTenureYear($protocolDATA['meeting_date'],$protocolDATA['sticky_date']);

		// here the real code starts
		$contentProtocolTable = '<tr '.($oddLine? 'class="meetings_row_odd"':'class="fsimprotocols_row_even"').'>';
		// date, room, time
		$contentProtocolTable .= '<td>'.
					  $this->pi_linkTP(
								date('d.m.Y',$protocolDATA['meeting_date']),
								array(
									$this->extKey.'[showUid]' => $protocolDATA['uid'],
									$this->extKey.'[year]' => $this->year,
								),
								$this->cache
						).'</td>';

		// switch for Title element at overview page
		if ($this->Display['ShowTitleElement'])
			$contentProtocolTable .= '<td>'.$this->pi_linkTP(
								$protocolDATA['protocol_name'],
								array(
									$this->extKey.'[showUid]' => $protocolDATA['uid'],
									$this->extKey.'[year]' => $this->year,
								),
								$this->cache
						).'</td>';

		// switch for agenda and documents at list view
		$contentProtocolTable .= '<td>';
		if ($this->Display['ShowAgendaElement']) {

			if ($protocolDATA['agenda']!='')
				$contentProtocolTable .= $this->pi_linkToPage(
															($protocolDATA['agenda_preliminary']==0? $this->pi_getLL('agenda') : $this->pi_getLL('preliminary_agenda')),
															$GLOBALS['TSFE']->id.'#meetings_agenda',
															'',
															array(
																$this->extKey.'[showUid]' => $protocolDATA['uid'],
																$this->extKey.'[year]' => $this->year,
															)).
								  '<br />';

			$admitted = '';
			// TODO dirty hack
			if ($protocolDATA['not_admitted']==1)
				$admitted = '<strong>('.$this->pi_getLL('not_admitted').')</strong> ';
			if ($protocolDATA['protocol']!='' || $protocolDATA['protocol_pdf'])
				$contentProtocolTable .= $this->pi_linkToPage($this->pi_getLL('meeting-protocol'), $GLOBALS['TSFE']->id.'#meetings_protocol','',
															array(
																$this->extKey.'[showUid]' => $protocolDATA['uid'],
																$this->extKey.'[year]' => $this->year,
															)).
								  ' '.$admitted.'<br />';
		}
		if ($this->Display['ShowDocumentsElement']) {
			$documents = $this->getDocumentsForProtocol($protocolDATA['uid']);
			if ($this->accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date']))
				foreach($documents as $documentDATA) {
					if ($this->accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date'],$documentDATA['uid']))
						$contentProtocolTable .= $this->printLinkToDocument($documentDATA['uid'], true).' ';
					else
						$contentProtocolTable .= '<img title="'.$documentDATA['name'].'" src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.
						$this->pi_getLL('documents').'" /> ';
				}
			else
				foreach($documents as $documentDATA)
					$contentProtocolTable .= '<img title="'.$documentDATA['name'].'" src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.
						$this->pi_getLL('documents').'" /> ';
		}
		$contentProtocolTable .= '</td>';
		// switch for resolutions display at list view
		if ($this->Display['ShowResolutionsElement']) {
			$resolutions = $this->getResolutionsForProtocol($protocolDATA['uid']);
			$contentProtocolTable .= '<td>';
			if ($this->accessObj->isAccessAllowedResolutions($protocolDATA['meeting_date']))
				foreach ($resolutions as $resolutionDATA)
					$contentProtocolTable .= $this->printLinkToResolution($resolutionDATA['uid']).'<br /> ';
			else
				foreach ($resolutions as $resolutionDATA)
					$contentProtocolTable .= '<i title="'.
						$this->pi_getLL('access_denied').'">'.$this->printResolutionTitle($resolutionDATA['uid']).'</i><br /> ';
			$contentProtocolTable .= '</td>';
		}

		$contentProtocolTable .= '</tr>';

		return $contentProtocolTable;
	}

	function printProtocols ($protocolUIDs, $accessObj) {
		$this->accessObj = $accessObj;

		$content = '';
		$content .= '<table class="meetings_table">';
		$content .= '<tr>';
		$content .= '<th width="75">'.$this->pi_getLL('date').'</th>';
		if ($this->Display['ShowTitleElement'])
			$content .= '<th width="200">'.$this->pi_getLL('title').'</th>';
		if ($this->Display['ShowAgendaElement'])
			$content .= '<th width="200">'.$this->pi_getLL('meeting-documents').'</th>';
		if ($this->Display['ShowResolutionsElement'])
			$content .= '<th width="200">'.$this->pi_getLL('resolutions').'</th>';
		$content .= '</tr>';
		// TODO add name
		$counter=1;
		foreach ($protocolUIDs as $meeting)
			$content .= $this->printProtocolTableLine($meeting,$counter++%2); //TODO fix committee!
		$content .= '</table>';

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_table.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_table.php']);
}

?>