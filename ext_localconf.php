<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
// FOLLOWING makes problems with old Typo3
//t3lib_extMgm::addUserTSConfig('
//	options.saveDocNew.tx_meetings_list=1
//');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_meetings_pi1 = < plugin.tx_meetings_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_meetings_pi1.php','_pi1','list_type',0);

t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.shortcut.20.0.conf.tx_meetings_list = < plugin.'.t3lib_extMgm::getCN($_EXTKEY).'_pi1
	tt_content.shortcut.20.0.conf.tx_meetings_list.CMD = singleView
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_meetings_pi2.php','_pi2','list_type',0);

t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.shortcut.20.0.conf.tx_meetings_list = < plugin.'.t3lib_extMgm::getCN($_EXTKEY).'_pi2
	tt_content.shortcut.20.0.conf.tx_meetings_list.CMD = singleView
',43);

$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['meetings'] = 'EXT:meetings/hooks/class.tx_meetings_realurl.php:&tx_meetings_realurl->addRealURLConfig';
?>
