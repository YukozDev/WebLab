{{--
    Gabarit principal de l'application.

    Aucune ressource externe (CDN, police distante, script tiers) n'est chargee :
    tout le style est servi par l'application elle-meme. Cela supprime le risque
    de compromission par un tiers et garantit que la demonstration fonctionne
    sans acces Internet.

    Le menu n'affiche que les liens correspondant aux roles de l'utilisateur.
    Ce filtrage est ergonomique, pas securitaire : la protection reelle est
    assuree par le middleware CheckRole sur chaque route.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Empeche le navigateur d'interpreter un fichier avec un type MIME devine. --}}
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    {{-- Ne transmet pas l'URL courante aux sites externes. --}}
    <meta name="referrer" content="same-origin">
    <title>@yield('titre', 'Accueil') — GTI619 Laboratoire 5</title>
    <style>
        :root {
            --bleu: #1d4ed8;
            --bleu-clair: #eff6ff;
            --gris-bord: #d4d8e0;
            --gris-texte: #4b5563;
            --rouge: #b91c1c;
            --rouge-clair: #fef2f2;
            --vert: #15803d;
            --vert-clair: #f0fdf4;
            --ambre: #b45309;
            --ambre-clair: #fffbeb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: #111827;
            background: #f7f8fa;
        }

        header.barre {
            background: #111827;
            color: #fff;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        header.barre .marque {
            font-weight: 700;
            letter-spacing: .04em;
            padding: 14px 0;
        }

        nav.menu { display: flex; gap: 4px; flex-wrap: wrap; }

        nav.menu a {
            color: #d1d5db;
            text-decoration: none;
            padding: 14px 14px;
            border-bottom: 3px solid transparent;
        }

        nav.menu a:hover { color: #fff; background: #1f2937; }
        nav.menu a.actif { color: #fff; border-bottom-color: var(--bleu); }

        .identite {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #9ca3af;
            padding: 8px 0;
        }

        .identite strong { color: #fff; font-weight: 600; }

        main { max-width: 1080px; margin: 28px auto; padding: 0 24px; }

        h1 { font-size: 24px; margin: 0 0 4px; }
        h2 { font-size: 18px; margin: 28px 0 12px; }
        .sous-titre { color: var(--gris-texte); margin: 0 0 22px; }

        .carte {
            background: #fff;
            border: 1px solid var(--gris-bord);
            border-radius: 8px;
            padding: 22px;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e8eaf0; }
        th { background: #f3f4f6; font-size: 13px; text-transform: uppercase; letter-spacing: .03em; color: var(--gris-texte); }
        tbody tr:last-child td { border-bottom: none; }
        .table-vide { padding: 24px; text-align: center; color: var(--gris-texte); }

        label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }

        input[type=text], input[type=password], input[type=email], input[type=number], select {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid var(--gris-bord);
            border-radius: 6px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
        }

        input:focus, select:focus { outline: 2px solid var(--bleu); outline-offset: -1px; border-color: var(--bleu); }

        .champ { margin-bottom: 16px; }
        .aide { font-size: 13px; color: var(--gris-texte); margin-top: 4px; }
        .grille-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0 20px; }

        .case { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .case input { width: 16px; height: 16px; }
        .case label { margin: 0; font-weight: 400; }

        button, .bouton {
            display: inline-block;
            background: var(--bleu);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            font-size: 15px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        button:hover, .bouton:hover { background: #1e40af; }
        .bouton-secondaire { background: #fff; color: #111827; border: 1px solid var(--gris-bord); }
        .bouton-secondaire:hover { background: #f3f4f6; }

        .alerte { border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; border: 1px solid; }
        .alerte-succes { background: var(--vert-clair); border-color: #bbf7d0; color: var(--vert); }
        .alerte-erreur { background: var(--rouge-clair); border-color: #fecaca; color: var(--rouge); }
        .alerte-info { background: var(--bleu-clair); border-color: #bfdbfe; color: var(--bleu); }
        .alerte-attention { background: var(--ambre-clair); border-color: #fde68a; color: var(--ambre); }
        .alerte ul { margin: 6px 0 0; padding-left: 20px; }

        .erreur-champ { color: var(--rouge); font-size: 13px; margin-top: 4px; }

        .etiquette {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 999px;
            background: #e5e7eb;
            color: #374151;
        }

        .etiquette-admin { background: #ede9fe; color: #6d28d9; }
        .etiquette-residentiel { background: #dbeafe; color: #1d4ed8; }
        .etiquette-affaire { background: #ccfbf1; color: #0f766e; }
        .etiquette-bloque { background: #fee2e2; color: var(--rouge); }
        .etiquette-actif { background: #dcfce7; color: var(--vert); }

        code.secret {
            display: inline-block;
            font-family: Consolas, "Courier New", monospace;
            font-size: 16px;
            background: #111827;
            color: #fbbf24;
            padding: 8px 14px;
            border-radius: 6px;
            letter-spacing: .05em;
            user-select: all;
        }

        .connexion { max-width: 400px; margin: 8vh auto; padding: 0 20px; }
        .connexion .carte { padding: 28px; }
        .connexion h1 { text-align: center; }
        .connexion .sous-titre { text-align: center; }
        .connexion button { width: 100%; }
    </style>
</head>
<body>

@auth
    <header class="barre">
        <span class="marque">GTI619 · LAB 5</span>

        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'actif' : '' }}">Accueil</a>

            @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMINISTRATEUR, \App\Models\Role::PREPOSE_RESIDENTIEL]))
                <a href="{{ route('clients.residentiels') }}" class="{{ request()->routeIs('clients.residentiels') ? 'actif' : '' }}">Clients résidentiels</a>
            @endif

            @if (auth()->user()->hasAnyRole([\App\Models\Role::ADMINISTRATEUR, \App\Models\Role::PREPOSE_AFFAIRE]))
                <a href="{{ route('clients.affaires') }}" class="{{ request()->routeIs('clients.affaires') ? 'actif' : '' }}">Clients d'affaire</a>
            @endif

            @if (auth()->user()->isAdministrateur())
                <a href="{{ route('admin.utilisateurs.index') }}" class="{{ request()->routeIs('admin.utilisateurs.*') ? 'actif' : '' }}">Utilisateurs</a>
                <a href="{{ route('admin.journaux.index') }}" class="{{ request()->routeIs('admin.journaux.*') ? 'actif' : '' }}">Journaux</a>
                <a href="{{ route('admin.parametres.edit') }}" class="{{ request()->routeIs('admin.parametres.*') ? 'actif' : '' }}">Paramètres de sécurité</a>
            @endif
        </nav>

        <div class="identite">
            <span><strong>{{ auth()->user()->username }}</strong> — {{ auth()->user()->libelleRoles() }}</span>
            <a href="{{ route('password.edit') }}" style="color:#9ca3af">Mot de passe</a>
            {{-- La deconnexion est un POST protege par un jeton CSRF : un simple
                 lien GET permettrait a un site tiers de deconnecter l'utilisateur. --}}
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" style="background:#374151;padding:6px 12px;font-size:13px">Déconnexion</button>
            </form>
        </div>
    </header>
@endauth

<main>
    @if (session('statut'))
        <div class="alerte alerte-info">{{ session('statut') }}</div>
    @endif

    @yield('contenu')
</main>

</body>
</html>
