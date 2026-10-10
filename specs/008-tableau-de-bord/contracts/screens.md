# Contrat : écran « Tableau de bord »

Route `GET /dashboard`, nommée `dashboard` (inchangée), middleware `auth` et `verified`. Composant `App\Livewire\Dashboard\Dashboard`. C'est la page de destination de Fortify après la connexion (`fortify.home`, inchangé).

## Page `Dashboard`

- `x-page-heading` « Tableau de bord ». Dans la zone d'actions, un `flux:select` « Agence » liste les 7 agences par nom, puis « Toutes les agences ».
- Propriété `#[Url(as: 'agence')] public string $agency = ''`, résolue selon [data-model.md](../data-model.md#agence-sélectionnée).
- Composants enfants, chacun inséré sous `@can` de sa permission et avec une `wire:key` qui dépend de l'agence :
  1. `<livewire:dashboard.day-operations :agency-id="$agencyId" />`
  2. `<livewire:dashboard.pending-work />`
  3. `<livewire:dashboard.fleet-status :agency-id="$agencyId" />`
  4. `<livewire:dashboard.vgp-watch :agency-id="$agencyId" />`
- Si le salarié n'a aucune des permissions, la page affiche `x-empty-state` « Aucune information disponible ».
- Les bandeaux du layout (transmissions et attestations) restent affichés au-dessus.

## `DayOperations` (permission `reservations.manage`)

- Propriété `#[Reactive] public ?int $agencyId`.
- Écoute `echo-private:fleet,.reservation.changed` et `.machine.changed`, plus `wire:poll.60s`.
- Contient cinq sous-sections `x-section-heading level=3`, dans cet ordre :
  1. Départs du jour : lignes de départ ; « départ prévu le jj/mm » quand la date de début est passée.
  2. Départs à venir (7 jours) : lignes groupées sous un intitulé de date (« lundi 12 octobre »).
  3. Retours du jour.
  4. Retours en retard : « N jours de retard » sur chaque ligne.
  5. Réservations en conflit : motif du conflit sur chaque ligne.
- Chaque ligne (partiel `reservation-row`) est un lien `wire:navigate` vers `reservations.show`. Elle affiche la référence de la machine, sa catégorie, le client et les dates `jj/mm/aaaa → jj/mm/aaaa`. En vue « Toutes les agences », elle affiche aussi l'agence de rattachement de la machine.
- Une section vide affiche un texte neutre (« Aucun départ prévu aujourd'hui », etc.).
- Une section de plus de 20 éléments affiche « 20 sur N » et un lien « Voir toutes les réservations » vers `reservations.index`.

## `PendingWork` (aucune permission propre ; chaque compteur la sienne)

- Grille de compteurs (`flux:card`) : intitulé, nombre, lien vers l'écran.
- Un nombre non nul est en évidence (badge `amber`) ; 0 est affiché en texte atténué.
- Les compteurs ne dépendent pas de l'agence choisie.
- Écoute, sur le canal `fleet`, `.certificate.changed`, `.deposit.changed`, `.damage.changed` et `.reservation.changed`. Écoute `echo-private:sales,.sale.changed` seulement si `sales.manage`. S'y ajoute `wire:poll.60s`.

| Ordre | Intitulé | Lien |
|---|---|---|
| 1 | Transmissions à traiter | `billing.transmissions` |
| 2 | Attestations VGP à traiter | `certification.certificates` |
| 3 | Cautions en attente | `deposit.pending.index` |
| 4 | Bons de commande manquants | `accounts.missing-purchase-orders` |
| 5 | Dégâts à traiter | `inspection.damages` |
| 6 | Ventes en retard | `sales.index?statut=reserved` |

## `FleetStatus` (permission `machines.manage`)

- Propriété `#[Reactive] public ?int $agencyId`.
- Écoute `.machine.changed`, `.reservation.changed` et `.fleet.imported` sur le canal `fleet`, plus `wire:poll.60s`.
- Quatre chiffres : Disponibles, Sorties, Atelier, En panne. Chacun est un lien vers `machines.index` avec `statut=<status>` et, si une agence est choisie, `agence=<id>`.

## `VgpWatch` (permission `certification.manage`)

- Propriété `#[Reactive] public ?int $agencyId`.
- Écoute `.machine.changed` sur le canal `fleet`, plus `wire:poll.60s`.
- Chaque ligne est un lien vers `certification.machines.show`. Elle affiche la référence, la catégorie, le statut au parc (badge `MachineStatus::color()`) et l'échéance :
  - « Expirée le jj/mm/aaaa » en rouge ;
  - « Non renseignée » en rouge ;
  - « Expire le jj/mm/aaaa (dans N jours) » en orange.
- Sans machine, la section affiche « Aucune VGP à surveiller ».
- Au-delà de 20 lignes, un lien mène à `certification.machines` avec `agence=<id>`.
