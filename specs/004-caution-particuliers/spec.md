# Feature Specification: Caution des particuliers

**Feature Branch**: `004-caution-particuliers`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — règle « La caution d'un particulier est encaissée avant le départ de la machine ». Acteurs : les salariés d'agence ; le client particulier ne se connecte pas à l'outil. S'appuie sur `001-reservation-machines` (clients, réservations, sortie, retour, annulation), `002-photos-qr-code` (dégâts signalés au retour) et `003-transmission-facturation` (dégâts refacturés ou classés non refacturés).

## Contexte

Vallet Location a perdu 85 000 € l'an dernier en réparations non refacturées. Les features 002 et 003 prouvent le dégât et le transmettent à la facturation ; mais face à un particulier, une facture de réparation émise après coup est souvent impayée. La caution est la seule garantie dont l'entreprise dispose avant que la machine ne quitte l'agence.

Aujourd'hui, rien n'empêche une machine de partir chez un particulier sans caution, et rien ne dit, au retour, si la caution doit être rendue ou conservée. Cette fonctionnalité distingue les clients particuliers des professionnels, bloque la sortie tant que la caution d'un particulier n'est pas encaissée, et suit chaque caution jusqu'à sa restitution ou sa retenue, en lien avec les dégâts constatés au retour.

## Clarifications

### Session 2026-10-10

- Q: Le montant de la caution est-il un forfait unique, un montant par catégorie de machine, ou saisi au cas par cas ? → A: Un montant par catégorie de machine, avec un montant par défaut, paramétrés sur un écran dédié comme les vues photo de la feature 002 (US6, FR-017).
- Q: L'outil enregistre-t-il un encaissement fait hors outil ou pilote-t-il une pré-autorisation bancaire ? → A: Il enregistre un encaissement fait hors outil (moyen et référence) ; la pré-autorisation bancaire fera l'objet d'une spec ultérieure (FR-006, Out of Scope).
- Q: Quand les dégâts d'une réservation sont réglés, combien l'outil propose-t-il de retenir sur la caution ? → A: Le total hors taxes des dégâts refacturés, plafonné au montant de la caution ; le reste est restitué ; le salarié valide ce solde sans pouvoir le modifier. La TVA et le solde éventuel au-delà de la caution sont réclamés par la facture du dégât émise par le logiciel de facturation, qui rapproche la caution retenue (FR-013, FR-014).
- Q: Que faut-il pour qu'un salarié puisse restituer la caution d'une réservation clôturée sans dégât ? → A: Au moment de restituer, le salarié confirme « comparaison départ / retour faite, aucun dégât constaté » ; cette confirmation est inscrite dans l'historique avec son auteur et sa date (FR-012).
- Q: Que fait-on d'un client sans type au moment de la sortie ? → A: La sortie est refusée jusqu'à ce qu'un salarié le qualifie particulier ou professionnel (FR-005).
- Q: La caution est-elle transmise au logiciel de facturation ? → A: Non ; le rapprochement entre la caution retenue et la facture du dégât se fait dans le logiciel de facturation (Assumptions).
- Q: Peut-on corriger un encaissement après la sortie ? → A: Oui, le moyen et la référence, jusqu'à la restitution ou au solde, avec un motif obligatoire ; l'historique conserve l'ancienne et la nouvelle valeur (FR-010).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Bloquer la sortie tant que la caution d'un particulier n'est pas encaissée (Priority: P1)

Le jour du départ, le salarié ouvre la réservation d'un client particulier. Le détail de la réservation affiche une section « Caution » avec le montant attendu et l'état « à encaisser ». Le salarié encaisse la caution auprès du client, puis l'enregistre dans l'outil (moyen, référence). La section passe « encaissée » et la sortie peut être enregistrée. Tant qu'elle n'est pas encaissée, la sortie est refusée.

**Why this priority**: C'est la règle de la fiche. Sans ce blocage, la caution reste une bonne intention et la machine part sans garantie.

**Independent Test**: Sur une réservation confirmée d'un client particulier, tenter la sortie : refus « caution non encaissée ». Enregistrer l'encaissement de la caution, retenter : la sortie est acceptée (sous réserve des règles des features 001 et 002).

**Acceptance Scenarios**:

