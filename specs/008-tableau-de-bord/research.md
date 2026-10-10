# Research: Tableau de bord d'accueil

## R1. Où vit le tableau de bord ?

- **Decision** : dans `app/`, qui sert de racine de composition. Les données sont lues par des classes de requête des layers.
- **Rationale** :
  - `app/` assemble déjà les layers : la barre latérale importe les permissions des 8 layers, et le layout affiche les bandeaux de `billing` et de `certification`. La page d'accueil relève de la même colle de navigation.
  - Les définitions métier restent dans leur layer. `app/` ne fait qu'appeler des requêtes et choisir quoi afficher.
  - Aucun layer existant ne change.
- **Alternatives considered** :
  - *Nouveau layer `functional/dashboard`* : écarté. Ce n'est pas un domaine métier, et il dépendrait des 8 layers ; ce serait un layer fourre-tout qui fait le travail de `app/`.
  - *Registre générique de widgets dans `fleet`, rempli par chaque layer* : écarté pour cette feature.
    - Il faudrait un contrat et un registre nouveaux dans `fleet`, puis un enregistrement dans 8 service providers, pour un seul consommateur.
    - Ce serait aussi 8 layers modifiés au lieu de 5 fichiers ajoutés.
    - À reconsidérer si un futur layer doit apparaître sur l'accueil sans toucher `app/`.

## R2. Les compteurs reprennent-ils exactement les listes ?

- **Decision** : chaque compteur appelle la requête de son écran, ou une requête identique quand l'écran construit la sienne en ligne.

| Compteur | Écran (route) | Source du nombre |
|---|---|---|
| Transmissions à traiter | `billing.transmissions` | `Billing\Queries\TransmissionsToHandle::query()->count()` (déjà utilisée par le bandeau) |
| Attestations VGP à traiter | `certification.certificates` | `Certification\Queries\CertificatesToHandle::query()->count()` (déjà utilisée par le bandeau) |
| Cautions en attente | `deposit.pending.index` | `Deposit\Queries\PendingDeposits::query()->count()` |
| Bons de commande manquants | `accounts.missing-purchase-orders` | `Accounts\Queries\MissingPurchaseOrders::query()->count()` |
| Dégâts à traiter | `inspection.damages` | nouvelle `Inspection\Queries\DamagesToHandle` : dégâts dont `resolved_at` est nul, le filtre exact de `ReservationsToReinvoice` ; le nombre compte les dégâts, une ligne d'action chacun sur l'écran |
| Ventes en retard | `sales.index?statut=reserved` | nouvelle `Sales\Queries\OverdueSales` : ventes `reserved` dont `planned_handover_date` est avant aujourd'hui, c'est-à-dire les lignes que la liste des ventes met en évidence (007 FR-023) |

- **Rationale** : FR-002 et SC-002 imposent la même définition. Réutiliser la requête de l'écran rend tout écart impossible. Pour les dégâts et les ventes, `ReservationsToReinvoice` regroupe en PHP et la liste des ventes filtre en ligne. Une requête de comptage dédiée dans le layer évite de charger des lignes et ne modifie pas l'écran existant.
- **Ventes en retard** : la liste des ventes n'a pas de filtre « en retard ». Le compteur renvoie vers la liste filtrée sur « réservée », où les ventes en retard sont mises en évidence ; le nombre est celui des lignes mises en évidence. Ajouter un filtre à l'écran des ventes sortirait du périmètre (spec, Out of Scope).
- **Alternatives considered** : recompter dans `app/` avec des requêtes écrites à la main, écarté car la définition serait dupliquée hors de son layer.

## R3. Agence d'une réservation et filtre

- **Decision** : l'agence affichée filtre par `machines.agency_id`, au moyen de `whereIn('machine_id', Machine::select('id')->where('agency_id', …))`. C'est le motif de `PendingDeposits` et de `MissingPurchaseOrders`.
- **Valeurs du paramètre `agence`** :
  - absent : agence du salarié ;
  - `toutes` : aucun filtre ;
  - identifiant d'une agence existante : cette agence ;
  - toute autre valeur : agence du salarié.
- **Rationale** : spec FR-005 ; c'est l'agence où la machine est retirée et rendue (001).
- **Alternatives considered** : l'agence qui a créé la réservation (`reservations.agency_id`, filtre de la liste des réservations), écartée par la spec.

## R4. Définitions des listes du jour

