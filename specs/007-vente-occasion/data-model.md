# Data Model: Vente de machines d'occasion

Montants stockés en centimes (entiers) et exposés en `Functional\Billing\Money\Money` par `MoneyCast` (attributs `asking_price`, `final_price`, `amount`), dates métier en heure de Paris. Statuts en colonnes texte castées en enums PHP. Aucune clé étrangère en cascade.

## Layer `sales` (nouvelles tables)

### `sales`

| Colonne | Type | Règles |
|---|---|---|
| id | bigint PK | |
| machine_id | FK `machines` | obligatoire |
| status | string | `SaleStatus` : `listed`, `reserved`, `sold`, `cancelled` ; défaut `listed` |
| asking_price_cents | integer | CHECK `> 0` ; attribut `asking_price` (`Money`, `MoneyCast`) |
| year_of_manufacture | smallint nullable | CHECK `>= 1950` ; pas dans le futur (contrôle serveur) |
| operating_hours | integer nullable | CHECK `>= 0` |
| condition | string | descriptif de l'état général |
| comment | text nullable | |
| agency_id | FK `agencies` | agence du salarié qui a mis en vente |
| listed_by | FK `users` | |
| buyer_id | FK `customers` nullable | renseigné ssi `reserved` ou `sold` |
| accepted_offer_id | FK `sale_offers` nullable | renseigné ssi `reserved` ou `sold` |
| final_price_cents | integer nullable | renseigné ssi `reserved` ou `sold` ; CHECK `> 0` ; attribut `final_price` (`Money`) |
| planned_handover_date | date nullable | renseigné ssi `reserved` ou `sold` |
| handed_over_on | date nullable | renseigné ssi `sold` |
| handed_over_by | FK `users` nullable | renseigné ssi `sold` |
| cancellation_reason | string nullable | renseigné ssi `cancelled` |
| cancelled_by | FK `users` nullable | renseigné ssi `cancelled` |
| timestamps | | |

Contraintes :
- `sales_one_live_sale_per_machine` : index unique partiel `(machine_id) WHERE status <> 'cancelled'` (FR-002 : une vente ouverte, et plus jamais de mise en vente après une vente conclue).
- `sales_reservation_fields` : CHECK `(status IN ('reserved','sold')) = (buyer_id IS NOT NULL AND accepted_offer_id IS NOT NULL AND final_price_cents IS NOT NULL AND planned_handover_date IS NOT NULL)`.
- `sales_handover_fields` : CHECK `(status = 'sold') = (handed_over_on IS NOT NULL AND handed_over_by IS NOT NULL)`.
- `sales_cancellation_fields` : CHECK `(status = 'cancelled') = (cancellation_reason IS NOT NULL AND cancelled_by IS NOT NULL)`.
- Index `(status, planned_handover_date)` pour la liste et les ventes en retard (FR-023).

La FK `accepted_offer_id` → `sale_offers` est ajoutée après la création de `sale_offers` (dépendance circulaire), sans cascade.

### `sale_offers`

| Colonne | Type | Règles |
|---|---|---|
| id | bigint PK | |
| sale_id | FK `sales` | |
| customer_id | FK `customers` | l'acheteur |
| amount_cents | integer | CHECK `> 0` ; attribut `amount` (`Money`) |
| offered_on | date | défaut aujourd'hui, pas dans le futur (contrôle serveur) |
| status | string | `OfferStatus` : `pending`, `accepted`, `rejected`, `withdrawn` ; défaut `pending` |
| recorded_by | FK `users` | |
| decided_by | FK `users` nullable | renseigné quand le statut quitte `pending` |
| decided_at | timestamp nullable | idem |
| timestamps | | |

Contraintes :
- `sale_offers_one_accepted_per_sale` : index unique partiel `(sale_id) WHERE status = 'accepted'` (FR-011).
- CHECK `(status = 'pending') = (decided_by IS NULL)`.

## Layer `billing` (modification additive de `transmissions`)

| Colonne | Changement |
|---|---|
| reservation_id | devient nullable |
| source_type | nouvelle, string nullable (`BillableLineType`) |
| source_id | nouvelle, bigint nullable |

