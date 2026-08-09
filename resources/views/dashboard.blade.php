{{--
    Tableau de bord : point d'entree apres authentification.

    Les cartes affichees dependent des roles de l'utilisateur. Ce filtrage est
    ergonomique ; l'autorisation reelle est appliquee par le middleware
    CheckRole sur chacune des routes ciblees.
--}}
@extends('layouts.app')

@section('titre', 'Accueil')

@section('contenu')
    <h1>Bonjour {{ $utilisateur->full_name }}</h1>
    <p class="sous-titre">
        Connecté en tant que <strong>{{ $utilisateur->username }}</strong> —
        rôle : {{ $utilisateur->libelleRoles() }}
    </p>

    <div class="carte">
        <h2 style="margin-top:0">Vos accès</h2>
        <table>
            <tbody>
            @if ($utilisateur->hasAnyRole([\App\Models\Role::ADMINISTRATEUR, \App\Models\Role::PREPOSE_RESIDENTIEL]))
                <tr>
                    <td><strong>Clients résidentiels</strong><div class="aide">Liste des clients résidentiels</div></td>
                    <td style="width:140px"><a class="bouton" href="{{ route('clients.residentiels') }}">Consulter</a></td>
                </tr>
            @endif

            @if ($utilisateur->hasAnyRole([\App\Models\Role::ADMINISTRATEUR, \App\Models\Role::PREPOSE_AFFAIRE]))
                <tr>
                    <td><strong>Clients d'affaire</strong><div class="aide">Liste des clients d'affaire</div></td>
                    <td><a class="bouton" href="{{ route('clients.affaires') }}">Consulter</a></td>
                </tr>
            @endif

            @if ($utilisateur->isAdministrateur())
                <tr>
                    <td><strong>Gestion des utilisateurs</strong><div class="aide">Créer un compte et lui attribuer un rôle</div></td>
                    <td><a class="bouton" href="{{ route('admin.utilisateurs.index') }}">Ouvrir</a></td>
                </tr>
                <tr>
                    <td><strong>Paramètres de sécurité</strong><div class="aide">Politique de mots de passe, tentatives, sessions</div></td>
                    <td><a class="bouton" href="{{ route('admin.parametres.edit') }}">Configurer</a></td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

    <div class="carte">
        <h2 style="margin-top:0">Votre compte</h2>
        <table>
            <tbody>
            <tr>
                <th style="width:280px">Dernière connexion</th>
                <td>{{ $utilisateur->last_login_at?->format('Y-m-d H:i:s') ?? 'Première connexion' }}</td>
            </tr>
            <tr>
                <th>Dernier changement de mot de passe</th>
                <td>{{ $utilisateur->password_changed_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
            </tr>
            </tbody>
        </table>
    </div>
@endsection