1. **Given** une réservation confirmée d'un client particulier, **When** le salarié ouvre son détail, **Then** une section « Caution » affiche le montant attendu et l'état « à encaisser ».
2. **Given** une réservation confirmée d'un client particulier sans caution encaissée, **When** le salarié tente d'enregistrer la sortie, **Then** l'outil refuse en indiquant que la caution doit être encaissée et son montant.
3. **Given** la même réservation, **When** le salarié enregistre l'encaissement de la caution (moyen et référence), **Then** la caution passe « encaissée » avec le montant, le moyen, la référence, l'auteur, l'agence et la date, et l'étape « départ » est signalée prête du point de vue de la caution.
4. **Given** une caution encaissée, **When** le salarié enregistre la sortie, **Then** la sortie est acceptée si les autres règles (VGP, disponibilité, photos de départ) sont respectées.
5. **Given** une réservation confirmée d'un client professionnel, **When** le salarié enregistre la sortie, **Then** aucune caution n'est exigée et la section « Caution » indique « non requise (client professionnel) ».
6. **Given** deux salariés qui enregistrent au même instant l'encaissement de la caution d'une même réservation, **When** les deux validations arrivent, **Then** une seule caution est enregistrée et l'autre salarié reçoit un refus explicite.

---

### User Story 2 - Qualifier chaque client comme particulier ou professionnel (Priority: P1)

À la création d'un client, le salarié indique s'il s'agit d'un particulier ou d'un professionnel. Les clients déjà saisis avant la mise en service n'ont pas de type ; le salarié le renseigne au plus tard au moment de la sortie.

**Why this priority**: La règle de la caution dépend du type de client. Sans qualification fiable, un particulier saisi comme « client » sans précision partirait sans caution.

**Independent Test**: Créer un client sans choisir de type : refus. Ouvrir une réservation d'un client existant sans type : la section « Caution » demande de qualifier le client, et la sortie est refusée tant qu'il ne l'est pas.

**Acceptance Scenarios**:

1. **Given** le formulaire de création de client, **When** le salarié l'enregistre sans indiquer « particulier » ou « professionnel », **Then** l'outil refuse et demande le type.
2. **Given** un client existant sans type, **When** le salarié ouvre une de ses réservations confirmées, **Then** la section « Caution » indique « type de client à renseigner » et permet de le renseigner.
3. **Given** un client existant sans type, **When** le salarié tente d'enregistrer la sortie d'une de ses réservations, **Then** l'outil refuse en indiquant que le type de client doit être renseigné.
4. **Given** un client existant sans type, **When** le salarié le qualifie « particulier », **Then** ses réservations confirmées exigent une caution avant la sortie.
5. **Given** un client particulier dont une réservation a une caution encaissée, **When** un salarié le requalifie « professionnel », **Then** la caution déjà encaissée reste suivie jusqu'à sa restitution.

---

### User Story 3 - Restituer ou retenir la caution au retour (Priority: P1)

Au retour de la machine, la caution n'est pas rendue automatiquement : l'outil indique si elle peut être restituée ou si elle doit être conservée. Sans dégât à traiter, le salarié restitue la caution et l'enregistre. Si un dégât a été signalé (feature 002), la caution reste bloquée jusqu'au règlement de chaque dégât (feature 003) : le montant des dégâts refacturés est retenu sur la caution, dans la limite de son montant, et le reste est restitué.

**Why this priority**: C'est ce qui rend la caution utile contre les 85 000 € perdus : une caution rendue avant la constatation d'un dégât ne garantit plus rien.

**Independent Test**: Clôturer une réservation sans dégât : la caution passe « à restituer » ; enregistrer la restitution : elle passe « restituée ». Sur une autre réservation, signaler un dégât puis le refacturer 450 € sur une caution de 1 500 € : l'outil propose une retenue de 450 € et une restitution de 1 050 €.

**Acceptance Scenarios**:

