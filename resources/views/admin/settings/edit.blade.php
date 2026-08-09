{{--
    Configuration des parametres de securite (reservee a l'administrateur).

    Regroupe tous les parametres marques « Oui » dans la colonne Parametres du
    tableau de l'enonce. Les bornes indiquees sous chaque champ correspondent
    aux regles de validation appliquees cote serveur : les attributs min et max
    du HTML ne sont qu'un confort, ils sont contournables et ne protegent rien.
--}}
@extends('layouts.app')

@section('titre', 'Paramètres de sécurité')

@section('contenu')
    <h1>Paramètres de sécurité</h1>
    <p class="sous-titre">
        Ces valeurs s'appliquent immédiatement à l'ensemble des comptes.
    </p>

    @if ($errors->any())
        <div class="alerte alerte-erreur">
            <strong>Les paramètres n'ont pas été enregistrés :</strong>
            <ul>
                @foreach ($errors->all() as $erreur)
                    <li>{{ $erreur }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.parametres.update') }}">
        @csrf
        @method('PUT')

        <div class="carte">
            <h2 style="margin-top:0">Protection contre la force brute</h2>
            <div class="grille-2">
                <div class="champ">
                    <label for="max_login_attempts">Nombre maximal de tentatives</label>
                    <input type="number" id="max_login_attempts" name="max_login_attempts"
                           value="{{ old('max_login_attempts', $parametres->max_login_attempts) }}"
                           min="1" max="20" required>
                    <div class="aide">
                        Au-delà, le compte est bloqué et seul un administrateur peut le réactiver. (1 à 20)
                    </div>
                </div>

                <div class="champ">
                    <label for="failed_attempt_delay_seconds">Délai après un échec (secondes)</label>
                    <input type="number" id="failed_attempt_delay_seconds" name="failed_attempt_delay_seconds"
                           value="{{ old('failed_attempt_delay_seconds', $parametres->failed_attempt_delay_seconds) }}"
                           min="0" max="600" required>
                    <div class="aide">
                        Temps d'attente imposé avant la tentative suivante. (0 à 600)
                    </div>
                </div>
            </div>
        </div>

        <div class="carte">
            <h2 style="margin-top:0">Norme des mots de passe</h2>
            <div class="grille-2">
                <div class="champ">
                    <label for="password_min_length">Longueur minimale</label>
                    <input type="number" id="password_min_length" name="password_min_length"
                           value="{{ old('password_min_length', $parametres->password_min_length) }}"
                           min="8" max="64" required>
                    <div class="aide">Minimum imposé par l'application : 8 caractères. (8 à 64)</div>
                </div>

                <div class="champ">
                    <label>Classes de caractères exigées</label>
                    <div class="case">
                        <input type="checkbox" id="require_lowercase" name="require_lowercase" value="1"
                               @checked(old('require_lowercase', $parametres->require_lowercase))>
                        <label for="require_lowercase">Au moins une minuscule</label>
                    </div>
                    <div class="case">
                        <input type="checkbox" id="require_uppercase" name="require_uppercase" value="1"
                               @checked(old('require_uppercase', $parametres->require_uppercase))>
                        <label for="require_uppercase">Au moins une majuscule</label>
                    </div>
                    <div class="case">
                        <input type="checkbox" id="require_digit" name="require_digit" value="1"
                               @checked(old('require_digit', $parametres->require_digit))>
                        <label for="require_digit">Au moins un chiffre</label>
                    </div>
                    <div class="case">
                        <input type="checkbox" id="require_special" name="require_special" value="1"
                               @checked(old('require_special', $parametres->require_special))>
                        <label for="require_special">Au moins un caractère spécial</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="carte">
            <h2 style="margin-top:0">Cycle de vie du mot de passe</h2>
            <div class="grille-2">
                <div class="champ">
                    <label for="password_history_count">Anciens mots de passe interdits</label>
                    <input type="number" id="password_history_count" name="password_history_count"
                           value="{{ old('password_history_count', $parametres->password_history_count) }}"
                           min="0" max="24" required>
                    <div class="aide">
                        Les X derniers mots de passe ne peuvent pas être réutilisés. 0 désactive le contrôle. (0 à 24)
                    </div>
                </div>

                <div class="champ">
                    <label for="password_expiry_days">Durée de validité (jours)</label>
                    <input type="number" id="password_expiry_days" name="password_expiry_days"
                           value="{{ old('password_expiry_days', $parametres->password_expiry_days) }}"
                           min="0" max="730" required>
                    <div class="aide">
                        Au-delà, le changement est imposé à la connexion. 0 désactive l'expiration. (0 à 730)
                    </div>
                </div>
            </div>
        </div>

        <div class="carte">
            <h2 style="margin-top:0">Hachage et sessions</h2>
            <div class="grille-2">
                <div class="champ">
                    <label for="hash_iterations">Itérations PBKDF2</label>
                    <input type="number" id="hash_iterations" name="hash_iterations"
                           value="{{ old('hash_iterations', $parametres->hash_iterations) }}"
                           min="10000" max="1000000" required>
                    <div class="aide">
                        Coût du hachage des nouveaux mots de passe. L'OWASP recommande
                        au moins 210 000 pour PBKDF2-SHA256. (10 000 à 1 000 000)
                    </div>
                </div>

                <div class="champ">
                    <label for="session_idle_timeout_minutes">Expiration de session par inactivité (minutes)</label>
                    <input type="number" id="session_idle_timeout_minutes" name="session_idle_timeout_minutes"
                           value="{{ old('session_idle_timeout_minutes', $parametres->session_idle_timeout_minutes) }}"
                           min="1" max="480" required>
                    <div class="aide">(1 à 480)</div>
                </div>
            </div>
        </div>

        <button type="submit">Enregistrer les paramètres</button>
    </form>
@endsection
