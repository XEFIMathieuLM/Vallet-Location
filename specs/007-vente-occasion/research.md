# Research: Vente de machines d'occasion

Aucune inconnue technique bloquante : la stack, les packages et les mécanismes (registres d'extension, transmissions, journal d'activité) existent déjà. Les décisions ci-dessous fixent comment la vente s'insère dans les layers existants.

## R1. Placement : un nouveau layer `functional/sales`

- **Decision**: Nouveau layer OSDD `functional/sales`, généré par `osdd:layer`, qui dépend de `fleet`, `booking` et `billing`. Nouveau sens des dépendances : `sales → billing → inspection → booking → fleet` (et `sales → booking`, `sales → fleet` en direct).
- **Rationale**: Principe I : un nouveau domaine est un nouveau layer. La vente a besoin du parc (machine, retrait), des clients et des réservations (acheteur, conflits de location) et de la transmission (facturation). Aucun layer existant ne doit connaître la vente.
- **Alternatives considered**: Étendre `fleet` (rejeté : fleet devrait dépendre de booking et de billing, ce qui inverse le sens). Mettre la vente dans `billing` (rejeté : un domaine métier distinct, avec son propre cycle de vie et ses écrans).

## R2. Refus d'une location au-delà de la date de remise (FR-010) : un registre de gardes de création dans booking

- **Decision**: booking expose un nouveau point d'extension `ReservationRequestGuards` (registre, sur le modèle de `ReservationTransitionGuards`) et un contrat `ReservationRequestGuard` avec deux méthodes :
  - `ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void`, appelée par `CreateReservation::reserve()` sous le verrou de la machine, après `MachineEligibility` ; lève une `RefusalException` ;
  - `excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void`, appelée par `AvailableMachinesQuery` pour ne pas proposer en recherche une machine qui serait refusée.
  sales y enregistre `ReservedSaleReservationGuard`.
