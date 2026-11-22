<?php

namespace App\Http\Middleware;

use App\Services\LegislatureContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The current legislature lives at the unprefixed URLs, so /legislatura/{its sigla}/… would be a
 * second address for the same page. It is sent permanently to the canonical one, query included.
 */
class RedirectCurrentLegislature
{
    public function __construct(private LegislatureContext $legislatureContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route->parameter('legislature')->is($this->legislatureContext->current())) {
            return $next($request);
        }

        $parameters = [...$request->query(), ...$route->originalParameters()];
        unset($parameters['legislature']);

        $url = route(
            str($route->getName())->after('legislatures.')->toString(),
            $parameters,
        );

        return redirect($url, Response::HTTP_MOVED_PERMANENTLY);
    }
}
