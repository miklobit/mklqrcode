<?php

/**
 * extend tt_address with additional marker for generated qr code
 */

// include qr code to plugin list
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.user_mklqrcode_pi1.php', '_pi1', 'list_type', 0);


// include user_cbqrcdes to the tt_address hook array if tt_address is installed and version > 1.0.0
if( 1000000 < t3lib_div::int_from_ver(t3lib_extMgm::getExtensionVersion('tt_address'))){
	
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_address']['extraItemMarkerHook'][] = 
		'EXT:mklqrcode/pi1/class.user_mklqrcode_pi1.php:user_mklqrcode_pi1';
}

?>
