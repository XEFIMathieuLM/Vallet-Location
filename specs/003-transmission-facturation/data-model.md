# Data Model: Transmission des locations et des réparations au logiciel de facturation

Mêmes conventions que la 001 et la 002 : code en anglais, libellés en français par traduction, statuts en texte casté en enum PHP, aucune suppression en cascade en base. Toutes les tables ci-dessous appartiennent au layer `billing`. Les relations partent de `billing` vers `booking` et `inspection`, jamais l'inverse.

## Enum `BillablePeriodKind`

`intermediate` (fin de mois, location encore en cours), `final` (retour).

## BillablePeriod

Une tranche d'une location à transmettre (FR-001 à FR-003b).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire |
| kind | `BillablePeriodKind` | obligatoire |
| start_date | date | obligatoire |
| end_date | date | obligatoire, ≥ start_date |
| days | entier | `end_date - start_date + 1`, > 0 |
| created_at | date-heure | |

**Contraintes en base** :
- Exclusion : pour un même `reservation_id`, aucune paire de périodes ne partage un jour (`daterange(start_date, end_date, '[]')`, B4).
- Au plus une période `final` par réservation (index unique partiel).

**Règles de création** (B6) :
- Début = lendemain de la `end_date` de la dernière période de la réservation, ou date de sortie (heure de Paris) s'il n'y en a pas.
- `intermediate` : fin = dernier jour du mois, seulement si ce jour est passé et que la réservation est encore `in_progress`.
- `final` : fin = date de retour (heure de Paris). Si le retour tombe le dernier jour du mois, aucune période `intermediate` n'est créée pour ce mois : la réservation est déjà `closed` quand la clôture mensuelle passe.
- Aucune période pour une réservation dont la date de retour (date de `returned_at`, heure de Paris) est antérieure à `billing.go_live_date` ; une réservation rendue le jour même est transmise. Une réservation sortie avant cette date mais encore en cours, ou rendue ce jour-là ou après, est découpée en entier depuis sa date de sortie.
- Aucune période `intermediate` n'est créée tant que la date du jour (heure de Paris) est antérieure à `billing.go_live_date` : avant la mise en service, l'ancien circuit facture encore.
- Sans `billing.go_live_date` (vide ou invalide), aucune période n'est créée : `MissingGoLiveDateException`.
- Chaque période crée sa `Transmission` dans la même transaction.

**Invariant vérifié en test** : pour une réservation `closed`, la somme des `days` est égale au nombre de jours entre la date de sortie et la date de retour, bornes incluses.

## Enum `DamageOutcome`

`billed` (refacturé), `waived` (non refacturé).

## DamageSettlement

Le règlement d'un dégât de la 002 (FR-012 à FR-016).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| damage_id | référence → Damage | obligatoire, unique |
| outcome | `DamageOutcome` | obligatoire |
| amount_cents | entier | obligatoire et > 0 si `billed`, vide si `waived` (contrainte CHECK) |
| label | texte | obligatoire si `billed` |
| waiver_reason | texte | obligatoire si `waived` |
| settled_by | référence → User | obligatoire |
| settled_at | date-heure | obligatoire |

**Règles** :
- Créé une seule fois, jamais modifié (FR-016). Une correction passe par un avoir dans le logiciel de facturation.
- Le dégât doit être non traité (`resolved_at` vide) au moment du règlement.
- La création appelle `ResolveDamage` (`inspection`) dans la même transaction : le dégât sort de la liste « à refacturer » de la 002.
- `billed` crée sa `Transmission` dans la même transaction ; `waived` n'en crée pas.

## Enum `TransmissionStatus` (pattern State)

```text
pending ──(accepté par le logiciel)──► sent
pending ──(refusé par le logiciel)───► failed ──(relance manuelle)──► pending
pending ──(export de secours)────────► exported
failed ───(export de secours)────────► exported
```

`sent` et `exported` sont terminaux. Une transition illégale lève `IllegalTransmissionTransitionException`. Un logiciel injoignable n'est **pas** une transition : la transmission reste `pending`, `attempts` augmente et `next_attempt_at` est repoussée.

## Enum `TransmissionFailureReason`

`customer_unknown` (pas de référence client, B8), `rejected` (refus du logiciel, motif détaillé dans `last_error`).

## Transmission

L'envoi d'un élément facturable (FR-005 à FR-010, FR-021 à FR-023).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| uuid | UUID | unique ; clé d'idempotence envoyée au logiciel (B4) |
| billable_period_id | référence → BillablePeriod | nullable, unique |
| damage_settlement_id | référence → DamageSettlement | nullable, unique |
| reservation_id | référence → Reservation | obligatoire, recopiée pour les listes et le relevé |
| status | `TransmissionStatus` | défaut `pending` |
| attempts | entier | défaut 0 |
| next_attempt_at | date-heure | échéance de relance quand le logiciel était injoignable |
| last_attempt_at | date-heure | nullable |
| failure_reason | `TransmissionFailureReason` | nullable, renseigné si `failed` |
| last_error | texte | message lisible du dernier échec ou de la dernière indisponibilité |
| external_ref | texte | identifiant renvoyé par le logiciel, renseigné si `sent` |
| sent_at | date-heure | nullable |
| billing_export_id | référence → BillingExport | nullable, renseigné si `exported` |

**Contraintes en base** : exactement un de `billable_period_id` et `damage_settlement_id` est renseigné (CHECK).

**Relance automatique** : à chaque indisponibilité, `next_attempt_at` = maintenant + 1, 5, 15, 60 minutes, puis toutes les heures. Pas de limite de tentatives (FR-008). L'alerte (FR-011) se déclenche pour une transmission `pending` créée il y a plus de `billing.alert_after_hours` (24).

**Relance manuelle** (FR-010) : `failed → pending`, `attempts` conservé, `next_attempt_at` = maintenant, envoi immédiat en file.

## CustomerBillingAccount

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| customer_id | référence → Customer | obligatoire, unique |
| external_ref | texte | obligatoire ; identifiant du client dans le logiciel de facturation |

## BillingExport

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| created_by | référence → User | obligatoire |
| created_at | date-heure | |
| line_count | entier | ≥ 1 ; un export sans élément n'est pas créé |
| file_path | texte | fichier sur le disque privé `billing-exports` |

## Historique

Entrées `spatie/laravel-activitylog` du journal `billing`, sujet = la réservation, écrites explicitement par les actions : création d'une période, chaque tentative, échec, relance manuelle, export, chiffrage et classement « non refacturé » (FR-020).

## Relations

```text
Reservation 1──* BillablePeriod 1──0..1 Transmission
Damage 1──0..1 DamageSettlement 1──0..1 Transmission
Reservation 1──* Transmission
BillingExport 1──* Transmission
Customer 1──0..1 CustomerBillingAccount
User 1──* DamageSettlement (settled_by)
User 1──* BillingExport (created_by)
```

## Configuration (`functional/billing/config/billing.php`)

| Clé | Défaut | Rôle |
|-----|--------|------|
| `go_live_date` | aucun, obligatoire (AAAA-MM-JJ) | réservations rendues avant cette date : non transmises ; aucune clôture mensuelle avant cette date ; vide → rien n'est transmis |
| `gateway` | `fake` | implémentation de `BillingGateway` |
| `http_timeout_seconds` | 10 | borne l'attente du logiciel sous verrou (B4) |
| `retry_delays_minutes` | `[1, 5, 15, 60]` | puis 60 min à chaque tentative |
| `alert_after_hours` | 24 | FR-009, FR-011 |
| `damage_overdue_days` | 7 | FR-019 |
| `export_disk` | `billing-exports` | disque privé |
