# Feature Specification: Vente de machines d'occasion

**Feature Branch**: `007-vente-occasion`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — « Le logiciel intègrera aussi la Vente d'occasion. » S'appuie sur `001-reservation-machines` (parc à référence unique, statuts dont « retirée du parc », refus du retrait d'une machine ayant des réservations, historique) et `003-transmission-facturation` (transmission fiable au logiciel de facturation, dont la « Facturation de la vente d'occasion » était renvoyée à une spec séparée).

## Contexte

Vallet Location renouvelle régulièrement son parc d'environ 400 machines : les engins les plus anciens sont revendus d'occasion, à des clients, à des négociants ou à des particuliers. Aujourd'hui, ces ventes se suivent hors de l'outil : une machine peut être promise à un acheteur alors qu'une autre agence vient de la louer pour le mois suivant, deux agences peuvent négocier la même machine avec deux acheteurs, et la vente doit être ressaisie dans le logiciel de facturation.

Cette fonctionnalité met les ventes d'occasion dans le même outil que les locations : une machine mise en vente est visible de toutes les agences, une vente conclue fait sortir la machine du parc sans casser de location, et chaque vente arrive dans le logiciel de facturation sans ressaisie, avec la même garantie « rien ne se perd » que les locations.

## Clarifications

### Session 2026-10-10

- Q: Une machine en vente reste-t-elle louable ? → A: Oui, normalement, jusqu'à l'acceptation d'une offre ; ensuite, toute location dont la date de fin atteint ou dépasse la date de remise prévue est refusée (FR-005, FR-010).
- Q: Qui peut accepter une offre et conclure une vente ? → A: Tous les salariés, comme dans les features 001 à 003 ; l'auteur de chaque acceptation et de chaque remise est tracé dans l'historique (FR-012, FR-024).
- Q: Quand la vente est-elle transmise au logiciel de facturation ? → A: À la remise de la machine seulement (vente conclue), en une seule transmission ; un éventuel acompte est géré dans le logiciel de facturation (FR-019).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Mettre une machine en vente et la proposer depuis n'importe quelle agence (Priority: P1)

