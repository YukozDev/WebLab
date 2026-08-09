<?php

namespace App\Http\Middleware;

use App\Services\PasswordManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contraint un utilisateur a renouveler son mot de passe avant toute autre action.
 *
 * Deux situations declenchent la contrainte :
 *  - must_change_password est actif : compte cree par l'administrateur ou mot
 *    de passe reinitialise a la suite d'un oubli ou d'un blocage ;
 *  - le mot de passe a depasse sa duree de validite (changement periodique).
 *
 * Tant que la contrainte n'est pas levee, toutes les routes protegees
 * redirigent vers le formulaire de changement de mot de passe. La verification
 * est faite cote serveur a chaque requete, et non une seule fois a la
 * connexion, pour qu'une expiration survenant en cours de session soit prise
 * en compte.
 */
class EnsurePasswordIsCurrent
{
    /**
     * @param  \App\Services\PasswordManager  $gestionnaire  Service de cycle de vie du mot de passe.
     */
    public function __construct(private readonly PasswordManager $gestionnaire)
    {
    }

    /**
     * Redirige vers le changement de mot de passe si celui-ci est requis.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete entrante.
     * @param  \Closure  $suivant  Le maillon suivant de la chaine de middlewares.
     * @return \Symfony\Component\HttpFoundation\Response La reponse HTTP.
     */
    public function handle(Request $requete, Closure $suivant): Response
    {
        $utilisateur = $requete->user();

        if ($utilisateur === null) {
            return $suivant($requete);
        }

        // Sans cette exception, la redirection pointerait vers une route
        // elle-meme protegee par ce middleware : boucle infinie.
        if ($requete->routeIs('password.*') || $requete->routeIs('logout')) {
            return $suivant($requete);
        }

        if ($utilisateur->must_change_password) {
            return redirect()
                ->route('password.edit')
                ->with('statut', 'Vous devez definir un nouveau mot de passe avant de continuer.');
        }

        if ($this->gestionnaire->estExpire($utilisateur)) {
            return redirect()
                ->route('password.edit')
                ->with('statut', 'Votre mot de passe a expire. Vous devez en choisir un nouveau.');
        }

        return $suivant($requete);
    }
}
