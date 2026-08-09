{{--
    Creation d'un utilisateur et attribution de son role.

    L'administrateur ne saisit pas de mot de passe : le systeme en genere un
    temporaire, affiche une seule fois apres la creation. L'administrateur doit
    en revanche ressaisir le sien (reauthentification pour fonction critique).
--}}
@extends('layouts.app')

@section('titre', 'Créer un utilisateur')

@section('contenu')
    <h1>Créer un utilisateur</h1>
    <p class="sous-titre">Un mot de passe temporaire sera généré automatiquement.</p>

    @if ($errors->any())
        <div class="alerte alerte-erreur">
            <strong>Le compte n'a pas été créé :</strong>
            <ul>
                @foreach ($errors->all() as $erreur)
                    <li>{{ $erreur }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="carte" style="max-width:620px">
        <form method="POST" action="{{ route('admin.utilisateurs.store') }}">
            @csrf

            <div class="grille-2">
                <div class="champ">
                    <label for="username">Identifiant</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                           maxlength="50" required autofocus>
                    <div class="aide">Lettres, chiffres, tiret et souligné.</div>
                </div>

                <div class="champ">
                    <label for="full_name">Nom complet</label>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}"
                           maxlength="100" required>
                </div>
            </div>

            <div class="champ">
                <label for="email">Courriel (facultatif)</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="150">
            </div>

            <div class="champ">
                <label for="role">Rôle</label>
                <select id="role" name="role" required>
                    <option value="">— Choisir un rôle —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                            {{ $role->label }}
                        </option>
                    @endforeach
                </select>
                <div class="aide">Détermine les pages auxquelles ce compte aura accès.</div>
            </div>

            <hr style="border:none;border-top:1px solid #e8eaf0;margin:22px 0">

            <div class="champ">
                <label for="admin_password">Confirmez votre mot de passe</label>
                <input type="password" id="admin_password" name="admin_password"
                       autocomplete="current-password" required>
                <div class="aide">
                    La création d'un compte est une fonction critique : votre identité
                    doit être confirmée à nouveau.
                </div>
            </div>

            <button type="submit">Créer le compte</button>
            <a class="bouton bouton-secondaire" href="{{ route('admin.utilisateurs.index') }}">Annuler</a>
        </form>
    </div>
@endsection
