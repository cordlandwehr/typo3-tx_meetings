<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Cord-Landwehr <cola@uni-paderborn.de>
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
/**
 * This is the base class for all views. Each specific view must
 * extend this class.
 *
 * @author	Andreas Cord-Landwehr <cola@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_meetings
 */



require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');

require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_access.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_list.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_table.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_resolutions.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_single.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_single_resolution.php');

/**
 * Base view for all users. This shall be extended by specific view (list, table, single...)
 *
 */
class tx_meetings_view_base extends tx_meetings_pi1 {
	const kVIEW_LIST		= 1;
	const kVIEW_TABLE		= 2;
	const kVIEW_RESOLUTIONS = 3;
	const kVIEW_LATEST		= 4;

	const kPROTOCOL_TYPE_PLAIN	= 0;
	const kPROTOCOL_TYPE_PDF	= 1;

	public $prefixId        	= 'tx_meetings';		// Same as class name
	public $extKey           	= 'meetings';	// The extension key.
	public $pi_checkCHash     	= true;
	public $pi_USER_INT_obj  	= 0;
	public $cache 				= 1;
	var $scriptRelPath    		= 'pi1/class.tx_meetings_pi1.php';	// Path to this script relative to the extension dir.

 	var $LANG;						// language object
	var $cObj;
	var $conf;

	private $disclosure    = tx_meetings_div::kDISCLOSURE_STANDARD;
	private $committee		= 0;
	protected $year;
	protected $commission;
	protected $Display;
	protected $accessObj;

	function __construct () {
		$this->pi_setPiVarDefaults();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->LANG = t3lib_div::makeInstance('language');
		$this->LANG->init($GLOBALS['TSFE']->tmpl->setup['config.']['language']);
		$this->LANG->includeLLFile('typo3conf/ext/meetings/locallang_db.xml');
	}

	function init($committee, $year, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin

		// initialization procedure for language
		$this->LLkey = $GLOBALS['TSFE']->lang;
		$this->LOCAL_LANG_loaded = 0;
		$this->pi_loadLL();

 		$this->committee = $committee;
		$this->year = $year;
		$this->accessObj = t3lib_div::makeInstance(tx_meetings_access);
		$this->accessObj->init($committee);
	}

	function setDisplay($Display) { $this->Display = $Display; }

	function printMeetingList($view, $protocolUIDs) {
		switch($view) {
			case self::kVIEW_LIST: {
				$view = t3lib_div::makeInstance(tx_meetings_view_list);
				$view->setDisplay($this->Display);
				return $view->printMeetings($protocolUIDs, $this->accessObj);
				break;
			}
			case self::kVIEW_TABLE: {
				$view = t3lib_div::makeInstance(tx_meetings_view_table);
				$view->setDisplay($this->Display);
				return $view->printMeetings($protocolUIDs, $this->accessObj);
				break;
			}
			case self::kVIEW_RESOLUTIONS: {
				$view = t3lib_div::makeInstance(tx_meetings_view_resolutions);
				return $view->printResolutions($protocolUIDs, $this->accessObj);
				break;
			}
		}
	}

