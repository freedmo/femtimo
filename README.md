# femtimo
A web framework, build/based on symfony components. 
It allows developer to build, easy website based on MVC with magic routing.

# About current state

# todo
* better configuration, via yaml.
* seo url for routes
* remove view part from kernel, and make it avaible throw plugin/component
* basic doctrine plugin/component to be published

# Example index.php
A few changes about configuration are coming soon.

```php
<?php

reuqire_once "vendor/autoload.php";

use femtimo\engine\Kernel;

$application = new Kernel(
"view" /* Folder for view */,
"Project\\Controller\\" /* Namespace from Controller */,
"Project\\Components\\" /* Folder for autoloaded class by DI-Container */,
"Blog" /* Basic controller, which is used as default */,
"show" /* Basic action of basic controller, which is used as default */,
"component/" /* Default path for component folder */
);

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$response = $application->handle($request);

if($response){
  $responce->send();
}
```
