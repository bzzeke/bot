<?php

namespace Bot\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request as SRequest;
use Symfony\Component\HttpFoundation\Response as SResponse;

abstract class Controller
{
    protected $app;
    protected $request;

    public function __construct(Application $app, SRequest $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    public function handle(string $action)
    {
        if (method_exists($this, $action)) {
            $reflection = new \ReflectionMethod($this, $action);
            if ($reflection->isPublic()) {
                return $this->$action();
            }
        }

        return $this->response('Action not found', 404);
    }

    protected function response(string $text, int $status)
    {
        return new SResponse($text, $status);
    }
}