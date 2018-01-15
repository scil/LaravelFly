<?php

namespace LaravelFly\Coroutine\Illuminate;

class Route extends \Illuminate\Routing\Route
{

    function __clone()
    {
        // TODO: Implement __clone() method.
    }

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