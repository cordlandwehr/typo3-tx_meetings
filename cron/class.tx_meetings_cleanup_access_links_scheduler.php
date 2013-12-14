<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Andreas Cord-Landwehr
 * Fachschaft Mathematik/Informatik, Uni Paderborn
 *
 * You can redistribute this file and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation;
 * either version 2 of the License, or (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This file is distributed in the hope that it will be useful for ministry,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the file!
 ***************************************************************/

require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');

class tx_meetings_cleanup_access_links_scheduler
    extends tx_scheduler_Task
    implements tx_scheduler_AdditionalFieldProvider
{
    var $lifetime;

	public function execute() {
		// global extension settings
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['meetings']);

		if ($handle = opendir(PATH_site . tx_meetings_div::documentFolder)) {
			while (($dir = readdir($handle)) !== false) {
				if (!is_dir(PATH_site . tx_meetings_div::documentFolder . $dir)) {
					continue;
				}
				if ($dir == "." || $dir == "..") {
					continue;
				}

				$subHandle = opendir(PATH_site . tx_meetings_div::documentFolder . $dir);
				$containsLinks = false;
				while (($file = readdir($subHandle)) !== false) {
					if (is_dir(PATH_site . tx_meetings_div::documentFolder . $dir . $file)) {
						debug("Found subfolder, not valid filestructure: aborting");
						$containsLinks = true;
						break;
					}
					if ($file == "." || $file == "..") {
						continue;
					}
					if ($file == ".htaccess") {
						continue;
					}
					// get information about symbolic links
					$stat = lstat(PATH_site . tx_meetings_div::documentFolder . $dir . '/' . $file);
					if ($stat["ctime"] < time() - $this->lifetime * 60 * 60) {
						unlink(PATH_site . tx_meetings_div::documentFolder . $dir . '/' . $file);
						continue;
					}
					$containsLinks = true;
				}
				closedir($subHandle);
				// remove complete folder if no symlinks remain
				if (!$containsLinks) {
					unlink(PATH_site . tx_meetings_div::documentFolder . $dir . '/' . ".htaccess");
					rmdir(PATH_site . tx_meetings_div::documentFolder . $dir);
				}
			}
			closedir($handle);
		}

        return true;
	}

    /**
     * \see Interface tx_scheduler_AdditionalFieldProvider
     */
    public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
        if (empty($taskInfo['lifetime'])) {
            if($parentObject->CMD == 'edit') {
                $taskInfo['lifetime'] = $task->lifetime;
            } else {
                $taskInfo['lifetime'] = '0';
            }
        }

        // Write the code for the field
        $fieldCode = '<input name="tx_scheduler[lifetime]" id="lifetime" value="'.$taskInfo['lifetime'].'" />';

        $additionalFields = array();
        $additionalFields[$fieldID] = array(
            'code'     => $fieldCode,
            'label'    => 'File Link Lifetime (hours)'
        );

        return $additionalFields;
    }

    /**
     * \see Interface tx_scheduler_AdditionalFieldProvider
     */
    public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
        $submittedData['lifetime'] = intval($submittedData['lifetime']);
        return true;
    }

    /**
     * \see Interface tx_scheduler_AdditionalFieldProvider
     */
    public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
        $task->lifetime = $submittedData['lifetime'];
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/cron/class.tx_meetings_cleanup_access_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/cron/class.tx_meetings_cleanup_access_links.php']);
}
?>
