{{--
    Page affichee lorsqu'un utilisateur authentifie demande une ressource que
    son role ne lui permet pas de consulter (abort(403) dans CheckRole).

    Le message reste generique et ne decrit ni la ressource demandee, ni les
    roles qui y auraient acces : ce serait renseigner un attaquant sur la
    structure des privileges de l'application. Le detail complet, lui, est
    consigne dans le journal de securite cote serveur.
--}}
@extends('layouts.app')

@section('titre', 'Accès refusé')

@section('contenu')
    <h1>Accès refusé</h1>
    <p class="sous-titre">Votre rôle ne vous donne pas accès à cette page.</p>

    <div class="carte" style="max-width:560px">
        <p style="margin-top:0">
            Cette tentative a été enregistrée dans le journal de sécurité.
            Si vous pensez qu'il s'agit d'une erreur, communiquez avec un administrateur.
        </p>
        <a class="bouton" href="{{ route('dashboard') }}">Retour à l'accueil</a>
    </div>
@endsection