Toutes les dates se calculent avec `CarbonImmutable::today()` en `Europe/Paris`.

| Section | Statut | Dates | Tri |
|---|---|---|---|
| Départs du jour | `confirmed` | `start_date <= today` | `start_date`, puis référence machine |
| Départs à venir | `confirmed` | `start_date` entre `today + 1` et `today + 7` | `start_date`, puis référence ; regroupés par date à l'affichage |
| Retours du jour | `in_progress` | `end_date = today` | référence machine |
| Retours en retard | `in_progress` | `end_date < today` | `end_date` croissant (plus anciens d'abord) |
| Réservations en conflit | `confirmed` | `conflict_reason` non nul | `start_date` |

Précisions :
- Le nombre de jours de retard vaut `end_date->diffInDays(today)`.
- Le motif de conflit est affiché par `ConflictReason::label()`.
- Chaque section charge les 20 premières lignes (`config('dashboard.section_limit')`) et un comptage sans tri (`getCountForPagination()`).
- Le lien « voir tout » mène à `reservations.index` avec les filtres de statut et de période existants, au plus près de la section. C'est un raccourci : il n'affiche pas exactement la même liste.

## R5. État du parc et VGP à surveiller

- **Statuts** : une requête `select status, count(*) from machines where status <> 'retired' [and agency_id = ?] group by status`, qui renvoie un tableau `MachineStatus → nombre`, avec 0 pour un statut absent. Chaque chiffre renvoie vers `machines.index?agence=<id>&statut=<status>`, filtres existants de `MachineIndex`.
- **VGP à surveiller** :
  - Critères : `is_subject_to_vgp`, `status <> retired`, et `vgp_due_date is null or vgp_due_date <= today + 30`.
  - Tri : `vgp_due_date` avec les nulls en premier, puis la référence.
  - Affichage : 20 lignes au plus et le total. Chaque ligne renvoie vers `certification.machines.show` ; le lien « voir tout » vers `certification.machines?agence=<id>`.
  - Les mêmes champs que la règle VGP de 001 (`VgpCompliance`) sont utilisés.
- **Rationale** : spec FR-015 à FR-017 et clarification Q5. Le seuil de 30 jours est un paramètre d'affichage (`config('dashboard.vgp_watch_days')`) ; aucune règle de `fleet` ne change.

## R6. Fraîcheur

- **Decision** : `wire:poll.60s` sur chaque composant enfant, plus les écouteurs Echo existants.
  - `DayOperations`, `FleetStatus` et `VgpWatch` écoutent `echo-private:fleet,.reservation.changed` et `.machine.changed`.
  - `PendingWork` écoute, sur le canal `fleet`, `.certificate.changed`, `.deposit.changed`, `.damage.changed` et `.reservation.changed`. S'y ajoute `echo-private:sales,.sale.changed` si le salarié a la permission des ventes, faute de quoi la souscription serait refusée.
  - Les transmissions de `billing` n'émettent aucun événement : la mise à jour périodique les couvre en moins d'une minute (SC-004).
- **Rationale** : c'est le même mécanisme que `CertificationAlert` (écouteur et `wire:poll.60s`). Le jour qui change est rattrapé par la mise à jour périodique.

## R7. Contrôle d'accès

- **Decision** : chaque composant enfant appelle `$this->authorize()` (ou `Gate::authorize()`) dans `mount()` avec la permission de l'écran ciblé. La page n'insère l'enfant que sous `@can`. Pour `PendingWork`, chaque compteur est filtré par `Gate::allows()` avant d'être calculé : aucune requête pour un compteur masqué.

| Section | Permission |
|---|---|
| Opérations du jour | `BookingPermission::ManageReservations` |
| État du parc | `FleetPermission::ManageMachines` |
| VGP à surveiller | `CertificationPermission::Manage` |
| Compteurs | permission de chaque liste : `BillingPermission::Manage`, `CertificationPermission::Manage`, `DepositPermission::ManageDeposits`, `AccountsPermission::ManagePurchaseOrders`, `InspectionPermission::ManageDamages`, `SalesPermission::Manage` |

- **Rationale** : FR-018, Principe V. Un salarié sans aucune de ces permissions voit la page avec un état vide (`x-empty-state`).

## R8. Bandeaux existants

- **Decision** : inchangés (FR-003). Le layout continue d'afficher `billing.alert` et `certification.alert` sur toutes les pages, y compris l'accueil.
