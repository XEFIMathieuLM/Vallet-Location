# Implementation Plan: Caution des particuliers

**Branch**: `004-caution-particuliers` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/004-caution-particuliers/spec.md`

## Summary

Chaque client devient « particulier » ou « professionnel ». La sortie d'une machine louée par un particulier est refusée tant que sa caution n'est pas encaissée, et celle d'un client sans type tant qu'il n'est pas qualifié. L'outil enregistre un encaissement fait hors outil (chèque, empreinte bancaire au terminal, espèces) au montant de la catégorie de la machine, puis suit la caution jusqu'à sa restitution ou son solde : restituée en entier sans dégât (avec confirmation « aucun dégât constaté »), ou soldée par une retenue égale au total hors taxes des dégâts refacturés, plafonnée au montant de la caution.

Approche : le type de client entre dans `booking` (contrat `UpdateCustomer` / `CustomerChanged` / `CustomerChangeGuards` partagé avec les 005 et 006). La caution est un nouveau layer OSDD **`deposit`** au sommet (`deposit → billing → inspection → booking → fleet`). Elle bloque la sortie par un garde de `ReservationTransitionGuards`, s'affiche par une section gardienne de `ReservationDetailSections`, et suit son cycle de vie en pattern State, mis à jour par des listeners sur `ReservationChanged` et `DamageChanged` et rattrapé par une commande planifiée.

## Technical Context

**Language/Version**: PHP 8.5 (runtime Sail), Laravel 13, Livewire 4 + Flux (identique aux 001 à 003)

**Primary Dependencies**: existantes. Aucun nouveau package : `spatie/laravel-activitylog` (historique), `spatie/laravel-permission` + `lomkit/laravel-access-control` (accès), `Money` / `MoneyCast` du layer `billing` (montants).

**Storage**: PostgreSQL. `booking` : colonne `customers.type`. `deposit` : 2 tables (`deposit_rates` avec index uniques partiels, `deposits` avec unicité par réservation et CHECK de cohérence).

**Testing**: PHPUnit. Un test Feature par scénario d'acceptation ; tests Unit purs pour les états de `Deposit` et le calcul de la retenue (constitution 1.0.1 : calcul en mémoire → Unit, calcul en base → Feature). Trait `CreatesUsers`, `AssertsRefusals::assertRefused`, horloge `travelTo()`. Larastan niveau 7 avec `xefi/phpstan-xefi-rules`, Pint.

**Target Platform**: serveur Linux (conteneurs), planificateur actif en production.

**Project Type**: application web monolithique (rendu serveur + Livewire)

**Performance Goals**: encaissement en moins de 1 minute depuis le détail (SC-004) ; volume faible (quelques dizaines de cautions par jour au plus).

**Constraints**: zéro sortie de particulier sans caution (SC-001), zéro restitution avec un dégât à traiter (SC-003) : garanties serveur sous verrou. Pas d'observers, pas de `try/catch`, pas de cascade.

**Scale/Scope**: 1 colonne + 4 contrats dans `booking` ; 2 tables, 1 garde, 1 section, 2 écrans, 2 listeners, 1 commande planifiée dans `deposit`.

## Affected Repos

Dépôt unique : l'application Laravel à la racine du dépôt (pas de `repos.yml`, comme pour les 001 à 003). Aucun autre dépôt touché.

**Dépendances** :
- **001** (`booking`, `fleet`) — après remise à jour de cette branche sur la 003 corrigée, qui doit contenir `7423fc9` et `7122415` : `ReservationTransitionGuards` + `ReservationTransitionGuard::beforeDeparture()`, `ReservationDetailSections::register(string, int, ReservationTransition ...)` et l'événement `reservation-transition-readiness` (`step`, `section`, `is_ready`), `ReservationTransition`, `ReservationChanged`, `CreateReservation` + `NewCustomer`, `RefusalException`, `DisplaysRefusals`, `AgencyMember`, `RecordsAuthorAgency`, `CreatesUsers`, `AssertsRefusals`, `PermissionSeeder` (rôle `salarie` ← `Permission::all()`), composants `x-empty-state`, `x-loading-hint`, `x-section-heading`.
- **002** (`inspection`) : `CountUnresolvedDamages::for()`, `DamageChanged` (`reservationId`, `unresolvedCount`), `Damage`.
- **003** (`billing`) : `damage_settlements` (`outcome`, `amount_cents`), `DamageOutcome::Billed`, `Money`, `MoneyCast`.

**Modifications de `booking` par la feature** (propriétaire du client, D1) : colonne `customers.type`, `CustomerType`, `NewCustomer::$type`, champ type dans `CreateReservationForm`, `UpdateCustomer::qualify()`, `CustomerChanged`, `CustomerChangeGuard` + `CustomerChangeGuards`, historique du client. Aucun fichier de `inspection` ni de `billing` n'est modifié.

L'implémentation démarre **après** le feu vert de la coordination et la remise à jour de cette branche (prérequis listés dans `tasks.md`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Portes tirées de la constitution v1.0.1 :

| Principe | Porte | Statut |
|----------|-------|--------|
| I. Layers OSDD | nouveau domaine = nouveau layer `functional/deposit` au sommet ; le type de client est ajouté à `booking` par la feature, dans le layer propriétaire du client, pas écrit depuis `deposit` ; `deposit` agit sur `booking` uniquement par ses points d'extension (garde, section) et par `UpdateCustomer` ; `booking` expose un nouveau point d'extension `CustomerChangeGuards` pour la 006 | ✅ D1, D2, D3 |
| II. Garanties en base et serveur | unicité `deposits.reservation_id`, index uniques partiels des montants, CHECK de cohérence ; encaissement, restitution, solde et qualification sous `lockForUpdate()` ; refus de sortie dans la transaction de `DepartReservation` ; pas de cascade ; total des dégâts refacturés agrégé en SQL | ✅ D2, D5, D7, D8, data-model |
| III. Cycles de vie explicites | `Deposit` en pattern State (6 états, transitions interdites typées) ; situations sans caution dérivées, justifiées (une seule transition : l'encaissement) ; statuts texte + enum ; montants en centimes (`Money`) ; ancienneté en heure de Paris | ✅ D4 |
| IV. Effets de bord et erreurs typées | listeners sur `ReservationChanged` et `DamageChanged`, état persisté et rattrapage planifié `deposit:reconcile` ; pas d'observer ; refus typés (`DepositRefusedException`, `IllegalDepositTransitionException`) ; aucun système externe (encaissement hors outil) | ✅ D6 |
| V. Accès par permission | `DepositPermission` (`deposits.manage`, `deposit_rates.manage`) créées par `DepositPermissionSeeder`, données au rôle salarié par `PermissionSeeder` ; aucun nom de rôle dans le code | ✅ D12 |
| VI. Tests par scénario | un test Feature par scénario d'acceptation, écrit d'abord ; Unit purs pour les états et la retenue ; `travelTo()` pour l'ancienneté ; Larastan à zéro erreur | ✅ tasks.md |
| VII. Code simple et lisible | aucun nouveau package ; code en anglais, textes traduits (`deposit::`, `booking::`) ; fichiers < 200 lignes, méthodes < 40 lignes, sans commentaire | à vérifier pendant l'implémentation |

**Résultat** : aucune violation. Un écart de transparence (modification de fichiers de `booking` par la feature) est justifié dans « Complexity Tracking ».

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes. La seule écriture hors du layer `deposit` est l'enrichissement de `booking` par la feature, dans le layer propriétaire (D1).

## Project Structure

### Documentation (this feature)

```text
specs/004-caution-particuliers/
├── spec.md
├── plan.md              # ce fichier
├── research.md          # décisions D1–D12
├── data-model.md        # tables, états, configuration
├── quickstart.md        # guide de vérification
├── contracts/
│   ├── customer-contract.md   # UpdateCustomer, CustomerChanged, CustomerChangeGuards (005, 006)
│   └── screens.md             # écrans
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
composer.json, phpunit.xml, phpstan.neon      # enregistrement du layer deposit (modifiés)
database/seeders/DatabaseSeeder.php            # + DepositPermissionSeeder (modifié)
tests/TestCase.php                             # seedPermissions() + DepositPermissionSeeder (modifié)
resources/views/layouts/app/sidebar.blade.php  # + menu Cautions (modifié)
functional/
├── booking/                                   # enrichi
│   ├── database/migrations/2026_10_10_000080_add_type_to_customers_table.php
│   ├── database/factories/CustomerFactory.php       # états individual(), professional(), untyped()
│   ├── src/Enums/CustomerType.php
│   ├── src/Data/NewCustomer.php                     # + CustomerType $type
│   ├── src/Actions/CreateReservation.php            # persiste le type
│   ├── src/Actions/UpdateCustomer.php               # qualify()
│   ├── src/Events/CustomerChanged.php
│   ├── src/Contracts/CustomerChangeGuard.php
│   ├── src/Extensions/CustomerChangeGuards.php
│   ├── src/Models/Customer.php                      # cast, LogsActivity, RecordsAuthorAgency
│   ├── src/Livewire/CreateReservationForm.php       # champ type obligatoire
│   ├── src/Providers/BookingServiceProvider.php     # singleton CustomerChangeGuards
│   ├── resources/{views,lang/fr}/
│   └── tests/{Feature,Unit}/
└── deposit/                                   # nouveau
    ├── composer.json                          # dépend de billing, inspection, booking, fleet
    ├── config/deposit.php
    ├── src/
    │   ├── Models/                # Deposit, DepositRate
    │   ├── Enums/                 # DepositStatus, PaymentMethod, DepositSituationKind, DepositHistoryEvent
    │   ├── States/                # Collected, ToRefund, BlockedByDamage, ToSettle, Refunded, Settled (+ DepositState)
    │   ├── Actions/               # CollectDeposit, CorrectDepositPayment, RefundDeposit, SettleDeposit, SyncDepositStatus, ResolveDepositAmount, SetDepositRate, RemoveDepositRate
    │   ├── Queries/               # DepositSituation, BilledDamagesTotal, PendingDeposits
    │   ├── ValueObjects/          # DepositRetention
    │   ├── Guards/                # DepositCollectedGuard
    │   ├── Listeners/             # SyncDepositOnReservationChanged, SyncDepositOnDamageChanged
    │   ├── Console/               # deposit:reconcile
    │   ├── History/               # DepositHistory
    │   ├── Exceptions/            # DepositRefusedException, IllegalDepositTransitionException
    │   ├── Access/                # DepositPermission, Controls/DepositControl, Controls/DepositRateControl
    │   ├── Providers/             # DepositServiceProvider (garde, section, listeners, planification)
    │   └── Livewire/              # ReservationDepositSection (affichage + disponibilité), Section/{CollectDepositForm, QualifyCustomerForm, CorrectPaymentForm, CloseDepositActions}, PendingDepositsList, DepositRatesIndex, DepositRateForm
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views,lang/fr}/
    ├── routes/{web.php,console.php}   # /cautions, /cautions/montants ; deposit:reconcile toutes les 5 minutes
    └── tests/{Feature,Unit}/
