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
 * Plugin 'Show protocols' for the 'meetings' extension.
 *
 * @author	Andreas Cord-Landwehr <phoenixx@upb.de>
 * @package	TYPO3
 * @subpackage	tx_meetings
 */
class tx_meetings_view_single extends tx_meetings_view_base {
	protected $Display;
	public $year          = 0;
	private $disclosure    = tx_meetings_pi1::kDISCLOSURE_STANDARD;
	public $userNotInIPrestrictedNetwork	= true;

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
	 * Display a single item from the database
	 *
	 * @param	$protocolUID UID of protocol
	 * @param	$accessObj object of type tx_meetings_access that is preconfigured with access information
	 * @return	HTML of a single database entry
	 */
	function printProtocol($protocolUID, $accessObj)	{
		$content = '';

		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);
		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee', $protocolDATA['committee']);

		if (
			$protocolDATA['deleted']==1
			|| $protocolDATA['hidden']==1
			|| $protocolDATA['pid']<0
		) {
			return $this->getLL('meeting_id_invalid');
		}

		/*
		 * start with protocol information
		 */

		// This sets the title of the page for use in indexed search results:
		if ($protocolDATA['title'])
			$GLOBALS['TSFE']->indexedDocTitle=$protocolDATA['title'];

		// configure protocol title
		$content .= '<h2>'.$this->printMeetingTitle($protocolUID).'</h2>';
		$content .= '<div><i>'.$this->pi_getLL('committee').': '.$committeeDATA['committee_name'].'</i></div>';

		$content .= '<ul>';
		$content .= ($protocolDATA['meeting_date']!=0? '<li>'.$this->pi_getLL('date').': '.date('d.m.Y',$protocolDATA['meeting_date']).'</li>': '');
		$content .= ($protocolDATA['meeting_time']!=0? '<li>'.$this->pi_getLL('time').': '.	// this time are seconds at day
			intval($protocolDATA['meeting_time']/3600).':'.
			tx_meetings_div::twoDigits(intval( ($protocolDATA['meeting_time']%3600) / 60)).'</li>': '');
		$content .= ($protocolDATA['meeting_room']!=''? '<li>'.$this->pi_getLL('place').': '.$protocolDATA['meeting_room'].'</li>': '');
		$content .= '</ul>';

		if ($this->Display['ShowAgendaElement']) {
			// display agenda if given
			if ($protocolDATA['agenda']!='' && $protocolDATA['agenda_preliminary']==1) {
				if ($accessObj->isAccessAllowedAgendaPreliminary($protocolDATA['meeting_date'])==true) {
					$content .= '<h3><a name="meetings_agenda">'.$this->pi_getLL('preliminary_agenda').'</a></h3>';
					$content .= '<div>'.$this->showTextareaContentRTE($protocolDATA['agenda']).'</div>';
				}
				else
					$content .= tx_meetings_notifier::printNotification(
						$this->pi_getLL('access_to_preliminary_agenda'),
						$this->pi_getLL('access_to_preliminary_agenda_denied_long'));
			}
			elseif ($protocolDATA['agenda']!='' && $protocolDATA['agenda_preliminary']==0) {
				if ($accessObj->isAccessAllowedAgenda($protocolDATA['meeting_date'])==true) {
					$content .= '<h3><a name="meetings_agenda">'.$this->pi_getLL('agenda').'</a></h3>';
					$content .= '<div>'.$this->showTextareaContentRTE($protocolDATA['agenda']).'</div>';
				}
				else
					$content .= tx_meetings_notifier::printNotification(
						$this->pi_getLL('access_to_agenda'),
						$this->pi_getLL('access_to_agenda_denied_long'));
			}
			else
				$content .=  tx_meetings_notifier::printNotification(
						$this->pi_getLL('no_agenda'),
						$this->pi_getLL('no_agenda_long'));
		}

