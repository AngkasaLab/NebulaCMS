<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddAgentDiscoveryHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->routeIs('home')) {
            $newLink = '</.well-known/api-catalog>; rel="api-catalog", </sitemap.xml>; rel="sitemap"';
            $existingLink = $response->headers->get('Link');

            if ($existingLink) {
                if (!str_contains($existingLink, 'rel="api-catalog"')) {
                    $response->headers->set('Link', $existingLink . ', ' . $newLink);
                }
            } else {
                $response->headers->set('Link', $newLink);
            }
        }

        return $response;
    }
}
