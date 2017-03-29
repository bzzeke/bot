<?php

class Synology_SurveillanceStation_Api extends Synology_Api_Authenticate
{

    const API_SERVICE_NAME = 'SurveillanceStation';

    const API_NAMESPACE = 'SYNO';

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

    public function getSnapshot()
    {
        return $this->_request('Camera', 'entry.cgi', 'GetSnapshot', array('cameraId' => 1));
    }
}