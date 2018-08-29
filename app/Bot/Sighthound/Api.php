<?php

namespace Bot\Sighthound;

class Api
{
    protected $auth = [];
    protected $rpc_url = '';
    protected $host = '';

    public function __construct($host, $auth)
    {
        $this->setHost($host);
        $this->setAuth($auth);
    }

    public function getClips($camera, $date, $limit = 500)
    {
        $template = <<<EOT
<?xml version="1.0"?>
<methodCall>
    <methodName>remoteGetClipsForRule</methodName>
    <params>
        <param>
            <value>
                <string>$camera</string>
            </value>
        </param>
        <param>
            <value>
                <string>All objects</string>
            </value>
        </param>
        <param>
            <value>
                <int>$date</int>
            </value>
        </param>
        <param>
            <value>
                <int>$limit</int>
            </value>
        </param>
        <param>
            <value>
                <int>0</int>
            </value>
        </param>
        <param>
            <value>
                <boolean>0</boolean>
            </value>
        </param>
    </params>
</methodCall>
EOT;

        $response = $this->rpcRequest($template);
        $xml = simplexml_load_string($response);

        $result = [];
        foreach ($xml->xpath('/methodResponse/params/param/value/array/data/value/array/data/value') as $data) {
            $result[(int)$data->array->data->value[1]->array->data->value[0]->int] = [
                'camera' => (string)$data->array->data->value[0]->string,
                'first_id' => (int)$data->array->data->value[1]->array->data->value[1]->int,
                'first_timestamp' => (int)$data->array->data->value[1]->array->data->value[0]->int,
                'second_timestamp' => (int)$data->array->data->value[2]->array->data->value[0]->int,
                'second_id' => (int)$data->array->data->value[2]->array->data->value[1]->int,
                'third_timestamp' => (int)$data->array->data->value[3]->array->data->value[0]->int,
                'third_id' => (int)$data->array->data->value[3]->array->data->value[1]->int,
                'object_ids' => isset($data->array->data->value[5]) ? (int)$data->array->data->value[5]->array->data->value[0]->int : 0
            ];
        }

        return $result;
    }

    public function downloadClip($params)
    {
        $template = <<<EOT
<?xml version="1.0"?>
<methodCall>
    <methodName>remoteGetClipUriForDownload</methodName>
    <params>
        <param>
            <value>
                <string>{$params['camera']}</string>
            </value>
        </param>
        <param>
            <value>
                <array>
                    <data>
                        <value>
                            <int>{$params['first_timestamp']}</int>
                        </value>
                        <value>
                            <int>{$params['first_id']}</int>
                        </value>
                    </data>
                </array>
            </value>
        </param>
        <param>
            <value>
                <array>
                    <data>
                        <value>
                            <int>{$params['second_timestamp']}</int>
                        </value>
                        <value>
                            <int>{$params['second_id']}</int>
                        </value>
                    </data>
                </array>
            </value>
        </param>
        <param>
            <value>
                <int>{$params['id']}</int>
            </value>
        </param>
        <param>
            <value>
                <string>video/h264</string>
            </value>
        </param>
        <param>
            <value>
                <struct>
                    <member>
                        <name>objectIds</name>
                        <value>
                            <array>
                                <data>
                                    <value>
                                        <int>{$params['object_ids']}</int>
                                    </value>
                                </data>
                            </array>
                        </value>
                    </member>
                </struct>
            </value>
        </param>
    </params>
</methodCall>
EOT;

        $response = $this->rpcRequest($template);

        $xml = simplexml_load_string($response);

        $url = sprintf(
            '%s://%s%s?%s',
            'https',
            $this->host,
            $xml->params->param->value->array->data->value[1]->string,
            $params['camera'] . $params['first_timestamp']
        );

        $file = tempnam('/tmp', 'video_' . $params['camera'] . $params['first_timestamp']);
        $filesize = $this->downloadRequest($url, $file);

        return !empty($filesize) ? $file : false;
    }

    public function parseFileName($filename)
    {
        list($camera, $date, $time) = explode('-', $filename);
        if (strpos($time, ' ') === false) { // dirty
            $time = str_replace(['am', 'pm'], [' am', ' pm'], $time);
        }
        list($time, $ampm) = explode(' ', str_replace('.jpg', '', $time));
        $hours = substr($time, 0, 2);
        $minutes = substr($time, 2, 2);
        $seconds = substr($time, 4, 2);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $year = substr($date, 0, 4);
// FIXME
        return [$camera, strtotime(sprintf('%s/%s/%s %s:%s:%s %s GMT+4', $month, $day, $year, $hours, $minutes, $seconds, $ampm))];
    }

    protected function rpcRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $this->auth));
        curl_setopt($ch, CURLOPT_URL, $this->rpc_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);

        return $content;
    }

    protected function downloadRequest($url, $file)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $this->auth));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_HTTPGET, 1);

        $f = fopen($file, 'w');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FILE, $f);

        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($f);

        return filesize($file);
    }

    protected function setAuth($auth)
    {
        $this->auth = $auth;
    }

    protected function setHost($host)
    {
        $this->host = $host;
        $this->rpc_url = sprintf('%s://%s/xmlrpc/', 'https', $host);
    }
}