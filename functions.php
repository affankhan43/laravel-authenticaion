<?php
require_once __DIR__.'/vendor/autoload.php';
use \Curl\Curl;
function(){
	
}
$curl = new Curl();
$curl->setHeader('Content-Type', 'application/json');
$curl->post('http://127.0.0.1:8545',array(
        "jsonrpc"=>"2.0",
        "method"=>"personal_unlockAccount",
        "params"=>array("0xa65aa441ddebc33a11ab52653b7874bd8cf781fc","affan"),
        "id"=>1));
if($curl->error){
        echo $curl->errorMessage;
}
else{
  
?>