Un salarié met en vente une machine du parc en indiquant un prix demandé hors taxes et un descriptif (année, heures d'utilisation, état). La machine apparaît dans la liste des machines en vente, consultable et filtrable par catégorie et par agence depuis les 7 agences. Une machine ne peut avoir qu'une seule vente ouverte à la fois.

**Why this priority**: Sans annonce partagée, aucune vente ne peut être suivie dans l'outil, et deux agences continuent de proposer la même machine à deux acheteurs.

**Independent Test**: Mettre en vente une machine à 18 000 € HT depuis l'agence A ; un salarié de l'agence B la voit dans la liste des machines en vente avec son prix ; une seconde mise en vente de la même machine est refusée.

**Acceptance Scenarios**:

1. **Given** une machine du parc sans vente ouverte, **When** un salarié la met en vente à 18 000 € HT avec un descriptif, **Then** la vente est ouverte « en vente » et la machine apparaît dans la liste des machines en vente pour toutes les agences, avec son prix, son agence de rattachement et son statut au parc.
2. **Given** une machine déjà en vente, **When** un salarié d'une autre agence tente de la mettre en vente, **Then** l'outil refuse et indique la vente ouverte (prix, agence, date).
3. **Given** deux salariés qui mettent en vente la même machine au même instant, **When** les deux demandes arrivent, **Then** une seule vente est ouverte et l'autre salarié reçoit un refus explicite.
4. **Given** une machine en vente, **When** un salarié modifie le prix demandé, **Then** le nouveau prix est affiché partout et l'ancien prix reste dans l'historique de la vente.
5. **Given** une machine retirée du parc par une vente précédente conclue, **When** un salarié tente de la mettre en vente, **Then** l'outil refuse.
6. **Given** une machine en vente, **When** elle est consultée dans le parc ou dans le planning, **Then** elle porte la mention « en vente » et reste réservable en location selon les règles habituelles de la feature 001.

---

### User Story 2 - Enregistrer les offres et réserver la machine pour un acheteur (Priority: P1)

Un salarié enregistre chaque offre reçue : l'acheteur (un client existant ou un nouveau client), le montant proposé hors taxes et la date de l'offre. Plusieurs offres peuvent coexister sur une même vente. Quand une offre est acceptée, la vente passe « réservée » pour cet acheteur, avec une date de remise prévue ; les autres offres sont classées « refusées ». À partir de là, la machine ne peut plus être louée au-delà de la date de remise.

**Why this priority**: C'est le moment où une vente entre en conflit avec les locations. Sans réservation de la vente, une machine promise à un acheteur peut être louée par une autre agence pour la date de remise.

**Independent Test**: Sur une machine en vente, enregistrer deux offres, accepter celle de 16 500 € avec une remise prévue le 20 novembre : la vente est « réservée », l'autre offre est « refusée », et une réservation de location du 18 au 22 novembre est refusée.

**Acceptance Scenarios**:

1. **Given** une machine en vente, **When** un salarié enregistre une offre de 16 500 € HT d'un client existant, **Then** l'offre est rattachée à la vente avec l'acheteur, le montant, la date et l'auteur.
2. **Given** un acheteur absent du fichier client, **When** un salarié enregistre son offre, **Then** il crée l'acheteur comme nouveau client (nom et coordonnées) au même moment, sans quitter l'écran de la vente.
3. **Given** une vente avec deux offres, **When** un salarié accepte l'offre de 16 500 € avec une date de remise prévue au 20 novembre, **Then** la vente passe « réservée » pour cet acheteur au prix de 16 500 € HT, et l'autre offre passe « refusée ».
4. **Given** une machine ayant une location confirmée du 18 au 25 novembre, **When** un salarié tente d'accepter une offre avec une remise prévue au 20 novembre, **Then** l'outil refuse et indique la location en conflit (dates, agence, client).
5. **Given** une vente réservée avec une remise prévue au 20 novembre, **When** un salarié tente de réserver la machine en location du 18 au 22 novembre, **Then** l'outil refuse et indique la vente réservée et sa date de remise.
6. **Given** une vente réservée avec une remise prévue au 20 novembre, **When** un salarié réserve la machine en location du 10 au 14 novembre, **Then** la réservation est acceptée.
7. **Given** une vente réservée, **When** un salarié tente d'enregistrer ou d'accepter une nouvelle offre, **Then** l'outil refuse tant que la réservation de la vente n'est pas levée.
8. **Given** une offre de 12 000 € sur une machine affichée à 18 000 €, **When** un salarié l'accepte, **Then** l'acceptation est enregistrée comme pour toute autre offre, avec son auteur dans l'historique de la vente ; tous les salariés ont les mêmes droits.

---

### User Story 3 - Conclure la vente et faire sortir la machine du parc (Priority: P1)

Le jour de la remise, un salarié enregistre la vente comme conclue. La machine sort définitivement du parc (retirée, plus jamais réservable) et la vente passe « vendue » avec la date de remise réelle et le prix final. La vente conclue est transmise au logiciel de facturation pour que la facture soit émise à l'acheteur.

**Why this priority**: C'est l'aboutissement de la vente et le point où l'outil doit garantir la cohérence du parc : une machine vendue qui resterait au parc pourrait encore être louée.

**Independent Test**: Sur une vente réservée, enregistrer la remise : la vente est « vendue », la machine est retirée du parc et n'apparaît plus dans la recherche de disponibilité, et la vente est transmise au logiciel de facturation avec l'acheteur, la machine et le prix.

**Acceptance Scenarios**:

1. **Given** une vente réservée et la machine présente au parc, **When** un salarié enregistre la remise, **Then** la vente passe « vendue » avec la date de remise et le prix final, et la machine passe « retirée du parc ».
2. **Given** une vente réservée dont la machine est encore sortie en location, **When** un salarié tente d'enregistrer la remise, **Then** l'outil refuse tant que le retour de la location n'est pas enregistré.
3. **Given** une vente réservée dont la machine a une location confirmée non annulée, **When** un salarié tente d'enregistrer la remise, **Then** l'outil refuse et liste les réservations à annuler ou à déplacer.
4. **Given** une vente qui vient d'être conclue, **When** un salarié cherche une machine disponible de cette catégorie, **Then** la machine vendue n'apparaît plus et ne peut plus être réservée.
5. **Given** une vente conclue, **When** la remise est enregistrée, **Then** la vente est transmise au logiciel de facturation, en une seule fois, avec l'acheteur, la référence et la catégorie de la machine, l'agence, le prix hors taxes et la date de vente.
6. **Given** une vente conclue, **When** un salarié tente d'en modifier le prix ou l'acheteur, **Then** l'outil refuse et indique que toute correction se fait dans le logiciel de facturation (avoir).

---

### User Story 4 - Ne perdre aucune vente transmise (Priority: P1)

La transmission d'une vente suit les mêmes règles que celle des locations : si le logiciel de facturation est injoignable ou refuse la vente (acheteur inconnu), la vente reste conclue pour le salarié, la transmission est relancée automatiquement ou apparaît dans la liste des transmissions à traiter, et l'export de secours la reprend.

**Why this priority**: Une vente de machine représente plusieurs milliers d'euros ; une vente conclue mais jamais facturée est une perte bien plus lourde qu'une journée de location.

**Independent Test**: Rendre le logiciel de facturation indisponible, conclure une vente : la remise est enregistrée, la vente apparaît « en attente de transmission » ; rétablir le logiciel : elle part sans action et une seule facture est créée.

**Acceptance Scenarios**:

1. **Given** le logiciel de facturation injoignable, **When** un salarié enregistre la remise d'une vente, **Then** la remise est enregistrée normalement et la vente est marquée « en attente de transmission ».
2. **Given** une vente en attente suite à une indisponibilité, **When** le logiciel redevient joignable, **Then** la vente est transmise automatiquement, sans action d'un salarié.
3. **Given** une vente refusée par le logiciel de facturation (acheteur inconnu), **When** un salarié consulte la liste des transmissions à traiter, **Then** il y voit la vente, l'acheteur, la date de l'échec et le motif en clair, aux côtés des locations et des dégâts.
4. **Given** une vente déjà transmise, **When** une nouvelle tentative a lieu (relance, double clic, reprise après panne), **Then** le logiciel de facturation ne reçoit pas de doublon.
5. **Given** une vente en échec et l'envoi automatique indisponible, **When** un salarié produit l'export de secours, **Then** la vente y figure et passe « transmise par export ».

---

### User Story 5 - Annuler une vente et suivre l'historique des ventes (Priority: P2)

Une vente peut être annulée tant qu'elle n'est pas conclue (acheteur qui se désiste, machine finalement gardée). L'annulation lève les restrictions sur les locations ; la machine reste au parc. Un responsable consulte la liste des ventes filtrable par statut, agence et période, et chaque machine garde l'historique de ses mises en vente.

**Why this priority**: L'annulation et le suivi complètent le cycle ; les ventes peuvent se conclure sans eux au démarrage.

**Independent Test**: Annuler une vente réservée avec un motif : la machine redevient réservable au-delà de l'ancienne date de remise, et l'historique de la machine montre la vente annulée, ses offres et le motif.

**Acceptance Scenarios**:

1. **Given** une vente réservée avec une remise prévue au 20 novembre, **When** un salarié l'annule avec le motif « acheteur désisté », **Then** la vente passe « annulée », la machine reste au parc et peut de nouveau être réservée du 18 au 22 novembre.
2. **Given** une vente « en vente », **When** un salarié l'annule sans motif, **Then** l'outil refuse.
3. **Given** une vente « vendue », **When** un salarié tente de l'annuler, **Then** l'outil refuse et indique que toute correction se fait dans le logiciel de facturation (avoir).
4. **Given** une vente annulée, **When** un salarié remet la machine en vente, **Then** une nouvelle vente est ouverte ; l'ancienne reste consultable dans l'historique de la machine.
5. **Given** des ventes sur novembre, **When** un responsable affiche la liste des ventes de novembre pour l'agence de Rouen, **Then** il voit pour chacune la machine, le statut, le prix demandé, le prix final, l'acheteur et l'état de transmission, ainsi que le total des ventes conclues.

---

### Edge Cases

- **Machine en panne ou à l'atelier** : peut être mise en vente et vendue en l'état ; son statut au parc est affiché sur l'annonce.
- **Machine sortie en location le jour prévu de la remise** : la remise est refusée tant que le retour n'est pas enregistré ; la date de remise prévue peut être repoussée.
- **Date de remise prévue modifiée** : la nouvelle date est refusée si une location confirmée ou en cours la chevauche ou la dépasse ; avancée ou repoussée, elle s'applique aussitôt au blocage des locations.
- **Date de remise prévue dépassée sans remise** : la vente reste « réservée » et apparaît en évidence dans la liste des ventes ; la machine reste non louable au-delà de cette date jusqu'à la remise, au report de la date ou à l'annulation.
- **Retrait du parc manuel d'une machine ayant une vente ouverte** : refusé ; la machine sort du parc par la conclusion de la vente, ou la vente est annulée d'abord.
- **Machine déjà retirée du parc sans vente** (machine réformée, stockée) : peut être mise en vente ; la conclusion de la vente ne change pas son statut au parc.
- **Acheteur sans identifiant dans le logiciel de facturation** : la vente peut être conclue ; sa transmission échoue avec le motif « client inconnu » jusqu'à correction et relance (comme en 003).
- **Montant d'offre ou prix demandé nul ou négatif** : refusé.
- **Offre retirée par l'acheteur avant acceptation** : classée « retirée », elle reste dans l'historique.
- **Location à venir sur une machine en vente non réservée** : acceptée selon les règles habituelles de la feature 001 .
- **Deux salariés acceptent deux offres différentes au même instant** : une seule est acceptée ; l'autre reçoit un refus explicite.

## Requirements *(mandatory)*

### Functional Requirements

**Mise en vente**

- **FR-001**: Les salariés DOIVENT pouvoir mettre en vente une machine du parc, quel que soit son statut au parc, avec un prix demandé hors taxes strictement positif et un descriptif (année de mise en service, heures d'utilisation, état général, commentaire libre).
- **FR-002**: Une machine NE DOIT avoir qu'une seule vente ouverte (en vente ou réservée) à la fois, y compris en cas de demandes simultanées ; une machine dont une vente a été conclue NE DOIT plus pouvoir être mise en vente.
- **FR-003**: Le système DOIT présenter à toutes les agences la liste des machines en vente, filtrable par catégorie, agence de rattachement et statut de vente, avec le prix demandé, l'agence de rattachement et le statut au parc.
- **FR-004**: Les salariés DOIVENT pouvoir modifier le prix demandé et le descriptif d'une vente ouverte ; chaque changement de prix est conservé dans l'historique.
- **FR-005**: Une machine ayant une vente ouverte DOIT porter la mention « en vente » ou « vendue sous réserve » dans le parc et le planning. Une machine « en vente » reste réservable en location selon les règles de la feature 001 ; seules les ventes réservées restreignent les locations (FR-010).

**Offres et réservation de la vente**

- **FR-006**: Les salariés DOIVENT pouvoir enregistrer sur une vente « en vente » une ou plusieurs offres : acheteur, montant hors taxes strictement positif, date de l'offre.
- **FR-007**: L'acheteur DOIT être un client du fichier client de l'outil ; les salariés DOIVENT pouvoir le créer au moment de l'offre s'il n'existe pas.
- **FR-008**: Les salariés DOIVENT pouvoir accepter une offre en indiquant une date de remise prévue (aujourd'hui ou plus tard) ; la vente passe « réservée » pour cet acheteur au montant de l'offre, et les autres offres en cours passent « refusées ».
- **FR-009**: Le système DOIT refuser l'acceptation d'une offre, ou le report de la date de remise, si une location confirmée ou en cours de la machine se termine après la date de remise prévue, en indiquant la location en conflit.
- **FR-010**: Tant qu'une vente est réservée, le système DOIT refuser toute réservation de location de la machine dont la date de fin est postérieure ou égale à la date de remise prévue, en indiquant la vente réservée et sa date de remise.
- **FR-011**: Le système DOIT garantir qu'une seule offre peut être acceptée sur une vente, y compris en cas de validations simultanées.
- **FR-012**: Les salariés DOIVENT pouvoir classer une offre non acceptée « retirée » ou « refusée ».
- **FR-012a**: Tous les salariés DOIVENT avoir les mêmes droits sur les ventes (mise en vente, offres, acceptation, remise, annulation) ; aucune action de vente n'est réservée à un rôle dans cette version.

**Conclusion de la vente**

- **FR-013**: Les salariés DOIVENT pouvoir enregistrer la remise d'une vente réservée ; la vente passe « vendue » avec la date de remise réelle (aujourd'hui), le prix final (montant de l'offre acceptée) et l'auteur.
- **FR-014**: Le système DOIT refuser la remise si la machine est sortie en location ou a des réservations de location confirmées non annulées.
- **FR-015**: À la remise, le système DOIT retirer la machine du parc (statut « retirée du parc ») dans la même opération ; si la machine était déjà retirée, son statut est inchangé.
- **FR-016**: Le système DOIT refuser le retrait manuel du parc d'une machine ayant une vente ouverte.
- **FR-017**: Le prix, l'acheteur et la date d'une vente conclue NE DOIVENT plus être modifiables dans l'outil ; une vente conclue NE DOIT pas pouvoir être annulée.

**Annulation**

- **FR-018**: Les salariés DOIVENT pouvoir annuler une vente « en vente » ou « réservée » avec un motif obligatoire ; ses offres en cours passent « refusées » et toute restriction de location liée à la vente est levée.

**Transmission au logiciel de facturation**

- **FR-019**: Le système DOIT transmettre chaque vente conclue au logiciel de facturation, une seule fois, à l'enregistrement de la remise et jamais avant, sans action d'un salarié, avec : l'acheteur, la référence et la catégorie de la machine, l'agence de rattachement, l'agence et l'auteur de la vente, le prix hors taxes et la date de vente. Le logiciel de facturation applique la TVA et émet la facture.
- **FR-020**: La transmission d'une vente DOIT suivre les règles de la feature 003 : états (en attente, transmis, transmis par export, en échec), relance automatique, liste des transmissions à traiter, relance manuelle, export de secours, et jamais de doublon quel que soit le nombre de tentatives.
- **FR-021**: L'enregistrement de la remise NE DOIT PAS être bloqué ni ralenti par l'état du logiciel de facturation.

**Suivi et traçabilité**

- **FR-022**: Le système DOIT fournir une liste des ventes filtrable par statut, agence et période, avec pour chacune la machine, le prix demandé, le prix final, l'acheteur, la date de remise prévue ou réelle et l'état de transmission, ainsi que le total hors taxes des ventes conclues sur le filtre.
- **FR-023**: Le système DOIT mettre en évidence les ventes réservées dont la date de remise prévue est dépassée.
- **FR-024**: Le système DOIT enregistrer dans l'historique de la vente chaque mise en vente, changement de prix, offre, acceptation, refus, retrait d'offre, remise, annulation et tentative de transmission, avec l'auteur, l'agence et la date ; l'historique de la machine DOIT donner accès à toutes ses ventes, y compris annulées.
- **FR-025**: Toute ouverture, réservation, conclusion ou annulation de vente DOIT être visible par toutes les agences sans action manuelle de rafraîchissement.

### Key Entities

- **Vente** : la mise en vente d'une machine ; machine, prix demandé hors taxes, descriptif, statut (en vente, réservée, vendue, annulée), acheteur et prix final une fois réservée, date de remise prévue, date de remise réelle, motif d'annulation, agence et auteur de la mise en vente.
- **Offre** : une proposition d'achat sur une vente ; acheteur, montant hors taxes, date, statut (en cours, acceptée, refusée, retirée), auteur.
- **Acheteur** : un client du fichier client (feature 001), avec son identifiant dans le logiciel de facturation (feature 003).
- **Machine** (feature 001) : associée à ses ventes ; sort du parc par la conclusion d'une vente.
- **Transmission** (feature 003) : étendue à un troisième type d'élément facturable, la vente conclue.
- **Historique** : trace horodatée de chaque action sur une vente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Zéro machine vendue encore réservable ou louée après sa date de remise, sur les 12 premiers mois.
- **SC-002**: Zéro machine promise à deux acheteurs en même temps (une seule vente ouverte et une seule offre acceptée par machine).
- **SC-003**: Zéro location annulée ou déplacée à cause d'une vente conclue sans que le conflit ait été signalé avant l'acceptation de l'offre.
- **SC-004**: 100 % des ventes conclues sont transmises au logiciel de facturation (automatiquement ou par export) ou figurent dans la liste des transmissions à traiter ; aucune n'est absente des deux.
- **SC-005**: Zéro ressaisie manuelle d'une vente d'occasion dans le logiciel de facturation.
- **SC-006**: Un salarié de n'importe quelle agence trouve les machines en vente d'une catégorie donnée en moins de 30 secondes, et met une machine en vente en moins de 2 minutes.

## Out of Scope

- Publication des annonces hors de l'outil (site internet de Vallet Location, plateformes d'annonces) et photos d'annonce.
- Estimation automatique du prix de revente, valeur comptable, amortissement et plus-value.
- Émission de la facture, TVA, encaissement, acompte et relances de paiement : ils restent dans le logiciel de facturation.
- Reprise d'une machine d'un client en échange d'une vente, et achat de machines d'occasion pour le parc.
- Contrat de vente, certificat de cession et documents réglementaires (carte grise des engins immatriculés) : produits hors de l'outil.
- Vente de pièces détachées ou d'accessoires.
- Transferts de machines entre agences.

## Assumptions

- Les prix et montants sont saisis hors taxes en euros ; le logiciel de facturation applique la TVA (y compris un éventuel régime particulier des biens d'occasion) et reste la référence pour la facture.
- La date de remise est le jour où l'acheteur prend possession de la machine ; à partir de ce jour la machine n'appartient plus au parc. Une location qui se termine la veille de la remise est compatible.
- L'acheteur est enregistré dans le même fichier client que les locataires ; un acheteur professionnel ou particulier est traité de la même façon dans cette version.
- La transmission d'une vente réutilise le circuit de la feature 003 (liste des transmissions à traiter, alerte, export de secours) ; le logiciel de facturation sait recevoir une ligne de vente rattachée à un client.
- Une machine retirée du parc sans vente (réformée) peut être vendue ; une machine vendue ne revient jamais au parc (une reprise éventuelle serait une nouvelle machine).
- Les ventes d'occasion sont peu nombreuses (quelques dizaines par an) au regard des locations.
- Le descriptif de l'annonce est saisi par le salarié ; aucune donnée d'heures d'utilisation n'est collectée automatiquement.
