<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'u538922476_mainamatrol');
define('DB_USER',    'u538922476_admin');
define('DB_PASS',    '$Lovewins1');
define('DB_CHARSET', 'utf8mb4');
function getDB(){
  static $pdo=null;
  if($pdo===null){
    $dsn="mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $pdo=new PDO($dsn,DB_USER,DB_PASS,[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false
    ]);
  }
  return $pdo;
}
