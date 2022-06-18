<?php

    function arrayToXml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;

        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }

        // Visit all key value pair
        foreach ($array as $k => $v) {

            // If there is nested array then
            if (is_array($v)) { 

                // Call function for nested array
                arrayToXml($v, $k, $_xml->addChild($k));
                }

            else {

                // Simply add child element. 
                $_xml->addChild($k, $v);
            }
        }

        return $_xml->asXML();
    }

    class Response {
        private $_success;
        private $_httpStatusCode;
        private $_messages = array();
        private $_data;
        private $_toCache = false;
        private $_responseData = array();

        public function setSuccess($success) {
            $this->_success = $success;
        }

        public function setHttpStatusCode($httpStatusCode) {
            $this->_httpStatusCode = $httpStatusCode;
        }

        public function addMessage($message) {
            $this->_messages[] = $message;
        }

        public function setData($data) {
            $this->_data = $data;
        }

        public function toCache($toCache) {
            $this->_toCache = $toCache;
        }

        public function send() {
            if (isset($_SERVER['HTTP_RESPONSE_TYPE']) && $_SERVER['HTTP_RESPONSE_TYPE'] === 'application/json') {
                header('Content-type: application/json;charset=utf-8');
            } elseif (isset($_SERVER['HTTP_RESPONSE_TYPE']) && $_SERVER['HTTP_RESPONSE_TYPE'] === 'text/xml') {
                header('Content-type: text/xml;charset=utf-8');
            }

            if ($this->_toCache === true) {
                header('Cache-control: max-age=60');
            } else {
                header('Cache-control: no-cache, no-store');
            }

            if (($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode)) {
                $this->_responseData['success'] = false;
                http_response_code(500);
                $this->_responseData['statusCode'] = 500;
                $this->addMessage("500 Internal Server Error");
                $this->_responseData['messages'] = $this->_messages;
            } else {
                $this->_responseData['success'] = true;
                http_response_code($this->_httpStatusCode);
                $this->_responseData['statusCode'] = $this->_httpStatusCode;
                $this->_responseData['messages'] = $this->_messages;
                $this->_responseData['data'] = $this->_data;
            }

            if (isset($_SERVER['HTTP_RESPONSE_TYPE'])) {
                if ($_SERVER['HTTP_RESPONSE_TYPE'] === 'application/json') {
                    echo json_encode($this->_responseData);
                } elseif ($_SERVER['HTTP_RESPONSE_TYPE'] === 'text/xml') { 
                    echo arrayToXml($this->_responseData);
                } elseif (strlen($_SERVER['HTTP_RESPONSE_TYPE']) >= 1){
                    $this->_responseData['success'] = false;
                    $this->_responseData['statusCode'] = 415;
                    http_response_code(415);
                    $this->_responseData['data'] = "Client Error.";
                    $this->_responseData['messages'] = "More than one Response-Type header has been provided, please only provide 1";
                    echo json_encode($this->_responseData);
                }
            } else {
                $this->_responseData['success'] = false;
                $this->_responseData['statusCode'] = 415;
                http_response_code(415);
                $this->_responseData['data'] = "Client Error.";
                $this->_responseData['messages'] = "No Response-Type header has been provided, this API currently supports JSON & XML";
                echo json_encode($this->_responseData);
            }
        }
    }



?>