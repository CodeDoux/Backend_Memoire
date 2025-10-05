<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,...$roles): Response
    {
        //Vérifier d'abord si l'utilisateur est authentifié
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        //Maintenant on peut accéder au rôle en toute sécurité
        if (!in_array($user->role, $roles)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return $next($request);
    }

}
