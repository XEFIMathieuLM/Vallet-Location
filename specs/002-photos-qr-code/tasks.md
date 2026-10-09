---

description: "Task list for feature 002-photos-qr-code"
---

# Tasks: Photos de départ et de retour via QR code

**Input**: Design documents from `specs/002-photos-qr-code/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/screens.md), [quickstart.md](quickstart.md)

**Tests**: Inclus, comme pour la 001 : un test Feature PHPUnit par scénario d'acceptation. Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : feature 001

Cette feature s'appuie sur le code de la 001 **jusqu'à sa phase 5 incluse** (US3 : `DepartReservation`, `ReturnReservation`, `CancelReservation`, écran `ReservationDetail`, événement `ReservationChanged`). Avant T001 :

1. la 001 est implémentée jusqu'à T058 et commitée ;
2. la branche `002-photos-qr-code` est mise à jour par-dessus la branche de la 001 ;
3. `./vendor/bin/sail artisan test` passe sur la branche mise à jour.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US4)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : Sail pour toutes les commandes, code en anglais, textes dans `layers/inspection/resources/lang/fr/*.php`, statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (utiliser `rescue()` + rethrow), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers de code < 200 lignes. En plus :

- `inspection` dépend de `booking` et `fleet` ; **aucun fichier de `booking` ni de `fleet` n'importe une classe d'`inspection`**.
- `Reservation` et `MachineCategory` ne déclarent aucune relation vers les tables d'`inspection`.
- Le jeton du QR code n'est jamais stocké, journalisé ni diffusé en clair : seul son SHA-256 est en base.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `inspection`, médiathèque, disque de stockage, permissions.

- [ ] T001 Installer `spatie/laravel-medialibrary` (`./vendor/bin/sail composer require spatie/laravel-medialibrary`), publier et migrer sa migration `media` dans `database/migrations/`, publier `config/media-library.php` avec `queue_conversions_by_default = true` ; publier `config/livewire.php` et fixer `temporary_file_upload.rules` à `['required', 'file', 'max:15360']` ; relever `upload_max_filesize` et `post_max_size` à 16M dans la configuration PHP de Sail (`docker-compose.yml` / `php.ini` du conteneur)
- [ ] T002 Ajouter le disque privé `photos` dans `config/filesystems.php` (driver lu depuis `PHOTOS_DISK_DRIVER`, `local` par défaut sur `storage/app/private/photos`, `s3` en production avec `PHOTOS_S3_*`) et les variables dans `.env.example`
- [ ] T003 Créer le layer OSDD `layers/inspection/` (`composer.json` LayerManifest déclarant la dépendance à `booking` et `fleet`, namespace PSR-4, service provider, dossiers `src/`, `config/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `resources/views/`, `resources/js/`, `resources/lang/fr/`, `routes/`, `tests/Feature/`, `tests/Unit/`) ; enregistrer sa suite de tests dans `phpunit.xml`
- [ ] T004 [P] Créer `layers/inspection/config/inspection.php` : `default_views` = `['Avant', 'Arrière', 'Gauche', 'Droite', 'Compteur d\'heures']` (clés de traduction), `session_lifetime_minutes` = 30, `max_photo_kilobytes` = 15360, `allowed_mimes` = `jpeg, png, webp` ; le charger depuis le service provider du layer
- [ ] T005 [P] Ajouter les permissions `damages.manage` et `inspection_views.manage` au rôle `salarie` dans `layers/inspection/database/seeders/InspectionPermissionSeeder.php`, appelé depuis `database/seeders/DatabaseSeeder.php`
- [ ] T006 [P] Créer le canal privé `reservation.{reservation}` dans `routes/channels.php`, autorisé pour tout utilisateur ayant la permission `reservations.manage`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Points d'extension de `booking`, modèles d'`inspection`, figement des vues. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### Points d'extension de `booking` (modifie la 001)

- [ ] T007 Créer l'interface `ReservationTransitionGuard` dans `layers/booking/src/Contracts/ReservationTransitionGuard.php` avec `beforeDeparture(Reservation $reservation): void` et `beforeReturn(Reservation $reservation): void` (docblock : lève une exception de domaine pour refuser)
- [ ] T008 Créer le registre `ReservationTransitionGuards` (singleton, `register(class-string<ReservationTransitionGuard>)`, `all()`) dans `layers/booking/src/Support/ReservationTransitionGuards.php`, lié dans le service provider de `booking`
- [ ] T009 Appeler `beforeDeparture()` de chaque guard enregistré dans `layers/booking/src/Actions/DepartReservation.php` et `beforeReturn()` dans `layers/booking/src/Actions/ReturnReservation.php`, **dans la transaction et avant tout changement d'état** (depends on T008)
- [ ] T010 Test Feature dans `layers/booking/tests/Feature/ReservationTransitionGuardsTest.php` : un guard de test qui refuse bloque la sortie et le retour, sans modifier la réservation ni la machine ; sans guard enregistré, le comportement de la 001 est inchangé (depends on T009)
- [ ] T011 Créer le registre `ReservationDetailSections` (`register(string $livewireComponent, int $position)`, `all()` trié) dans `layers/booking/src/Support/ReservationDetailSections.php`, lié dans le service provider de `booking` ; rendre chaque section enregistrée dans la vue de `layers/booking/src/Livewire/ReservationDetail.php` via `<livewire:dynamic-component :component="…" :reservation="$reservation" :key="…" />`
- [ ] T012 Dans `layers/booking/src/Livewire/ReservationDetail.php` : afficher le message de toute exception de domaine levée par un guard comme refus de l'action (même rendu que les refus de la 001) ; écouter l'événement Livewire générique `reservation-transition-readiness` `{ step, is_ready }` et désactiver « Enregistrer la sortie » (`step = departure`) ou « Enregistrer le retour » (`step = return`) **tant qu'une section enregistrée n'a pas répondu `is_ready = true`** pour cette étape ; sans section enregistrée, les boutons restent actifs comme dans la 001. Le refus serveur reste la seule garantie (depends on T009, T011)

### Modèles d'`inspection`

- [ ] T013 [P] Créer l'enum `InspectionStep` (`departure`, `return`) avec `label()` traduit et `isOpenFor(Reservation $reservation): bool` — `departure` : « `confirmed` et `today >= start_date` » ; `return` : « `in_progress` » — et `isValidatedFor(Reservation $reservation): bool` — `departure` : « dès que la réservation n'est plus `confirmed` » ; `return` : « dès qu'elle est `closed` » — dans `layers/inspection/src/Enums/InspectionStep.php` ; test Unit `layers/inspection/tests/Unit/InspectionStepTest.php`
- [ ] T014 [P] Créer l'enum `RevocationReason` (`replaced`, `step_validated`, `reservation_cancelled`) dans `layers/inspection/src/Enums/RevocationReason.php`
- [ ] T015 [P] Migration + modèle `CategoryView` dans `layers/inspection/database/migrations/` et `layers/inspection/src/Models/CategoryView.php` : `machine_category_id` obligatoire (FK sans cascade), `label` « obligatoire, unique par catégorie » (index unique `machine_category_id, label`), `position` entier ; factory associée
- [ ] T016 [P] Migration + modèle `ReservationView` dans `layers/inspection/database/migrations/` et `layers/inspection/src/Models/ReservationView.php` : `reservation_id` obligatoire (FK sans cascade), `label`, `position` ; « jamais modifiée ensuite » (aucune action d'écriture hors création) ; factory associée
- [ ] T017 [P] Migration + modèle `PhotoSession` dans `layers/inspection/database/migrations/` et `layers/inspection/src/Models/PhotoSession.php` : `reservation_id` obligatoire, `step` casté `InspectionStep`, `token_hash` « SHA-256 du jeton, unique », `created_by` → users obligatoire, `expires_at`, `revoked_at` nullable, `revoked_reason` casté `RevocationReason` nullable ; méthode `isActive(): bool` = « `revoked_at` est vide et `expires_at` est dans le futur » et étape toujours ouverte (`InspectionStep::isOpenFor`) ; factory associée
- [ ] T018 Migration + modèle `Photo` dans `layers/inspection/database/migrations/` et `layers/inspection/src/Models/Photo.php` : `reservation_id`, `reservation_view_id` (« appartient à la même réservation »), `step` casté `InspectionStep`, `photo_session_id`, timestamps ; `HasMedia` + `InteractsWithMedia`, collection `photo` `singleFile()` sur le disque `photos`, conversion `thumb` (400 px) **`nonQueued()`** pour qu'elle existe avant la diffusion de `PhotoChanged`, conversion `display` (1 600 px) en file d'attente avec repli sur l'original tant qu'elle n'est pas générée ; factory associée avec un fichier image factice (depends on T016, T017)
- [ ] T019 Créer `ResolveRequiredViews` dans `layers/inspection/src/Actions/ResolveRequiredViews.php` : renvoie les libellés ordonnés des `CategoryView` de la catégorie de la machine, ou `config('inspection.default_views')` si la catégorie n'en a aucune (FR-002)
- [ ] T020 Créer `FreezeReservationViews` dans `layers/inspection/src/Actions/FreezeReservationViews.php` : si la réservation n'a encore aucune `ReservationView`, copie le résultat de `ResolveRequiredViews` ; sinon ne fait rien (idempotent, FR-003) ; test Unit `layers/inspection/tests/Unit/FreezeReservationViewsTest.php` (depends on T019)
- [ ] T021 Créer `MissingViews` dans `layers/inspection/src/Actions/MissingViews.php` : pour une réservation et une étape, renvoie les `ReservationView` sans aucune `Photo` de cette étape (« Complétude d'une étape ») ; test Unit `layers/inspection/tests/Unit/MissingViewsTest.php` (depends on T018)
- [ ] T022 Créer les exceptions de domaine dans `layers/inspection/src/Exceptions/` : `MissingPhotosException` (message traduit listant les vues manquantes), `PhotoSessionUnavailableException`, `StepAlreadyValidatedException`, `StepNotOpenException`
- [ ] T023 [P] Créer les événements diffusables `PhotoChanged` (`photo.changed`, `{ reservation_id, step, reservation_view_id, missing_views_count }`) et `PhotoSessionChanged` (`photo-session.changed`, `{ reservation_id, step, is_active }`) sur `PrivateChannel('reservation.{id}')` dans `layers/inspection/src/Events/`, `broadcastWith()` = allow-list de [contracts/broadcast-events.md](contracts/broadcast-events.md)

**Checkpoint**: `sail artisan migrate:fresh --seed` passe ; T010 et les tests Unit de la phase passent ; la suite de la 001 reste verte.

---

## Phase 3: User Story 1 - Prendre les photos de départ avec son téléphone et débloquer la sortie (Priority: P1) 🎯 MVP

**Goal**: QR code sur le poste, page téléphone, photos en direct, sortie refusée tant qu'une vue manque.

**Independent Test**: Réservation confirmée d'une nacelle commençant aujourd'hui ; 4 photos sur 5 → sortie refusée avec la vue manquante ; 5ᵉ photo → sortie acceptée ; photos de départ ensuite non modifiables.

### Tests for User Story 1 ⚠️

- [ ] T024 [P] [US1] Tests Feature des scénarios 1 à 5 de US1 dans `layers/inspection/tests/Feature/DeparturePhotosTest.php` (lancement → vues « manquantes » ; page téléphone sans connexion avec référence, client, étape ; photo reçue → `PhotoChanged` diffusé et miniature `thumb` déjà disponible ; sortie refusée avec liste des vues manquantes ; sortie acceptée puis ajout / suppression refusés) ; plus : **sortie tentée sans avoir jamais lancé de QR code → refusée, les 5 vues listées comme manquantes, et les vues de la réservation figées**
- [ ] T025 [P] [US1] Tests Feature du lien dans `layers/inspection/tests/Feature/PhoneLinkTest.php` : jeton inconnu, expiré (> 30 min), remplacé par un nouveau QR code, étape validée, réservation annulée → même page « lien plus valable » ; la page n'affiche ni téléphone ni e-mail du client ni dates ; en-têtes `X-Robots-Tag: noindex` et `Referrer-Policy: no-referrer` ; limite de débit de 60 requêtes par minute ; la base ne contient pas le jeton en clair ; **avec un jeton valide, supprimer depuis le composant `PhoneCapture` une photo d'une autre réservation ou de l'autre étape est refusé et la photo existe toujours ; envoyer une photo sur une vue d'une autre réservation est refusé ; modifier la propriété du jeton depuis le navigateur lève une erreur de propriété verrouillée**
- [ ] T026 [P] [US1] Tests Feature des fichiers dans `layers/inspection/tests/Feature/PhotoUploadValidationTest.php` : fichier non image refusé, fichier > 15 Mo refusé, JPEG / PNG / WebP acceptés ; deux téléphones sur le même jeton alimentent la même réservation ; plusieurs photos par vue acceptées ; lancement refusé si la date de début n'est pas atteinte

### Implementation for User Story 1

- [ ] T027 [US1] Implémenter `OpenPhotoSession` dans `layers/inspection/src/Actions/OpenPhotoSession.php` : refuse si l'étape n'est pas ouverte (`StepNotOpenException`) ; appelle `FreezeReservationViews` ; révoque la session active de même réservation et même étape (`replaced`) ; génère un jeton de 40 caractères par `Str::random(40)`, stocke `hash('sha256', …)`, `expires_at` = maintenant + `session_lifetime_minutes`, `created_by` = salarié connecté ; dispatche `PhotoSessionChanged` ; renvoie le jeton en clair (seule occurrence)
- [ ] T028 [US1] Implémenter `FindActivePhotoSession` dans `layers/inspection/src/Actions/FindActivePhotoSession.php` : retrouve la session par hachage du jeton et lève `PhotoSessionUnavailableException` si elle n'existe pas ou si `isActive()` est faux
- [ ] T029 [US1] Implémenter `StorePhoto` dans `layers/inspection/src/Actions/StorePhoto.php` : vérifie que la session est active et que la `ReservationView` appartient à la réservation de la session ; crée la `Photo` (étape et session de la session) et y attache le fichier (collection `photo`) ; dispatche `PhotoChanged` avec `missing_views_count` (depends on T028)
- [ ] T030 [US1] Implémenter `DeletePhoto` dans `layers/inspection/src/Actions/DeletePhoto.php` : refuse (`StepAlreadyValidatedException`) si `InspectionStep::isValidatedFor` est vrai ; appelée depuis le téléphone, reçoit la `PhotoSession` et ne trouve la photo que **parmi celles de la réservation et de l'étape de cette session** (sinon `ModelNotFoundException`, rendu identique à une photo inexistante) ; supprime la `Photo` (la médiathèque supprime le fichier) ; dispatche `PhotoChanged`
- [ ] T031 [US1] Implémenter `PhotosCompleteGuard` dans `layers/inspection/src/Guards/PhotosCompleteGuard.php` : `beforeDeparture` appelle **d'abord `FreezeReservationViews`** (une réservation sans vue figée n'est jamais complète), puis lève `MissingPhotosException` si `MissingViews` pour `departure` n'est pas vide ; `beforeReturn` laissé passant pour l'instant (complété en US2) ; l'enregistrer dans `ReservationTransitionGuards` depuis le service provider d'`inspection`
- [ ] T032 [US1] Créer le listener `RevokePhotoSessionsOnReservationChanged` dans `layers/inspection/src/Listeners/RevokePhotoSessionsOnReservationChanged.php` écoutant `ReservationChanged` : réservation passée `in_progress` → révoque les sessions `departure` actives (`step_validated`) ; passée `closed` → révoque les sessions `return` actives (`step_validated`) ; passée `cancelled` → révoque toutes ses sessions actives (`reservation_cancelled`) ; dispatche `PhotoSessionChanged`
- [ ] T033 [US1] Créer la route publique `GET /photos/{token}` dans `layers/inspection/routes/web.php` avec `throttle:60,1` et le middleware `layers/inspection/src/Http/Middleware/PhoneLinkHeaders.php` (`X-Robots-Tag: noindex`, `Referrer-Policy: no-referrer`) ; un jeton invalide rend la vue `inspection::phone.expired` (statut 200, message unique)
- [ ] T034 [US1] Créer le composant Livewire `PhoneCapture` dans `layers/inspection/src/Livewire/PhoneCapture.php` et sa vue mobile `layers/inspection/resources/views/livewire/phone-capture.blade.php` : seule propriété publique, le jeton, marquée `#[Locked]` ; la session est retrouvée par `FindActivePhotoSession` à chaque action, et tout identifiant de vue reçu du navigateur est cherché parmi les `ReservationView` de la réservation de la session ; affiche uniquement référence de la machine, nom du client, étape, vues avec état « manquante / reçue » et miniatures (URL temporaires signées ≤ 30 min, conversion `thumb`) ; `WithFileUploads`, un `<input type="file" accept="image/*" capture="environment">` par vue ; validation `image|mimes:jpeg,png,webp|max:15360` ; appelle `StorePhoto` / `DeletePhoto` ; revérifie la session à chaque action (depends on T029, T030, T033)
- [ ] T035 [US1] Créer le script de réduction côté téléphone dans `layers/inspection/resources/js/photo-resize.js` (composant Alpine : lit le fichier, le redessine dans un canvas au plus 2 560 px sur le plus grand côté, l'exporte en JPEG qualité 0,85, l'envoie par `$wire.upload` ; en cas d'échec, marque la vue « non envoyée » avec un bouton « réessayer », FR-015) ; l'importer dans `resources/js/app.js`
- [ ] T036 [US1] Créer le composant Livewire `PhotosPanel` dans `layers/inspection/src/Livewire/PhotosPanel.php` et sa vue : selon l'étape ouverte, bouton « Lancer / Régénérer le QR code » (appelle `OpenPhotoSession`), QR code SVG généré avec `bacon/bacon-qr-code` à partir de `route('inspection.phone', $token)`, compte à rebours d'expiration, liste des vues avec état et miniatures, suppression d'une photo depuis le poste ; écoute `echo-private:reservation.{id},.photo.changed` et `.photo-session.changed` pour se recharger ; l'enregistrer dans `ReservationDetailSections` depuis le service provider d'`inspection` (depends on T027, T011)
- [ ] T037 [US1] Dans `layers/inspection/src/Livewire/PhotosPanel.php`, émettre `reservation-transition-readiness` `{ step: 'departure', is_ready }` **dès le montage** puis à chaque `photo.changed`, `is_ready` = `MissingViews` vide (en figeant les vues d'abord, comme le guard) ; vérifier dans un test Feature `layers/inspection/tests/Feature/PhotosPanelReadinessTest.php` que le bouton de sortie est désactivé au premier affichage d'une réservation sans photos (depends on T036, T012)
- [ ] T038 [US1] Ajouter les traductions de la prise de photos, du lien et des refus dans `layers/inspection/resources/lang/fr/photos.php`

**Checkpoint**: T024–T026 passent ; la suite de la 001 reste verte. **MVP livrable.**

---

## Phase 4: User Story 2 - Prendre les photos de retour et bloquer la clôture tant qu'elles manquent (Priority: P1)

**Goal**: Même parcours au retour, avec les vues figées au départ ; clôture refusée tant qu'une vue manque.

**Independent Test**: Réservation en cours : retour sans photos refusé ; toutes les vues de retour envoyées → retour enregistré.

### Tests for User Story 2 ⚠️

- [ ] T039 [P] [US2] Tests Feature des scénarios 1 à 4 de US2 dans `layers/inspection/tests/Feature/ReturnPhotosTest.php` (nouveau QR code d'étape « retour » avec les mêmes vues qu'au départ ; retour refusé avec vues manquantes ; retour accepté puis photos figées ; QR code de départ scanné pendant la location → « lien plus valable »)
- [ ] T040 [P] [US2] Test Feature « réservation sortie avant la mise en service » (FR-018) dans `layers/inspection/tests/Feature/LegacyReservationReturnTest.php` : réservation `in_progress` sans `ReservationView` → l'ouverture de la session de retour fige les vues de la catégorie ; le retour exige les photos de retour seulement ; **retour tenté sans avoir jamais lancé de QR code → refusé, toutes les vues de la catégorie listées comme manquantes**
- [ ] T041 [P] [US2] Test Feature « liste de la catégorie modifiée entre départ et retour » (US4 scénario 3) dans `layers/inspection/tests/Feature/FrozenViewsTest.php` : le retour exige les vues photographiées au départ, pas la nouvelle liste

### Implementation for User Story 2

- [ ] T042 [US2] Compléter `PhotosCompleteGuard::beforeReturn` dans `layers/inspection/src/Guards/PhotosCompleteGuard.php` : appelle **d'abord `FreezeReservationViews`**, puis lève `MissingPhotosException` si `MissingViews` pour `return` n'est pas vide
- [ ] T043 [US2] Dans `layers/inspection/src/Actions/OpenPhotoSession.php`, l'ouverture d'une session `return` sur une réservation sans `ReservationView` fige les vues courantes de la catégorie, sans exiger de photos de départ (FR-018) ; T040 passe
- [ ] T044 [US2] Étendre `PhotosPanel` (`layers/inspection/src/Livewire/PhotosPanel.php`) à l'étape « retour » : quand la réservation est `in_progress`, proposer le QR code de retour ; afficher les photos de départ en lecture seule à côté ; émettre `reservation-transition-readiness` `{ step: 'return', is_ready }` dès le montage et à chaque `photo.changed`

**Checkpoint**: T039–T041 passent ; US1 toujours vert.

---

## Phase 5: User Story 3 - Comparer départ et retour et signaler un dégât (Priority: P2)

**Goal**: Comparaison vue par vue, signalement de dégâts, liste « à refacturer », traitement.

**Independent Test**: Réservation avec photos complètes ; dégât signalé sur « Gauche » → visible dans `/degats` depuis un autre poste avec vue, commentaire, auteur, date ; dégât traité → la réservation sort de la liste.

### Tests for User Story 3 ⚠️

- [ ] T045 [P] [US3] Tests Feature des scénarios 1 à 4 de US3 dans `layers/inspection/tests/Feature/DamagesTest.php` (comparaison avec dates et auteurs ; signalement → réservation « à refacturer » ; liste des dégâts à traiter ; traitement → sort de la liste, historique conservé) ; plus : signalement refusé si les photos de retour ne sont pas complètes ; commentaire vide refusé ; permission `damages.manage` requise ; `ResolveDamage` appelée hors d'un composant (comme depuis un autre layer) traite le dégât et refuse un dégât déjà traité ; **sans action enregistrée dans `DamageActions`, « Marquer traité » s'affiche sur la liste et sur la comparaison ; avec un composant de test enregistré, il est rendu pour chaque dégât non traité et « Marquer traité » n'apparaît sur aucun des deux écrans**
- [ ] T046 [P] [US3] Test Feature de diffusion dans `layers/inspection/tests/Feature/DamageBroadcastTest.php` : signalement et traitement diffusent `damage.changed` sur `fleet` avec `{ reservation_id, unresolved_count }` uniquement

### Implementation for User Story 3

- [ ] T047 [US3] Migration + modèle `Damage` dans `layers/inspection/database/migrations/` et `layers/inspection/src/Models/Damage.php` : `reservation_id` obligatoire, `reservation_view_id` obligatoire, `comment` « obligatoire », `reported_by` et `reported_at` obligatoires, `resolved_by` et `resolved_at` nullables (FK sans cascade) ; factory associée
- [ ] T048 [US3] Créer l'événement diffusable `DamageChanged` (`damage.changed` sur `PrivateChannel('fleet')`, `{ reservation_id, unresolved_count }`) dans `layers/inspection/src/Events/DamageChanged.php`
- [ ] T049 [US3] Implémenter `ReportDamage` dans `layers/inspection/src/Actions/ReportDamage.php` : refuse si `MissingViews` pour `return` n'est pas vide ou si aucune photo de retour n'existe ; la vue appartient à la réservation ; renseigne `reported_by` / `reported_at` ; dispatche `DamageChanged`
- [ ] T050 [US3] Implémenter `ResolveDamage` dans `layers/inspection/src/Actions/ResolveDamage.php` — action publique, appelable depuis un autre layer (la feature 003 l'appelle) : refuse un dégât déjà traité ; renseigne `resolved_by` / `resolved_at` ; dispatche `DamageChanged`
- [ ] T051 [US3] Créer la requête « à refacturer » dans `layers/inspection/src/Queries/ReservationsToReinvoice.php` : réservations ayant au moins un `Damage` dont `resolved_at` est vide, avec machine, client, agence et dégâts non traités chargés en une requête (pas de requête par ligne) — « calculé par requête, jamais stocké »
- [ ] T052 [US3] Créer le contrôle d'accès `DamageControl` (permission `damages.manage`) dans `layers/inspection/src/Controls/DamageControl.php`, et le registre `DamageActions` (singleton : `register(string $livewireComponent, int $position)`, `all()` trié, `isEmpty()`) dans `layers/inspection/src/Support/DamageActions.php`, lié dans le service provider d'`inspection` ; créer la vue partielle `layers/inspection/resources/views/partials/damage-actions.blade.php` : si le registre est vide, bouton « Marquer traité » (appelle `ResolveDamage`) ; sinon, pour chaque composant enregistré, `<livewire:dynamic-component :component="…" :damage="$damage" :key="…" />`
- [ ] T053 [US3] Créer l'écran Livewire « Comparaison » (`/reservations/{reservation}/photos`) dans `layers/inspection/src/Livewire/Comparison.php` et sa vue : pour chaque `ReservationView`, photos de départ à gauche et de retour à droite (conversion `display`, servies par une route authentifiée), date et auteur de chaque prise, agrandissement ; formulaire de signalement (vue, commentaire) ; liste des dégâts existants, avec pour chaque dégât non traité la vue partielle `damage-actions` (depends on T049, T050, T052)
- [ ] T054 [US3] Créer l'écran Livewire « Dégâts à traiter » (`/degats`) dans `layers/inspection/src/Livewire/DamagesList.php` et sa vue, basé sur `ReservationsToReinvoice`, avec lien vers la comparaison et, pour chaque dégât non traité, la vue partielle `damage-actions` ; écoute `echo-private:fleet,.damage.changed` (depends on T051, T052)
- [ ] T055 [US3] Ajouter un lien « Comparer les photos » dans `PhotosPanel` quand la réservation a des photos de retour, et l'entrée de navigation « Dégâts » dans `resources/views/components/layouts/app/sidebar.blade.php`
- [ ] T056 [US3] Ajouter les traductions des dégâts et de la comparaison dans `layers/inspection/resources/lang/fr/damages.php`

**Checkpoint**: T045–T046 passent ; US1 et US2 toujours verts.

---

## Phase 6: User Story 4 - Paramétrer les vues requises par catégorie de machine (Priority: P2)

**Goal**: Liste de vues propre à chaque catégorie, défaut sinon.

**Independent Test**: Ajouter « Godet » à « Mini-pelle » → la prise de photos de départ suivante d'une mini-pelle exige 6 vues.

### Tests for User Story 4 ⚠️

- [ ] T057 [P] [US4] Tests Feature des scénarios 1, 2 et 4 de US4 dans `layers/inspection/tests/Feature/CategoryViewsTest.php` (défaut appliqué sans paramétrage ; ajout de « Godet » exigé ensuite ; retrait de la dernière vue refusé) ; plus : libellé en doublon dans la catégorie refusé ; retour explicite à la liste par défaut ; permission `inspection_views.manage` requise

### Implementation for User Story 4

- [ ] T058 [US4] Implémenter `AddCategoryView`, `RenameCategoryView`, `MoveCategoryView`, `RemoveCategoryView` et `ResetCategoryViews` dans `layers/inspection/src/Actions/CategoryViews/` : `RemoveCategoryView` refuse le retrait de la dernière vue (« au moins une vue est requise ») ; `ResetCategoryViews` supprime toutes les vues de la catégorie pour revenir au défaut ; la première écriture sur une catégorie sans vue part d'une copie du défaut
- [ ] T059 [US4] Créer le contrôle d'accès `CategoryViewControl` (permission `inspection_views.manage`) dans `layers/inspection/src/Controls/CategoryViewControl.php`
- [ ] T060 [US4] Créer les écrans Livewire « Vues par catégorie » dans `layers/inspection/src/Livewire/CategoryViewsIndex.php` (`/vues-photos` : liste des catégories, nombre de vues, « par défaut » ou « personnalisée ») et `layers/inspection/src/Livewire/CategoryViews.php` (`/vues-photos/{category}` : liste ordonnée, ajout, renommage, réordonnancement, retrait, « Revenir à la liste par défaut ») avec leurs vues (depends on T058, T059)
- [ ] T061 [US4] Ajouter les traductions dans `layers/inspection/resources/lang/fr/views.php` et l'entrée de navigation « Vues photos » dans `resources/views/components/layouts/app/sidebar.blade.php` ; **aucun fichier de `layers/fleet/` n'est modifié**

**Checkpoint**: T057 passe ; toutes les stories vertes.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T062 [P] Ajouter `LogsActivity` à `PhotoSession` (création, révocation), `Photo` (création, suppression) et `Damage` (signalement, traitement) en rattachant chaque entrée à la réservation (`performedOn`) ; test Feature `layers/inspection/tests/Feature/InspectionHistoryTest.php` (FR-024) ; vérifier qu'aucune entrée ne contient le jeton
- [ ] T063 Rendre `Photo` `Prunable` (pas `MassPrunable`) dans `layers/inspection/src/Models/Photo.php` : « réservation `closed` avec `returned_at` < il y a un an, sans dégât non traité, et sans dégât traité depuis moins d'un an ; ou réservation `cancelled` et photo reçue il y a plus d'un an » ; planifier `model:prune` chaque nuit dans `routes/console.php` ; test Feature `layers/inspection/tests/Feature/PhotoRetentionTest.php` vérifiant aussi la suppression du fichier sur le disque
- [ ] T064 [P] Ajouter un service MinIO facultatif dans `docker-compose.yml` et documenter `PHOTOS_DISK_DRIVER=s3` dans `.env.example` pour tester le stockage S3 en local
- [ ] T065 [P] Vérifier qu'aucun fichier de `layers/booking/` ni `layers/fleet/` n'importe `Inspection\` ni ne cite une route `inspection.` ou le mot `inspection` (`grep -ri "inspection" layers/booking layers/fleet` vide)
- [ ] T066 Lancer `./vendor/bin/sail php vendor/bin/phpstan analyse` et corriger toutes les erreurs dans `layers/inspection/` et les fichiers modifiés de `layers/booking/` ; vérifier que chaque fichier de code fait moins de 200 lignes
- [ ] T067 Dérouler le parcours manuel de [quickstart.md](quickstart.md) avec un vrai smartphone iOS et un Android, **chronométrer SC-003 (moins de 3 min pour 5 vues) et SC-004 (moins de 5 s par photo)** et noter les mesures et les écarts dans `specs/002-photos-qr-code/checklists/requirements.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Prérequis 001** (phase 5 de la 001 commitée) → bloque tout.
- **Setup (Phase 1)** → **Foundational (Phase 2)** → stories.
- **US1 (Phase 3)** : après la phase 2. MVP.
- **US2 (Phase 4)** : après US1 (réutilise `OpenPhotoSession`, `PhotosPanel`, `PhotosCompleteGuard`).
- **US3 (Phase 5)** : après US2 (un dégât exige des photos de retour).
- **US4 (Phase 6)** : après la phase 2 seulement ; parallélisable avec US1 à US3.
- **Polish (Phase 7)** : après les stories voulues.

### Story Completion Order

```text
Prérequis 001 → Setup → Foundational ─┬─► US1 → US2 → US3 → Polish
                                      └─► US4 ────────────┘
```

### Within Each User Story

- Tests écrits et en échec avant l'implémentation.
- Modèles → actions → guards / listeners → composants Livewire → traductions.

### Parallel Opportunities

- Phase 1 : T004, T005, T006.
- Phase 2 : T013 à T017 et T023 (fichiers distincts) ; les tâches `booking` (T007 à T012) en parallèle des modèles d'`inspection`.
- Chaque story : ses tâches de test [P] ensemble.
- US4 entière en parallèle de US1 à US3.

## Parallel Example: User Story 1

```text
T024 DeparturePhotosTest   T025 PhoneLinkTest   T026 PhotoUploadValidationTest
puis : T027 OpenPhotoSession → T028 FindActivePhotoSession → T029 StorePhoto / T030 DeletePhoto
       T031 PhotosCompleteGuard   T032 RevokePhotoSessionsOnReservationChanged   T033 route publique
puis : T034 PhoneCapture + T035 photo-resize.js   T036 PhotosPanel → T037 bouton de sortie
```

## Parallel Example: US4 en parallèle de US1

```text
Développeur A : T024 → T038 (US1)
Développeur B : T057 → T061 (US4)
```

## Implementation Strategy

### MVP First

1. Attendre la phase 5 de la 001, mettre à jour la branche.
2. Phases 1 et 2.
3. US1 : photos de départ et sortie bloquée. **Démo possible** : c'est déjà la preuve d'état au départ.

### Incremental Delivery

1. US1 → démo « départ ».
2. US2 → la paire départ / retour est complète, la clôture est bloquée.
3. US3 → les dégâts sont signalés et listés : la perte de 85 000 € est adressée.
4. US4 → adaptation des vues par catégorie.
5. Polish → historique, rétention, contrôle avec un vrai téléphone.

## Notes

- Les tâches T007 à T012 modifient le layer `booking` de la 001 : les faire relire par la personne qui a implémenté la 001.
- Un test vert sans téléphone réel ne prouve pas la caméra : T067 est obligatoire avant la démo client.
- Aucun commit avant validation de chaque phase.
