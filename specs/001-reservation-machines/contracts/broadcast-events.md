# Contrat : événements temps réel

Transport : Soketi (protocole Pusher), consommé par Laravel Echo dans les composants Livewire.

## Canal

| Canal | Type | Autorisation |
|-------|------|--------------|
| `fleet` | privé | tout utilisateur connecté ayant la permission `fleet.view` (donnée au rôle « salarié ») |

## Événements

Payload minimal : identifiants et champs nécessaires au rafraîchissement, jamais le modèle complet. Le composant recharge le détail lui-même.

| Nom (`broadcastAs`) | Déclencheur | Payload |
|---------------------|-------------|---------|
| `reservation.changed` | création, sortie, retour, annulation, changement de conflit | `{ id, machine_id, status, start_date, end_date, conflict_reason }` |
| `machine.changed` | création, changement de statut, de VGP ou d'agence, retrait | `{ id, status, agency_id, vgp_due_date }` |
| `fleet.imported` | fin d'un import du parc | `{ created_count }` |
