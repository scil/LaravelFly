<?php

namespace LaravelFly\Tests\Map\Feature;

use Dotenv\Loader;
use LaravelFly\Tests\Map\MapTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class ObjectsInWorkerTest extends MapTestCase
{

    protected $instances = [
        'path',
        'path.base',
        'path.lang',
        'path.config',
        'path.public',
        'path.storage',
        'path.database',
        'path.resources',
        'path.bootstrap',
        'app',
        'Illuminate\Foundation\Container',
        'Illuminate\Foundation\PackageManifest',
        'events',
        'router',
        'Illuminate\Contracts\Http\Kernel',
        'request',
        'config',

        'db.factory',
        'db',
        'view.engine.resolver',
        'files',
        'view',

        'Illuminate\Contracts\Auth\Access\Gate',
        'routes',
        'url',
        'Illuminate\Contracts\Debug\ExceptionHandler',
        'blade.compiler',
        'translation.loader',
        'translator',
        'validation.presence',
        'validator',
        'session',

        'cache',
        'session.store',
        'Illuminate\Session\Middleware\StartSession',
        'hash',
        'filesystem',
        'filesystem.disk',
        'encrypter',
        'cookie',
        'cache.store',
        'auth',
        'log',
    ];

    protected $allStaticProperties = [
        'app' => ['instance'],
        'Illuminate\Foundation\Container' => ['instance'],
        'router' => ['macros','verbs'],
        'files' => ['macros'],
        'view' => ['parentPlaceholder'],
        'url' => ['macros'],
        'translator' => ['macros'],
        'cache.store' => ['macros'],
        'blade.compiler' => ['mapFly'],

        // don't worry about 'request', every request has it's own 'request'.
        // The 'request' object in worker is a fake request.
        // see: \LaravelFly\Server\HttpServer::onWorkerStart
        'request'=>[
            'formats',
            'httpMethodParameterOverride',
            'macros',
            'requestFactory',
            'trustedHostPatterns',
            'trustedHosts',
            'trustedProxies',
        ]
    ];

    static function initConfig(){

        (new Loader(''))->setEnvironmentVariable('APP_ENV','production');
        @unlink(static::$laravelAppRoot.'/bootstrap/cache/config.php');
        @unlink(static::$laravelAppRoot.'/bootstrap/cache/laravelfly_config_map.php');
        @unlink(static::$laravelAppRoot.'/bootstrap/cache/laravelfly_config_simple.php');

    }

    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::initConfig();

        static::makeNewFlyServer(['LARAVELFLY_MODE' => 'Map'], ['worker_num' => 1]);

        static::$chan = $chan = new \Swoole\Channel(1024 * 256);

        static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan) {
            $appR = new \ReflectionObject($event['app']);
            $corDictR = $appR->getProperty('corDict');
            $corDictR->setAccessible(true);
            $instances = $corDictR->getValue()[WORKER_COROUTINE_ID]['instances'];

            $chan->push(array_keys($instances));

            $allStaticProperties = [];
            foreach ($instances as $name => $instance) {
                if (!is_object($instance)) continue;
                $instanceR = new \ReflectionObject($instance);
                $staticProperties = array_keys($instanceR->getStaticProperties());
                if ($staticProperties) {
                    $clean = array_diff($staticProperties, ['corDict', 'corStaticDict',
                        'normalAttriForObj','arrayAttriForObj','normalStaticAttri','arrayStaticAttri'
                        ]);
                    if ($clean){
                        sort($clean); // force it index from 0 ,otherwise self::assertEqual fail
                        $allStaticProperties[$name] = $clean;
                    }
                }
            }
            $chan->push($allStaticProperties);

            sleep(3);
            $event['server']->getSwooleServer()->shutdown();
        });

        static::$flyServer->start();

    }

    function testInstances()
    {
        $instances = static::$chan->pop();
        // PHPUnit: assert two arrays are equal, but order of elements not important
        // https://stackoverflow.com/questions/3838288/phpunit-assert-two-arrays-are-equal-but-order-of-elements-not-important
        self::assertEquals($this->instances, $instances, "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true);

        //self::assertEquals($this->instances, $instances);
    }

    function testStaticProperties()
    {
        $allStaticProperties =  static::$chan->pop();
        self::assertEquals($this->allStaticProperties, $allStaticProperties);
    }
}

