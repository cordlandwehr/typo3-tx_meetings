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
* This class provides a pretty small notification functionality
*
* @author Andreas Cord-Landwehr <cola@uni-paderborn.de>
*/


require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');


/** @class tx_meetings_notifier
 *
 * This class is meant to allow a unified and nice output of infos,
 * warnings, and errors
 *
 */
class tx_meetings_notifier {
	const imgPath			= 'typo3conf/ext/meetings/images/'; // absolute path to images
	const extKey			= 'meetings';

	const kNOTIFICATION_INFO	= 0;
	const kNOTIFICATION_WARNING	= 1;
	const kNOTIFICATION_ERROR	= 2;

	/**
	 * Returns HTML string in type of a <div> area that represents notification
	 * @param	string	$title	of notification
	 * @param	string	$notification	text
	 * @param	integer $urgency	is optional parameter in type of of self::kNOTIFICATION_*
	 */
	static function printNotification($title, $notification, $urgency=self::kNOTIFICATION_INFO) {
		$notificationDiv = '<div>';

		// TODO not implemented, yet
		switch($urgency) {
			default: break;
		}
		$notificationDiv .= '<strong>'.$title.'</strong><br />';
		$notificationDiv .= $notification;

		$notificationDiv .= '</div>';
		return $notificationDiv;
	 }
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_notifier.php'])    {
    require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_notifier.php']);
}
?>