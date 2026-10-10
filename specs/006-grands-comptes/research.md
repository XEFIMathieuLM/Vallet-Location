# Research: Grands comptes : tarifs négociés et bon de commande

Décisions de conception G1 à G10. Les décisions métier (lieu des tarifs, moment du bon de commande, numéro seul, numéro réutilisable) sont dans la section « Clarifications » de la spec.

## G1. Emplacement : un nouveau layer `accounts`

- **Decision** : un layer OSDD `functional/accounts` (namespace `Functional\Accounts`), au sommet de la chaîne : `accounts → billing → inspection → booking → fleet`. Il possède la désignation des grands comptes et le bon de commande des réservations.
- **Rationale** : principe I, « un nouveau domaine métier DOIT être un nouveau layer ». Le grand compte est une relation commerciale (accord tarifaire, bon de commande) distincte de la réservation (`booking`) et de la transmission (`billing`). Placé au-dessus de `billing`, le layer lit l'identifiant de facturation du client (`CustomerBillingAccount`, FR-002) sans qu'aucun layer inférieur ne le connaisse. Il agit sur les écrans et les transitions des layers inférieurs uniquement par leurs points d'extension (G3 à G6).
- **Alternatives considered** :
  - *Tout dans `billing`* (colonnes sur `customer_billing_accounts`, table des bons de commande dans `billing`, champ natif de `BillableLine`) : moins de points d'extension, mais mélange la relation commerciale et la mécanique d'envoi dans un layer déjà large, et modifie le cœur de la 003 au lieu de l'étendre.
- **Statut** : validé par la coordination (2026-10-10).
  - *Dans `booking`* (indicateur sur `customers`, colonne sur `reservations`) : impossible, `booking` ne peut pas lire l'identifiant de facturation (`billing` dépend de `booking`).

## G2. Le type de client vient de la 004

