---

description: "Task list for feature 005-attestation-vgp"
---

# Tasks: Envoi automatique de l'attestation VGP au client

**Input**: Design documents from `specs/005-attestation-vgp/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/extension-points.md), [quickstart.md](quickstart.md)

**Tests**: Inclus (constitution, principe VI) : un test Feature PHPUnit par scénario d'acceptation, des tests Unit pour les états et la classification des échecs. Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : feature 001 corrigée et points d'extension

Cette feature s'appuie sur la 001 telle que corrigée par la session de coordination (commit `e8cba36` et suivants) :

- `Functional\Booking\Events\ReservationChanged` (`ShouldDispatchAfterCommit`), `Reservation`, `Customer`, `ReservationStatus`, `Functional\Booking\Enums\ReservationTransition` ;
- `Functional\Booking\Extensions\ReservationTransitionGuards` + `Functional\Booking\Contracts\ReservationTransitionGuard` (appelé dans la transaction de `DepartReservation`) ;
- `Functional\Booking\Extensions\ReservationDetailSections` ;
- `abstract Functional\Fleet\Exceptions\RefusalException` (constructeur protégé : message technique anglais, clé de traduction, replacements), `DisplaysRefusals`, `AssertsRefusals::assertRefused` ;
- `Functional\Fleet\Actions\UpdateMachineVgp`.

Avant T004 :

1. la 003 corrigée est commitée et la branche `005-attestation-vgp` est mise à jour par-dessus (feu vert de la session de coordination) ;
2. la 004 a poussé son contrat client et la phase 0 est terminée (`changeEmail()` ajoutée à `UpdateCustomer`) ;
3. `docker compose exec -u sail laravel.test php artisan test` passe sur la branche mise à jour ;
4. l'environnement Sail de la feature est isolé : `.env` copié depuis `/Users/macbook/Documents/Vallet-Location/.env`, `COMPOSE_PROJECT_NAME=vallet-005` et ports libres ([quickstart.md](quickstart.md#prérequis)).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US5)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : commandes via `docker compose exec -u sail laravel.test …`, code en anglais, textes dans `functional/certification/resources/lang/fr/*.php`, statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (`rescue()` qui relance tout ce qui n'est pas l'exception attendue), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers < 200 lignes, agrégats en base, pas de requête dans une boucle. En plus :

- `certification` dépend de `booking` et `fleet` ; **aucun fichier de `booking`, `fleet`, `inspection` ni `billing` n'importe une classe de `certification`** ; `Machine`, `Reservation` et `Customer` ne déclarent aucune relation vers les tables de `certification`.
- `certification` n'écrit dans aucune table d'un autre layer : l'échéance passe par `UpdateMachineVgp`, l'e-mail par `UpdateCustomer::changeEmail()`.
- Tout e-mail passe par `VgpCertificateNotification` (`toMail()` rend `VgpCertificateMail`) et `Notification::route('mail', …)` ; jamais `Mail::to()`.
- Chaque refus est une sous-classe `final` de `RefusalException` à factory nommée, testée avec `assertRefused`.
- Les tests utilisent `$this->travelTo()`, `Storage::fake('vgp-reports')`, `Notification::fake()` / `Mail::fake()` et le transport factice de T020 ; jamais d'envoi réel.
- Calculs en mémoire → tests Unit purs ; ce qui lit la base → tests Feature (constitution 1.0.1).
- Conventions de la 001 corrigée ([research.md](research.md#c13--conventions-de-la-001-corrigée) C13) : jamais `App\Models\User` dans un layer, auteur typé `Authenticatable&AgencyMember`, trait `RecordsAuthorAgency` si la forme s'y prête, trait de test `CreatesUsers`, permissions en enum `CertificationPermission`, `x-empty-state`, `x-loading-hint`, `flux:modal`, `Flux::toast`.
- `UpdateCustomer` et `CustomerChanged` viennent de la 004 : T001–T002 ajoutent seulement la méthode `changeEmail()` et ses tests.

---

## Phase 0: Prérequis dans `booking` (arbitrage de la session de coordination)

**Purpose**: [contracts/extension-points.md](contracts/extension-points.md#demandés-à-la-001-à-livrer-avant-limplémentation). Le contrat client est livré par la 004 (`contracts/customer-contract.md` de la 004) : `Functional\Booking\Actions\UpdateCustomer` (méthode `qualify()`), `Functional\Booking\Events\CustomerChanged(Customer $customer, list<string> $changedAttributes)` (`ShouldDispatchAfterCommit`), enum `CustomerType`. Cette feature y ajoute seulement la méthode `changeEmail()` sur le même modèle ; la disponibilité par section est livrée par la 001. Avant T001, vérifier ces noms sur la branche mise à jour ; signaler tout écart à la session de coordination.

- [X] T001 Écrire `functional/booking/tests/Feature/UpdateCustomerChangeEmailTest.php` (à côté du `UpdateCustomerQualifyTest.php` de la 004) pour `changeEmail()` : mise à jour de `customers.email` ; aucun effet ni événement si l'e-mail est inchangé ; refus d'un e-mail vide ou invalide (`assertRefused`) ; trace dans le journal d'activité du client ; `CustomerChanged` émis après commit avec `changedAttributes = ['email']` (`Event::fake([CustomerChanged::class])`)
- [X] T002 Ajouter à `functional/booking/src/Actions/UpdateCustomer.php` (créée par la 004) la méthode `changeEmail(Customer $customer, string $email, Authenticatable&AgencyMember $author): Customer` sur le modèle de `qualify()` (transaction + `lockForUpdate()`, no-op si inchangé, puis `CustomerChanged($customer, ['email'])` après commit), et créer `functional/booking/src/Exceptions/InvalidCustomerEmailException.php` (`final`, hérite de `RefusalException`) avec ses traductions dans `functional/booking/resources/lang/fr/customers.php` ; T001 passe
- [X] T003 Vérifier sur la branche mise à jour que le contrat par section de la 001 (`7423fc9`) est présent dans `functional/booking/src/Extensions/ReservationDetailSections.php` et `functional/booking/src/Livewire/ReservationDetail.php` : `register(string $name, int $position, ReservationTransition $guardedTransition)` et `reservation-transition-readiness` avec `step`, `section`, `is_ready` ; relever aussi `AgencyMember`, `CreatesUsers`, `RecordsAuthorAgency` et la convention des enums de permissions ; signaler tout écart à la session de coordination avant T004

**Checkpoint**: suite complète verte ; commit de phase.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `certification`, configuration, disque des rapports, permission.

- [ ] T004 Créer le layer OSDD `functional/certification/` avec les commandes `osdd:*` (`composer.json` LayerManifest dépendant de `functional/booking` et `functional/fleet`, namespace `Functional\Certification\`, `CertificationServiceProvider`, dossiers `src/`, `config/`, `database/{migrations,factories,seeders}/`, `resources/{views,lang/fr}/`, `routes/`, `tests/{Feature,Unit}/`) ; déclarer `functional/certification` dans le `require` et l'`autoload-dev` (`Functional\\Certification\\Tests\\`) du `composer.json` racine, mettre à jour `composer.lock` ; ajouter `functional/certification/{src,database,routes,config}` à `phpstan.neon` et `functional/certification/src` à la `<source>` de `phpunit.xml`
- [ ] T005 [P] Créer `functional/certification/config/certification.php` avec les clés de [data-model.md](data-model.md#configuration--functionalcertificationconfigcertificationphp) : `go_live_date` = `env('CERTIFICATION_GO_LIVE_DATE')` (AAAA-MM-JJ, sans défaut), `timezone` = `Europe/Paris`, `retry_delays_minutes` = `[1, 5, 15, 60]`, `alert_after_minutes` = 60, `accepted_mimes` = `['pdf', 'jpg', 'jpeg', 'png']`, `max_report_kilobytes` = 10240, `reports_disk` = `vgp-reports` ; la fusionner depuis le service provider
- [ ] T006 [P] Déclarer le disque privé `vgp-reports` (driver `local`, racine `storage/app/private/vgp-reports`, `throw` vrai, non public) dans `functional/certification/config/filesystems.php`, chargé par `overrideConfigFrom(…, 'filesystems')` dans le provider (même forme que `billing`), et ajouter `CERTIFICATION_GO_LIVE_DATE=` dans `.env.example`
- [ ] T007 [P] Créer l'enum `functional/certification/src/Enums/CertificationPermission.php` (`Manage = 'certification.manage'`) et `functional/certification/database/seeders/CertificationPermissionSeeder.php` qui crée seulement la permission (convention de la 001 corrigée relevée en T003), attribuée au rôle salarié par le mécanisme central de la 001 ; appeler le seeder depuis `database/seeders/DatabaseSeeder.php` ; routes et contrôles utilisent `CertificationPermission::Manage->value`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tables, modèles, cycle de vie de l'attestation, ouverture idempotente, historique, mise en service. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### Enums et tables

- [ ] T008 [P] Créer les enums backed `CertificateStatus` (`awaiting_report`, `awaiting_email`, `pending`, `failed`, `sent`, `hand_delivered`, avec `isDelivered()` vrai pour `sent` et `hand_delivered`), `DispatchChannel` (`email`, `hand`), `DispatchOutcome` (`sent`, `failed`), `DispatchFailureReason` (`invalid_address`, `recipient_rejected`, `mail_service_unavailable`, avec `isPermanent()` faux seulement pour `mail_service_unavailable`) dans `functional/certification/src/Enums/`, libellés de [data-model.md](data-model.md) dans `functional/certification/resources/lang/fr/enums.php`
- [ ] T009 Créer la migration `vgp_reports` dans `functional/certification/database/migrations/` : `machine_id` (`->constrained('machines')` sans cascade), `file_path` string, `original_name` string, `mime_type` string, `size_bytes` integer, `verified_on` date, `due_on` date, `deposited_by` (`->constrained('users')`), timestamps ; CHECK `due_on > verified_on` ; index `(machine_id, id)`
- [ ] T010 Créer la migration `reservation_certificates` dans `functional/certification/database/migrations/` : `reservation_id` (`->constrained('reservations')` sans cascade, **unique**), `status` string avec CHECK sur les 6 valeurs de `CertificateStatus`, `attempts` integer ≥ 0, `next_attempt_at` timestamp nullable, `last_failure_reason` string nullable, `status_changed_at` timestamp (date d'entrée dans l'état courant, mise à jour à chaque transition), `delivered_at` timestamp nullable avec CHECK « non nul si et seulement si `status` ∈ (`sent`, `hand_delivered`) », timestamps ; index `(status, next_attempt_at)` et `(status, status_changed_at)`
- [ ] T011 Créer la migration `certificate_dispatches` dans `functional/certification/database/migrations/` : `reservation_certificate_id` et `vgp_report_id` (`->constrained()` sans cascade), `channel` string CHECK (`email`, `hand`), `recipient_email` string nullable (CHECK non nul si `channel = 'email'`), `is_automatic` boolean (CHECK faux si `channel = 'hand'`), `author_id` nullable `->constrained('users')` (CHECK non nul si `is_automatic` est faux), `outcome` string CHECK (`sent`, `failed`) et `sent` si `channel = 'hand'`, `failure_reason` string nullable (CHECK non nul si et seulement si `outcome = 'failed'`), `attempted_at` timestamp, `created_at` ; **index unique partiel** `(reservation_certificate_id) WHERE is_automatic AND outcome = 'sent'` (depends on T009, T010)

### Modèles et états

- [ ] T012 [P] Créer `functional/certification/src/Models/VgpReport.php` (relations `machine()`, `depositor()` ; le rapport en vigueur se lit par la requête de T017) et `functional/certification/database/factories/VgpReportFactory.php` (fichier PDF factice écrit sur le disque `vgp-reports`, `due_on` dans 6 mois)
- [ ] T013 [P] Créer `functional/certification/src/Models/ReservationCertificate.php` (casts `status` → `CertificateStatus`, `last_failure_reason` → `DispatchFailureReason`, dates ; relations `reservation()`, `dispatches()`, `lastDispatch()` ; `state()` via la fabrique de T015) et sa factory avec un état par statut
- [ ] T014 [P] Créer `functional/certification/src/Models/CertificateDispatch.php` (sans `updated_at`, casts des enums ; relations `certificate()`, `report()`, `author()`) et sa factory
- [ ] T015 Écrire `functional/certification/tests/Unit/CertificateStateTest.php` couvrant chaque case du tableau de [research.md](research.md#c4--lattestation-en-pattern-state) (transitions permises et `IllegalCertificateTransitionException` pour les autres), puis créer `functional/certification/src/States/CertificateState.php`, les six classes d'état, `CertificateStateFactory` et `functional/certification/src/Exceptions/IllegalCertificateTransitionException.php` ; le test passe

### Services transverses

- [ ] T016 [P] Créer `functional/certification/src/Calendar/CertificationCalendar.php` (même forme que `BillingCalendar`) (`hasGoLiveDate()`, `goLiveDate()`, `isLive()` à l'heure de Paris ; `MissingGoLiveDateException` si la date est vide ou invalide, dans `functional/certification/src/Exceptions/`) et son test `functional/certification/tests/Unit/CertificationCalendarTest.php` avec `travelTo()`
- [ ] T017 [P] Créer `functional/certification/src/Queries/ReportInForce.php` (`for(Machine $machine): ?VgpReport` et `forMachines(Collection $machines): Collection` en une requête, rapport d'`id` maximal par machine) et son test Feature
- [ ] T018 [P] Créer `functional/certification/src/History/CertificationHistory.php` (journal `certification`, même forme que `BillingHistory` : auteur lu par `Auth::user()` s'il est `AgencyMember`, propriété `author_agency_id`, sujet `Reservation` ou `Machine`) et l'enum `functional/certification/src/Enums/CertificationHistoryEvent.php` (événements de [data-model.md](data-model.md#historique), `description()` traduite) avec les libellés dans `functional/certification/resources/lang/fr/history.php`
- [ ] T019 Écrire `functional/certification/tests/Feature/OpenReservationCertificateTest.php` (une seule attestation même appelée deux fois ou en concurrence ; état initial `awaiting_report` sans rapport, `awaiting_email` sans e-mail, `pending` sinon ; rien pour une machine non soumise à VGP, une réservation non confirmée ou avant la mise en service), puis créer `functional/certification/src/Actions/OpenReservationCertificate.php`, `functional/certification/src/Actions/ResolveCertificateReadiness.php` (calcule l'état, met en file `SendCertificateJob` quand il devient `pending`) et `functional/certification/src/Jobs/SendCertificateJob.php` (`ShouldQueue`, `ShouldBeUnique` par `certificateId`, `afterCommit()` ; `handle()` vide à ce stade, l'envoi est branché en T027 — le test vérifie seulement la mise en file avec `Queue::fake()`) ; le test passe
- [ ] T020 [P] Créer le transport mail factice des tests `functional/certification/tests/Doubles/SimulatedMailTransport.php` (modes « accepte », « indisponible » — `TransportException` sans code 5xx —, « destinataire refusé » — code 550) et le trait `functional/certification/tests/Concerns/SimulatesMailTransport.php` qui l'enregistre via `Mail::extend()` et bascule `mail.default`
- [ ] T021 [P] Créer `functional/certification/src/Events/CertificateChanged.php` (`ShouldBroadcast`, `ShouldDispatchAfterCommit`, canal privé `fleet`, `broadcastAs()` = `certificate.changed`, `broadcastWith()` = `{reservation_id, status, delivered_at}`), émis par chaque transition d'état

**Checkpoint**: migrations, modèles, états et ouverture testés ; suite complète verte ; commit de phase.

---

## Phase 3: User Story 1 - Envoyer automatiquement l'attestation dès la réservation (Priority: P1) 🎯 MVP

**Goal**: Une réservation confirmée d'une machine soumise à VGP, avec rapport déposé et client avec e-mail, part par e-mail sans action ; la réservation affiche l'état.

**Independent Test**: [spec.md US1](spec.md#user-story-1---envoyer-automatiquement-lattestation-au-client-dès-la-réservation-priority-p1) — réservation d'une nacelle : e-mail avec rapport en pièce jointe, section « envoyée » ; mini-pelle : rien.

### Tests for User Story 1 ⚠️

- [ ] T022 [P] [US1] Écrire `functional/certification/tests/Feature/AutomaticCertificateTest.php` : sc. 1 (`Notification::assertSentOnDemand` vers l'adresse du client, attestation `sent`, `delivered_at`, trace `is_automatic`), sc. 2 (machine non soumise, avec ou sans rapport : aucune attestation, aucune notification), sc. 3 (création confirmée sans envoi synchrone : `Queue::fake()`, job mis en file après commit), sc. 4 (réservation annulée avant le traitement du job : rien envoyé, attestation inchangée), sc. 5 (historique `certificate_sent` avec adresse, rapport et date) ; plus : deux exécutions du job n'envoient qu'une fois (FR-009)
- [ ] T023 [P] [US1] Écrire `functional/certification/tests/Feature/VgpCertificateMailTest.php` : contenu de [contracts/certificate-email.md](contracts/certificate-email.md#contenu-vgpcertificatemail-gabarit-markdown-certificationmailvgp-certificate-en-français) (objet, nom du client, référence et catégorie, dates `d/m/Y`, agence, dates de VGP du rapport envoyé) et pièce jointe nommée `VGP-{référence}-{date}.{ext}` avec le contenu du fichier déposé

### Implementation for User Story 1

- [ ] T024 [US1] Créer `functional/certification/src/Mail/VgpCertificateMail.php` (Mailable, `Envelope` avec l'objet traduit, `Content` markdown `certification::mail.vgp-certificate`, `attachments()` via `Attachment::fromStorageDisk`) et la vue `functional/certification/resources/views/mail/vgp-certificate.blade.php`, textes dans `functional/certification/resources/lang/fr/mail.php` ; T023 passe
- [ ] T025 [US1] Créer `functional/certification/src/Notifications/VgpCertificateNotification.php` (`via()` = `['mail']`, `toMail()` rend `VgpCertificateMail` avec `->to()` sur l'adresse routée)
- [ ] T026 [US1] Créer `functional/certification/src/Actions/SendCertificate.php` : transaction, `lockForUpdate()` sur l'attestation, sortie silencieuse si elle n'est pas `pending`, si `next_attempt_at` est futur, si la réservation n'est plus confirmée ou la machine plus soumise à VGP ; `Notification::route('mail', $email)->notifyNow(...)` avec le rapport en vigueur ; trace `certificate_dispatches` (`is_automatic`, `outcome = sent`) ; transition `markSent()` ; historique `certificate_sent` ; échecs : seulement relancés tels quels ici (classement en US4)
- [ ] T027 [US1] Brancher `SendCertificate` dans `handle()` de `functional/certification/src/Jobs/SendCertificateJob.php` et vérifier que deux jobs pour la même attestation ne l'envoient qu'une fois (FR-009, dans `AutomaticCertificateTest.php`)
- [ ] T028 [US1] Créer `functional/certification/src/Listeners/OpenCertificateOnReservationChanged.php` (`ShouldQueue`, `ShouldQueueAfterCommit`, appelle `OpenReservationCertificate` si la mise en service est atteinte) et l'enregistrer avec `Event::listen(ReservationChanged::class, …)` dans `CertificationServiceProvider` ; T022 passe
- [ ] T029 [US1] Créer la section `functional/certification/src/Livewire/ReservationCertificateSection.php` + `functional/certification/resources/views/livewire/reservation-certificate-section.blade.php` (contenu masqué si machine non soumise ou avant la mise en service, mais la section émet toujours sa disponibilité — voir T046 ; état, adresse, date du dernier envoi, tentatives ; rafraîchie sur `echo-private:fleet,.certificate.changed`), enregistrée comme gardienne de la sortie : `app(ReservationDetailSections::class)->register('certification.reservation-section', 30, ReservationTransition::Departure)` ; son test `functional/certification/tests/Feature/ReservationCertificateSectionTest.php` (affichage par état, absence pour une mini-pelle)

**Checkpoint**: US1 livrable seule (MVP) ; suite complète verte ; Larastan à zéro ; commit de phase.

---

## Phase 4: User Story 2 - Déposer le rapport de VGP d'une machine (Priority: P1)

**Goal**: Les agences déposent les rapports ; l'échéance de la machine suit ; les attestations en attente de rapport partent.

**Independent Test**: [spec.md US2](spec.md#user-story-2---déposer-le-rapport-de-vgp-dune-machine-priority-p1) — dépôt sur une nacelle avec une réservation en attente de rapport : l'e-mail part.

### Tests for User Story 2 ⚠️

- [ ] T030 [P] [US2] Écrire `functional/certification/tests/Feature/DepositVgpReportTest.php` : sc. 1 (rapport créé, fichier sur `vgp-reports`, `machines.vgp_due_date` = `due_on` via `UpdateMachineVgp`, `MachineChanged` émis, historique machine `vgp_report_deposited`), sc. 2 (réservation sans rapport : `awaiting_report`), sc. 3 (dépôt → attestation `pending` puis `sent`), sc. 4 (format hors `pdf, jpg, jpeg, png`, taille > 10240 Ko, dates manquantes, `due_on <= verified_on`, machine non soumise à VGP : `assertRefused` avec `InvalidVgpReportException`), sc. 5 (nouveau rapport en vigueur, l'ancien toujours listé)
- [ ] T031 [P] [US2] Écrire `functional/certification/tests/Feature/VgpReportScreensTest.php` : liste des machines soumises à VGP avec rapport en vigueur ou « aucun rapport » (filtre agence et « sans rapport »), page VGP d'une machine avec ses rapports successifs, téléchargement réservé à `certification.manage` (403 sinon), nombre de requêtes constant pour 50 machines

### Implementation for User Story 2

- [ ] T032 [US2] Créer `functional/certification/src/Exceptions/InvalidVgpReportException.php` (`final`, `RefusalException`, factories `notSubjectToVgp()`, `invalidFile()`, `inconsistentDates()`) avec ses traductions dans `functional/certification/resources/lang/fr/refusals.php`
- [ ] T033 [US2] Créer `functional/certification/src/Actions/DepositVgpReport.php` (`handle(Authenticatable&AgencyMember $author, Machine $machine, UploadedFile $file, CarbonImmutable $verifiedOn, CarbonImmutable $dueOn): VgpReport` : validation, stockage sous nom généré, transaction avec création du rapport, `UpdateMachineVgp`, historique machine) et `functional/certification/src/Events/VgpReportDeposited.php` (`ShouldDispatchAfterCommit`)
- [ ] T034 [US2] Créer `functional/certification/src/Listeners/ResolveCertificatesOnReportDeposited.php` (attestations `awaiting_report` des réservations confirmées de la machine, chargées en une requête, puis `ResolveCertificateReadiness`) et l'enregistrer dans le provider ; T030 passe
- [ ] T035 [US2] Créer `functional/certification/src/Livewire/VgpMachines.php` et `functional/certification/src/Livewire/MachineVgpReports.php` (formulaire de dépôt avec `WithFileUploads`, liste des rapports), leurs vues dans `functional/certification/resources/views/livewire/`, `functional/certification/src/Http/Controllers/VgpReportFileController.php`, les contrôles `functional/certification/src/Access/Controls/VgpReportControl.php` et les routes `/vgp/machines`, `/vgp/machines/{machine}`, `/vgp/rapports/{report}/fichier` sous `can:certification.manage` dans `functional/certification/routes/web.php` ; T031 passe
- [ ] T036 [US2] Ajouter l'entrée « Conformité VGP » > « Rapports VGP » sous `@can('certification.manage')` dans `resources/views/layouts/app/sidebar.blade.php`

**Checkpoint**: US1 et US2 fonctionnent ensemble ; suite verte ; commit de phase.

---

## Phase 5: User Story 3 - Bloquer la sortie tant que l'attestation n'est pas envoyée ou remise (Priority: P1)

**Goal**: La sortie est refusée par le serveur tant que l'attestation n'est pas livrée ; l'e-mail se saisit depuis la réservation ; la remise en main propre débloque.

**Independent Test**: [spec.md US3](spec.md#user-story-3---bloquer-la-sortie-tant-que-lattestation-nest-pas-envoyée-ou-remise-priority-p1) — client sans e-mail : sortie refusée ; e-mail saisi : envoi puis sortie possible ; ou remise en main propre.

### Tests for User Story 3 ⚠️

- [ ] T037 [P] [US3] Écrire `functional/certification/tests/Feature/DepartureGuardTest.php` : sc. 1 (`awaiting_email` affiché), sc. 2 (sortie refusée pour chacun des motifs `awaiting_email`, `awaiting_report`, `pending`, `failed`, attestation absente → refus « en attente » sans création (la transaction de sortie est annulée), via `DepartReservation` et `assertRefused`), sc. 4 (attestation `sent` : la garde laisse passer, les autres gardes enregistrées restent appelées — garde factice qui refuse enregistrée dans le test), sc. 5 (machine non soumise : passe), avant la mise en service : passe, retour jamais bloqué
- [ ] T038 [P] [US3] Écrire `functional/certification/tests/Feature/CustomerEmailFromReservationTest.php` : sc. 3 (saisie de l'e-mail depuis la section → `UpdateCustomer::changeEmail()` → `CustomerChanged` → attestation `pending` puis `sent`, historique `customer_email_updated`), e-mail invalide refusé, l'attestation d'une autre réservation confirmée du même client part aussi, une attestation `failed` (`invalid_address`) n'est pas relancée par un `CustomerChanged` qui laisse l'e-mail inchangé
- [ ] T039 [P] [US3] Écrire `functional/certification/tests/Feature/HandDeliveryTest.php` : sc. 7 (remise → `hand_delivered`, `delivered_at`, trace `channel = hand` avec auteur et rapport, historique, sortie acceptée, absente de la liste à traiter), sc. 8 (sans rapport : `assertRefused` `HandDeliveryRefusedException`), réservation non confirmée refusée, attestation déjà livrée refusée
- [ ] T040 [P] [US3] Écrire `functional/certification/tests/Feature/DepartureConcurrencyTest.php` : sc. 6 — la garde lit l'attestation sous `lockForUpdate()` ; une sortie vérifiée pendant que l'attestation est `pending` est refusée, et acceptée une fois `sent` commitée (deux connexions ou vérification du verrou par `DB::listen` sur `for update`)

### Implementation for User Story 3

- [ ] T041 [US3] Créer `functional/certification/src/Exceptions/CertificateNotDeliveredException.php` (`final`, `RefusalException`, factory `because(CertificateStatus $status, ?DispatchFailureReason $reason)`, message traduit par motif) et `functional/certification/src/Exceptions/HandDeliveryRefusedException.php` (`noReport()`, `reservationNotConfirmed()`, `alreadyDelivered()`), traductions dans `refusals.php`
- [ ] T042 [US3] Créer `functional/certification/src/Guards/CertificateDeliveredGuard.php` ([research.md](research.md#c6--la-garde-de-sortie-dans-la-transaction-de-la-001) C6) et l'enregistrer dans `ReservationTransitionGuards` depuis le provider ; T037 et T040 passent
- [ ] T043 [US3] Créer `functional/certification/src/Actions/RecordHandDelivery.php` (verrou, contrôles, trace `channel = hand`, `handDeliver()`, historique `certificate_hand_delivered`) ; T039 passe
- [ ] T044 [US3] Créer `functional/certification/src/Listeners/ResolveCertificatesOnCustomerChanged.php` (sans effet si `! in_array('email', $event->changedAttributes, true)` ; sinon attestations `awaiting_email` des réservations confirmées du client, et `failed` seulement si l'e-mail actuel diffère de `recipient_email` du dernier envoi raté, chargées en une requête, puis `ResolveCertificateReadiness`) et l'enregistrer sur `CustomerChanged` dans le provider
- [ ] T045 [US3] Ajouter à la section, dans des composants d'action séparés pour garder chaque fichier sous 200 lignes (`functional/certification/src/Livewire/CustomerEmailForm.php`, `functional/certification/src/Livewire/HandDeliveryButton.php`, rendus par `ReservationCertificateSection`), la saisie de l'e-mail du client (appel de `UpdateCustomer::changeEmail()`, historique `customer_email_updated`) et le bouton « Rapport VGP remis en main propre » avec confirmation (`flux:modal`), refus affichés par `DisplaysRefusals`, contrôle `functional/certification/src/Access/Controls/ReservationCertificateControl.php` ; T038 passe
- [ ] T046 [US3] La section émet au `mount()` et à chaque changement (réception de `certificate.changed`, saisie d'e-mail, remise) `dispatch('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: 'certification.reservation-section', is_ready: $isReady)`, avec `$isReady` vrai si la machine n'est pas soumise à VGP, avant la mise en service, ou si l'attestation est livrée — une gardienne muette bloquant la sortie, elle émet dans tous les cas ([research.md](research.md#c10--disponibilité-de-la-sortie-agrégée-par-section) C10) ; dans `functional/certification/src/Livewire/ReservationCertificateSection.php`, tests dans `functional/certification/tests/Feature/ReservationCertificateSectionTest.php` (émission `true` pour une mini-pelle et avant la mise en service, `false` puis `true` quand l'attestation passe `sent`, bouton de sortie de `ReservationDetail` actif seulement quand toutes les gardiennes ont répondu `true`)

**Checkpoint**: la règle « pas de sortie sans attestation » est garantie ; suite verte ; commit de phase.

---

## Phase 6: User Story 4 - Ne perdre aucun envoi en cas d'échec (Priority: P1)

**Goal**: Relances automatiques, échecs définitifs listés, alerte visible, rattrapage à la mise en service.

**Independent Test**: [spec.md US4](spec.md#user-story-4---ne-perdre-aucun-envoi-en-cas-déchec-priority-p1) — service indisponible : « en attente d'envoi », puis envoi unique au retour du service.

### Tests for User Story 4 ⚠️

- [ ] T047 [P] [US4] Écrire `functional/certification/tests/Unit/DispatchFailureClassifierTest.php` : `RfcComplianceException` → `invalid_address` ; transport 550, 551, 553, 554 → `recipient_rejected` ; transport sans code, 421, 450, délai → `mail_service_unavailable` ; autre exception → non classée (relancée)
- [ ] T048 [P] [US4] Écrire `functional/certification/tests/Feature/CertificateRetryTest.php` avec `SimulatesMailTransport` : sc. 1 (indisponible : réservation confirmée, attestation `pending`, `attempts` = 1, `next_attempt_at` = +1 min, trace `failed` `mail_service_unavailable`), délais 1, 5, 15, 60 puis 60 avec `travelTo()`, sc. 2 (service rétabli : `certification:reconcile` envoie une seule fois), refus définitif → `failed` sans relance
- [ ] T049 [P] [US4] Écrire `functional/certification/tests/Feature/CertificatesToHandleTest.php` : sc. 3 (liste : réservation, date de départ, client, adresse, date, motif en clair), contenu exact (`failed`, `awaiting_email`, `awaiting_report`, `pending` dont `status_changed_at` a plus de 60 min — y compris une attestation restée longtemps `awaiting_email` puis passée `pending` il y a 5 min, qui n'apparaît **pas** ; pas `pending` récente, ni livrée, ni réservation annulée, en cours ou clôturée), sc. 5 (tri par date de départ), sc. 4 (bandeau avec le compte, visible avec `certification.manage`), nombre de requêtes constant
- [ ] T050 [P] [US4] Écrire `functional/certification/tests/Feature/GoLiveCatchUpTest.php` : réservations confirmées créées avant `CERTIFICATION_GO_LIVE_DATE` (listener inactif) ; avant la date, `certification:reconcile` n'ouvre rien ; à la date, il ouvre et envoie chacune une seule fois, les réservations en cours ou clôturées ne sont pas concernées ; une réservation dont l'événement a été perdu est rattrapée ; deux exécutions successives n'ouvrent ni n'envoient rien de plus ; délai (SC-003) : une réservation créée à T est envoyée par le job mis en file après commit, et à défaut par le rattrapage avant T + 1 min (`travelTo()`) ; sans date configurée, la commande échoue avec `MissingGoLiveDateException`

### Implementation for User Story 4

- [ ] T051 [US4] Créer `functional/certification/src/Dispatches/DispatchFailureClassifier.php` ([research.md](research.md#c7--classer-les-échecs-denvoi) C7) ; T047 passe
- [ ] T052 [US4] Compléter `functional/certification/src/Actions/SendCertificate.php` : envoi dans `rescue()` qui classe les erreurs de transport et relance les autres ; temporaire → trace `failed`, `scheduleRetry()` (`attempts` + 1, `next_attempt_at` depuis `retry_delays_minutes`, dernière valeur répétée), historique `certificate_failed` ; définitif → `markFailed()` avec le motif ; T048 passe
- [ ] T053 [US4] Créer `functional/certification/src/Console/ReconcileCommand.php` (`certification:reconcile` : si la mise en service est atteinte, ouvre par lots `chunkById(100)` les attestations des réservations confirmées de machines soumises à VGP sans attestation — `whereNotExists` en SQL —, puis met en file `SendCertificateJob` pour les `pending` dont `next_attempt_at` est nul ou échu) et la planifier `everyMinute()->withoutOverlapping()` dans `functional/certification/routes/console.php` ; T050 passe
- [ ] T054 [US4] Créer `functional/certification/src/Queries/CertificatesToHandle.php` (requête unique avec jointures `reservations`, `customers`, `machines`, tri par `reservations.start_date`), `functional/certification/src/Livewire/CertificatesToHandle.php` + vue, route `/vgp/attestations`, `functional/certification/src/Livewire/CertificationAlert.php` + vue (rafraîchie sur `certificate.changed`), inclusion `<livewire:certification.alert />` sous `@can('certification.manage')` dans `resources/views/layouts/app.blade.php` et entrée « Attestations à traiter » dans `resources/views/layouts/app/sidebar.blade.php` ; T049 passe

**Checkpoint**: aucun envoi perdu ; suite verte ; commit de phase.

---

## Phase 7: User Story 5 - Renvoyer l'attestation à la demande (Priority: P2)

**Goal**: Renvoi en un clic depuis la réservation, avec le rapport en vigueur.

**Independent Test**: [spec.md US5](spec.md#user-story-5---renvoyer-lattestation-à-la-demande-priority-p2) — renvoi : second e-mail, deux envois dans l'historique.

### Tests for User Story 5 ⚠️

- [ ] T055 [P] [US5] Écrire `functional/certification/tests/Feature/ResendCertificateTest.php` : sc. 1 (renvoi sur réservation confirmée puis en cours : notification, trace manuelle avec auteur, historique), sc. 2 (attestation `failed` + e-mail corrigé → envoi et sortie de la liste — via T044), sc. 3 (réservation annulée ou clôturée : bouton absent et `assertRefused` `CertificateNotResendableException`), sc. 4 (nouveau rapport déposé : le renvoi joint le nouveau), renvoi sans rapport ou sans e-mail refusé, renvoi en échec immédiat : motif affiché, état d'une attestation livrée inchangé, attestation non livrée passée `sent` en cas de succès

### Implementation for User Story 5

- [ ] T056 [US5] Créer `functional/certification/src/Exceptions/CertificateNotResendableException.php` (`final`, `RefusalException`, `reservationNotActive()`, `noReport()`, `noEmail()`, `sendingFailed(DispatchFailureReason)`) et `functional/certification/src/Actions/ResendCertificate.php` (synchrone, verrou, `notifyNow`, trace `is_automatic = false` avec auteur, `markSent()` si l'attestation n'était pas livrée, historique ; échec classé par `DispatchFailureClassifier` puis refus `sendingFailed`)
- [ ] T057 [US5] Ajouter « Renvoyer l'attestation » dans un composant d'action séparé `functional/certification/src/Livewire/ResendCertificateButton.php`, rendu par `ReservationCertificateSection` (réservation confirmée ou en cours, rapport et e-mail présents) ; T055 passe

**Checkpoint**: toutes les stories fonctionnent ; suite verte ; commit de phase.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [ ] T058 [P] Écrire `functional/certification/tests/Feature/LayerBoundaryTest.php` : aucun fichier de `functional/{fleet,booking,inspection,billing}` ne référence `Functional\Certification` ; `certification` n'écrit pas dans `machines` ni `customers` (recherche d'`update(`/`save(` sur ces modèles dans `functional/certification/src`)
- [ ] T059 [P] Ajouter des données de démonstration : rapports déposés pour une partie des nacelles du seeder de `fleet` et attestations dans chaque état dans `functional/certification/database/seeders/CertificationDemoSeeder.php`, appelé depuis `database/seeders/DatabaseSeeder.php` en local
- [ ] T060 Vérifier que chaque fichier de `functional/certification/src` fait moins de 200 lignes, sans commentaire, sans nom générique ; découper sinon
- [ ] T061 Lancer `docker compose exec -u sail laravel.test vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse` (zéro erreur) et `php artisan test` (vert) sur `functional/certification` puis toute la suite
- [ ] T062 Dérouler la recette manuelle de [quickstart.md](quickstart.md#recette-manuelle-worker-et-planificateur-lancés) (tests 1 à 10) sur l'environnement isolé et noter le résultat dans le message de commit de phase

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 0** : obligatoire, après la mise à jour de la branche ; bloque US3 (T038, T044, T045) ; T003 vérifie les contrats de la 001 avant T004.
- **Phase 1 (Setup)** : après le feu vert de coordination et la mise à jour de la branche.
- **Phase 2 (Foundational)** : après la phase 1 ; bloque toutes les stories.
- **US1 (phase 3)** : après la phase 2 — MVP.
- **US2 (phase 4)** : après la phase 2 ; ses tests sc. 3 utilisent l'envoi de US1.
- **US3 (phase 5)** : après la phase 2 (garde, remise) ; la saisie d'e-mail dépend des points d'extension de la phase 0 ; les scénarios « passe une fois envoyée » utilisent US1.
- **US4 (phase 6)** : après US1 (complète `SendCertificate`).
- **US5 (phase 7)** : après US1 ; sc. 2 utilise T044 (US3) et le classement de US4.
- **Polish (phase 8)** : après toutes les stories.

### Within Each User Story

Tests d'abord (ils échouent), puis exceptions et actions, puis listeners et écrans ; commit de phase quand la suite complète est verte et Larastan à zéro.

### Parallel Opportunities

- Phase 1 : T005, T006, T007 en parallèle après T004.
- Phase 2 : T008 ; puis T012, T013, T014 ; T016, T017, T018, T020, T021 en parallèle.
- Dans chaque story, toutes les tâches de test [P] en parallèle.
- Après US1 : US2 et US3 peuvent avancer en parallèle (fichiers distincts, sauf la section du détail : T045 après T029).

## Parallel Example: User Story 3

```text
T037 DepartureGuardTest.php
T038 CustomerEmailFromReservationTest.php
T039 HandDeliveryTest.php
T040 DepartureConcurrencyTest.php
```

## Implementation Strategy

### MVP First (User Story 1)

1. Phases 1 et 2.
2. Phase 3 (US1) : l'attestation part automatiquement pour une machine dont le rapport est déposé.
3. Valider US1 seule (tests + quickstart test 2), commit.

### Incremental Delivery

1. US2 (dépôt des rapports) — sans elle, la production n'a rien à envoyer : US1 + US2 forment le premier lot livrable.
2. US3 (blocage de sortie, e-mail, remise) — rend la règle de la fiche vraie à chaque sortie.
3. US4 (relances, liste, alerte, rattrapage de mise en service) — indispensable avant d'activer `CERTIFICATION_GO_LIVE_DATE` en production.
4. US5 (renvoi).

La mise en service en production (renseigner `CERTIFICATION_GO_LIVE_DATE`) n'a lieu qu'après US4, une fois les rapports déposés par les agences.
