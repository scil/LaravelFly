<?php

namespace LaravelFly\Simple;

use Illuminate\Config\Repository;
use Illuminate\Events\EventServiceProvider;
//use LaravelFly\Routing\RoutingServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;

class Application extends \Illuminate\Foundation\Application
{
    use \LaravelFly\ApplicationTrait\ProvidersInRequest;
    use \LaravelFly\ApplicationTrait\InConsole;
    use \LaravelFly\ApplicationTrait\Server;
    use \LaravelFly\ApplicationTrait\NoMorePackageManifest;

    protected $needBackUpAppAttributes = [
        'resolved',
        'bindings',
        'methodBindings',
        'instances',
        'aliases',
        'abstractAliases',
        'extenders',
        'tags',
        'contextual',

        'reboundCallbacks',
        'globalResolvingCallbacks',
        'globalAfterResolvingCallbacks',
        'resolvingCallbacks',
        'afterResolvingCallbacks',
        'terminatingCallbacks',

        'serviceProviders',
        'loadedProviders',
        'deferredServices',

        /** not necessary to backup
         *
         * 'buildStack',
         * 'with',
         * 'monologConfigurator'
         *
         * I don't think there're some situatins where a new callback would be inserted into them during any request,
         * that's useless for a php app which would be freed in memory after a request
         * // 'bootingCallbacks',
         * // 'bootedCallbacks',
         */

    ];
    protected $needBackupServiceAttributes = [];
    protected $backupedConfig = [];
    protected $backupedValuesBeforeRequest = [];
    protected $restoreTool = [];

    /**
     * replace \Illuminate\Foundation\Bootstrap\BootProviders::class,
     * code from \Illuminate\Foundation\Application::boot
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->app->registerConfiguredProvidersInRequest();

        $this->fireAppCallbacks($this->bootingCallbacks);

        /**  array_walk
         * If when a provider booting, it reg some other providers,
         * then the new providers added to $this->serviceProviders
         * then array_walk will loop the new ones and boot them. // pingpong/modules 2.0 use this feature
         */
        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }


    public function setBackupedConfig()
    {
        $config = $this->make('config');

        $config->changedConfig = [];

    }

    public function addNeedBackupServiceAttributes($need)
    {
        $this->needBackupServiceAttributes = array_merge($this->needBackupServiceAttributes, $need);
    }

    public function backUpOnWorker()
    {

        foreach ($this->needBackUpAppAttributes as $attri) {
            $this->backupedValuesBeforeRequest[$attri] = $this->$attri;
        }

        foreach ($this->needBackupServiceAttributes as $name => $attris) {
            $o = $this->instances[$name] ?? $this->make($name);
            $changed = $this->backupToolMaker($attris)->call($o);

            /** $changed would be false when
             *    obj.xxx is empty array, such as 'router'=>[ 'obj.routes'=>[] ]
             *    all attributes defind in config/laravelfly are not valid, such as 'url'=>['love','happy']
             */
            if ($changed)
                $this->restoreTool[$name] = $this->restoreToolMaker()->bindTo($o, get_class($o));
        }
//        var_dump($this->restoreTool);

    }

    public function restoreAfterRequest()
    {


        // clear all, not just request
        Facade::clearResolvedInstances();

        foreach ($this->backupedValuesBeforeRequest as $attri => $v) {
//            echo "\n $attri\n";
//            if (is_array($this->$attri))
//                echo 'dif:', count($this->$attri) - count($this->__oldValues[$attri]);
            $this->$attri = $v;
        }


        foreach ($this->restoreTool as $tool) {
            $tool();
        }

        $config = $this->make('config');
        if ($config->changedConfig ) {

            foreach ($config->changedConfig as $key => $value) {
                $config->set($key, $value);
            }
            $config->changedConfig = [];
        }

        $this->booted = false;
    }

    // Accessing private PHP class members without reflection
    // http://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
    protected function backupToolMaker($attriList)
    {
        return function () use ($attriList) {

            $changed = false;
            foreach ($attriList as $key => $attri) {

                if (is_string($key) && substr($key, 0, 4) == 'obj.') {
                    $oAttriName = substr($key, 4);
                    if (!$attri) {
                        continue;
                    }
                    if (!property_exists($this, $oAttriName)) {
                        echo "[WARN] check laravelfly config, obj property '$oAttriName' not exists for ", get_class($this),"\n";
                        continue;
                    }
                    $o = $this->$oAttriName;
                    $info = ['obj' => $o, 'r_props' => [], 'values' => []];

                    $r = new \ReflectionObject($o);
                    foreach ($attri as $o_attr) {
                        $r_attr = $r->getProperty($o_attr);
                        $r_attr->setAccessible(true);
                        $info['r_props'][$o_attr] = $r_attr;
                        $info['values'][$o_attr] = $r_attr->getValue($o);
                    }
                    // note:`$this` is not Application, but the service object, like event,log....
                    $this->__oldObj[] = $info;
                    $changed = true;

                } elseif (property_exists($this, $attri)) {
                    // note:`$this` is not Application, but the service object, like event,log....
                    $this->__old[$attri] = $this->$attri;
                    $changed = true;
                } else {
                    echo "[WARN] check config\laravelfly.php,property '$attri' not exists for ", get_class($this),"\n";

                }
            }
            return $changed;
        };
    }

    protected function restoreToolMaker()
    {
        return function () {
            if (property_exists($this, '__old')) {
                foreach ($this->__old as $name => $v) {
                    $this->$name = $v;
                }
            }

            if (property_exists($this, '__oldObj')) {
                foreach ($this->__oldObj as $info) {
//                    var_dump(app('view')->finder->views);
                    foreach ($info['r_props'] as $s_attr => $r_attr) {
                        $r_attr->setValue($info['obj'], $info['values'][$s_attr]);
                    }
//                    var_dump(app('view')->finder->views);
                }
            }
        };
    }

}
