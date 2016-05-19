<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['Settings_qry'];
  } else {
   $session->data['Settings_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_Settings="ID DESC";
  $out['SORTBY']=$sortby_Settings;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM Settings WHERE $qry ORDER BY ".$sortby_Settings);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
