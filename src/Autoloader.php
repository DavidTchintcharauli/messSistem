<?php
spl_autoload_register(function(string $class){
    if (strpos($class, 'App\\') !== 0) return;
    $path = __DIR__ . '/' . str_replace('App\\', '', $class) . '.php';
    $path = str_replace('\\', '/', $path);
    if (is_file($path)) require $path;
});
