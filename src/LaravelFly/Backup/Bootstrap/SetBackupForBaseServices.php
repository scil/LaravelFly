<?php

namespace LaravelFly\Backup\Bootstrap;

use LaravelFly\Backup\Application;

class SetBackupForBaseServices
{

    public function bootstrap(Application $app)
    {

        $needBackup = [];

        foreach ($this->getConfig() as $name => $config) {
            if ($config) {
                $needBackup[$name] = $config;
            }
        }

        $app->addNeedBackupServiceAttributes($needBackup);


    }

    /**
     * Which properties of base services need to backup. Only for Mode Backup
     *
     * See: Illuminate\Foundation\Application::registerBaseServiceProviders
     */
    function getConfig(){

        return [

            \Illuminate\Contracts\Http\Kernel::class => LARAVELFLY_SERVICES['kernel'] ? [] : [

                'middleware',

                /** depends
                 * put new not safe properties here
                 */
                // 'newProp1', 'newProp2',

            ],
            /* Illuminate\Events\EventServiceProvider::class : */
            'events' => [
                'listeners', 'wildcards', 'wildcardsCache', 'queueResolver',
            ],

            /* Illuminate\Routing\RoutingServiceProvider::class : */
            'router' => [
                /** depends
                 * Uncomment them if it's not same on each request. They may be changed by Route::middleware
                 */
                // 'middleware','middlewareGroups','middlewarePriority',

                /** depends */
                // 'binders',

                /** depends */
                // 'patterns',


                /** not necessary to backup,
                 * // 'groupStack',
                 */

                /** not necessary to backup,
                 * it will be changed during next request
                 * // 'current',
                 */

                /** not necessary to backup,
                 * the ref to app('request') will be released during next request
                 * //'currentRequest',
                 */

                /* Illuminate\Routing\RouteCollection */
                'obj.routes' => LARAVELFLY_SERVICES['routes'] ? [] : [
                    'routes', 'allRoutes', 'nameList', 'actionList',
                ],
            ], /* end 'router' */

            'url' => [
                /* depends */
                // 'forcedRoot', 'forceScheme',
                // 'rootNamespace',
                // 'sessionResolver','keyResolver',
                // 'formatHostUsing','formatPathUsing',

                /** not necessary to backup,
                 *
                 * the ref to app('request') will be released during next request;
                 * and no need set request for `url' on every request , because there is a $app->rebinding for request:
                 *      $app->rebinding( 'request', $this->requestRebinder() )
                 *
                 * // 'request',
                 *
                 * auto reset when request is updated ( setRequest )
                 * // 'routeGenerator','cachedRoot', 'cachedSchema',
                 *
                 * same as 'request'
                 * // 'routes'
                 */
            ],


            /** nothing need to backup
             *
             * // 'redirect' => false,
             * // 'routes' => false,
             * // 'log' => false,
             */
        ];
    }
}