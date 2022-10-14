<?php

require_once('db.php');
require_once('../model/Response.php');
require_once('../model/User.php');

# Initial DB Setup
# Change what the users entering to lowercase to allow endpoints
if ( $_SERVER['REQUEST_URI'] != strtolower ( $_SERVER['REQUEST_URI'] ) )
    header ('Location: //' . $_SERVER['HTTP_HOST'] . strtolower ( $_SERVER['REQUEST_URI'] ));

try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
} catch(PDOException $exception) {
    error_log("Data Connecion Error - ".$exception, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection Failed");
    $response->send();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $response = new Response();
            $response->setHttpStatusCode(415);
            $response->setSuccess(false);
            $response->addMessage("Error: Unsupported Content Type Header");
            $response->send();
            exit;
        }
        
        $rawPOSTData = file_get_contents('php://input');

        if(!$jsonData = json_decode($rawPOSTData)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Error: Request Body is not Valid JSON");
            $response->send();
            exit;
        }

        if(!isset($jsonData->username) || !isset($jsonData->password)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            (!isset($jsonData->username) ? $response->addMessage("Error: username is a Mandatory Field") : false);
            (!isset($jsonData->password) ? $response->addMessage("Error: password is a Mandatory Field") : false);
            $response->send();
            exit;
        }

        $newUser = new User((isset($jsonData->username) ? $jsonData->username : null),
                            (isset($jsonData->password) ? $jsonData->password : null));
        $username = $newUser->getUsername();
        $rawPassword = $newUser->getPassword();

        $encryptedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

        # Check user account dosen't already exist
        $query = $readDB->prepare('select username from Users where username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        $userArray = array();

        if ($rowCount === 1) {
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Error: User already exists, please try using a different username");
            $response->send();
            exit;
        }

        $query = $writeDB->prepare('insert into Users (username, password) values (:username, :password)');

        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':password', $encryptedPassword, PDO::PARAM_STR);

        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Insert User into Database");
            $response->send();
            exit;
        }

        $lastUserID = $writeDB->lastInsertID();

        $query = $readDB->prepare('select username, password from Users where userID = :userID');
        $query->bindParam(':userID', $lastUserID, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        $userArray = array();

        if ($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("Error: User ID Not Found");
            $response->send();
            exit;
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['username'], $row['password']);
            $userArray[] = $user->getUsersAsArray();
        }


        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['user'] = $userArray;

        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit;
    } catch (UserException $exception) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage($exception->getMessage());
        $response->send();
        exit;
    } catch (PDOException $exception) {
        error_log("Database Query Error: ".$exception, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Error: Failed to Insert User into Database");
        $response->send();
        exit;
    }
}
else {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Error: Invalid Endpoint");
    $response->send();
    exit;
} 

?>
