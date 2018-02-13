<?php

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
        if (defined('LARAVELFLY_MODE'))
            return 'extract(\LaravelFly\Tinker\Shell::debug(get_defined_vars(), isset($this) ? $this : null));';
    }
}
