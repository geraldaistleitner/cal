<?php
/***************************************************************
* Copyright notice
*
* (c) 2005 Foundation for Evangelism
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://webempoweredchurch.org) ministry of the Foundation for Evangelism
* (http://evangelize.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
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

require_once(t3lib_extMgm::extPath('cal').'model/class.tx_cal_organizer_partner.php');
require_once(t3lib_extMgm::extPath('cal').'service/class.tx_cal_base_service.php');

/**
 * Base model for the calendar organizer.  Provides basic model functionality that other
 * models can use or override by extending the class.  
 *
 * @author Mario Matzulla <mario@matzullas.de>
 * @package TYPO3
 * @subpackage cal
 */
class tx_cal_organizer_partner_service extends tx_cal_base_service {

 	var $extensionIsNotLoaded = false;
 	
 	function setCObj(&$cObj){
		$this->cObj = $cObj;
		$this->cObj->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cal']);
		$useOrganizerStructure = ($this->cObj->confArr['useOrganizerStructure']?$this->cObj->confArr['useOrganizerStructure']:'tx_cal_organizer');
 	 	if($useOrganizerStructure!='tx_partner_main'){
 	 		$this->extensionIsNotLoaded = true;
 	 		return;
 	 	}
 	 	require_once(t3lib_extMgm::extPath('partner').'api/class.tx_partner_main.php');
		$this->controller = &$cObj->conf[$this->prefixId];
		$this->rightsObj = &$this->controller->rightsObj;
	}
 	
