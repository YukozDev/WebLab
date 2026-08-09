{{--
    Changement de mot de passe par l'utilisateur.

    Le formulaire demande le mot de passe actuel : c'est la reauthentification
    exigee pour les fonctions critiques. Les exigences de complexite affichees
    proviennent de la table security_settings et suivent donc en temps reel la
    configuration decidee par l'administrateur.
--}}
@extends('layouts.app')

@section('titre', 'Changer le mot de passe')

@section('contenu')
    <h1>Changer mon mot de passe</h1>
    <p class="sous-titre">
        Votre mot de passe actuel vous est demandé pour confirmer votre identité.
    </p>

    @if ($errors->any())
        <div class="alerte alerte-erreur">
            <strong>Le changement n'a pas été effectué :</strong>
            <ul>
                @foreach ($errors->all() as $erreur)
                    <li>{{ $erreur }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="carte" style="max-width:560px">
        <div class="alerte alerte-info">
            <strong>Exigences en vigueur :</strong>
            <ul>
                @foreach ($exigences as $exigence)
                    <li>{{ $exigence }}</li>
                @endforeach
            </ul>
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="champ">
                <label for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password"
                       autocomplete="current-password" required autofocus>
            </div>

            <hr style="border:none;border-top:1px solid #e8eaf0;margin:22px 0">

            <div class="champ">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" required>
            </div>

            <div class="champ">
                <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       autocomplete="new-password" required>
            </div>

            <button type="submit">Enregistrer le nouveau mot de passe</button>
        </form>
    </div>
@endsection
