<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

/**
 * Consultation des listes de clients.
 *
 * Deux actions distinctes plutot qu'une seule action parametree par le type :
 * le type de clientele ne doit jamais provenir de la requete HTTP. S'il etait
 * lu dans l'URL, un prepose aux clients residentiels pourrait consulter la
 * liste des clients d'affaire en modifiant simplement le parametre. Ici, la
 * route porte le type et le middleware role: qui lui est attache, les deux
 * restant obligatoirement coherents.
 */
class ClientController extends Controller
{
    /**
     * Affiche la liste des clients residentiels.
     *
     * Accessible aux roles « Prepose aux clients residentiels » et « Administrateur ».
     *
     * @return \Illuminate\View\View La vue de la liste.
     */
    public function residentiels(): View
    {
        return view('clients.index', [
            'titre' => 'Clients residentiels',
            'type' => Client::TYPE_RESIDENTIEL,
            'clients' => Client::ofType(Client::TYPE_RESIDENTIEL)
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    /**
     * Affiche la liste des clients d'affaire.
     *
     * Accessible aux roles « Prepose aux clients d'affaire » et « Administrateur ».
     *
     * @return \Illuminate\View\View La vue de la liste.
     */
    public function affaires(): View
    {
        return view('clients.index', [
            'titre' => "Clients d'affaire",
            'type' => Client::TYPE_AFFAIRE,
            'clients' => Client::ofType(Client::TYPE_AFFAIRE)
                ->orderBy('company_name')
                ->get(),
        ]);
    }
}
