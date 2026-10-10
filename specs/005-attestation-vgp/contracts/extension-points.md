# Contrat : points d'extension

## Consommés (existants dans la 001)

| Point d'extension | Layer | Utilisation par `certification` |
|---|---|---|
| `ReservationChanged` (`ShouldDispatchAfterCommit`) | booking | `OpenCertificateOnReservationChanged` (listener en file, après commit) : si la mise en service est atteinte, la réservation confirmée et la machine soumise à VGP, ouvre l'attestation (idempotent) |
| `ReservationTransitionGuards::register()` + `ReservationTransitionGuard` | booking | `CertificateDeliveredGuard::beforeDeparture()` refuse la sortie tant que l'attestation n'est pas livrée ; `beforeReturn()` sans effet |
| `ReservationDetailSections::register()` | booking | section `certification.reservation-section`, position 30 |
| `RefusalException` (abstraite), `DisplaysRefusals`, `AssertsRefusals` | fleet | toutes les exceptions de refus de la feature en héritent (classes `final`, factories nommées) |
| `UpdateMachineVgp` | fleet | report de l'échéance du rapport déposé sur la machine |

## Demandés à la 001 (à livrer avant l'implémentation)

### 1. `UpdateCustomer::changeEmail()` et `CustomerChanged` (booking) — livrés par la 005 (phase 0) — [research.md](../research.md) C9

```text
Functional\Booking\Actions\UpdateCustomer::changeEmail(User $author, Customer $customer, string $email): Customer
  - refuse un e-mail vide ou invalide (RefusalException dédiée, traduite)
  - met à jour customers.email, journal d'activité sur le client
  - émet CustomerChanged après commit
Functional\Booking\Events\CustomerChanged(public readonly Customer $customer)  // ShouldDispatchAfterCommit
```

`certification` écoute `CustomerChanged` (`ResolveCertificatesOnCustomerChanged`) et résout les attestations `awaiting_email` des réservations confirmées du client, et les `failed` seulement si l'e-mail actuel diffère de l'adresse du dernier envoi raté.

### 2. Disponibilité d'une étape par section (booking) — livrée par la 001 — [research.md](../research.md) C10

```text
événement Livewire reservation-transition-readiness : { step: ReservationTransition value, section: string, is_ready: bool }
ReservationDetail::$readinessBySteps : array<step, array<section, bool>>
isReadyFor(step) : vrai si aucune section n'est enregistrée, sinon toutes les sections qui se sont prononcées pour step sont prêtes
```

Repli tant qu'il n'est pas livré : la section attestation n'émet pas de disponibilité ; seul le refus serveur bloque.

## Exposés par `certification`

| Événement | Charge | Usage |
|---|---|---|
| `VgpReportDeposited` | `VgpReport` | résolution des attestations `awaiting_report` de la machine |
| `CertificateChanged` (`ShouldBroadcast`, canal privé `fleet`) | `reservation_id`, `status`, `delivered_at` | rafraîchir la section et le bandeau |