1. **Given** une réservation clôturée sans dégât à traiter et une caution encaissée, **When** le salarié consulte la réservation, **Then** la caution est « à restituer ».
2. **Given** une caution « à restituer » d'une réservation clôturée, **When** le salarié enregistre sa restitution en confirmant « comparaison départ / retour faite, aucun dégât constaté », **Then** la caution passe « restituée » avec l'auteur, l'agence et la date, et la confirmation est inscrite dans l'historique.
3. **Given** une réservation clôturée portant au moins un dégât à traiter, **When** le salarié tente de restituer la caution, **Then** l'outil refuse et liste les dégâts à régler d'abord.
4. **Given** une caution de 1 500 € et un dégât refacturé 450 € (feature 003), **When** tous les dégâts de la réservation sont réglés, **Then** la caution passe « à solder » avec une retenue proposée de 450 € et une restitution de 1 050 €.
5. **Given** une caution « à solder », **When** le salarié valide le solde, **Then** la caution passe « soldée » avec le montant retenu et le montant restitué calculés par l'outil, l'auteur et la date ; le salarié ne peut pas modifier ces montants.
6. **Given** une caution de 1 500 € et des dégâts refacturés pour 2 000 €, **When** les dégâts sont réglés, **Then** la retenue proposée est de 1 500 € et la restitution de 0 € ; les 500 € restants et la TVA sont réclamés au client par la facture du dégât émise par le logiciel de facturation (feature 003).
7. **Given** une caution dont le seul dégât est classé « non refacturé », **When** le dégât est réglé, **Then** la caution passe « à restituer » en entier.
8. **Given** une caution « à restituer » d'une réservation clôturée, **When** le salarié tente d'enregistrer la restitution sans cocher la confirmation, **Then** l'outil refuse.

---

### User Story 4 - Rendre la caution d'une réservation annulée (Priority: P2)

Une caution peut être encaissée avant le jour du départ. Si la réservation est annulée avant la sortie, la caution doit être rendue au client.

**Why this priority**: Cas moins fréquent que le départ et le retour, mais une caution oubliée sur une réservation annulée est un litige client assuré.

**Independent Test**: Encaisser la caution d'une réservation confirmée, annuler la réservation : la caution passe « à restituer » ; enregistrer la restitution.

**Acceptance Scenarios**:

1. **Given** une réservation confirmée avec une caution encaissée, **When** le salarié annule la réservation, **Then** l'annulation est acceptée et la caution passe « à restituer ».
2. **Given** une réservation confirmée sans caution encaissée, **When** le salarié l'annule, **Then** aucune caution n'est à restituer.

---

### User Story 5 - Suivre les cautions à restituer depuis toutes les agences (Priority: P2)

Un salarié consulte la liste des cautions qui attendent une action : à restituer, à solder, ou bloquées par un dégât à traiter. Il filtre par agence et voit depuis combien de temps chaque caution attend.

**Why this priority**: La restitution fonctionne sans cette liste depuis la réservation ; la liste évite qu'une caution reste oubliée et permet de répondre à un client qui réclame la sienne.

**Independent Test**: Avec 3 cautions (une à restituer depuis 10 jours, une à solder, une bloquée par un dégât), la liste les affiche toutes les trois avec leur état et leur ancienneté, et met en évidence celle qui attend depuis plus de 7 jours.

**Acceptance Scenarios**:

1. **Given** des cautions à restituer, à solder et bloquées dans plusieurs agences, **When** un salarié ouvre la liste, **Then** il voit pour chacune la réservation, le client, l'agence, le montant, l'état et la date depuis laquelle elle attend.
2. **Given** une caution à restituer depuis plus de 7 jours, **When** un salarié consulte la liste, **Then** elle est mise en évidence comme en retard.

---

### User Story 6 - Paramétrer le montant de la caution (Priority: P3)

Un salarié définit un montant de caution par défaut, et peut fixer pour chaque catégorie de machine un montant propre (par exemple 3 000 € pour une nacelle, 1 500 € pour une mini-pelle). Le montant exigé d'une réservation est celui de la catégorie de sa machine, ou le montant par défaut si la catégorie n'en a pas. Le paramétrage se fait sur un écran dédié, comme les vues photo par catégorie de la feature 002.

**Why this priority**: Un montant par défaut suffit pour démarrer ; l'ajustement par catégorie vient ensuite.

**Independent Test**: Fixer 3 000 € pour la catégorie « nacelle » : la caution d'une réservation de nacelle exige 3 000 €, celle d'une mini-pelle sans montant propre exige le montant par défaut ; une caution déjà encaissée garde son montant.

**Acceptance Scenarios**:

