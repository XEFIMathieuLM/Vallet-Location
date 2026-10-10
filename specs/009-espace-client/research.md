# Research: Espace client et réservation en ligne

Décisions de conception de la feature 009. Chaque décision cite les exigences de la [spec](spec.md) qu'elle sert.

## R1 — Un nouveau layer `portal` au sommet du graphe

- **Decision** : un layer OSDD `functional/portal`, qui dépend de `booking`, `fleet` et `certification` et dont aucun layer ne dépend. Il contient tout ce qui est propre au client : comptes clients, demandes, prix indicatifs, écrans de l'espace client, et les deux écrans salariés de la feature (demandes en ligne, prix indicatifs).
- **Rationale** : les demandes et les comptes clients forment un domaine nouveau (principe I). Le layer ne fait que lire `booking`, `fleet` et `certification` et appeler leurs actions publiques (`CreateReservation`, `AvailableMachinesQuery`), sans écrire dans leurs tables. Aucun fichier d'un layer existant n'est modifié (FR-036).
- **Alternatives considered** :
  - Mettre les demandes dans `booking`. Écarté : cela modifie le layer de la 001 et y introduit la notion de client connecté, contraire au périmètre.
  - Mettre les comptes clients dans `app/`, à côté des salariés. Écarté : `app/` est la colle des salariés, et le compte client est indissociable des demandes.
  - Un nom de layer `customers`. Écarté : il se confond avec la fiche `Customer` de `booking`. `portal` désigne l'espace, pas la fiche.

## R2 — Comptes clients séparés : guard `customer`

- **Decision** :
  - Un modèle `CustomerAccount` authentifiable, table `customer_accounts`, distinct de `users`.
  - Un guard de session `customer`, un provider `customer_accounts` et un broker de mots de passe `customer_accounts` (table `customer_password_reset_tokens`).
  - Toutes les routes de l'espace sont sous `/espace-client`, nommées `portal.*`. Celles réservées aux visiteurs portent `guest:customer` ; les autres `auth:customer` puis `verified:portal.verification.notice`.
  - Les écrans salariés gardent `auth` (guard `web`) : un client connecté sur le guard `customer` n'y est pas authentifié et en est renvoyé (FR-003).
  - Le guard, le provider et le broker sont déclarés dans `config/auth.php`. Les redirections des visiteurs et des connectés sont aiguillées dans `bootstrap/app.php` selon que la route est `portal.*` ou non. `app/` reste ainsi la colle de l'authentification, comme le prévoit le `CLAUDE.md`.
- **Rationale** :
  - Les deux sessions ont des clés différentes (`login_web_*`, `login_customer_*`) : aucune confusion possible entre un salarié et un client, même dans le même navigateur.
  - Une adresse e-mail peut exister côté salarié et côté client sans conflit (FR-003, scénario US1-7).
  - Le middleware `Authenticate` appelle `shouldUse($guard)` : après `auth:customer`, `$request->user()` est le compte client, donc `verified` fonctionne tel quel.
- **Alternatives considered** :
  - Une seule table `users` avec un rôle client. Écarté par la clarification 4 : un oubli de permission ouvrirait un écran salarié.
  - Un second jeu de routes Fortify. Écarté : Fortify ne gère qu'un guard (`fortify.guard`) et sert déjà les salariés, avec l'inscription désactivée (`RegistrationDisabledTest`).

## R3 — Inscription, connexion, confirmation d'e-mail, mot de passe : écrans Livewire du layer

- **Decision** : des composants Livewire dans `portal` :
  - `Register`, `Login`, `ForgotPassword`, `ResetPassword` ;
  - `VerifyEmailNotice` et une route signée `portal.verification.verify` ;
  - `AccountSettings` (nom, téléphone, mot de passe).

  Ces composants s'appuient sur les briques de Laravel :
  - `Auth::guard('customer')->attempt()` ;
  - `Password::broker('customer_accounts')` ;
  - `URL::temporarySignedRoute` ;
  - `RateLimiter`, limité à 5 tentatives par minute par e-mail et adresse IP, comme le `login` de Fortify.

  `CustomerAccount` implémente `MustVerifyEmail` et `CanResetPassword`. Il redéfinit `sendEmailVerificationNotification()` et `sendPasswordResetNotification()` pour envoyer des notifications propres au layer (`VerifyCustomerEmail`, `ResetCustomerPassword`), dont `toMail()` rend un Mailable (règle Xefi `mail-via-notifications`).
