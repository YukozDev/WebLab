{{--
    Liste des clients, residentiels ou d'affaire selon la route appelee.

    Toutes les valeurs sont affichees avec la syntaxe {{ }} de Blade, qui
    applique htmlspecialchars() : une donnee client contenant du HTML ou du
    JavaScript est rendue comme du texte et non executee (protection XSS).
    La syntaxe {!! !!}, qui ne protege pas, n'est utilisee nulle part.
--}}
@extends('layouts.app')

@section('titre', $titre)

@section('contenu')
    <h1>{{ $titre }}</h1>
    <p class="sous-titre">
        {{ $clients->count() }} client{{ $clients->count() > 1 ? 's' : '' }} dans cette catégorie.
    </p>

    <div class="carte" style="padding:0;overflow:hidden">
        <table>
            <thead>
            <tr>
                @if ($type === \App\Models\Client::TYPE_AFFAIRE)
                    <th>Entreprise</th>
                @endif
                <th>Nom</th>
                <th>Prénom</th>
                <th>Courriel</th>
                <th>Téléphone</th>
                <th>Ville</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($clients as $client)
                <tr>
                    @if ($type === \App\Models\Client::TYPE_AFFAIRE)
                        <td><strong>{{ $client->company_name }}</strong></td>
                    @endif
                    <td>{{ $client->last_name }}</td>
                    <td>{{ $client->first_name }}</td>
                    <td>{{ $client->email ?? '—' }}</td>
                    <td>{{ $client->phone ?? '—' }}</td>
                    <td>{{ $client->city ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="table-vide">Aucun client à afficher.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
