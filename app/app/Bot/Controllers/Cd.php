<?php

namespace Bot\Controllers;

use GuzzleHttp\Client;

class Cd extends Controller
{
    public function hook()
    {
        $payload = $this->request->getContent();

        if (!empty($payload) && $decoded = json_decode($payload)) {
            $client = new Client();
            $client->post(getenv("CD_SERVER"), [
                'json' => $decoded
            ]);
        }

        return "OK";
    }
}