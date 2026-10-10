# Research: Cœur de réservation des machines

Décisions techniques prises pour [plan.md](plan.md). Chaque entrée : décision, raison, alternatives écartées.

## R1 — Stack applicative

- **Decision**: une seule application Laravel (dernière version stable au moment du scaffold), interface en Livewire via le starter kit Livewire officiel. Pas d'API REST ni de front séparé.
- **Rationale**: demande explicite « 100 % Laravel » et stack web par défaut Xefi. Les 85 utilisateurs sont tous des salariés sur poste d'agence : une application serveur avec rendu Livewire couvre le besoin sans contrat d'API à maintenir.
- **Alternatives considered**: Laravel + Nuxt (deux bases de code pour un outil interne, rejeté) ; Laravel + Inertia/Vue (sort du « 100 % Laravel »).

## R2 — Architecture du code

- **Decision**: `xefi/laravel-osdd`, deux layers métier : `fleet` (agences, catégories, machines, VGP, import du parc) et `booking` (clients, réservations, disponibilité, conflits). `app/` ne garde que la colle (utilisateurs du starter kit, layout, routes).
- **Rationale**: standard Xefi pour un nouveau projet Laravel. Les specs suivantes (photos, caution, facturation, occasion) deviendront de nouveaux layers sans toucher aux existants.
- **Alternatives considered**: arborescence `app/` plate (non standard Xefi, mélange les domaines dès la 2e spec).

## R3 — Base de données et garantie anti-chevauchement

- **Decision**: PostgreSQL. Le non-chevauchement est garanti par **une contrainte d'exclusion** (`EXCLUDE USING gist` sur `machine_id` + `daterange(start_date, end_date, '[]')`, hors réservations annulées), doublée d'un verrou `lockForUpdate()` sur la ligne machine pendant la création pour renvoyer un message de refus précis.
- **Rationale**: FR-009 exige qu'une seule réservation passe en cas de validations simultanées. Une vérification applicative seule laisse une fenêtre de concurrence ; la contrainte d'exclusion rend la double réservation impossible au niveau de la base, quelle que soit la façon dont la ligne est écrite.
- **Mise en œuvre**: une violation de la contrainte (SQLSTATE `23P01`) est traduite en refus de chevauchement dans `CreateReservation` via `rescue()`, et non dans `bootstrap/app.php` : Livewire intercepte les exceptions avant le gestionnaire HTTP. Les refus métier héritent de `RefusalException` et sont affichés par le trait Livewire `DisplaysRefusals`, sans `try/catch`.
- **Alternatives considered**: MySQL + verrou applicatif seul (garantie non portée par la base) ; table de « jours réservés » avec index unique (machine, jour) — fonctionne en MySQL mais multiplie les lignes et complique les modifications de dates.

## R4 — Temps réel entre agences

