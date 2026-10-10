# Contrat : événements temps réel

Complète [le contrat de la 001](../../001-reservation-machines/contracts/broadcast-events.md). Même transport (Soketi, Laravel Echo) et même principe : payload minimal, le composant recharge lui-même le détail.

## Canaux

| Canal | Type | Autorisation |
|-------|------|--------------|
| `fleet` (001) | privé | permission `reservations.manage` |
| `reservation.{id}` (nouveau) | privé | permission `reservations.manage` |

## Événements

| Nom (`broadcastAs`) | Canal | Déclencheur | Payload |
|---------------------|-------|-------------|---------|
| `photo.changed` | `reservation.{id}` | photo reçue ou supprimée | `{ reservation_id, step, reservation_view_id, missing_views_count }` |
| `photo-session.changed` | `reservation.{id}` | QR code généré ou révoqué | `{ reservation_id, step, is_active }` |
| `damage.changed` | `fleet` | dégât signalé ou traité | `{ reservation_id, unresolved_count }` |

Le panneau « Photos » du poste écoute `reservation.{id}` ; l'écran « Dégâts à traiter » écoute `damage.changed` sur `fleet`.
