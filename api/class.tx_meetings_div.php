<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr <cola@uni-paderborn.de>
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
 * This class provides a huge amount on utility functions, e.g. for database access...
 *
 * @author Andreas Cord-Landwehr <cola@uni-paderborn.de>
 * @package TYPO3
 * @subpackage	tx_meetings
 */


require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');


/**
 * Internal, contributing functions for tx_meetings
 * @class	tx_meetings_div
 */
class tx_meetings_div {
	const imgPath			= 'typo3conf/ext/meetings/images/'; // absolute path to images
	const extKey			= 'meetings';
	const uploadFolder		= 'uploads/tx_meetings/';
	const feExtId			= 'tx_meetings';

	const kDISCLOSURE_STANDARD = 0;
	const kDISCLOSURE_REVIEWERS = 1;

	const kIMAGE_HIDDEN			= 1;
	const kIMAGE_PUBLISHED		= 2;
	const kIMAGE_REVIEW_MISSING	= 3;

	const kPROTOCOL_TYPE_PLAIN	= 0;
	const kPROTOCOL_TYPE_PDF	= 1;

	const kTERM_KIND_ACADEMIC	= 0;
	const kTERM_KIND_YEARLY		= 1;

	/**
	 *	Adds entry in pagecontent wizard form for meetings extension.
	 */
	//FIXME use translation
	function proc(&$wizardItems) {
		global $BE_USER, $LANG;
		if ($BE_USER->checkAuthMode('tt_content','',self::extKey,'explicitDeny') != FALSE) {
			$LL = $LOCAL_LANG;
			$wizardItems['plugins_'.self::feExtId.'_pi1'] = array(
				'icon' => t3lib_extMgm::extRelPath(self::extKey).'icon_tx_meetings_list.gif',
				'title' => 'Show Protocols',
				'description' => 'Plugin shows protocols of desired committee',
				'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]='.self::extKey.'_pi1');
			$wizardItems['plugins_'.self::feExtId.'_pi2'] = array(
				'icon' => t3lib_extMgm::extRelPath(self::extKey).'icon_tx_meetings_list.gif',
				'title' => 'Edit Protocols',
				'description' => 'Plugin shows forms to edit protocols of desired committee',
				'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]='.self::extKey.'_pi2');
		}
		return $wizardItems;
	}


	/**
	 * For given state of a prococol return HTML element with image to represent this state.
	 * @param	integer	$state	state as given by constants kIMAGE_*
	 * @return	string	HTML wrapped IMG element
	 */
	static function printImageProtocolState ($state) {
		switch($state) {
			case self::kIMAGE_HIDDEN:
				return '<img src="'.self::imgPath.'hidden.png" alt="Versteckt"
					title="Dieses Protokoll wurde deaktiviert und ist nicht öffentlich sichtbar." />';
				break;
			case self::kIMAGE_PUBLISHED:
				return '<img src="'.self::imgPath.'published.png" alt="Veröffentlicht"
					title="Das Protokoll wurde veröffentlicht." />';
				break;
			case self::kIMAGE_REVIEW_MISSING:
				return '<img src="'.self::imgPath.'review.png" alt="Achtung"
					title="Dieses Protokoll wurde noch nicht von genügend Leuten Korrektur gelesen." />';
				break;
		}
		return '';
	 }


	/**
	 * Function returns HTML string including image element that represents download symbol
	 * @return	string	HTML img element
	 */
	static function printDownloadSymbol () {
		return '<img src="'.self::imgPath.
			'download.png" alt="Datei speichern" title="Datei als PDF speichern." />';
	}


	/**
	 * Function returns TCA backend display compatible name for given meeting.
	 * @param	array	$params	reference for meeting parameters that are changed by this function
	 * @param	array	$pObj	backend page object (not used here!)
	 * @return	void
	 */
	static function printTCAlabelProtocol(&$params, &$pObj) {
		$meetingDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $params['row']['uid']);

		if ($meetingDATA['protocol_name']!='' && $meetingDATA['protocol_name']!='0')
			$params['title'] = date('d-m-Y',$meetingDATA['meeting_date']).' ('.$meetingDATA['protocol_name'].')';
		else
			$params['title'] = date('d-m-Y',$meetingDATA['meeting_date']);
	}


	/**
	 * This functions get some year as input and returns only the two last letters
	 * @param	integer	$year as 4 digit integer
	 * @return	string	of the last two digits
	 */
	static function twoDigits ($year) {
		$year = $year % 100;
		if ($year<10)
			return '0'.$year;
		else
			return $year;
	}


	/**
	 * Transforms a given date to the starting date of a given term.
	 * @param	integer	$meeting_date	date that shall be converted
	 * @param	boolean	$termKind	answers: is it academic or not (=yearly)?
	 * @return	integer	as 4 digit year
	 */
	static function dateToTenureYear($meetingDate, $stickyDate, $term=self::kTERM_KIND_ACADEMIC) {
			// handle sticky dates
		if ($stickyDate!=0 && $stickyDate!=1)
			return $stickyDate;

			// handle yearly turn
		if ($term==self::kTERM_KIND_YEARLY)
			return date('Y', $meetingDate);

			// else we have academic turn
		if (date('m',$meetingDate)>9)
			return date('Y',$meetingDate);
		else
			return date('Y',$meetingDate)-1;
	}


	/**
	 * This function returns an array of associative arrays (each of DB-table like structure from documents table)
	 * that contains data for all documents for one specific meeting.
	 * Documents are ordered by name.
	 * @param	integer	$meetingUID	UID of meeting
	 * @return	array	each element is one document UID
	 **/
	static function getDocumentsForMeeting ($meetingUID) {
		$documents = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_documents
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$meetingUID.'
											 ORDER BY sorting, crdate');

		while($res && $documentDATA = mysql_fetch_assoc($res))
			$documents[] = $documentDATA['uid'];

		return $documents;
	}


	/**
	 * This function returns an array of associative arrays (each of DB-table like structure from resolution table)
	 * that contains data for all resolutions for one specific meeting.
	 * Resolutions are ordered by id, name.
	 * @param	integer	$meetingUID UID of meeting
	 * @return	array	each element is one resolution UID
	 **/
	function getResolutionsForMeeting ($meetingUID) {
		$resolutions = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_resolution
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$meetingUID.'
											 ORDER BY resolution_id, name');

		while($res && $resolutionDATA = mysql_fetch_assoc($res))
			$resolutions[] = $resolutionDATA['uid'];

		return $resolutions;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_div.php'])    {
	require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_div.php']);
}
?>
