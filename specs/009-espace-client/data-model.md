# Data Model: Espace client et réservation en ligne

Toutes les tables appartiennent au layer `portal`. Aucune table d'un autre layer n'est modifiée. Les clés étrangères sont déclarées sans `cascadeOnDelete()`.

## `customer_accounts` — Compte client

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(255) | obligatoire ; nom ou raison sociale |
| `email` | string(255) | obligatoire, normalisé en minuscules et sans espaces ; **index unique** (FR-001) |
| `email_verified_at` | timestamp nullable | posé par le lien signé (FR-002) |
| `phone` | string(30) | obligatoire |
| `declared_type` | string | enum `CustomerType` de `booking` (`individual`, `professional`) ; CHECK sur les deux valeurs |
| `password` | string | haché (cast `hashed`), règle `Password::defaults()` |
| `customer_id` | FK nullable → `customers.id` | rattachement ; posé une fois, jamais modifié ensuite (FR-005, FR-007) ; plusieurs comptes possibles par fiche |
| `remember_token` | string nullable | |
| `created_at`, `updated_at` | timestamps | |

- Modèle `Functional\Portal\Models\CustomerAccount` : `Authenticatable`, `MustVerifyEmail`, `CanResetPassword`, `Notifiable`.
- Index : `customer_id`.
- Le rattachement est un `UPDATE … SET customer_id = ? WHERE id = ? AND customer_id IS NULL` sous verrou du compte ; une ligne non touchée ⇒ `CustomerAccountAlreadyAttachedException` (US3-12).
- Les comptes ne sont jamais supprimés dans cette version (hors périmètre).

## `customer_password_reset_tokens`

Table standard du broker `customer_accounts` (`email` PK, `token`, `created_at`). Expiration à 60 minutes et anti-rafale à 60 secondes, comme le broker des salariés.

## `reservation_requests` — Demande de réservation

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint PK | |
| `customer_account_id` | FK → `customer_accounts.id` | obligatoire |
| `machine_id` | FK → `machines.id` | machine demandée |
| `start_date`, `end_date` | date | `end_date >= start_date` (CHECK) |
| `comment` | string(500) nullable | |
| `indicative_daily_price_cents` | integer nullable | copié à l'envoi (FR-014) ; CHECK `> 0` si non nul |
| `status` | string | enum `ReservationRequestStatus` ; CHECK sur les 5 valeurs |
| `reservation_id` | FK nullable → `reservations.id` | **index unique** ; non nul ⇔ `status = 'confirmed'` (CHECK) |
| `refusal_reason` | string(500) nullable | non nul ⇔ `status = 'refused'` (CHECK) |
| `decided_by` | FK nullable → `users.id` | salarié qui confirme ou refuse |
| `decided_agency_id` | FK nullable → `agencies.id` | agence du salarié |
| `decided_at` | timestamp nullable | posé à toute sortie de `pending` |
| `customer_notified_at` | timestamp nullable | posé par le job d'e-mail (R8) |
| `created_at`, `updated_at` | timestamps | `created_at` = date d'envoi |

**Contraintes et index**

- `EXCLUDE USING gist (customer_account_id WITH =, machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&) WHERE (status = 'pending')` : interdit une demande qui en chevauche une autre en attente du même compte sur la même machine (FR-012).
- Index `(status, start_date)` : liste des salariés et expiration.
- Index `(customer_account_id, status)` : espace client et décompte des demandes en attente.
- Limite de 10 demandes en attente : comptée sous `lockForUpdate()` du compte dans la transaction d'envoi (R5) ; seuil dans `config/portal.php` (`max_pending_requests`).

### Cycle de vie (pattern State)

```text
pending ──confirm──▶ confirmed   (salarié ; crée la réservation)
pending ──refuse───▶ refused     (salarié ; motif obligatoire)
pending ──cancel───▶ cancelled   (client)
pending ──expire───▶ expired     (automatique ; start_date < aujourd'hui, Europe/Paris)
```

- Classes : `PendingRequestState`, `ConfirmedRequestState`, `RefusedRequestState`, `CancelledRequestState`, `ExpiredRequestState`, fabrique `ReservationRequestStateFactory`.
- Les états finaux refusent toute transition : `IllegalReservationRequestTransitionException` (une `RefusalException`).
- Toute transition se fait sous `lockForUpdate()` de la demande, dans une transaction (FR-020).
- Une demande confirmée garde le lien vers sa réservation, même si la réservation est annulée ensuite par un salarié.

### Statut affiché au client pour une réservation (lecture de `booking`)

| `ReservationStatus` (001) | Libellé client |
|---|---|
| `confirmed` | Confirmée |
| `in_progress` | En cours |
| `closed` | Terminée |
| `cancelled` | Annulée |

Enum de présentation `CustomerReservationStatus` du layer `portal`. Le conflit, les dégâts, les cautions, les transmissions et les bons de commande ne sont jamais lus pour le client (FR-025).

## `category_indicative_prices` — Prix indicatif de catégorie

| Colonne | Type | Règles |
|---|---|---|
| `id` | bigint PK | |
| `machine_category_id` | FK → `machine_categories.id` | **index unique** |
| `daily_price_cents` | integer | CHECK `> 0` (FR-031) |
| `updated_by` | FK → `users.id` | |
| `updated_agency_id` | FK → `agencies.id` | |
| `created_at`, `updated_at` | timestamps | |

- Retirer le prix supprime la ligne. Saisie, modification et retrait sont tracés dans le journal d'activité `portal`, avec l'ancien et le nouveau montant.
- Une catégorie sans ligne affiche « prix sur demande ».

## Entités lues dans les autres layers (sans écriture directe)

| Entité | Layer | Usage |
|---|---|---|
| `Machine`, `MachineCategory`, `Agency` | fleet | recherche, affichage, choix de la machine de remplacement |
| `Customer`, `CustomerType` | booking | rattachement ; création via `CreateReservation` (`NewCustomer`) |
| `Reservation`, `ReservationStatus` | booking | réservation créée ; liste « Mes réservations » |
| `AvailableMachinesQuery` | booking | recherche client et vérification à l'envoi et à la confirmation |
| `CreateReservation` | booking | seule écriture de réservation, sous les règles de la 001 |
| `ReservationCertificate`, `CertificateDispatch`, `VgpReport` | certification | document téléchargeable (R13) |

## Historique (`activity_log`, journal `portal`)

| Événement | Sujet | Auteur | Propriétés |
|---|---|---|---|
| `request_sent` | demande | compte client | machine, dates, prix indicatif |
| `request_confirmed` | demande | salarié | réservation, machine réservée, rattachement (`existing` / `created`) |
| `account_attached` | fiche `Customer` | salarié | compte, e-mail du compte |
| `request_refused` | demande | salarié | motif |
| `request_cancelled` | demande | compte client | |
| `request_expired` | demande | automatique | |
| `indicative_price_set` / `indicative_price_removed` | catégorie | salarié | ancien et nouveau montant |

## Configuration — `functional/portal/config/portal.php`

| Clé | Valeur | Source |
|---|---|---|
| `max_pending_requests` | 10 | FR-012 (à confirmer avec M. Vallet) |
| `notification_retry_after_minutes` | 10 | R8 |
| `comment_max_length` / `refusal_reason_max_length` | 500 | FR-011, FR-019 |
