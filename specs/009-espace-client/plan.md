# Implementation Plan: Espace client et réservation en ligne

**Branch**: `009-espace-client` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/009-espace-client/spec.md`

## Summary

Un nouveau layer OSDD **`portal`**, au sommet du graphe (`portal → certification → booking → fleet`), ouvre l'outil aux clients sans changer ce que font les salariés.

- **Comptes clients** : un modèle `CustomerAccount` et sa table, un guard de session `customer` et un broker de mots de passe dédiés. Inscription, connexion, confirmation de l'e-mail et réinitialisation passent par des écrans Livewire du layer, sous `/espace-client`. Un client n'est jamais authentifié sur le guard `web` des salariés, donc n'ouvre aucun écran salarié (R2, R3).
- **Demandes** : table `reservation_requests`, cycle de vie en pattern State (`pending` → `confirmed` / `refused` / `cancelled` / `expired`). Une demande ne bloque rien.
  - **Recherche** : celle de la 001, `AvailableMachinesQuery` (R7).
  - **Confirmation** : appelle `CreateReservation` de la 001 telle quelle, dans une transaction qui verrouille la demande et le compte. Les règles 001 à 007 s'appliquent sans exception, et le compte est rattaché à une fiche à ce moment-là, par le salarié (R5, R6).
- **Côté salarié** :
  - un écran « Demandes en ligne », mis à jour en temps réel (canal privé `portal-requests`), avec un compteur dans la navigation ;
  - un écran « Prix indicatifs » ;
  - une section « Demande en ligne », non bloquante, dans le détail de la réservation (R11).
- **E-mails et expiration** : un job unique par demande envoie l'e-mail de décision. Une commande `portal:reconcile` passe les demandes échues en « expirée » et rattrape les e-mails non envoyés (R8).
- **Documents** : le client télécharge le rapport VGP du dernier envoi ou de la remise de la 005, en lecture seule (R13).

Aucun fichier d'un layer existant n'est modifié. Hors du layer, seule la colle de `app/` change : `config/auth.php`, `bootstrap/app.php`, la barre latérale, et l'enregistrement du layer.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Livewire 4, Flux 2, `xefi/laravel-osdd`, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `spatie/laravel-activitylog`, `pusher/pusher-php-server` (Soketi), authentification native de Laravel (guards, brokers, URL signées, `RateLimiter`). **Aucun nouveau package.**

**Storage**: PostgreSQL. Le layer `portal` crée 4 tables : `customer_accounts`, `customer_password_reset_tokens`, `reservation_requests`, `category_indicative_prices`. Une contrainte d'exclusion gist utilise `btree_gist`, déjà activée par la 001.

**Testing**: PHPUnit 12, dans `functional/portal/tests/`.
- **Feature** : un test par scénario d'acceptation, sur PostgreSQL.
- **Unit** : états de la demande et formatage du prix, sans framework (`PHPUnit\Framework\TestCase`).
- **Outils** : factories avec `faker()`, `travelTo()`, `Notification::fake()`, `Livewire::test()`, `actingAs($account, 'customer')`.
- **Qualité** : Larastan avec `xefi/phpstan-xefi-rules`, Pint.

**Target Platform**: application web servie par Sail (Docker) ; worker de file et planificateur actifs en production.

**Project Type**: monolithe Laravel en layers OSDD (dépôt unique, application à la racine).

**Performance Goals**:
- Recherche client aussi rapide que la recherche salarié : une requête plus les chargements anticipés (SC-002).
- Liste des demandes et espace client : nombre de requêtes constant, quel que soit le volume (pagination par 20, chargements anticipés). Vérifié par un test de nombre de requêtes.

**Constraints**:
- Aucune règle des features 001 à 008 modifiée ; aucun écran salarié changé hormis les ajouts listés par FR-036.
- Aucune requête dans une boucle ; fichiers de moins de 200 lignes ; pas de `try/catch`, pas d'observer, pas de cascade.

**Scale/Scope**: quelques dizaines de demandes par jour, ~400 machines, 7 agences. Livrables :
- 4 tables, 3 modèles ;
- 11 composants Livewire clients et salariés, 1 section de réservation, 1 badge ;
- 5 notifications, 1 job, 1 commande planifiée, 1 événement diffusé.

## Affected Repos

Dépôt unique : l'application Laravel à la racine. Il n'y a pas de `repos.yml`, comme pour les features 001 à 008.

**Prérequis** : `origin/main` à `ee02c93`, qui contient les features 001 à 008 fusionnées. Aucune autre branche n'est attendue. Aucun point d'extension manquant : tout ce que `portal` consomme existe ([contracts/extension-points.md](contracts/extension-points.md)).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principe | Application | Statut |
|---|---|---|
| I. Layers OSDD | Nouveau domaine, donc nouveau layer `functional/portal` généré par `osdd:*`. Dépendances `portal → certification → booking → fleet`, aucun layer ne dépend de `portal` (test `LayerBoundaryTest`). Extension par les points existants (`ReservationDetailSections`, `ReservationChanged`) et par les actions et requêtes publiques (`CreateReservation`, `AvailableMachinesQuery`). Aucune table ni aucun fichier d'un autre layer modifié. `app/` ne reçoit que de la colle : auth, redirections, navigation. | ✅ R1, R2, R11 |
| II. Garanties base et serveur | Une décision et au plus une réservation par demande : verrou de la demande, index unique sur `reservation_id`, CHECK de cohérence. Pas de doublon en attente : contrainte d'exclusion gist partielle. 10 demandes en attente au plus : décompte sous verrou du compte. Rattachement unique : verrou du compte et `UPDATE … WHERE customer_id IS NULL`. E-mail unique : index unique. Règles de réservation : celles de `CreateReservation` (contrainte d'exclusion de la 001, verrou de la machine). Aucune cascade. Comptes en SQL. | ✅ R5, R6, data-model |
| III. Cycles de vie explicites | La demande suit le pattern State : 5 classes, une fabrique et `IllegalReservationRequestTransitionException`. Statuts en texte avec enum PHP et CHECK. Expiration calculée en `Europe/Paris`. Prix en centimes entiers. | ✅ R4, R9 |
| IV. Effets de bord explicites | Pas d'observer. Les e-mails de décision passent par un job dispatché après commit et unique par demande. L'état est persisté (`customer_notified_at`, `status`) et `portal:reconcile` rattrape toutes les 5 minutes. Refus typés (`RefusalException`), `rescue()` sans `try/catch`. Le service d'envoi est le mailer de Laravel, déjà isolé (`Notification::fake()`, transport `array`, Mailpit), comme dans la 005. | ✅ R8 |
| V. Accès par permission | Côté salarié : `portal.handle-requests` et `portal.manage-prices`, déclarées dans `PortalPermissionSeeder` et données au rôle salarié. Côté client : guard `customer` et périmètre de propriété `OwnAccountPerimeter` (lomkit). Un compte client n'a pas de permission : voir Complexity Tracking. | ⚠️ justifié |
| VI. Tests par scénario | Un test Feature par scénario d'acceptation des US1 à US6, écrit d'abord dans chaque phase. Unit pour les états de la demande et le formatage du prix. Feature pour les contraintes en base (exclusion, unicité) et le nombre de requêtes. | ✅ |
| VII. Code simple | Code en anglais, textes dans `functional/portal/resources/lang/fr`, seuils dans `functional/portal/config/portal.php`, fichiers de moins de 200 lignes, aucun package nouveau. | ✅ |

**Re-check après la phase 1** : le modèle de données et les contrats respectent les portes. Il n'y a qu'un écart, la règle V côté client, justifié ci-dessous.

## Project Structure

### Documentation (this feature)

```text
specs/009-espace-client/
├── spec.md
├── plan.md                    # ce fichier
├── research.md                # décisions R1–R14
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── screens.md             # écrans client et salarié, navigation, section de réservation
│   ├── emails.md              # notifications et garanties d'envoi
│   └── extension-points.md    # ce que portal consomme ; fichiers modifiés hors du layer
├── checklists/requirements.md
└── tasks.md                   # /speckit-tasks
```

### Source Code (repository root)

```text
config/auth.php                                   # guard customer, provider et broker customer_accounts (modifié)
bootstrap/app.php                                 # redirectGuestsTo / redirectUsersTo selon portal.* (modifié)
resources/views/layouts/app/sidebar.blade.php     # groupe « Espace client » (modifié)
composer.json, phpunit.xml, phpstan.neon          # enregistrement du layer (modifiés)
database/seeders/DatabaseSeeder.php, tests/TestCase.php   # PortalPermissionSeeder (modifiés)

