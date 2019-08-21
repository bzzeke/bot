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

        if (!empty($payload) && $decoded = json_decode($payload)) {
            try {
                $client = new Client();
                $client->post($cd_server, [
                    'json' => $decoded
                ]);
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        return "OK";
    }
}