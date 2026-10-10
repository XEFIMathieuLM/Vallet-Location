---

description: "Task list for the customer portal and online booking requests feature"
---

# Tasks: Espace client et réservation en ligne

**Input**: Design documents from `specs/009-espace-client/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ (screens, emails, extension-points), quickstart.md

**Tests**: obligatoires (constitution, principe VI).
- **Feature** : un test par scénario d'acceptation, sur PostgreSQL.
- **Unit** (`PHPUnit\Framework\TestCase`, sans framework) : états de la demande et formatage du prix.
- **Ordre** : dans chaque phase, les tests sont écrits d'abord et doivent échouer avant l'implémentation.
- **Outils** :
  - factories avec `faker()` ;
  - `travelTo()` (date de référence `2026-10-10`, heure de Paris) ;
  - `Notification::fake()`, `Livewire::test()`, `actingAs($account, 'customer')` ;
  - traits `Functional\Fleet\Tests\Concerns\CreatesUsers` et `AssertsRefusals`, `$this->seedPermissions()`.
- **Exception** : les tests qui ouvrent le détail d'une réservation n'utilisent pas `WithoutTransitionExtensions`, car il faut que la section de `portal` soit enregistrée. Ils enregistrent seulement les gardes nécessaires.

**Organization**: tâches regroupées par user story ; chemins depuis la racine du dépôt (dépôt unique).

## Format: `[ID] [P?] [Story] Description`

- **[P]** : parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]** : user story de la spec (US1 à US6)

## Prérequis (constitution : « Une feature qui s'appuie sur une autre déclare ses prérequis »)

| Prérequis | Éléments attendus | État |
|---|---|---|
| 001-reservation-machines | `CreateReservation::handle(AgencyMember, Machine, Customer\|NewCustomer, …)`, `NewCustomer`, `AvailableMachinesQuery::get()`, `Reservation`, `ReservationStatus`, `Customer`, `ReservationChanged` (canal `fleet`, après commit), `ReservationDetailSections::register()`, `RefusalException`, `DisplaysRefusals`, `GlobalPerimeter`, `AgencyMember`, contrainte d'exclusion et `btree_gist` | dans `main` |
| 004-caution-particuliers | `CustomerType` (`individual`, `professional`) dans `booking` ; garde de caution à la sortie | dans `main` |
| 005-attestation-vgp | `ReservationCertificate`, `CertificateDispatch` (`vgp_report_id`, `channel`, `outcome`, `attempted_at`), `VgpReport`, `DispatchOutcome`, `DispatchChannel`, disque `vgp-reports`, ouverture de l'attestation sur `ReservationChanged` | dans `main` |
| 006 / 007 | garde de bon de commande à la sortie ; `ReservationRequestGuards` de la vente (machine réservée pour une vente) | dans `main` |
| 008-tableau-de-bord | aucun élément consommé ; écran inchangé | dans `main` |

- Base : `origin/main` à `ee02c93`. Aucune branche à attendre.
- Aucun fichier existant d'un layer n'est modifié. Hors de `functional/portal`, seuls les fichiers de colle listés dans [contracts/extension-points.md](contracts/extension-points.md) changent.
- Chaque phase se termine par `vendor/bin/phpstan clear-result-cache`, puis `composer ci:check` en code 0, puis un commit de phase sans mention d'outil d'IA.

---

## Phase 1: Setup

**Purpose**: créer le layer et l'outillage partagé.

- [X] T001 Générer le layer avec `php artisan osdd:layer functional/portal --generators=service-provider,routes`. Puis :
  - dans `functional/portal/composer.json`, requérir `functional/booking`, `functional/fleet`, `functional/certification` et déclarer le provider de faker sur le modèle de `functional/certification/composer.json` ;
  - dans le `composer.json` racine, ajouter `functional/portal` à `require` et `Functional\\Portal\\Tests\\` à `autoload-dev` ;
  - lancer `php artisan osdd:phpunit` ;
  - dans `phpstan.neon`, ajouter `functional/portal/{src,database,routes,config}` ;
  - lancer `composer update functional/portal`.
- [X] T002 [P] Créer `functional/portal/config/portal.php` (`'max_pending_requests' => 10`, `'notification_retry_after_minutes' => 10`, `'comment_max_length' => 500`, `'refusal_reason_max_length' => 500`), le fusionner dans `PortalServiceProvider::register()`.
- [X] T003 [P] Créer les fichiers de traduction `functional/portal/resources/lang/fr/{auth,search,requests,reservations,prices,mail,refusals,navigation}.php`. Ils sont complétés au fil des phases avec les textes de [contracts/screens.md](contracts/screens.md) et [contracts/emails.md](contracts/emails.md).
- [X] T004 [P] Créer `functional/portal/tests/Concerns/BuildsPortalFixtures.php`. Ce trait utilise `CreatesUsers` et expose, toutes par factories :
  - `customerAccount(array $attributes = [])` (adresse confirmée) et `unverifiedCustomerAccount()` ;
  - `attachedCustomerAccount(?Customer $customer = null)` ;
  - `reservableMachine(?MachineCategory, ?Agency, array $attributes = [])` (disponible, VGP valide) ;
  - `pendingRequest(CustomerAccount, Machine, string $start, string $end, array $attributes = [])`.
- [X] T005 [P] Écrire `functional/portal/tests/Feature/LayerBoundaryTest.php`, sur le modèle de `functional/certification/tests/Feature/LayerBoundaryTest.php`. Il vérifie deux choses :
  - aucun fichier de `functional/*/src` hors de `portal` ne référence `Functional\Portal` ;
  - `portal` ne référence ni `Functional\Billing`, ni `Functional\Deposit`, ni `Functional\Accounts`, ni `Functional\Sales`, ni `Functional\Inspection`, ni `App\`.

**Checkpoint**: `composer ci:check` vert ; commit « Espace client : création du layer ».

---

## Phase 2: Foundational

**Purpose**: tables, modèles, états, guard, permissions, accès, layout. Aucune user story ne commence avant la fin de cette phase.

### Tests d'abord

- [X] T006 [P] Écrire `functional/portal/tests/Unit/ReservationRequestStateTest.php` (Unit, sans framework) :
  - depuis `pending`, `confirm`, `refuse`, `cancel` et `expire` donnent l'état attendu ;
  - depuis chacun des 4 états finaux, toute transition lève `IllegalReservationRequestTransitionException`.
- [X] T007 [P] Écrire `functional/portal/tests/Feature/PortalConstraintsTest.php` :
  - deux demandes `pending` du même compte sur la même machine avec des dates qui se chevauchent : la seconde est refusée par la contrainte d'exclusion ;
  - la même chose avec une demande `cancelled` ou d'un autre compte : acceptée ;
  - `reservation_id` dupliqué refusé (index unique) ;
  - `confirmed` sans `reservation_id` refusé (CHECK) ; `refused` sans `refusal_reason` refusé (CHECK) ; `end_date < start_date` refusé ;
  - `customer_accounts.email` dupliqué refusé ;
  - `daily_price_cents <= 0` refusé.

### Implémentation

- [X] T008 Migration `functional/portal/database/migrations/2026_10_11_000100_create_customer_accounts_table.php` :
  - colonnes de [data-model.md](data-model.md#customer_accounts--compte-client) : `email` string(255) **unique**, `phone` string(30), `declared_type` avec CHECK `IN ('individual','professional')`, `customer_id` FK nullable vers `customers` sans cascade et indexée, `email_verified_at`, `password`, `remember_token` ;
  - plus la table `customer_password_reset_tokens` (`email` PK, `token`, `created_at`).
- [X] T009 Migration `functional/portal/database/migrations/2026_10_11_000101_create_reservation_requests_table.php`, selon [data-model.md](data-model.md#reservation_requests--demande-de-réservation) :
  - `comment` string(500) nullable, `refusal_reason` string(500) nullable, `indicative_daily_price_cents` integer nullable avec CHECK `> 0` ;
  - `status` avec CHECK sur `pending, confirmed, refused, cancelled, expired` ;
  - `reservation_id` **unique** ; CHECK `(status = 'confirmed') = (reservation_id IS NOT NULL)` et `(status = 'refused') = (refusal_reason IS NOT NULL)` ; CHECK `end_date >= start_date` ;
  - `EXCLUDE USING gist (customer_account_id WITH =, machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&) WHERE (status = 'pending')` ;
  - index `(status, start_date)` et `(customer_account_id, status)` ;
  - FK sans cascade vers `customer_accounts`, `machines`, `reservations`, `users` (`decided_by`), `agencies` (`decided_agency_id`).
- [X] T010 Migration `functional/portal/database/migrations/2026_10_11_000102_create_category_indicative_prices_table.php` : `machine_category_id` FK **unique**, `daily_price_cents` integer CHECK `> 0`, `updated_by` FK `users`, `updated_agency_id` FK `agencies`, timestamps.
- [X] T011 [P] Enums dans `functional/portal/src/Enums/` (`HasLabel` de `fleet`, libellés traduits) :
  - `ReservationRequestStatus` : `Pending`, `Confirmed`, `Refused`, `Cancelled`, `Expired` ;
  - `CustomerReservationStatus::fromReservationStatus(ReservationStatus)` : confirmée, en cours, terminée, annulée ;
  - `PortalHistoryEvent` : `RequestSent`, `RequestConfirmed`, `AccountAttached`, `RequestRefused`, `RequestCancelled`, `RequestExpired`, `IndicativePriceSet`, `IndicativePriceRemoved`.
- [X] T012 Modèles et factories :
  - `functional/portal/src/Models/CustomerAccount.php` : `Authenticatable`, `MustVerifyEmail`, `CanResetPassword`, `Notifiable` ; casts `declared_type` → `CustomerType`, `password` → `hashed`, `email_verified_at` → `immutable_datetime` ; relations `customer()`, `reservationRequests()`. Factory avec les états `unverified()` et `attachedTo(Customer)`.
  - `ReservationRequest.php` : casts, relations `account()`, `machine()`, `reservation()`, `decidedBy()`, méthode `state()` par la fabrique. Factory avec les états `confirmed()`, `refused()`, `cancelled()`, `expired()`.
  - `CategoryIndicativePrice.php` : modifications tracées par `PortalHistory` (ancien et nouveau montant). Avec sa factory.
- [X] T013 États dans `functional/portal/src/States/` :
  - `ReservationRequestState` (contrat), `PendingRequestState`, `ConfirmedRequestState`, `RefusedRequestState`, `CancelledRequestState`, `ExpiredRequestState` ;
  - le trait `RefusesRequestTransitions` et la fabrique `ReservationRequestStateFactory` ;
  - l'exception `functional/portal/src/Exceptions/IllegalReservationRequestTransitionException.php` (`RefusalException`, clé `portal::refusals.illegal_transition`).

  T006 doit passer.
- [X] T014 [P] Permissions :
  - `functional/portal/src/Access/PortalPermission.php` : `HandleRequests = 'portal.handle-requests'`, `ManagePrices = 'portal.manage-prices'` ;
  - `functional/portal/database/seeders/PortalPermissionSeeder.php` : crée les permissions sans toucher au rôle ;
  - l'appeler avant `PermissionSeeder` dans `database/seeders/DatabaseSeeder.php` ;
  - l'ajouter à `Tests\TestCase::seedPermissions()` dans `tests/TestCase.php`.
- [X] T015 Colle d'authentification :
  - dans `config/auth.php` : guard `customer` (`session`, provider `customer_accounts`), provider `customer_accounts` (`eloquent`, `Functional\Portal\Models\CustomerAccount`), broker `customer_accounts` (table `customer_password_reset_tokens`, `expire` 60, `throttle` 60) ;
  - dans `bootstrap/app.php` : `$middleware->redirectGuestsTo(...)` et `redirectUsersTo(...)` renvoient vers `portal.login` et `portal.search` pour une requête `portal.*` ou sous `espace-client/*`, et vers `login` et `dashboard` sinon.
- [X] T016 [P] `functional/portal/src/History/PortalHistory.php` : `record(Model $subject, PortalHistoryEvent, Model|null $author, array $details)` sur `activity('portal')`, sur le modèle de `functional/accounts/src/Support/AccountsHistory.php`. L'auteur est un salarié (avec son agence), un compte client, ou `null` pour « automatique ».
- [X] T017 [P] Diffusion temps réel :
  - `functional/portal/src/Events/ReservationRequestChanged.php` : `ShouldBroadcast`, `ShouldDispatchAfterCommit`, canal `PrivateChannel('portal-requests')`, `broadcastAs` `reservation-request.changed`, charge utile `{id, status, machine_id, start_date}` ;
  - `functional/portal/routes/channels.php` : `Broadcast::channel('portal-requests', fn (Authorizable $user): bool => $user->can(PortalPermission::HandleRequests->value))`.
- [X] T018 [P] Accès dans `functional/portal/src/Access/` :
  - `Perimeters/OwnAccountPerimeter.php` ;
  - `Controls/ReservationRequestControl.php` : `GlobalPerimeter` pour un salarié avec `portal.handle-requests` ; `OwnAccountPerimeter` pour un `CustomerAccount`, requête `where customer_account_id = id` ;
  - `Controls/IndicativePriceControl.php` (`portal.manage-prices`).
- [X] T019 Layout et provider :
  - `functional/portal/resources/views/layouts/portal.blade.php` : `partials.head`, `x-app-logo`, en-tête Flux avec les liens Rechercher, Mes demandes, Mes réservations, Mon compte et Déconnexion pour un compte connecté ; pas de barre latérale salarié ;
  - `PortalServiceProvider::boot()` : migrations, traductions, vues, contrôles, enregistrement des composants Livewire au fil des phases, `withRouting(web, channels, commands)`.

  T007 doit passer.

**Checkpoint**: T006 et T007 verts, `PermissionSeederTest` et `tests/Feature/Auth/*` verts ; `composer ci:check` vert ; commit « Espace client : fondations ».

---

## Phase 3: User Story 1 — Créer son compte client et se connecter (P1) 🎯 MVP

**Goal**: inscription libre avec type obligatoire, confirmation de l'e-mail, connexion, mot de passe oublié, comptes séparés des salariés.

**Independent Test**: créer un compte professionnel, confirmer l'adresse, se connecter ; tenter le planning des salariés avec ce compte : refus.

### Tests d'abord

- [ ] T020 [P] [US1] `functional/portal/tests/Feature/RegistrationTest.php` :
  - scénario 1 : compte créé, e-mail normalisé, `VerifyCustomerEmail` envoyée, redirection vers `portal.verification.notice` ;
  - scénario 2 : sans type, refus de validation ;
  - scénario 3 : e-mail déjà pris, refus ;
  - aucune fiche `Customer` n'est créée (FR-005).
- [ ] T021 [P] [US1] `functional/portal/tests/Feature/EmailVerificationTest.php` :
  - scénario 4 : compte non confirmé sur `portal.search`, `portal.requests` et `portal.reservations`, redirigé vers la page de confirmation ; le renvoi du lien envoie une nouvelle notification ;
  - le lien signé pose `email_verified_at` ; un lien expiré ou altéré est refusé (FR-002).
- [ ] T022 [P] [US1] `functional/portal/tests/Feature/LoginTest.php` :
  - scénario 5 : connexion puis redirection vers `portal.search` ;
  - mauvais mot de passe refusé ;
  - 6ᵉ tentative en une minute bloquée (FR-004) ;
  - déconnexion.
- [ ] T023 [P] [US1] `functional/portal/tests/Feature/PasswordResetTest.php` :
  - scénario 8 : `ResetCustomerPassword` envoyée avec un lien `portal.password.reset` ;
  - la réinitialisation change le mot de passe ;
  - une adresse inconnue donne la même réponse ;
  - les liens de réinitialisation des salariés (`password.reset`) sont inchangés.
- [ ] T024 [P] [US1] `functional/portal/tests/Feature/SpaceSeparationTest.php` :
  - scénario 6 : `actingAs($account, 'customer')` sur `dashboard`, `reservations.index`, `planning.index`, `machines.index`, `portal.staff.requests` et `portal.staff.prices`, toujours redirigé vers `login` (FR-003) ;
  - scénario 7 : les identifiants d'un salarié sur `portal.login` sont refusés ;
  - un salarié connecté sur `/espace-client` est redirigé vers `portal.login`.
- [ ] T025 [P] [US1] `functional/portal/tests/Feature/AccountSettingsTest.php` :
  - modification du nom et du téléphone ;
  - changement de mot de passe avec le mot de passe actuel ;
  - la fiche `Customer` rattachée n'est pas modifiée (FR-004).

### Implémentation

- [ ] T026 [US1] `functional/portal/src/Actions/RegisterCustomerAccount.php` (e-mail en minuscules et sans espaces, `Password::defaults()`, type obligatoire). Puis le composant `functional/portal/src/Livewire/Auth/Register.php` et sa vue : radio type sans défaut, connexion du compte après l'inscription, redirection vers `portal.verification.notice`.
- [ ] T027 [US1] Confirmation de l'adresse :
  - notification `functional/portal/src/Notifications/VerifyCustomerEmail.php`, dont `toMail()` rend `Mail/VerifyCustomerEmailMail.php` (lien `URL::temporarySignedRoute('portal.verification.verify', 60 min)`) ;
  - `CustomerAccount::sendEmailVerificationNotification()` ;
  - `Http/Controllers/VerifyCustomerEmailController.php` ;
  - composant `Livewire/Auth/VerifyEmailNotice.php`, renvoi limité à 6 par minute.
- [ ] T028 [US1] Connexion et déconnexion :
  - `functional/portal/src/Auth/CustomerLoginThrottle.php` (`RateLimiter`, 5 par minute par e-mail et adresse IP) ;
  - `Livewire/Auth/Login.php` (`Auth::guard('customer')->attempt()`, régénération de session) ;
  - `Http/Controllers/LogoutController.php`.
- [ ] T029 [US1] Mot de passe oublié :
  - `Livewire/Auth/ForgotPassword.php` et `ResetPassword.php` (`Password::broker('customer_accounts')`) ;
  - notification `Notifications/ResetCustomerPassword.php` et `Mail/ResetCustomerPasswordMail.php` ;
  - `CustomerAccount::sendPasswordResetNotification()`.
- [ ] T030 [US1] `functional/portal/src/Livewire/Customer/AccountSettings.php` et sa vue (nom, téléphone, mot de passe).
- [ ] T031 [US1] `functional/portal/routes/web.php` : groupes `guest:customer`, `auth:customer`, `auth:customer` + `verified:portal.verification.notice`, avec les routes et les noms de [contracts/screens.md](contracts/screens.md). `portal.search` affiche provisoirement un écran vide, remplacé en US2.

**Checkpoint**: T020 à T025 verts, `tests/Feature/Auth/*` verts ; `composer ci:check` vert ; commit « Espace client : comptes clients ».

---

## Phase 4: User Story 2 — Chercher une machine disponible et envoyer une demande (P1)

**Goal**: recherche identique à celle des salariés, prix indicatif affiché, envoi d'une demande en attente qui ne bloque rien.

**Independent Test**: deux nacelles, dont une réservée aux dates cherchées : seule l'autre apparaît, avec son prix ; la demande envoyée est « en attente » et la machine reste réservable par un salarié.

### Tests d'abord

- [ ] T032 [P] [US2] `functional/portal/tests/Unit/IndicativePriceFormatterTest.php` (Unit) : `9500` donne « à partir de 95,00 € HT / jour », `null` donne « prix sur demande », et les centimes sont respectés (`9999`).
- [ ] T033 [P] [US2] `functional/portal/tests/Feature/PortalSearchTest.php` :
  - scénario 1 : seules les machines disponibles de la catégorie et de l'agence, avec référence, catégorie, agence et prix indicatif ;
  - scénario 2 : exclusion d'une machine en panne, à l'atelier, retirée, réservée en partie, ou dont la VGP expire avant la fin ;
  - scénario 9 : aucun nom de client ni aucune réservation dans le rendu ;
  - SC-008 : pour 3 jeux de critères, les identifiants affichés sont égaux à `AvailableMachinesQuery::get()` avec les mêmes critères ;
  - catégorie sans prix : « prix sur demande » ;
  - filtres obligatoires.
- [ ] T034 [P] [US2] `functional/portal/tests/Feature/SendReservationRequestTest.php` :
  - scénario 3 : demande `pending` avec commentaire, prix copié (FR-014), historique `request_sent`, `ReservationRequestChanged` diffusé ;
  - scénario 4 : un salarié réserve ensuite la même machine aux mêmes dates par `CreateReservation`, accepté ;
  - FR-013 : avec une demande en attente sur une machine, `AvailableMachinesQuery` et la recherche client présentent toujours cette machine ;
  - scénario 5 : machine devenue indisponible, `RequestedMachineUnavailableException` ;
  - scénario 6 : dates incohérentes refusées ;
  - scénario 7 : doublon en attente, `DuplicatePendingRequestException` ;
  - scénario 8 : 11ᵉ demande en attente, `PendingRequestLimitReachedException` ;
  - commentaire de plus de 500 caractères refusé ;
  - compte non confirmé refusé.

### Implémentation

- [ ] T035 [P] [US2] `functional/portal/src/Pricing/IndicativePriceFormatter.php` (`Number::currency($cents / 100, 'EUR', 'fr')`, textes traduits). T032 doit passer.
- [ ] T036 [P] [US2] Refus typés dans `functional/portal/src/Exceptions/` (sous-classes de `RefusalException`, textes dans `portal::refusals`) : `RequestedMachineUnavailableException`, `DuplicatePendingRequestException`, `PendingRequestLimitReachedException`.
- [ ] T037 [US2] `functional/portal/src/Data/PortalMachineOffer.php` (référence, catégorie, agence, `?int $dailyPriceCents`) et `functional/portal/src/Queries/PortalMachineSearch.php`. La requête appelle `AvailableMachinesQuery::get($start, $end, $category, $agency)`, puis charge les prix des catégories en une seule requête (`whereIn`), sans boucle de requêtes.
- [ ] T038 [US2] `functional/portal/src/Actions/SendReservationRequest.php`, dans une transaction :
  - dates cohérentes (même règle que la 001, heure de Paris) ;
  - verrou du compte, puis décompte des demandes `pending` par rapport à `max_pending_requests` ;
  - machine présente dans `PortalMachineSearch` pour ses dates ;
  - création avec le prix copié ;
  - erreur `23P01` traduite en `DuplicatePendingRequestException` par `rescue()` ;
  - historique, puis `ReservationRequestChanged`.
- [ ] T039 [US2] Composants `functional/portal/src/Livewire/Customer/Search.php` et `SendRequestForm.php` (modal, `DisplaysRefusals`) et leurs vues : filtres obligatoires, cartes, mention « Prix indicatif ; le prix facturé est établi par votre agence. », état vide. Ils remplacent l'écran provisoire de T031. T033 et T034 doivent passer.

**Checkpoint**: T032 à T034 verts ; `composer ci:check` vert ; commit « Espace client : recherche et demandes ».

---

## Phase 5: User Story 3 — Traiter les demandes en ligne en agence (P1)

**Goal**: liste des demandes en attente, confirmation par `CreateReservation` avec rattachement du compte, refus motivé, e-mail au client, section « Demande en ligne ».

**Independent Test**: confirmer une demande d'un nouveau compte en créant la fiche : une réservation confirmée existe, la demande est « confirmée », l'e-mail part ; confirmer une demande dont la machine a été prise entre-temps : refus de la 001.

### Tests d'abord

- [ ] T040 [P] [US3] `functional/portal/tests/Feature/OnlineRequestsListTest.php` :
  - scénario 1 : seules les demandes `pending`, triées par date de début, avec le client déclaré, le rattachement ou « compte non rattaché », la machine, l'agence, le commentaire, le prix et la date d'envoi ; filtre par agence ;
  - accès refusé sans `portal.handle-requests` ;
  - la liste écoute `echo-private:portal-requests,.reservation-request.changed` ;
  - FR-016 : le badge de la barre latérale affiche le nombre de demandes en attente, rien à zéro, et n'apparaît pas sans la permission.
- [ ] T041 [P] [US3] `functional/portal/tests/Feature/ConfirmReservationRequestTest.php` :
  - scénario 2 : compte rattaché, réservation `confirmed` pour sa fiche, auteur et agence du salarié, demande `confirmed` avec `reservation_id`, historique ;
  - scénario 3 : compte non rattaché, choix « fiche existante » parmi les fiches proposées par `SuggestedCustomers` (même e-mail ou même téléphone), puis rattachement et historique `account_attached` sur la fiche ;
  - scénario 3 bis : choix « créer la fiche », fiche créée avec le nom, le téléphone, l'e-mail et le type déclarés ;
  - scénario 4 : machine prise, en panne ou VGP insuffisante, refus de la 001 affiché, demande toujours `pending`, aucune fiche créée ;
  - scénario 5 : autre machine de la même catégorie acceptée ; machine d'une autre catégorie, `ConfirmationMachineMismatchException`.
- [ ] T042 [P] [US3] `functional/portal/tests/Feature/RefuseReservationRequestTest.php` :
  - scénario 6 : `refused` avec motif, auteur, agence et date ;
  - scénario 7 : motif vide ou de plus de 500 caractères refusé ;
  - refuser une demande déjà décidée lève `IllegalReservationRequestTransitionException`.
- [ ] T043 [P] [US3] `functional/portal/tests/Feature/RequestDecisionMailTest.php` :
  - scénario 8 : avec `Notification::fake()`, `ReservationRequestDecided` part après une confirmation et après un refus ;
  - rendu des Mailables : machine réservée, dates, agence de retrait, motif ;
  - FR-021 : la décision est enregistrée même si l'envoi échoue (transport en erreur) ;
  - `customer_notified_at` posé après l'envoi ; un second passage du job n'envoie rien.
- [ ] T044 [P] [US3] `functional/portal/tests/Feature/ConfirmationConcurrencyTest.php` :
  - scénario 9 : deux confirmations successives de la même demande, une réservation et la seconde refusée (verrou et état relus) ;
  - deux demandes d'un même compte non rattaché confirmées l'une après l'autre avec « créer la fiche » : une seule fiche, et la seconde confirmation réutilise la fiche rattachée ;
  - scénario 12 : rattacher un compte déjà rattaché lève `CustomerAccountAlreadyAttachedException`.
- [ ] T045 [P] [US3] `functional/portal/tests/Feature/ReservationOriginSectionTest.php` :
  - scénario 11 : le détail d'une réservation issue d'une demande montre « Demande en ligne », la date, le compte et le commentaire ;
  - une réservation saisie au comptoir n'affiche rien ;
  - FR-022 : `ReservationDetailSections::isGuardedBy('portal.reservation-origin-section', $transition)` est faux pour toutes les transitions, et la sortie n'est pas bloquée par la section.
- [ ] T046 [P] [US3] `tests/Feature/Portal/ConfirmedRequestFollowsExistingRulesTest.php`. Ce test vit au niveau de l'application, qui voit tous les layers. Scénario 10 et FR-023 :
  - réservation issue d'une demande : la sortie est refusée sans photos de départ (002) ;
  - réservation issue d'une demande d'un particulier : la sortie est refusée sans caution (004) ;
  - fiche grand compte : refusée sans bon de commande (006) ;
  - machine soumise à VGP : l'attestation est ouverte et envoyée à l'e-mail de la fiche (005) ;
  - machine réservée pour une vente (007) : la confirmation est refusée.

### Implémentation

- [ ] T047 [P] [US3] `functional/portal/src/Queries/SuggestedCustomers.php` : fiches de même e-mail (insensible à la casse) ou de même téléphone que le compte, au plus 10, plus une recherche par nom, e-mail ou téléphone. Une requête par appel.
- [ ] T048 [P] [US3] `functional/portal/src/Data/CustomerChoice.php` (`CustomerChoiceKind::Existing` avec l'id de la fiche, ou `CustomerChoiceKind::Create`) et les exceptions `CustomerAccountAlreadyAttachedException` et `ConfirmationMachineMismatchException`.
- [ ] T049 [US3] `functional/portal/src/Actions/AttachCustomerAccount.php` : `UPDATE … WHERE customer_id IS NULL`, exception si aucune ligne n'est mise à jour, historique `account_attached` sur la fiche.
- [ ] T050 [US3] `functional/portal/src/Actions/ConfirmReservationRequest.php`, dans une transaction :
  - verrou de la demande, transition par l'état ;
  - verrou du compte ;
  - fiche résolue : imposée si le compte est rattaché, sinon `Customer` existante ou `NewCustomer` ;
  - contrôle de la catégorie de la machine choisie ;
  - `CreateReservation::handle()` ;
  - mise à jour de la demande (`reservation_id`, `decided_by`, `decided_agency_id`, `decided_at`) ;
  - `AttachCustomerAccount` si besoin ;
  - historique, `ReservationRequestChanged`, puis dispatch de `NotifyRequestDecisionJob` après commit.
- [ ] T051 [US3] `functional/portal/src/Actions/RefuseReservationRequest.php` : verrou, motif obligatoire de 500 caractères au plus, historique, événement, job après commit.
- [ ] T052 [US3] E-mails de décision :
  - `functional/portal/src/Jobs/NotifyRequestDecisionJob.php` (`ShouldBeUnique` par id ; relit la demande sous verrou ; ne fait rien si `customer_notified_at` est posé ; sinon notifie puis pose `customer_notified_at`) ;
  - notification `Notifications/ReservationRequestDecided.php`, dont `toMail()` rend `Mail/ReservationRequestConfirmedMail.php` ou `Mail/ReservationRequestRefusedMail.php` selon l'état ;
  - vues dans `resources/views/mail/`.
- [ ] T053 [US3] `functional/portal/src/Queries/PendingRequests.php` (paginée par 20, filtre d'agence, chargements anticipés `account.customer`, `machine.category`, `machine.agency`) et le composant `functional/portal/src/Livewire/Staff/OnlineRequests.php` avec sa vue. Il écoute `portal-requests` et `fleet`, avec `wire:poll.60s` en secours.
- [ ] T054 [US3] `functional/portal/src/Livewire/Staff/ConfirmRequestModal.php` (machines de la même catégorie via `AvailableMachinesQuery`, la demandée en tête ; choix de la fiche ; `DisplaysRefusals`) et `RefuseRequestModal.php`, avec leurs vues.
- [ ] T055 [US3] `functional/portal/src/Livewire/Staff/PendingRequestsBadge.php` (un `count()`) et, dans `resources/views/layouts/app/sidebar.blade.php`, le groupe « Espace client » sous `@canany` des deux permissions. Pour cette phase, il contient l'entrée « Demandes en ligne » et son badge.
- [ ] T056 [US3] `functional/portal/src/Livewire/Staff/ReservationOriginSection.php` et sa vue. Le composant est enregistré par `ReservationDetailSections::register('portal.reservation-origin-section', 5)`, sans transition gardée. La demande est lue par `reservation_id` en une requête.
- [ ] T057 [US3] Route salarié `GET /demandes-en-ligne` (`portal.staff.requests`, `auth`, `verified`, `can:portal.handle-requests`) dans `functional/portal/routes/web.php`. T040 à T046 doivent passer.

**Checkpoint**: T040 à T046 verts ; suite complète verte (non-régression des 001 à 008) ; `composer ci:check` vert ; commit « Espace client : traitement des demandes en agence ».

---

## Phase 6: User Story 4 — Suivre ses demandes et ses réservations, annuler une demande (P2)

**Goal**: espace « Mes demandes » et « Mes réservations », annulation d'une demande en attente, expiration automatique avec e-mail, cloisonnement entre clients.

**Independent Test**: un compte rattaché voit sa demande en attente, sa demande refusée et une réservation saisie au comptoir ; il annule la demande en attente, qui disparaît de la liste des salariés.

### Tests d'abord

- [ ] T058 [P] [US4] `functional/portal/tests/Feature/MyRequestsTest.php` :
  - scénario 1 : chaque état avec son badge, le motif d'un refus, la machine réservée d'une demande confirmée ;
  - scénario 4 : annulation, `cancelled`, historique, événement, plus dans `PendingRequests`, aucun e-mail ;
  - scénario 5 : annuler une demande confirmée, refusée ou expirée est refusé.
- [ ] T059 [P] [US4] `functional/portal/tests/Feature/MyReservationsTest.php` :
  - scénario 2 : les réservations de la fiche, y compris celles saisies par un salarié, avec l'état client, sans aucune information interne (conflit, caution, bon de commande, dégâts, transmissions) ;
  - scénario 3 : compte non rattaché, état vide ;
  - scénario 6 : aucune annulation proposée, mention « contactez l'agence » ;
  - plusieurs comptes rattachés à la même fiche voient les mêmes réservations ;
  - cas limite : une réservation issue d'une demande, annulée ensuite par un salarié, apparaît « annulée » côté client, et la demande reste « confirmée » avec son lien.
- [ ] T060 [P] [US4] `functional/portal/tests/Feature/ExpireReservationRequestsTest.php` :
  - scénario 7 : avec `travelTo()`, une demande `pending` dont la date de début est hier passe `expired` par `portal:reconcile`, avec l'historique « automatique » et `ReservationRequestDecided` (expiration) ;
  - une demande dont la date de début est aujourd'hui reste `pending` ;
  - la commande est rejouable sans double expiration ni double e-mail ;
  - rattrapage : une demande décidée depuis plus de 10 minutes sans `customer_notified_at` est renvoyée en file ;
  - la planification `portal:reconcile` toutes les 5 minutes est enregistrée.
- [ ] T061 [P] [US4] `functional/portal/tests/Feature/CustomerIsolationTest.php` :
  - scénario 8 : un compte A ne voit ni les demandes ni les réservations du compte B ;
  - le compte A ne peut pas annuler une demande de B (refus d'accès par `OwnAccountPerimeter`) ;
  - scénario 9 : annulation puis confirmation de la même demande, ou l'inverse, une seule transition et l'autre refusée.

### Implémentation

- [ ] T062 [P] [US4] `functional/portal/src/Queries/AccountRequests.php` (demandes du compte, chargements anticipés, pagination par 20) et `AccountReservations.php` (réservations de `account.customer_id`, à venir puis passées, chargements anticipés `machine.category`, `machine.agency`, pagination par 20 ; vide si le compte n'est pas rattaché).
- [ ] T063 [US4] `functional/portal/src/Actions/CancelReservationRequest.php` : contrôle d'accès, verrou, transition, historique, événement.
- [ ] T064 [US4] Expiration et rattrapage :
  - `functional/portal/src/Actions/ExpireReservationRequest.php` : verrou, transition `expire`, historique automatique, événement, job ;
  - `Mail/ReservationRequestExpiredMail.php` ;
  - `Console/ReconcileCommand.php` (`portal:reconcile`) : expirations une par une, chacune dans sa transaction, puis remise en file des e-mails non partis ;
  - `functional/portal/routes/console.php` : `Schedule::command('portal:reconcile')->everyFiveMinutes()->withoutOverlapping()`.
- [ ] T065 [US4] Composants `functional/portal/src/Livewire/Customer/MyRequests.php` et `MyReservations.php`, avec leurs vues, conformes à [contracts/screens.md](contracts/screens.md). T058 à T061 doivent passer.

**Checkpoint**: T058 à T061 verts ; `composer ci:check` vert ; commit « Espace client : suivi, annulation et expiration ».

---

## Phase 7: User Story 5 — Télécharger les attestations VGP de ses réservations (P2)

**Goal**: le client télécharge le rapport VGP du dernier envoi ou de la remise de la 005, sans rien modifier de l'attestation.

**Independent Test**: sur une réservation de nacelle dont l'attestation est envoyée, le téléchargement rend le rapport envoyé ; sur une mini-pelle, aucun document.

### Tests d'abord

- [ ] T066 [P] [US5] `functional/portal/tests/Feature/CustomerCertificateDownloadTest.php` (`Storage::fake('vgp-reports')`) :
  - scénario 1 : envoi e-mail réussi, téléchargement du rapport de ce dispatch ; remise en main propre, rapport remis ; plusieurs envois, rapport du dernier réussi ;
  - scénario 2 : « Attestation pas encore disponible » ;
  - scénario 3 : machine non soumise à VGP, rien ;
  - scénario 4 : réservation annulée, rien et 404 ;
  - scénario 5 : réservation d'une autre fiche, 404 ;
  - scénario 6 : état de l'attestation, envois et `CertificateDeliveredGuard` inchangés après le téléchargement.

### Implémentation

- [ ] T067 [US5] `functional/portal/src/Queries/AccountCertificateDocuments.php` : pour les réservations d'une page de `AccountReservations`, le dernier `CertificateDispatch` réussi par attestation (`outcome` réussi, canal e-mail ou main propre). Une seule requête avec une sous-requête de dernier envoi, sans boucle.
- [ ] T068 [US5] `functional/portal/src/Http/Controllers/CustomerCertificateDownloadController.php` : réservation de la fiche du compte, non annulée, rapport du dernier envoi, puis `Storage::disk('vgp-reports')->download()`. Route `portal.reservations.certificate`. Liens et mentions dans la vue de `MyReservations`. T066 doit passer.

**Checkpoint**: T066 vert ; `composer ci:check` vert ; commit « Espace client : attestations VGP ».

---

## Phase 8: User Story 6 — Afficher un prix indicatif par catégorie (P3)

**Goal**: écran salarié de saisie des prix indicatifs. L'affichage côté client existe depuis l'US2.

**Independent Test**: saisir 95 € pour « Nacelle », la recherche client l'affiche ; aucune transmission ne contient ce prix.

### Tests d'abord

- [ ] T069 [P] [US6] `functional/portal/tests/Feature/IndicativePricesTest.php` :
  - scénario 1 : saisie de `95` puis `95,50`, ligne avec auteur et agence, historique avec l'ancien et le nouveau montant ;
  - scénario 4 : `0`, `-5` et `abc` refusés ;
  - scénario 5 : retrait, puis « prix sur demande » côté client ;
  - scénario 7 : une demande envoyée à 95 € garde 95 € après un passage à 110 € ;
  - accès refusé sans `portal.manage-prices`.
- [ ] T070 [P] [US6] `tests/Feature/Portal/IndicativePriceIsolationTest.php`, au niveau de l'application :
  - scénario 6 : un client dont la fiche est grand compte voit le même prix indicatif ;
  - scénario 8 et FR-033 : la période transmise d'une réservation issue d'une demande (faux logiciel de facturation de la 003) ne contient aucun prix indicatif.

### Implémentation

- [ ] T071 [US6] `functional/portal/src/Actions/SetIndicativePrice.php` et `RemoveIndicativePrice.php` (montant en euros vers des centimes entiers, strictement positif, sinon `InvalidIndicativePriceException` ; historique), et l'exception dans `functional/portal/src/Exceptions/`.
- [ ] T072 [US6] Écran et navigation :
  - `functional/portal/src/Livewire/Staff/IndicativePrices.php` et sa vue (validation `^\d+([.,]\d{1,2})?$`) ;
  - route `GET /prix-indicatifs` (`portal.staff.prices`, `can:portal.manage-prices`) ;
  - entrée « Prix indicatifs » dans le groupe « Espace client » de `resources/views/layouts/app/sidebar.blade.php`.

  T069 et T070 doivent passer.

**Checkpoint**: T069 et T070 verts ; `composer ci:check` vert ; commit « Espace client : prix indicatifs ».

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T073 [P] `functional/portal/tests/Feature/PortalQueryCountTest.php` : nombre de requêtes identique avec 2 et 20 éléments pour `OnlineRequests`, `MyRequests`, `MyReservations` (documents compris) et `Search` (contrainte « aucune requête dans une boucle »).
- [ ] T074 [P] Relecture des fichiers de `functional/portal` : moins de 200 lignes, aucun commentaire, aucun texte en dur hors des traductions, booléens préfixés, méthodes de moins de 40 lignes. Découper si nécessaire.
- [ ] T075 Recette sur l'environnement du worktree (port 8099) :
  - copier `storage/app/load-client-data/load.php` et `reprise-ech40.php` depuis le dossier principal et charger les données réelles ;
  - dérouler [quickstart.md](quickstart.md), parcours 1 à 8, dans le navigateur, avec Mailpit sur le port 8199 ;
  - noter tout écart dans la PR.
- [ ] T076 `vendor/bin/phpstan clear-result-cache`, puis `composer ci:check` en code 0 sur la suite complète. Les tests des features 001 à 008 sont verts sans modification (FR-036, SC-005 de la 008).

**Checkpoint**: commit « Espace client : finitions ». Pas de push : la coordinatrice pousse et ouvre la PR.

---

## Dependencies & Execution Order

- **Setup (Phase 1)**, puis **Foundational (Phase 2)** : bloquent tout.
- **US1 (Phase 3)** : prérequis de toutes les autres stories, qui ont besoin d'un compte connecté et confirmé.
- **US2 (Phase 4)** : dépend de US1, car elle crée les demandes.
- **US3 (Phase 5)** : dépend de US2, car elle traite des demandes. Ses tests peuvent créer les demandes par factory.
- **US4 (Phase 6)** : dépend de US3 pour les états confirmée et refusée et pour le job d'e-mail. L'annulation seule ne dépend que de US2.
- **US5 (Phase 7)** : dépend de US4, car elle étend `MyReservations`.
- **US6 (Phase 8)** : dépend de US2, car le formateur et l'affichage y sont déjà ; indépendante de US3 à US5.
- **Polish (Phase 9)** : après toutes les stories.

## Parallel Opportunities

- **Phase 1** : T002, T003, T004 et T005 en parallèle après T001.
- **Phase 2** : T006 et T007 (tests) en parallèle, puis T011, T014, T016, T017 et T018 en parallèle après les migrations T008 à T010.
- **Dans chaque story** : tous les tests marqués [P] s'écrivent en parallèle.
  - US3 : T040 à T046, puis T047 et T048 en parallèle avant les actions.
  - US4 : T058 à T061, puis T062.
- **Entre stories** : US6 peut être menée en parallèle de US3 à US5 une fois US2 terminée.

## Implementation Strategy

- **MVP** : Phases 1 à 5 (US1 à US3). Un client crée son compte, cherche, demande ; l'agence confirme ou refuse et le client est prévenu par e-mail. C'est la valeur demandée.
- **Incrément 2** : US4 et US5, pour le suivi en ligne et les documents.
- **Incrément 3** : US6, l'écran des prix indicatifs.
- **Fin de chaque phase** : `composer ci:check` en code 0 et un commit de phase. Aucun push.

## Récapitulatif

| Phase | Tâches | Nombre |
|---|---|---|
| 1 Setup | T001–T005 | 5 |
| 2 Foundational | T006–T019 | 14 |
| 3 US1 | T020–T031 | 12 |
| 4 US2 | T032–T039 | 8 |
| 5 US3 | T040–T057 | 18 |
| 6 US4 | T058–T065 | 8 |
| 7 US5 | T066–T068 | 3 |
| 8 US6 | T069–T072 | 4 |
| 9 Polish | T073–T076 | 4 |
| **Total** | | **76** |
