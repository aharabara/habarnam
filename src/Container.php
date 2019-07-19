<?php

use Base\Core\Workspace;
use Illuminate\Container\Container as BaseContainer;
use Illuminate\Contracts\Foundation\Application;

class Container extends BaseContainer implements Application
{
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return '0.5';
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @return string
     */
    public function basePath()
    {
        return Workspace::rootPath();
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string $path Optionally, a path to append to the bootstrap path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return __DIR__."/../bootstrap/$path";
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param  string $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath($path = '')
    {
        return Workspace::rootPath("/app/config/$path");
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string $path Optionally, a path to append to the database path
     * @return string
     */
    public function databasePath($path = '')
    {
        return Workspace::rootPath("/database/$path");
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath()
    {
        return $this->basePath();
    }

    /**
     * Get the path to the resources directory.
     *
     * @param  string $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return Workspace::resourcesPath($path);
    }

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath()
    {
        return Workspace::rootPath('/storage/');
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array $environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        return $environments === 'production';
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return true;
    }

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return false;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        // TODO: Implement registerConfiguredProviders() method.
        throw new BadMethodCallException('Not implemented. Dont forget to add service provider and get them from config.');
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param  bool $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $force = false)
    {
        throw new BadMethodCallException('Not implemented. Dont forget to add service provider and get them from config.');
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string $provider
     * @param  string|null $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        throw new BadMethodCallException('Not implemented. Dont forget to add service provider and get them from config.');
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProvider($provider)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Register a new boot listener.
     *
     * @param  callable $callback
     * @return void
     */
    public function booting($callback)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  callable $callback
     * @return void
     */
    public function booted($callback)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Run the given array of bootstrap classes.
     *
     * @param  array $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Determine if the application configuration is cached.
     *
     * @return bool
     */
    public function configurationIsCached()
    {
        throw new BadMethodCallException('Not implemented.');
        return false;
    }

    /**
     * Detect the application's current environment.
     *
     * @param  \Closure $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @return array
     */
    public function getProviders($provider)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Set the environment file to be loaded during bootstrapping.
     *
     * @param  string $file
     * @return $this
     */
    public function loadEnvironmentFrom($file)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Set the current application locale.
     *
     * @param  string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware()
    {
        throw new BadMethodCallException('Not implemented.');
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate()
    {
        // TODO: Implement terminate() method.
        throw new BadMethodCallException('Not implemented.');
    }
}