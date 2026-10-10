# Implementation Plan: Photos de départ et de retour via QR code

**Branch**: `002-photos-qr-code` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/002-photos-qr-code/spec.md`

## Summary

Le poste d'agence affiche un QR code ; le salarié le scanne avec son téléphone et photographie les vues imposées de la machine. Les photos arrivent en direct dans la réservation. La sortie et la clôture sont refusées par le serveur tant qu'une vue n'a pas sa photo. Au retour, le salarié compare départ et retour vue par vue et signale les dégâts, qui alimentent une liste « à refacturer ».

Approche : un nouveau layer OSDD **`inspection`** au-dessus de `booking` et `fleet` (feature 001). Le blocage passe par un **point d'extension** ajouté à `booking` (guards de transition), pour que `booking` ne dépende jamais d'`inspection`. Le lien du QR code est un jeton aléatoire haché, révocable, valable 30 min. Les photos sont réduites dans le navigateur du téléphone, stockées par `spatie/laravel-medialibrary` sur un disque privé, et diffusées en temps réel par Soketi sur un canal par réservation.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13, Livewire 4 + Flux (identique à la 001)

**Primary Dependencies**: existantes (001) + `spatie/laravel-medialibrary` (nouveau : fichiers, conversions et suppression des fichiers avec le modèle, plutôt que du code maison), `bacon/bacon-qr-code` (déjà installé par Fortify, déclaré explicitement par le layer), `league/flysystem-aws-s3-v3` (nouveau : adaptateur officiel Laravel pour le disque `photos` en S3 en production), Alpine (fourni par Livewire) pour la réduction des photos côté téléphone. En local, un service S3 facultatif SeaweedFS (profil `s3` de `compose.yaml`) permet de tester le stockage S3 ; il remplace MinIO, dont les images ne sont plus publiées sur Docker Hub ni sur quay.io.

**Storage**: PostgreSQL (6 nouvelles tables : 5 pour `inspection`, plus `media` de la médiathèque) ; fichiers sur un disque privé `photos` (local en dev, S3-compatible en production)

**Testing**: PHPUnit — un test Feature par scénario d'acceptation, tests Unit pour la complétude des vues et la validité du jeton. Larastan niveau ≥ 7 avec `xefi/phpstan-xefi-rules`.

**Target Platform**: serveur Linux (conteneurs) ; postes d'agence (navigateur) ; smartphones iOS et Android (navigateur ; l'appareil photo passe par un champ d'envoi de fichier, qui fonctionne aussi en HTTP ; HTTPS en production)

**Project Type**: application web monolithique (rendu serveur + Livewire), une page publique mobile

**Performance Goals**: photo visible sur le poste moins de 5 s après la prise (SC-004) ; prise des 5 vues de départ en moins de 3 min (SC-003)

**Constraints**: blocage garanti côté serveur, pas seulement par un bouton désactivé. Le jeton ne doit donner accès à rien d'autre que l'ajout de photos sur une réservation et une étape. Pas d'observers, pas de `try/catch`, pas de cascade en base (règles Xefi).

**Scale/Scope**: environ 400 machines, 2 étapes × 5 vues par location, environ 600 Ko par photo réduite ; 5 écrans (panneau photos, comparaison, dégâts, vues par catégorie, page téléphone)

## Affected Repos

Dépôt unique : l'application Laravel à la racine du dépôt (pas de `repos.yml`, comme pour la 001). Aucun autre dépôt touché.

**Dépendance** : cette feature s'appuie sur deux points d'extension du layer `booking` (voir P2 et P3 de [research.md](research.md)). Génériques, ils sont réalisés **dans la 001**, avec les actions de sortie et de retour et l'écran de détail ; cette branche ne modifie aucun fichier de `booking`. Les parties d'`inspection` qui n'en dépendent pas ont démarré dès la phase 2 de la 001 ; le blocage de la sortie et du retour s'appuie sur sa phase 5, désormais livrée (registres `Functional\Booking\Extensions\ReservationTransitionGuards` et `ReservationDetailSections`, voir [tasks.md](tasks.md#️-prérequis--feature-001)).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

`.specify/memory/constitution.md` est toujours le modèle vide. Comme pour la 001, les conventions Xefi servent de portes :

| Porte | Statut |
|-------|--------|
| Stack et layout OSDD de la 001, nouveau domaine = nouveau layer | ✅ P1 |
| Sens de dépendance des layers respecté (`inspection → booking → fleet`) | ✅ P2, P3 (points d'extension) |
| Packages recommandés plutôt que code maison (médias, QR code, audit) | ✅ P5, P7, data-model |
| Contrôles par permission, jamais par nom de rôle | ✅ P12 |
| Cycles de vie : State seulement si plusieurs états et transitions ; ici états dérivés ou à une transition | ✅ data-model (`PhotoSession`, `Damage`) |
| Pas d'observers ; réactions par listeners (révocation à l'annulation) | ✅ data-model |
| Rétention par `Prunable` (pas `MassPrunable`, fichiers à supprimer) | ✅ P9 |
| Pas de cascade en base | ✅ data-model |
| Garanties portées par le serveur (guard dans la transaction de sortie / retour) | ✅ P2 |
| Données exposées publiquement réduites au strict nécessaire | ✅ [phone-link.md](contracts/phone-link.md) |
| Fichiers de code < 200 lignes, code en anglais, textes traduits | à vérifier pendant l'implémentation |

**Résultat** : aucune violation. La 001 recommandait `/speckit-constitution` avant la 2e feature : toujours pas fait. Non bloquant, mais c'est le bon moment pour inscrire ces principes.

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes.

## Project Structure

### Documentation (this feature)

```text
specs/002-photos-qr-code/
├── spec.md
├── plan.md              # ce fichier
├── research.md          # décisions P1–P12
├── data-model.md        # entités, règles, purge
├── quickstart.md        # guide de vérification
├── contracts/
│   ├── screens.md           # écrans poste et téléphone
│   ├── phone-link.md        # jeton du QR code
│   └── broadcast-events.md  # temps réel
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
functional/
├── booking/                          # 001 (points d'extension réalisés dans la 001)
│   └── src/
│       ├── Contracts/                # ReservationTransitionGuard (001, T079)
│       ├── Extensions/               # registres : guards, sections du détail (001, T080 et T083)
│       ├── Actions/                  # DepartReservation, ReturnReservation : appellent les guards
│       └── Livewire/                 # détail de réservation : rend les sections enregistrées
└── inspection/                       # nouveau
    ├── composer.json                 # LayerManifest, dépend de booking et fleet
    ├── config/inspection.php         # vues par défaut, durée du jeton, taille max
    ├── src/
    │   ├── Models/                   # CategoryView, ReservationView, PhotoSession, Photo, Damage
    │   ├── Enums/                    # InspectionStep, RevocationReason
    │   ├── Actions/                  # OpenPhotoSession, FindActivePhotoSession, RevokePhotoSessions, StorePhoto, DeletePhoto, FreezeReservationViews, ResolveRequiredViews, MissingViews, ReportDamage, ResolveDamage, CountUnresolvedDamages, CategoryViews/*
    │   ├── Guards/                   # PhotosCompleteGuard
    │   ├── Queries/                  # ReservationsToReinvoice
    │   ├── Support/                  # DamageActions (point d'extension pour la 003), InspectionHistory (journal), QrCodeSvg
    │   ├── Exceptions/               # refus typés (sous-classes de RefusalException)
    │   ├── Events/                   # PhotoChanged, PhotoSessionChanged, DamageChanged
    │   ├── Listeners/                # révocation des sessions sur ReservationChanged
    │   ├── Access/Controls/          # DamageControl, CategoryViewControl (les photos sont protégées par la permission des routes)
    │   ├── Http/Controllers/         # PhotoFileController : photos servies aux salariés connectés
    │   ├── Http/Middleware/          # en-têtes noindex / no-referrer sur /photos/*
    │   └── Livewire/                 # PhotosPanel, PhoneCapture, Comparison, DamagesList, CategoryViewsIndex, CategoryViews
    ├── database/{migrations,factories,seeders}/
    ├── resources/{views,js,lang/fr}/ # js : réduction des photos (Alpine)
    ├── routes/web.php                # /photos/{token} (public), écrans poste
    ├── routes/channels.php           # canal privé reservation.{id}
    ├── routes/console.php            # purge nocturne des photos (model:prune)
    └── tests/{Feature,Unit,Concerns,Fixtures}/
config/filesystems.php                # + disque photos
compose.yaml                    # + S3 local (SeaweedFS, profil s3), facultatif
```

**Structure Decision**: un layer `inspection` dans le même dépôt que la 001. `inspection` expose à son tour le registre `DamageActions` (P13), que la feature 003 remplira pour remplacer « Marquer traité ». Le layer `fleet` n'est pas modifié : l'écran des vues par catégorie appartient à `inspection` et a sa propre entrée de menu. `booking` gagne deux points d'extension génériques (guards de transition, sections du détail) qu'`inspection` remplit depuis son service provider. Ces points d'extension resserviront à la caution (guard de départ) sans nouvelle modification de `booking`.

## Complexity Tracking

Aucune violation à justifier.
