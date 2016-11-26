<?php
require_once 'vendor/autoload.php';

use femtimo\engine\Kernel;

print_r(get_declared_classes);

$k = new Kernel();

echo $k->test();