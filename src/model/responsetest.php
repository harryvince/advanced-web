<?php

require_once('Response.php');

$response = new Response();

$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage("This is a test message");
$response->addMessage("This is a test message 2");
$response->setData("Data test");
$response->send();

?>