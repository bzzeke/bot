<?php

namespace Bot\Synology\SurveillanceStation;
use Synology\Api\Authenticate;

class Api extends Authenticate
{

    const API_SERVICE_NAME = 'SurveillanceStation';
    const API_NAMESPACE = 'SYNO';

    protected $user;
    protected $password;
    protected $isConnected = false;

    protected function _request($api, $path, $method, $params = [], $version = null, $httpMethod = 'get')
    {
        if (!$this->isConnected()) {
            $this->connect($this->user, $this->password);
        }

        return parent::_request($api, $path, $method, $params, $version, $httpMethod);
    }

    protected function isConnected()
    {
        return $this->isConnected;
    }

    protected function setConnected()
    {
        $this->isConnected = true;
    }

    /**
     * Info API setup
     *
     * @param string $address
     * @param int $port
     * @param string $protocol
     * @param int $version
     * @param boolean $verifySSL
     */
    public function __construct($address, $port = null, $protocol = null, $version = 1, $verifySSL = false)
    {
        parent::__construct(self::API_SERVICE_NAME, self::API_NAMESPACE, $address, $port, $protocol, $version, $verifySSL);
    }

    public function getSnapshot($cameraId)
    {
        return $this->_request('Camera', 'entry.cgi', 'GetSnapshot', array('cameraId' => $cameraId));
    }

    public function setPosition($cameraId, $presetId)
    {
        return $this->_request('PTZ', 'entry.cgi', 'GoPreset', array('cameraId' => $cameraId, 'presetId' => $presetId));
    }

    public function getRecordings($cameraId, $limit)
    {
        return $this->_request('Recording', 'entry.cgi', 'List', array('cameraIds' => $cameraId, 'limit' => $limit));
    }

    public function getRecording($id)
    {
        return $this->_request('Recording', 'entry.cgi', 'Download', array('id' => $id));
    }

    public function setAuth($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }
}