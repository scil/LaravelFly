<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/9/9
 * Time: 1:33
 *
 * only for compiling all routes made before any request
 */

namespace LaravelFly\Greedy\Routing;


class Route extends \Illuminate\Routing\Route
{

    /**
     * Override
     */
    public function __construct($methods, $uri, $action)
    {
        parent::__construct($methods, $uri, $action);
        $this->compileRoute();
    }

    /**
     * Override
     */
    public function matches(\Illuminate\Http\Request $request, $includingMethod = true)
    {
        // laravelfly
        // $this->compileRoute();

        foreach ($this->getValidators() as $validator) {
            if (!$includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (!$validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }


}