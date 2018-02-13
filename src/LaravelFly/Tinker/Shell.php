<?php
/**
 * User: scil
 * Date: 2018/2/13
 * Time: 14:22
 */

namespace LaravelFly\Tinker;

use Psy\Shell as BaseShell;
use Psy\Configuration;
use Illuminate\Console\Command;
use Laravel\Tinker\ClassAliasAutoloader;
use Symfony\Component\Console\Input\InputArgument;

class Shell extends \Psy\Shell
{
    protected $config;

    /**
     * @var \Laravel\Tinker\Console\TinkerCommand
     */
    protected $laravelTinkerCommand;

    protected $loader;

    protected $application;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    protected $server;

    static $me;

    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);
        $this->config = $config ?: new Configuration();
        $this->config->setShell($this);
        \Psy\info($this->config);
    }

    function init($server)
    {
        $this->server = $server;
        $this->laravelTinkerCommand = new TinkerCommand();
        $this->make();
    }

    function setApplication($app)
    {
        $this->laravelTinkerCommand->setApplication($app);
    }

    function make()
    {
        $config = new Configuration([
            'updateCheck' => 'never'
        ]);

        $config->getPresenter()->addCasters(
            $this->laravelTinkerCommand->getCasters()
        );

        static::$me = $shell = new Shell($config);

        $shell->addCommands($this->getCommands());
        //todo
//        $shell->setIncludes($this->argument('include'));

        $path = $this->server->path('vendor/composer/autoload_classmap.php');

        $this->loader = ClassAliasAutoloader::register($shell, $path);

    }

    static function debug(array $vars = array(), $boundObject = null)
    {

        $sh = static::$me;
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
//            $this->loader->unregister();
        }
        return $sh->getScopeVariables(false);
    }


    protected function getCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new WhereamiCommand($this->config->colorMode());

        if ($this->laravelTinkerCommand->hasApplication()) {
            $commands = array_merge($commands, $this->laravelTinkerCommand->getCommands());
        }

        return $commands;
    }
}


