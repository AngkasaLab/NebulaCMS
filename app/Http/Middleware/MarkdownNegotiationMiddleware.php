<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\HtmlToMarkdown;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkdownNegotiationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if Markdown negotiation should be applied
        if ($request->isMethod('GET')
            && ! $request->hasHeader('X-Inertia')
            && ! $request->is('admin*')
            && ! $request->is('api*')
            && $response->getStatusCode() === 200
            && str_contains($request->header('Accept', ''), 'text/markdown')
        ) {
            $contentType = $response->headers->get('Content-Type', '');
            if (str_starts_with($contentType, 'text/html')) {
                $html = $response->getContent();
                if ($html) {
                    $markdown = HtmlToMarkdown::convert($html);
                    $tokenCount = (int) ceil(strlen($markdown) / 4);

                    return response($markdown, 200)
                        ->header('Content-Type', 'text/markdown; charset=UTF-8')
                        ->header('x-markdown-tokens', (string) $tokenCount);
                }
            }
        }

        return $response;
    }
}
