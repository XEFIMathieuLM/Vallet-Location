# Implementation Plan: Transmission des locations et des réparations au logiciel de facturation

**Branch**: `003-transmission-facturation` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/003-transmission-facturation/spec.md`

## Summary

Chaque location est découpée en périodes facturables : une par mois écoulé tant que la machine est dehors, puis une finale au retour. Chaque période, et chaque dégât chiffré au retour, est transmis automatiquement au logiciel de facturation du client. Rien ne se perd : une transmission qui échoue est relancée ou listée avec son motif, et un export de secours permet de tout faire passer par fichier. Rien n'est facturé deux fois : les garanties sont portées par la base et par une clé d'idempotence. Le logiciel applique ses propres tarifs aux locations ; seuls les dégâts sont transmis avec un montant.

Approche : un nouveau layer OSDD **`billing`** au-dessus de `inspection`, `booking` et `fleet`. Une file d'envoi persistante (outbox) en base, traitée par des jobs et rattrapée chaque minute par une commande planifiée. Les locations en cours à la mise en service sont transmises en entier depuis leur sortie. Le logiciel du client est isolé derrière un port `BillingGateway` : toute la feature se construit et se teste avec un faux logiciel, et seul l'adaptateur réel attend les informations du client.

## Technical Context

**Language/Version**: PHP 8.5 (runtime Sail), Laravel 13, Livewire 4 + Flux (identique à la 001 et la 002)

**Primary Dependencies**: existantes (001, 002). Aucun nouveau package : `spatie/simple-excel` (export CSV), `spatie/laravel-activitylog` (historique), client HTTP de Laravel pour l'adaptateur réel. Le pilote du logiciel de facturation sera choisi une fois le logiciel identifié (B2).

**Storage**: PostgreSQL, 5 nouvelles tables dans `billing` (`billable_periods` avec contrainte d'exclusion, `transmissions`, `damage_settlements`, `customer_billing_accounts`, `billing_exports`) ; fichiers d'export sur un disque privé `billing-exports`.

**Testing**: PHPUnit. Un test Feature par scénario d'acceptation, avec `FakeBillingGateway` et l'horloge contrôlée pour les fins de mois. Tests Unit pour les états de `Transmission` et le découpage en périodes. Larastan niveau ≥ 7 avec `xefi/phpstan-xefi-rules`.

**Target Platform**: serveur Linux (conteneurs), avec un worker de file et le planificateur Laravel actifs en production (déjà requis par la 001 pour le broadcasting).

**Project Type**: application web monolithique (rendu serveur + Livewire)

**Performance Goals**: 95 % des locations transmises dans les 5 minutes suivant le retour (SC-002) ; périodes de fin de mois transmises dans les 24 h (SC-001a). Volume faible : environ 400 machines, de l'ordre de quelques dizaines de transmissions par jour.

**Constraints**: zéro jour ni dégât facturé deux fois, y compris entre envoi automatique et export (SC-003). Le retour d'une machine ne dépend jamais de la disponibilité du logiciel de facturation (FR-006). Pas d'observers, pas de `try/catch` (`rescue()` et exceptions typées), pas de cascade en base (règles Xefi).

**Scale/Scope**: 5 tables, 2 commandes planifiées (dont `billing:reconcile` chaque minute), 1 job, 4 écrans plus une section de détail et un bandeau d'alerte.

**Dépendance externe** : le nom du logiciel de facturation, son moyen d'envoi automatique, son authentification, sa capacité à dédoublonner et son format d'import ne sont pas connus. Ils ne bloquent ni le plan ni l'implémentation, seulement l'adaptateur réel et donc la mise en production. Liste exacte des questions dans [contracts/billing-gateway.md](contracts/billing-gateway.md).

## Affected Repos

Dépôt unique : l'application Laravel à la racine du dépôt (pas de `repos.yml`, comme pour la 001 et la 002). Aucun autre dépôt touché.

**Dépendances** : cette feature s'appuie sur le code de la 001 (`Reservation`, `ReservationChanged`, et les points d'extension de `booking` : registre `ReservationDetailSections` des sections du détail de réservation, déplacé de la 002 vers la 001) et de la 002 (`Damage`, `ResolveDamage`, registre `DamageActions` des actions d'un dégât, B7). Emplacements vérifiés sur `fb3e51a` : `Functional\Booking\Extensions\ReservationDetailSections`, `Functional\Booking\Events\ReservationChanged` (`ShouldDispatchAfterCommit`), `Functional\Booking\Actions\ReturnReservation`, `Functional\Inspection\Support\DamageActions`, `Functional\Inspection\Actions\ResolveDamage` (lève `DamageAlreadyResolvedException`), `Functional\Inspection\Models\Damage` (vue : `ReservationView::label`). Elle ne modifie aucun fichier de ces deux features. Son implémentation démarre **après** que la 001 et la 002 sont commitées et que cette branche est mise à jour par-dessus.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Portes tirées de la constitution v1.0.0 (`.specify/memory/constitution.md`) :

| Principe | Porte | Statut |
|----------|-------|--------|
| I. Layers OSDD | nouveau domaine = nouveau layer `functional/billing` ; dépendances `billing → inspection → booking → fleet` ; aucun fichier d'un autre layer modifié, extension par `ReservationDetailSections` et `DamageActions` | ✅ B1, B6, B7 |
| II. Garanties en base et serveur | exclusion sur les périodes, unicité des transmissions et des règlements, CHECK, envoi en trois temps (réserver, appel hors transaction, régler) et export sous `lockForUpdate()` des transmissions non réservées ; pas de cascade ; agrégats du relevé en SQL | ✅ B4, B9, B10, data-model |
| III. Cycles de vie explicites | `Transmission` en pattern State (4 états, transitions interdites) ; statuts texte + enum ; dates en heure de Paris ; montants en centimes | ✅ B5, data-model |
| IV. Effets de bord et erreurs typées | listener sur `ReservationChanged`, job, commandes planifiées ; pas d'observer ; `rescue()` et exceptions typées ; logiciel de facturation derrière `BillingGateway` avec `FakeBillingGateway` ; file d'envoi persistée et rattrapée chaque minute | ✅ B2, B3, B5, B6 |
| V. Accès par permission | `billing.manage` attribuée au rôle salarié ; aucun nom de rôle dans le code | ✅ B11 |
| VI. Tests par scénario | un test Feature par scénario d'acceptation, écrit d'abord ; horloge contrôlée, faux logiciel ; Larastan à zéro erreur | ✅ tasks.md |
| VII. Code simple et lisible | packages existants (`simple-excel`, `activitylog`, client HTTP) ; code en anglais, textes traduits ; fichiers < 200 lignes, sans commentaire | à vérifier pendant l'implémentation (T061) |

**Résultat** : aucune violation.

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes.

## Project Structure

### Documentation (this feature)

```text
specs/003-transmission-facturation/
├── spec.md
├── plan.md              # ce fichier
├── research.md          # décisions B1–B11
├── data-model.md        # entités, contraintes, états, configuration
├── quickstart.md        # guide de vérification
├── contracts/
│   ├── billing-gateway.md   # port vers le logiciel de facturation
│   ├── export-format.md     # fichier d'export de secours
│   └── screens.md           # écrans
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
resources/views/layouts/
├── app.blade.php                  # + bandeau d'alerte billing (modifié)
└── app/sidebar.blade.php          # + menu Facturation (modifié)
composer.json, phpunit.xml, phpstan.neon, database/seeders/DatabaseSeeder.php  # enregistrement du layer (modifiés)
functional/
└── billing/                       # nouveau
    ├── composer.json              # LayerManifest : inspection, booking, fleet, paquets utilisés, provider Faker
    ├── config/
    │   ├── billing.php            # go_live_date, gateway, délais, seuils, disque
    │   └── filesystems.php        # disque privé billing-exports (fusionné par overrideConfigFrom)
    ├── src/
    │   ├── Models/                # BillablePeriod, Transmission, DamageSettlement, CustomerBillingAccount, BillingExport
    │   ├── Enums/                 # BillablePeriodKind, TransmissionStatus, TransmissionFailureReason, DamageOutcome, BillingHistoryEvent, BillingPermission
    │   ├── States/                # une classe par état de Transmission
    │   ├── Transmissions/         # TransmissionLifecycle (écritures d'état et historique d'une transmission)
    │   ├── Contracts/             # BillingGateway
    │   ├── Gateways/              # FakeBillingGateway ; adaptateur du logiciel client (quand identifié)
    │   ├── Lines/                 # BillableLine, RentalPeriodLine, DamageLine, RentalContext (ligne envoyée au logiciel)
    │   ├── Money/                 # Money, Currency, MoneyCast
    │   ├── Periods/               # DateRange, PeriodSplitter
    │   ├── Calendar/              # BillingCalendar (heure de Paris, date de mise en service)
    │   ├── History/               # BillingHistory (historique de la réservation)
    │   ├── Exports/               # ExportLineFormatter
    │   ├── ValueObjects/          # StatementFigures (résultat du relevé)
    │   ├── Actions/               # RecordFinalPeriod, RecordMonthEndPeriods, SendTransmission, RetryTransmission, BillDamage, WaiveDamage, CreateBillingExport, SetCustomerBillingRef
    │   ├── Queries/               # BillingStatement, TransmissionsToHandle
    │   ├── Jobs/                  # SendTransmissionJob
    │   ├── Listeners/             # période finale sur ReservationChanged
    │   ├── Console/               # billing:close-months, billing:reconcile, billing:fake-gateway (local et tests)
    │   ├── Exceptions/            # exceptions typées de billing
    │   ├── Faker/                 # BillingExtension et son provider (générateurs des factories)
    │   ├── Access/Controls/       # TransmissionControl, DamageSettlementControl, BillingExportControl
    │   ├── Providers/             # BillingServiceProvider (enregistrements dans les registres, liaison du gateway)
    │   └── Livewire/              # ReservationBillingSection, DamageBillingActions, Transmissions, Exports, Statement, BillingAlert
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views,lang/fr}/
    ├── routes/{web,console}.php   # /facturation/*, planification
    └── tests/{Feature,Unit}/
```

**Structure Decision**: un layer `billing` dans le même dépôt que la 001 et la 002. `booking` n'est pas modifié : la période finale est créée par un listener sur `ReservationChanged`, et la section « Facturation » passe par le registre des sections du détail exposé par `booking` (point d'extension livré par la 001). `inspection` n'est pas modifié non plus : le chiffrage remplace « Marquer traité » en s'enregistrant dans le registre `DamageActions` livré par la 002, sans qu'`inspection` connaisse `billing`. Le layout de `app/` inclut le bandeau d'alerte, puisque `app/` est la colle entre les layers.

## Complexity Tracking

Aucune violation à justifier.
