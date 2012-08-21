<?php
require_once("common/phpoc/couch.php");
require_once("common/phpoc/couchClient.php");
require_once("common/phpoc/couchDocument.php");
require_once("common/phpoc/couchReplicator.php");

$cdb = new couchClient("http://<user>:<pass>@<host>:<port>/", "<db_name>");
