<?php

    class TaskException extends Exception { }

    class Task {
        private $_id;
        private $_title;
        private $_description;
        private $_date;
        private $_start_time;
        private $_end_time;
        private $_deadline;
        private $_complete;

        public function __construct($id, $title, $description, $date, $start_time, $end_time, $deadline, $complete) {

            $this->setId($id);
            $this->setTitle($title);
            $this->setDescription($description);
            $this->setDate($date);
            $this->setStartTime($start_time);
            $this->setEndTime($end_time);
            $this->setDeadline($deadline);
            $this->setComplete($complete);

        }

        public function getId() {
            return $this->_id;
        }

        public function getTitle() {
            return $this->_title;
        }

        public function getDescription() {
            return $this->_description;
        }

        public function getDate() {
            return $this->_date;
        }

        public function getStartTime () {
            return $this->_start_time;
        }

        public function getEndTime() {
            return $this->_end_time;
        }

        public function getDeadline() {
            return $this->_deadline;
        }

        public function getComplete() {
            return $this->_complete;
        }

        public function isValidDate($date, $format = 'Y-m-d') {
            $dateObj = DateTime::createFromFormat($format, $date);
            return $dateObject && dateObj->format($format) == $date;
        }

        public function setId($id) {
            if (($id !== null)) && (!is_numeric($id) || $this->_id !== null) {
                throw new TaskException("Error: Task ID Issue");
            }
            $this->_id = $id;
        }

        public function setTitle($title) {
            if (strlen($title) <= 0 || strlen($title) >= 255) {
                throw new TaskException("Error: Title Issue");
            }
            $this->_title = $title;
        }

        public function setDescription($description) {
            $this->_description = $description;
        }

        public function setDate($date) {
            if (!$this->isValidDate($date, 'd-m-Y')) {
                throw new TaskException("Error: Task Date Issue");
            }
            $this->_date = $date;
        }

        public function setStartTime($start_time) {
            if (!$this->isValidDate($start_time, 'H:i:s')) {
                throw new TaskException("Error: Start Time Issue");
            }
            $this->_start_time = $start_time;
        }

        public function setEndTime($end_time) {
            if(!$this->isValidDate($end_time, 'H:i:s')) {
                throw new TaskException("Error: End Time Issue")
            }
            $this->_end_time = $end_time;
        }

        public function setDeadline($deadline) {
            if (($deadline !== null) && date_format(date_create_from_format('d-m-Y H:i:', $deadline), 'd-m-Y H:i') !== $deadline) {
                throw new TaskException("Error: Deadline Issue");
            }
            $this->_deadline = $deadline;
        }

        public function setComplete($complete) {
            if (strtoupper($complete) !== 'Y' && strtoupper($complete) !== 'N') {
                throw new TaskException("Error: Status Issue");
            }
        }

        public function getTasksAsArray() {
            $task = array();
            $task['id'] = $this->getId();
            $task['title'] = $this->getTitle();
            $task['description'] = $this->getDescription();
            $task['date'] = $this->getDate();
            $task['start_time'] = $this->getStartTime();
            $task['end_time'] = $this->getEndTime();
            $task['deadline'] = $this->getDeadline();
            $task['complete'] = $this->getComplete();
            return $task;
        }
    }


?>