<?php

namespace CSVAPI\Controllers;

use CSVAPI\Service;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ServiceController extends BaseController
{
    protected $request;
    protected $service;

    public function __construct(Application $app, Service $service)
    {
        parent::__construct($app);
        $this->service = $service;

        $this->initServiceFromRequest($this->getRequest());

    }

    public function parseDataAction() {
        $data = $this->service->getData();
        $response = new JsonResponse($data);

        if ($this->request->query->get('callback')) {
            $callback = (string)$this->request->query->get('callback');
            $response->setCallback($callback);
        }


        return $response;
    }




    protected function initServiceFromRequest(Request $request) {

        //@TODO - validate these inputs
        $this->service
            ->setSource((string) $request->query->get('source'))
            ->setSourceFormat((string) $request->query->get('format'))
            ->setSort((string) $request->query->get('sort'))
            ->setSortDir((string) $request->query->get('sortDir'));

    }
}