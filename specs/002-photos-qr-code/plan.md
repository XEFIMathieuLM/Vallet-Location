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

Vérifié contre la [constitution v1.0.0](../../.specify/memory/constitution.md) (ratifiée le 2026-10-10, après la conception de cette feature : la vérification ci-dessous porte sur le plan **et** sur le code livré).

| Principe | Statut | Où |
|----------|--------|----|
| **I. Layers OSDD** — nouveau domaine = nouveau layer généré par `osdd:layer` ; sens `inspection → booking → fleet` ; points d'extension remplis depuis le service provider ; aucun fichier d'un autre layer modifié | ✅ | layer `functional/inspection` ; guards et sections réalisés dans la 001 (T079 à T084) ; registre `DamageActions` exposé à la 003 ; T065 (aucune référence à `inspection` dans `booking` ni `fleet`) |
| **II. Garanties en base et au serveur** — refus serveur dans une transaction avec verrou ; contraintes en base ; pas de cascade ; agrégats en base | ✅ | `PhotosCompleteGuard` dans la transaction de sortie / retour ; `lockForUpdate()` sur la réservation pour l'ouverture d'une session, l'ajout et la suppression d'une photo (T068), sur la catégorie pour le réglage des vues, sur le dégât pour son traitement ; index uniques (`token_hash`, vue par catégorie, position par réservation) ; clés étrangères sans cascade ; vues manquantes et dégâts non traités comptés en SQL (`ViewCompleteness` en une requête `count(*) filter` pour les deux étapes, `CountUnresolvedDamages`), comme la position suivante d'une vue (`max`), la règle de la dernière vue (`count`) et les révocations (`select distinct`, nombre de lignes renvoyé par l'`update`) (T075) ; aucune requête dans une boucle : vues figées et vues par défaut insérées en une requête, renumérotation en un seul `decrement`, une entrée d'historique par révocation (T072) ; écritures liées (dégât et historique, révocation et historique) dans une même transaction (T073) |
| **III. Cycles de vie explicites** — statuts en texte + enum ; pattern State seulement pour plusieurs états avec transitions interdites ; heure de Paris | ✅ | `InspectionStep`, `RevocationReason` en enums ; `PhotoSession` (une seule transition, la révocation) et `Damage` (signalé → traité) sans pattern State, justifié dans [data-model.md](data-model.md) ; `APP_TIMEZONE=Europe/Paris` ; aucun montant |
| **IV. Effets de bord explicites, erreurs typées, systèmes externes isolés** | ✅ | pas d'observer : listener `RevokePhotoSessionsOnReservationChanged`, historique écrit par les actions ; pas de `try/catch` ; refus typés (sous-classes de `RefusalException`) ; stockage derrière le disque Laravel `photos` (local, S3, `Storage::fake` en test) ; purge par commande planifiée sur un état en base ; seules la grande version des photos (repli sur l'original) et les diffusions temps réel passent par la file, sans perte de donnée possible |
| **V. Accès par permission** — permissions déclarées dans un seeder du layer et données au rôle salarié | ✅ | `InspectionPermissionSeeder` (`damages.manage`, `inspection_views.manage`) ; routes `can:` ; `DamageControl`, `CategoryViewControl` ; la page téléphone n'agit que par son jeton ([phone-link.md](contracts/phone-link.md)) |
| **VI. Tests par scénario d'acceptation** — un test Feature par scénario, tests d'abord, factories, horloge contrôlée, PHPStan à zéro | ⚠️ | chaque scénario de US1 à US4 a son test Feature ; les transitions et calculs ont leurs tests Unit (`InspectionStep`, `FreezeReservationViews`, `MissingViews`, `StepCompleteness`, `ViewCompleteness`, règle de rétention, T076) ; `travelTo()` / `setTestNow()` ; PHPStan sans erreur. **Écart** : tests de US2 écrits après le code (voir Complexity Tracking) |
| **VII. Code simple et lisible** — anglais, textes traduits, fichiers < 200 lignes, pas de commentaire, packages justifiés | ✅ | textes dans `functional/inspection/resources/lang/fr` ; plus gros fichier de code 148 lignes ; paquets justifiés dans le Technical Context ; seuls les fichiers de configuration publiés par les paquets gardent leurs commentaires (voir Complexity Tracking) |
| **Contraintes techniques** — stack, Docker, worker de file et planificateur en production | ✅ | temps réel Soketi sur canaux privés à charge utile explicite ([broadcast-events.md](contracts/broadcast-events.md)) ; worker et planificateur requis (temps réel, conversions, purge nocturne) |
| **Workflow** — spec-kit dans l'ordre, prérequis déclarés, analyse sans problème critique, haut ou moyen avant l'implémentation | ⚠️ | prérequis 001 déclarés dans [tasks.md](tasks.md) ; analyses successives jusqu'à zéro problème critique ou haut. **Écart** : les parties indépendantes de la 001 ont été codées avant le premier `/speckit-analyze` (voir Complexity Tracking) |

**Résultat** : aucune violation ouverte. Le premier `/speckit-analyze` mené avec la v1.0.0 avait relevé deux violations du principe II (requêtes dans des boucles, écritures liées hors transaction), corrigées par T072 et T073 ; l'analyse suivante a relevé des agrégats encore calculés en PHP et des calculs sans test Unit, corrigés par T075 à T077. Deux écarts de méthode, passés, sont documentés ci-dessous, sans effet sur le code livré.

**Re-check post-design et post-implémentation** : le modèle de données, les contrats et le code respectent les principes I à VII.

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

| Écart | Pourquoi | Effet et rattrapage |
|-------|----------|---------------------|
| Tests de US2 écrits après le code (principe VI) | `PhotosCompleteGuard::beforeReturn` et le figement au retour ont été codés avec US1, dont ils partagent le code | Aucun sur le comportement : T039 à T041 couvrent tous les scénarios de US2 et passent ; l'écart est signalé dans la PR |
| Parties indépendantes codées avant le premier `/speckit-analyze` (workflow) | Reprise de la feature sur un autre poste, avant que le workflow strict soit rappelé | Régularisé : `/speckit-analyze` relancé à chaque étape jusqu'à zéro problème critique ou haut ; les constats (dont la faille de concurrence D1) ont été corrigés |
| Commentaires dans `config/livewire.php` et `config/media-library.php` (principe VII) | Fichiers publiés tels quels par les paquets ; seules les valeurs utiles ont été modifiées | Toléré : ce ne sont pas des fichiers écrits pour le projet |
