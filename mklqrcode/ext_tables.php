<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// register plugin
t3lib_extMgm::addPlugin(array(
	'LLL:EXT:'. $_EXTKEY .'/lang/locallang_csh.xml:tx_qrcode_pi1.title',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');


// set locallang file
t3lib_extMgm::addLLrefForTCAdescr('tx_mklqrcode', 'EXT:mklqrcode/lang/locallang_csh.xml');

// add static files
t3lib_extMgm::addStaticFile($_EXTKEY,'static/mklqrcode/', 'QR-Codes');


// set plugin signature
$pluginSignature = str_replace('_','',$_EXTKEY) . '_pi1';
// add some new fields by flexform definition
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
// define flexform file
t3lib_extMgm::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/pi1/flexform.xml');

// exclude some default backend fields, like: layout, select_key, pages and recursive
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';

?>