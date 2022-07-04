<?php

require_once('db.php');
require_once('../model/Book.php');
require_once('../model/Response.php');
require_once('../model/User.php');

session_start();

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

# User authentication
if (!isset($_SERVER['PHP_AUTH_USER'])) {
  header('WWW-Authenticate: Basic realm="My Realm"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'You have not provided credentials to access the API, please authenticate';
  exit;
} else {
  $user = new User($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
  try {
    $username = $user->getUsername();
    $query = $readDB->prepare('select * from Users where username = :username');
    $query->bindParam(':username', $username, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();

    if ($rowCount === 0) {
      $response = new Response();
      $response->setHttpStatusCode(404);
      $response->setSuccess(false);
      $response->addMessage("Authorization Failure: Username Not Found");
      $response->send();
      exit;
    }

  while($row = $query->fetch(PDO::FETCH_ASSOC)) {
      $hashedPassword = $row['password'];
      $checkPassword = password_verify($user->getPassword(), $hashedPassword);

      if (!(password_verify($user->getPassword(), $hashedPassword))) {
        $response = new Response();
        $response->setHttpStatusCode(403);
        $response->setSuccess(false);
        $response->addMessage("Authorization Failure: Incorrect password");
        $response->send();
        exit;
      }

      $_SESSION['userID'] = $row['userID'];
  }

  } catch (PDOException $exception) {
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Failed to Login");
    $response->send();
    exit;
}
}


if(array_key_exists("bookid", $_GET)) {
    $bookid = $_GET['bookid'];

    if($bookid == '' || !is_numeric($bookid)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Book ID: Cannot be null and must be numeric");
        $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
        $response->send();
    exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $query = $readDB->prepare('select bookID, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete, userID from Books where bookID = :bookid');
            $query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $bookArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Book ID Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }
            $realRowCount = 0;
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $book = new Book($row['bookID'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete'], $row['userID']);
                if ($row['userID'] === $_SESSION['userID']) {
                  $bookArray[] = $book->getBooksAsArray();
                  $realRowCount = $realRowCount + 1;
                }
            }

            if($realRowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Error: No Books Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }

            $returnData = array();
            $returnData['rows_returned'] = $realRowCount;
            $returnData['books'] = $bookArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch(BookException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        }
        catch (PDOException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Retrieve Book");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        try {
            $query = $writeDB->prepare('delete from Books where bookID=:bookid');
            $query->bindparam(':bookid', $bookid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $bookArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Book not found!");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit; 
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("Book Deleted Successfully!");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit; 

        } catch (PDOException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete Book");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        
        try {
            if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
              $response = new Response();
              $response->setHttpStatusCode(400);
              $response->setSuccess(false);
              $response->addMessage("Error: Invalid Content Type Header");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit();
            }
      
            $rawPATCHData = file_get_contents('php://input');
      
            if(!$jsonData = json_decode($rawPATCHData)) {
              $response = new Response();
              $response->setHttpStatusCode(400);
              $response->setSuccess(false);
              $response->addMessage("Error: Request Body is not Valid JSON");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit();
            }
      
            $titleUpdated = false;
            $descriptionUpdated = false;
            $dateUpdated = false;
            $start_timeUpdated = false;
            $end_timeUpdated = false;
            $deadlineUpdated = false;
            $completeUpdated = false;
      
            $queryFields = "";
      
            if(isset($jsonData->title)){
              $titleUpdated = true;
              $queryFields .= "title = :title, ";
            }
            if(isset($jsonData->description)){
              $descriptionUpdated = true;
              $queryFields .= "description = :description, ";
            }
            if(isset($jsonData->date)){
              $dateUpdated = true;
              $queryFields .= "date = STR_TO_DATE(:date, '%d-%m-%Y'), ";
            }
            if(isset($jsonData->start_time)){
              $start_timeUpdated = true;
              $queryFields .= "start_time = :start_time, ";
            }
            if(isset($jsonData->end_time)){
              $end_timeUpdated = true;
              $queryFields .= "end_time = :end_time, ";
            }
            if(isset($jsonData->deadline)){
              $deadlineUpdated = true;
              $queryFields .= "deadline = STR_TO_DATE(:deadline, \'%d-%m-%Y\'), ";
            }
            if(isset($jsonData->complete)){
              $completeUpdated = true;
              $queryFields .= "complete = :complete, ";
            }
      
            $queryFields = rtrim($queryFields, ", ");
      
            if($queryFields === "") {
              $response = new Response();
              $response->setHttpStatusCode(400);
              $response->setSuccess(false);
              $response->addMessage("No Data Provided");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit();
            }
      
            $bookid = $_GET['bookid'];
            $query = $readDB->prepare('select bookID, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete, userID from Books where bookID = :bookid');
            $query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
            $query->execute();
      
            $rowCount = $query->rowCount();
      
            if($rowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Book ID Not Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }
      
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
              $book = new Book($row['bookID'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete'], $row['userID']);
            }
      
            $updateQueryString = "update Books set ".$queryFields." where bookID = :bookid";
            $updateQuery = $writeDB->prepare($updateQueryString);
      
            if($titleUpdated === true){
              $book->setTitle($jsonData->title);
              $updatedTitle = $book->getTitle();
              $updateQuery->bindParam(':title', $updatedTitle, PDO::PARAM_STR);
            }
            if($descriptionUpdated === true){
              $book->setDescription($jsonData->description);
              $updatedDescription = $book->getDescription();
              $updateQuery->bindParam(':description', $updatedDescription, PDO::PARAM_STR);
            }
            if($dateUpdated === true){
              $book->setDate($jsonData->date);
              $updatedDate = $book->getDate();
              $updateQuery->bindParam(':date', $updatedDate, PDO::PARAM_STR);
            }
            if($start_timeUpdated === true){
              $book->setStartTime($jsonData->start_time);
              $updatedStartTime = $book->getStartTime();
              $updateQuery->bindParam(':start_time', $start_time, PDO::PARAM_STR);
            }
            if($end_timeUpdated === true){
              $book->setEndTime($jsonData->end_time);
              $updatedEndTime = $book->getEndTime();
              $updateQuery->bindParam(':end_time', $updatedEndTime, PDO::PARAM_STR);
            }
            if($deadlineUpdated === true){
              $book->setDeadline($jsonData->deadline);
              $updatedDeadline = $book->getDeadline();
              $updateQuery->bindParam(':deadline', $updatedDeadline, PDO::PARAM_STR);
            }
            if($completeUpdated === true){
              $book->setComplete($jsonData->complete);
              $updatedComplete = $book->getComplete();
              $updateQuery->bindParam(':complete', $updatedComplete, PDO::PARAM_STR);
            }
      
            $updateQuery->bindParam(':bookid', $bookid, PDO::PARAM_INT);
            $updateQuery->execute();
      
            $rowCount = $updateQuery->rowCount();
            $bookArray = array();
      
            $query = $readDB->prepare('select bookID, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete, userID from Books where bookID = :bookid');
            $query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
            $query->execute();
      
            $rowCount = $query->rowCount();
            $bookArray = array();
      
            if($rowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Book ID Not Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }
            $realRowCount = 0;
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
              $book = new Book($row['bookID'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete'], $row['userID']);
              if ($row['userID'] === $_SESSION['userID']) {
                $bookArray[] = $book->getBooksAsArray();
                $realRowCount = $realRowCount + 1;
              }
            }

            if($realRowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Error: No Book Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }
      
            $returnData = array();
            $returnData['rows_returned'] = $realRowCount;
            $returnData['books'] = $bookArray;
      
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
          }
      
          catch(BookException $exception) {
              $response = new Response();
              $response->setHttpStatusCode(400);
              $response->setSuccess(false);
              $response->addMessage($exception->getMessage());
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit();
          }
          catch(PDOException $exception) {
              $response = new Response();
              $response->setHttpStatusCode(500);
              $response->setSuccess(false);
              $response->addMessage("Failed to Update Book");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit();
          }
    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Invalid Request Method");
        $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
        $response->send();
        exit;
    }
    
} elseif(array_key_exists("complete", $_GET)) {
    $complete = strtoupper($_GET['complete']);

    if ($complete !== 'Y' && $complete !== 'N') {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Error: Complete must be Y or N");
        $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
        $response->send();
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $query = $readDB->prepare('select bookID, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete, userID from Books where complete = :complete');
            $query->bindparam(':complete', $complete, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $bookArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Books Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            $realRowCount = 0;
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $book = new Book($row['bookID'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete'], $row['userID']);
                if ($row['userID'] === $_SESSION['userID']) {
                  $bookArray[] = $book->getBooksAsArray();
                  $realRowCount = $realRowCount + 1;
                }
            }

            if($realRowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Error: No Books Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }

            $returnData = array();
            $returnData['rows_returned'] = $realRowCount;
            $returnData['books'] = $bookArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (BookException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (PDOException $Exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Get Books");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } 
    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Request Method");
        $response->send();
        exit;
    } 

} elseif (empty($_GET)) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
                $response = new Response();
                $response->setHttpStatusCode(415);
                $response->setSuccess(false);
                $response->addMessage("Error: Unsupported Content Type Header");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }
            
            $rawPOSTData = file_get_contents('php://input');

            if(!$jsonData = json_decode($rawPOSTData)) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Error: Request Body is not Valid JSON");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            if(!isset($jsonData->title) || !isset($jsonData->complete)) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->title) ? $response->addMessage("Error: Title is a Mandatory Field") : false);
                (!isset($jsonData->complete) ? $response->addMessage("Error: Complete Status is a Mandatory Field") : false);
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            $newBook = new Book(null,
                                (isset($jsonData->title) ? $jsonData->title : null),
                                (isset($jsonData->description) ? $jsonData->description : null),
                                (isset($jsonData->date) ? $jsonData->date : null),
                                (isset($jsonData->start_time) ? $jsonData->start_time : null),
                                (isset($jsonData->end_time) ? $jsonData->end_time : null),
                                (isset($jsonData->deadline) ? $jsonData->deadline : null),
                                (isset($jsonData->complete) ? $jsonData->complete : null),
                                ($_SESSION['userID']));
            $title = $newBook->getTitle();
            $description = $newBook->getDescription();
            $date = $newBook->getDate();
            $start_time = $newBook->getStartTime();
            $end_time = $newBook->getEndTime();
            $deadline = $newBook->getDeadline();
            $complete = $newBook->getComplete();
            $userID = $newBook->getUserId();

            $query = $writeDB->prepare('insert into Books (title, description, date, start_time, end_time, deadline, complete, userID) values (:title, :description, STR_TO_DATE(:date, \'%d-%m-%Y\'), :start_time, :end_time, STR_TO_DATE(:deadline, \'%d-%m-%Y %H:%i\'), :complete, :userid)');

            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':description', $description, PDO::PARAM_STR);
            $query->bindParam(':date', $date, PDO::PARAM_STR);
            $query->bindParam(':start_time', $start_time, PDO::PARAM_STR);
            $query->bindParam(':end_time', $end_time, PDO::PARAM_STR);
            $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
            $query->bindParam(':complete', $complete, PDO::PARAM_STR);
            $query->bindParam(':userid', $userID, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Failed to Insert Book into Database");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            $lastBookID = $writeDB->lastInsertID();

            $query = $readDB->prepare('select bookID, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete, userID from Books where bookID = :bookid');
            $query->bindParam(':bookid', $lastBookID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $bookArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Book ID Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $book = new Book($row['bookID'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete'], $row['userID']);
                $bookArray[] = $book->getBooksAsArray();
            }


            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['books'] = $bookArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (BookException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (PDOException $exception) {
            error_log("Database Query Error: ".$exception, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Insert Book into Database");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        }
    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(404);
        $response->setSuccess(false);
        $response->addMessage("Error: Invalid Endpoint");
        $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
        $response->send();
        exit;
    } 
}
    
?>