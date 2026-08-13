@extends('layouts.app')

@section('titre', 'Journal des connexions')

@section('contenu')
    <h1>Journal des événements</h1>
    <p class="sous-titre">Consulter l'historique des événements de sécurité.</p>

    <div class="carte" style="padding:0;overflow:hidden">
        <table>
            <thead>
            <tr>
                <th>Date et heure</th>
                <th>Événement</th>
                <th>Utilisateur (tenté)</th>
                <th>Détails</th>
                <th>Adresse IP</th>
                <th>Navigateur</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td style="white-space:nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @php
                            $classe = match ($log->event) {
                                \App\Models\AuthLog::CONNEXION_REUSSIE => 'etiquette-actif',
                                \App\Models\AuthLog::CONNEXION_ECHOUEE, \App\Models\AuthLog::ACCES_REFUSE, \App\Models\AuthLog::COMPTE_BLOQUE => 'etiquette-bloque',
                                default => 'etiquette',
                            };
                        @endphp
                        <span class="etiquette {{ $classe }}">{{ $log->libelleEvenement() }}</span>
                    </td>
                    <td>
                        @if ($log->user)
                            <strong>{{ $log->user->username }}</strong>
                        @else
                            <span style="color:var(--gris-texte)">{{ $log->username_attempted ?? '-' }}</span>
                        @endif
                    </td>
                    <td>{{ $log->details ?? '-' }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td><span style="font-size: 11px; color: var(--gris-texte);" title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent, 40) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="table-vide">Aucun événement dans le journal.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    
    @if ($logs->hasPages())
        <div style="margin-top: 16px;">
            {{ $logs->links() }}
        </div>
    @endif
@endsection
