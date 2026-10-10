# Implementation Plan: Vente de machines d'occasion

**Branch**: `007-vente-occasion` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/007-vente-occasion/spec.md`

## Summary

Un nouveau layer `functional/sales`, au-dessus de `fleet`, `booking` et `billing`, gère la vente d'occasion : mise en vente d'une machine, offres d'acheteurs (clients de booking), réservation de la vente avec une date de remise, remise qui retire la machine du parc, annulation et levée de réservation. Les deux cycles de vie (vente, offre) suivent le pattern State. Les règles d'unicité et de non-conflit avec les locations sont garanties en base (index uniques partiels, CHECK) et par verrou sur la ligne `machines`.

sales agit sur les layers existants uniquement par quatre points d'extension génériques à ajouter (voir [contracts/extension-points.md](contracts/extension-points.md)) :

| # | Layer modifié | Point d'extension | Pour |
|---|---|---|---|
| E1 | booking | registre `ReservationRequestGuards` appelé à la création d'une réservation et dans la recherche de disponibilité | refuser une location qui finit le jour de remise ou après (FR-010) |
| E2 | fleet (+ booking) | registre `MachineRetirementGuards` à la place du binding unique `MachineRetirementGuard` | refuser le retrait manuel d'une machine en vente (FR-016) sans écraser le garde des réservations |
| E3 | fleet (+ booking pour l'affichage) | registre `MachineBadges` | mention « en vente » dans le parc et le planning (FR-005) |
| E4 | billing | registre `BillableSources`, colonnes `source_type`/`source_id` sur `transmissions`, action `QueueSourceTransmission` | transmettre la vente avec toutes les garanties de 003 (FR-019 à FR-021) sans que billing connaisse sales |

**Arbitrage de la coordination (2026-10-10)** : les quatre modifications sont réalisées dans la branche 007 (Phase 2 de `tasks.md`, un commit par bloc), E1 et E2 uniquement après la remise à jour de la branche sur les corrections de 001 et 003. Elles restent génériques (aucune mention de la vente dans fleet, booking ou billing), la migration de billing est additive et réversible, et chaque bloc s'accompagne des tests de non-régression des features 001 et 003 concernées.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Livewire 4, Flux 2, `xefi/laravel-osdd` 2, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `spatie/laravel-activitylog`, `pusher/pusher-php-server` (Soketi). Aucun nouveau package.

**Storage**: PostgreSQL (index uniques partiels, CHECK, `num_nonnulls`)

**Testing**: PHPUnit 12 : Unit purs pour les calculs et transitions en mémoire, Feature pour tout ce qui passe par la base (constitution 1.0.1 annoncée) ; factories avec `faker()`, `travelTo()`, `FakeBillingGateway`, `AssertsRefusals::assertRefused` ; Larastan + `xefi/phpstan-xefi-rules` ; Pint

**Target Platform**: application web Laravel servie par Sail (Docker) ; worker de file et planificateur actifs en production

**Project Type**: application web monolithe Laravel en layers OSDD

**Performance Goals**: liste des ventes filtrée en moins d'une seconde pour quelques centaines de ventes ; badges chargés en une requête par fournisseur et par écran ; visibilité inter-agences en moins de 5 secondes (comme 001)

**Constraints**: aucune règle d'unicité ou de conflit seulement côté interface ; aucune requête dans une boucle ; fichiers de moins de 200 lignes ; aucune dépendance de fleet, booking, inspection ou billing vers sales

**Scale/Scope**: ~400 machines, quelques dizaines de ventes par an, 85 salariés, 7 agences ; 4 écrans, 2 tables, 1 migration additive dans billing

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principe | Application | Statut |
|---|---|---|
| I. Layers OSDD | Nouveau layer `functional/sales` généré par `osdd:*` ; sens `sales → billing → inspection → booking → fleet` ; aucune relation Eloquent ni import d'un layer inférieur vers sales (test `LayerBoundariesTest` dans sales) ; les layers inférieurs exposent des registres génériques (E1–E4) remplis depuis `SalesServiceProvider` ; sales ne modifie ni le schéma ni les fichiers d'un autre layer (la migration de `transmissions` appartient à billing et fait partie de E4) ; écrire des lignes au travers du modèle public d'un layer inférieur reste permis dans le sens des dépendances (création d'un `Customer` de booking au moment d'une offre, exactement comme le formulaire de réservation de booking) | ✅ (E1–E4 signalés à la coordination) |
| II. Garanties base et serveur | Index uniques partiels (une vente non annulée par machine, une offre acceptée par vente, une transmission par source) ; CHECK sur montants et champs liés à l'état ; verrou `machines … FOR UPDATE` partagé avec `CreateReservation` pour toute règle de conflit ; FK sans cascade ; total des ventes et ventes en retard calculés en base | ✅ |
| III. Cycles de vie | `SaleStatus` et `OfferStatus` : colonnes texte + enums backed ; pattern State pour les deux (une classe par état, `IllegalSaleTransitionException`, `IllegalOfferTransitionException`) ; dates en `Europe/Paris` ; montants stockés en centimes et manipulés en `Functional\Billing\Money\Money` via `MoneyCast` (`bdef4c6`) | ✅ |
| IV. Effets de bord explicites | Pas d'observer : la remise appelle explicitement `RetireMachine` et `QueueSourceTransmission` dans sa transaction ; `SaleChanged` dispatché après commit ; pas de `try/catch` (`rescue()` qui traduit `23505` et relance le reste) ; refus = classes `final` héritant de `RefusalException`, factories nommées, message technique anglais + clé `sales::refusals.*` (forme du socle, `e8cba36`) ; logiciel de facturation derrière `BillingGateway` (faux existant) ; transmission persistée en base et rattrapée par `billing:reconcile` | ✅ |
| V. Accès par permission | Permission `sales.manage` (routes, canal `sales`, `SaleControl`), seeder `SalesPermissionSeeder`, attribuée au rôle salarié (FR-012a) | ✅ |
| VI. Tests par scénario | Un test Feature par scénario d'acceptation (US1 à US5), tests Unit des états et des règles de date ; tests écrits avant l'implémentation dans chaque phase ; faux logiciel et `travelTo()` | ✅ |
| VII. Code simple | Code en anglais, textes dans `functional/sales/resources/lang/fr` ; une action par opération métier ; fichiers < 200 lignes ; aucun nouveau package | ✅ |

Re-check après la phase 1 (design) : aucun écart. Les quatre modifications de layers existants sont conformes au Principe I (points d'extension génériques, aucun nom de sales dans les layers inférieurs) ; arbitrage de la coordination : elles sont réalisées dans la branche 007 (Phase 2).

## Project Structure

### Documentation (this feature)

```text
specs/007-vente-occasion/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── extension-points.md
│   ├── billing-sale-line.md
│   └── screens.md
├── checklists/requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
functional/sales/                                  # nouveau layer
├── composer.json                                  # require functional/billing, booking, fleet
├── database/
│   ├── factories/SaleFactory.php, SaleOfferFactory.php
│   ├── migrations/2026_10_10_000070_create_sales_table.php
│   │              2026_10_10_000071_create_sale_offers_table.php
│   │              2026_10_10_000072_add_accepted_offer_foreign_key_to_sales_table.php
│   └── seeders/SalesPermissionSeeder.php, SalesDemoSeeder.php
├── resources/
│   ├── lang/fr/sales.php, history.php, refusals.php
│   └── views/livewire/sale-list, list-machine-form, sale-detail, sale-offers, machine-sale-history
├── routes/web.php, channels.php
├── src/
│   ├── Access/Controls/SaleControl.php
│   ├── Actions/ListMachineForSale, UpdateSaleListing, RecordOffer, AcceptOffer, RejectOffer,
│   │           WithdrawOffer, ChangePlannedHandoverDate, ReleaseSaleReservation, HandOverSale, CancelSale
│   ├── Billing/SaleBillableSource.php              # E4
│   ├── Enums/SaleStatus.php, OfferStatus.php, SaleTransition.php, OfferTransition.php
│   ├── Events/SaleChanged.php
│   ├── Exceptions/…                               # héritent de Fleet\Exceptions\RefusalException
│   ├── Guards/ReservedSaleReservationGuard.php    # E1
│   │          OpenSaleRetirementGuard.php         # E2
│   ├── Badges/SaleMachineBadges.php               # E3
│   ├── Livewire/SaleList, ListMachineForm, SaleDetail, SaleOffers, MachineSaleHistory
│   ├── Models/Sale.php, SaleOffer.php
│   ├── Providers/SalesServiceProvider.php
│   ├── Queries/SaleListQuery.php, HandoverConflicts.php
│   ├── States/Sale…State (4), Offer…State (4), fabriques, traits Refuses…Transitions
│   └── History/SaleHistory.php, Enums/SaleHistoryEvent.php
└── tests/
    ├── Concerns/BuildsSalesFixtures.php
    ├── Feature/ (un fichier par user story + concurrence, extension points, layer boundaries, écrans)
    └── Unit/ (états vente et offre, règles de date)