Contraintes :
- unique `(source_type, source_id)` : une vente n'a qu'une transmission (FR-020, pas de doublon même en cas de double clic).
- `transmissions_single_source` remplacé par : CHECK `num_nonnulls(billable_period_id, damage_settlement_id, source_id) = 1`.
- CHECK `(source_id IS NULL) = (source_type IS NULL)`.
- CHECK `source_id IS NOT NULL OR reservation_id IS NOT NULL` (une période ou un dégât reste rattaché à sa réservation).

Aucune FK de `transmissions` vers `sales` (billing ne connaît pas sales) ; l'intégrité de `source_id` est garantie par sales, qui crée la transmission dans la même transaction que la remise et ne supprime jamais une vente.

## Cycles de vie

### Vente (`SaleState`)

```text
listed ──reserve(offre, date de remise)──▶ reserved ──sell──▶ sold
  │                                          │
  │◀──────────release(motif)─────────────────┤
  │                                          │
  └──cancel(motif)──▶ cancelled ◀──cancel(motif)
```

| Transition | De | Vers | Effets dans la même transaction |
|---|---|---|---|
| reserve | listed | reserved | offre → accepted, autres offres pending → rejected ; buyer, prix final, date de remise renseignés |
| release | reserved | listed | offre acceptée → withdrawn ; buyer, accepted_offer, prix final, date de remise effacés |
| sell | reserved | sold | handed_over_on = aujourd'hui ; machine retirée si elle ne l'est pas ; transmission créée |
| cancel | listed, reserved | cancelled | offres pending → rejected ; offre acceptée → withdrawn |

Transitions interdites : toute transition depuis `sold` ou `cancelled`, `sell` depuis `listed`, `reserve` depuis `reserved`. → `IllegalSaleTransitionException`.

Opérations sans changement d'état : modifier le prix demandé ou le descriptif (`listed` ou `reserved`), modifier la date de remise prévue (`reserved`), enregistrer une offre (`listed` uniquement).

### Offre (`OfferState`)

| Transition | De | Vers |
|---|---|---|
| accept | pending | accepted |
| reject | pending | rejected |
| withdraw | pending, accepted | withdrawn |

Toute autre transition → `IllegalOfferTransitionException`.

## Règles portées par le serveur (transaction + verrou)

| Règle | Où | Garantie |
|---|---|---|
| Une seule vente non annulée par machine | `ListMachineForSale` | index unique partiel, `23505` traduit en `MachineAlreadyForSaleException` |
| Une seule offre acceptée | `AcceptOffer` | verrou `machines` puis `sales`, index unique partiel |
| Aucune location ne finit le jour de remise ou après (acceptation, changement de date) | `AcceptOffer`, `ChangePlannedHandoverDate` | verrou `machines`, requête sur `reservations` confirmées ou en cours avec `end_date >= planned_handover_date` → `HandoverConflictsWithReservationException` |
| Aucune location créée qui finit le jour de remise ou après | garde booking `ReservedSaleReservationGuard` | appelé dans `CreateReservation` sous le verrou `machines` → `MachineReservedForSaleException` |
| Remise refusée si machine sortie ou réservée | `HandOverSale` | verrou `machines` ; contrôle des réservations confirmées ou en cours ; `RetireMachine` (garde booking) relance le même contrôle |
| Retrait manuel refusé si vente ouverte | garde fleet `OpenSaleRetirementGuard` | appelé dans `RetireMachine` sous le verrou `machines` |
| Vente conclue figée | états `Sold` / `Cancelled` | aucune méthode de modification acceptée → exception typée |

## Valeurs dérivées

- **Vente en retard** (FR-023) : `status = reserved AND planned_handover_date < aujourd'hui (Paris)`. Calculée en requête, pas stockée.
- **Total des ventes conclues** (FR-022) : `SUM(final_price_cents)` en base sur le filtre, restitué en `Money::fromStored()`.
- **Jour de remise compatible** : une location dont `end_date < planned_handover_date` est compatible.
