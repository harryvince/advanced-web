<?php

require_once('Response.php');

$response = new Response();

$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage("This is a test message");
$response->setData("Data test");
$response->send('xml');

?>