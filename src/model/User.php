<?php

class UserException extends Exception { }

class User {

    private $_username;
    private $_password;

    public function __construct($username, $password) {

        $this->setUsername($username);
        $this->setPassword($password);

    }

    public function getUsername() {
        return $this->_username;
    }

    public function getPassword() {
        return $this->_password;
    }

    public function setUsername($username) {
        $this->_username = $username;
    }

    public function setPassword($password) {
        $this->_password = $password;
    }

    public function getUsersAsArray() {
        $user = array();
        $user['username'] = $this->getUsername();
        $user['password'] = $this->getPassword();
        return $user;
    }

}


?>