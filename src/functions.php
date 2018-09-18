<?php
/**
 * GLOBAL functions and constances
 */

const WORKER_COROUTINE_ID = -1;

if (!function_exists('tinker')) {
    /**
     * Command to return the eval-able code to startup PsySH.
     *
     *     eval(\LaravelFly\tinker());
     *
     * @return string
     */
    function tinker()
    {
        return 'extract(\LaravelFly\Tinker\Shell::debug(get_defined_vars(), $this ?? null));';
    }
}


if (!function_exists('fly')) {
    function fly($callback,$write=false)
    {
        /**
         * @var \LaravelFly\Map\Application $app
         */
        static $app = null;
        if (null === $app)
            $app = \Illuminate\Container\Container::getInstance();

        $app->fly($callback,$write);
    }

}
if (!function_exists('fly2')) {
   function fly2($callback){
       \fly($callback,true);
   }
}
