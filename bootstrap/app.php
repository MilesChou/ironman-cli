<?php

use App\Commands\Example;
use App\Version;
use Illuminate\Console\Application as IlluminateApplication;
use LaravelBridge\Scratch\Application as LaravelBridge;

require dirname(__DIR__) . '/vendor/autoload.php';

return (static function () {
    $container = (new LaravelBridge())
        ->bootstrap();

    $app = new IlluminateApplication($container, $container->make('events'), Version::VERSION);
    $app->add(new Example());

    return $app;
})();
