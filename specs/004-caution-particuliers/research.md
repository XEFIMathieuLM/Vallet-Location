# Research: Caution des particuliers

Décisions D1–D12 prises pour le plan. Chacune : décision, raison, alternatives écartées.

## D1 — Un layer `deposit` au sommet, le type de client dans `booking`

**Décision** : la caution est un nouveau layer OSDD `functional/deposit` (namespace `Functional\Deposit`), au-dessus de `billing` : `deposit → billing → inspection → booking → fleet`. Le type de client (particulier / professionnel) n'est pas dans `deposit` mais dans `booking`, propriétaire du `Customer` : enum `CustomerType`, colonne `customers.type`, action `UpdateCustomer`, événement `CustomerChanged`, registre `CustomerChangeGuards`.

**Raison** : la caution lit les règlements de dégâts (`billing`), les dégâts non traités (`inspection`) et réutilise `Money` (`billing`) ; elle doit donc dépendre de ces trois layers. Le type de client sert à la 004, à la 005 (attestation) et à la 006 (grands comptes) : le mettre dans `deposit` obligerait la 006 à dépendre de la caution. La 001 annonçait que le client serait « enrichi par les specs suivantes ». C'est la feature 004 qui enrichit le layer `booking`, pas le layer `deposit` qui écrit dans les tables de `booking` : le principe I (« un layer ne modifie jamais les fichiers ni les tables d'un autre layer ») est respecté. Contrat arbitré avec la coordination des features 005 et 006.