		$content .= '<h3>'.$this->pi_getLL('meeting-documents').'</h3>';
		// display protocol
		if ($protocolDATA['protocol']!='' || $protocolDATA['protocol_pdf']!='') {
			if ($protocolDATA['not_admitted']==1)
				$content .= '<h4><a name="meetings_protocol">'.$this->pi_getLL('not_admitted_protocol').'</a></h4>';
			else
				$content .= '<h4><a name="meetings_protocol">'.$this->pi_getLL('protocol').'</a></h4>';
			if ($accessObj->isAccessAllowedProtocols($protocolDATA['meeting_date'])==false) {
					$content .= tx_meetings_notifier::printNotification(
						$this->pi_getLL('access_to_protocol'),
						$this->pi_getLL('access_to_protocol_denied_long'));
			}
			else { // access allowed
				// print text-protocol
				if ($protocolDATA['type']==tx_meetings_view_base::kPROTOCOL_TYPE_PLAIN &&
					$protocolDATA['protocol']!=''
				) {
					$content .= '<div'.$this->pi_classParam('singleView').'>';
					$content .= $this->showTextareaContent($protocolDATA['protocol'],100);
					$content .=  '<p>'.$this->pi_linkTP(
											$this->pi_getLL('to_overview'),
											array(
												$this->extKey.'[year]' => tx_meetings_div::dateToTenureYear(
																									  $protocolDATA['meeting_date'],
																									  $protocolDATA['sticky_date']
																		  )
											),
											$this->cache
								).
					'</p></div>';
				}
				// give PDF protocol
				elseif ($protocolDATA['type']==tx_meetings_view_base::kPROTOCOL_TYPE_PDF && $protocolDATA['protocol_pdf']!='')
					$content .= '<p>'.$this->pi_getLL('protocol_as_pdf_descr').': <a href="'.tx_meetings_div::uploadFolder.$protocolDATA['protocol_pdf'].'">
						'.$this->pi_getLL('meeting-protocol').'</a></p>';
			}
		}
		else // no protocol exists
			$content .= tx_meetings_notifier::printNotification($this->pi_getLL('no_protocol'),$this->pi_getLL('no_protocol_long'));

		// print additional documents for protocols
		if ($this->Display['ShowDocumentsElement']) {
			$documents = $this->getDocumentsForProtocol($protocolDATA['uid']);
			$content .= '<h4>'.$this->pi_getLL('documents').'</h4>';
			if (count($documents)>0) {
				if ($accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date'])==false) {
					$content .= tx_meetings_notifier::printNotification(
						$this->pi_getLL('access_to_documents'),
						$this->pi_getLL('access_to_documents_denied_long'));
					$content .= '<ul>';
					// if no access: only print names
					foreach($documents as $documentDATA) {
							$content .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
								$this->pi_getLL('documents').': '.$documentDATA['name'].'</li>';
					}
					$content .= '</ul>';
				}
				else {
					$content .= '<p>'.$this->pi_getLL('available_documents_descr').'</p>';
					$content .= '<ul>';

					foreach($documents as $documentDATA) {
						if ($accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date'],$documentDATA['uid'])==false)
							$content .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
								$this->pi_getLL('documents').': '.$documentDATA['name'].
								($documentDATA['description']!=''?'<div><i>'.$documentDATA['description'].'</i></div>':'').
								'</li>';
						else
							$content .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
								$this->pi_getLL('documents').': '.$this->printLinkToDocument($documentDATA['uid']).
								($documentDATA['description']!=''?'<div><i>'.$documentDATA['description'].'</i></div>':'').
								'</li>';
					}
					$content .= '</ul>';
				}
			} else
				$content .= '<p>'.$this->pi_getLL('no_documents_long').'</p>';

		}

		// print resolutions
		if ($this->Display['ShowResolutionsElement']) {
			$resolutions = $this->getResolutionsForProtocol($protocolDATA['uid']);
			$content .= '<h4>'.$this->pi_getLL('resolutions').'</h4>';
			if (count($resolutions)>0) {
				if ($accessObj->isAccessAllowedResolutions($protocolDATA['meeting_date'])==false)
					$content .= tx_meetings_notifier::printNotification(
						$this->pi_getLL('access_to_resolutions'),
						$this->pi_getLL('access_to_resolutions_denied_long'));
				else {
				$content .= '<p>'.$this->pi_getLL('passed_resolutions_descr').'.</p>';
					$content .= '<ul>';
					foreach($resolutions as $resolutionDATA)
						$content .= $this->printResolutionLI($resolutionDATA['uid']);
					$content .= '</ul>';
				}
			}
			else
				$content .= '<p>'.$this->pi_getLL('no_resolutions_long').'</p>';

		}

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_single.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_single.php']);
}

?>