	/**
	 * Looks for an organizer with a given uid on a certain pid-list
	 * @param	array		$conf		The configuration array
	 * @param	integer		$uid		The uid to search for
	 * @param	string		$pidList	The pid-list to search in
	 * @return	object	A tx_cal_organizer_partner object
	 */
	function find($uid, $pidList){
		if($this->extensionIsNotLoaded){
			return;
		}
		if($pidList==""){
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_partner_main", " hidden = 0 AND deleted = 0 AND uid=".$uid);
		}else{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_partner_main", " pid IN (".$pidList.") AND hidden = 0 AND deleted = 0 AND uid=".$uid);
		}
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$tx_cal_organizer_partner = t3lib_div::makeInstanceClassName('tx_cal_organizer_partner');
			return new $tx_cal_organizer_partner($this->cObj, $this->rightsObj, $row['uid'], $pidList);
		}
	}
	
	/**
	 * Looks for an organizer with a given uid on a certain pid-list
	 * @param	string		$pidList	The pid-list to search in
	 * @return	array	A tx_cal_organizer_partner object array
	 */
	function findAll($pidList){
		if($this->extensionIsNotLoaded){
			return;
		}
		$organizer = array();
		if($pidList==""){
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_partner_main", " hidden = 0 AND deleted = 0 ");
		}else{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_partner_main", " pid IN (".$pidList.") AND hidden = 0 AND deleted = 0 ");
		}
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$tx_cal_organizer = t3lib_div::makeInstanceClassName('tx_cal_organizer_partner');
			$organizer[] = new $tx_cal_organizer($this->cObj, $this->rightsObj, $row['uid'], $pidList);
		}
		return $organizer;
	}
	
	/**
	 * Search for organizer
	 * @param	object	$cObj	The content object
	 * @param	string	$pidList	The pid-list to search in
	 */
	function search($pidList=''){
		if($this->extensionIsNotLoaded){
			return;
		}
		$sw = $this->controller->piVars['query'];
		$organizerArray = array();
		$organizer = t3lib_div::makeInstanceClassName('tx_cal_organizer_partner');
		if($sw!=""){
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery("*", "tx_partner_main", " pid IN (".$pidList.") AND hidden = 0 AND deleted = 0 ".$this->searchWhere($sw));
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
				$organizerArray[] = new $organizer($this->cObj, $row['uid'], $pidList);
			}
		}
		return $organizerArray;
	}
	
	/**
	 * Generates a search where clause.
	 *
	 * @param	string		$sw: searchword(s)
	 * @return	string		querypart
	 */
	function searchWhere($sw) {
		$where = $this->cObj->searchWhere($sw, $this->cObj->conf["view."]["search."]["searchOrganizerFieldList"], 'tx_partner_main');
		return $where;
	}
	
	function updateOrganizer($uid){
		if($this->extensionIsNotLoaded){
			return;
		}
		$insertFields = array("tstamp" => time());
		//TODO: Check if all values are correct
		
		$this->retrievePostData($insertFields);
		
		// Creating DB records
		$table = "tx_partner_main";
		$where = "uid = ".$uid;			
		$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$insertFields);
	}
	
	function removeOrganizer($uid){
		if($this->extensionIsNotLoaded){
			return;
		}
		if($rightsObj->isAllowedToDeleteLocations()){
			$updateFields = array("tstamp" => time(), "deleted" => 1);
			$table = "tx_partner_main";
			$where = "uid = ".$uid;	
			$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$updateFields);
		}
	}
	
	function retrievePostData(&$insertFields){
		if($this->extensionIsNotLoaded){
			return;
		}
		$hidden = 0;
		if($this->controller->piVars['hidden']=="true" && 
				($this->rightsObj->isAllowedToEditOrganizerHidden() || $this->rightsObj->isAllowedToCreateOrganizerHidden()))
			$hidden = 1;
		$insertFields['hidden'] = $hidden;

		if($this->rightsObj->isAllowedToEditOrganizerName() || $this->rightsObj->isAllowedToCreateOrganizerName()){
			$insertFields['name'] = $this->controller->piVars['name'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerDescription() || $this->rightsObj->isAllowedToCreateOrganizerDescription()){
			$insertFields['title'] = $this->controller->piVars['description'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerStreet() || $this->rightsObj->isAllowedToCreateOrganizerStreet()){
			$insertFields['address'] = $this->controller->piVars['street'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerZip() || $this->rightsObj->isAllowedToCreateOrganizerZip()){
			$insertFields['zip'] = $this->controller->piVars['zip'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerCity() || $this->rightsObj->isAllowedToCreateOrganizerCity()){
			$insertFields['city'] = $this->controller->piVars['city'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerPhone() || $this->rightsObj->isAllowedToCreateOrganizerPhone()){
			$insertFields['phone'] = $this->controller->piVars['phone'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerEmail() || $this->rightsObj->isAllowedToCreateOrganizerEmail()){
			$insertFields['email'] = $this->controller->piVars['email'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerImage() || $this->rightsObj->isAllowedToCreateOrganizerImage()){
			$insertFields['image'] = $this->controller->piVars['image'];
		}
		
		if($this->rightsObj->isAllowedToEditOrganizerLink() || $this->rightsObj->isAllowedToCreateOrganizerLink()){
			$insertFields['www'] = $this->controller->piVars['link'];
		}

	}
	
	function saveOrganizer($pid){
		if($this->extensionIsNotLoaded){
			return;
		}
		$crdate = time();
		$insertFields = array("pid" => $pid, "tstamp" => $crdate, "crdate" => $crdate);
		//TODO: Check if all values are correct
		
		$hidden = 0;
		if($this->controller->piVars['hidden']=="true")
			$hidden = 1;
		$insertFields['hidden'] = $hidden;
		if($this->controller->piVars['name']!=''){
			$insertFields['name'] = $this->controller->piVars['name'];
		}
		if($this->controller->piVars['description']!=''){
			$insertFields['title'] = $this->controller->piVars['description'];
		}
		if($this->controller->piVars['street']!=''){
			$insertFields['address'] = $this->controller->piVars['street'];
		}
		if($this->controller->piVars['zip']!=''){
			$insertFields['zip'] = $this->controller->piVars['zip'];
		}
		if($this->controller->piVars['city']!=''){
			$insertFields['city'] = $this->controller->piVars['city'];
		}
		if($this->controller->piVars['phone']!=''){
			$insertFields['phone'] = $this->controller->piVars['phone'];
		}
		if($this->controller->piVars['email']!=''){
			$insertFields['email'] = $this->controller->piVars['email'];
		}
		if($this->controller->piVars['image']!=''){
			$insertFields['image'] = $this->controller->piVars['image'];
		}
		if($this->controller->piVars['link']!=''){
			$insertFields['www'] = $this->controller->piVars['link'];
		}
		
		// Creating DB records
		$insertFields['cruser_id'] = $this->rightsObj->getUserId();
		$table = "tt_address";
						
		$result = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$insertFields);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/service/class.tx_cal_organizer_partner_service.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/service/class.tx_cal_organizer_partner_service.php']);
}
?>