- **Decision** : `accounts` lit le type de client introduit par la 004 (particulier / professionnel / à renseigner) et ne crée aucun type. Noms provisoires dans ce plan : colonne `customers.type`, enum `Functional\Booking\Enums\CustomerType` avec le cas `Professional` ; les noms définitifs sont repris de la 004 au rebase.
- **Écriture du client** (convention de la coordination) : toute écriture de la table `customers` passe par l'action unique `Functional\Booking\Actions\UpdateCustomer` (une méthode par changement : type pour la 004, e-mail pour la 005) et émet `Functional\Booking\Events\CustomerChanged` (`ShouldDispatchAfterCommit`). La désignation grand compte n'écrit pas `customers` (G9, table `key_accounts` d'`accounts`) : elle ne passe donc pas par `UpdateCustomer`, que `booking` ne pourrait pas doter du contrôle de l'identifiant de facturation (FR-002, `billing` est au-dessus de `booking`). `DesignateKeyAccount` et `RevokeKeyAccount` émettent `CustomerChanged` après commit, pour que les écrans qui suivent le client se rafraîchissent comme pour tout changement de client.
- **Rationale** : spec, Assumptions ; la 004 (FR-003) laisse volontairement le type « professionnel » sans règle grand compte.
- **Alternatives considered** : un type `key_account` ajouté à l'enum de la 004 : rejeté, il concurrencerait le modèle de la 004 et ferait perdre la qualité « professionnel » (caution non exigée).

## G3. Garde de sortie : `ReservationTransitionGuards` (001)

- **Decision** : `PurchaseOrderDepartureGuard` (`accounts`) implémente `ReservationTransitionGuard` et s'enregistre dans `ReservationTransitionGuards`. `beforeDeparture` refuse par `MissingPurchaseOrderException` (sous-classe de `RefusalException`, message technique anglais et clé de traduction, factory nommée) si le client est grand compte et que la réservation n'a pas de numéro. `beforeReturn` ne fait rien.
- **Rationale** : `DepartReservation` appelle les gardes dans sa transaction, après `lockForUpdate()` sur la réservation (FR-007, principe II). La saisie du numéro verrouille la même ligne de réservation (G7) : une sortie et une saisie concurrentes sont sérialisées.
- **Alternatives considered** : un listener après la sortie : ne peut pas refuser.

## G4. Section « Bon de commande » et readiness par section (001)

- **Decision** : composant Livewire `accounts.purchase-order-section` enregistré dans `ReservationDetailSections` (position 15, entre les photos et la facturation). La section se prononce pour l'étape `ReservationTransition::Departure` selon le contrat de readiness par section livré par la 001 : prête si le numéro est saisi ou s'il n'est pas exigé. Pour un client particulier ou de type à renseigner, la section n'affiche rien mais se déclare prête.
- **Rationale** : FR-008 ; décision utilisateur relayée par la coordination (readiness par section). Le nom exact de l'API de readiness est repris de la 001 au rebase ; sur la base actuelle, c'est l'événement Livewire `reservation-transition-readiness` (`step`, `is_ready`).

## G5. Badge grand compte : nouveau registre `CustomerBadges` dans `booking`

- **Decision** : point d'extension générique dans `booking`, de même forme que `MachineBadges` de la 007 dans `fleet` : `Functional\Booking\Extensions\CustomerBadges` (`register`, `forCustomers(list<int>)`, `refreshListeners()`), fournisseurs `Functional\Booking\Contracts\CustomerBadgeProvider` (`badgesFor(list<int> $customerIds): array<int, list<CustomerBadge>>`, `refreshListeners(): list<string>`), valeur `Functional\Booking\ValueObjects\CustomerBadge` (`label`, `color`, `?url`, `?description`). `CreateReservationForm` l'appelle une fois pour la liste de clients affichée (aucune requête par client) et affiche la précision du client sélectionné. `accounts` enregistre `KeyAccountBadgeProvider` : « Grand compte » / « Tarif négocié appliqué par la facturation, bon de commande exigé avant la sortie ».
- **Rationale** : FR-004 sans que `booking` connaisse `accounts` (principe I). Appel groupé : principe II (pas de requête dans une boucle).
- **Alternatives considered** : afficher le badge seulement dans la section du détail : ne couvre pas la recherche de client (FR-004, US1 scénario 4).

## G6. Requalification d'un grand compte : garde sur l'écriture du client (004)

- **Decision** : FR-003 (refuser de requalifier « particulier » un grand compte) a besoin d'un garde appelé par l'action d'écriture du client que la 004 ajoute dans `booking` (avec l'événement `CustomerChanged`). Point d'extension demandé : registre `CustomerChangeGuards` + contrat `CustomerChangeGuard::beforeTypeChange(Customer, ?CustomerType $newType)` qui lève une `RefusalException`, appelé dans la transaction de l'action, client verrouillé. `accounts` enregistre `KeyAccountTypeGuard` (`KeyAccountMustStayProfessionalException`).
- **Rationale** : un événement après commit ne peut pas refuser. Aucune action concurrente d'écriture du client n'est créée (consigne de coordination).
- **Statut** : tranché par la coordination : le registre `CustomerChangeGuards` est livré par la 004, qui possède le changement de type et l'appelle dans la transaction de `UpdateCustomer`. La 006 y enregistre seulement sa garde.

## G7. Bon de commande : une ligne par réservation, verrou sur la réservation

- **Decision** : table `reservation_purchase_orders` (`accounts`), au plus une ligne par réservation (index unique). `SetPurchaseOrder` ouvre une transaction, verrouille la réservation (`lockForUpdate()`), refuse si elle n'est pas `confirmed` (`PurchaseOrderFrozenException`), normalise le numéro (trim, 1 à 50 caractères, sinon `InvalidPurchaseOrderNumberException`), refuse si le client n'est pas professionnel, puis crée ou remplace la ligne et écrit l'historique (ancien et nouveau numéro). Aucune action de suppression (FR-009).
- **Rationale** : FR-005, FR-009 ; principe II. Le verrou sur la réservation sérialise la saisie avec `DepartReservation` (G3) : après la sortie, la saisie est refusée ; la sortie voit le numéro saisi juste avant. Deux saisies simultanées : la seconde attend la première et la remplace, les deux sont tracées (edge case de la spec).
- **Alternatives considered** : colonne sur `reservations` : interdit, un layer ne modifie pas les tables d'un autre (principe I). Garde base par trigger sur le statut : rejeté, le statut appartient à `booking`.

## G8. Transmission du numéro : port `PurchaseOrderNumbers` dans `billing`

