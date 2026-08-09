{{--
    Liste des utilisateurs et de leurs roles (reservee a l'administrateur).

    Le sel de chaque utilisateur est affiche ici : l'enonce demande qu'il soit
    « visible lors de la demo ». Un sel n'est pas un secret, il n'a pas besoin
    d'etre protege : son role est d'etre unique, pour que deux comptes ayant le
    meme mot de passe produisent des empreintes differentes et qu'une table
    arc-en-ciel precalculee soit inutilisable.
--}}
@extends('layouts.app')

@section('titre', 'Utilisateurs')

@section('contenu')
    <h1>Gestion des utilisateurs</h1>
    <p class="sous-titre">Créer des comptes et leur attribuer un rôle.</p>

    @if (session('motDePasseTemporaire'))
        <div class="alerte alerte-attention">
            <strong>Compte « {{ session('utilisateurCree') }} » créé.</strong>
            <p style="margin:8px 0">
                Mot de passe temporaire — transmettez-le à l'utilisateur par un canal sûr.
                Il ne sera plus affiché après avoir quitté cette page, et l'utilisateur
                devra le remplacer dès sa première connexion.
            </p>
            <code class="secret">{{ session('motDePasseTemporaire') }}</code>
        </div>
    @endif

    <p><a class="bouton" href="{{ route('admin.utilisateurs.create') }}">+ Créer un utilisateur</a></p>

    <div class="carte" style="padding:0;overflow:hidden">
        <table>
            <thead>
            <tr>
                <th>Identifiant</th>
                <th>Nom complet</th>
                <th>Rôle</th>
                <th>État</th>
                <th>Sel</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($utilisateurs as $utilisateur)
                <tr>
                    <td><strong>{{ $utilisateur->username }}</strong></td>
                    <td>{{ $utilisateur->full_name }}</td>
                    <td>
                        @forelse ($utilisateur->roles as $role)
                            @php
                                $classe = match ($role->name) {
                                    \App\Models\Role::ADMINISTRATEUR => 'etiquette-admin',
                                    \App\Models\Role::PREPOSE_RESIDENTIEL => 'etiquette-residentiel',
                                    \App\Models\Role::PREPOSE_AFFAIRE => 'etiquette-affaire',
                                    default => '',
                                };
                            @endphp
                            <span class="etiquette {{ $classe }}">{{ $role->label }}</span>
                        @empty
                            <span class="etiquette">Aucun rôle</span>
                        @endforelse
                    </td>
                    <td>
                        @if ($utilisateur->is_locked)
                            <span class="etiquette etiquette-bloque">Bloqué</span>
                        @elseif ($utilisateur->must_change_password)
                            <span class="etiquette">Doit changer son mot de passe</span>
                        @else
                            <span class="etiquette etiquette-actif">Actif</span>
                        @endif
                    </td>
                    <td>
                        <code style="font-size:11px;color:#6b7280;word-break:break-all">{{ $utilisateur->salt }}</code>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
