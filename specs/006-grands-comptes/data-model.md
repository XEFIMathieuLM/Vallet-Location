# Data Model: Grands comptes : tarifs négociés et bon de commande

Mêmes conventions que les features 001 à 003 : code en anglais, libellés en français par traduction, aucune suppression en cascade en base, dates métier en heure de Paris. Les deux tables appartiennent au nouveau layer `accounts` ; leurs clés étrangères pointent vers `booking`, `fleet` et `users`, jamais l'inverse. Aucun prix n'est stocké.

## KeyAccount

Un client professionnel désigné grand compte (FR-001, FR-002).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| customer_id | référence → Customer | obligatoire, **unique** |
| designated_by | référence → User | obligatoire |
| designated_at | date-heure | obligatoire |
| created_at, updated_at | date-heure | |

**Règles de création** (`DesignateKeyAccount`, sous `lockForUpdate()` du client) :
- le client est de type professionnel (type de la 004), sinon `KeyAccountRefusedException::notProfessional` ;
- un `CustomerBillingAccount` (003) existe pour ce client avec un `external_ref` non vide, sinon `KeyAccountRefusedException::missingBillingRef` ;
- le client n'est pas déjà grand compte, sinon `KeyAccountRefusedException::alreadyDesignated` (l'index unique reste la garantie) ;
- émet `CustomerChanged` après commit.

**Retrait** (`RevokeKeyAccount`) : suppression de la ligne, `CustomerChanged` après commit ; les numéros de bon de commande déjà saisis restent sur les réservations et sont transmis (edge case de la spec).

**Invariant gardé par `KeyAccountTypeGuard`** (G6) : tant que la ligne existe, le type du client ne peut pas devenir autre chose que professionnel.

## ReservationPurchaseOrder

Le numéro de bon de commande d'une réservation (FR-005, FR-006, FR-009).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire, **unique** |
| number | texte (50) | obligatoire ; CHECK `length(btrim(number)) between 1 and 50` |
| entered_by | référence → User | auteur de la dernière saisie |
| agency_id | référence → Agency | agence de l'auteur de la dernière saisie |
| entered_at | date-heure | date de la dernière saisie |
| created_at, updated_at | date-heure | |

**Règles d'écriture** (`SetPurchaseOrder`, transaction, `lockForUpdate()` de la réservation) :
- la réservation est `confirmed`, sinon `PurchaseOrderFrozenException` (numéro figé dès la sortie, FR-009) ;
- le client de la réservation est professionnel, sinon `PurchaseOrderRefusedException::notProfessional` (FR-005) ;
- le numéro est normalisé (`trim`) et fait 1 à 50 caractères, sinon `InvalidPurchaseOrderNumberException` ;
- crée la ligne ou remplace `number`, `entered_by`, `agency_id`, `entered_at` ; aucun effacement possible ;
- un même numéro peut figurer sur plusieurs réservations : aucune unicité sur `number` (FR-006).

**Exigence à la sortie** (`PurchaseOrderDepartureGuard`, dans la transaction de `DepartReservation`) : si une ligne `KeyAccount` existe pour le client de la réservation et qu'aucune ligne `ReservationPurchaseOrder` n'existe pour la réservation → `MissingPurchaseOrderException`. Le statut grand compte est évalué au moment de la sortie (FR-007).

## État affiché de la section « Bon de commande » (dérivé, sans pattern State)

| Condition | État | Départ prêt |
|-----------|------|-------------|
| client particulier ou type à renseigner | section masquée | oui |
| client professionnel non grand compte, sans numéro | facultatif | oui |
| client grand compte, sans numéro | exigé, à saisir | non |
| numéro saisi, réservation confirmée | saisi (modifiable) | oui |
| numéro saisi, réservation en cours ou clôturée | saisi (figé) | sans objet |

Calculé par `PurchaseOrderSectionState` sous la forme de l'enum `PurchaseOrderSectionStatus` (`hidden`, `optional`, `required`, `entered`, `frozen`), jamais stocké. État dérivé, sans transition propre : il se lit dans les tables ci-dessus et le statut de la réservation (principe III, aucun pattern State requis).

## Historique (journal d'activité, log `accounts`)

| Événement | Sujet | Détails |
|-----------|-------|---------|
| `key_account_designated` | Customer | auteur, agence de l'auteur |
| `key_account_revoked` | Customer | auteur, agence de l'auteur |
| `purchase_order_entered` | Reservation | numéro, auteur, agence |
| `purchase_order_corrected` | Reservation | ancien numéro, nouveau numéro, auteur, agence |

## Modifications des layers inférieurs (points d'extension)

| Layer | Élément | Nature |
|-------|---------|--------|
| booking | `Extensions\CustomerBadges` + `Contracts\CustomerBadgeProvider` + `ValueObjects\CustomerBadge` | nouveau registre, de même forme que `MachineBadges` (007), appelé par `CreateReservationForm` (G5) |
| booking | `CustomerChangeGuards` + `CustomerChangeGuard` | livré par la 004, appelé par `UpdateCustomer` ; `accounts` y enregistre `KeyAccountTypeGuard` (G6) |
| booking | `Events\CustomerChanged` | introduit avec `UpdateCustomer` ; émis aussi par `DesignateKeyAccount` et `RevokeKeyAccount` |
| billing | `Contracts\PurchaseOrderNumbers` + `NullPurchaseOrderNumbers` | port lié par défaut, remplacé par `accounts` (G8) |
| billing | `BillableLine::$purchaseOrderNumber`, `MakeBillableLine`, colonne d'export | champ ajouté au contrat de transmission (G8) |

## Configuration (`functional/accounts/config/accounts.php`)

| Clé | Défaut | Usage |
|-----|--------|-------|
| `highlight_days_before_departure` | 3 | mise en évidence dans la liste des bons manquants (FR-015) |
| `purchase_order_max_length` | 50 | longueur maximale du numéro (doit rester égale au CHECK) |
