# Data Model: Envoi automatique de l'attestation VGP au client

Trois tables dans le layer `certification`. Clés étrangères sans suppression en cascade (principe II) ; statuts en colonnes texte castées en enums PHP, avec CHECK (principe III).

## VgpReport — `vgp_reports`

Un rapport de VGP déposé pour une machine (FR-001 à FR-004).

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint | clé |
| `machine_id` | bigint | FK `machines.id`, sans cascade |
| `file_path` | string | chemin sur le disque `vgp-reports`, nom généré |
| `original_name` | string | nom du fichier déposé, pour la pièce jointe |
| `mime_type` | string | `application/pdf`, `image/jpeg`, `image/png` |
| `size_bytes` | integer | ≤ `certification.max_report_kilobytes` × 1024 |
| `verified_on` | date | date de vérification |
| `due_on` | date | date d'échéance ; CHECK `due_on > verified_on` |
| `deposited_by` | bigint | FK `users.id` |
| `created_at`, `updated_at` | timestamps | `created_at` = date du dépôt |

- Index `(machine_id, id)`. **Rapport en vigueur** d'une machine = son rapport d'`id` le plus grand.
- Relations : `machine()` (BelongsTo `Functional\Fleet\Models\Machine`), `depositor()` (BelongsTo `User`). `fleet` ne déclare aucune relation vers `vgp_reports`.
- Validation du dépôt (`DepositVgpReport`) : machine soumise à VGP, fichier dans les formats et la taille de la configuration, dates présentes et cohérentes ; sinon `InvalidVgpReportException` (refus traduit avec le motif).
- Effets du dépôt, dans une transaction : création du rapport ; `UpdateMachineVgp::handle($machine, $dueOn)` ; trace dans l'historique de la machine ; après commit, `VgpReportDeposited`.

## ReservationCertificate — `reservation_certificates`

L'obligation d'envoyer le rapport au client d'une réservation confirmée d'une machine soumise à VGP (FR-005, FR-014).

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint | clé |
| `reservation_id` | bigint | FK `reservations.id`, sans cascade, **unique** |
| `status` | string | `CertificateStatus` ; CHECK sur les 6 valeurs |
| `attempts` | integer | nombre de tentatives automatiques, ≥ 0 |
| `next_attempt_at` | timestamp nullable | prochaine relance automatique (état `pending`) |
| `last_failure_reason` | string nullable | `DispatchFailureReason` du dernier échec |
| `status_changed_at` | timestamp | date d'entrée dans l'état courant, mise à jour à chaque transition |
| `delivered_at` | timestamp nullable | date de l'envoi réussi ou de la remise ; CHECK non nul si et seulement si `status` ∈ (`sent`, `hand_delivered`) |
| `created_at`, `updated_at` | timestamps | `created_at` = ouverture de l'attestation |

- Index `(status, next_attempt_at)` pour le rattrapage et `(status, status_changed_at)` pour la liste à traiter.
- Relations : `reservation()` (BelongsTo `Functional\Booking\Models\Reservation`), `dispatches()` (HasMany `CertificateDispatch`), `lastDispatch()` (HasOne latestOfMany). `booking` ne déclare aucune relation vers cette table.

### `CertificateStatus` et états (pattern State, [research.md](research.md) C4)

| Valeur | Libellé affiché | Livrée ? | À traiter ? (réservation confirmée) |
|---|---|---|---|
| `awaiting_report` | En attente : rapport de VGP non déposé | non | oui |
| `awaiting_email` | Non envoyée : e-mail du client manquant | non | oui |
| `pending` | En attente d'envoi | non | si `status_changed_at` est plus ancien que `alert_after_minutes` |
| `failed` | Échec de l'envoi : motif | non | oui |
| `sent` | Envoyée le … à … | oui | non |
| `hand_delivered` | Remise en main propre le … par … | oui | non |

Classes : `AwaitingReportCertificate`, `AwaitingEmailCertificate`, `PendingCertificate`, `FailedCertificate`, `SentCertificate`, `HandDeliveredCertificate`, implémentant `CertificateState` (`resolve()`, `markSent()`, `scheduleRetry()`, `markFailed()`, `handDeliver()`), via `CertificateStateFactory::fromStatus()`. Les transitions absentes du tableau C4 lèvent `IllegalCertificateTransitionException`.

