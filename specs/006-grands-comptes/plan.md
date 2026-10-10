# Implementation Plan: Grands comptes : tarifs négociés et bon de commande

**Branch**: `006-grands-comptes` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/006-grands-comptes/spec.md`

## Summary

Un salarié désigne « grand compte » un client professionnel dont l'identifiant de facturation est connu. L'outil le signale à la réservation (tarif négocié appliqué par la facturation), exige un numéro de bon de commande avant la sortie, fige ce numéro à la sortie et le transmet au logiciel de facturation avec chaque période et chaque dégât de la réservation. Une liste permet de relancer les bons manquants avant le départ. Aucun prix n'est saisi ni calculé dans l'outil.

Approche : un nouveau layer OSDD **`accounts`** au sommet de la chaîne (`accounts → billing → inspection → booking → fleet`), avec deux tables (`key_accounts`, `reservation_purchase_orders`). Il agit sur les couches inférieures uniquement par leurs points d'extension : garde de sortie et section du détail (existants, 001), registre de badges client (nouveau, `booking`, même forme que `MachineBadges` de la 007), garde sur le changement de type du client (registre `CustomerChangeGuards` livré par la 004 dans `UpdateCustomer`), port `PurchaseOrderNumbers` et champ `purchase_order_number` de la ligne facturable (nouveaux, `billing`).

## Technical Context

**Language/Version**: PHP 8.5 (runtime Sail), Laravel 13, Livewire 4 + Flux (identique aux features 001 à 003)

**Primary Dependencies**: existantes. Aucun nouveau package : `spatie/laravel-activitylog` (historique), `spatie/laravel-permission` et `lomkit/laravel-access-control` (accès), `xefi/laravel-osdd` (layer).

**Storage**: PostgreSQL, 2 nouvelles tables dans `accounts` (`key_accounts`, `reservation_purchase_orders`), avec index uniques et CHECK. Aucune table d'un autre layer modifiée.

**Testing**: PHPUnit. Un test Feature par scénario d'acceptation (avec `FakeBillingGateway` de la 003 pour US3), tests Unit pour la normalisation du numéro et l'état dérivé de la section. `AssertsRefusals` pour les refus. Larastan avec `xefi/phpstan-xefi-rules` à zéro erreur.

**Target Platform**: serveur Linux (conteneurs) ; worker de file et planificateur déjà requis par la 003.

**Project Type**: application web monolithique (rendu serveur + Livewire)

**Performance Goals**: saisie d'un numéro en moins de 30 secondes (SC-004) ; liste des bons manquants et badges sans requête par ligne. Volume : quelques dizaines de grands comptes, quelques dizaines de réservations par jour.

**Constraints**: refus de sortie garanti côté serveur, dans la transaction de `DepartReservation` (FR-007) ; numéro figé à la sortie, sérialisé avec la sortie par le verrou de la réservation (FR-009) ; aucun prix stocké (SC-006) ; pas d'observers, pas de `try/catch`, pas de cascade.

**Scale/Scope**: 2 tables, 1 garde de sortie, 1 garde de changement de type, 1 section de détail, 2 écrans, 1 fournisseur de badges, 1 liaison de port ; 4 points d'extension dans `booking` et `billing` (dont 2 existants).

**Dépendance externe** : le nom du champ « référence de commande client » dans le logiciel de facturation est à obtenir avec les questions de la 003 ([contracts/billing-purchase-order.md](contracts/billing-purchase-order.md)). Il ne bloque que l'adaptateur réel.

## Affected Repos

Dépôt unique : l'application Laravel à la racine (pas de `repos.yml`, comme pour les features 001 à 003). Aucun autre dépôt touché.

**Dépendances** (prérequis à commiter avant l'implémentation, branche rebasée par-dessus) :

- **001** (7423fc9) : `ReservationTransitionGuards` / `ReservationTransitionGuard` ; `ReservationDetailSections::register(name, position, ReservationTransition ...$guarded)` et readiness par section (`reservation-transition-readiness` avec `step`, `section`, `is_ready` ; une gardienne muette bloque) ; `RefusalException` et `AssertsRefusals` ; contrat `Functional\Fleet\Contracts\AgencyMember` (plus de `App\Models\User` dans les layers) et trait de test `CreatesUsers` ; permissions en enum par layer, seeders de layer qui créent seulement, appelés par `DatabaseSeeder` avant `PermissionSeeder` qui donne toutes les permissions au rôle salarié ; trait `RecordsAuthorAgency` (`author_agency_id`) ; composants `x-empty-state`, `x-loading-hint`, `flux:modal`, `Flux::toast`.
- **003** : `CustomerBillingAccount` (identifiant de facturation), `BillableLine`, `MakeBillableLine`, export de secours, `FakeBillingGateway`. À rebaser sur sa refonte (`Money`, suppression de `Support/`).
- **004** : type de client (particulier / professionnel / à renseigner), action unique d'écriture du client `Functional\Booking\Actions\UpdateCustomer` (une méthode par changement), événement `Functional\Booking\Events\CustomerChanged` (`ShouldDispatchAfterCommit`), registre `CustomerChangeGuards` appelé par la qualification du type. Écran de qualification du type.
- **007** : modifie aussi `BillableLine` (sources de transmission) : à fusionner au rebase.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Portes tirées de la constitution v1.0.1 :

| Principe | Porte | Statut |
|----------|-------|--------|
| I. Layers OSDD | nouveau domaine = nouveau layer `functional/accounts` ; dépendances `accounts → billing → inspection → booking → fleet` ; aucune table d'un autre layer modifiée ; action sur `booking` et `billing` uniquement par points d'extension génériques (registres, garde, port) | ✅ G1, G3 à G8 — les modifications de fichiers de `booking` et `billing` se limitent à ajouter ces points d'extension, emplacement à valider par la coordination |
| II. Garanties en base et serveur | unicité `key_accounts.customer_id` et `reservation_purchase_orders.reservation_id` ; CHECK de longueur du numéro ; refus de sortie dans la transaction de `DepartReservation` ; saisie sous `lockForUpdate()` de la réservation ; désignation sous verrou du client ; pas de cascade ; badges et liste en une requête | ✅ G3, G7, G9, data-model |
| III. Cycles de vie explicites | aucun nouveau cycle de vie : l'état de la section est dérivé (justifié dans data-model) ; aucun montant ; dates en heure de Paris pour la mise en évidence | ✅ data-model |
| IV. Effets de bord et erreurs typées | aucun observer ; refus par exceptions typées héritant de `RefusalException` ; logiciel de facturation toujours derrière `BillingGateway` ; numéro fourni par un port avec implémentation par défaut | ✅ G3, G6, G8 |
| V. Accès par permission | enum `AccountsPermission` (`key_accounts.manage`, `purchase_orders.manage`), attribuées au rôle salarié par `PermissionSeeder` (toutes les permissions existantes, non modifié) ; contrôles `lomkit` ; aucun nom de rôle | ✅ G10 |
| VI. Tests par scénario | un test Feature par scénario d'acceptation, écrit d'abord ; faux logiciel ; Larastan à zéro erreur ; suite complète verte | ✅ tasks.md |
| VII. Code simple et lisible | aucun nouveau package ; code en anglais, textes traduits ; fichiers < 200 lignes, sans commentaire | à vérifier pendant l'implémentation |

**Résultat** : aucune violation.

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes. La 006 ajoute des fichiers dans `booking` (`CustomerBadges`) et dans `billing` (port `PurchaseOrderNumbers`, champ de `BillableLine`, colonne d'export). Ce sont des points d'extension génériques (principe I), validés par la coordination ; ils sont écrits dans cette branche après sa remise à jour sur la 001 à la 004 et la 003 corrigée.

## Project Structure

### Documentation (this feature)

```text
specs/006-grands-comptes/
├── spec.md
├── plan.md                      # ce fichier
├── research.md                  # décisions G1–G10
├── data-model.md                # tables, règles, état dérivé, historique
├── quickstart.md                # guide de vérification
├── contracts/
│   ├── billing-purchase-order.md  # port et champ ajoutés à billing (extension de la 003)
│   ├── booking-extensions.md      # points d'extension de booking utilisés ou ajoutés
│   └── screens.md                 # écrans
├── checklists/
│   └── requirements.md
└── tasks.md                     # /speckit-tasks
```

### Source Code (repository root)

```text
composer.json, phpunit.xml, phpstan.neon, database/seeders/DatabaseSeeder.php   # enregistrement du layer, appel d'AccountsPermissionSeeder avant PermissionSeeder (modifiés)
resources/views/layouts/app/sidebar.blade.php                                  # + entrées Grands comptes, Bons de commande (modifié)
functional/
├── booking/                                   # points d'extension (ajouts)
│   └── src/
│       ├── Contracts/CustomerBadgeProvider.php
│       ├── ValueObjects/CustomerBadge.php
│       ├── Extensions/CustomerBadges.php
│       └── Livewire/CreateReservationForm.php # + affichage des badges (modifié)
│├── billing/                                   # point d'extension (ajouts)
│   └── src/
│       ├── Contracts/PurchaseOrderNumbers.php
│       ├── Gateways/NullPurchaseOrderNumbers.php   # liée avec bindIf ; emplacement repris au rebase de la 003
│       ├── ValueObjects/BillableLine.php      # + purchaseOrderNumber (modifié)
│       ├── Actions/MakeBillableLine.php       # + lecture du port (modifié)
│       └── Providers/BillingServiceProvider.php  # + liaison par défaut (modifié)
│       ├── Exports/ExportLineFormatter.php    # + colonne purchase_order_number (modifié, emplacement après correction de la 003)
└── accounts/                                  # nouveau layer
    ├── composer.json                          # LayerManifest, dépend de billing, inspection, booking, fleet
    ├── config/accounts.php                    # highlight_days_before_departure, purchase_order_max_length
    ├── src/
    │   ├── Models/                            # KeyAccount, ReservationPurchaseOrder
    │   ├── Actions/                           # DesignateKeyAccount, RevokeKeyAccount, SetPurchaseOrder
    │   ├── Guards/                            # PurchaseOrderDepartureGuard, KeyAccountTypeGuard
    │   ├── Badges/                            # KeyAccountBadgeProvider
    │   ├── Billing/                           # KeyAccountPurchaseOrderNumbers
    │   ├── Queries/                           # MissingPurchaseOrders
    │   ├── Support/                           # AccountsHistory (journal d'activité), PurchaseOrderNumber (normalisation)
    │   ├── Exceptions/                        # KeyAccountRefusedException, KeyAccountMustStayProfessionalException, MissingPurchaseOrderException, PurchaseOrderFrozenException, PurchaseOrderRefusedException, InvalidPurchaseOrderNumberException
    │   ├── Access/                            # AccountsPermission (enum) ; Controls/ : KeyAccountControl, PurchaseOrderControl
    │   ├── Providers/                         # AccountsServiceProvider (gardes, section, badges, liaison du port)
    │   └── Livewire/                          # KeyAccounts, MissingPurchaseOrders, PurchaseOrderSection
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views,lang/fr}/
    ├── routes/web.php                         # /grands-comptes, /bons-de-commande
    └── tests/{Feature,Unit}/
