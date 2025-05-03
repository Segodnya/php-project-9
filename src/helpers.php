<?php

if (!function_exists('optional')) {
    /**
     * Provide access to optional object properties.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function optional($value = null, ?callable $callback = null)
    {
        if ($callback === null) {
            return $value !== null ? $value : new class {
                public function __call($method, $parameters)
                {
                    return null;
                }

                public function __get($key)
                {
                    return null;
                }
            };
        }

        return $value !== null ? $callback($value) : null;
    }
}