## CertificateDispatch — `certificate_dispatches`

Une tentative d'envoi par e-mail ou une remise en main propre ; journal immuable (FR-015, FR-022).

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint | clé |
| `reservation_certificate_id` | bigint | FK, sans cascade |
| `vgp_report_id` | bigint | FK `vgp_reports.id` : le rapport envoyé ou remis |
| `channel` | string | `DispatchChannel` : `email`, `hand` ; CHECK |
| `recipient_email` | string nullable | CHECK non nul si `channel = 'email'` |
| `is_automatic` | boolean | CHECK `is_automatic = false` si `channel = 'hand'` |
| `author_id` | bigint nullable | FK `users.id` ; CHECK non nul si `is_automatic = false` |
| `outcome` | string | `DispatchOutcome` : `sent`, `failed` ; CHECK `outcome = 'sent'` si `channel = 'hand'` |
| `failure_reason` | string nullable | `DispatchFailureReason` ; CHECK non nul si et seulement si `outcome = 'failed'` |
| `attempted_at` | timestamp | |

- **Index unique partiel** `(reservation_certificate_id) WHERE is_automatic AND outcome = 'sent'` : au plus un envoi automatique réussi par attestation (FR-009).
- Pas de `updated_at` : une trace n'est jamais modifiée.

### `DispatchFailureReason` ([research.md](research.md) C7)

| Valeur | Définitif ? | Libellé |
|---|---|---|
| `invalid_address` | oui | Adresse e-mail invalide |
| `recipient_rejected` | oui | Adresse refusée par la messagerie du destinataire |
| `mail_service_unavailable` | non | Service d'envoi indisponible, nouvel essai prévu |

## Entités des autres layers utilisées

| Entité | Layer | Utilisation |
|---|---|---|
| `Machine` (`is_subject_to_vgp`, `vgp_due_date`, `reference`, `category`) | fleet | décider si une attestation est due ; échéance mise à jour par `UpdateMachineVgp` |
| `Reservation` (`status`, `start_date`, `end_date`, `agency`, `customer`, `machine`) | booking | ouverture, contenu de l'e-mail, liste triée par date de départ |
| `Customer` (`name`, `email`) | booking | destinataire ; e-mail modifié par `UpdateCustomer::changeEmail()` (point d'extension C9) |
| `activity_log` | app (spatie) | historique de la réservation (journal `certification`) et de la machine |

## Historique

`CertificationHistory::record($subject, CertificationHistoryEvent $event, $details)` sur le modèle de `BillingHistory` (auteur lu par `Auth::user()`), journal `certification` :

| Sujet | Événement | Détails |
|---|---|---|
| Machine | `vgp_report_deposited` | rapport, dates |
| Réservation | `certificate_sent` | adresse, rapport, automatique ou manuel |
| Réservation | `certificate_failed` | adresse, motif, temporaire ou définitif |
| Réservation | `certificate_hand_delivered` | rapport |
| Réservation | `customer_email_updated` | ancienne et nouvelle adresse |

Chaque écriture d'historique se fait dans la transaction de l'action qui la provoque.

## Configuration — `functional/certification/config/certification.php`

| Clé | Valeur par défaut | Usage |
|---|---|---|
| `go_live_date` | `env('CERTIFICATION_GO_LIVE_DATE')` | mise en service (C8) |
| `timezone` | `Europe/Paris` | calcul de la date de mise en service |
| `retry_delays_minutes` | `[1, 5, 15, 60]` | relances ; la dernière valeur se répète |
| `alert_after_minutes` | `60` | seuil d'apparition d'une attestation `pending` dans la liste |
| `accepted_mimes` | `['pdf', 'jpg', 'jpeg', 'png']` | formats de rapport |
| `max_report_kilobytes` | `10240` | taille maximale d'un rapport |
| `reports_disk` | `vgp-reports` | disque privé (déclaré dans `functional/certification/config/filesystems.php`) |

L'adresse d'expédition et de réponse vient de `mail.from` (configuration standard de Laravel), à fournir par le client avant la mise en production.