```

**Structure Decision**: un layer `accounts` dans le même dépôt. Les règles de grand compte vivent toutes dans `accounts` ; `booking` et `billing` ne reçoivent que des points d'extension génériques, sans connaître `accounts`. La 003 sans la 006 se comporte exactement comme avant (`NullPurchaseOrderNumbers`). L'emplacement final de chaque ajout dans `booking` et `billing` est soumis à la coordination à la relecture du plan.

## Décisions prises

| Décision | Retenu | Par |
|----------|--------|-----|
| Saisie du numéro à la création (US2 scénario 5) | saisie dans la section du détail ouvert juste après la création ; spec amendée ; pas de registre `ReservationCreationFields` | utilisateur (2026-10-10) |
| Livreur du garde de changement de type (G6) | registre `CustomerChangeGuards` livré par la 004 dans `UpdateCustomer` ; la 006 y enregistre sa garde | coordination |
| Emplacement des règles de grand compte (G1) | nouveau layer `functional/accounts` ; `billing` expose `PurchaseOrderNumbers` sans connaître `accounts` | coordination |
| Badges client | registre `CustomerBadges` générique, appel groupé, aligné sur `MachineBadges` (007) | coordination |
| Écriture du client | `UpdateCustomer` + `CustomerChanged` ; la désignation n'écrit pas `customers` et émet seulement `CustomerChanged` (research G2) | coordination (confirmé : désignation dans `accounts`, `CustomerChanged` émis sans écriture de `customers`) |

## Complexity Tracking

Aucune violation à justifier.
