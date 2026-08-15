<?php
/**
 * Fallback PSR-4 autoloader used only when `composer install` has not been run
 * (vendor/autoload.php missing). If Composer's autoloader exists it always wins.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'PetScript\\RxCheckout\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($path)) {
        require $path;
    }
});
