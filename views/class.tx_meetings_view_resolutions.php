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
class tx_meetings_view_resolutions extends tx_meetings_view_base {

	public $year          = 0;
	private $disclosure    = tx_meetings_div::kDISCLOSURE_STANDARD;
	public $userNotInIPrestrictedNetwork	= true;

	var $LANG;						// language object
	var $cObj;

	function __construct () {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->LANG = t3lib_div::makeInstance('language');
		// initialization procedure for language
		$this->LLkey = $GLOBALS['TSFE']->lang;
		$this->LOCAL_LANG_loaded = 0;
		$this->pi_loadLL();
	}


	/**
	 * This function prints a <UL> list of all resolutions
	 * @param uid of committee
	 * @return string
	 */
	function printResolutions ($committee, $accessObj) {
		$content = '';
		$resolutions = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT tx_meetings_resolution.uid as uid, tx_meetings_list.meeting_date as meeting_date
											FROM tx_meetings_resolution, tx_meetings_list
											WHERE
												tx_meetings_resolution.deleted=0
												AND tx_meetings_resolution.hidden=0
												AND tx_meetings_resolution.protocol=tx_meetings_list.uid
												AND tx_meetings_list.committee='.$committee.'
												AND tx_meetings_list.deleted=0
												AND tx_meetings_list.hidden=0
											 ORDER BY tx_meetings_list.meeting_date DESC,
												tx_meetings_resolution.resolution_id DESC,
												tx_meetings_resolution.name');

		$resolutionDATA = array();
		while($res && $resolutionDATA = mysql_fetch_assoc($res))
			$resolutions[] = $resolutionDATA;

		// create output
		$content .= '<ul>';
		foreach($resolutions as $uid) {
			if ($accessObj->isAccessAllowedResolutions($uid['meeting_date']))
				$content .= '<li>'.$this->printLinkToResolution($uid['uid']).'</li>';
			else
				$content .= '<li title="'.$this->pi_getLL('access_denied').'"><i>'.$this->printResolutionTitle($uid['uid']).'</i></li>';
		}
		$content .= '</ul>';

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_resolutions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_resolutions.php']);
}

?>