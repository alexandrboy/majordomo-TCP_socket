<?php
/**
* TCP socket 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 09:04:13 [Apr 30, 2016])
*/
//
//
class TCP_socket extends module {
/**
* TCP_socket
*
* Module class constructor
*
* @access private
*/
function TCP_socket() {
  $this->name="TCP_socket";
  $this->title="TCP socket";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='Sockets' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_Sockets') {
   $this->search_Sockets($out);
  }
  if ($this->view_mode=='edit_Sockets') {
   $this->edit_Sockets($out, $this->id);
  }
  if ($this->view_mode=='delete_Sockets') {
   $this->delete_Sockets($this->id);
   $this->redirect("?data_source=Sockets");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='Channels') {
  if ($this->view_mode=='' || $this->view_mode=='search_Channels') {
   $this->search_Channels($out);
  }
  if ($this->view_mode=='edit_Channels') {
   $this->edit_Channels($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* Sockets search
*
* @access public
*/
 function search_Sockets(&$out) {
  require(DIR_MODULES.$this->name.'/Sockets_search.inc.php');
 }
/**
* Sockets edit/add
*
* @access public
*/
 function edit_Sockets(&$out, $id) {
  require(DIR_MODULES.$this->name.'/Sockets_edit.inc.php');
 }
/**
* Sockets delete record
*
* @access public
*/
 function delete_Sockets($id) {
  $rec=SQLSelectOne("SELECT * FROM Sockets WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM Sockets WHERE ID='".$rec['ID']."'");
 }
/**
* Channels search
*
* @access public
*/
 function search_Channels(&$out) {
  require(DIR_MODULES.$this->name.'/Channels_search.inc.php');
 }
/**
* Channels edit/add
*
* @access public
*/
 function edit_Channels(&$out, $id) {
  require(DIR_MODULES.$this->name.'/Channels_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
   $table='Channels';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
    }
   }
 }
    
 function processCycle() {
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS Sockets');
  SQLExec('DROP TABLE IF EXISTS Channels');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
Sockets - 
Channels - 
*/
  $data = <<<EOD
 Sockets: ID int(10) unsigned NOT NULL auto_increment
 Sockets: TITLE varchar(100) NOT NULL DEFAULT ''
 Sockets: IP varchar(255) NOT NULL DEFAULT ''
 Sockets: PORT varchar(255) NOT NULL DEFAULT ''
 Sockets: STATUS int(3) NOT NULL DEFAULT '0'
 Channels: ID int(10) unsigned NOT NULL auto_increment
 Channels: TITLE varchar(100) NOT NULL DEFAULT ''
 Channels: VALUE varchar(255) NOT NULL DEFAULT ''
 Channels: STATUS int(3) NOT NULL DEFAULT '0'
 Channels: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 Channels: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 Channels: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 Channels: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDMwLCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