- **Rationale**: Principe I (point d'extension générique rempli depuis le provider du layer supérieur) et Principe II : le contrôle se fait dans la transaction de création, sous le même verrou `machines … FOR UPDATE` que l'acceptation d'une offre (R5), donc une location et une acceptation simultanées sont sérialisées.
- **Alternatives considered**: Ajouter une méthode `beforeCreation` à `ReservationTransitionGuard` (rejeté : oblige les gardes existants de l'inspection à implémenter une méthode vide, et mélange création et transitions). Passer la machine en un statut fleet « vendue sous réserve » (rejeté : la machine reste louable avant la date de remise ; un statut global ne sait pas exprimer « louable jusqu'au 19 »).
- **Modification d'un layer existant (booking)** : nouveau contrat, nouveau registre singleton, appel dans `CreateReservation` et `AvailableMachinesQuery`. Aucun changement de comportement sans garde enregistré. À arbitrer par la coordination : faite dans 007 ou confiée à 001.

## R3. Refus du retrait manuel d'une machine en vente (FR-016) : un registre de gardes de retrait dans fleet

- **Decision**: fleet remplace le binding unique `MachineRetirementGuard` (aujourd'hui `bindIf` sur `UnrestrictedRetirement`, écrasé par booking) par un registre `MachineRetirementGuards` ; `RetireMachine` appelle tous les gardes enregistrés. booking enregistre `ActiveReservationsRetirementGuard` dans le registre au lieu de rebinder le contrat ; sales enregistre `OpenSaleRetirementGuard`. `UnrestrictedRetirement` disparaît (un registre vide = aucune restriction).
- **Rationale**: avec un binding unique, le dernier provider qui bind gagne : sales écraserait le garde des réservations, ou devrait le décorer en connaissant sa classe. Le registre est le point d'extension générique attendu par le Principe I.
- **Alternatives considered**: Décorateur dans sales qui enveloppe le garde de booking (rejeté : dépend de l'ordre de chargement des providers et masque le garde de booking). Garde composite dans sales (rejeté : même problème).
- **Modification d'un layer existant (fleet + booking)** : `RetireMachine`, `FleetServiceProvider`, suppression de `UnrestrictedRetirement`, `BookingServiceProvider`. À arbitrer par la coordination.

## R4. Mention « en vente » dans le parc et le planning (FR-005) : un registre de badges dans fleet

- **Decision**: fleet expose `MachineBadges` (registre) et le contrat `MachineBadgeProvider::badgesFor(array $machineIds): array<int, list<MachineBadge>>` (lot de machines, une requête par fournisseur, pas de requête dans une boucle). `MachineIndex` (fleet) et `Planning` / `AvailabilitySearch` (booking) affichent les badges. sales enregistre `SaleMachineBadges` (« en vente », « vendue sous réserve au JJ/MM », avec lien vers la vente).
- **Rationale**: Principe I : fleet ne peut pas lire les ventes. Le chargement par lot respecte le Principe II (agrégats en base, pas de requête dans une boucle).
- **Alternatives considered**: Rien afficher dans le parc et le planning (rejeté : FR-005 ; c'est ce qui évite qu'une agence promette une machine déjà vendue). Une section ajoutée au détail de machine (rejeté : il n'existe pas d'écran de détail de machine, et le planning resterait muet).
- **Modification d'un layer existant (fleet + booking)** : contrat, registre et objet-valeur dans fleet, affichage dans trois vues. À arbitrer par la coordination.

## R5. Concurrence et garanties en base (FR-002, FR-011, FR-009, FR-010, FR-014)

- **Decision**:
  - une seule vente non annulée par machine : index unique partiel `sales(machine_id) WHERE status <> 'cancelled'` ; il couvre « une seule vente ouverte » et « jamais remise en vente après une vente conclue » ;
  - une seule offre acceptée par vente : index unique partiel `sale_offers(sale_id) WHERE status = 'accepted'` ;
  - CHECK : prix et montants `> 0` ; `buyer_id`, `final_price_cents` et `planned_handover_date` renseignés si et seulement si le statut est `reserved` ou `sold` ; `handed_over_at` renseigné si et seulement si `sold` ; `cancellation_reason` renseigné si et seulement si `cancelled` ;
  - toute action de vente qui touche aux locations (accepter, changer la date de remise, remettre) verrouille d'abord la ligne `machines` (`lockForUpdate`), puis la vente : même ordre de verrous que `CreateReservation`, donc sérialisation et pas d'interblocage ;
  - violation d'unicité (SQLSTATE `23505`) traduite en exception de domaine via `rescue()`, comme `CreateReservation` le fait pour `23P01`.
- **Rationale**: Principe II : deux agences agissent en même temps ; seule la base voit les deux requêtes.
- **Alternatives considered**: Contrôle applicatif seul (rejeté : Principe II).

## R6. Cycles de vie : pattern State pour la vente et pour l'offre

- **Decision**: `SaleStatus` (`listed`, `reserved`, `sold`, `cancelled`) avec une classe par état ; transitions `reserve` (listed → reserved), `release` (reserved → listed), `sell` (reserved → sold), `cancel` (listed|reserved → cancelled) ; toute autre transition lève `IllegalSaleTransitionException`. `OfferStatus` (`pending`, `accepted`, `rejected`, `withdrawn`) avec une classe par état ; transitions `accept` et `reject` (pending), `withdraw` (pending|accepted, ce dernier cas à la levée de la réservation) ; `IllegalOfferTransitionException` sinon.
- **Rationale**: Principe III : deux cycles à plusieurs états avec des transitions interdites. Même structure que `MachineState` et `TransmissionState` (interface, trait `Refuses…Transitions`, fabrique `fromStatus`).

## R7. Transmission de la vente : une source de transmission enregistrable dans billing

- **Decision**: billing généralise la transmission à des sources fournies par les layers supérieurs, sans connaître sales :
  - contrat `BillableSource` (billing) : `type(): BillableLineType`, `line(Transmission $transmission): BillableLine`, `subject(Transmission $transmission): TransmissionSubject` (libellé, client, URL de l'écran d'origine, modèle auquel rattacher l'historique) ;
  - registre `BillableSources` (billing) indexé par type ;
  - nouvelle colonne `transmissions.source_type` / `source_id` (nullable, unique ensemble) ; `reservation_id` devient nullable ; le CHECK `transmissions_single_source` devient `num_nonnulls(billable_period_id, damage_settlement_id, source_id) = 1`, avec `reservation_id` obligatoire pour une période ou un dégât ;
  - action `QueueSourceTransmission::handle(BillableLineType $type, int $sourceId): Transmission` (idempotente grâce à l'index unique), appelée par sales dans la transaction de remise ; l'envoi part après commit par le `SendTransmissionJob` existant ;
  - nouveau cas `BillableLineType::UsedMachineSale` et champs nullables `sourceRef` / `saleDate` dans `BillableLine` ; `reservationRef` et `bookingAgency` deviennent nullables ;
  - `MakeBillableLine`, `TransmissionLifecycle` (historique), l'écran `Transmissions` et l'export délèguent à la source enregistrée quand `source_type` est renseigné.
  Tout le reste de la feature 003 (relances, liste à traiter, alerte, export de secours, idempotence par UUID, rattrapage planifié `ReconcileCommand`) s'applique sans changement.
- **Rationale**: FR-020 exige les mêmes garanties que 003 ; les réutiliser évite un second circuit. Le cas `UsedMachineSale` appartient au vocabulaire de billing (ce que le logiciel de facturation reçoit) ; billing n'importe aucune classe de sales.
- **Alternatives considered**: Relation polymorphe Eloquent `morphTo` vers `Sale` (rejeté : billing déclarerait une relation vers un layer supérieur, interdit par le Principe I). Une table de transmission propre à sales avec son propre job (rejeté : duplique relances, export, alerte et liste ; une vente échouée n'apparaîtrait pas avec les autres). Transmettre via un événement écouté par billing (rejeté : billing devrait connaître l'événement de sales).
- **Modification d'un layer existant (billing)** : migration additive sur `transmissions`, contrat, registre, action, enum, objet-valeur, délégation dans 4 classes et 1 vue. À arbitrer par la coordination.

## R8. Acheteur

- **Decision**: L'acheteur est un `Customer` de booking (`sale_offers.customer_id`, `sales.buyer_id`). Le formulaire d'offre propose la recherche d'un client existant ou la création d'un nouveau client (nom, téléphone, e-mail), comme `CreateReservationForm`. L'identifiant dans le logiciel de facturation reste géré par billing (`CustomerBillingAccount`), et se corrige depuis l'écran des transmissions comme pour une location.
- **Rationale**: FR-007, réutilisation du fichier client ; sales dépend de booking, sens autorisé.

## R9. Historique, temps réel, permissions, traductions

- **Decision**:
  - Historique : `spatie/laravel-activitylog` (déjà installé), journal `sales`, `performedOn($sale)`, via une classe `SaleHistory` sur le modèle de `BillingHistory` ; les tentatives de transmission sont rattachées à la vente par `TransmissionSubject`.
  - Temps réel : événement `SaleChanged` (`ShouldBroadcast`, `ShouldDispatchAfterCommit`) sur le canal privé `sales`, charge utile explicite (id, machine_id, status, planned_handover_date) ; les écrans de ventes, le parc et le planning se rafraîchissent (badges).
  - Permission `sales.manage`, déclarée dans `SalesPermissionSeeder` et attribuée au rôle salarié (FR-012a), contrôlée sur les routes et dans un `SaleControl` lomkit.
  - Traductions dans `functional/sales/resources/lang/fr`.
- **Rationale**: Principes IV, V, VII ; mêmes mécanismes que les features 001 à 003.

## R10. Aucun nouveau package

Tout est couvert par les dépendances actuelles (Livewire, Flux, activitylog, permission, access-control, faker).