functional/booking/   (E1, E2, E3 affichage)  Contracts/ReservationRequestGuard.php, Extensions/ReservationRequestGuards.php,
                      Actions/CreateReservation.php, Queries/AvailableMachinesQuery.php, Providers/BookingServiceProvider.php,
                      views planning + availability-search
functional/fleet/     (E2, E3)  Extensions/MachineRetirementGuards.php, Extensions/MachineBadges.php,
                      Contracts/MachineBadgeProvider.php, ValueObjects/MachineBadge.php, Actions/RetireMachine.php,
                      Providers/FleetServiceProvider.php, Guards/UnrestrictedRetirement.php (supprimé), view machine-index
functional/billing/   (E4)  migration additive transmissions, Contracts/BillableSource.php, Extensions/BillableSources.php,
                      Transmissions/TransmissionSubject.php, Actions/QueueSourceTransmission.php, Enums/BillableLineType.php,
                      Lines/BillableLine.php (+ amountExclTax()), Exports/ExportLineFormatter.php, Actions/MakeBillableLine.php, History/BillingHistory.php,
                      Transmissions/TransmissionLifecycle.php, Livewire/Transmissions.php + vue
app/ et racine         composer.json (path repository + require functional/sales), phpunit.xml (source), phpstan.neon (paths),
                      database/seeders/DatabaseSeeder.php (SalesPermissionSeeder), resources/views/layouts/app/sidebar.blade.php
