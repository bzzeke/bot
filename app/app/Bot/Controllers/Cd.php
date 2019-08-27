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
            "error" => "Empty request"
        ];
        if (!empty($payload) && $decoded = json_decode($payload)) {
            try {
                $client = new Client();
                $cd_response = $client->post($cd_server, [
                    'json' => $decoded
                ]);
                $response = json_decode($cd_response->getBody(), true);
            } catch (\Exception $e) {
                $response["error"] = $e->getMessage();
            }
        }

        return json_encode($response);
    }
}