- **Rationale** :
  - Les callbacks globaux `VerifyEmail::createUrlUsing` et `ResetPassword::createUrlUsing` changeraient aussi les liens envoyés aux salariés. Redéfinir les méthodes du modèle ne touche que les clients.
  - La règle de mot de passe est `Password::defaults()`, celle des salariés.
- **Alternatives considered** :
  - Un package d'authentification multi-guard. Écarté : pas de nouveau package (principe VII), et les briques de Laravel suffisent.

## R4 — La demande : pattern State, sans blocage de la machine

- **Decision** :
  - Table `reservation_requests`, statut texte casté en enum `ReservationRequestStatus` : `pending`, `confirmed`, `refused`, `cancelled`, `expired`.
  - Pattern State : une classe par état et une fabrique. Seul `PendingRequestState` accepte une transition (`confirm`, `refuse`, `cancel`, `expire`) ; les quatre autres refusent tout avec `IllegalReservationRequestTransitionException`.
  - Une contrainte CHECK porte sur les états et sur la cohérence des colonnes : `reservation_id` seulement si `confirmed`, `refusal_reason` seulement si `refused`.
  - La demande ne touche ni `reservations` ni la recherche (FR-013).
- **Rationale** : un cycle de vie à états finaux et transitions interdites (principe III). Les cinq états figurent dans la spec (FR-024).
- **Alternatives considered** :
  - Un statut « demandée » dans `ReservationStatus`. Écarté : cela modifie la 001, et une demande bloquerait la machine.
  - Un état dérivé sans classes. Écarté : quatre transitions et quatre états finaux.

## R5 — Garanties de la demande en base et sous verrou

- **Decision** :
  - **Au plus une décision et une réservation par demande** (FR-020) :
    - toute transition verrouille la demande (`lockForUpdate()`) dans une transaction et vérifie l'état sous verrou ;
    - un index unique porte sur `reservation_requests.reservation_id`.
  - **Pas de doublon en attente** (FR-012) : une contrainte d'exclusion `EXCLUDE USING gist (customer_account_id WITH =, machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&) WHERE (status = 'pending')`. `btree_gist` est déjà activée par la 001.
  - **10 demandes en attente au plus** (FR-012) : comptées sous verrou du compte (`lockForUpdate()` sur `customer_accounts`) dans la transaction d'envoi.
  - **Rattachement unique et définitif** (FR-005, FR-007) : la confirmation verrouille le compte. Si `customer_id` est déjà posé, la fiche est imposée ; sinon, le salarié choisit. Deux confirmations simultanées de deux demandes d'un même compte non rattaché ne créent donc pas deux fiches. `UPDATE … WHERE customer_id IS NULL` en garde-fou.
- **Rationale** : principe II. Deux salariés de deux agences, ou un client et un salarié, peuvent agir au même instant (US3-9, US4-9).
- **Alternatives considered** : le seul contrôle dans le composant Livewire. Écarté : le confort d'interface n'est pas une garantie.

## R6 — Confirmation : `CreateReservation` de la 001, appelée telle quelle

- **Decision** : `ConfirmReservationRequest` :
  - ouvre une transaction ;
  - verrouille la demande puis le compte ;
  - résout la fiche : existante, imposée par le rattachement, ou `NewCustomer` construite depuis le compte ;
  - appelle `Functional\Booking\Actions\CreateReservation::handle($salarié, $machine, $fiche, $début, $fin)`, puis passe la demande `confirmed` avec `reservation_id` ;
  - rattache le compte s'il ne l'était pas.

  Les règles de la 001 s'appliquent sans exception : dates, statut, VGP, chevauchement, contrainte d'exclusion, gardes ajoutées par la 007 (FR-017). Un refus de `CreateReservation` (`RefusalException`) annule la transaction : la demande reste `pending` et le motif s'affiche par `DisplaysRefusals`.
- **Rationale** :
  - `CreateReservation` se transacte elle-même ; imbriquée, elle devient un point de sauvegarde, et sa traduction de l'erreur d'exclusion (`23P01`) reste valable.
  - `ReservationChanged` implémente `ShouldDispatchAfterCommit` : les listeners des autres layers (attestation 005, caution 004…) ne partent qu'après la validation de la transaction englobante (FR-023).
