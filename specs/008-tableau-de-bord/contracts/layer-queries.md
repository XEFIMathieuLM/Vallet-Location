# Contrat : requêtes de layer en lecture seule

Cinq classes `final` ajoutées, chacune dans le layer qui possède les données. Aucune ne modifie un fichier existant, ne déclare de relation nouvelle ni n'écrit en base. Chaque méthode renvoie un `Builder` non exécuté, sauf `FleetStatusCounts`, qui renvoie le résultat de sa requête groupée.

Paramètres communs :
- `?int $homeAgencyId` : `null` signifie toutes les agences ; sinon, filtre sur `machines.agency_id` au moyen d'une sous-requête `whereIn('machine_id', Machine::query()->select('id')->where('agency_id', $homeAgencyId))`.
- `CarbonImmutable $today` : la date du jour en `Europe/Paris`, passée par l'appelant pour permettre `travelTo()`.

## booking — `Functional\Booking\Queries\DayOperations`

| Méthode | Renvoie `Builder<Reservation>` | Filtre | Tri |
|---|---|---|---|
| `departures(?int $homeAgencyId, CarbonImmutable $today)` | départs du jour | `status = confirmed`, `start_date <= today` | `start_date`, `id` |
| `upcomingDepartures(?int $homeAgencyId, CarbonImmutable $today, int $days)` | départs à venir | `status = confirmed`, `start_date` entre `today + 1` et `today + days` | `start_date`, `id` |
| `returns(?int $homeAgencyId, CarbonImmutable $today)` | retours du jour | `status = in_progress`, `end_date = today` | `id` |
| `lateReturns(?int $homeAgencyId, CarbonImmutable $today)` | retours en retard | `status = in_progress`, `end_date < today` | `end_date`, `id` |
| `conflicts(?int $homeAgencyId)` | réservations en conflit | `status = confirmed`, `conflict_reason` non nul | `start_date`, `id` |

Chaque `Builder` charge par anticipation `machine.category`, `machine.agency` et `customer`.

## fleet — `Functional\Fleet\Queries\FleetStatusCounts`

`count(?int $agencyId): array<string, int>` : une requête `select status, count(*) … where status <> 'retired' group by status`. Le tableau renvoyé contient les clés `available`, `rented_out`, `workshop` et `out_of_order`, à 0 quand un statut est absent.

## fleet — `Functional\Fleet\Queries\VgpWatchList`

`query(?int $agencyId, CarbonImmutable $until): Builder<Machine>` : `is_subject_to_vgp = true`, `status <> retired`, `vgp_due_date is null or vgp_due_date <= until`. Tri `vgp_due_date asc nulls first`, puis `reference`. Chargement anticipé de `category`.

## inspection — `Functional\Inspection\Queries\DamagesToHandle`

`query(): Builder<Damage>` : `resolved_at is null`. C'est le filtre de `ReservationsToReinvoice`, dont le nombre de dégâts est celui que liste l'écran `inspection.damages`.

## sales — `Functional\Sales\Queries\OverdueSales`

`query(CarbonImmutable $today): Builder<Sale>` : `status = reserved`, `planned_handover_date < today`. Ce sont les ventes que la liste des ventes met en évidence (007 FR-023).

## Requêtes existantes réutilisées telles quelles

`Billing\Queries\TransmissionsToHandle::query()`, `Certification\Queries\CertificatesToHandle::query()`, `Deposit\Queries\PendingDeposits::query()`, `Accounts\Queries\MissingPurchaseOrders::query()` : appel de `->count()` sans argument, donc sur tout le réseau (FR-014).
