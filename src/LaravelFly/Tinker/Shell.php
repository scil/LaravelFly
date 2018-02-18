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
use Symfony\Component\Console\Input\InputArgument;

class Shell extends \Psy\Shell
{
    /**
     * @var Configuration
     */
    protected static $config;

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

    /**
     * @var ClassAliasAutoloader
     */
    protected $loader;


    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);
    }

    /**
     * @param $server \LaravelFly\Server\ServerInterface
     */
    static function make($server)
    {
        static::$config = $config = new Configuration([
            'updateCheck' => 'never',
            'eraseDuplicates' => true,
        ]);

        $tk = new TinkerCommand();

        $config->getPresenter()->addCasters(
            $tk->getCasters()
        );

        static::$instance = $shell = new Shell($config);

        $config->setShell($shell);

        \Psy\info($config);

        $shell->server = $server;

        $commands = $shell->getDefaultCommands();
        // this will overwrite Psy's WhereamiCommand
        $commands[] = new WhereamiCommand($config->colorMode());

        $shell->addCommands($commands);
        //todo
//        $shell->setIncludes($this->argument('include'));

        $path = $shell->server->path('vendor/composer/autoload_classmap.php');

        $shell->loader = ClassAliasAutoloader::register($shell, $path);

        $shell->addInput('whereami -n3', true);
    }

    static function addAlias($array)
    {
        $shell = static::$instance;
        $shell->loader->addClasses($array);
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


