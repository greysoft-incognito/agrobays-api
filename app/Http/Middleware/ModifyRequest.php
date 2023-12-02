<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ModifyRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $action, ...$params)
    {
        // Modify the request here
        if (method_exists($this, $action)) {
            /** @var \Illuminate\Http\Request  $request */
            $request = $this->$action($request, ...$params);
        }

        return $next($request);
    }

    public function replace(Request $request, $key, $with_key, $condition = null): Request
    {
        $check = false;
        if ($condition) {
            $parts = preg_split('/(==|!=|>|<)/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);

            if (isset($parts[1]) && in_array($parts[1], ['==','!=','<>','<','>'])) {
                eval("
                    \$type = \$request->route()->parameters['verify'] ?? 'initial';
                    \$check = \$type $parts[1] '$parts[2]';
                ");
            }
        }

        if ($check) {
            $request->merge(['remove_input' => $key, $with_key => $request->$key]);
            $request->request->remove($key);
            $request->offsetUnset($key);
            return Request::createFrom($request);
        }

        return $request;
    }
}
