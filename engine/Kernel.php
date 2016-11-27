<?php

namespace femtimo\engine;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Kernel implements HttpKernelInterface
{
    protected $container;

    private $configuration;


    public function __construct(
        $themeFolder,
        $namespace,
        $defaultController = 'Index',
        $defaultAction = 'index',
        $componentFolder = null
    )
    {
        $this->configuration['theme'] = $themeFolder;
        $this->configuration['component'] = $componentFolder;
        $this->configuration['namespace'] = $namespace;
        $this->configuration['controller'] = $defaultController;
        $this->configuration['action'] = $defaultAction;
        if (empty($this->configuration['namespace']) || empty($this->configuration['theme'])){
            throw new \Exception("Missing construct param.");
        }
    }

    public function handle(
        \Symfony\Component\HttpFoundation\Request $request,
        $type = self::MASTER_REQUEST,
        $catch = true)
    {
        if (PHP_SAPI === 'cli') {
            return new \Symfony\Component\HttpFoundation\Response('<html><body>CLI-Mode currently not supported.</body></html>>');
        } else {
            if (count($request->getRequestUri()) != 0) {
                $param = explode('?', $request->getRequestUri());
                $req = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $param[0]), 'strlen'));
                if (empty($req)) {
                    $controllerName = $this->configuration['namespace'] . $this->configuration['controller'] . 'Controller';
                } else {
                    $controllerName = $this->configuration['namespace'] . ucfirst($req[0]) . 'Controller';
                }
            }
                if (class_exists($controllerName)) {
                    (isset($req[1]) ? $actionName = $req[1] . 'Action' : $actionName = $this->configuration['action'] . 'Action');

                    $controller = new $controllerName($request, $this->initializeContainer());

                    $methods = get_class_methods($controllerName);

                    if (in_array($actionName, $methods)) {
                        $paramNames = array_map(function ($item) {
                            return $item->getName();
                        }, (new \ReflectionMethod($controller, $actionName))->getParameters());

                        $paramCall = [];
                        foreach ($paramNames as $paramName) {
                            if ($request->query->get($paramName)) {
                                $paramCall[] = $request->query->get($paramName);
                            }
                        }
                        if (count(array_filter($paramCall)) == 0) {
                            (call_user_func([$controller, $actionName]));
                        } else {
                            (call_user_func_array([$controller, $actionName], $paramCall));
                        }

                        if ($redirect = $controller->isRedirect()) {
                            return $redirect;
                        } elseif ($redirect = $controller->isJson()) {
                            return new JsonResponse($controller->getJson());
                        } else {
                            return new Response($this->configuration['theme'] . str_replace("Controller", "", $controller) . DIRECTORY_SEPARATOR . str_replace("Action", "", $actionName) . ".php");
                        }
                    } else {
                        return new RedirectResponse(DIRECTORY_SEPARATOR . $this->configuration['controller']);
                    }
                } else {
                    return \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST;
                }
        }
    }

    private function initializeContainer()
    {
        $this->container = new ContainerBuilder();
        $files = array_diff(scandir('engine' . DIRECTORY_SEPARATOR . 'component'), array('.', '..'));

        foreach ($files as $file) {
            if ($item = pathinfo("engine" . DIRECTORY_SEPARATOR . "component" . DIRECTORY_SEPARATOR . "$file")) {
                if ($item["extension"] === "php") {
                    $this->container->register($item["filename"], "femtimo\\engine\\component\\" . $item["filename"]);
                }
            }
        }

        $files = array_diff(scandir($this->configuration['component']), array('.', '..'));

        foreach ($files as $file) {
            if ($item = pathinfo($this->configuration['component'] . DIRECTORY_SEPARATOR . "$file")) {
                if ($item["extension"] === "php") {
                    $this->container->register($item["filename"], $this->configuration['namespace'] . $item["filename"]);
                }
            }
        }
        return $this->container;
    }
}

?>