1. **Given** une catégorie sans montant propre, **When** le salarié ouvre la section « Caution » d'une réservation d'une machine de cette catégorie, **Then** le montant attendu est le montant par défaut.
2. **Given** la catégorie « nacelle » à 3 000 €, **When** le salarié ouvre la section « Caution » d'une réservation de nacelle, **Then** le montant attendu est 3 000 €.
3. **Given** un montant de catégorie modifié, **When** une caution de cette catégorie est encaissée ensuite, **Then** le nouveau montant est exigé.
4. **Given** une caution déjà encaissée, **When** le montant de sa catégorie ou le montant par défaut est modifié, **Then** la caution encaissée garde son montant.
5. **Given** l'écran de paramétrage, **When** un salarié saisit un montant nul ou négatif, **Then** l'outil refuse.
6. **Given** une catégorie avec un montant propre, **When** un salarié retire ce montant, **Then** la catégorie revient au montant par défaut.

---

### Edge Cases

- **Client sans type au moment de la sortie** : la sortie est refusée tant que le type n'est pas renseigné (US2) ; aucune déduction automatique à partir du nom ou des coordonnées.
- **Client requalifié professionnel → particulier** sur une réservation confirmée : la caution devient exigée avant la sortie.
- **Client requalifié particulier → professionnel** après encaissement : la caution reste due au client et suit son cycle jusqu'à restitution (US2, scénario 5).
- **Réservation déjà sortie avant la mise en service** : aucune caution n'est exigée ni suivie pour elle.
- **Réservation créée avant la mise en service mais pas encore sortie** : la caution est exigée avant sa sortie si le client est particulier.
- **Montant paramétré modifié après encaissement** : sans effet sur la caution encaissée ; aucun complément ni remboursement partiel n'est demandé.
- **Sortie refusée pour une autre raison** (VGP, photos manquantes) après encaissement : la caution reste encaissée, rattachée à la réservation.
- **Dégât signalé après la restitution de la caution** : la caution est déjà rendue ; le dégât suit le circuit de la feature 003 (facturation au client) sans retenue possible. L'historique montre qui a confirmé « aucun dégât constaté » et quand, avant le signalement.
- **Restitution après annulation** : la réservation n'est jamais sortie, il n'y a pas de photos à comparer ; la confirmation « aucun dégât constaté » n'est pas demandée.
- **Dégât signalé avant la clôture mais clôture pas encore enregistrée** : la caution n'est pas restituable tant que la réservation n'est pas clôturée.
- **Plusieurs réservations du même particulier** : une caution par réservation ; une caution encaissée sur une réservation ne couvre pas une autre.
- **Retour en retard** : sans effet sur la caution ; les jours supplémentaires sont facturés par la feature 003.
- **Erreur de saisie de l'encaissement** (mauvais moyen, mauvaise référence) : corrigeable jusqu'à la restitution ou au solde de la caution, y compris après la sortie, avec un motif obligatoire ; l'historique conserve l'ancienne et la nouvelle valeur. Le montant n'est pas corrigeable : il est celui exigé à l'encaissement.
- **Encaissement saisi sur une réservation en cours, clôturée ou annulée** : refusé ; une caution s'encaisse uniquement sur une réservation confirmée.

## Requirements *(mandatory)*

### Functional Requirements

**Type de client**

- **FR-001**: Le système DOIT permettre de qualifier chaque client comme « particulier » ou « professionnel » ; ce type est obligatoire à la création d'un client.
- **FR-002**: Les clients existant avant la mise en service DOIVENT apparaître « type à renseigner » et pouvoir être qualifiés depuis la section « Caution » d'une de leurs réservations (l'outil n'a pas d'écran de fiche client).
- **FR-003**: Le type « professionnel » NE DOIT PAS présumer de règles propres aux grands comptes (tarifs négociés, bon de commande), qui feront l'objet d'une spec séparée.

**Encaissement et blocage de la sortie**

