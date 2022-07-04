<?php

    class BookException extends Exception { }

    class Book {
        private $_id;
        private $_title;
        private $_description;
        private $_date;
        private $_start_time;
        private $_end_time;
        private $_deadline;
        private $_complete;
        private $_userID;

        public function __construct($id, $title, $description, $date, $start_time, $end_time, $deadline, $complete, $userID) {

            $this->setId($id);
            $this->setTitle($title);
            $this->setDescription($description);
            $this->setDate($date);
            $this->setStartTime($start_time);
            $this->setEndTime($end_time);
            $this->setDeadline($deadline);
            $this->setComplete($complete);
            $this->setUserId($userID);

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

        public function getUserId() {
            return $this->_userID;
        }

        public function isValidDate($date, $format = 'Y-m-d') {
            if($date === null || $date === "") {
                return true;
              }
            $dateObj = DateTime::createFromFormat($format, $date);
            return $dateObj && $dateObj->format($format) == $date;
        }

        public function setId($id) {
            if (($id !== null) && (!is_numeric($id) || $this->_id !== null)) {
                throw new BookException("Error: Task ID Issue");
            }
            $this->_id = $id;
        }

        public function setTitle($title) {
            if (strlen($title) <= 0 || strlen($title) >= 255) {
                throw new BookException("Error: Title Issue");
            }
            $this->_title = $title;
        }

        public function setDescription($description) {
            $this->_description = $description;
        }

        public function setDate($date) {
            if (!$this->isValidDate($date, 'd-m-Y')) {
                throw new BookException("Error: Task Date Issue");
            }
            $this->_date = $date;
        }

        public function setStartTime($start_time) {
            if (!$this->isValidDate($start_time, 'H:i:s')) {
                throw new BookException("Error: Start Time Issue");
            }
            $this->_start_time = $start_time;
        }

        public function setEndTime($end_time) {
            if(!$this->isValidDate($end_time, 'H:i:s')) {
                throw new BookException("Error: End Time Issue");
            }
            $this->_end_time = $end_time;
        }

        public function setDeadline($deadline) {
            if (($deadline !== null) && date_format(date_create_from_format('d-m-Y H:i', $deadline), 'd-m-Y H:i') !== $deadline) {
                throw new BookException("Error: Deadline Issue");
            }
            $this->_deadline = $deadline;
        }

        public function setComplete($complete) {
            if (strtoupper($complete) !== 'Y' && strtoupper($complete) !== 'N') {
                throw new BookException("Error: Status Issue");
            }
            $this->_complete = $complete;
        }

        public function setUserId($id) {
            // if (($id !== null) && (!is_numeric($id) || $this->_id !== null)) {
            //     throw new BookException("Error: User ID Issue");
            // }
            $this->_userID = $id;
        }

        public function getBooksAsArray() {
            $book = array();
            $book['id'] = $this->getId();
            $book['title'] = $this->getTitle();
            $book['description'] = $this->getDescription();
            $book['date'] = $this->getDate();
            $book['start_time'] = $this->getStartTime();
            $book['end_time'] = $this->getEndTime();
            $book['deadline'] = $this->getDeadline();
            $book['complete'] = $this->getComplete();
            // Don't need to add user ID as this is an invisible field that will only be used on the backend
            // To identify which books belong to who
            return $book;
        }
    }


?>