```

**Structure Decision**: un layer `deposit` dans le même dépôt que les 001 à 003, au sommet de la chaîne car il lit les dégâts (`inspection`) et leurs règlements (`billing`). `inspection` et `billing` ne sont pas modifiés : la caution réagit à leurs événements et lit leurs données. `booking` est enrichi par la feature dans son propre périmètre (le client), et expose `CustomerChangeGuards` pour que la 006 puisse refuser une requalification sans que `booking` la connaisse. `app/` reçoit l'entrée de menu, comme pour la 003.

## Complexity Tracking

| Écart | Pourquoi | Alternative plus simple écartée |
|-------|----------|--------------------------------|
| La feature modifie des fichiers de `booking` livrés par la 001 (`Customer`, `NewCustomer`, `CreateReservation`, `CreateReservationForm`, `BookingServiceProvider`) | Le principe I interdit à un layer de modifier un autre layer : il est respecté, `deposit` n'écrit rien dans `booking`. Mais sa raison d'être dit qu'une feature s'ajoute sans toucher au code des précédentes. Le type de client appartient au client (`booking`), il est exigé à sa création et sert aussi aux 005 et 006 ; la 001 annonçait ce client « enrichi par les specs suivantes ». Arbitré par la coordination des features. | Type dans `deposit` (la 006 dépendrait de la caution, et la création du client ne pourrait pas l'exiger) ; layer `customer` dédié (un layer pour une colonne, mêmes modifications de `booking` pour l'exiger à la création). |
| Modification du test `functional/inspection/tests/Feature/PhotosPanelReadinessTest.php` (002) : il enregistre un registre de sections ne contenant que le panneau photos | Ce test supposait que le panneau photos était la seule section gardienne du départ ; avec la disponibilité par section (001 `7423fc9`), toute section gardienne ajoutée (caution, attestation de la 005) le faisait échouer. Seul le test change ; aucun code applicatif d'`inspection` n'est modifié. Signalé à la coordination. | Ne pas enregistrer la section caution comme gardienne (le bouton de sortie s'activerait sans caution, contraire à FR-005 et à la décision « disponibilité par section ») ; enregistrer les gardes du test dans chaque feature suivante (même modification répétée).
