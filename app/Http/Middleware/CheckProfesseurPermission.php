<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Professeur;

class CheckProfesseurPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Le type de permission à vérifier
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Vérifier que l'utilisateur est connecté
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté.');
        }

        $user = auth()->user();

        // Vérifier que l'utilisateur est un professeur
        if (!$user->isProfesseur()) {
            abort(403, 'Accès refusé. Vous devez être professeur.');
        }

        $professeur = $user->professeur;

        // Vérifier la permission spécifique
        switch ($permission) {
            case 'edit_video':
                if (!$professeur->canEditVideo()) {
                    abort(403, 'Vous n\'avez pas la permission de modifier des vidéos.');
                }
                break;

            case 'broadcast_video':
                if (!$professeur->canBroadcastVideo()) {
                    abort(403, 'Vous n\'avez pas la permission de diffuser des vidéos.');
                }
                break;

            case 'delete_video':
                if (!$professeur->canDeleteVideo()) {
                    abort(403, 'Vous n\'avez pas la permission de supprimer des vidéos.');
                }
                break;

            case 'administer':
                if (!$professeur->canAdminister()) {
                    abort(403, 'Vous n\'avez pas la permission d\'administrer le site.');
                }
                break;

            case 'admin':
                if (!$professeur->isAdmin()) {
                    abort(403, 'Accès réservé aux administrateurs.');
                }
                break;

            default:
                abort(500, 'Permission non reconnue : ' . $permission);
        }

        return $next($request);
    }
}
