<?php

namespace App\Http\Middleware;

use Closure;
use Session;
class lang
{

    public function handle($request, Closure $next)
    {
         if (Session::get('lang') == "") {
                Session::put('lang', "en");
                //  dd("Catch errors for script and full tracking ( 1 )");
            }
      //dd(Session::get('lang'));
        return $next($request);
    }
}
