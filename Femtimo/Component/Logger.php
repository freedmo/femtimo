<?php
/**
 * Project: Femtimo.
 * User: Eduard Grünwald <freedmo@freedmo.de>
 * Date: 27.11.2016
 * Time: 02:29
 */

namespace femtimo\engine\component;


class Logger
{
    public function __construct()
    {
//        dump(__CLASS__);
    }

    public function out($message, $debug = false)
    {
        if ($debug)
            dump($message);
        else
            echo "$message<br />";
    }

    public function log($message, $file = 'log.txt', $path = '/var/log')
    {
        if (!file_exists('/var/log')) {
            mkdir('/var/log');
        }
        file_put_contents($path . DIRECTORY_SEPARATOR . $file, $message, FILE_APPEND);
    }
}