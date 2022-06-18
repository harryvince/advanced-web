<?php

require_once('db.php');
require_once('../model/Task.php');
require_once('../model/Response.php');

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

if(array_key_exists("taskid", $_GET)) {
    $taskid = $_GET['taskid'];

    if($taskid == '' || !is_numeric($taskid)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Task ID: Cannot be null and must be numeric");
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
            $response->send();
            exit;
        } catch(TaskException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit;
        }
        catch (PDOException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Retrieve Task");
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
                $response->send();
                exit; 
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("Task Deleted Successfully!");
            $response->send();
            exit; 

        } catch (PDOException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to Delete Task");
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        // TODO: Implement in Phase 10
    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Invalid Request Method");
        $response->send();
        exit;
    }
    
} elseif(array_key_exists("complete", $_GET)) {
    $complete = $_GET['complete'];

    if ($complete !== 'Y' && $complete !== 'N') {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Error: Complete must be Y or N");
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
            $response->send();
            exit;
        } catch (TaskException $exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($exception->getMessage());
            $response->send();
            exit;
        } catch (PDOException $Exception) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error: Failed to Get Tasks");
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

            if(!isset($jsonData->title) || !isset($jsonData->complete)) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->title) ? $response->addMessage("Error: Title is a Mandatory Field") : false);
                (!isset($jsonData->complete) ? $response->addMessage("Error: Complete Status is a Mandatory Field") : false);
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
            $response->send();
            exit;
        } catch (TaskException $exception) {
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
            $response->addMessage("Error: Failed to Insert Task into Database");
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
}
    
?>