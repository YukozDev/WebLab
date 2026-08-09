{{--
    Formulaire d'authentification.

    Le champ mot de passe est de type password : il n'est ni affiche a l'ecran,
    ni conserve par l'autocompletion du navigateur en clair. En cas d'echec,
    l'identifiant est repropose mais jamais le mot de passe (old() n'est pas
    utilise sur ce champ), afin qu'il ne se retrouve pas dans le HTML renvoye.
--}}
@extends('layouts.app')

@section('titre', 'Connexion')

@section('contenu')
<div class="connexion">
    <h1>Authentification</h1>

    <div class="carte">
        @if ($errors->any())
            <div class="alerte alerte-erreur">
                @foreach ($errors->all() as $erreur)
                    <div>{{ $erreur }}</div>
                @endforeach
            </div>
        @endif

        {{-- @csrf insere un jeton anti-falsification de requete : sans lui, un
             site tiers pourrait soumettre ce formulaire au nom du visiteur. --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="champ">
                <label for="username">Identifiant</label>
                <input type="text"
                       id="username"
                       name="username"
                       value="{{ old('username') }}"
                       autocomplete="username"
                       autofocus
                       required>
            </div>

            <div class="champ">
                <label for="password">Mot de passe</label>
                <input type="password"
                       id="password"
                       name="password"
                       autocomplete="current-password"
                       required>
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </div>
</div>
@endsection
