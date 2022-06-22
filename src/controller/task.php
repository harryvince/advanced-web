<?php

require_once('db.php');
require_once('../model/Task.php');
require_once('../model/Response.php');
require_once('../model/User.php');

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
  
}


if(array_key_exists("taskid", $_GET)) {
    $taskid = $_GET['taskid'];

    if($taskid == '' || !is_numeric($taskid)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Task ID: Cannot be null and must be numeric");
        $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
        $response->send();
    exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete from tbl_tasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Task ID Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $task = new Task($row['id'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete']);
                $taskArray[] = $task->getTasksAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch(TaskException $exception) {
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
            $response->addMessage("Failed to Retrieve Task");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        try {
            $query = $writeDB->prepare('delete from tbl_tasks where id=:taskid');
            $query->bindparam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Task not found!");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit; 
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("Task Deleted Successfully!");
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit; 

        } catch (PDOException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete Task");
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
      
            $taskid = $_GET['taskid'];
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete from tbl_tasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();
      
            $rowCount = $query->rowCount();
      
            if($rowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Task ID Not Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }
      
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
              $task = new Task($row['id'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete']);
            }
      
            $updateQueryString = "update tbl_tasks set ".$queryFields." where id = :taskid";
            $updateQuery = $writeDB->prepare($updateQueryString);
      
            if($titleUpdated === true){
              $task->setTitle($jsonData->title);
              $updatedTitle = $task->getTitle();
              $updateQuery->bindParam(':title', $updatedTitle, PDO::PARAM_STR);
            }
            if($descriptionUpdated === true){
              $task->setDescription($jsonData->description);
              $updatedDescription = $task->getDescription();
              $updateQuery->bindParam(':description', $updatedDescription, PDO::PARAM_STR);
            }
            if($dateUpdated === true){
              $task->setDate($jsonData->date);
              $updatedDate = $task->getDate();
              $updateQuery->bindParam(':date', $updatedDate, PDO::PARAM_STR);
            }
            if($start_timeUpdated === true){
              $task->setStartTime($jsonData->start_time);
              $updatedStartTime = $task->getStartTime();
              $updateQuery->bindParam(':start_time', $start_time, PDO::PARAM_STR);
            }
            if($end_timeUpdated === true){
              $task->setEndTime($jsonData->end_time);
              $updatedEndTime = $task->getEndTime();
              $updateQuery->bindParam(':end_time', $updatedEndTime, PDO::PARAM_STR);
            }
            if($deadlineUpdated === true){
              $task->setDeadline($jsonData->deadline);
              $updatedDeadline = $task->getDeadline();
              $updateQuery->bindParam(':deadline', $updatedDeadline, PDO::PARAM_STR);
            }
            if($completeUpdated === true){
              $task->setComplete($jsonData->complete);
              $updatedComplete = $task->getComplete();
              $updateQuery->bindParam(':complete', $updatedComplete, PDO::PARAM_STR);
            }
      
            $updateQuery->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $updateQuery->execute();
      
            $rowCount = $updateQuery->rowCount();
            $taskArray = array();
      
            // if($rowCount === 0) {
            //   $response = new Response();
            //   $response->setHttpStatusCode(404);
            //   $response->setSuccess(false);
            //   $response->addMessage("Task Not Updated");
            //   $response->send();
            //   exit;
            // }
      
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete from tbl_tasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();
      
            $rowCount = $query->rowCount();
            $taskArray = array();
      
            if($rowCount === 0) {
              $response = new Response();
              $response->setHttpStatusCode(404);
              $response->setSuccess(false);
              $response->addMessage("Task ID Not Found");
              $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
              $response->send();
              exit;
            }
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
              $task = new Task($row['id'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete']);
              $taskArray[] = $task->getTasksAsArray();
            }
      
            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;
      
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
          }
      
          catch(TaskException $exception) {
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
              $response->addMessage("Failed to Update Task");
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
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete from tbl_tasks where complete = :complete');
            $query->bindparam(':complete', $complete, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Tasks Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $task = new Task($row['id'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete']);
                $taskArray[] = $task->getTasksAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (TaskException $exception) {
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
            $response->addMessage("Error: Failed to Get Tasks");
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

            $newTask = new Task(null,
                                (isset($jsonData->title) ? $jsonData->title : null),
                                (isset($jsonData->description) ? $jsonData->description : null),
                                (isset($jsonData->date) ? $jsonData->date : null),
                                (isset($jsonData->start_time) ? $jsonData->start_time : null),
                                (isset($jsonData->end_time) ? $jsonData->end_time : null),
                                (isset($jsonData->deadline) ? $jsonData->deadline : null),
                                (isset($jsonData->complete) ? $jsonData->complete : null));
            $title = $newTask->getTitle();
            $description = $newTask->getDescription();
            $date = $newTask->getDate();
            $start_time = $newTask->getStartTime();
            $end_time = $newTask->getEndTime();
            $deadline = $newTask->getDeadline();
            $complete = $newTask->getComplete();

            $query = $writeDB->prepare('insert into tbl_tasks (title, description, date, start_time, end_time, deadline, complete) values (:title, :description, STR_TO_DATE(:date, \'%d-%m-%Y\'), :start_time, :end_time, STR_TO_DATE(:deadline, \'%d-%m-%Y %H:%i\'), :complete)');

            $query->bindParam(':title', $title, PDO::PARAM_STR);
            $query->bindParam(':description', $description, PDO::PARAM_STR);
            $query->bindParam(':date', $date, PDO::PARAM_STR);
            $query->bindParam(':start_time', $start_time, PDO::PARAM_STR);
            $query->bindParam(':end_time', $end_time, PDO::PARAM_STR);
            $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
            $query->bindParam(':complete', $complete, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Failed to Insert Task into Database");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            $lastTaskID = $writeDB->lastInsertID();

            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(date, "%d-%m-%Y") as "date", start_time, end_time, DATE_FORMAT(deadline, "%d-%m-%Y %H:%i") as "deadline", complete from tbl_tasks where id = :taskid');
            $query->bindParam(':taskid', $lastTaskID, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();

            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Error: Task ID Not Found");
                $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
                $response->send();
                exit;
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $task = new Task($row['id'], $row['title'], $row['description'], $row['date'], $row['start_time'], $row['end_time'], $row['deadline'], $row['complete']);
                $taskArray[] = $task->getTasksAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->setAuthenticatedUser($_SERVER['PHP_AUTH_USER']);
            $response->send();
            exit;
        } catch (TaskException $exception) {
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
            $response->addMessage("Error: Failed to Insert Task into Database");
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