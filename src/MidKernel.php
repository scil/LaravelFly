<?php
namespace LaravelFly {

    use Illuminate\Foundation\Http\Kernel as HttpKernel;

    if (LARAVELFLY_MODE === 'Map') {
        class MidKernel extends \LaravelFly\Map\Kernel
        {
        }
    } elseif (LARAVELFLY_MODE === 'Backupk') {
        class MidKernel extends \LaravelFly\Backup\Kernel
        {
        }
    } elseif (LARAVELFLY_MODE === 'FpmLike') {

        class MidKernel extends HttpKernel
        {
        }
    } else {
        class MidKernel extends \LaravelFly\Backup\Kernel
        {
        }
    }

}
