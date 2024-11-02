<?php

namespace Valet\Drivers;

class LaravelValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/public/index.php') &&
               file_exists($sitePath.'/artisan');
    }

    /**
     * Take any steps necessary before loading the front controller for this driver.
     */
    public function beforeLoading(string $sitePath, string $siteName, string $uri): void
    {
        // Shortcut for getting the "local" hostname as the HTTP_HOST, especially when proxied or using 'share'
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }

        $_SERVER['PHP_SELF'] = $uri;
        $_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['REMOTE_ADDR'] = $this->remoteIP();
    }

    public function remoteIP()
    {
        if ($_SERVER['HTTP_X_REAL_IP'] ?? false) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? false) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] ?? $_SERVER['REMOTE_ADDR'];
        }

        return $_SERVER['REMOTE_ADDR'];

    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)
           && is_file($staticFilePath)) {
            return $staticFilePath;
        }

        $storageUri = $uri;

        if (strpos($uri, '/storage/') === 0) {
            $storageUri = substr($uri, 8);
        }

        if ($this->isActualFile($storagePath = $sitePath.'/storage/app/public'.$storageUri)) {
            return $storagePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        if (file_exists($staticFilePath = $sitePath.'/public'.$uri)
           && $this->isActualFile($staticFilePath)) {
            return $staticFilePath;
        }

        return $sitePath.'/public/index.php';
    }
}
