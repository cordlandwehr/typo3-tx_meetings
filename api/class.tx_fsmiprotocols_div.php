<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr (cola@uni-paderborn.de)
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
*/


require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');


/**
 * Script Class to download files as defined in reports
 *
 */
class tx_fsmiprotocols_div {
	const imgPath			= 'typo3conf/ext/fsmi_protocols/images/'; // absolute path to images
	const extKey			= 'fsmi_protocols';
	const uploadFolder		= 'uploads/tx_fsmiprotocols/';

	const kDISCLOSURE_STANDARD = 0;
	const kDISCLOSURE_REVIEWERS = 1;

	const kIMAGE_HIDDEN			= 1;
	const kIMAGE_PUBLISHED		= 2;
	const kIMAGE_REVIEW_MISSING	= 3;

	const kPROTOCOL_TYPE_PLAIN	= 0;
	const kPROTOCOL_TYPE_PDF	= 1;

	const kTERM_KIND_ACADEMIC	= 0;
	const kTERM_KIND_YEARLY		= 1;

	static function printImageProtocolState($state) {
		switch($state) {
			case self::kIMAGE_HIDDEN:
				return '<img src="'.self::imgPath.'hidden.png" alt="Versteckt" title="Dieses Protokoll wurde deaktiviert und ist nicht öffentlich sichtbar." />';
				break;
			case self::kIMAGE_PUBLISHED:
				return '<img src="'.self::imgPath.'published.png" alt="Veröffentlicht" title="Das Protokoll wurde veröffentlicht." />';
				break;
			case self::kIMAGE_REVIEW_MISSING:
				return '<img src="'.self::imgPath.'review.png" alt="Achtung" title="Dieses Protokoll wurde noch nicht von genügend Leuten Korrektur gelesen." />';
				break;
		}
		return '';
	 }

	static function printDownloadSymbol() {
		return '<img src="'.self::imgPath.'download.png" alt="Datei speichern" title="Datei als PDF speichern." />';
	}

	static function printTCAlabelProtocol ($params, $pObj) {
		$protocolDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_list', $params['row']['uid']);

		if ($protocolDATA['protocol_name']!='' && $protocolDATA['protocol_name']!='0')
			$params['title'] = date('d-m-Y',$protocolDATA['meeting_date']).' ('.$protocolDATA['protocol_name'].')';
		else
			$params['title'] = date('d-m-Y',$protocolDATA['meeting_date']);
	}


	/**
	 * This functions get some year as input and returns only the two last letters
	 * @param $year as 4 digit integer
	 * @return string of the last two digits
	 */
	static function twoDigits($year) {
		$year = $year % 100;
		if ($year<10)
			return '0'.$year;
		else
			return $year;
	}

	/**
	 * Transforms a given date to the starting date of a given term.
	 * @param $meeting_date
	 * @param $termKind is it academic or yearly?
	 * @return integer as 4 digit year
	 */
	static function dateToTenureYear($meeting_date, $sticky_date, $term=self::kTERM_KIND_ACADEMIC) {
		if ($sticky_date!=0 && $sticky_date!=1)
			return $sticky_date;
		// TODO academic turn is only setting at moment
		if (date('m',$meeting_date)>9)
			return date('Y',$meeting_date);
		else
			return date('Y',$meeting_date)-1;
	}

	/**
	 * This function returns an array of associative arrays (each of database structure from documents table)
	 * that contains data for all documents for one specific protocol.
	 * Documents ordered by name.
	 * @param $protocol UID of protocol
	 * @return array
	 **/
	static function getDocumentsForMeeting($meetingUID) {
		$documents = array();

		// print additional documents for protocols
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_fsmiprotocols_documents
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$meetingUID.'
											 ORDER BY sorting, crdate');

		while($res && $documentDATA = mysql_fetch_assoc($res))
			$documents[] = $documentDATA['uid'];

		return $documents;
	}

	/**
	 * This function returns an array of associative arrays (each of database structure from resolution table)
	 * that contains data for all resolutions for one specific protocol.
	 * Resolutions ordered by id, name.
	 * TODO deprecate other version
	 * @param $protocol UID of protocol
	 * @return array
	 **/
	function getResolutionsForMeeting($meeting) {
		$resolutions = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_fsmiprotocols_resolution
											WHERE
												deleted=0 AND hidden=0
												AND protocol='.$meeting.'
											 ORDER BY resolution_id, name');

		while($res && $resolutionDATA = mysql_fetch_assoc($res))
			$resolutions[] = $resolutionDATA['uid'];

		return $resolutions;
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/api/class.tx_fsmiprotocols_div.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/api/class.tx_fsmiprotocols_div.php']);
}
?>