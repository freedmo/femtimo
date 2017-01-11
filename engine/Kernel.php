<?php
/**
 * User: Eduard Grünwald
 * Date: 27.11.2016
 * Time: 02:09
 */

namespace femtimo\engine;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class Kernel
 * @package femtimo\engine
 */
class Kernel implements HttpKernelInterface
{
    /**
     * @var
     */
    protected $container;

    /**
     * @var
     */
    private $configuration;

    /**
     * Kernel constructor.
     * @param $themeFolder
     * @param $namespace
     * @param $namespaceComponent
     * @param string $defaultController
     * @param string $defaultAction
     * @param null $componentFolder
     * @throws \Exception
     */
    public function __construct(
        $themeFolder,
        $namespace,
        $namespaceComponent,
        $defaultController = 'Index',
        $defaultAction = 'index',
        $componentFolder = null
    )
    {
        $this->configuration['theme'] = $themeFolder;
        $this->configuration['component'] = $componentFolder;
        $this->configuration['namespace'] = $namespace;
        $this->configuration['namespaceComponent'] = $namespaceComponent;
        $this->configuration['controller'] = $defaultController;
        $this->configuration['action'] = $defaultAction;
        if (empty($this->configuration['namespace']) || empty($this->configuration['theme'])) {
            throw new \Exception("Missing construct param.");
        }
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $type
     * @param bool $catch
     * @return int|RedirectResponse|Response
     */
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
                list($controllerShort, $controllerName) = $this->generateController($req);
            }
            if (class_exists($controllerName)) {
                list($actionShort, $actionName) = $this->generateAction($req);

                /** @var BaseController $controller */
                $controller = new $controllerName($request, $this->initializeContainer());

                $methods = get_class_methods($controllerName);

                if (in_array($actionName, $methods)) {
                    $paramCall = $this->generateParam($request, $controller, $actionName);

                    if (count(array_filter($paramCall)) == 0) {
                        (call_user_func([$controller, $actionName]));
                    } else {
                        (call_user_func_array([$controller, $actionName], $paramCall));
                    }

                    if ($controller->isRedirect()) {
                        return $controller->getRedirect();
                    } elseif ($controller->isJson()) {
                        return new JsonResponse($controller->getJson());
                    } else {
                        if (is_object($this->container->get('view')))
                            return new Response($this->container->get('view')->display($this->configuration['theme'] . DIRECTORY_SEPARATOR . $controllerShort . DIRECTORY_SEPARATOR . $actionShort . ".tpl"));
                    }
                } else {
                    return new RedirectResponse(DIRECTORY_SEPARATOR . $this->configuration['controller']);
                }
            } else {
                return \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST;
            }
        }
    }

    /**
     * @param $req
     * @return array
     */
    private function generateController($req)
    {
        if (empty($req)) {
            $controllerName = $this->configuration['namespace'] . ucfirst($this->configuration['controller']) . 'Controller';
            $controllerShort = ucfirst($this->configuration['controller']);
        } else {
            $controllerName = $this->configuration['namespace'] . ucfirst($req[0]) . 'Controller';
            $controllerShort = ucfirst($req[0]);
        }
        return [$controllerShort, $controllerName];
    }

    /**
     * @param $req
     * @return array
     */
    private function generateAction($req)
    {
        if (isset($req[1])) {
            $actionShort = $req[1];
            $actionName = $req[1] . 'Action';
        } else {
            $actionShort = $this->configuration['action'];
            $actionName = $this->configuration['action'] . 'Action';
        }
        return [$actionShort, $actionName];
    }

    /**
     * @return ContainerBuilder
     */
    private function initializeContainer()
    {
        $this->container = new ContainerBuilder();

        $files = array_diff(scandir(realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'component'), array('.', '..'));
        foreach ($files as $file) {
            if ($item = pathinfo(realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "component" . DIRECTORY_SEPARATOR . "$file")) {
                if ($item["extension"] === "php") {
                    $this->container->register($item["filename"], "femtimo\\engine\\component\\" . $item["filename"]);
                }
            }
        }

        $files = array_diff(scandir($this->configuration['component']), array('.', '..'));
        foreach ($files as $file) {
            if ($item = pathinfo($this->configuration['component'] . DIRECTORY_SEPARATOR . "$file")) {
                if (
                    $item["extension"] === "php"
                ) {
                    $this->container->register($item["filename"], $this->configuration['namespaceComponent'] . $item["filename"]);
                }
            }
        }
        return $this->container;
    }

    /**
     * @param $request
     * @param $controller
     * @param $action
     */
    public function generateParam($request, $controller, $action)
    {
        $paramCall = [];
        $paramNames = array_map(function ($item) {
            return $item->getName();
        }, (new \ReflectionMethod($controller, $action))->getParameters());

        foreach ($paramNames as $paramName) {
            if ($request->query->get($paramName)) {
                $paramCall[] = $request->query->get($paramName);
            }
        }
        return $paramCall;
    }
}

?>