# Implementation Plan: Transmission des locations et des réparations au logiciel de facturation

**Branch**: `003-transmission-facturation` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/003-transmission-facturation/spec.md`

## Summary

Chaque location est découpée en périodes facturables : une par mois écoulé tant que la machine est dehors, puis une finale au retour. Chaque période, et chaque dégât chiffré au retour, est transmis automatiquement au logiciel de facturation du client. Rien ne se perd : une transmission qui échoue est relancée ou listée avec son motif, et un export de secours permet de tout faire passer par fichier. Rien n'est facturé deux fois : les garanties sont portées par la base et par une clé d'idempotence. Le logiciel applique ses propres tarifs aux locations ; seuls les dégâts sont transmis avec un montant.

Approche : un nouveau layer OSDD **`billing`** au-dessus de `inspection`, `booking` et `fleet`. Une file d'envoi persistante (outbox) en base, traitée par des jobs et rattrapée chaque minute par une commande planifiée. Les locations en cours à la mise en service sont transmises en entier depuis leur sortie. Le logiciel du client est isolé derrière un port `BillingGateway` : toute la feature se construit et se teste avec un faux logiciel, et seul l'adaptateur réel attend les informations du client.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13, Livewire 4 + Flux (identique à la 001 et la 002)

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

**Dépendances** : cette feature s'appuie sur le code de la 001 (`Reservation`, `ReservationChanged`) et de la 002 (`Damage`, `ResolveDamage`, registre des sections du détail de réservation, registre `DamageActions` des actions d'un dégât, B7). Elle ne modifie aucun fichier de ces deux features. Son implémentation démarre **après** que la 001 et la 002 sont commitées et que cette branche est mise à jour par-dessus.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

`.specify/memory/constitution.md` est toujours le modèle vide. Comme pour la 001 et la 002, les conventions Xefi servent de portes :

| Porte | Statut |
|-------|--------|
| Stack et layout OSDD de la 001, nouveau domaine = nouveau layer | ✅ B1 |
| Sens de dépendance des layers respecté (`billing → inspection → booking → fleet`) | ✅ B1, B6, B7 (listener et points d'extension, aucune dépendance inverse) |
| Système externe isolé derrière une interface, testable sans lui | ✅ B2 |
| Packages existants plutôt que code maison (export, historique) | ✅ B9, B11 |
| Contrôles par permission, jamais par nom de rôle | ✅ B11 |
| Nouveau cycle de vie à plusieurs états et transitions interdites → pattern State | ✅ B5 (`Transmission`) |
| Pas d'observers ; réactions par listeners et commandes planifiées | ✅ B6 |
| Pas de `try/catch` : `rescue()` et exceptions typées | ✅ B5, contrat du gateway |
| Pas de cascade en base | ✅ data-model |
| Garanties portées par la base (exclusion, unicité, CHECK, verrou), pas par la doc | ✅ B4 |
| Montants en entiers (centimes) | ✅ data-model |
| Agrégats calculés en base pour le relevé | ✅ B10 |
| Fichiers de code < 200 lignes, code en anglais, textes traduits | à vérifier pendant l'implémentation |

**Résultat** : aucune violation. `/speckit-constitution` n'a toujours pas été lancé ; trois features reposent maintenant sur les mêmes portes implicites, c'est le moment de les inscrire.

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
app/
└── resources/views/…/layout       # + bandeau d'alerte billing (modifié)
layers/
└── billing/                       # nouveau
    ├── composer.json              # LayerManifest, dépend de inspection, booking, fleet
    ├── config/billing.php         # go_live_date, gateway, délais, seuils, disque
    ├── src/
    │   ├── Models/                # BillablePeriod, Transmission, DamageSettlement, CustomerBillingAccount, BillingExport
    │   ├── Enums/                 # BillablePeriodKind, TransmissionStatus, TransmissionFailureReason, DamageOutcome
    │   ├── States/                # une classe par état de Transmission
    │   ├── Contracts/             # BillingGateway
    │   ├── Gateways/              # FakeBillingGateway ; adaptateur du logiciel client (quand identifié)
    │   ├── ValueObjects/          # BillableLine (ligne envoyée au logiciel)
    │   ├── Actions/               # RecordFinalPeriod, RecordMonthEndPeriods, SendTransmission, RetryTransmission, BillDamage, WaiveDamage, CreateBillingExport, SetCustomerBillingRef
    │   ├── Queries/               # BillingStatement (agrégats du relevé)
    │   ├── Jobs/                  # SendTransmissionJob
    │   ├── Listeners/             # période finale sur ReservationChanged
    │   ├── Console/               # billing:close-months, billing:reconcile, billing:fake-gateway (local et tests)
    │   ├── Exceptions/            # BillingSoftwareRejectedException, BillingSoftwareUnreachableException, IllegalTransmissionTransitionException, DamageAlreadySettledException, NothingToExportException
    │   ├── Controls/              # TransmissionControl, DamageSettlementControl, BillingExportControl
    │   └── Livewire/              # ReservationBillingSection, DamageBillingActions, Transmissions, Exports, Statement, Alert
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views,lang/fr}/
    ├── routes/web.php             # /facturation/*
    └── tests/{Feature,Unit}/
config/filesystems.php             # + disque billing-exports
```

**Structure Decision**: un layer `billing` dans le même dépôt que la 001 et la 002. `booking` n'est pas modifié : la période finale est créée par un listener sur `ReservationChanged`, et la section « Facturation » passe par le registre des sections du détail ajouté par la 002. `inspection` n'est pas modifié non plus : le chiffrage remplace « Marquer traité » en s'enregistrant dans le registre `DamageActions` livré par la 002, sans qu'`inspection` connaisse `billing`. Le layout de `app/` inclut le bandeau d'alerte, puisque `app/` est la colle entre les layers.

## Complexity Tracking

Aucune violation à justifier.
