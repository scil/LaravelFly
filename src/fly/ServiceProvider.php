<?php
/**
 * hack to make Container to work in LaravelFly Mode Coroutine
 *
 * because app is cloned, there may be many instances of Container
 *
 */

namespace Illuminate\Support;

use Illuminate\Console\Application as Artisan;
use LaravelFly\Coroutine\Util\Containers;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
     // hack to make Container to work in LaravelFly Mode Coroutine
    // protected $app;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The paths that should be published.
     *
     * @var array
     */
    public static $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * @var array
     */
    public static $publishGroups = [];

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        // hack by laravelfly
        // $this->app = $app;
        static::initStaticArrayForOneCoroutine(-1);
    }

    /**
     * support $this->app for this class and its subclasses in Mode Coroutine
     *
     * hack by laravelfly
     *
     * why use __get, not adding a new attri  $containers like $app('event')
     * because this class has subclasses, they could use $this->app
     *
     * @param $key
     * @return \LaravelFly\Coroutine\Application|null
     */
    function __get($key)
    {
        if($key==='app'){
            return \Illuminate\Container\Container::getInstance();
        }
    }
    static function initStaticArrayForOneCoroutine(int $id )
    {
        static::$publishes[$id] = $id==-1? []: static::$publishes[-1];
        static::$publishGroups[$id] = $id==-1? []: static::$publishGroups[-1];
    }
    static function delStaticArrayForOneCoroutine(int $id )
    {
        unset(static::$publishes[$id],static::$publishGroups[$id]);
    }
    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge(require $path, $config));
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($path)
    {
        if (! $this->app->routesAreCached()) {
            require $path;
        }
    }

    /**
     * Register a view file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadViewsFrom($path, $namespace)
    {
        if (is_array($this->app->config['view']['paths'])) {
            foreach ($this->app->config['view']['paths'] as $viewPath) {
                if (is_dir($appPath = $viewPath.'/vendor/'.$namespace)) {
                    $this->app['view']->addNamespace($namespace, $appPath);
                }
            }
        }

        $this->app['view']->addNamespace($namespace, $path);
    }

    /**
     * Register a translation file namespace.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadTranslationsFrom($path, $namespace)
    {
        $this->app['translator']->addNamespace($namespace, $path);
    }

    /**
     * Register a JSON translation file path.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadJsonTranslationsFrom($path)
    {
        $this->app['translator']->addJsonPath($path);
    }

    /**
     * Register a database migration path.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadMigrationsFrom($paths)
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  string  $group
     * @return void
     */
    protected function publishes(array $paths, $group = null)
    {
        $this->ensurePublishArrayInitialized($class = static::class);

        $cid=\Swoole\Coroutine::getuid();
        static::$publishes[$cid][$class] = array_merge(static::$publishes[$cid][$class], $paths);

        if ($group) {
            $this->addPublishGroup($group, $paths);
        }
    }

    /**
     * Ensure the publish array for the service provider is initialized.
     *
     * @param  string  $class
     * @return void
     */
    protected function ensurePublishArrayInitialized($class)
    {
        $cid=\Swoole\Coroutine::getuid();
        if (! array_key_exists($class, static::$publishes[$cid])) {
            static::$publishes[$cid][$class] = [];
        }
    }

    /**
     * Add a publish group / tag to the service provider.
     *
     * @param  string  $group
     * @param  array  $paths
     * @return void
     */
    protected function addPublishGroup($group, $paths)
    {
        $cid=\Swoole\Coroutine::getuid();
        if (! array_key_exists($group, static::$publishGroups[$cid])) {
            static::$publishGroups[$cid][$group] = [];
        }

        static::$publishGroups[$cid][$group] = array_merge(
            static::$publishGroups[$cid][$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * @param  string  $provider
     * @param  string  $group
     * @return array
     */
    public static function pathsToPublish($provider = null, $group = null)
    {
        $cid=\Swoole\Coroutine::getuid();
        if (! is_null($paths = (static::pathsForProviderOrGroup[$cid])($provider, $group))) {
            return $paths;
        }

        return collect(static::$publishes[$cid])->reduce(function ($paths, $p) {
            return array_merge($paths, $p);
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * @param  string|null  $provider
     * @param  string|null  $group
     * @return array
     */
    protected static function pathsForProviderOrGroup($provider, $group)
    {
        $cid=\Swoole\Coroutine::getuid();
        if ($provider && $group) {
            return (static::pathsForProviderAndGroup[$cid])($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publishGroups[$cid])) {
            return static::$publishGroups[$cid][$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes[$cid])) {
            return static::$publishes[$cid][$provider];
        } elseif ($group || $provider) {
            return [];
        }
    }

    /**
     * Get the paths for the provider and group.
     *
     * @param  string  $provider
     * @param  string  $group
     * @return array
     */
    protected static function pathsForProviderAndGroup($provider, $group)
    {
        $cid=\Swoole\Coroutine::getuid();
        if (! empty(static::$publishes[$cid][$provider]) && ! empty(static::$publishGroups[$cid][$group])) {
            return array_intersect_key(static::$publishes[$cid][$provider], static::$publishGroups[$cid][$group]);
        }

        return [];
    }

    /**
     * Get the service providers available for publishing.
     *
     * @return array
     */
    public static function publishableProviders()
    {
        return array_keys(static::$publishes[\Swoole\Coroutine::getuid()]);
    }

    /**
     * Get the groups available for publishing.
     *
     * @return array
     */
    public static function publishableGroups()
    {
        return array_keys(static::$publishGroups[\Swoole\Coroutine::getuid()]);
    }

    /**
     * Register the package's custom Artisan commands.
     *
     * @param  array|mixed  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        Artisan::starting(function ($artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }
}