**Alternatives écartées** : table `customer_types` dans `deposit` (la 006 dépendrait de la caution) ; type dans un layer `customer` dédié (un layer pour une colonne, et `booking` ne pourrait pas l'exiger à la création du client).

## D2 — Le blocage de la sortie par un garde de transition

**Décision** : `Functional\Deposit\Guards\DepositCollectedGuard implements ReservationTransitionGuard`, enregistré dans `ReservationTransitionGuards`. `beforeDeparture()` lève `DepositRefusedException::customerTypeMissing()` si le client n'a pas de type, `::notCollected(Money $expected)` si le client est particulier et qu'aucune caution n'est encaissée ; `beforeReturn()` ne fait rien.

**Raison** : `DepartReservation` appelle les gardes dans sa transaction, après le verrou sur la réservation : le refus est garanti côté serveur (FR-005, principe II). L'encaissement verrouille aussi la réservation (D5) : une sortie et un encaissement simultanés se sérialisent.

**Alternatives écartées** : écouter `ReservationChanged` (trop tard, la sortie est faite) ; bloquer seulement dans l'interface (interdit par le principe II).

## D3 — La section « Caution » et la disponibilité de la sortie par section

**Décision** : composant Livewire `deposit.reservation-section` (`ReservationDepositSection`), enregistré par `register('deposit.reservation-section', 30, ReservationTransition::Departure)` (contrat 001 `7423fc9`). Il émet au mount et après chaque action `dispatch('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: 'deposit.reservation-section', is_ready: $isReady)` ; `$isReady` est vrai quand la caution est encaissée ou non requise (client professionnel, réservation déjà sortie).

**Raison** : depuis la décision utilisateur, le bouton de sortie ne s'active que si toutes les sections gardiennes sont prêtes ; une section gardienne muette bloque. Le garde serveur (D2) reste la seule garantie.

## D4 — Cycle de vie de la caution en pattern State

**Décision** : `deposits.status` (texte, enum `DepositStatus`) avec six états et une classe par état sous `src/States/` : `Collected` (encaissée), `ToRefund` (à restituer), `BlockedByDamage` (bloquée par un dégât), `ToSettle` (à solder), `Refunded` (restituée), `Settled` (soldée). Toute transition non prévue lève `IllegalDepositTransitionException`. Les états « non requise », « type de client à renseigner » et « à encaisser » sont **dérivés** (aucune ligne `deposits`) : calculés par `DepositSituation` à partir du type du client, du statut de la réservation et de l'existence d'une caution, sans pattern State (une seule transition : l'encaissement), comme l'autorise le principe III.

Transitions :

| De | Vers | Déclencheur |
|----|------|-------------|
| Collected | ToRefund | réservation annulée, ou clôturée sans dégât non traité |
| Collected | BlockedByDamage | réservation clôturée avec au moins un dégât non traité |
| ToRefund | BlockedByDamage | dégât signalé après la clôture, avant la restitution |
| ToSettle | BlockedByDamage | nouveau dégât signalé avant le solde |
| BlockedByDamage | ToSettle | tous les dégâts réglés, au moins un refacturé |
| BlockedByDamage | ToRefund | tous les dégâts réglés, aucun refacturé |
| ToRefund | Refunded | restitution enregistrée (confirmation « aucun dégât » si la réservation est clôturée) |
| ToSettle | Settled | solde validé |

**Raison** : les transitions interdites portent de l'argent (restituer une caution bloquée par un dégât, solder deux fois) : principe III.

## D5 — Encaissement unique et sérialisé

**Décision** : `CollectDeposit::handle(Reservation, PaymentMethod, ?string $reference, Authenticatable&AgencyMember $author): Deposit` : transaction, `Reservation::lockForUpdate()`, refus si la réservation n'est pas confirmée, si le client n'est pas particulier, ou si une caution existe ; montant = `ResolveDepositAmount::forReservation()` au moment de l'encaissement. Index unique `deposits.reservation_id` en dernier rempart (FR-008).

**Raison** : principe II ; le verrou sur la réservation sérialise aussi avec `DepartReservation` et `CancelReservation`, qui verrouillent la même ligne.

## D6 — Réagir au retour, à l'annulation et aux dégâts : listeners + rattrapage

**Décision** : un seul calcul, `SyncDepositStatus::for(Reservation)`, qui lit le statut de la réservation, le nombre de dégâts non traités (`CountUnresolvedDamages`) et les règlements (`damage_settlements`), puis applique la transition due sous `lockForUpdate()` sur la caution. Il est appelé par :
- `SyncDepositOnReservationChanged` (listener de `ReservationChanged`, statut `closed` ou `cancelled`) ;
- `SyncDepositOnDamageChanged` (listener de `DamageChanged`, `reservationId`) ;
- la commande planifiée `deposit:reconcile` (toutes les 5 minutes) sur les cautions non finales, pour rattraper un événement perdu.

**Raison** : principe IV (événement explicite + listener ; état persisté et rattrapage planifié). `SyncDepositStatus` est idempotent : rejouer un événement ne change rien. Les actions de restitution et de solde revérifient les conditions dans leur transaction (D8), donc un état en retard ne permet jamais une restitution interdite.

**Alternatives écartées** : état entièrement dérivé à la lecture (la liste « en attente depuis » de FR-019 exigerait une requête sur quatre layers et des dates absentes : `booking` n'a pas de date d'annulation) ; job en file seul (perte possible, principe IV).

## D7 — Calcul de la retenue

**Décision** : `DepositRetention` (value object, calcul en mémoire) : `retained = min(billedTotal, deposit)`, `refunded = deposit - retained`, en `Money`. Le total hors taxes des dégâts refacturés de la réservation est agrégé en SQL (`SUM(damage_settlements.amount_cents)` joint à `damages`, `outcome = billed`) par `BilledDamagesTotal::for(Reservation): Money`.

**Raison** : principe II (agrégat en base) et constitution 1.0.1 (calcul en mémoire → test Unit pur ; calcul en base → test Feature). FR-013 : retenue non modifiable par le salarié.

## D8 — Restitution et solde

**Décision** :
- `RefundDeposit::handle(Deposit, bool $isNoDamageConfirmed, Authenticatable&AgencyMember $author)` : transaction, verrou sur la caution, appelle d'abord `SyncDepositStatus` puis exige l'état `ToRefund` ; si la réservation est clôturée, `$isNoDamageConfirmed` doit être vrai (`DepositRefusedException::noDamageNotConfirmed()`) ; enregistre `refunded_cents = amount_cents`, `retained_cents = 0`, `closed_by`, `closed_at`, `is_no_damage_confirmed`.
- `SettleDeposit::handle(Deposit, Authenticatable&AgencyMember $author)` : même verrou, exige `ToSettle`, recalcule la retenue (D7) au moment de la validation et l'enregistre ; aucun montant n'est saisi.

**Raison** : FR-012, FR-013, FR-014 ; le recalcul au solde empêche d'utiliser une retenue périmée.

## D9 — Montant par catégorie et montant par défaut

**Décision** : table `deposit_rates` : `machine_category_id` nullable (null = montant par défaut), `amount_cents` > 0 (CHECK). Deux index uniques partiels : un par catégorie, un seul `WHERE machine_category_id IS NULL`. La migration insère le montant par défaut initial depuis la config `deposit.initial_default_amount_cents` (1 500,00 €, à confirmer avec M. Vallet). Le montant par défaut se modifie mais ne se supprime pas. `ResolveDepositAmount::forCategory(MachineCategory): Money` et `::forReservation(Reservation): Money`. Écrans calqués sur les vues photo de la 002 : `/cautions/montants` (liste des catégories avec leur montant effectif) et un formulaire par catégorie.

**Raison** : Q1 → A ; FR-017, FR-018 (le montant est figé dans `deposits.amount_cents` à l'encaissement).

## D10 — Correction d'un encaissement

**Décision** : `CorrectDepositPayment::handle(Deposit, PaymentMethod, ?string $reference, string $reason, Authenticatable&AgencyMember $author)` : autorisé dans les états `Collected`, `ToRefund`, `BlockedByDamage`, `ToSettle` ; motif obligatoire ; l'historique conserve l'ancienne et la nouvelle valeur. Le montant n'est jamais corrigé.

**Raison** : clarification du 2026-10-10 (FR-010).

## D11 — Historique

**Décision** : `DepositHistory::record(Reservation, DepositHistoryEvent, Authenticatable&AgencyMember $author, array $details)` sur le modèle de `BillingHistory` : `activity('deposit')->performedOn($reservation)->causedBy($author)`, propriété `author_agency_id`. Événements : `collected`, `payment_corrected` (motif, ancien, nouveau), `refunded` (confirmation « aucun dégât »), `settled` (retenu, restitué), `blocked_by_damage`, `released`. La qualification du client est tracée par `booking` sur le client (`Customer` passe en `LogsActivity` + `RecordsAuthorAgency`, `logOnly(['type'])`) : un client a plusieurs réservations, la qualification lui appartient.

**Raison** : FR-021 ; même mécanisme que les 002 et 003. La spec est alignée : la qualification est tracée dans l'historique du client.

## D12 — Permissions

**Décision** : enum `Functional\Deposit\Access\DepositPermission` : `ManageDeposits = 'deposits.manage'` (encaisser, corriger, restituer, solder, qualifier un client depuis la section) et `ManageDepositRates = 'deposit_rates.manage'` (écran des montants). `DepositPermissionSeeder` crée seulement ses permissions ; `DatabaseSeeder` et `Tests\TestCase::seedPermissions()` l'appellent avant `PermissionSeeder`, qui donne tout au rôle `salarie`. La qualification passe par `UpdateCustomer` dans `booking`, contrôlée par `BookingPermission::ManageReservations` côté `booking` et par `deposits.manage` côté section.

**Raison** : principe V ; convention 001 (`7122415`).