```

**Structure Decision**: dépôt unique, application Laravel à la racine ; le nouveau domaine est le layer `functional/sales`. Les modifications des layers existants sont limitées aux points d'extension E1 à E4 et à leurs appels.

## Déroulé de la remise (`HandOverSale`)

Une seule transaction :
1. verrou `machines` (même ordre que `CreateReservation`), puis verrou `sales` ;
2. état `Reserved` → `sell()` (sinon `IllegalSaleTransitionException`) ;
3. refus si une réservation de la machine est `in_progress` (machine sortie) ou `confirmed` (`SaleHandoverRefusedException` avec la liste) ;
4. vente → `sold`, `handed_over_on` = aujourd'hui (Paris), `handed_over_by` ;
5. si la machine n'est pas déjà `retired` : `RetireMachine::handle()` (gardes fleet : booking revérifie les réservations, sales ne voit plus de vente ouverte) ;
6. `QueueSourceTransmission::handle(BillableLineType::UsedMachineSale, $sale->id)` ;
7. `SaleHistory` ; `SaleChanged` dispatché après commit ; le job d'envoi part après commit (003).

Un refus à n'importe quelle étape annule tout : ni vente conclue, ni retrait, ni transmission.

## Prérequis et ordre

- Base : `origin/003-transmission-facturation` (001 + 002 + 003). Les corrections de conformité en cours sur 001 à 003 doivent être fusionnées et la branche 007 remise à jour par-dessus avant `speckit-implement` ; le plan suppose déjà leurs formes : `RefusalException` à constructeur protégé + `HasLabel` + `AssertsRefusals` (`e8cba36`), `ReservationTransition` (`e8cba36`, non utilisé par sales), `Money` / `MoneyCast` / `Exports/ExportLineFormatter` (`bdef4c6`).
- E1 à E4 : Phase 2 de 007 ; E1 et E2 après la remise à jour de la branche sur les corrections de 001 et 003.
- Aucune dépendance vers les features 004 à 006.

## Complexity Tracking

Aucune violation de la constitution à justifier.
