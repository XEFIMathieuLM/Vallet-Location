# Contrat : écrans de l'espace client et écrans salariés de la 009

Textes en français dans `functional/portal/resources/lang/fr`. Composants Livewire du layer `portal`, enregistrés par `PortalServiceProvider`.

## Espace client — routes `/espace-client`, nommées `portal.*`, layout `portal::layouts.portal`

### Visiteur (`guest:customer`)

| Route | Nom | Composant | Rôle |
|---|---|---|---|
| `GET /espace-client/inscription` | `portal.register` | `Register` | nom ou raison sociale, e-mail, téléphone, type (radio particulier ou professionnel, sans valeur par défaut), mot de passe et confirmation (FR-001) |
| `GET /espace-client/connexion` | `portal.login` | `Login` | e-mail, mot de passe, « se souvenir de moi » ; limité à 5 tentatives par minute (FR-004) |
| `GET /espace-client/mot-de-passe-oublie` | `portal.password.request` | `ForgotPassword` | même réponse que l'adresse existe ou non |
| `GET /espace-client/reinitialisation/{token}` | `portal.password.reset` | `ResetPassword` | |

- Après l'inscription : le compte est connecté et redirigé vers `portal.verification.notice`.
- Après la connexion : redirection vers `portal.search`.

### Connecté, adresse non confirmée (`auth:customer`)

| Route | Nom | Rôle |
|---|---|---|
| `GET /espace-client/confirmer-adresse` | `portal.verification.notice` | page « confirmez votre adresse » et bouton « renvoyer le lien » (limité à 6 par minute) |
| `GET /espace-client/confirmer-adresse/{id}/{hash}` | `portal.verification.verify` | route signée, valable 60 minutes ; pose `email_verified_at` puis redirige vers `portal.search` |
| `POST /espace-client/deconnexion` | `portal.logout` | |

### Connecté et confirmé (`auth:customer`, `verified:portal.verification.notice`)

| Route | Nom | Composant | Contenu |
|---|---|---|---|
| `GET /espace-client` | `portal.search` | `Search` | voir ci-dessous |
| `GET /espace-client/demandes` | `portal.requests` | `MyRequests` | demandes du compte, les plus récentes en premier |
| `GET /espace-client/reservations` | `portal.reservations` | `MyReservations` | réservations de la fiche rattachée et leurs documents |
| `GET /espace-client/reservations/{reservation}/attestation-vgp` | `portal.reservations.certificate` | contrôleur `CustomerCertificateDownloadController` | rapport VGP du dernier envoi ou de la remise (R13) ; 404 si la réservation n'est pas de la fiche du compte |
| `GET /espace-client/compte` | `portal.account` | `AccountSettings` | nom, téléphone, mot de passe (FR-004) |

#### `Search`

- Filtres :
  - `flux:select` des catégories ;
  - `flux:select` des 7 agences ;
  - dates de début et de fin (`flux:date-picker`, mode plage) ;
  - les trois sont obligatoires.
- Résultats : `AvailableMachinesQuery` projetée en `PortalMachineOffer`. Chaque carte affiche :
  - la référence, la catégorie et l'agence ;
  - « à partir de 95,00 € HT / jour » ou « prix sur demande » ;
  - la mention fixe « Prix indicatif ; le prix facturé est établi par votre agence. » (FR-032).
- Résultat vide : `x-empty-state` « Aucune machine disponible pour ces critères ».
- Bouton « Demander cette machine » : ouvre `SendRequestForm` dans un `flux:modal`. Il récapitule la machine, les dates et le prix indicatif, propose un commentaire facultatif (500 caractères) et rappelle que la demande doit être confirmée par l'agence.
- Refus à l'envoi (`DisplaysRefusals`) :
  - machine devenue indisponible ;
  - dates incohérentes ;
  - doublon en attente ;
  - limite de 10 demandes atteinte.

#### `MyRequests`

- Une ligne par demande :
  - machine, dates, date d'envoi ;
  - badge d'état : en attente (zinc), confirmée (green), refusée (red), annulée (zinc), expirée (amber) ;
  - le motif si la demande est refusée ;
  - si elle est confirmée, la machine réservée et un lien vers `portal.reservations`.
