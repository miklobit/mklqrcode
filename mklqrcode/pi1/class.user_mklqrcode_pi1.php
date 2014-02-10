<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Brinkert <christianbrinkert@googlemail.com>
 *  (c) 2013 Mi³osz K³osowicz <typo3@miklobit.com>
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

require_once (PATH_tslib . 'class.tslib_pibase.php');


/**
 * Add new value with qr code  Extends the tt_address extension by
 * adding new fields to the marker
 */
class user_mklqrcode_pi1 extends tslib_pibase {  

	var $typo3tempDir = 'typo3temp/pics/';
	var $errorCorrection = 'L';
	var $imageFormat = 'png';
	var $bgColor = 0xFFFFFF;
	var $fgColor = 0x000000;
	var $codeSize = 100;	
	var $pixelSize = 2;
	var $borderSize = 2;
	var $enableBorder = true;
	var $filename = null;	
	var $pi_flexform = null;
	
	/**
	 * Create qr code by given plugin data and configuration
	 * 
	 * @param array $content	current tt_content data
	 * @param array $conf		plugin typoscript configurations
	 * @return string
	 */
	function main($content, $conf){
		$this->conf = $conf;
		$stringToQrCode = null;
		
		// init flexform
		$this->pi_initPIflexForm();
		
		// set configuration params from plugin
		$this->setConfigurationFromCE(); 
		
		// set action
		$action = $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeType', 'sDEF');
		
		switch ($action){			
			case 1:
				// URL: set filename
				$this->filename = md5($this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeUrl', 'sDEF')
								    . implode(',', $this->getConfigArray()));
				// set string to build qrcode from
				$stringToQrCode = $this->escapeValue(
								  $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeUrl', 'sDEF'));		
				break;
			case 2:
				// PHONE: set filename
				$this->filename = md5($this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodePhone', 'sDEF')
								    . implode(',', $this->getConfigArray()));
				// set string to build qrcode from
				$stringToQrCode = 'tel:'
				   			    . $this->escapeValue(
				   			      $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodePhone', 'sDEF'));		
				break;
			case 3:
				// EMAIL: set filename
				$this->filename = md5($this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeEmail', 'sDEF')
								    . implode(',', $this->getConfigArray()));
				// set string to build qrcode from
				$stringToQrCode = 'mailto:'
				   			    . $this->escapeValue(
				   			      $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeEmail', 'sDEF'));
				break;
			case 4:
				// enable all vcard fields
				$this->enableAllVCardFields();
				
				// VCARD: build address array
				$address = $this->getVcardFromCE();
				// set filename
				$this->filename = md5( implode(',',
								  array_merge($address, $this->getConfigArray())) );
				// parse address data
				$stringToQrCode = $this->parseAddessToVCardString($address);
	
				break;
			case 5:
				// CURRENT PAGE: get current url and set filename
				$currentUrl = t3lib_div::locationHeaderUrl(
							  t3lib_div::linkThisScript(
							  array('id' => htmlspecialchars($GLOBALS['TSFE']->id))
							  ));	
		
				// set filename
				$this->filename = md5($currentUrl . implode(',', $this->getConfigArray()));
				// set string to build qrcode from
				$stringToQrCode = $this->escapeValue($currentUrl);
				break;
			default:
				// TEXT: set filename
				$this->filename = md5( $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeText', 'sDEF')
				   					 . implode(',', $this->getConfigArray()) );
				// set string to build qrcode from
				$stringToQrCode = $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrCodeText', 'sDEF');
		}
		
		// build qrcode
		$qrCodeUri = $this->buildQrCode($stringToQrCode);
				
		if ($qrCodeUri){
			
			switch ($this->imageFormat) {
				case 'png':
					// set image resource to the typoscript
					$this->conf['qrcodePNG.']['file'] = $qrCodeUri;					
					// get qrcode uri
					$qrCodeImage = $this->cObj->cObjGetSingle($this->conf['qrcodePNG'], $this->conf['qrcodePNG.']);
				break;
				
				case 'svg':
					// set image resource to the typoscript
					$this->conf['qrcodeSVG.']['src'] = $qrCodeUri;	
					if( $this->codeSize > 0 ) {
						$this->conf['qrcodeSVG.']['width'] = $this->codeSize ;
						$this->conf['qrcodeSVG.']['height'] = $this->codeSize ;
					}				
					// get qrcode uri
					$qrCodeImage = $this->cObj->cObjGetSingle($this->conf['qrcodeSVG'], $this->conf['qrcodeSVG.']);
				break;
			}
			//debug($this->conf,'this->conf');				
			//debug($qrCodeImage,'qrCodeImage');
			
			// return qrcode		
			return $qrCodeImage;
		} 
		
		return false;
	}
	
	
	
	
	
	/**
	 * Create and return qr-code as image by typoscript 
	 * @param unknown_type $content
	 * @param unknown_type $conf
	 */
	function getQrCodeByTS($content, $conf){
		$this->conf = $conf;
		
		// errorCorrection
		$temp = $this->conf['userFunc.']['errorCorrection'];
		if ($temp && in_array($temp, array('L','M','Q','H'))) $this->errorCorrection = $temp;
		
		// pixelSize
		$temp = (int) $this->conf['userFunc.']['pixelSize'];
		if (0 < $temp) $this->pixelSize = $temp;
		
		// borderSize
		$temp = (int) $this->conf['userFunc.']['borderSize'];
		if (0 < $temp) $this->borderSize = $temp;
		
		// enableBorder
		$this->enableBorder = (true === (boolean) $this->conf['userFunc.']['enableBorder'])
							? true : false;		
		
		// create output string
		$qrCodeString = $this->escapeValue($this->cObj->cObjGetSingle($this->conf['userFunc.']['qrCodeString'], 
												  					  $this->conf['userFunc.']['qrCodeString.']));
	
		// build qrcode if string to parse was given
		if ($qrCodeString){
			// set filename
			$this->filename = md5($qrCodeString . implode(',', $this->getConfigArray()));

			// build qrcode
			$qrCodeUri = $this->buildQrCode($qrCodeString);		
			// set image resource to the typoscript
			$this->conf['userFunc.']['qrCodeImage.']['file'] = $qrCodeUri;
	
			// get qrcode uri
			$qrCodeImage = $this->cObj->cObjGetSingle($this->conf['userFunc.']['qrCodeImage'], 
													  $this->conf['userFunc.']['qrCodeImage.']);
			
			return $qrCodeImage;
		} 
	
		return NULL; 
	}
	
	
	
	
	
	/**
	 * Create and return qr-code img_resource by typoscript reference
	 * @param unknown_type $content
	 * @param unknown_type $conf
	 */
	function getQrCodeResourceByTS($content, $conf){		
		$this->conf = $conf;
		
		// errorCorrection
		$temp = $this->conf['userFunc.']['errorCorrection'];
		if ($temp && in_array($temp, array('L','M','Q','H'))) $this->errorCorrection = $temp;
		
		// pixelSize
		$temp = (int) $this->conf['userFunc.']['pixelSize'];
		if (0 < $temp) $this->pixelSize = $temp;
		
		// borderSize
		$temp = (int) $this->conf['userFunc.']['borderSize'];
		if (0 < $temp) $this->borderSize = $temp;
		
		// enableBorder	
		$this->enableBorder = (true === (boolean) $this->conf['userFunc.']['enableBorder'])
							? true : false;
							
		// create output string
		$qrCodeString = $this->escapeValue($this->cObj->cObjGetSingle($this->conf['userFunc.']['qrCodeString'], 
												  					  $this->conf['userFunc.']['qrCodeString.']));

		// build qrcode if string to parse was given
		if ($qrCodeString){
			// set filename
			$this->filename = md5($qrCodeString . implode(',', $this->getConfigArray()));

			// build qrcode
			$qrCodeUri = $this->buildQrCode($qrCodeString);		
			// set image resource to the typoscript
			$this->conf['userFunc.']['qrCodeImgResource.']['file'] = $qrCodeUri;
	
			// get qrcode uri
			$qrCodeImage = $this->cObj->cObjGetSingle($this->conf['userFunc.']['qrCodeImgResource'], 
													  $this->conf['userFunc.']['qrCodeImgResource.']);	
			return $qrCodeImage;
		} 			
		return NULL; 
	}
	
	
	
	
	/**
	 * Return configuration array
	 * @return array
	 */
	function getConfigArray(){
		return $config = array( 'imageFormat' => $this->imageFormat,
								'codeSize' => $this->codeSize,
								'bgColor' => $this->bgColor,
		                        'fgColor' => $this->fgColor,
		                        'errorCorrection' => $this->errorCorrection,
					   			'pixelSize' => $this->pixelSize,
					   			'enableBorder' => $this->enableBorder,
					   			'borderSize' => $this->borderSize
					  		   );		
	}
	
	
	
	
	/**
	 * Build vCard address array from definition of current content element
	 * @return array
	 */
	function getVcardFromCE(){
		return $vCard = array('first_name' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardFirstName', 'sDEF'),
							  'middle_name' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardMiddleName', 'sDEF'),
							  'last_name' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardLastName', 'sDEF'),
							  'title' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardTitle', 'sDEF'),
							  'birthday' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardBirthday', 'sDEF'),
							  'company' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardCompany', 'sDEF'),
						      'description' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardDescription', 'sDEF'),
							  'image' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardImage', 'sDEF'),
							  'address' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardStreet', 'sDEF'),
							  'zip' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardZip', 'sDEF'),
							  'city' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardCity', 'sDEF'),
							  'country' =>$this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardCountry', 'sDEF') ,
							  'region' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardRegion', 'sDEF'),
							  'phone' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardPhone', 'sDEF'),
							  'fax' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardFax', 'sDEF'),
							  'mobile' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardMobile', 'sDEF'),
							  'email' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardEmail', 'sDEF'),
							  'www' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'vcardWww', 'sDEF')
							 );
	}
	
	
	
	/**
	 * Set configuration defined by content element
	 * @return void
	 */
	function setConfigurationFromCE(){	
		
		// imageFormat
		$temp = $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.imageFormat', 'sLAYOUT');
		if ($temp && in_array($temp, array('png','svg','eps'))) $this->imageFormat = $temp;

		// codeSize (for vector formats)
		$temp = (int) $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.codeSize', 'sLAYOUT');
		if (0 < $temp) $this->codeSize = $temp;		
		
		// errorCorrection
		$temp = $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.errorCorrection', 'sLAYOUT');
		if ($temp && in_array($temp, array('L','M','Q','H'))) $this->errorCorrection = $temp;
		
		// fgColor
		$temp = (int)hexdec($this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.fgColor', 'sLAYOUT'));
		$this->fgColor = $temp;	
		//debug($this->fgColor,'fgcolor');	

		// bgColor
		$temp = (int)hexdec($this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.bgColor', 'sLAYOUT'));
		$this->bgColor = $temp;	
		// debug($this->bgColor,'bgcolor');		
		
		// pixelSize
		$temp = (int) $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.pixelSize', 'sLAYOUT');
		if (0 < $temp) $this->pixelSize = $temp;
		
		// borderSize
		$temp = (int) $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.borderSize', 'sLAYOUT');
		if (0 < $temp) $this->borderSize = $temp;
		
		// enableBorder
		$this->enableBorder = (true === (boolean) $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'qrcode.enableBorder', 'sLAYOUT'))
							? true : false;									
	}
	
	
	
	/**
	 * Retruns the extended marker array to tt_address. The QR-Code will be 
	 * inserted as image and as imgage resource url
	 * 
	 * @param array $markerArray
	 * @param array $address
	 * @param object $lConf
	 * @param object $pObj
	 * @return array 		extended marker array
	 */
	function extraItemMarkerProcessor($markerArray, $address, $lConf, $pObj) {
		$this->address = $address;
		$this->conf = $lConf;
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
				
		// check if at least a family/last name was given
		$addressString = $this->parseAddessToVCardString($address);
						
		// set configuration parameters
		$this->errorCorrection = in_array($this->conf['mklqrcode.']['vcard.']['errorCorrection'], 
								 		  array('L','M','Q','H'))
						 	   ? $this->conf['mklqrcode.']['vcard.']['errorCorrection'] : 'L';
		$this->pixelSize = (0 < (int) $this->conf['mklqrcode.']['vcard.']['pixelSize'])
				          ? (int) $this->conf['mklqrcode.']['vcard.']['pixelSize'] : 4;
		$this->enableBorder = (true === (boolean) $this->conf['mklqrcode.']['vcard.']['enableBorder'])
					        ? true : false;
		$this->borderSize = (0 < (int) $this->conf['mklqrcode.']['vcard.']['borderSize'])
					      ? (int) $this->conf['mklqrcode.']['vcard.']['borderSize'] : 2;
									      
		// set filename
		$this->filename = md5( implode(',', 
						  array_merge($this->address, $this->getConfigArray()) ));
									  
		// build qrcode
		$qrCodeUri = $this->buildQrCode($addressString);

		if ($qrCodeUri){
			// set image resource to the typoscript
			$this->conf['mklqrcode.']['vcard.']['qrcode.']['file'] = $qrCodeUri;
			
			// extend marker array
		    $markerArray['###CBQRCODE###'] = 
		    		$this->cObj->cObjGetSingle('IMAGE', $this->conf['mklqrcode.']['vcard.']['qrcode.']);
		    $markerArray['###CBQRCODEURI###'] = $qrCodeUri;
		      
			// return marker array
			return $markerArray;
		}
		
		return $markerArray;
	}

	
	
	
	/**
	 * Build grafically qr-code with given text string
	 * @param string 	$testString		text to included in qr-code image
	 * @param string 	$filename 		filename to cache
	 * @return 
	 */
	function buildQrCode($qrCodeString){

		if ($this->filename){
			// require qrcode library
			require_once(PATH_typo3conf.'ext/mklqrcode/res/phpqrcode/phpqrcode.php');
			
			// make sure that filename is not null
			if (null === $this->filename) $this->filename = md5(time());
			
			// set file location
			$qrCodeUri = $this->typo3tempDir . 'mklqrcode_'. md5($this->filename) .'.'.$this->imageFormat; 

			// if file allready exists return url 
			if (file_exists($qrCodeUri)){
				// file already exists, so return resource url
				return $qrCodeUri;	
				
			} else {		
				$borderSize = ($this->enableBorder)? $this->borderSize : 0;
			
				// create qr code and save it				
			switch ($this->imageFormat) {	
				case 'png':
					QRcode::png($qrCodeString, 
					            $qrCodeUri, 
					            $this->errorCorrection, 
								$this->pixelSize, 
								$borderSize,
	 							false,
	 							$this->bgColor,
								$this->fgColor);
                    break ;
				case 'svg':
					QRcode::svg($qrCodeString, 
					            $qrCodeUri, 
					            $this->errorCorrection, 
								$this->pixelSize, 
								$borderSize,
								false,
								$this->bgColor,
								$this->fgColor);
					break;			
			}				
													
				// return resource url
				return $qrCodeUri;
			}
		} 
	
		return false;
	}
	
	
	
	
	/**
	 * Enable all vCard fields to override typoscript configuration if individual vCards 
	 * should be created
	 */
	function enableAllVCardFields(){		
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['birthday'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['organisation'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['description'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['image'] = 
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['phone'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['fax'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['cell'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['email'] =
		$this->conf['mklqrcode.']['vcard.']['enableFields.']['url'] = true;
	}
	
	
	
	/**
	 * Enter description here ...
	 * @param array $adr
	 */
	function parseAddessToVCardString($adr){
		// check for mandatory values
		if (empty($adr['first_name'])
		 && empty($adr['middle_name'])
		 && empty($adr['last_name']) ) return false;
		
		$str  = array();
		// set begin-string
		$str[] = 'BEGIN:VCARD';
		
		// add version string
		$str[] = 'VERSION:2.1';
			
	   	// add FN (formated name)
	    $fullname = '';	
    	$names = array();
    	if (!empty($adr['title'])) $names[] = $adr['title'];
    	if (!empty($adr['first_name'])) $names[] = $adr['first_name'];
    	if (!empty($adr['middle_name'])) $names[] = $adr['middle_name'];
    	if (!empty($adr['last_name'])) $names[] = $adr['last_name'];
    	
	   	$fullname = implode(' ', $names);	
	   	    
	    $str[] = 'FN:'. $this->escapeValue($fullname);
		
		// add N (name)
		$str[] = 'N:'. $this->escapeValue($adr['last_name']) .';'	// add family name
			   . $this->escapeValue($adr['first_name']) .';'		// add given name
			   . $this->escapeValue($adr['middle_name']) .';'		// add additional name
			   . $this->escapeValue($adr['title']) .';';			// add honorific prefix
			    
	    // add BDAY (birthday) 
	    if (!empty($adr['birthday']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['birthday'] )
	    	$str[] = 'BDAY;'. $this->escapeValue(strftime("%Y-%m-%d" , $adr['birthday']));
				   
	    // add ORG (organisation)
	    if (!empty($adr['company']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['organisation'] )
	    	$str[] = 'ORG:'. $this->escapeValue($adr['company']);
			   
	    // add ORG (organisation)
	    if (!empty($adr['description']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['description'] )
	    	$str[] = 'TITLE:'. $this->escapeValue($adr['description']);
	    
	    // add PHOTO (picture of contact)
	    if (!empty($adr['image']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['image'] ){

	    	// fetch assigned photos
	    	$photoFiles = split(',', $adr['image']);
	    	$photoFile = PATH_site .'typo3temp/pics/'. $photoFiles[0];
    	
	    	if ($photoFile[0] && file_exists($photoFile)){
	    		$this->conf['mklqrcode.']['vcard.']['photo.']['file.']['10.']['file'] = 'typo3temp/pics/'. $photoFiles[0];
	    			    	    	
		    	$photo = $this->cObj->cObjGetSingle($this->conf['mklqrcode.']['vcard.']['photo'],
		    										$this->conf['mklqrcode.']['vcard.']['photo.']);
		    										
		    	$str[] = 'PHOTO;VALUE=uri:'. t3lib_div::locationHeaderUrl() . $photo;	
	    	}								
	    }	    

	    // add ADR (adress information)
	    if ($adr['address'] || $adr['zip'] || $adr['city'] || $adr['country'] || $adr['region']){
		    $str[] = 'ADR;TYPE='
		    	   . $this->conf['mklqrcode.']['vcard.']['fieldTypes.']['adrType'] .':' 	// address type
		    	   . ';' 																	// extended address
		    	   . ';' 																	// post office box
		    	   . $this->parseLinebreaks($this->escapeValue($adr['address'])) .';'	 	// street;
		    	   . $this->escapeValue($adr['city']) .';'									// city
		    	   . $this->escapeValue($adr['region']) .';'								// region
		    	   . $this->escapeValue($adr['zip']) .';' 									// zip
		    	   . $this->escapeValue($adr['country']);									// country
	    }
	    	
	    // add TEL (phone information)
	    if (!empty($adr['phone']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['phone'] )
	    	$str[] = 'TEL;TYPE='. $this->conf['mklqrcode.']['vcard.']['fieldTypes.']['telType'] .':' 	
	    		   . $this->escapeValue($adr['phone']);
	    
	    // add FAX (fax information)
	    if (!empty($adr['fax']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['fax'] )
	    	$str[] = 'TEL;TYPE='. $this->conf['mklqrcode.']['vcard.']['fieldTypes.']['faxType'] .':' 	
	    		   . $this->escapeValue($adr['fax']);
	    
	    // add CELL (mobile information)
	    if (!empty($adr['mobile']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['cell'] )
	    	$str[] = 'TEL;TYPE='. $this->conf['mklqrcode.']['vcard.']['fieldTypes.']['cellType'] .':' 
	    		   . $this->escapeValue($adr['mobile']);
	    
	    // add EMAIL (email information)
	    if (!empty($adr['email']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['email'] )
	    	$str[] = 'EMAIL;TYPE='. $this->conf['mklqrcode.']['vcard.']['fieldTypes.']['emailType'] .':' 
	    		   . $this->escapeValue($adr['email']);
	    
	    // add URL (url of website information)
	    if (!empty($adr['www']) && $this->conf['mklqrcode.']['vcard.']['enableFields.']['url'] )
	    	$str[] = 'URL:http://'. $this->escapeValue($adr['www']);						
	    
	    // add URL (url of website information)
	    $str[] = 'REV:'. strftime("%Y%m%dT%H%M%SZ", time());								
	    
	    		   
		// set end-string
		$str[] = 'END:VCARD';
				
		return implode(PHP_EOL, $str);
	}
	
	
	/**
	 * Escape all comma and semicolons in a string, by adding a 
	 * backslash in front of each "," or ";"
	 * @param string $string
	 * @return strring
	 */
	function escapeValue($string){
		return preg_replace('/([,;])/', '\\\\$1', $string);
	}
	
	
	/**
	 * Substitute linebreaks with '\n'
	 * @param string $string	string to be parsed
	 * @return string
	 */
	function parseLinebreaks($string){
		return str_replace(array("\r\n", "\r", "\n"), '\n', $string);
	}
	
}

?>
