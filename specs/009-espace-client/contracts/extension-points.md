# Contrat : ce que `portal` consomme, et ce qu'il change hors de son layer

## Dépendances du layer

`functional/portal/composer.json` requiert `functional/booking`, `functional/fleet` et `functional/certification`. Graphe : `portal → certification → booking → fleet`. Aucun layer ne requiert `portal`. Un test `LayerBoundaryTest` vérifie qu'aucun fichier de `functional/*/src` hors de `portal` n'importe `Functional\Portal`.

## Consommé, sans modification

| Élément | Layer | Usage dans `portal` |
|---|---|---|
| `Actions\CreateReservation::handle(AgencyMember, Machine, Customer\|NewCustomer, CarbonImmutable, CarbonImmutable)` | booking | seule écriture d'une réservation, à la confirmation (R6) |
| `Data\NewCustomer` | booking | création de la fiche depuis le compte |
| `Queries\AvailableMachinesQuery::get()` | booking | recherche client, vérification à l'envoi, choix de la machine de remplacement (R7) |
| `Models\Reservation`, `Models\Customer`, `Enums\ReservationStatus`, `Enums\CustomerType` | booking | lecture |
| `Extensions\ReservationDetailSections::register(name, position)` sans étape | booking | section « Demande en ligne » (R11) |
| `Events\ReservationChanged` (diffusé sur `fleet`) | booking | rafraîchissement de la liste des demandes |
| `Models\Machine`, `MachineCategory`, `Agency` ; `Access\Perimeters\GlobalPerimeter` ; `Exceptions\RefusalException` ; `Livewire\Concerns\DisplaysRefusals` ; `Contracts\AgencyMember` | fleet | lecture, contrôles d'accès, refus typés |
| `Models\ReservationCertificate`, `CertificateDispatch`, `VgpReport` ; `Enums\DispatchOutcome`, `DispatchChannel` ; disque `vgp-reports` | certification | document téléchargeable, en lecture seule (R13) |

## Modifié hors du layer (colle `app/` et enregistrement du layer)

| Fichier | Modification |
|---|---|
| `config/auth.php` | guard `customer` (session, provider `customer_accounts`) ; provider `customer_accounts` (eloquent, `CustomerAccount`) ; broker `customer_accounts` (table `customer_password_reset_tokens`) |
| `bootstrap/app.php` | `redirectGuestsTo` et `redirectUsersTo` aiguillés : routes `portal.*` vers `portal.login` et `portal.search`, sinon comportement actuel (`login`, `dashboard`) |
| `resources/views/layouts/app/sidebar.blade.php` | groupe « Espace client » (2 entrées + badge) sous `@canany` |
| `composer.json`, `phpunit.xml`, `phpstan.neon` | enregistrement du layer, de ses tests et de ses chemins |
| `database/seeders/DatabaseSeeder.php` | `PortalPermissionSeeder` avant `PermissionSeeder` |
| `tests/TestCase.php` | `PortalPermissionSeeder` ajouté à `seedPermissions()` |

Le comportement des écrans salariés ne change pas (FR-036). Les tests existants (`tests/Feature/Auth/*`, `NavigationTest`) restent verts et vérifient la non-régression de l'authentification des salariés.

## Exposé par `portal`

| Élément | Destinataire |
|---|---|
| Canal privé `portal-requests` (`routes/channels.php` du layer), autorisé par `portal.handle-requests` ; événement `ReservationRequestChanged` (`reservation-request.changed`, charge utile `{id, status, machine_id, start_date}`) | écrans salariés de `portal` |
| Composant `portal.pending-requests-badge` | barre latérale de `app/` |
| Commande `portal:reconcile` (planifiée toutes les 5 minutes, `withoutOverlapping()`) | planificateur |
