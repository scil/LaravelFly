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
     * @var \Illuminate\Foundation\Application
     */
    protected $application;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    protected $server;

    /**
     * @var Shell
     */
    static $instance;

    protected $loader;


    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);
        $this->config = $config ?: new Configuration();
        $this->config->setShell($this);
        \Psy\info($this->config);
    }

    /**
     * @param $server \LaravelFly\Server\ServerInterface
     */
    static function make($server)
    {
        $config = new Configuration([
            'updateCheck' => 'never'
        ]);

        $tk = new TinkerCommand();

        $config->getPresenter()->addCasters(
            $tk->getCasters()
        );

        static::$instance = $shell = new Shell($config);

        $shell->server = $server;

        $commands = $shell->getDefaultCommands();
        // this will overwrite Psy's WhereamiCommand
        $commands[] = new WhereamiCommand($shell->config->colorMode());

        $shell->addCommands($commands);
        //todo
//        $shell->setIncludes($this->argument('include'));

        $path = $shell->server->path('vendor/composer/autoload_classmap.php');

        $shell->loader = ClassAliasAutoloader::register($shell, $path);

        if ($shell->has('whereami')) {
            $shell->addInput('whereami -n3', true);
        }
    }

    /**
     * @param $app \Illuminate\Foundation\Application
     */
    static function withApplication($app)
    {
        $shell = static::$instance;

        $app->instance('tinker', $shell);
    }

    static function debug(array $vars = array(), $boundObject = null)
    {

        $shell = static::$instance;

        $shell->setScopeVariables($vars);


        if ($boundObject !== null) {
            $shell->setBoundObject($boundObject);
        }

        try {
            $shell->run();
        } finally {
            //todo
//            $this->loader->unregister();
        }
        return $shell->getScopeVariables(false);
    }

}