functional/portal/                                # nouveau layer
├── composer.json                                 # requiert booking, fleet, certification
├── config/portal.php                             # max_pending_requests, délais, longueurs
├── src/
│   ├── Access/                                   # PortalPermission ; Controls/ReservationRequestControl, IndicativePriceControl ; Perimeters/OwnAccountPerimeter
│   ├── Actions/                                  # RegisterCustomerAccount, SendReservationRequest, ConfirmReservationRequest, RefuseReservationRequest,
│   │                                             # CancelReservationRequest, ExpireReservationRequest, AttachCustomerAccount, SetIndicativePrice, RemoveIndicativePrice
│   ├── Auth/                                     # CustomerLoginThrottle
│   ├── Console/ReconcileCommand.php              # portal:reconcile
│   ├── Data/                                     # PortalMachineOffer, CustomerChoice
│   ├── Enums/                                    # ReservationRequestStatus, CustomerReservationStatus, PortalHistoryEvent, CustomerChoiceKind
│   ├── Events/ReservationRequestChanged.php      # diffusé sur portal-requests
│   ├── Exceptions/                               # refus typés (RefusalException) : RequestedMachineUnavailable, PendingRequestLimitReached,
│   │                                             # DuplicatePendingRequest, IllegalReservationRequestTransition, CustomerAccountAlreadyAttached,
│   │                                             # ConfirmationMachineMismatch, InvalidIndicativePrice
│   ├── History/PortalHistory.php
│   ├── Http/Controllers/                         # CustomerCertificateDownloadController, VerifyCustomerEmailController, LogoutController
│   ├── Jobs/NotifyRequestDecisionJob.php
│   ├── Livewire/
│   │   ├── Auth/                                 # Register, Login, ForgotPassword, ResetPassword, VerifyEmailNotice
│   │   ├── Customer/                             # Search, SendRequestForm, MyRequests, MyReservations, AccountSettings
│   │   └── Staff/                                # OnlineRequests, ConfirmRequestModal, RefuseRequestModal, IndicativePrices,
│   │                                             # PendingRequestsBadge, ReservationOriginSection
│   ├── Mail/                                     # VerifyCustomerEmailMail, ResetCustomerPasswordMail, ReservationRequest{Confirmed,Refused,Expired}Mail
│   ├── Models/                                   # CustomerAccount, ReservationRequest, CategoryIndicativePrice
│   ├── Notifications/                            # VerifyCustomerEmail, ResetCustomerPassword, ReservationRequestDecided
│   ├── Pricing/IndicativePriceFormatter.php
│   ├── Providers/PortalServiceProvider.php
│   ├── Queries/                                  # PortalMachineSearch, PendingRequests, AccountRequests, AccountReservations,
│   │                                             # AccountCertificateDocuments, SuggestedCustomers
│   └── States/                                   # ReservationRequestState + 5 états, fabrique, RefusesRequestTransitions
├── database/{migrations, factories, seeders/PortalPermissionSeeder.php}
├── resources/{lang/fr, views/{layouts, livewire, mail}}
├── routes/{web.php, channels.php, console.php}
└── tests/{Feature, Unit}
```

**Structure Decision** : dépôt unique, layer `portal` neuf, avec les écrans clients et salariés de la feature. Les fichiers de `app/` modifiés sont de la colle (authentification, navigation), conformément au `CLAUDE.md`.

## Prérequis et ordre

1. **Fondation** : layer, migrations, modèles, guard, permissions, états, layout client.
2. **US1** : compte client, séparation des espaces.
3. **US2** : recherche et envoi de demande.
4. **US3** : traitement en agence, rattachement, e-mails de décision.
5. **US4** : suivi, annulation, expiration.
6. **US5** : documents VGP.
7. **US6** : prix indicatifs. Le prix est affiché dès l'US2 si une ligne existe ; l'écran de saisie vient ici.
8. **Finitions** : nombre de requêtes, `LayerBoundaryTest`, non-régression, `composer ci:check`.

## Complexity Tracking

| Écart | Pourquoi c'est nécessaire | Alternative plus simple écartée parce que |
|---|---|---|
| Principe V : l'accès d'un client n'est pas contrôlé par une permission spatie, mais par le guard `customer` et un périmètre de propriété lomkit (`OwnAccountPerimeter`) | Un compte client n'a ni rôle ni droit variable : tout client peut faire exactement la même chose, et uniquement sur ses propres données. La règle qui protège est la propriété (FR-028), pas un droit. | Donner à chaque compte client une permission sur le guard `customer` : elle serait accordée à 100 % des comptes à l'inscription, ne contrôlerait rien et ajouterait un rôle et un seeder à maintenir. Le contrôle réel (le cloisonnement entre clients) resterait le périmètre de propriété. |
