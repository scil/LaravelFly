<?php
/**
 * User: scil
 * Date: 2018/2/13
 * Time: 14:22
 */

namespace LaravelFly;

use Psy\Shell;
use Psy\Configuration;
use Illuminate\Console\Command;
use Laravel\Tinker\ClassAliasAutoloader;
use Symfony\Component\Console\Input\InputArgument;

class Tinker extends \Laravel\Tinker\Console\TinkerCommand
{
    static protected $shell;
    static protected $loader;

    protected $application;

    static protected $server;

    function make($server=null)
    {
//        parent::__construct('fly_tinker');

        if($server)
            static::$server = $server;

        $config = new Configuration([
            'updateCheck' => 'never'
        ]);

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        static::$shell = $shell = new Shell($config);

        $shell->addCommands($this->getCommands());
        $shell->setIncludes($this->argument('include'));

        $path = static::$server->path('vendor/composer/autoload_classmap.php');

        static::$loader = ClassAliasAutoloader::register($shell, $path);

    }

    static function debug(array $vars = array(), $boundObject = null)
    {
        if (is_null(static::$shell))
            (new static())->make();

        $sh = static::$shell;
        $sh->setScopeVariables($vars);
        if ($sh->has('whereami')) {
            $sh->addInput('whereami -n2', true);
        }
        if ($boundObject !== null) {
            $sh->setBoundObject($boundObject);
        }

        try {
            $sh->run();
        } finally {
            //todo
            static::$loader->unregister();
        }
        return $sh->getScopeVariables(false);
    }


    protected function getCommands()
    {
        $commands = [];

        if ($this->application) {
            $commands = parent::getCommands();
        }

        return $commands;
    }
}

