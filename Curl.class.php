<?php
class Curl {
    const USER_AGENT = 'PHP-Curl-Class/1.0 (+https://github.com/php-curl-class/php-curl-class)';

    function __construct() {
        if (!extension_loaded('curl')) {
            throw new ErrorException('cURL library is not loaded');
        }

        $this->curl = curl_init();
        $this->setUserAgent(self::USER_AGENT);
        $this->setopt(CURLINFO_HEADER_OUT, TRUE);
        $this->setopt(CURLOPT_HEADER, TRUE);
        $this->setopt(CURLOPT_RETURNTRANSFER, TRUE);
    }

    function get($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url . '?' . http_build_query($data));
        $this->setopt(CURLOPT_HTTPGET, TRUE);
        $this->_exec();
    }

    function post($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url);
        $this->setopt(CURLOPT_POST, TRUE);
        $this->setopt(CURLOPT_POSTFIELDS, $data);
        $this->_exec();
    }

    function put($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url . '?' . http_build_query($data));
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->_exec();
    }

    function patch($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url);
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->setopt(CURLOPT_POSTFIELDS, $data);
        $this->_exec();
    }

    function delete($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url . '?' . http_build_query($data));
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->_exec();
    }

    function setBasicAuthentication($username, $password) {
        $this->setopt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setopt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    function setHeader($key, $value) {
        $this->_headers[$key] = $key . ': ' . $value;
        $this->setopt(CURLOPT_HTTPHEADER, array_values($this->_headers));
    }

    function setUserAgent($user_agent) {
        $this->setopt(CURLOPT_USERAGENT, $user_agent);
    }

    function setReferrer($referrer) {
        $this->setopt(CURLOPT_REFERER, $referrer);
    }

    function setCookie($key, $value) {
        $this->_cookies[$key] = $value;
        $this->setopt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; '));
    }

    function setOpt($option, $value) {
        return curl_setopt($this->curl, $option, $value);
    }

    function verbose($on=TRUE) {
        $this->setopt(CURLOPT_VERBOSE, $on);
    }

    function close() {
        curl_close($this->curl);
    }

    function _exec() {
        $this->response = curl_exec($this->curl);
        $this->curl_error_code = curl_errno($this->curl);
        $this->curl_error_message = curl_error($this->curl);
        $this->curl_error = !($this->curl_error_code === 0);
        $this->http_error_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->http_error = in_array(floor($this->http_error_code / 100), array(4, 5));
        $this->error = $this->curl_error || $this->http_error;

        $this->request_headers = preg_split('/\r\n/', curl_getinfo($this->curl, CURLINFO_HEADER_OUT), NULL, PREG_SPLIT_NO_EMPTY);
        $this->response_headers = '';
        if (!(strpos($this->response, "\r\n\r\n") === FALSE)) {
            $parts = explode("\r\n\r\n", $this->response, 3);
            if (count($parts) === 3 && $parts['0'] === 'HTTP/1.1 100 Continue') {
                array_shift($parts);
            }
            list($this->response_headers, $this->response) = $parts;
            $this->response_headers = preg_split('/\r\n/', $this->response_headers, NULL, PREG_SPLIT_NO_EMPTY);
        }

        return $this->error_code;
    }

    function __destruct() {
        $this->close();
    }

    private $_cookies = array();
    private $_headers = array();

    public $curl;
    public $error = NULL;
    public $error_code = 0;
    public $error_message = NULL;
    public $request_headers = NULL;
    public $response_headers = NULL;
    public $response = NULL;
}