- **Decision** : point d'extension dans `billing` (contrat : [contracts/billing-purchase-order.md](contracts/billing-purchase-order.md)) :
  - interface `Functional\Billing\Contracts\PurchaseOrderNumbers` : `forReservation(int $reservationId): ?string` et sa version groupée `forReservations(list<int>): array<int, string>` pour l'export de secours ; implémentation par défaut `NullPurchaseOrderNumbers` (renvoie `null`) liée par `BillingServiceProvider` avec `bindIf`, pour que la liaison d'`accounts` l'emporte quel que soit l'ordre de chargement des providers ;
  - `BillableLine` reçoit `?string $purchaseOrderNumber` (`purchase_order_number` dans `toArray()`), rempli par `MakeBillableLine` pour les périodes et les dégâts ;
  - l'export de secours ajoute la colonne `purchase_order_number` en dernière position.
  `accounts` lie `KeyAccountPurchaseOrderNumbers` (lecture de `reservation_purchase_orders`) à la place de l'implémentation par défaut.
- **Rationale** : FR-011 à FR-013 ; consigne de coordination (décrire la modification de la 003 comme point d'extension). `billing` ne sait pas d'où vient le numéro. Le numéro est lu au moment de l'envoi : il est figé à la sortie (G7), donc identique pour toutes les périodes et tous les dégâts. Les éléments déjà `sent` ou `exported` ne sont jamais renvoyés (FR-013, garanti par la 003).
- **Alternatives considered** : copier le numéro dans `transmissions` à leur création : inutile puisque le numéro est figé, et `billing` devrait connaître le bon de commande au moment de créer les périodes.
- **Rebase** : la 007 modifie aussi `BillableLine` (sources de transmission) et la 003 introduit `Money` et supprime `Support/` (`ExportLineFormatter`) ; l'emplacement exact des modifications de `billing` est repris au rebase.

## G9. Désignation : table `key_accounts`, préalable identifiant de facturation

- **Decision** : table `key_accounts` (`accounts`) : `customer_id` unique, `designated_by`, `designated_at`. `DesignateKeyAccount` verrouille le client, refuse si son type n'est pas professionnel (`KeyAccountRefusedException::notProfessional`) ou si aucun `CustomerBillingAccount` à `external_ref` non vide n'existe (`::missingBillingRef`), sinon crée la ligne. `RevokeKeyAccount` supprime la ligne. Les deux écrivent l'historique du client.
- **Rationale** : FR-001, FR-002, FR-016. La suppression de la ligne n'efface rien d'utile : l'historique est conservé dans le journal d'activité, et l'index unique empêche toute double désignation.
- **Alternatives considered** : colonne `is_key_account` sur `customers` (table de `booking`) ou sur `customer_billing_accounts` (table de `billing`) : interdit par le principe I.

## G10. Écrans, permissions, historique

- **Decision** :
  - Écran `/grands-comptes` (`accounts.key-accounts`) : liste des grands comptes ; recherche de clients professionnels avec leur identifiant de facturation ; désigner ou retirer. Écran propre au layer, pour ne dépendre d'aucune fiche client de la 004.
  - Écran `/bons-de-commande` (`accounts.missing-purchase-orders`) : réservations `confirmed` de grands comptes sans numéro, triées par date de début, filtre par agence de rattachement de la machine, mise en évidence à `accounts.highlight_days_before_departure` (3) jours ou moins, saisie en ligne.
  - Permissions `key_accounts.manage` et `purchase_orders.manage` (`AccountsPermissionSeeder`), attribuées au rôle salarié ; contrôles `lomkit` `KeyAccountControl` et `PurchaseOrderControl`.
  - Historique par `spatie/laravel-activitylog` (log `accounts`) : sur la réservation pour le bon de commande, sur le client pour la désignation ; auteur et agence de l'auteur.
  - Entrée « Grands comptes » et « Bons de commande » dans la barre latérale (`app/`, colle).
- **Rationale** : FR-014 à FR-017 ; principe V.

## Saisie du bon de commande à la création de la réservation (US2, scénario 5)

- **Decision** : ne pas ajouter de champ au formulaire de création de `booking`. Après la création, `CreateReservationForm` redirige déjà vers le détail de la réservation, où la section « Bon de commande » permet la saisie immédiate. Le scénario 5 devient : « après la création, le détail s'ouvre et le numéro se saisit dans la section ».
- **Rationale** : un champ dans le formulaire de création demanderait un point d'extension « champs du formulaire de création » dans `booking`, qui écrirait le numéro avant que la réservation existe ou après coup hors de sa transaction. Le gain (un écran de moins) ne justifie pas ce point d'extension.
- **Statut** : décision de l'utilisateur (option A), relayée par la coordination ; spec amendée (US2 scénario 5, FR-005, Clarifications).
