<?php
/**
 * GLOBAL functions and constances
 */

const WORKER_COROUTINE_ID = 1;

// todo
/**
 * use new ones? that's strict. grammar and  processor should be same with ones in connection
 * use old?
 *      maybe
 *      the old ones hold some changed valus( mainly Grammar::$tablePrefix), while new ones not.
 *
 */
const USE_NEWLY_CREATED_G_AND_P = false;

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
    if (defined('LARAVELFLY_COROUTINE') && LARAVELFLY_COROUTINE) {

        function fly($callback, $write = false)
        {
            /**
             * @var \LaravelFly\Map\Application $app
             */
            static $app = null;
            if (null === $app)
                $app = \Illuminate\Container\Container::getInstance();

            $app->fly($callback, $write);
        }
    }

}
if (!function_exists('fly2')) {
    if (defined('LARAVELFLY_COROUTINE') && LARAVELFLY_COROUTINE) {
        function fly2($callback)
        {
            \fly($callback, true);
        }
    }
}