	function printSingleProtocol($protocolUID) {
		$view = t3lib_div::makeInstance(tx_meetings_view_single);
		$view->setDisplay($this->Display);
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);
		$content = '<p style="border-top:1px solid; border-bottom:1px solid; padding: 3px;">';
		$content .= $this->printLinkToPreviousMeeting($protocolUID);
		$content .= ' &lt; ';
		$content .= $this->pi_linkTP(
						$this->pi_getLL('overview'),
						array(
							$this->extKey.'[year]' => tx_meetings_div::dateToTenureYear(
																				$protocolDATA['meeting_date'],
																				$protocolDATA['sticky_date']
													  ),
						),
						$this->cache
			);
		$content .= ' &gt; ';
		$content .= $this->printLinkToNextMeeting($protocolUID);
		$content .= '</p>';
		$content .= $view->printProtocol($protocolUID, $this->accessObj);
		return $content;
	}

	function printSingleResolution($resolutionUID) {
		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $resolutionDATA['protocol']);
		$view = t3lib_div::makeInstance(tx_meetings_view_single_resolution);
		$view->setDisplay($this->Display);
		$content = '<p>'.$this->pi_linkTP(
						'&lt; '.$this->pi_getLL('to_overview'),
						array(
							$this->extKey.'[year]' => tx_meetings_div::dateToTenureYear(
																				$protocolDATA['meeting_date'],
																				$protocolDATA['sticky_date']
													  ),
						),
						$this->cache
			).'</p>';
		$content .= $view->printSingleResolution($resolutionUID, $this->accessObj);
		return $content;
	}

	/**
	 * Print if available next meeting in future, or otherwise last occured meeting
	 * as singel view.
	 * @return	string is HTML formatted meeting information
	 */
	function printLatestProtocol() {
			// get future meeting
		$resFuture =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_list
											WHERE
												tx_meetings_list.committee = '.$this->committee.'
												AND tx_meetings_list.deleted=0
												AND tx_meetings_list.hidden=0
												AND tx_meetings_list.pid >= 0
												AND tx_meetings_list.meeting_date>'.mktime().'
											  ORDER BY meeting_date ASC'
										);

		if($resFuture && $protocol = mysql_fetch_assoc($resFurture)) {
			$view = t3lib_div::makeInstance(tx_meetings_view_single);
			$view->setDisplay($this->Display);
			return $view->printProtocol($protocol['uid'], $this->accessObj);
		}
			// no future meeting available, use previous one
		else {
			$resPast =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_list
											WHERE
												tx_meetings_list.committee = '.$this->committee.'
												AND tx_meetings_list.deleted=0
												AND tx_meetings_list.hidden=0
												AND tx_meetings_list.pid >= 0
												AND tx_meetings_list.meeting_date<='.mktime().'
											  ORDER BY meeting_date DESC'
										);
			if($resPast && $protocol = mysql_fetch_assoc($resPast)) {
				$view = t3lib_div::makeInstance(tx_meetings_view_single);
				$view->setDisplay($this->Display);
				return $view->printProtocol($protocol['uid'], $this->accessObj);
			}
				// in this case we have no meeting available/database error
			else
				return tx_meetings_notifier::printNotification(
						$this->pi_getLL('no_meeting_available'),
						$this->pi_getLL('no_meeting_available_long'));
		}
	}

	/**
	 * Function formats given meetingUID to its title of format
	 * "MEEING-Title DD.MM.YY"
	 * @param	integer	$meetingUID	the unique meeting id
	 * @return	string
	 */
	function printMeetingTitle($meetingUID) {
		$meetingDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $meetingUID);

		// configure meeting title
		// the '0' is needed due to a legacy bug which once introduced "0" as default value
		if (	$meetingDATA['protocol_name']!='' &&
				$meetingDATA['protocol_name']!='0' &&
				$this->Display['ShowTitleElement']
			)
			$meetingTitle = $meetingDATA['protocol_name'];
		else
			$meetingTitle = $this->pi_getLL('meeting_from').' '.strftime('%d. %m. %Y', $meetingDATA['meeting_date']);

		return $meetingTitle;
	}

	/**
	 * Prints link to display page of detail information for a specific meeting
	 * @param	integer	$protocolUID	is uid of protocol/meeting
	 * @param	integer	$linkname	is OPTIONAL argument if the name the link should be set to something specific
	 * @return	string	HTML formatted link
	 */
	function printLinkToSingleMeeting($protocolUID, $linkname='') {
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);
		if ($linkname=='')
			$linkname = $this->printMeetingTitle($protocolUID);

		return $this->pi_linkTP(
								$linkname,
								array(
									$this->extKey.'[showUid]' => $protocolDATA['uid'],
									$this->extKey.'[year]' => tx_meetings_div::dateToTenureYear(
																	$protocolDATA['meeting_date'],
																	$protocolDATA['sticky_date']

															  ),
								),
								$this->cache
						);
	}

	function printLinkToPreviousMeeting($protocolUID) {
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);

		// print additional documents for protocols
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_list
											WHERE
												tx_meetings_list.committee = '.$this->committee.'
												AND tx_meetings_list.deleted=0 AND tx_meetings_list.hidden=0
												AND meeting_date<'.$protocolDATA['meeting_date'].'
												AND pid >= 0
											  ORDER BY meeting_date DESC'
										);
		if($res && $protocol = mysql_fetch_assoc($res)) {
			$view = t3lib_div::makeInstance(tx_meetings_view_single);
			$view->setDisplay($this->Display);
			return $this->printLinkToSingleMeeting($protocol['uid'], $this->pi_getLL('previous_meeting'));
		} else
			return '<i>'.$this->pi_getLL('previous_meeting').'</i>';
	}

	function printLinkToNextMeeting($protocolUID) {
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocolUID);

		// print additional documents for protocols
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_list
											WHERE
												tx_meetings_list.committee = '.$this->committee.'
												AND tx_meetings_list.deleted=0 AND tx_meetings_list.hidden=0
												AND meeting_date>'.$protocolDATA['meeting_date'].'
												AND pid >= 0
											  ORDER BY meeting_date ASC'
										);
		if($res && $protocol = mysql_fetch_assoc($res)) {
			$view = t3lib_div::makeInstance(tx_meetings_view_single);
			$view->setDisplay($this->Display);
			return $this->printLinkToSingleMeeting($protocol['uid'], $this->pi_getLL('next_meeting'));
		} else
			return '<i>'.$this->pi_getLL('next_meeting').'</i>';
	}

	/**
	 * This function returns an array of associative arrays (each of database structure from documents table)
	 * that contains data for all documents for one specific protocol.
	 * Documents ordered by name.
	 * @param	integer	$protocol	UID of protocol
	 * @return	array
	 **/
	function getDocumentsForProtocol($protocol) {
		$documents = array();

		// print additional documents for protocols
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_documents
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$protocol.'
											 ORDER BY sorting, crdate');

		while($res && $documentDATA = mysql_fetch_assoc($res))
			$documents[] = $documentDATA;

		return $documents;
	}

	/**
	 * This function prints a link to given resolution
	 * TODO depending on state: plain/pdf should give result
	 * @param	integer	$resolutionUID
	 * @return	string	HTML
	 **/
	function printLinkToDocument($documentUID, $onlySymbol=false) {
		$documentDATA = t3lib_BEfunc::getRecord('tx_meetings_documents', $documentUID);

		$documentDisplayName = $documentDATA['name'];

		// output if no PDF exists
		if ($documentDATA['document_file']=='') {
			if ($onlySymbol)
				return '<img src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.$this->pi_getLL('file').'" />';
			else
				return '<i>'.$documentDisplayName.'</i>';
		}

		if ($onlySymbol)
			return '<a href="'.tx_meetings_div::uploadFolder.$documentDATA['document_file'].'" '.
						'title="'.$documentDATA['name'].
						($documentDATA['description']!=''? ' - '.$documentDATA['description']: '').'">
						<img src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.$this->pi_getLL('file').'" />
						</a>';
		else
			return '<a href="'.tx_meetings_div::uploadFolder.$documentDATA['document_file'].'" '.
						'title="'.$documentDATA['description'].'">'.
						$documentDisplayName.
							'</a>';
	}

	/**
	 * This function prints a link to given PDF protocol
	 * TODO depending on state: plain/pdf should give result
	 * @param	integer	$meetingUID
	 * @return	string	HTML
	 **/
	function printLinkToProtocolPDF($meetingUID, $onlySymbol=false) {
		$meetingDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $meetingUID);

		$protocolDisplayName = $this->pi_getLL('meeting-protocol');

		// output if no PDF exists
		if ($meetingDATA['document_file']=='') {
			if ($onlySymbol)
				return '<img src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.$this->pi_getLL('file').'" />';
			else
				return '<i>'.$protocolDisplayName.'</i>';
		}

		if ($onlySymbol)
			return '<a href="'.tx_meetings_div::uploadFolder.$meetingDATA['protocol_pdf'].'" '.
						'title="'.$protocolDisplayName.'">
						<img src="'.tx_meetings_div::imgPath.'/file_additional.png" alt="'.$this->pi_getLL('file').'" />
						</a>';
		else
			return '<a href="'.tx_meetings_div::uploadFolder.$meetingDATA['protocol_pdf'].'" '.
						'title="'.$protocolDisplayName.'">'.
						$protocolDisplayName.
							'</a>';
	}

	/**
	 * This function returns an array of associative arrays (each of database structure from resolution table)
	 * that contains data for all resolutions for one specific protocol.
	 * Resolutions ordered by id, name.
	 * @param	integer	$protocol	UID of protocol
	 * @return	array
	 **/
	function getResolutionsForProtocol($protocol) {
		$resolutions = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_resolution
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$protocol.'
											 ORDER BY resolution_id, name');

		while($res && $resolutionDATA = mysql_fetch_assoc($res))
			$resolutions[] = $resolutionDATA;

		return $resolutions;
	}

	function printResolutionTitle($resolutionUID) {
		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		return $this->pi_getLL('resolution').': '.$resolutionDATA['resolution_id'].' '.$resolutionDATA['name'];
	}

	/**
	 * This function prints the link to a given resolution
	 * @param	integer	$resolutionUID
	 * @return 	string	HTML
	 **/
	function printLinkToResolution($resolutionUID) {
		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		$resolutionDisplayName = $this->pi_getLL('resolution').': '.$resolutionDATA['resolution_id'].' '.$resolutionDATA['name'];

		if ($resolutionDATA['resolution_pdf']!='')
			return '<a href="'.tx_meetings_div::uploadFolder.$resolutionDATA['resolution_pdf'].'" '.
							'title="'.$resolutionDATA['resolution_id'].'">'.
							' '.$this->pi_getLL('resolution').': '.$resolutionDATA['resolution_id'].' '.$resolutionDATA['name'].
							'</a>';
		// else return link to single view
		else
			return $this->pi_linkTP(
								$resolutionDisplayName,
								array(
									$this->extKey.'[resolutionUid]' => $resolutionUID
								),
								$this->cache
						);
	}

	function printResolution($resolutionUID) {
		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		$resolutionDisplayName = $this->pi_getLL('resolution').': '.$resolutionDATA['resolution_id'].' '.$resolutionDATA['name'];

		$content = '<h4>'.$resolutionDisplayName.'</h4>';
		if ($resolutionDATA['resolution_pdf']!='')
			return $content .= $this->printLinkToResolution($resolutionUID);
		else
			return $content .= $this->showTextareaContentRTE($resolutionDATA['resolution_text']);
	}

	function printResolutionLI($resolutionUID) {
		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		$resolutionDisplayName = $this->pi_getLL('resolution').': '.$resolutionDATA['resolution_id'].' '.$resolutionDATA['name'];
		$content = '';

		if ($resolutionDATA['resolution_pdf']!='')
			return $content .= '<li>'.$this->printLinkToResolution($resolutionUID).'</li>';
		else
			return $content .= '<li><strong>'.$resolutionDisplayName.'</strong>'.
				$this->showTextareaContentRTE($resolutionDATA['resolution_text']).'</li>';
	}

	function isDisclosed($protocol, $disclosureType) {
		$protocolDB = t3lib_BEfunc::getRecord('tx_meetings_list', $protocol);

		switch ($disclosureType) {
			case tx_meetings_div::kDISCLOSURE_REVIEWERS: {
				if ($protocolDB['reviewer_a'] &&  $protocolDB['reviewer_b'] && $protocolDB['hidden']==0)
					return true;
				break;
			}
			case tx_meetings_div::kDISCLOSURE_STANDARD: {
				if ($protocolDB['hidden']==0)
					return true;
				break;
			}
			default:
				return false;
		}
	}

	/**
	 * This functions returns a pretext-field which displays contents of a plaintext.
	 * If $text=='' function returns empty string.
	 * @param $text the text
	 * @param $wordwrap after how many characters should the text be wrapped
	 * @return HTML formatted text
	 */
	function showTextareaContent($text, $wordwrap=100) {
		if ($text=='')
			return '';
		$content = '<pre>'.
			wordwrap($text, $wordwrap, "\n").	// wrap lines after 120 letters
			'</pre>';
		return $content;
	}

	/**
	 * This function returns a preformatted text-field which displays contents of a RTE edited text.
	 * TODO probably there are some things to implement for this
	 * @param $text
	 * @return HTML formatted <div>...</div> field
	 */
	function showTextareaContentRTE($text) {
		if ($text=='')
			return '';

		$lines = preg_split( "\n[\r]", $text);
		$paragraphText="";
		foreach ($lines as $line)
			$paragraphText .= "<p>".$line."</p>";

		return '<div>'.$paragraphText.'</div>';
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_base.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_base.php']);
}
?>
