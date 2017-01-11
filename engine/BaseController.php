<?php
/**
 * User: Eduard Grünwald
 * Date: 27.11.2016
 * Time: 02:09
 */

namespace femtimo\engine;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Class BaseController
 *
 * @package femtimo\engine
 */
class BaseController
{
    /** @var  \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var  RedirectResponse $redirect */
    protected $redirect;

    /**
     * @var boolean
     */
    protected $dontDisplay;

    /**
     * @var
     */
    protected $json;

    /**
     * BaseController constructor.
     *
     * @param $request Request
     * @param $container Container
     * @param $from array
     */
    public function __construct($request, $container)
    {
        /** @var Request request */
        $this->request = $request;
        /** @var Container container */
        $this->container = $container;
        /** @var boolean dontDisplay */
        $this->dontDisplay = false;
    }

    /**
     * @param     $controller
     * @param     $action
     * @param int $status
     */
    protected function redirect($controller, $action, $status = 302)
    {
        $path = $this->resolveUri();
        if ($path === false) {
            $response = new RedirectResponse("$controller" . DIRECTORY_SEPARATOR . "$action");
            $response->setStatusCode($status);
            $this->redirect = $response;
        } else {
            if (strcmp($path['controller'], $controller) !== 0) {
                $response = new RedirectResponse("$controller" . DIRECTORY_SEPARATOR . "$action");
                $response->setStatusCode($status);
                $this->redirect = $response;
            } else {
                if (strcmp($path['action'], $action) !== 0) {
                    $response = new RedirectResponse("$controller" . DIRECTORY_SEPARATOR . "$action");
                    $response->setStatusCode($status);
                    $this->redirect = $response;
                }
            }
        }
    }

    private function resolveUri()
    {
        $param = explode('?', $this->request->getRequestUri());
        $path = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $param[0]), 'strlen'));

        if (empty($path)) {
            return false;
        }
        if (isset($path[0])) {
            if (isset($path[1])) {
                return ['controller' => $path[0], 'action' => $path[1]];
            } else {
                return ['controller' => $path[0]];
            }
        }
    }

    /**
     * @return boolean
     */
    public function isRedirect()
    {
        return empty($this->redirect) ? false : true;
    }

    /**
     * @return boolean
     */
    public function isJson()
    {
        return empty($this->json) ? false : true;
    }

    /**
     * @return RedirectResponse
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /** @param $json array */
    public function setXHR($json)
    {
        $this->json = json_encode($json);
    }

    /**
     * @return mixed
     */
    public function getJson()
    {
        return $this->json;
    }

    /** @param $json array */
    public function setJson($json)
    {
        $this->json = json_encode($json);
    }

    /**
     * @return mixed
     */
    public function isDontDisplay()
    {
        return empty($this->dontDisplay) ? false : true;
    }


    /**
     * @return mixed
     */
    public function getDontDisplay()
    {
        return $this->dontDisplay;
    }

    /**
     * @param mixed $dontDisplay
     */
    public function setDontDisplay($dontDisplay)
    {
        $this->dontDisplay = $dontDisplay;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @param      $controller
     * @param      $action
     * @param null $params
     *
     * @return int|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function forward($controller, $action, $params = null)
    {

        $path = $this->resolveUri();
        if ($path === false) {
            return $this->forwardLogic($controller, $action, $params);
        } else {
            if (strcmp($path['controller'], $controller) !== 0) {
                return $this->forwardLogic($controller, $action, $params);
            } else {
                if (strcmp($path['action'], $action) !== 0) {
                    return $this->forwardLogic($controller, $action, $params);
                }
            }
        }
    }
}