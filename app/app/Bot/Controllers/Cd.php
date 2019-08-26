<?php

namespace Bot\Controllers;

use GuzzleHttp\Client;

class Cd extends Controller
{
    public function remote()
    {
        return $this->runBuild(getenv("CD_REMOTE_SERVER"));
    }

    public function local()
    {
        return $this->runBuild(getenv("CD_LOCAL_SERVER"));
    }

    protected function runBuild($cd_server)
    {
        $payload = $this->request->getContent();
        $response = [
            "message" => "Empty request"
        ];
        if (!empty($payload) && $decoded = json_decode($payload)) {
            try {
                $client = new Client();
                $response = $client->post($cd_server, [
                    'json' => $decoded
                ]);
            } catch (\Exception $e) {
                $response["message"] = $e->getMessage();
            }
        }

        return $response;
    }
}