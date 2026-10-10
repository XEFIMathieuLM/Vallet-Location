---

description: "Task list for feature 002-photos-qr-code"
---

# Tasks: Photos de départ et de retour via QR code

**Input**: Design documents from `specs/002-photos-qr-code/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/screens.md), [quickstart.md](quickstart.md)

**Tests**: Inclus, comme pour la 001 : un test Feature PHPUnit par scénario d'acceptation. Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : feature 001

Cette feature s'appuie sur la 001 de deux façons.

**Pour les parties indépendantes** (phase 1, modèles d'`inspection` de la phase 2, actions et page téléphone de US1, US4) : la phase 2 de la 001 suffit (`Reservation`, `ReservationStatus`, `MachineCategory`, `User`, permission `reservations.manage`). Elles peuvent démarrer dès que la branche `002-photos-qr-code` est rebasée sur la branche de la 001.

**Pour le blocage de la sortie et du retour** (branchement de `PhotosCompleteGuard` et de `PhotosPanel`, T024, T032, T037, US2) : la 001 doit être implémentée **jusqu'à sa phase 5 incluse**, points d'extension compris. **Prérequis atteint** : phase 5 de la 001 commitée (commit `1496e2c`, points d'extension réalisés sous T079 à T084 de la 001), branche `002-photos-qr-code` rebasée dessus, suite complète verte.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US4)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : Docker pour toutes les commandes (`docker compose exec -u sail laravel.test …`), code en anglais, textes dans `functional/inspection/resources/lang/fr/*.php`, statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (utiliser `rescue()` + rethrow), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers de code < 200 lignes. En plus :

- `inspection` dépend de `booking` et `fleet` ; **aucun fichier de `booking` ni de `fleet` n'importe une classe d'`inspection`**.
- `Reservation` et `MachineCategory` ne déclarent aucune relation vers les tables d'`inspection`.
- Le jeton du QR code n'est jamais stocké, journalisé ni diffusé en clair : seul son SHA-256 est en base.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `inspection`, médiathèque, disque de stockage, permissions.

- [X] T001 Installer `spatie/laravel-medialibrary` (`docker compose exec -u sail laravel.test composer require spatie/laravel-medialibrary`), publier et migrer sa migration `media` dans `database/migrations/`, publier `config/media-library.php` avec `queue_conversions_by_default = true` ; publier `config/livewire.php` et fixer `temporary_file_upload.rules` à `['required', 'file', 'max:15360']` ; relever `upload_max_filesize` et `post_max_size` à 16M dans la configuration PHP de Sail (`compose.yaml` / `php.ini` du conteneur) — **non nécessaire** : le `php.ini` du conteneur Sail fixe déjà ces deux limites à 100M
- [X] T002 Ajouter le disque privé `photos` dans `config/filesystems.php` (driver lu depuis `PHOTOS_DISK_DRIVER`, `local` par défaut sur `storage/app/private/photos`, `s3` en production avec `PHOTOS_S3_*`) et les variables dans `.env.example`
- [X] T003 Créer le layer OSDD `functional/inspection/` (`composer.json` LayerManifest déclarant la dépendance à `booking` et `fleet`, namespace PSR-4, service provider, dossiers `src/`, `config/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `resources/views/`, `resources/js/`, `resources/lang/fr/`, `routes/`, `tests/Feature/`, `tests/Unit/`) ; ses tests sont pris en compte par les suites Unit et Feature de `phpunit.xml` (`functional/*/tests/Unit` et `functional/*/tests/Feature`), sans suite dédiée, qui ferait doublon et ferait sortir PHPUnit en erreur
- [X] T004 [P] Créer `functional/inspection/config/inspection.php` : `default_views` = `['Avant', 'Arrière', 'Gauche', 'Droite', 'Compteur d\'heures']` (clés de traduction), `session_lifetime_minutes` = 30, `max_photo_kilobytes` = 15360, `allowed_mimes` = `jpeg, png, webp` ; le charger depuis le service provider du layer
- [X] T005 [P] Ajouter les permissions `damages.manage` et `inspection_views.manage` au rôle `salarie` dans `functional/inspection/database/seeders/InspectionPermissionSeeder.php`, appelé depuis `database/seeders/DatabaseSeeder.php`
- [X] T006 [P] Créer le canal privé `reservation.{reservation}` dans `functional/inspection/routes/channels.php` (fichier de routes du layer), autorisé pour tout utilisateur ayant la permission `reservations.manage`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Points d'extension de `booking`, modèles d'`inspection`, figement des vues. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### Points d'extension de `booking` (réalisés dans la 001, T079 à T084)

Les tâches T007 à T012 ne sont pas réalisées dans cette branche : génériques (réutilisées par la caution et la 003), elles ont été implémentées dans la 001 avec `DepartReservation`, `ReturnReservation` et `ReservationDetail`. Contrat utilisé par `inspection` :

- interface `Functional\Booking\Contracts\ReservationTransitionGuard` : `beforeDeparture(Reservation $reservation): void`, `beforeReturn(Reservation $reservation): void` ; refus en levant une sous-classe de `Functional\Fleet\Exceptions\RefusalException` ;
- registre singleton `Functional\Booking\Extensions\ReservationTransitionGuards` : `register(class-string<ReservationTransitionGuard>)`, `all()` ; appelé dans la transaction de `DepartReservation` (après les contrôles de `booking`) et de `ReturnReservation::handle($reservation, ReturnCondition $condition)` (avant le verrou de la machine), avant toute modification ;
- registre singleton `Functional\Booking\Extensions\ReservationDetailSections` : `register(string $livewireComponent, int $position)`, `all()` trié, `isEmpty()` ; `ReservationDetail` rend `<livewire:dynamic-component :component="$section" :reservation="$reservation" :key="'section-'.$section" />` ;
- `ReservationDetail` écoute `reservation-transition-readiness` `{ step, is_ready }` (`ReservationDetail::DEPARTURE_STEP` = `departure`, `RETURN_STEP` = `return`) ; dès qu'une section est enregistrée, chaque bouton reste désactivé jusqu'à une réponse `is_ready = true` pour son étape : la section doit donc émettre pour **les deux** étapes ;
- `Functional\Booking\Events\ReservationChanged` (porte la réservation seule, sans statut précédent) est dispatché par la création, la sortie, le retour, l'annulation et le recalcul des conflits ;
- les refus sont affichés par le trait `Functional\Fleet\Livewire\Concerns\DisplaysRefusals` ; les composants d'`inspection` l'utilisent plutôt qu'un hook `exception()` maison.

### Modèles d'`inspection`

- [X] T013 [P] Créer l'enum `InspectionStep` (`departure`, `return`) avec `label()` traduit et `isOpenFor(Reservation $reservation): bool` — `departure` : « `confirmed` et `today >= start_date` » ; `return` : « `in_progress` » — et `isValidatedFor(Reservation $reservation): bool` — `departure` : « dès que la réservation n'est plus `confirmed` » ; `return` : « dès qu'elle est `closed` » — dans `functional/inspection/src/Enums/InspectionStep.php` ; test Unit `functional/inspection/tests/Unit/InspectionStepTest.php`
- [X] T014 [P] Créer l'enum `RevocationReason` (`replaced`, `step_validated`, `reservation_cancelled`) dans `functional/inspection/src/Enums/RevocationReason.php`
- [X] T015 [P] Migration + modèle `CategoryView` dans `functional/inspection/database/migrations/` et `functional/inspection/src/Models/CategoryView.php` : `machine_category_id` obligatoire (FK sans cascade), `label` « obligatoire, unique par catégorie » (index unique `machine_category_id, label`), `position` entier ; factory associée
- [X] T016 [P] Migration + modèle `ReservationView` dans `functional/inspection/database/migrations/` et `functional/inspection/src/Models/ReservationView.php` : `reservation_id` obligatoire (FK sans cascade), `label`, `position` ; « jamais modifiée ensuite » (aucune action d'écriture hors création) ; factory associée
- [X] T017 [P] Migration + modèle `PhotoSession` dans `functional/inspection/database/migrations/` et `functional/inspection/src/Models/PhotoSession.php` : `reservation_id` obligatoire, `step` casté `InspectionStep`, `token_hash` « SHA-256 du jeton, unique », `created_by` → users obligatoire, `expires_at`, `revoked_at` nullable, `revoked_reason` casté `RevocationReason` nullable ; méthode `isActive(): bool` = « `revoked_at` est vide et `expires_at` est dans le futur » et étape toujours ouverte (`InspectionStep::isOpenFor`) ; factory associée
- [X] T018 Migration + modèle `Photo` dans `functional/inspection/database/migrations/` et `functional/inspection/src/Models/Photo.php` : `reservation_id`, `reservation_view_id` (« appartient à la même réservation »), `step` casté `InspectionStep`, `photo_session_id`, timestamps ; `HasMedia` + `InteractsWithMedia`, collection `photo` `singleFile()` sur le disque `photos`, conversion `thumb` (400 px) **`nonQueued()`** pour qu'elle existe avant la diffusion de `PhotoChanged`, conversion `display` (1 600 px) en file d'attente avec repli sur l'original tant qu'elle n'est pas générée ; factory associée avec un fichier image factice (depends on T016, T017)
- [X] T019 Créer `ResolveRequiredViews` dans `functional/inspection/src/Actions/ResolveRequiredViews.php` : renvoie les libellés ordonnés des `CategoryView` de la catégorie de la machine, ou `config('inspection.default_views')` si la catégorie n'en a aucune (FR-002)
- [X] T020 Créer `FreezeReservationViews` dans `functional/inspection/src/Actions/FreezeReservationViews.php` : si la réservation n'a encore aucune `ReservationView`, copie le résultat de `ResolveRequiredViews` ; sinon ne fait rien (idempotent, FR-003) ; test Unit `functional/inspection/tests/Unit/FreezeReservationViewsTest.php` (depends on T019)
- [X] T021 Créer `MissingViews` dans `functional/inspection/src/Actions/MissingViews.php` : pour une réservation et une étape, renvoie les `ReservationView` sans aucune `Photo` de cette étape (« Complétude d'une étape ») ; test Unit `functional/inspection/tests/Unit/MissingViewsTest.php` (depends on T018)
- [X] T022 Créer les exceptions de domaine dans `functional/inspection/src/Exceptions/` : `MissingPhotosException` (message traduit listant les vues manquantes), `PhotoSessionUnavailableException`, `StepAlreadyValidatedException`, `StepNotOpenException`
- [X] T023 [P] Créer les événements diffusables `PhotoChanged` (`photo.changed`, `{ reservation_id, step, reservation_view_id, missing_views_count }`) et `PhotoSessionChanged` (`photo-session.changed`, `{ reservation_id, step, is_active }`) sur `PrivateChannel('reservation.{id}')` dans `functional/inspection/src/Events/`, `broadcastWith()` = allow-list de [contracts/broadcast-events.md](contracts/broadcast-events.md)

**Checkpoint**: `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed` passe ; les tests Unit de la phase passent ; la suite de la 001 reste verte.

---

## Phase 3: User Story 1 - Prendre les photos de départ avec son téléphone et débloquer la sortie (Priority: P1) 🎯 MVP

**Goal**: QR code sur le poste, page téléphone, photos en direct, sortie refusée tant qu'une vue manque.

**Independent Test**: Réservation confirmée d'une nacelle commençant aujourd'hui ; 4 photos sur 5 → sortie refusée avec la vue manquante ; 5ᵉ photo → sortie acceptée ; photos de départ ensuite non modifiables.

### Tests for User Story 1 ⚠️

- [X] T024 [P] [US1] Tests Feature des scénarios 1 à 5 de US1 dans `functional/inspection/tests/Feature/DeparturePhotosTest.php` (lancement → vues « manquantes » ; page téléphone sans connexion avec référence, client, étape ; photo reçue → `PhotoChanged` diffusé et miniature `thumb` déjà disponible ; sortie refusée avec liste des vues manquantes ; sortie acceptée puis ajout / suppression refusés) ; plus : **sortie tentée sans avoir jamais lancé de QR code → refusée, les 5 vues listées comme manquantes, et rien n'est figé (transaction annulée, FR-003 révisé)**
- [X] T025 [P] [US1] Tests Feature du lien dans `functional/inspection/tests/Feature/PhoneLinkTest.php` : jeton inconnu, expiré (> 30 min), remplacé par un nouveau QR code, étape validée, réservation annulée → même page « lien plus valable » ; la page n'affiche ni téléphone ni e-mail du client ni dates ; en-têtes `X-Robots-Tag: noindex` et `Referrer-Policy: no-referrer` ; limite de débit de 60 requêtes par minute ; la base ne contient pas le jeton en clair ; **avec un jeton valide, supprimer depuis le composant `PhoneCapture` une photo d'une autre réservation ou de l'autre étape est refusé et la photo existe toujours ; envoyer une photo sur une vue d'une autre réservation est refusé ; modifier la propriété du jeton depuis le navigateur lève une erreur de propriété verrouillée**
- [X] T026 [P] [US1] Tests Feature des fichiers dans `functional/inspection/tests/Feature/PhotoUploadValidationTest.php` : fichier non image refusé, fichier > 15 Mo refusé, JPEG / PNG / WebP acceptés ; deux téléphones sur le même jeton alimentent la même réservation ; plusieurs photos par vue acceptées ; lancement refusé si la date de début n'est pas atteinte

### Implementation for User Story 1

- [X] T027 [US1] Implémenter `OpenPhotoSession` dans `functional/inspection/src/Actions/OpenPhotoSession.php` : refuse si l'étape n'est pas ouverte (`StepNotOpenException`) ; appelle `FreezeReservationViews` ; révoque la session active de même réservation et même étape (`replaced`) ; génère un jeton de 40 caractères par `Str::random(40)`, stocke `hash('sha256', …)`, `expires_at` = maintenant + `session_lifetime_minutes`, `created_by` = salarié connecté ; dispatche `PhotoSessionChanged` ; renvoie le jeton en clair (seule occurrence)
- [X] T028 [US1] Implémenter `FindActivePhotoSession` dans `functional/inspection/src/Actions/FindActivePhotoSession.php` : retrouve la session par hachage du jeton et lève `PhotoSessionUnavailableException` si elle n'existe pas ou si `isActive()` est faux
- [X] T029 [US1] Implémenter `StorePhoto` dans `functional/inspection/src/Actions/StorePhoto.php` : vérifie que la session est active et que la `ReservationView` appartient à la réservation de la session ; crée la `Photo` (étape et session de la session) et y attache le fichier (collection `photo`) ; dispatche `PhotoChanged` avec `missing_views_count` (depends on T028)
- [X] T030 [US1] Implémenter `DeletePhoto` dans `functional/inspection/src/Actions/DeletePhoto.php` : refuse (`StepAlreadyValidatedException`) si `InspectionStep::isValidatedFor` est vrai ; appelée depuis le téléphone, reçoit la `PhotoSession` et ne trouve la photo que **parmi celles de la réservation et de l'étape de cette session** (sinon `ModelNotFoundException`, rendu identique à une photo inexistante) ; supprime la `Photo` (la médiathèque supprime le fichier) ; dispatche `PhotoChanged`
- [X] T031 [US1] Implémenter `PhotosCompleteGuard` dans `functional/inspection/src/Guards/PhotosCompleteGuard.php` : `beforeDeparture` appelle **d'abord `FreezeReservationViews`** (une réservation sans vue figée n'est jamais complète), puis lève `MissingPhotosException` si `MissingViews` pour `departure` n'est pas vide ; `beforeReturn` laissé passant pour l'instant (complété en US2) ; testée en l'appelant directement ; l'enregistrer dans `ReservationTransitionGuards` depuis le service provider d'`inspection` une fois les points d'extension de la 001 disponibles
- [X] T032 [US1] Créer le listener `RevokePhotoSessionsOnReservationChanged` dans `functional/inspection/src/Listeners/RevokePhotoSessionsOnReservationChanged.php` écoutant `ReservationChanged` : réservation passée `in_progress` → révoque les sessions `departure` actives (`step_validated`) ; passée `closed` → révoque les sessions `return` actives (`step_validated`) ; passée `cancelled` → révoque toutes ses sessions actives (`reservation_cancelled`) ; dispatche `PhotoSessionChanged` ; `ReservationChanged` ne porte pas le statut précédent : la révocation se déduit du statut courant et reste idempotente (un événement de création ou de recalcul des conflits ne révoque rien)
- [X] T033 [US1] Créer la route publique `GET /photos/{token}` dans `functional/inspection/routes/web.php` avec `throttle:60,1` et le middleware `functional/inspection/src/Http/Middleware/PhoneLinkHeaders.php` (`X-Robots-Tag: noindex`, `Referrer-Policy: no-referrer`) ; un jeton invalide rend la vue `inspection::phone.expired` (statut 200, message unique)
- [X] T034 [US1] Créer le composant Livewire `PhoneCapture` dans `functional/inspection/src/Livewire/PhoneCapture.php` et sa vue mobile `functional/inspection/resources/views/livewire/phone-capture.blade.php` : seule propriété publique, le jeton, marquée `#[Locked]` ; la session est retrouvée par `FindActivePhotoSession` à chaque action, et tout identifiant de vue reçu du navigateur est cherché parmi les `ReservationView` de la réservation de la session ; affiche uniquement référence de la machine, nom du client, étape, vues avec état « manquante / reçue » et miniatures (URL temporaires signées ≤ 30 min, conversion `thumb`) ; `WithFileUploads`, un `<input type="file" accept="image/*" capture="environment">` par vue ; validation `image|mimes:jpeg,png,webp|max:15360` ; appelle `StorePhoto` / `DeletePhoto` ; revérifie la session à chaque action (depends on T029, T030, T033)
- [X] T035 [US1] Créer le script de réduction côté téléphone dans `functional/inspection/resources/js/photo-resize.js` (composant Alpine : lit le fichier, le redessine dans un canvas au plus 2 560 px sur le plus grand côté, l'exporte en JPEG qualité 0,85, l'envoie par `$wire.upload` ; en cas d'échec, marque la vue « non envoyée » avec un bouton « réessayer », FR-015) ; l'importer dans `resources/js/app.js`
- [X] T036 [US1] Créer le composant Livewire `PhotosPanel` dans `functional/inspection/src/Livewire/PhotosPanel.php` et sa vue : selon l'étape ouverte, bouton « Lancer / Régénérer le QR code » (appelle `OpenPhotoSession`), QR code SVG généré avec `bacon/bacon-qr-code` à partir de `route('inspection.phone', $token)`, compte à rebours d'expiration, liste des vues avec état et miniatures, suppression d'une photo depuis le poste ; écoute `echo-private:reservation.{id},.photo.changed` et `.photo-session.changed` pour se recharger ; l'enregistrer dans `ReservationDetailSections` depuis le service provider d'`inspection` (depends on T027, T011)
- [X] T037 [US1] Dans `functional/inspection/src/Livewire/PhotosPanel.php`, émettre `reservation-transition-readiness` `{ step: 'departure', is_ready }` **dès le montage** puis à chaque `photo.changed`, `is_ready` = `MissingViews` vide (en figeant les vues d'abord, comme le guard) ; vérifier dans un test Feature `functional/inspection/tests/Feature/PhotosPanelReadinessTest.php` que le bouton de sortie est désactivé au premier affichage d'une réservation sans photos (depends on T036, T012)
- [X] T038 [US1] Ajouter les traductions de la prise de photos, du lien et des refus dans `functional/inspection/resources/lang/fr/photos.php`

**Checkpoint**: T024–T026 passent ; la suite de la 001 reste verte. **MVP livrable.**

---

## Phase 4: User Story 2 - Prendre les photos de retour et bloquer la clôture tant qu'elles manquent (Priority: P1)

**Goal**: Même parcours au retour, avec les vues figées au départ ; clôture refusée tant qu'une vue manque.

**Independent Test**: Réservation en cours : retour sans photos refusé ; toutes les vues de retour envoyées → retour enregistré.

### Tests for User Story 2 ⚠️

- [X] T039 [P] [US2] Tests Feature des scénarios 1 à 4 de US2 dans `functional/inspection/tests/Feature/ReturnPhotosTest.php` (nouveau QR code d'étape « retour » avec les mêmes vues qu'au départ ; retour refusé avec vues manquantes ; retour accepté puis photos figées ; QR code de départ scanné pendant la location → « lien plus valable »)
- [X] T040 [P] [US2] Test Feature « réservation sortie avant la mise en service » (FR-018) dans `functional/inspection/tests/Feature/LegacyReservationReturnTest.php` : réservation `in_progress` sans `ReservationView` → l'ouverture de la session de retour fige les vues de la catégorie ; le retour exige les photos de retour seulement ; **retour tenté sans avoir jamais lancé de QR code → refusé, toutes les vues de la catégorie listées comme manquantes, rien n'est figé**
- [X] T041 [P] [US2] Test Feature « liste de la catégorie modifiée entre départ et retour » (US4 scénario 3) dans `functional/inspection/tests/Feature/FrozenViewsTest.php` : le retour exige les vues photographiées au départ, pas la nouvelle liste

### Implementation for User Story 2

- [X] T042 [US2] Compléter `PhotosCompleteGuard::beforeReturn` dans `functional/inspection/src/Guards/PhotosCompleteGuard.php` : appelle **d'abord `FreezeReservationViews`**, puis lève `MissingPhotosException` si `MissingViews` pour `return` n'est pas vide
- [X] T043 [US2] Dans `functional/inspection/src/Actions/OpenPhotoSession.php`, l'ouverture d'une session `return` sur une réservation sans `ReservationView` fige les vues courantes de la catégorie, sans exiger de photos de départ (FR-018) ; T040 passe
- [X] T044 [US2] Étendre `PhotosPanel` (`functional/inspection/src/Livewire/PhotosPanel.php`) à l'étape « retour » : quand la réservation est `in_progress`, proposer le QR code de retour ; afficher les photos de départ en lecture seule à côté ; émettre `reservation-transition-readiness` `{ step: 'return', is_ready }` dès le montage et à chaque `photo.changed`

**Checkpoint**: T039–T041 passent ; US1 toujours vert.

---

## Phase 5: User Story 3 - Comparer départ et retour et signaler un dégât (Priority: P2)

**Goal**: Comparaison vue par vue, signalement de dégâts, liste « à refacturer », traitement.

**Independent Test**: Réservation avec photos complètes ; dégât signalé sur « Gauche » → visible dans `/degats` depuis un autre poste avec vue, commentaire, auteur, date ; dégât traité → la réservation sort de la liste.

### Tests for User Story 3 ⚠️

- [X] T045 [P] [US3] Tests Feature des scénarios 1 à 4 de US3 dans `functional/inspection/tests/Feature/DamagesTest.php` (comparaison avec dates et auteurs ; signalement → réservation « à refacturer » ; liste des dégâts à traiter ; traitement → sort de la liste, historique conservé) ; plus : signalement refusé si les photos de retour ne sont pas complètes ; commentaire vide refusé ; permission `damages.manage` requise ; `ResolveDamage` appelée hors d'un composant (comme depuis un autre layer) traite le dégât et refuse un dégât déjà traité ; **sans action enregistrée dans `DamageActions`, « Marquer traité » s'affiche sur la liste et sur la comparaison ; avec un composant de test enregistré, il est rendu pour chaque dégât non traité et « Marquer traité » n'apparaît sur aucun des deux écrans**
- [X] T046 [P] [US3] Test Feature de diffusion dans `functional/inspection/tests/Feature/DamageBroadcastTest.php` : signalement et traitement diffusent `damage.changed` sur `fleet` avec `{ reservation_id, unresolved_count }` uniquement

### Implementation for User Story 3

- [X] T047 [US3] Migration + modèle `Damage` dans `functional/inspection/database/migrations/` et `functional/inspection/src/Models/Damage.php` : `reservation_id` obligatoire, `reservation_view_id` obligatoire, `comment` « obligatoire », `reported_by` et `reported_at` obligatoires, `resolved_by` et `resolved_at` nullables (FK sans cascade) ; factory associée
- [X] T048 [US3] Créer l'événement diffusable `DamageChanged` (`damage.changed` sur `PrivateChannel('fleet')`, `{ reservation_id, unresolved_count }`) dans `functional/inspection/src/Events/DamageChanged.php`
- [X] T049 [US3] Implémenter `ReportDamage` dans `functional/inspection/src/Actions/ReportDamage.php` : refuse si `MissingViews` pour `return` n'est pas vide ou si aucune photo de retour n'existe ; la vue appartient à la réservation ; renseigne `reported_by` / `reported_at` ; dispatche `DamageChanged`
- [X] T050 [US3] Implémenter `ResolveDamage` dans `functional/inspection/src/Actions/ResolveDamage.php` — action publique, appelable depuis un autre layer (la feature 003 l'appelle) : refuse un dégât déjà traité ; renseigne `resolved_by` / `resolved_at` ; dispatche `DamageChanged`
- [X] T051 [US3] Créer la requête « à refacturer » dans `functional/inspection/src/Queries/ReservationsToReinvoice.php` : réservations ayant au moins un `Damage` dont `resolved_at` est vide, avec machine, client, agence et dégâts non traités chargés en une requête (pas de requête par ligne) — « calculé par requête, jamais stocké »
- [X] T052 [US3] Créer le contrôle d'accès `DamageControl` (permission `damages.manage`) dans `functional/inspection/src/Access/Controls/DamageControl.php`, et le registre `DamageActions` (singleton : `register(string $livewireComponent, int $position)`, `all()` trié, `isEmpty()`) dans `functional/inspection/src/Support/DamageActions.php`, lié dans le service provider d'`inspection` ; créer la vue partielle `functional/inspection/resources/views/partials/damage-actions.blade.php` : si le registre est vide, bouton « Marquer traité » (appelle `ResolveDamage`) ; sinon, pour chaque composant enregistré, `<livewire:dynamic-component :component="…" :damage="$damage" :key="…" />`
- [X] T053 [US3] Créer l'écran Livewire « Comparaison » (`/reservations/{reservation}/photos`) dans `functional/inspection/src/Livewire/Comparison.php` et sa vue : pour chaque `ReservationView`, photos de départ à gauche et de retour à droite (conversion `display`, servies par une route authentifiée), date et auteur de chaque prise, agrandissement ; formulaire de signalement (vue, commentaire) ; liste des dégâts existants, avec pour chaque dégât non traité la vue partielle `damage-actions` (depends on T049, T050, T052)
- [X] T054 [US3] Créer l'écran Livewire « Dégâts à traiter » (`/degats`) dans `functional/inspection/src/Livewire/DamagesList.php` et sa vue, basé sur `ReservationsToReinvoice`, avec lien vers la comparaison et, pour chaque dégât non traité, la vue partielle `damage-actions` ; écoute `echo-private:fleet,.damage.changed` (depends on T051, T052)
- [X] T055 [US3] Ajouter un lien « Comparer les photos » dans `PhotosPanel` quand la réservation a des photos de retour, et l'entrée de navigation « Dégâts » dans `resources/views/layouts/app/sidebar.blade.php`
- [X] T056 [US3] Ajouter les traductions des dégâts et de la comparaison dans `functional/inspection/resources/lang/fr/damages.php`

**Checkpoint**: T045–T046 passent ; US1 et US2 toujours verts.

---

## Phase 6: User Story 4 - Paramétrer les vues requises par catégorie de machine (Priority: P2)

**Goal**: Liste de vues propre à chaque catégorie, défaut sinon.

**Independent Test**: Ajouter « Godet » à « Mini-pelle » → la prise de photos de départ suivante d'une mini-pelle exige 6 vues.

### Tests for User Story 4 ⚠️

- [X] T057 [P] [US4] Tests Feature des scénarios 1, 2 et 4 de US4 dans `functional/inspection/tests/Feature/CategoryViewsTest.php` (défaut appliqué sans paramétrage ; ajout de « Godet » exigé ensuite ; retrait de la dernière vue refusé) ; plus : libellé en doublon dans la catégorie refusé ; retour explicite à la liste par défaut ; permission `inspection_views.manage` requise

### Implementation for User Story 4

- [X] T058 [US4] Implémenter `AddCategoryView`, `RenameCategoryView`, `MoveCategoryView`, `RemoveCategoryView` et `ResetCategoryViews` dans `functional/inspection/src/Actions/CategoryViews/` : `RemoveCategoryView` refuse le retrait de la dernière vue (« au moins une vue est requise ») ; `ResetCategoryViews` supprime toutes les vues de la catégorie pour revenir au défaut ; la première écriture sur une catégorie sans vue part d'une copie du défaut
- [X] T059 [US4] Créer le contrôle d'accès `CategoryViewControl` (permission `inspection_views.manage`) dans `functional/inspection/src/Access/Controls/CategoryViewControl.php`
- [X] T060 [US4] Créer les écrans Livewire « Vues par catégorie » dans `functional/inspection/src/Livewire/CategoryViewsIndex.php` (`/vues-photos` : liste des catégories, nombre de vues, « par défaut » ou « personnalisée ») et `functional/inspection/src/Livewire/CategoryViews.php` (`/vues-photos/{category}` : liste ordonnée, ajout, renommage, réordonnancement, retrait, « Revenir à la liste par défaut ») avec leurs vues (depends on T058, T059)
- [X] T061 [US4] Ajouter les traductions dans `functional/inspection/resources/lang/fr/views.php` et l'entrée de navigation « Vues photos » dans `resources/views/layouts/app/sidebar.blade.php` ; **aucun fichier de `functional/fleet/` n'est modifié**

**Checkpoint**: T057 passe ; toutes les stories vertes.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T062 [P] Enregistrer l'historique d'`inspection` dans `spatie/laravel-activitylog` (journal `inspection`, `performedOn` = la réservation, `causedBy` = l'auteur) via `functional/inspection/src/Support/InspectionHistory.php`, appelé par les actions : QR code lancé et révoqué (`photo_session.opened`, `photo_session.revoked` avec la raison), photo reçue et supprimée (`photo.received`, `photo.deleted`, auteur = salarié qui a lancé le QR code ou salarié du poste), dégât signalé et traité (`damage.reported`, `damage.resolved`). Écriture explicite plutôt que le trait `LogsActivity` : les révocations passent par une mise à jour groupée sans événement de modèle, et chaque entrée doit être rattachée à la réservation (FR-024). Test Feature `functional/inspection/tests/Feature/InspectionHistoryTest.php` ; vérifier qu'aucune entrée ne contient le jeton
- [X] T063 Rendre `Photo` `Prunable` (pas `MassPrunable`) dans `functional/inspection/src/Models/Photo.php` : « réservation `closed` avec `returned_at` < il y a un an, sans dégât non traité, et sans dégât traité depuis moins d'un an ; ou réservation `cancelled` et photo reçue il y a plus d'un an » ; planifier `model:prune --model=Photo` chaque nuit (2 h 15) dans `functional/inspection/routes/console.php` ; test Feature `functional/inspection/tests/Feature/PhotoRetentionTest.php` vérifiant aussi la suppression du fichier sur le disque
- [X] T064 [P] Ajouter un service S3 local facultatif dans `compose.yaml` (SeaweedFS, profil `s3` : les images MinIO ne sont plus publiées sur Docker Hub ni sur quay.io), le paquet `league/flysystem-aws-s3-v3` au layer, la variable `PHOTOS_S3_ROOT` (racine vide en S3, la racine locale ne s'applique qu'au driver `local`), et documenter `PHOTOS_DISK_DRIVER=s3` dans `.env.example`
- [X] T065 [P] Vérifier qu'aucun fichier de `functional/booking/` ni `functional/fleet/` n'importe `Inspection\` ni ne cite une route `inspection.` ou le mot `inspection` (`grep -ri "inspection" functional/booking functional/fleet` vide)
- [X] T066 Lancer `docker compose exec -u sail laravel.test vendor/bin/phpstan analyse` et corriger toutes les erreurs dans `functional/inspection/` et les fichiers modifiés de `functional/booking/` ; vérifier que chaque fichier de code fait moins de 200 lignes
- [ ] T067 Dérouler le parcours manuel de [quickstart.md](quickstart.md) avec un vrai smartphone iOS et un Android, **chronométrer SC-003 (moins de 3 min pour 5 vues) et SC-004 (moins de 5 s par photo)** et noter les mesures et les écarts dans `specs/002-photos-qr-code/checklists/requirements.md`

---

## Phase 8: Corrections de l'analyse finale

**Purpose**: Constats D1, D2, D4, F3, F4 et U1 du `/speckit-analyze` final.

- [X] T068 [US1] Tests Feature dans `functional/inspection/tests/Feature/PhotoStepLockTest.php` : une suppression ou un ajout de photo dont la réservation a été validée entre le chargement et l'écriture est refusé (la vérification relit la réservation sous verrou) ; puis, dans `DeletePhoto` et `StorePhoto`, verrouiller la réservation (`lockForUpdate()`) dans la transaction et revérifier l'étape avant d'écrire, pour se sérialiser avec `DepartReservation` / `ReturnReservation` (D1, FR-014, FR-016, SC-001)
- [X] T069 [US1] Dans `functional/inspection/src/Livewire/PhotosPanel.php`, calculer `is_ready` avec `MissingViews` (requête en base, sans figer les vues) au lieu d'un parcours en PHP ; les tests de `PhotosPanelTest` restent verts (D2)
- [X] T070 [P] Justifier dans `plan.md` (Technical Context) les dépendances ajoutées : `league/flysystem-aws-s3-v3` (disque S3 de production) et SeaweedFS (S3 local facultatif, MinIO n'étant plus publié) (D4)
- [X] T071 [P] Aligner l'arborescence de `plan.md` sur le code (`Access/Controls/` sans `PhotoControl`, `Queries/`, `Http/Controllers/`, `Support/InspectionHistory`, `Support/QrCodeSvg`, `routes/channels.php` et `routes/console.php` du layer) et ajouter à T001 la note sur les limites d'envoi PHP déjà suffisantes (F3, F4, U1)

## Phase 9: Conformité à la constitution v1.0.0

**Purpose**: Constats K1, K2 et F6 du `/speckit-analyze` mené avec la constitution v1.0.0 (principe II).

- [X] T072 [US1] Aucune requête dans une boucle et agrégats en base (K1) : tests d'abord dans `functional/inspection/tests/Feature/QueryCountTest.php` (nombre de requêtes indépendant du nombre de vues ou de sessions) ; puis `FreezeReservationViews` et `EditableCategoryViews` insèrent les vues en une requête, `RemoveCategoryView` renumérote en un seul `decrement`, `RevokePhotoSessions` écrit une seule entrée d'historique par révocation, et un objet `ViewCompleteness` calculé en une requête SQL (`count(*) filter`) remplace les comptages en PHP dans `PhotosPanel`, `StorePhoto` et `DeletePhoto`
- [X] T073 [US3] Écritures liées dans une transaction (K2) : tests d'abord dans `functional/inspection/tests/Feature/HistoryAtomicityTest.php` (si l'historique échoue, ni le dégât ni la révocation ne sont enregistrés) ; puis `ReportDamage` et `RevokePhotoSessions` encadrés par `DB::transaction`
- [X] T074 Corriger le Constitution Check de `plan.md` (principe II) une fois T072 et T073 faits (F6)

## Phase 10: Conformité à la constitution v1.0.0 (suite)

**Purpose**: Constats K3, K4 et L1 du `/speckit-analyze` mené après la phase 9.

- [X] T075 [US4] Agrégats calculés en base (K3, principe II) : tests d'abord dans `functional/inspection/tests/Feature/DatabaseAggregatesTest.php` ; puis `AddCategoryView` (position suivante par `max(position)` en SQL), `RemoveCategoryView` (« dernière vue » par `count(*)` en SQL) et `RevokePhotoSessions` (étapes par `select distinct`, nombre de sessions renvoyé par l'`update`) ne calculent plus rien en PHP
- [X] T076 Tests Unit des calculs (K4, principe VI) : `functional/inspection/tests/Unit/StepCompletenessTest.php` (règles de complétude), `functional/inspection/tests/Unit/ViewCompletenessTest.php` (comptage SQL par étape) et `functional/inspection/tests/Unit/PhotoRetentionRuleTest.php` (photos retenues par `Photo::prunable()`)
- [X] T077 [US3] Dans `PhotosPanel`, afficher « Comparer les photos » d'après `ViewCompleteness` (`hasPhotosFor(InspectionStep::Return)`) au lieu d'un parcours des photos en PHP (L1)

## Phase 11: Conformité aux skills Xefi

**Purpose**: Audit de conformité aux skills Xefi (plugins `laravel`, `global`, `design-patterns`, `design`), validé par l'utilisateur : points HAUTE, MOYENNE et UI à corriger avant la fusion des PR. Périmètre : `functional/inspection` et les fichiers de configuration introduits par la 002. Lire le `SKILL.md` concerné avant chaque tâche.

**Dépendances 001** : T080 attend la séparation message utilisateur / message technique dans `RefusalException` et `DisplaysRefusals` (layer `fleet`) ; T085 attend le mécanisme `extra.faker` ; les tâches d'interface reprennent la convention commune d'échelle des titres et de modale de confirmation dès que la 001 l'a livrée et fusionnée dans cette branche.

### Haute

- [X] T078 [seed-new-features, seeder-conventions] Créer `functional/inspection/database/seeders/InspectionSeeder.php`, enregistré dans `DatabaseSeeder` : vues personnalisées pour une catégorie, vues figées, photos de départ et de retour avec fichier, dégâts traités et non traités, sessions actives, expirées et révoquées (chaque `RevocationReason`) ; uniquement via les factories (états nommés), sans orphelin ; vérifier `migrate:fresh --seed` ; `DatabaseSeeder` n'appelle pas `BookingSeeder` : l'`InspectionSeeder` crée ses réservations avec les factories de `booking` (confirmée, en cours, close, annulée) ; ajouter les états nommés nécessaires (session expirée, session révoquée avec raison, photo avec fichier) ; l'ajout dans `DatabaseSeeder` (fichier racine) est signalé

### Moyenne

- [X] T079 [no-god-classes] Supprimer le dossier fourre-tout `functional/inspection/src/Support/` : ranger `DamageActions`, `InspectionHistory`, `QrCodeSvg`, `StepCompleteness` et `ViewCompleteness` dans des dossiers nommés d'après leur concept ; destinations : `Extensions/DamageActions` (comme `booking`), `History/InspectionHistory` (avec l'enum de T082), `QrCodes/QrCodeSvg`, `Completeness/` (`ViewCompleteness`, `StepCompleteness`)
- [X] T080 [no-generic-exceptions, code-in-english] Messages techniques des exceptions d'`inspection` en anglais ; le texte affiché à l'utilisateur passe par les traductions selon la forme retenue par la 001 pour `RefusalException` / `DisplaysRefusals` (dépendance 001)
- [X] T081 [automated-tests] Déplacer en Feature les tests « Unit » qui utilisent la base (`FreezeReservationViewsTest`, `MissingViewsTest`, `ViewCompletenessTest`, `PhotoRetentionRuleTest`) ; rendre `InspectionStepTest` purement Unit (`PHPUnit\Framework\TestCase`, sans framework), en extrayant au besoin la règle d'ouverture et de validation sur le statut et les dates ; règle de la constitution amendée (amendement porté par la 001) : calculs en mémoire testés en Unit, calculs en base testés en Feature — `ViewCompletenessTest` et `PhotoRetentionRuleTest` passent donc en Feature
- [X] T082 [no-magic-strings] Requêtes par relation (`whereBelongsTo`) au lieu de `where('*_id', …)` dans le layer ; enum `InspectionHistoryEvent` pour les événements d'historique, qui sert aussi de clé de traduction ; signaler à la 001 la recopie des valeurs d'étape dans `ReservationDetail` ; l'enum porte les valeurs des événements ; aucune traduction tant qu'aucun écran n'affiche l'historique
- [X] T083 [no-fat-models] Sortir `Photo::temporaryUrl()` du modèle (classe dédiée aux URL des fichiers de photo) ; examiner `PhotoSession::isActive()` (accesseur trivial sur ses propres attributs ou règle à extraire)
- [X] T084 [retention-via-prunable] Durée de rétention des photos en configuration (`inspection.photo_retention_days` ou équivalent) au lieu de `subYear()` en dur ; rendre `PhotoSession` `Prunable` (sessions expirées ou révoquées depuis plus de N jours **sans photo**, à cause de la clé étrangère) ; s'aligner sur une entrée `model:prune` unique pour l'application si la 001 en planifie une ; règle des sessions décidée (FR-023) : expirées ou révoquées depuis plus de 30 jours (`inspection.photo_session_retention_days`) et sans photo ; durée des photos dans `inspection.photo_retention_days` (365) ; `Photo` et `PhotoSession` sont ajoutés à `prunable.models` depuis le provider du layer ; l'entrée `model:prune` unique de la 001 (`routes/console.php` racine) les purge chaque nuit
- [X] T085 [custom-faker-extensions, faker-extensions] Remplacer la logique des factories (`CategoryViewFactory`, `ReservationViewFactory`, `PhotoSessionFactory` avec `Str::random`) par des extensions faker du projet, déclarées via le mécanisme `extra.faker` de la 001 (dépendance 001)
- [X] T086 [extend-dont-override] Supprimer les copies intégrales de `config/livewire.php` et `config/media-library.php` ; ne garder que les clés surchargées
- [X] T087 Relier les limites de taille : une seule valeur (`inspection.max_photo_kilobytes`) pour la validation Livewire, l'envoi temporaire et `media-library.max_file_size` ; test d'une photo entre 10 et 15 Mo acceptée
- [X] T088 [layer-owned-config] Déplacer le disque `photos` dans la configuration du layer (surcharge `filesystems` par `overrideConfigFrom`, l'API de `xefi/laravel-osdd` v2.0.1, qui n'a pas de chargement automatique du dossier `config/` d'un layer) ; vérifier l'URL de service du disque (`/photo-files`) par rapport à la route `photo-fichiers`
- [X] T089 [osdd, osdd-scaffolding] Compléter `functional/inspection/composer.json` avec toutes les dépendances réellement utilisées (booking, fleet, medialibrary, livewire, flux, activitylog, permission, access-control, Flysystem S3, QR code…)

### Interface (skills design)

- [X] T090 [accessibility] Page téléphone : vrai bouton d'envoi avec focus visible (au lieu du label sur un champ masqué) ; alternative textuelle au QR code SVG du panneau ; niveaux de titres explicites sur les écrans d'inspection
- [X] T091 [buttons] Un seul bouton primaire par écran : « Lancer le QR code » secondaire (« Enregistrer la sortie » de la 001 reste le primaire) ; écran des vues et comparaison sans double primaire ; action principale du téléphone en taille L (48 px)
- [X] T092 [screen-states] Messages de succès (signalement de dégât, modification des vues) et état d'erreur de chargement sur les écrans d'inspection : message de succès après un signalement de dégât et après chaque modification des vues d'une catégorie ; message d'erreur sur le panneau photos et la comparaison si leur rechargement échoue
- [X] T093 [spacing, foundations] Supprimer les espacements de 12 px, `top-0.5` / `right-0.5`, et l'écart de 4 px entre titre et sous-titre (8 px) ; appliquer l'échelle des titres commune de la 001 dès qu'elle est fusionnée — espacements corrigés et titres passés aux composants communs `<x-page-heading>` (H1) et `<x-section-heading>` (H2) de la 001
- [X] T094 [ux-writing] Un seul verbe par action (« Supprimer » une photo, « Retirer » une vue : harmoniser) ; messages de refus qui disent quoi faire (`damages.php`, `photos.php`) ; taille explicite du badge d'étape sur le téléphone ; ne réécrire que les refus qui ne disent pas quoi faire (`step_validated`, `already_resolved`, `return_photos_incomplete`) ; les textes fixés par `contracts/screens.md` restent
- [X] T095 [Couverture FR-012] Test vérifiant que le panneau se met à jour à la réception d'une photo (`PhotosPanel::getListeners` et rafraîchissement sur `photo.changed`)
- [X] T096 Mettre à jour `plan.md` (arborescence, configuration du layer, Complexity Tracking), `data-model.md` et `quickstart.md` pour refléter la phase 11 (X6)

## Phase 12: Intégration de la 001 finale (0ccf727)

**Purpose**: Adapter `inspection` aux conventions livrées par la 001 terminée (fusionnée dans cette branche).

- [X] T097 Fusion de `origin/001-reservation-machines` (0ccf727) : `DatabaseSeeder` de la 001 conservé, `InspectionPermissionSeeder` appelé **avant** `PermissionSeeder` (qui donne `Permission::all()` au rôle `salarie`) et `InspectionSeeder` en dernier ; `InspectionPermissionSeeder` ajouté à la liste de `seedPermissions()` dans `tests/TestCase.php` (fichiers racine partagés, signalés) ; le seeder du layer ne touche jamais au rôle
- [X] T098 Disponibilité par section : enregistrer `inspection.photos-panel` comme gardienne de `ReservationTransition::Departure` et `Return` ; le panneau émet `reservation-transition-readiness` avec `section: 'inspection.photos-panel'` pour chaque étape ; test d'abord (bouton désactivé sans réponse, activé quand la section répond prête)
- [X] T099 Enum `Functional\Inspection\Access\InspectionPermission` (`damages.manage`, `inspection_views.manage`) : le seeder du layer crée seulement ses permissions ; routes, contrôles d'accès et `Gate` passent par l'enum ; canal `reservation.{id}` typé `Authorizable` ; tests d'accès avec `userWithPermissions()` / `userWithoutPermission()`
- [X] T100 Plus d'import de `App\Models\User` dans le layer : relations d'auteur via `auth.providers.users.model`, actions typées `Authenticatable&AgencyMember` (ou `Model`), factories via `Factory::factoryForModel`, tests via `Functional\Fleet\Tests\Concerns\CreatesUsers`
- [X] T101 Historique : chaque entrée d'`InspectionHistory` porte `author_agency_id` (même propriété que `RecordsAuthorAgency` de la 001 ; `inspection` n'utilise pas `LogsActivity`, l'historique est écrit explicitement) ; test d'abord
- [X] T102 Interface partagée : `<x-empty-state>` pour la liste des dégâts vide, `<x-loading-hint />` sur les écrans rafraîchis, confirmations par `<flux:modal>` au lieu de `wire:confirm` (suppression de photo sur le poste et le téléphone, retour aux vues par défaut, traitement d'un dégât), boutons de ligne `size="xs"`, message de connexion perdue de `lang/fr/screens.php`
- [X] T103 `InspectionSeeder` complète les réservations du `ReservationSeeder` de la 001 (en cours, clôturées, annulées) au lieu de créer réservations, machines et agences ; `DatabaseSeederTest` et `InspectionSeederTest` verts
- [X] T104 Mettre à jour `plan.md` et `quickstart.md` (permissions en enum, utilisateur via le contrat `AgencyMember`, disponibilité par section, seeder)

## Dependencies & Execution Order

### Phase Dependencies

- **Prérequis 001, phase 2** → bloque Setup, Foundational, la partie `inspection` de US1 (T025 à T030, T033 à T035, T038 et la logique de T031) et US4.
- **Prérequis 001, phase 5 avec points d'extension** (atteint, `1496e2c`) → débloque la fin de T031, T024, T032, la fin de T036 et T037, puis US2.
- **Setup (Phase 1)** → **Foundational (Phase 2)** → stories.
- **US1 (Phase 3)** : après la phase 2. MVP.
- **US2 (Phase 4)** : après US1 (réutilise `OpenPhotoSession`, `PhotosPanel`, `PhotosCompleteGuard`).
- **US3 (Phase 5)** : après US2 (un dégât exige des photos de retour).
- **US4 (Phase 6)** : après la phase 2 seulement ; parallélisable avec US1 à US3.
- **Polish (Phase 7)** : après les stories voulues.

### Story Completion Order

```text
001 phase 2 → Setup → Foundational ─┬─► US1 (inspection) ─┐
                                    └─► US4 ──────────────┤
001 phase 5 + points d'extension ─────────────────────────┴─► US1 (blocage) → US2 → US3 → Polish
```

### Within Each User Story

- Tests écrits et en échec avant l'implémentation.
- Modèles → actions → guards / listeners → composants Livewire → traductions.

### Parallel Opportunities

- Phase 1 : T004, T005, T006.
- Phase 2 : T013 à T017 et T023 (fichiers distincts).
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

1. Rebaser sur la 001 (phase 2), phases 1 et 2, puis la partie `inspection` de US1 et US4.
2. Attendre la phase 5 de la 001 (avec les points d'extension), rebaser.
3. US1 : photos de départ et sortie bloquée. **Démo possible** : c'est déjà la preuve d'état au départ.

### Incremental Delivery

1. US1 → démo « départ ».
2. US2 → la paire départ / retour est complète, la clôture est bloquée.
3. US3 → les dégâts sont signalés et listés : la perte de 85 000 € est adressée.
4. US4 → adaptation des vues par catégorie.
5. Polish → historique, rétention, contrôle avec un vrai téléphone.

## Notes

- Les points d'extension de `booking` (ex-T007 à T012) sont réalisés dans la 001 ; `inspection` ne fait que les remplir depuis son service provider.
- Un test vert sans téléphone réel ne prouve pas la caméra : T067 est obligatoire avant la démo client.
- Aucun commit avant validation de chaque phase.