- Bouton « Annuler la demande » sur les demandes en attente seulement, avec une confirmation `flux:modal` (FR-026).

#### `MyReservations`

- Compte non rattaché : `x-empty-state` « Vos réservations apparaîtront ici dès que l'agence aura confirmé votre première demande. » (FR-008).
- Compte rattaché : réservations de la fiche, à venir puis passées. Chaque ligne affiche :
  - la machine, les dates, l'agence de retrait (agence de rattachement de la machine) et l'état client (data-model) ;
  - selon le cas : un lien « Télécharger l'attestation VGP », « Attestation pas encore disponible », ou rien (machine non soumise à VGP, ou réservation annulée) ;
  - pour une réservation confirmée : « Pour modifier ou annuler, contactez l'agence de … » (FR-026).
- Pagination par 20.

## Écrans salariés — middleware `auth`, `verified`, `can:<permission>`

| Route | Nom | Composant | Permission |
|---|---|---|---|
| `GET /demandes-en-ligne` | `portal.staff.requests` | `OnlineRequests` | `portal.handle-requests` |
| `GET /prix-indicatifs` | `portal.staff.prices` | `IndicativePrices` | `portal.manage-prices` |

### `OnlineRequests`

- Filtre « Agence » (`flux:select`, toutes par défaut) sur l'agence de rattachement de la machine demandée. Tri par date de début croissante. Pagination par 20.
- Une ligne par demande en attente :
  - le client déclaré (nom, badge « Particulier » ou « Professionnel », e-mail, téléphone) ;
  - le rattachement : nom de la fiche, ou badge « Compte non rattaché » ;
  - la machine (référence, catégorie, agence), les dates, le commentaire, le prix indicatif affiché au client et la date d'envoi.
- Actions par ligne :
  - **Confirmer** : ouvre `ConfirmRequestModal`.
    - **Machine** : `flux:select` des machines de la même catégorie disponibles aux mêmes dates, toutes agences, la machine demandée en tête et présélectionnée si elle est disponible (FR-018).
    - **Fiche client**, si le compte n'est pas rattaché (FR-005, FR-006) :
      - radio « Fiche existante » avec les fiches proposées (même e-mail ou même téléphone, au plus 10) et une recherche par nom, e-mail ou téléphone ;
      - radio « Créer la fiche » avec le récapitulatif des informations déclarées.
    - Si le compte est déjà rattaché, la fiche est affichée sans choix possible.
    - Un refus de la 001 s'affiche dans la fenêtre et la demande reste en attente.
  - **Refuser** : `flux:modal` avec un motif obligatoire (500 caractères).
- Mise à jour : `echo-private:portal-requests,.reservation-request.changed` et `echo-private:fleet,.reservation.changed` (la disponibilité change). `wire:poll.60s` en secours.
- Liste vide : `x-empty-state` « Aucune demande en attente ».

### `IndicativePrices`

- Tableau des catégories : nom, prix actuel (« 95,00 € HT / jour » ou « — »), auteur et date de la dernière modification.
- Actions :
  - « Modifier » : champ montant en euros, format `^\d+([.,]\d{1,2})?$`, strictement positif (FR-031) ;
  - « Retirer le prix ».

### Section « Demande en ligne » du détail de réservation

- Composant `portal.reservation-origin-section`, enregistré dans `ReservationDetailSections` en position 5, **sans étape gardée** (FR-022).
- Si la réservation provient d'une demande : « Réservation issue d'une demande en ligne du jj/mm/aaaa », le compte (nom, e-mail), le commentaire du client, et la machine demandée si elle diffère de la machine réservée.
- Sinon la section ne rend rien. Elle n'émet jamais d'événement de disponibilité d'étape.

### Navigation (`resources/views/layouts/app/sidebar.blade.php`)

- Groupe « Espace client », sous `@canany` des deux permissions :
  - « Demandes en ligne » avec `<livewire:portal.pending-requests-badge />` (badge `amber` si au moins une demande, rien si zéro ; mis à jour par `portal-requests`) (FR-016) ;
  - « Prix indicatifs ».
