<?php

namespace CSVAPI\Controllers;


use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Controller
 * @package CSVAPI
 */
abstract class BaseController
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Controller constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $app['request_stack']->getCurrentRequest();

    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }






}