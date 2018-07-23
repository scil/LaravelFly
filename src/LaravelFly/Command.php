<?php

namespace LaravelFly;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Command extends \Illuminate\Console\Command
{

    protected $signature = '
    {action? : start|stop|reload|restart}
    {--c|conf : server conf file, default is <laravel_app_root>/fly.conf.php}
    {--h|help}
    ';

    protected $root;


    function exe()
    {
        $this->run(new \Symfony\Component\Console\Input\ArgvInput(), new \Symfony\Component\Console\Output\ConsoleOutput());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($this->option('help') || !$this->argument('action')) {
            $this->info($this->signature);
            return;
        }

        $this->root = $root = realpath(__DIR__ . '/../../../../../');

        $options = $this->getServerOptions();

        if (!isset($options['pid_file'])) {
            $pid_file = $root . '/bootstrap/laravel-fly-' . $options['listen_port'] . '.pid';
        } else {
            $pid_file = $options['pid_file'] . '-' . $options['listen_port'];
        }

        $pid = 0;
        if (is_file($pid_file)) {
            try {
                $pid = (int)file_get_contents($pid_file);
            } catch (Throwable $e) {
                print("pid can not be read from $pid_file \n");
            }
        }

        $action = $this->argument('action');

        if (!$pid && $action !== 'stop') {
            $this->startServer($options);
        }

        switch ($action) {
            case 'stop':
                posix_kill($pid, SIGTERM);
                break;
            case 'reload':
                posix_kill($pid, SIGUSR1);
                break;
            case 'restart':
                posix_kill($pid, SIGTERM);
                $this->startServer($options);
                break;
            default:
                $this->startServer($options);

        }

    }

    protected function getServerOptions()
    {
        $config_file = $this->getConfigFile();
        try {
            $options = require $config_file;
            $options['conf'] = $config_file;
        } catch (\Exception $e) {
            $this->error("config file not be loaded: $config_file");
        }
        $this->info("[INFO] conf: $config_file");

        return $options;

    }

    protected function getConfigFile()
    {
        if ($this->option('conf') && is_file($this->option('conf'))) {
            return realpath($this->option('conf'));
        }

        $config_file = $this->root . '/fly.conf.php';

        if (!is_file($config_file))
            $config_file = dirname(dirname(__DIR__)) . '/config/laravelfly-server-config.example.php';

        return $config_file;

    }

    function startServer($options)
    {

        // how to make a global function here?
// prevent errors if eval(tinker()); left in project code
//        if (empty($options['tinker']) && !function_exists('tinker')) {
//            function tinker()
//            {
//            }
//        }

        \LaravelFly\Fly::getServer($options)->start();
    }


}