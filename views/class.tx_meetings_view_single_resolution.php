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
class tx_meetings_view_single_resolution extends tx_meetings_view_base {

	public $year          = 0;
	private $disclosure    = tx_meetings_pi1::kDISCLOSURE_STANDARD;
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
	function printSingleResolution ($resolutionUID, $accessObj) {
		$content = '';
		$this->pi_loadLL();

		$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolutionUID);
		$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $resolutionDATA['protocol']);

		/*
		 * start with resolution information
		 */

		// This sets the title of the page for use in indexed search results:
		if ($resolutionDATA['title'])
			$GLOBALS['TSFE']->indexedDocTitle=$this->printResolutionTitle($resolutionUID);

		// configure protocol title
		$content .= '<h2>'.$this->printResolutionTitle($resolutionUID).'</h2>';

		if ($accessObj->isAccessAllowedResolutions($protocolDATA['meeting_date'])==false)
			$content .= tx_meetings_notifier::printNotification('Beschluss Zugriff','Sie haben nicht die Rechte um auf diesen Beschluss zuzugreifen.');
		else
			$content .= '<div>'.$this->showTextareaContentRTE($resolutionDATA['resolution_text']).'</div>';

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_single_resolution.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_single_resolution.php']);
}

?>