- **Machine de remplacement** (FR-018) : le salarié choisit dans `AvailableMachinesQuery::get($début, $fin, $catégorie)`, sans filtre d'agence, la machine demandée en tête. La machine choisie est vérifiée de la même catégorie dans l'action (`ConfirmationMachineMismatchException` sinon).
- **Alternatives considered** :
  - Recopier les règles de la 001 dans `portal`. Écarté : divergence assurée.
  - Rejouer la création hors transaction puis marquer la demande. Écarté : une réservation sans demande confirmée en cas de panne entre les deux.

## R7 — Recherche identique à celle des salariés

- **Decision** :
  - La recherche de l'espace client appelle `Functional\Booking\Queries\AvailableMachinesQuery::get($début, $fin, $catégorie, $agence)`, la requête de l'écran `AvailabilitySearch` de la 001. Elle applique déjà les exclusions des autres layers via `ReservationRequestGuards` (FR-009, SC-008).
  - L'envoi d'une demande revérifie que la machine figure dans le résultat de la même requête pour ses dates (FR-011).
  - Le résultat est projeté sur un DTO `PortalMachineOffer` : référence, catégorie, agence, prix indicatif. Ni la réservation en conflit ni aucun client n'est jamais exposé (FR-010).
- **Rationale** : une seule définition de la disponibilité.
- **Alternatives considered** : une requête propre au portail. Écarté : FR-009 exige l'identité des résultats.

## R8 — Expiration et e-mails sans perte : état persisté + rattrapage

- **Decision** :
  - **E-mails de décision** : chaque décision (confirmée, refusée, expirée) dispatche, après commit, un job `NotifyRequestDecisionJob` unique par demande (`ShouldBeUnique`, clé = id de la demande). Le job envoie la notification `ReservationRequestDecided` (via un Mailable) au compte, puis pose `customer_notified_at`.
  - **Commande `portal:reconcile`**, planifiée toutes les 5 minutes (`withoutOverlapping()`) :
    1. passe `expired` toute demande `pending` dont `start_date` est antérieure à aujourd'hui (heure de Paris), une par une sous verrou (FR-027) ;
    2. remet en file le job des demandes décidées depuis plus de 10 minutes dont `customer_notified_at` est nul.
  - L'annulation par le client n'envoie pas d'e-mail : c'est le client qui agit.
- **Rationale** : principe IV, « un traitement qui ne doit pas se perdre s'appuie sur un état persisté et un rattrapage planifié ». « Même si un traitement a été interrompu » (FR-027) : l'expiration est rejouée tant qu'il reste une demande échue. La décision n'attend pas l'e-mail (FR-021).
- **Alternatives considered** :
  - Expirer à l'affichage (état dérivé). Écarté : il faut un e-mail, donc un instant de transition persisté.
  - Un job planifié à minuit seulement. Écarté : une panne à minuit laisserait des demandes en attente.
  - Le suivi d'envoi complet de la 005 (envois, échecs, liste à traiter). Écarté : pas de condition bloquante ici, la trace `customer_notified_at` et le rattrapage suffisent.

## R9 — Prix indicatif : table du layer, centimes, jamais transmis

- **Decision** :
  - Table `category_indicative_prices` : `machine_category_id` unique, `daily_price_cents` entier strictement positif (CHECK), auteur et agence. Les modifications sont tracées par `spatie/laravel-activitylog` (FR-031).
  - Retirer le prix supprime la ligne, avec l'historique conservé dans l'activité.
  - La demande copie `indicative_daily_price_cents` à l'envoi (FR-014).
  - L'affichage passe par `Number::currency($cents / 100, 'EUR', 'fr')`.
  - Aucun layer ne lit cette table : `billing` ne dépend pas de `portal` (FR-033).
- **Rationale** : principe III (montants entiers en centimes). Le montant HT est saisi au centime (validation du même format que les montants de caution de la 004).
- **Alternatives considered** :
  - Une colonne sur `machine_categories`. Écarté : table du layer `fleet`.
  - Réutiliser `Money` de `billing`. Écarté : `portal` ne dépend pas de `billing`, et un entier suffit.

## R10 — Accès : permissions côté salarié, périmètre « mon compte » côté client

