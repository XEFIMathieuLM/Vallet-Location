# Implementation Plan: Envoi automatique de l'attestation VGP au client

**Branch**: `005-attestation-vgp` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/005-attestation-vgp/spec.md`

## Summary

Chaque machine soumise à VGP reçoit ses rapports de VGP successifs, déposés par les agences ; le dernier est le rapport en vigueur et sa date d'échéance devient celle de la machine. Chaque réservation confirmée d'une machine soumise à VGP porte une **attestation** : une obligation persistée d'envoyer le rapport en vigueur au client. L'attestation part par e-mail sans action du salarié, est relancée tant que l'échec est temporaire, et figure dans une liste à traiter tant qu'elle n'est ni envoyée ni remise en main propre. La sortie de la machine est refusée par le serveur tant que l'attestation n'est pas livrée.

Approche : un nouveau layer OSDD **`certification`** au-dessus de `booking` et `fleet`. L'attestation est créée par un listener sur `ReservationChanged` et, pour tout ce que l'événement aurait manqué (dont les réservations déjà confirmées à la mise en service), par une commande de rattrapage planifiée chaque minute. L'envoi passe par un job unique par attestation, sous verrou, qui envoie une Notification dont `toMail()` rend un Mailable avec le rapport en pièce jointe. Le blocage de sortie est un `ReservationTransitionGuard` enregistré dans le registre de `booking`.

## Technical Context

**Language/Version**: PHP 8.5 (runtime Sail), Laravel 13, Livewire 4 + Flux (identique aux features 001 à 003)

**Primary Dependencies**: existantes. Aucun nouveau package : notifications et mail de Laravel (transport SMTP, Mailpit en local), `spatie/laravel-activitylog` (historiques), stockage de fichiers de Laravel, `spatie/laravel-permission` et `lomkit/laravel-access-control`.

**Storage**: PostgreSQL, 3 nouvelles tables dans `certification` (`vgp_reports`, `reservation_certificates`, `certificate_dispatches`) ; fichiers des rapports sur un disque privé `vgp-reports`.

**Testing**: PHPUnit. Un test Feature par scénario d'acceptation avec `Notification::fake()` / `Mail::fake()`, un transport mail factice qui simule les pannes et les refus, l'horloge contrôlée (`travelTo()`) et `Storage::fake('vgp-reports')`. Tests Unit pour les états d'attestation et la classification des échecs d'envoi. Larastan avec `xefi/phpstan-xefi-rules` à zéro erreur.

**Target Platform**: serveur Linux (conteneurs), worker de file et planificateur actifs en production (déjà requis par la 001 et la 003).

**Project Type**: application web monolithique (rendu serveur + Livewire)

**Performance Goals**: 95 % des attestations parviennent au client dans les 5 minutes (SC-003) ; volume faible (quelques dizaines de réservations de machines VGP par jour, environ 400 machines).

**Constraints**: zéro envoi automatique en double (SC-004) ; la création de réservation ne dépend jamais de l'envoi (FR-008) ; zéro sortie sans attestation livrée (SC-001). Pas d'observers, pas de `try/catch`, pas de cascade, pas de requête dans une boucle.

**Scale/Scope**: 3 tables, 1 commande planifiée (`certification:reconcile` chaque minute), 1 job, 1 notification + 1 mailable, 1 garde de sortie, 3 écrans (machines VGP, page VGP d'une machine, attestations à traiter), 1 section du détail de réservation, 1 bandeau d'alerte.

## Affected Repos

Dépôt unique : l'application Laravel à la racine du dépôt (pas de `repos.yml`, comme pour les features 001 à 003). Aucun autre dépôt touché.

**Dépendances** : cette feature s'appuie sur la 001 telle que corrigée par la session de coordination (commit `e8cba36` et suivants) :

- `Functional\Booking\Events\ReservationChanged` (`ShouldDispatchAfterCommit`), `Reservation`, `Customer`, `ReservationStatus` ;
- le registre `Functional\Booking\Extensions\ReservationTransitionGuards` et le contrat `ReservationTransitionGuard` (appelé dans la transaction de `DepartReservation`, réservation verrouillée) ;
- le registre `ReservationDetailSections` et l'événement Livewire `reservation-transition-readiness` portant `step = ReservationTransition::Departure->value` ;
- `abstract Functional\Fleet\Exceptions\RefusalException` (constructeur protégé, clé de traduction, `userMessage()`) et `AssertsRefusals::assertRefused` pour les tests ;
- `Functional\Fleet\Actions\UpdateMachineVgp` (met à jour l'échéance et émet `MachineChanged`, qui recalcule les conflits).

Elle ne dépend pas de la 002 ni de la 003 ; elle se cumule avec leurs gardes de sortie (photos 002, caution 004) par le registre de la 001.

**Points d'extension manquants dans la 001** (voir [research.md](research.md) C9, C10) — à livrer dans `booking` avant l'implémentation, par la session de coordination ou en phase de prérequis de cette feature :

1. **Modification de l'e-mail d'un client** : action `UpdateCustomerEmail` et événement `CustomerChanged` (`ShouldDispatchAfterCommit`) dans `booking`. Sans eux, `certification` devrait écrire dans la table `customers` d'un autre layer (interdit par le principe I).
2. **Disponibilité d'une étape agrégée par section** : aujourd'hui `ReservationDetail::$readinessBySteps[$step]` garde le dernier booléen reçu, toutes sections confondues ; avec deux sections ou plus qui conditionnent la sortie (photos, caution, attestation), la dernière qui parle l'emporte. Il faut un état par section et par étape, l'étape étant prête quand toutes les sections qui se sont prononcées le sont. En attendant, la section attestation n'émet pas de disponibilité et le refus serveur reste la garantie.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Portes tirées de la constitution v1.0.0 (et des précisions 1.0.1 annoncées : calculs en mémoire → Unit, en base → Feature) :

| Principe | Porte | Statut |
|----------|-------|--------|
| I. Layers OSDD | nouveau domaine = nouveau layer `functional/certification` ; dépendances `certification → booking → fleet` ; extension par `ReservationTransitionGuards`, `ReservationDetailSections` et les événements `ReservationChanged` / `CustomerChanged` ; appels aux actions publiques des layers inférieurs (`UpdateMachineVgp`, `UpdateCustomerEmail`) ; aucun fichier ni table d'un autre layer modifié par `certification` | ✅ C1, C9, C10 (les deux points d'extension sont livrés dans `booking` lui-même) |
| II. Garanties en base et serveur | une attestation par réservation (index unique) ; un seul envoi automatique réussi (état sous `lockForUpdate()`, index unique partiel sur l'envoi automatique réussi) ; garde de sortie dans la transaction verrouillée de `DepartReservation` ; CHECK sur les états et les canaux ; pas de cascade ; compteurs et liste en SQL | ✅ C4, C5, C6, data-model |
| III. Cycles de vie explicites | attestation en pattern State (6 états, transitions interdites, `IllegalCertificateTransitionException`) ; statuts texte + enums ; dates en heure de Paris | ✅ C4, data-model |
| IV. Effets de bord et erreurs typées | listeners sur événements typés, job, commande planifiée ; pas d'observer ; `rescue()` et exceptions typées (`RefusalException` pour les refus) ; service d'envoi isolé derrière le mailer de Laravel, avec transport factice pour les tests et Mailpit en local ; état persisté + rattrapage chaque minute | ✅ C3, C5, C7 |
| V. Accès par permission | `certification.manage` déclarée dans le seeder du layer et attribuée au rôle salarié | ✅ C11 |
| VI. Tests par scénario | un test Feature par scénario d'acceptation, écrit d'abord ; Unit pour les états et la classification des échecs ; horloge contrôlée, faux transport, `Storage::fake` | ✅ tasks.md |
| VII. Code simple et lisible | aucun nouveau package ; code en anglais, textes en français dans `functional/certification/resources/lang/fr` ; fichiers < 200 lignes, sans commentaire | à vérifier pendant l'implémentation |

**Résultat** : aucune violation, sous réserve des deux points d'extension de la 001 ci-dessus.

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes ; la garde de sortie ne modifie ni ne remplace les gardes existantes.

## Project Structure

### Documentation (this feature)

```text
specs/005-attestation-vgp/
├── spec.md
├── plan.md              # ce fichier
├── research.md          # décisions C1–C12
├── data-model.md        # entités, contraintes, états, configuration
├── quickstart.md        # guide de vérification
├── contracts/
│   ├── certificate-email.md   # contenu de l'e-mail et classification des échecs
│   ├── extension-points.md    # ce que la feature consomme et demande à booking
│   └── screens.md             # écrans et section du détail
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
resources/views/layouts/
├── app.blade.php                  # + bandeau d'alerte certification (modifié)
└── app/sidebar.blade.php          # + entrées « Rapports VGP » et « Attestations à traiter » (modifié)
composer.json, phpunit.xml, phpstan.neon, database/seeders/DatabaseSeeder.php  # enregistrement du layer (modifiés)
config/filesystems.php             # + disque privé vgp-reports
functional/
├── booking/                       # points d'extension demandés (si livrés par cette feature)
│   └── src/{Actions/UpdateCustomerEmail.php, Events/CustomerChanged.php}, Livewire/ReservationDetail.php (disponibilité par section)
└── certification/                 # nouveau
    ├── composer.json              # LayerManifest, dépend de booking et fleet
    ├── config/certification.php   # go_live_date, délais de relance, seuil d'alerte, formats et taille, disque
    ├── src/
    │   ├── Models/                # VgpReport, ReservationCertificate, CertificateDispatch
    │   ├── Enums/                 # CertificateStatus, DispatchChannel, DispatchOutcome, DispatchFailureReason
    │   ├── States/                # une classe par état d'attestation + fabrique
    │   ├── Actions/               # DepositVgpReport, OpenReservationCertificate, ResolveCertificateReadiness, SendCertificate, ResendCertificate, RecordHandDelivery
    │   ├── Notifications/         # VgpCertificateNotification (toMail → Mailable)
    │   ├── Mail/                  # VgpCertificateMail (rapport en pièce jointe)
    │   ├── Jobs/                  # SendCertificateJob (unique par attestation)
    │   ├── Listeners/             # OpenCertificateOnReservationChanged, ResolveCertificatesOnReportDeposited, ResolveCertificatesOnCustomerChanged
    │   ├── Events/                # VgpReportDeposited, CertificateChanged (diffusé sur le canal privé fleet)
    │   ├── Guards/                # CertificateDeliveredGuard
    │   ├── Support/               # CertificationCalendar (mise en service), CertificationHistory, DispatchFailureClassifier
    │   ├── Queries/               # CertificatesToHandle
    │   ├── Console/               # certification:reconcile
    │   ├── Exceptions/            # CertificateNotDeliveredException, InvalidVgpReportException, CertificateNotResendableException, HandDeliveryRefusedException, IllegalCertificateTransitionException, MissingGoLiveDateException
    │   ├── Http/Controllers/      # VgpReportFileController (téléchargement sous permission)
    │   ├── Access/Controls/       # VgpReportControl, ReservationCertificateControl
    │   ├── Providers/             # CertificationServiceProvider
    │   └── Livewire/              # VgpMachines, MachineVgpReports, CertificatesToHandle, ReservationCertificateSection, CertificationAlert
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views (dont mail/), lang/fr}/
    ├── routes/{web.php,console.php}   # /vgp/*, planification
    └── tests/{Feature,Unit}/
```

**Structure Decision**: un layer `certification` dans le même dépôt. Il consomme les points d'extension de `booking` (registre des gardes, registre des sections, événements) et les actions publiques de `fleet` et `booking`, sans modifier leurs fichiers ni écrire dans leurs tables. Les deux points d'extension manquants sont des capacités de `booking` (modifier l'e-mail d'un client, agréger la disponibilité des sections) et sont livrés dans `booking`. Le layout de `app/` inclut le bandeau d'alerte et les entrées de menu, puisque `app/` est la colle entre les layers.

## Complexity Tracking

Aucune violation à justifier.
