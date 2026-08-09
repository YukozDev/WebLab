<?php

namespace App\Http\Middleware;

use App\Models\AuthLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Point de controle unique du modele RBAC.
 *
 * S'applique aux routes sous la forme role:role1,role2 et laisse passer la
 * requete si l'utilisateur authentifie possede au moins un des roles listes.
 *
 * Placer l'autorisation dans un middleware de route plutot que dans les vues
 * ou les controleurs presente deux avantages :
 *  - la regle est declarative et se lit d'un coup d'oeil dans routes/web.php,
 *    ce qui rend une omission facilement reperable ;
 *  - masquer un lien dans le menu ne protege rien, puisque l'URL reste
 *    accessible directement ; ici la verification est cote serveur, avant
 *    l'execution du controleur.
 */
class CheckRole
{
    /**
     * Verifie que l'utilisateur possede un des roles requis par la route.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete entrante.
     * @param  \Closure  $suivant  Le maillon suivant de la chaine de middlewares.
     * @param  string  ...$rolesRequis  Les noms techniques des roles autorises.
     * @return \Symfony\Component\HttpFoundation\Response La reponse HTTP.
     */
    public function handle(Request $requete, Closure $suivant, string ...$rolesRequis): Response
    {
        $utilisateur = $requete->user();

        // Le middleware auth s'execute avant celui-ci, mais on ne suppose pas
        // qu'il a bien ete applique : une route mal configuree ne doit pas
        // provoquer un acces anonyme.
        if ($utilisateur === null) {
            return redirect()->route('login');
        }

        if (! $utilisateur->hasAnyRole($rolesRequis)) {
            // Une tentative d'acces a une ressource interdite par un compte
            // authentifie est un evenement de securite : on la journalise.
            AuthLog::enregistrer(
                AuthLog::ACCES_REFUSE,
                $utilisateur,
                null,
                'Ressource demandee : /' . $requete->path()
            );

            abort(403, "Votre role ne vous donne pas acces a cette page.");
        }

        return $suivant($requete);
    }
}