- **FR-004**: Le système DOIT exiger, pour toute réservation d'un client particulier non sortie à la mise en service, une caution encaissée avant l'enregistrement de la sortie.
- **FR-005**: Le système DOIT refuser l'enregistrement de la sortie d'une réservation dont le client est particulier et dont la caution n'est pas encaissée, ou dont le client n'a pas de type, en indiquant la raison et, le cas échéant, le montant attendu. Ce refus DOIT être garanti côté serveur, même si l'interface a laissé le bouton actif.
- **FR-006**: Le système DOIT permettre d'enregistrer l'encaissement de la caution d'une réservation confirmée : montant, moyen (chèque, empreinte bancaire réalisée au terminal de paiement de l'agence, espèces), référence (numéro de chèque, numéro d'autorisation du terminal ; facultative pour les espèces), auteur, agence et date. L'outil enregistre un encaissement réalisé hors outil : il n'effectue ni paiement ni pré-autorisation bancaire.
- **FR-007**: Le montant encaissé DOIT être égal au montant exigé pour la réservation au moment de l'encaissement ; le salarié ne peut pas encaisser un montant inférieur.
- **FR-008**: Le système DOIT garantir qu'une réservation n'a jamais plus d'une caution encaissée, y compris en cas de validations simultanées.
- **FR-009**: Le système DOIT afficher, dans le détail de chaque réservation, une section « Caution » indiquant l'état de la caution (non requise, non suivie pour une réservation sortie avant la mise en service, type de client à renseigner, à encaisser, encaissée, à restituer, bloquée par un dégât, à solder, restituée, soldée) et signaler l'étape « départ » prête dès que la caution est encaissée ou non requise.
- **FR-010**: Les salariés DOIVENT pouvoir corriger le moyen ou la référence d'un encaissement jusqu'à la restitution ou au solde de la caution, avec un motif obligatoire ; le système DOIT conserver dans l'historique l'ancienne et la nouvelle valeur. Le montant encaissé n'est pas corrigeable.

**Restitution et retenue**

- **FR-011**: À la clôture d'une réservation, la caution encaissée DOIT passer « à restituer » si aucun dégât à traiter ne porte sur la réservation, ou « bloquée par un dégât » sinon.
- **FR-012**: Le système DOIT refuser la restitution d'une caution tant que la réservation n'est pas clôturée (hors annulation) ou qu'un dégât de la réservation est à traiter. Pour une réservation clôturée, la restitution DOIT exiger que le salarié confirme « comparaison départ / retour faite, aucun dégât constaté » ; cette confirmation est inscrite dans l'historique avec son auteur et sa date.
- **FR-013**: Quand tous les dégâts d'une réservation sont réglés (feature 003), la caution DOIT passer « à solder » si au moins un dégât est refacturé, avec une retenue égale au total hors taxes des dégâts refacturés plafonné au montant de la caution, et une restitution égale au reste ; « à restituer » en entier si aucun dégât n'est refacturé.
- **FR-014**: Les salariés DOIVENT pouvoir enregistrer la restitution d'une caution « à restituer » et valider le solde d'une caution « à solder » ; les montants retenu et restitué sont ceux calculés selon FR-013 et NE DOIVENT PAS être modifiables par le salarié ; leur somme DOIT égaler le montant encaissé.
- **FR-015**: L'annulation d'une réservation confirmée dont la caution est encaissée DOIT être acceptée et faire passer la caution « à restituer ».
- **FR-016**: Une caution restituée ou soldée NE DOIT plus être modifiable.

**Paramétrage**

- **FR-017**: Le système DOIT permettre de paramétrer un montant de caution par défaut et, pour chaque catégorie de machine, un montant propre facultatif ; le montant exigé d'une réservation est celui de la catégorie de sa machine, à défaut le montant par défaut. Un montant DOIT être strictement positif et exprimé en euros au centime près.
- **FR-018**: Une modification du paramétrage NE DOIT PAS changer le montant d'une caution déjà encaissée.

**Suivi et traçabilité**

- **FR-019**: Le système DOIT présenter à toutes les agences la liste des cautions en attente d'action (à restituer, à solder, bloquées par un dégât), filtrable par agence de rattachement de la machine, avec la réservation, le client, le montant, l'état et la date depuis laquelle la caution attend.
- **FR-020**: Le système DOIT mettre en évidence les cautions à restituer ou à solder depuis plus de 7 jours.
- **FR-021**: Le système DOIT enregistrer chaque qualification du type de client dans l'historique du client (ancien et nouveau type), et dans l'historique de la réservation chaque encaissement, correction (motif, ancienne et nouvelle valeur), restitution (avec la confirmation « aucun dégât constaté ») et solde de caution, avec l'auteur, l'agence et la date.
- **FR-022**: Toutes ces actions sont ouvertes à tous les salariés dans cette version, chacune contrôlée par une autorisation dédiée.

### Key Entities

- **Client** (feature 001) : enrichi d'un type — particulier, professionnel, ou à renseigner pour les clients antérieurs à la mise en service.
- **Caution** : la garantie d'une réservation d'un client particulier ; réservation, montant exigé, moyen, référence, état (encaissée, à restituer, bloquée par un dégât, à solder, restituée, soldée), montant retenu, montant restitué, auteur / agence / date de l'encaissement et du dénouement.
- **Paramétrage de caution** : un montant par défaut, et un montant propre facultatif par catégorie de machine.
- **Catégorie de machine** (feature 001) : enrichie d'un montant de caution propre, facultatif.
- **Réservation** (feature 001) : associée à au plus une caution ; sa sortie dépend de la caution si le client est particulier.
- **Dégât** (features 002 et 003) : son règlement (refacturé avec montant, ou non refacturé) détermine la retenue proposée sur la caution.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 % des sorties de réservations de clients particuliers enregistrées après la mise en service ont une caution encaissée.
- **SC-002**: Zéro sortie enregistrée pour un client sans type après la mise en service.
- **SC-003**: Zéro caution restituée alors qu'un dégât de la réservation était à traiter.
- **SC-004**: Un salarié enregistre l'encaissement d'une caution en moins de 1 minute depuis le détail de la réservation.
- **SC-005**: 95 % des cautions sans dégât sont restituées dans les 7 jours qui suivent la clôture ou l'annulation.
- **SC-006**: Sur les 12 mois suivant la mise en service, la part des réparations refacturées à des particuliers restée impayée baisse d'au moins 80 %, la retenue sur caution couvrant les dégâts dans la limite de son montant.

## Out of Scope

- Grands comptes : tarifs négociés et bon de commande (spec séparée) ; le type « professionnel » de cette feature ne porte aucune de ces règles.
- Émission de factures, encaissement des loyers et relance des impayés : ils restent dans le logiciel de facturation (feature 003).
- Retenue sur caution pour un autre motif qu'un dégât refacturé (retard, carburant, nettoyage), et ajustement manuel de la retenue (geste commercial).
- Calcul de la TVA sur la retenue et recouvrement du solde des dégâts au-delà de la caution : ils sont portés par la facture du dégât émise par le logiciel de facturation, qui rapproche la caution retenue.
- Dérogation à la caution pour un particulier (client fidèle, accord de la direction).
- Caution exigée des professionnels.
- Pré-autorisation ou paiement bancaire piloté par l'outil (empreinte bancaire en ligne, prestataire de paiement) : fera l'objet d'une spec ultérieure ; dans cette version, l'outil enregistre un encaissement réalisé hors outil.
- Accès du client particulier à l'outil (consultation de sa caution, signature électronique).
- Droits différenciés par rôle : tous les salariés peuvent encaisser, restituer et solder, comme dans les features 001 à 003.

## Assumptions

- La caution est une garantie, pas un paiement : elle n'est pas transmise au logiciel de facturation comme une location ; la retenue vient en règlement des dégâts refacturés par la feature 003. À confirmer avec la comptabilité de M. Vallet.
- La retenue calculée par l'outil est hors taxes, comme le montant des dégâts transmis par la feature 003. La facture du dégât émise par le logiciel de facturation reste la référence pour la TVA : elle réclame au client la TVA et le solde éventuel au-delà de la caution, et rapproche la caution retenue.
- Une caution par réservation : un particulier qui loue deux machines en même temps verse deux cautions.
- Les clients existants (feature 001) n'ont pas de type ; aucune reprise automatique n'est faite, chaque client est qualifié par un salarié au plus tard à la sortie de sa prochaine réservation.
- Le seuil de 7 jours pour une caution en attente est une proposition à confirmer avec M. Vallet.
- Le montant par défaut initial est de 1 500 € tant qu'aucun montant n'a été paramétré ; il est modifiable depuis l'écran des montants et reste à confirmer avec M. Vallet.
- Le blocage de la sortie s'ajoute à ceux des features 001 (VGP, disponibilité) et 002 (photos de départ), sans les remplacer. La disponibilité du bouton de sortie se décide section par section : la section « Caution », comme celles des photos (002) et de l'attestation (005), indique si l'étape « départ » est prête de son point de vue, et le bouton ne s'active que si toutes le sont. Le refus serveur, avec son message, reste la seule garantie.
- Cette fonctionnalité s'appuie sur la feature 001 (clients, réservations, sortie, retour, annulation, historique), la feature 002 (dégâts signalés au retour) et la feature 003 (règlement des dégâts : refacturé avec montant, ou non refacturé).
