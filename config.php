<?php
require_once("common/phpoc/couch.php");
require_once("common/phpoc/couchClient.php");
require_once("common/phpoc/couchDocument.php");
require_once("common/phpoc/couchReplicator.php");

$cdb = array();

$cdb[0] = new couchClient("http://<user>:<pass>@<host>:<port>/", "<db_name>"); 

//NOTE: add more than one database if you need to
//$cdb[1] = new couchClient("http://<user>:<pass>@<host>:<port>/", "<another_db_name>"); 