- **Decision**: broadcasting Laravel sur **Soketi** (protocole Pusher, driver `pusher`), Soketi en service Sail en local et en conteneur dédié en production. Les composants Livewire écoutent via Laravel Echo (`echo-private:fleet,...`) et se rafraîchissent.
- **Rationale**: SC-006 (visible en moins de 5 s dans les autres agences, FR-018 sans rafraîchissement manuel). Soketi est la solution temps réel standard Xefi.
- **Mise en œuvre**: les événements implémentent `ShouldBroadcast` et `ShouldDispatchAfterCommit` (rien n'est diffusé pour une modification annulée) ; un service `queue` de `compose.yaml` exécute les diffusions.
- **Alternatives considered**: polling Livewire (`wire:poll`) — simple mais charge inutile et latence fixe ; Reverb — non standard Xefi.
- **Action infra**: demander à l'infra un conteneur Soketi dédié avant la mise en production.

## R5 — Cycles de vie (réservation, machine)

- **Decision**: les statuts `ReservationStatus` (confirmed, in_progress, closed, cancelled) et `MachineStatus` (available, rented_out, workshop, out_of_order, retired) sont des colonnes texte castées en enums PHP, et leurs transitions sont portées par le **pattern State** (une classe par état, exception typée sur transition illégale).
- **Rationale**: plusieurs transitions avec préconditions (sortie bloquée si VGP invalide, annulation impossible après sortie). Le pattern State centralise ces règles au lieu de les répartir entre composants Livewire.
- **Alternatives considered**: méthodes procédurales `depart()` / `return()` avec des `if` sur le statut (règles dispersées, oublis silencieux).

## R6 — Droits

- **Decision**: `spatie/laravel-permission` pour le stockage, `lomkit/laravel-access-control` pour les contrôles. Un seul rôle « salarié » qui porte toutes les permissions (`machines.manage`, `reservations.manage`, `users.manage`) ; le code ne teste que des permissions, jamais des noms de rôle.
- **Rationale**: le client veut les mêmes droits pour tous dans cette version, mais des rôles différenciés viendront. Avec des permissions dès le départ, il suffira de créer de nouveaux rôles sans modifier le code.
- **Alternatives considered**: aucun contrôle d'accès (à refaire entièrement le jour où les rôles arrivent).

## R7 — Import du parc

- **Decision**: import CSV ou XLSX via `spatie/simple-excel` (lecture en flux), une ligne = une machine ; chaque ligne est validée individuellement et les lignes rejetées sont restituées avec leur motif.
- **Rationale**: FR-005 et SC-007 (400 machines en une opération, aucun doublon). Le client tient son parc dans un tableur.
- **Alternatives considered**: `maatwebsite/excel` (plus lourd, inutile pour une lecture simple) ; saisie manuelle des 400 machines.

## R8 — Traçabilité

- **Decision**: `spatie/laravel-activitylog` sur Machine et Reservation (auteur, date, anciennes / nouvelles valeurs). L'agence de l'auteur est lue sur l'utilisateur.
- **Rationale**: FR-022. Package recommandé Xefi pour l'audit.
- **Alternatives considered**: table d'historique maison (réinvente le package).

## R9 — Détection des conflits

- **Decision**: une réservation porte un champ `conflict_reason` (null si aucun conflit). Il est recalculé par des listeners sur les événements « statut de machine changé » et « date VGP changée », et par une tâche planifiée quotidienne qui détecte les retours en retard.
- **Rationale**: FR-019 et cas limites. Les conflits doivent être listés sans être recalculés à chaque affichage.
- **Précision (clarification 2026-10-10)**: en cas de retard de retour, seule la prochaine réservation `confirmed` de la machine est signalée `machine_not_returned` ; une annulation efface le motif.
- **Alternatives considered**: calcul à la volée à chaque affichage (coûteux sur le planning, et impossible de lister « toutes les réservations en conflit » efficacement) ; observers Eloquent (interdits par les règles Xefi).

## R10 — Environnement de développement

- **Decision**: Laravel Sail (Docker). Services : PostgreSQL, Soketi, Mailpit. Queue `database` avec un worker Sail pour le broadcasting.
- **Rationale**: le poste de développement a Docker et WSL mais pas PHP ni Composer. Sail fournit tout.
- **Alternatives considered**: installer PHP et Composer sous Windows (environnement différent de la production).

## R11 — Qualité

- **Decision**: PHPUnit (tests Feature pour chaque scénario d'acceptation, tests Unit pour les classes d'état), Larastan niveau 7 minimum avec `xefi/phpstan-xefi-rules`, `laravel/boost` pour l'outillage Claude, `xefi/faker-php-laravel` pour les factories.
- **Rationale**: conventions Xefi obligatoires.

## R12 — Points d'extension de `booking`

- **Decision**: `booking` expose deux points d'extension, sans connaître les layers qui les utilisent. (1) L'interface `ReservationTransitionGuard` (`beforeDeparture`, `beforeReturn`) : les guards enregistrés dans le registre `ReservationTransitionGuards` sont appelés par `DepartReservation` et `ReturnReservation` dans leur transaction, avant tout changement ; un guard refuse en levant une sous-classe de `RefusalException` : message technique en anglais (`getMessage()`), texte affiché via `userMessage()` (clé de traduction et remplacements). (2) Le registre `ReservationDetailSections` (composant Livewire + position) : l'écran de détail rend chaque section enregistrée et écoute l'événement Livewire `reservation-transition-readiness` `{ step, is_ready }`, où `step` est une valeur de l'enum `Functional\Booking\Enums\ReservationTransition` (`departure`, `return`) auquel les layers supérieurs se rattachent ; le bouton d'une étape s'active dès qu'une section a répondu `is_ready = true` pour cette étape, et reste actif sans section enregistrée. Les registres vivent dans `src/Extensions/`. Une section n'affiche pas de bouton primaire : le primaire du détail reste l'action de transition (sortie ou retour), une seule action primaire par écran.
- **Rationale**: la feature 002 (photos de départ et de retour) doit bloquer la sortie et le retour côté serveur et afficher son panneau dans le détail, tout en gardant le sens de dépendance `inspection → booking`. Le même mécanisme servira à la caution. Repris de la recherche P2 / P3 de la 002.
- **Alternatives considered**: appeler la 002 depuis `booking` (dépendance inversée) ; un événement « avant sortie » (un listener ne peut pas annuler proprement la transition) ; un dossier `Support/` (fourre-tout, écarté au profit d'`Extensions/`). Les tests de `booking` lient des registres vides (`WithoutTransitionExtensions`) pour rester indépendants des layers qui s'y branchent.

