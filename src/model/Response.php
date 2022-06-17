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
        private $_message;
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
            $this->_message = $message;
        }

        public function setData($data) {
            $this->_data = $data;
        }

        public function toCache($toCache) {
            $this->_toCache = $toCache;
        }

        public function send($responseType) {
            if ($responseType === 'json') {
                header('Content-type: application/json;charset=utf-8');
            } elseif ($responseType === 'xml') {
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
                $this->_responseData['message'] = $this->_message;
            } else {
                $this->_responseData['success'] = true;
                http_response_code($this->_httpStatusCode);
                $this->_responseData['statusCode'] = $this->_httpStatusCode;
                $this->_responseData['message'] = $this->_message;
                $this->_responseData['data'] = $this->_data;
            }

            if ($responseType === 'json') {
                echo json_encode($this->_responseData);
            } elseif ($responseType === 'xml') { 
                echo arrayToXml($this->_responseData);
            }
        }
    }



?>