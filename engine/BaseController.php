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
 * @package femtimo\engine
 */
class BaseController
{
    /** @var  \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var  RedirectResponse $redirect */
    protected $redirect;

    protected $action;

    protected $controller;

    /**
     * @var
     */
    protected $json;

    /**
     * BaseController constructor.
     * @param $request Request
     * @param $container Container
     * @param $from array
     */
    public function __construct($request, $container, $from)
    {
        /** @var Request request */
        $this->request = $request;
        /** @var Container container */
        $this->container = $container;

        $this->action = $from['action'];
        $this->controller = $from['controller'];

        /*
         * If a class authentication available,
         * check if we're in. If not move to AuthenticationController->indexAction
         * */
        if (is_object($this->container->get('authentication'))) {
            if ($this->container->get('authentication')->login() === false) {
                $this->redirect('authentication', 'index');
            }
        }
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @param $controller
     * @param $action
     * @param int $status
     */
    protected function redirect($controller, $action, $status = 302)
    {
        if (strcmp($this->controller, $controller) !== 0) {
                $response = new RedirectResponse("$controller" . DIRECTORY_SEPARATOR . "$action");
                $response->setStatusCode($status);
                $this->redirect = $response;
        }
        else{
            if(strcmp($this->action, $action) !== 0){
                $response = new RedirectResponse("$controller" . DIRECTORY_SEPARATOR . "$action");
                $response->setStatusCode($status);
                $this->redirect = $response;
            }
        }
    }

    /**
     * @param $controller
     * @param $action
     * @param null $params
     * @return int|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function forward($controller, $action, $params = null)
    {

        if (strcmp($this->controller, $controller) !== 0) {
                if ($this->action != $action) {
                    if (isset($params)) {
                        $this->request->attributes->add($params);
                    }

                    $subRequest = $this->request->duplicate(
                        array_merge($this->request->query->all(), $params),
                        array_merge($this->request->request->all(), $params),
                        null,
                        null,
                        null,
                        array_merge($this->request->server->all(), [
                            'REQUEST_URI' => DIRECTORY_SEPARATOR . "$controller" . DIRECTORY_SEPARATOR . "$action",
                            'REDIRECT_URL' => DIRECTORY_SEPARATOR . "$controller" . DIRECTORY_SEPARATOR . "$action",
                        ]));
                    return ((new Kernel())->handle($subRequest, HttpKernel::SUB_REQUEST));
                }
        }
        else{
            if(strcmp($this->action, $action) !== 0){
                if ($this->action != $action) {
                    if (isset($params)) {
                        $this->request->attributes->add($params);
                    }

                    $subRequest = $this->request->duplicate(
                        array_merge($this->request->query->all(), $params),
                        array_merge($this->request->request->all(), $params),
                        null,
                        null,
                        null,
                        array_merge($this->request->server->all(), [
                            'REQUEST_URI' => DIRECTORY_SEPARATOR . "$controller" . DIRECTORY_SEPARATOR . "$action",
                            'REDIRECT_URL' => DIRECTORY_SEPARATOR . "$controller" . DIRECTORY_SEPARATOR . "$action",
                        ]));
                    return ((new Kernel())->handle($subRequest, HttpKernel::SUB_REQUEST));
                }
            }
        }
    }

    /**
     * @return RedirectResponse
     */
    public function isRedirect()
    {
        return $this->redirect;
    }

    /**
     * @return mixed
     */
    public function isJson()
    {
        return $this->json;
    }

    /** @param $json array */
    public function setXHR($json)
    {
        $this->json = json_encode($json);
    }

    /** @param $json array */
    public function setJson($json)
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
}