- **Decision** :
  - **Salariés** : enum `PortalPermission` avec `portal.handle-requests` et `portal.manage-prices`, créées par `PortalPermissionSeeder` et données au rôle salarié par `PermissionSeeder` (FR-034).
  - **Clients** : des contrôles `lomkit/laravel-access-control` dans `portal`.
    - `ReservationRequestControl` a deux périmètres : `GlobalPerimeter` (salarié avec `portal.handle-requests`) et `OwnAccountPerimeter` (instance de `CustomerAccount`, requête restreinte à `customer_account_id`).
    - Les réservations et les documents d'un client sont lus par une requête du layer (`AccountReservations`) qui part toujours de la fiche rattachée au compte connecté (FR-028).
- **Rationale** : un compte client n'a ni rôle ni permission ; ce qui le limite, c'est la propriété des données. Voir Complexity Tracking du plan.
- **Alternatives considered** : des permissions spatie sur le guard `customer`, données à chaque compte. Écarté : une permission donnée à tous sans exception n'apporte aucun contrôle, et elle ajoute un rôle à maintenir.

## R11 — Côté salarié : liste temps réel, compteur, section de réservation

- **Decision** :
  - **Liste et confirmation** : l'écran `OnlineRequests` (route `portal.requests`) et sa fenêtre de confirmation `ConfirmRequestModal` (rattachement et machine).
  - **Temps réel** : l'événement `ReservationRequestChanged`, diffusé sur un nouveau canal privé `portal-requests` (autorisé par `portal.handle-requests`), rafraîchit la liste (FR-015).
  - **Compteur de navigation** : le composant `PendingRequestsBadge` est inclus dans `resources/views/layouts/app/sidebar.blade.php` avec l'entrée « Demandes en ligne » (FR-016). La même modification de la barre latérale ajoute l'entrée « Prix indicatifs ».
  - **Section du détail de réservation** : `ReservationOriginSection` est enregistrée dans `ReservationDetailSections` sans étape gardée (FR-022). Elle ne s'affiche que pour une réservation issue d'une demande, et `isGuardedBy()` reste faux pour toutes les étapes. Elle ne bloque donc ni la sortie ni le retour.
- **Rationale** :
  - Ce sont les points d'extension existants. `app/` est la colle de la navigation, comme pour les features 004 à 007.
  - Le tableau de bord (008) n'est pas modifié (FR-036).

## R12 — Écrans clients : layout propre, sans la barre des salariés

- **Decision** : un layout Blade `portal::layouts.portal` :
  - un en-tête Flux avec Rechercher, Mes demandes, Mes réservations, Mon compte et Déconnexion ;
  - les mêmes partials `head` et `app-logo` que l'application, pour garder la même identité visuelle.

  Les composants clients : `Search`, `SendRequestForm`, `MyRequests`, `MyReservations` (avec les documents), `AccountSettings`. Les tests vérifient qu'aucun écran salarié n'est accessible avec un compte client (FR-003).
- **Rationale** : le layout des salariés affiche le menu salarié et `auth()->user()` (guard `web`), inutilisables pour un client.

## R13 — Document VGP : le rapport du dernier envoi réussi ou de la remise

- **Decision** :
  - Requête `AccountCertificateDocuments` : pour chaque réservation non annulée de la fiche, elle cherche le dernier `CertificateDispatch` de sa `ReservationCertificate` dont le résultat est un succès (envoi e-mail réussi ou remise en main propre). Le résultat est calculé en une requête (sous-requête `latest` par attestation), sans boucle.
  - Le contrôleur `CustomerCertificateDownloadController` :
    - vérifie que la réservation appartient à la fiche du compte et que le rapport est bien celui du dernier envoi ;
    - lit le fichier sur le disque privé de la 005, sans rien écrire dans `certification` (FR-029, FR-030).
- **Rationale** : la 005 garantit que le rapport envoyé couvre toute la location ; c'est la preuve que le client doit présenter.
- **Alternatives considered** :
  - Le rapport en vigueur de la machine. Écarté : pour une réservation terminée, il peut être postérieur à la location.

## R14 — Historique

- **Decision** :
  - `PortalHistory` écrit dans le journal `spatie/laravel-activitylog` (`activity('portal')`), comme `AccountsHistory` de la 006. L'auteur est un salarié, un compte client ou « automatique ».
  - Sont tracés : sur la demande, l'envoi, la confirmation (réservation, rattachement), le refus (motif), l'annulation et l'expiration ; sur la fiche `Customer`, le rattachement (FR-007, FR-035).
- **Rationale** : même mécanisme que les features 004 à 007 ; c'est le journal partagé, pas une table de `booking`.
