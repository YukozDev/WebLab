<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page d'accueil de l'utilisateur authentifie.
 *
 * Elle ne presente que les acces correspondant aux roles de l'utilisateur.
 * Ce filtrage est purement ergonomique : la protection reelle est assuree par
 * le middleware CheckRole sur chaque route. Masquer un lien n'est pas une
 * mesure de securite, puisque l'URL reste connue et accessible.
 */
class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord.
     *
     * @param  \Illuminate\Http\Request  $requete  La requete entrante.
     * @return \Illuminate\View\View La vue du tableau de bord.
     */
    public function index(Request $requete): View
    {
        return view('dashboard', [
            'utilisateur' => $requete->user(),
        ]);
    }
}
