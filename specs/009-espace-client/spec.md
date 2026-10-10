# Feature Specification: Espace client et réservation en ligne

**Feature Branch**: `009-espace-client`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: « Les clients de Vallet Location doivent pouvoir réserver sur le site : il faut un espace client. » Décisions déjà prises par l'utilisateur : une réservation faite en ligne est une demande que l'agence confirme ou refuse, la confirmation passant par les règles existantes de la feature 001 (chevauchement, statut de la machine, VGP) ; caution (004), bon de commande (006) et attestation VGP (005) restent gérés comme aujourd'hui ; inscription libre de tout client, particulier ou professionnel, type obligatoire comme dans la feature 004 ; l'espace permet de chercher les machines disponibles et d'envoyer une demande, de suivre ses demandes et ses réservations et d'annuler une demande non confirmée, de télécharger ses attestations VGP et de voir des prix indicatifs. Hors périmètre : paiement en ligne, signature électronique, modification des règles des features 001 à 008. S'appuie sur les features 001 à 008, toutes livrées.

## Contexte

Jusqu'ici, l'outil est réservé aux 85 salariés : toutes les specs 001 à 008 posent que le client ne se connecte pas. Un client qui veut louer une machine appelle ou se déplace en agence, le salarié cherche une machine disponible, et la réservation est saisie pendant l'appel. Les demandes arrivent aux heures d'ouverture, et le client n'a aucun moyen de savoir ce qui est disponible, ni de retrouver ses réservations ou l'attestation VGP de la machine qu'il a louée.

Cette fonctionnalité ouvre l'outil aux clients, sans rien changer à ce que font les salariés aujourd'hui. Un client crée librement son compte, cherche une machine disponible, voit un prix indicatif et envoie une demande. La demande ne réserve rien : c'est une agence qui la confirme, et la confirmation crée une réservation ordinaire, soumise exactement aux règles de la feature 001 et suivie ensuite par les features 002 à 007 comme toute réservation saisie au comptoir. Le client suit ses demandes et ses réservations et télécharge ses attestations VGP depuis son espace.

## Clarifications

### Session 2026-10-10

- Q: Comment un compte client créé librement est-il rattaché à une fiche client existante, sans qu'un inconnu puisse s'approprier la fiche d'un vrai client ? → A: Par l'agence uniquement : à la première confirmation d'une demande, le salarié rattache le compte à une fiche existante (proposées : même e-mail ou même téléphone) ou crée la fiche à partir des informations déclarées ; aucun rattachement automatique ; avant, le client ne voit que ses demandes ; rattachement définitif, plusieurs comptes possibles par fiche (FR-005 à FR-008).
- Q: Côté agence, comment se traite une demande en ligne ? → A: Liste des demandes en attente de toutes les agences, filtrable par agence ; confirmation sur la machine demandée ou sur une autre machine de la même catégorie disponible aux mêmes dates, ou refus avec motif obligatoire ; expiration automatique d'une demande non traitée après sa date de début ; e-mail au client à la confirmation, au refus et à l'expiration (FR-015 à FR-021, FR-027).
- Q: Quel prix montre-t-on au client ? → A: Un prix journalier indicatif « à partir de … € HT / jour » par catégorie, facultatif (« prix sur demande » sinon), saisi par les salariés, toujours présenté comme indicatif ; ni total, ni TTC, ni tarif négocié ; jamais transmis à la facturation (FR-031 à FR-033).
- Q: Les comptes clients sont-ils séparés des comptes salariés ? → A: Oui : comptes, inscription, connexion et réinitialisation du mot de passe propres à l'espace client ; un client n'accède à aucun écran salarié et les identifiants d'un salarié ne permettent pas de se connecter à l'espace client (FR-003).
- Q: Quand le client doit-il avoir confirmé son adresse e-mail, et l'espace est-il accessible sans compte ? → A: Tout l'espace (recherche, prix, demandes) exige d'être connecté avec une adresse confirmée ; un compte non confirmé ne voit que la page de confirmation avec le renvoi du lien ; pas de catalogue public (FR-002, Out of Scope).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Créer son compte client et se connecter (Priority: P1)

Un client, particulier ou professionnel, ouvre l'espace client du site et crée son compte : nom (ou raison sociale), adresse e-mail, téléphone, type (particulier ou professionnel) et mot de passe. Il reçoit un e-mail pour confirmer son adresse. Une fois l'adresse confirmée, il accède à son espace. Le compte client est distinct d'un compte salarié : il ne donne accès à aucun écran des salariés.

**Why this priority**: Sans compte, aucune demande ne peut être rattachée à un client ni suivie. C'est la porte d'entrée de toute la fonctionnalité.

**Independent Test**: Créer un compte professionnel, confirmer l'adresse par le lien reçu, se connecter : l'espace client s'affiche. Tenter d'ouvrir le planning des salariés avec ce compte : refus. Tenter de créer un compte sans type : refus.

**Acceptance Scenarios**:

1. **Given** le formulaire d'inscription, **When** un visiteur le remplit avec un nom, une adresse e-mail, un téléphone, le type « professionnel » et un mot de passe conforme, **Then** le compte est créé, un e-mail de confirmation est envoyé à l'adresse saisie, et l'espace indique que l'adresse doit être confirmée.
2. **Given** le formulaire d'inscription, **When** le visiteur l'envoie sans choisir « particulier » ou « professionnel », **Then** l'inscription est refusée et le type est demandé.
3. **Given** une adresse e-mail déjà utilisée par un autre compte client, **When** un visiteur tente de s'inscrire avec cette adresse, **Then** l'inscription est refusée.
4. **Given** un compte dont l'adresse n'est pas confirmée, **When** le client tente de chercher une machine ou d'envoyer une demande, **Then** l'espace lui demande de confirmer son adresse et lui permet de renvoyer l'e-mail de confirmation.
5. **Given** un compte confirmé, **When** le client se connecte avec son adresse et son mot de passe, **Then** il arrive sur son espace.
6. **Given** un client connecté, **When** il tente d'ouvrir un écran salarié (planning, réservations, tableau de bord, paramétrage), **Then** l'accès est refusé.
7. **Given** un salarié, **When** il tente de se connecter à l'espace client avec ses identifiants de salarié, **Then** la connexion est refusée : les deux espaces ont des comptes distincts.
8. **Given** un client qui a oublié son mot de passe, **When** il le demande depuis la page de connexion, **Then** il reçoit un lien de réinitialisation par e-mail.

---

### User Story 2 - Chercher une machine disponible et envoyer une demande (Priority: P1)

Le client choisit une catégorie de machine, une agence et des dates. L'espace affiche les machines disponibles sur toute la période, selon les mêmes règles que la recherche des salariés (feature 001), avec le prix indicatif de la catégorie. Le client choisit une machine, ajoute s'il le souhaite un commentaire (adresse du chantier, besoin particulier) et envoie sa demande. La demande est « en attente » : elle ne bloque pas la machine, et l'espace rappelle qu'elle doit être confirmée par l'agence.

**Why this priority**: C'est le besoin exprimé : réserver sur le site. Sans recherche ni demande, l'espace n'a pas de raison d'être.

**Independent Test**: Avec une nacelle disponible du 10 au 14 novembre et une autre réservée sur ces dates, chercher les nacelles de l'agence pour ces dates : seule la première apparaît, avec le prix indicatif de la catégorie. Envoyer une demande : elle apparaît « en attente » dans l'espace du client et dans la liste des demandes à traiter des salariés ; la machine reste réservable par un salarié.

**Acceptance Scenarios**:

1. **Given** une catégorie, une agence et une période, **When** le client lance la recherche, **Then** il voit les machines de cette catégorie rattachées à cette agence et disponibles sur toute la période selon les règles de la feature 001 (statut, VGP valide jusqu'à la date de fin, aucune réservation non annulée qui chevauche, aucune autre indisponibilité), avec pour chacune sa référence, sa catégorie, son agence et le prix indicatif de la catégorie.
2. **Given** une machine en panne, à l'atelier, retirée du parc, réservée sur une partie de la période ou dont la VGP expire avant la date de fin, **When** le client lance la recherche, **Then** cette machine n'apparaît pas.
3. **Given** une machine disponible, **When** le client envoie une demande pour cette machine et ces dates, **Then** la demande est enregistrée « en attente » avec la machine, les dates, le commentaire éventuel et la date d'envoi, et l'espace indique qu'elle sera confirmée ou refusée par l'agence.
4. **Given** une demande « en attente » sur une machine, **When** un salarié réserve cette machine sur les mêmes dates pour un autre client, **Then** la réservation du salarié est acceptée : une demande ne bloque pas la machine.
5. **Given** une machine devenue indisponible entre la recherche et l'envoi, **When** le client envoie sa demande, **Then** l'envoi est refusé avec le motif, et le client est invité à relancer la recherche.
6. **Given** des dates incohérentes (date de fin avant la date de début, date de début passée), **When** le client lance la recherche ou envoie une demande, **Then** l'outil refuse et indique pourquoi.
7. **Given** un client qui a déjà une demande « en attente » sur la même machine et des dates qui se chevauchent, **When** il envoie une nouvelle demande, **Then** l'envoi est refusé.
8. **Given** un client qui a déjà 10 demandes « en attente », **When** il envoie une nouvelle demande, **Then** l'envoi est refusé jusqu'à ce qu'une de ses demandes soit traitée ou annulée.
9. **Given** les résultats d'une recherche, **When** le client les consulte, **Then** il ne voit aucune information sur les autres clients ni sur les réservations existantes.

---

### User Story 3 - Traiter les demandes en ligne en agence (Priority: P1)

Un salarié ouvre la liste des demandes en ligne en attente, de toutes les agences, triées par date de début. Pour chaque demande, il voit le client déclaré, la machine, les dates, le commentaire et le prix indicatif affiché au client. Il confirme la demande, ce qui crée une réservation ordinaire, ou la refuse avec un motif. Lors de la première confirmation d'un compte, le salarié rattache le compte à une fiche client existante (l'outil lui propose les fiches de même adresse e-mail ou de même téléphone) ou crée la fiche à partir des informations déclarées. Le client reçoit un e-mail à chaque décision.

**Why this priority**: Une demande qui n'est jamais traitée n'est qu'une promesse non tenue. La confirmation par l'agence est la garantie que les règles des features 001 à 007 s'appliquent aux réservations venues du site.

**Independent Test**: Sur une demande en attente d'un nouveau compte, confirmer en créant la fiche : une réservation confirmée existe pour la machine et les dates demandées, la demande passe « confirmée », le client reçoit l'e-mail. Sur une autre demande dont la machine a été réservée entre-temps, tenter de confirmer : refus avec la réservation en conflit ; refuser avec le motif « machine indisponible » : le client reçoit l'e-mail avec le motif.

**Acceptance Scenarios**:

1. **Given** des demandes en attente pour des machines de plusieurs agences, **When** un salarié ouvre la liste des demandes en ligne, **Then** il voit pour chacune le client déclaré (nom, type, e-mail, téléphone), la fiche client rattachée ou la mention « compte non rattaché », la machine, l'agence de rattachement, les dates, le commentaire et la date d'envoi, triées par date de début, et peut filtrer par agence de rattachement de la machine.
2. **Given** une demande en attente d'un compte déjà rattaché à une fiche, **When** le salarié la confirme, **Then** une réservation confirmée est créée pour cette fiche, cette machine et ces dates, selon les règles de la feature 001, avec le salarié comme auteur et son agence ; la demande passe « confirmée » et renvoie vers la réservation.
3. **Given** une demande en attente d'un compte non rattaché, **When** le salarié la confirme, **Then** il doit choisir entre une fiche existante proposée (même adresse e-mail ou même téléphone) ou une recherche de fiche, et la création d'une fiche à partir du nom, du téléphone, de l'adresse e-mail et du type déclarés ; le compte reste ensuite rattaché à cette fiche.
4. **Given** une demande dont la machine n'est plus disponible (réservée entre-temps, en panne, VGP qui ne couvre plus la période), **When** le salarié tente de la confirmer, **Then** la confirmation est refusée avec le motif de la feature 001, et la demande reste « en attente ».
5. **Given** une demande en attente, **When** le salarié la confirme sur une autre machine de la même catégorie, disponible sur les mêmes dates, **Then** la réservation est créée sur cette autre machine et l'e-mail au client indique la machine réservée.
6. **Given** une demande en attente, **When** le salarié la refuse avec le motif « machine indisponible à ces dates », **Then** la demande passe « refusée » avec le motif, l'auteur et la date.
7. **Given** une demande en attente, **When** le salarié tente de la refuser sans motif, **Then** l'outil refuse.
8. **Given** une demande confirmée ou refusée, **When** la décision est enregistrée, **Then** le client reçoit un e-mail en français indiquant la décision, la machine, les dates, l'agence de retrait et, en cas de refus, le motif.
9. **Given** deux salariés qui confirment ou refusent au même instant la même demande, **When** les deux décisions arrivent, **Then** une seule est enregistrée, au plus une réservation est créée, et l'autre salarié reçoit un refus explicite.
10. **Given** une réservation créée par la confirmation d'une demande d'un client particulier, d'un grand compte ou pour une machine soumise à VGP, **When** elle suit son cycle, **Then** la caution (004), le bon de commande (006), l'attestation VGP (005), les photos (002) et la transmission à la facturation (003) s'appliquent exactement comme pour une réservation saisie au comptoir.
11. **Given** une réservation créée depuis une demande, **When** un salarié ouvre son détail, **Then** il voit qu'elle provient d'une demande en ligne, avec le commentaire du client.
12. **Given** un compte rattaché à une fiche, **When** un salarié tente de le rattacher à une autre fiche, **Then** l'outil refuse : le rattachement est définitif dans cette version.

---

### User Story 4 - Suivre ses demandes et ses réservations, annuler une demande (Priority: P2)

Le client retrouve dans son espace toutes ses demandes avec leur état (en attente, confirmée, refusée avec le motif, annulée, expirée) et, une fois son compte rattaché à une fiche, toutes les réservations de cette fiche, y compris celles saisies au comptoir ou par téléphone, avec leur état. Il peut annuler une demande encore en attente. Une réservation confirmée ne s'annule pas en ligne : l'espace indique de contacter l'agence.

**Why this priority**: Le client doit savoir où en est sa demande sans appeler l'agence ; mais une demande traitée et notifiée par e-mail apporte déjà la valeur principale.

**Independent Test**: Avec un compte rattaché ayant une demande en attente, une demande refusée et une réservation saisie au comptoir pour la même fiche : l'espace affiche les trois avec leur état ; annuler la demande en attente : elle passe « annulée » et disparaît de la liste des salariés.

**Acceptance Scenarios**:

1. **Given** un client avec des demandes dans chaque état, **When** il ouvre son espace, **Then** il voit chaque demande avec la machine, les dates, l'état, la date d'envoi et, selon l'état, le motif du refus ou la réservation créée.
2. **Given** un compte rattaché à une fiche qui a des réservations saisies par les salariés, **When** le client ouvre son espace, **Then** il voit ces réservations avec la machine, les dates, l'agence de retrait et l'état (confirmée, en cours, terminée, annulée), sans les informations internes (historique, conflits, dégâts, transmissions, cautions, bons de commande).
3. **Given** un compte non rattaché, **When** le client ouvre son espace, **Then** il ne voit que ses demandes.
4. **Given** une demande en attente, **When** le client l'annule, **Then** elle passe « annulée » et sort de la liste des demandes à traiter des salariés.
5. **Given** une demande confirmée, refusée ou expirée, **When** le client tente de l'annuler, **Then** l'outil refuse.
6. **Given** une réservation confirmée, **When** le client la consulte, **Then** aucune annulation en ligne n'est proposée et l'espace indique de contacter l'agence de retrait.
7. **Given** une demande en attente dont la date de début est passée sans décision de l'agence, **When** le jour suivant commence, **Then** la demande passe « expirée », sort de la liste des demandes à traiter, et le client reçoit un e-mail l'en informant.
8. **Given** un client connecté, **When** il tente d'ouvrir une demande, une réservation ou un document d'un autre client, **Then** l'accès est refusé.
9. **Given** une demande annulée par le client au même instant où un salarié la confirme, **When** les deux actions arrivent, **Then** une seule est enregistrée et l'autre reçoit un refus explicite.

---

### User Story 5 - Télécharger les attestations VGP de ses réservations (Priority: P2)

Pour chacune de ses réservations d'une machine soumise à VGP, le client télécharge depuis son espace le rapport de VGP qui lui a été envoyé ou remis (feature 005), pour le présenter en cas de contrôle sur le chantier.

**Why this priority**: L'attestation part déjà par e-mail (feature 005) ; le téléchargement évite au client de la redemander à l'agence quand il l'a perdue.

**Independent Test**: Sur une réservation d'une nacelle dont l'attestation a été envoyée, télécharger le document depuis l'espace : c'est le rapport envoyé. Sur une réservation d'une mini-pelle non soumise à VGP : aucun document proposé.

**Acceptance Scenarios**:

1. **Given** une réservation non annulée d'une machine soumise à VGP dont l'attestation a été envoyée ou remise en main propre, **When** le client ouvre ses documents, **Then** il peut télécharger le rapport de VGP du dernier envoi ou de la remise.
2. **Given** une réservation dont l'attestation n'a encore été ni envoyée ni remise, **When** le client ouvre ses documents, **Then** l'espace indique « attestation pas encore disponible ».
3. **Given** une réservation d'une machine non soumise à VGP, **When** le client ouvre ses documents, **Then** aucune attestation n'est proposée.
4. **Given** une réservation annulée, **When** le client ouvre ses documents, **Then** aucune attestation n'est proposée.
5. **Given** le lien de téléchargement d'un rapport d'une réservation d'un autre client, **When** le client l'ouvre, **Then** l'accès est refusé.
6. **Given** un client qui télécharge un rapport, **When** le téléchargement a lieu, **Then** l'attestation de la feature 005 n'en est pas modifiée : son état, ses envois et la condition de sortie restent inchangés.

---

### User Story 6 - Afficher un prix indicatif par catégorie (Priority: P3)

Un salarié saisit, pour chaque catégorie de machine, un prix journalier indicatif « à partir de », hors taxes. Le client le voit dans les résultats de recherche et sur sa demande, toujours accompagné de la mention que le prix est indicatif et que le prix facturé est établi par l'agence. Une catégorie sans prix indicatif affiche « prix sur demande ».

**Why this priority**: Le client veut un ordre de grandeur avant de demander ; mais la demande et sa confirmation fonctionnent sans prix. Le prix facturé reste celui du logiciel de facturation (features 003 et 006).

**Independent Test**: Saisir 95 € pour la catégorie « nacelle » : la recherche de nacelles affiche « à partir de 95 € HT / jour — prix indicatif » ; une mini-pelle sans prix affiche « prix sur demande ». Aucune transmission à la facturation ne contient ce prix.

**Acceptance Scenarios**:

1. **Given** l'écran des prix indicatifs, **When** un salarié saisit 95 € pour la catégorie « nacelle », **Then** le prix est enregistré avec l'auteur et la date.
2. **Given** la catégorie « nacelle » à 95 €, **When** le client cherche des nacelles, **Then** chaque résultat affiche « à partir de 95,00 € HT / jour » et la mention « prix indicatif ; le prix facturé est établi par votre agence ».
3. **Given** une catégorie sans prix indicatif, **When** le client cherche des machines de cette catégorie, **Then** les résultats affichent « prix sur demande ».
4. **Given** l'écran des prix indicatifs, **When** un salarié saisit un montant nul ou négatif, **Then** l'outil refuse.
5. **Given** une catégorie avec un prix indicatif, **When** un salarié le retire, **Then** la catégorie affiche « prix sur demande ».
6. **Given** un client grand compte (feature 006), **When** il cherche une machine, **Then** il voit le même prix indicatif que tout client : aucun tarif négocié n'est affiché.
7. **Given** une demande envoyée quand la catégorie était à 95 €, **When** le prix passe à 110 €, **Then** la demande garde le prix indicatif affiché au moment de l'envoi, pour information.
8. **Given** une réservation issue d'une demande, **When** ses éléments sont transmis au logiciel de facturation (feature 003), **Then** aucun prix indicatif n'est transmis.

---

### Edge Cases

- **Adresse e-mail du compte identique à celle d'une fiche existante** : aucun rattachement automatique ; la fiche est proposée au salarié à la première confirmation, qui décide.
- **Plusieurs comptes pour une même entreprise** : plusieurs comptes peuvent être rattachés à la même fiche ; chacun voit toutes les réservations de la fiche et ses propres demandes.
- **Type déclaré différent du type de la fiche choisie au rattachement** : la fiche garde son type, géré par les salariés (feature 004) ; le type déclaré ne sert qu'à créer une fiche.
- **Fiche créée depuis un compte particulier** : sa première réservation exige une caution avant la sortie (feature 004), comme pour tout particulier.
- **Demande d'un client qui est grand compte** : la réservation créée exige un bon de commande avant la sortie (feature 006) ; le client n'en saisit pas en ligne.
- **Demande pour une machine soumise à VGP** : l'attestation part à la confirmation, c'est-à-dire à la création de la réservation (feature 005), à l'adresse e-mail de la fiche.
- **Machine mise en panne, à l'atelier ou retirée après l'envoi d'une demande** : la demande reste en attente ; sa confirmation est refusée par les règles de la feature 001 ; le salarié la refuse ou la confirme sur une autre machine.
- **Machine en vente avec offre acceptée** (feature 007) : exclue de la recherche et refusée à la confirmation dans les mêmes conditions que pour une réservation au comptoir.
- **Demande pour aujourd'hui** : acceptée ; elle expire à la fin de la journée si elle n'est pas traitée.
- **Compte non confirmé qui n'est jamais confirmé** : il ne peut envoyer aucune demande ; aucune fiche n'est créée.
- **Client qui change le nom ou le téléphone de son compte** : la fiche client rattachée n'est pas modifiée ; elle reste gérée par les salariés.
- **Réservation issue d'une demande annulée ensuite par un salarié** : le client la voit « annulée » dans son espace ; la demande reste « confirmée », avec le lien vers la réservation.
- **Réservation d'une fiche rattachée saisie par un salarié pour un autre interlocuteur de l'entreprise** : visible par tous les comptes rattachés à la fiche.
- **Prix indicatif de catégorie modifié** : sans effet sur les demandes déjà envoyées, qui gardent le prix affiché à l'envoi.
- **Tentatives répétées de connexion** : la connexion est temporairement bloquée après plusieurs échecs, comme pour les salariés.

## Requirements *(mandatory)*

### Functional Requirements

**Compte client**

- **FR-001**: Toute personne DOIT pouvoir créer un compte client avec un nom ou une raison sociale, une adresse e-mail, un téléphone, un type (particulier ou professionnel, obligatoire) et un mot de passe. Une adresse e-mail NE DOIT correspondre qu'à un seul compte client.
- **FR-002**: Le système DOIT faire confirmer l'adresse e-mail par un lien envoyé à cette adresse ; tant qu'elle n'est pas confirmée, le compte n'accède qu'à la page lui demandant de la confirmer et au renvoi du lien.
- **FR-003**: Les comptes clients DOIVENT être distincts des comptes salariés : un compte client ne donne accès à aucun écran ni à aucune action des salariés, et les identifiants d'un salarié ne permettent pas de se connecter à l'espace client.
- **FR-004**: Le client DOIT pouvoir se connecter, se déconnecter, réinitialiser son mot de passe par e-mail, changer son mot de passe, et modifier le nom et le téléphone de son compte ; ces modifications NE DOIVENT PAS modifier la fiche client rattachée. Les tentatives de connexion répétées DOIVENT être limitées.

**Rattachement à une fiche client**

- **FR-005**: Un compte client NE DOIT être rattaché à une fiche client que par un salarié, lors de la première confirmation d'une demande de ce compte : soit à une fiche existante, soit à une fiche créée à partir du nom, du téléphone, de l'adresse e-mail et du type déclarés. Aucun rattachement automatique n'est fait.
- **FR-006**: Lors du rattachement, le système DOIT proposer au salarié les fiches existantes de même adresse e-mail ou de même téléphone, et lui permettre de rechercher une autre fiche.
- **FR-007**: Un rattachement DOIT être définitif dans cette version ; plusieurs comptes PEUVENT être rattachés à la même fiche. Le rattachement est inscrit dans l'historique de la fiche avec l'auteur, l'agence et la date.
- **FR-008**: Tant que son compte n'est pas rattaché, le client NE DOIT voir que ses propres demandes ; une fois rattaché, il voit aussi les réservations et les documents de la fiche.

**Recherche et demande**

- **FR-009**: Le client DOIT pouvoir chercher les machines disponibles par catégorie, agence de rattachement et période ; les machines présentées DOIVENT être exactement celles que la recherche des salariés présente pour les mêmes critères (feature 001), y compris les exclusions ajoutées par les autres fonctionnalités.
- **FR-010**: Les résultats DOIVENT afficher la référence, la catégorie, l'agence de rattachement et le prix indicatif de la catégorie, et NE DOIVENT afficher aucune information sur les autres clients ni sur les réservations existantes.
- **FR-011**: Un client dont l'adresse est confirmée DOIT pouvoir envoyer une demande portant sur une machine, une date de début, une date de fin et un commentaire facultatif (500 caractères au plus). Le système DOIT vérifier à l'envoi que les dates sont cohérentes (fin après début, début non passé, heure de Paris) et que la machine est disponible sur toute la période selon FR-009, et refuser sinon avec le motif.
- **FR-012**: Le système DOIT refuser une demande qui chevauche une demande en attente du même compte sur la même machine, et toute nouvelle demande d'un compte qui a déjà 10 demandes en attente.
- **FR-013**: Une demande en attente NE DOIT PAS bloquer la machine : elle ne modifie ni la recherche, ni les réservations, ni le planning des salariés.
- **FR-014**: Une demande DOIT conserver le prix indicatif de la catégorie affiché au moment de l'envoi, ou l'absence de prix.

**Traitement en agence**

- **FR-015**: Le système DOIT présenter aux salariés la liste des demandes en attente de toutes les agences, triées par date de début, filtrable par agence de rattachement de la machine, avec le client déclaré, le rattachement du compte, la machine, les dates, le commentaire, le prix indicatif affiché et la date d'envoi. La liste DOIT se mettre à jour sans action manuelle du salarié quand une demande est envoyée, annulée ou traitée.
- **FR-016**: Le système DOIT signaler dans la navigation des salariés le nombre de demandes en attente.
- **FR-017**: Un salarié DOIT pouvoir confirmer une demande en attente ; la confirmation DOIT créer une réservation selon exactement les règles de la feature 001 (dates, statut de la machine, VGP, chevauchement, garanties en cas de validations simultanées, exclusions ajoutées par les autres fonctionnalités), avec le salarié comme auteur et son agence. Si la réservation est refusée, la demande DOIT rester en attente et le motif de la feature 001 DOIT être affiché.
- **FR-018**: Le salarié DOIT pouvoir confirmer la demande sur la machine demandée ou sur une autre machine de la même catégorie disponible sur les mêmes dates ; les dates de la demande NE DOIVENT PAS être modifiées.
- **FR-019**: Un salarié DOIT pouvoir refuser une demande en attente avec un motif obligatoire (500 caractères au plus).
- **FR-020**: Le système DOIT garantir qu'une demande n'est décidée qu'une fois (confirmée, refusée, annulée ou expirée) et qu'elle ne crée jamais plus d'une réservation, y compris en cas d'actions simultanées.
- **FR-021**: Le système DOIT envoyer au client, sans action du salarié, un e-mail en français à la confirmation (machine réservée, dates, agence de retrait), au refus (motif) et à l'expiration de sa demande. L'enregistrement de la décision NE DOIT PAS attendre l'envoi de l'e-mail.
- **FR-022**: Le détail d'une réservation issue d'une demande DOIT indiquer aux salariés qu'elle provient d'une demande en ligne, avec le commentaire du client ; cette indication NE DOIT bloquer aucune étape de la réservation.
- **FR-023**: Une réservation issue d'une demande DOIT être soumise, sans exception, aux règles des features 002 à 007 qui s'appliquent à toute réservation.

**Suivi par le client**

- **FR-024**: Le client DOIT voir ses demandes avec leur état (en attente, confirmée, refusée, annulée, expirée), la machine, les dates, la date d'envoi, le motif du refus et la réservation créée le cas échéant.
- **FR-025**: Le client d'un compte rattaché DOIT voir les réservations de la fiche avec la machine, les dates, l'agence de retrait et l'état (confirmée, en cours, terminée, annulée), sans aucune information interne aux salariés.
- **FR-026**: Le client DOIT pouvoir annuler une demande en attente ; le système DOIT refuser l'annulation d'une demande dans tout autre état. Aucune réservation NE PEUT être annulée en ligne.
- **FR-027**: Une demande en attente dont la date de début est passée DOIT passer « expirée » automatiquement au début du jour suivant (heure de Paris), même si un traitement a été interrompu.
- **FR-028**: Le système DOIT refuser à un client l'accès à toute demande, réservation ou document qui ne relève pas de son compte ou de la fiche à laquelle il est rattaché.

**Documents**

- **FR-029**: Le client d'un compte rattaché DOIT pouvoir télécharger, pour chaque réservation non annulée de la fiche portant sur une machine soumise à VGP, le rapport de VGP du dernier envoi ou de la remise en main propre de son attestation (feature 005) ; si l'attestation n'a été ni envoyée ni remise, l'espace DOIT indiquer qu'elle n'est pas encore disponible.
- **FR-030**: Le téléchargement NE DOIT modifier ni l'état de l'attestation, ni ses envois, ni la condition de sortie de la feature 005.

**Prix indicatifs**

- **FR-031**: Les salariés DOIVENT pouvoir saisir, modifier et retirer, pour chaque catégorie de machine, un prix journalier indicatif hors taxes, strictement positif, exprimé en euros au centime près ; chaque modification est tracée avec l'auteur et la date.
- **FR-032**: Le prix indicatif DOIT être affiché au client sous la forme « à partir de … € HT / jour » avec la mention qu'il est indicatif et que le prix facturé est établi par l'agence ; une catégorie sans prix DOIT afficher « prix sur demande ». Aucun total, devis ou tarif négocié NE DOIT être affiché.
- **FR-033**: Le prix indicatif NE DOIT être ni transmis au logiciel de facturation, ni utilisé par aucune règle des features 001 à 008.

**Accès et traçabilité**

- **FR-034**: Le traitement des demandes et la saisie des prix indicatifs sont ouverts à tous les salariés dans cette version, chacun contrôlé par une autorisation dédiée.
- **FR-035**: Le système DOIT enregistrer dans l'historique de la demande son envoi, sa confirmation (avec la réservation créée et le rattachement éventuel), son refus (avec le motif), son annulation et son expiration, avec l'auteur (client, salarié ou « automatique ») et la date.
- **FR-036**: Aucun écran ni aucune règle des features 001 à 008 NE DOIT changer de comportement pour les salariés, hormis l'ajout de la liste des demandes, de son compteur dans la navigation, de l'écran des prix indicatifs et de l'indication « demande en ligne » dans le détail d'une réservation.

### Key Entities

- **Compte client** : l'accès d'un client à l'espace client ; nom ou raison sociale, adresse e-mail unique et sa date de confirmation, téléphone, type déclaré (particulier ou professionnel), mot de passe, fiche client rattachée (facultative, définitive une fois posée).
- **Demande de réservation** : ce que le client envoie depuis l'espace ; compte, machine demandée, dates de début et de fin, commentaire, prix indicatif affiché à l'envoi, état (en attente, confirmée, refusée, annulée, expirée), motif du refus, réservation créée, auteur et date de chaque décision.
- **Prix indicatif de catégorie** : un prix journalier hors taxes facultatif par catégorie de machine, avec l'auteur et la date de la dernière modification.
- **Client** (features 001, 004, 006) : la fiche gérée par les salariés ; peut avoir un ou plusieurs comptes clients rattachés.
- **Réservation** (feature 001) : peut provenir d'une demande ; inchangée par ailleurs.
- **Catégorie de machine** (feature 001) : porte un prix indicatif facultatif.
- **Attestation et rapport de VGP** (feature 005) : le rapport du dernier envoi ou de la remise est le document téléchargeable par le client.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un client crée son compte et confirme son adresse en moins de 3 minutes.
- **SC-002**: Un client trouve une machine disponible et envoie sa demande en moins de 2 minutes.
- **SC-003**: Zéro réservation créée depuis une demande en violation d'une règle des features 001 à 007 (chevauchement, statut, VGP, caution, bon de commande, attestation).
- **SC-004**: Zéro accès d'un client à un écran salarié, ou à une demande, une réservation ou un document qui ne relève pas de son compte.
- **SC-005**: Zéro compte client rattaché à une fiche sans décision d'un salarié.
- **SC-006**: 100 % des demandes reçoivent une décision ou passent « expirées », et le client est informé par e-mail de chaque issue ; aucune demande ne reste « en attente » après sa date de début.
- **SC-007**: Un salarié traite une demande (confirmation avec rattachement ou refus) en moins de 1 minute depuis la liste.
- **SC-008**: Les machines présentées au client par la recherche sont, pour les mêmes critères, exactement celles présentées aux salariés.
- **SC-009**: Zéro prix indicatif transmis au logiciel de facturation ; zéro tarif négocié affiché à un client.

## Out of Scope

- Paiement en ligne (loyer, caution, empreinte bancaire) : la caution reste encaissée en agence (feature 004).
- Signature électronique d'un contrat ou des conditions générales.
- Saisie du bon de commande par le client (feature 006 : saisi par un salarié).
- Modification ou annulation en ligne d'une réservation confirmée ; modification des dates d'une demande (le client l'annule et en envoie une nouvelle).
- Devis, calcul d'un total, tarifs par durée, tarifs négociés, TVA : le prix facturé reste établi par le logiciel de facturation (features 003 et 006).
- Demande portant sur une catégorie sans machine précise, ou sur plusieurs machines à la fois.
- Détachement ou changement de la fiche rattachée à un compte ; fusion de fiches clients.
- Blocage ou suppression d'un compte client par un salarié, et suppression du compte par le client depuis l'espace : demande à adresser à l'agence.
- Modification par le client de l'adresse e-mail de son compte ou des informations de la fiche client.
- Téléchargement d'autres documents (photos, factures, contrats).
- Catalogue public consultable sans compte, référencement, pages vitrines.
- Notifications par SMS ; messagerie entre le client et l'agence.
- Application mobile.
- Toute modification des règles des features 001 à 008.
- Droits différenciés par rôle côté salariés : tous les salariés peuvent traiter les demandes et saisir les prix indicatifs.

## Assumptions

- L'espace client est servi par la même application que l'outil des salariés, sur une adresse distincte du site (par exemple `/espace-client`), en français.
- Le seuil de 10 demandes en attente par compte limite les abus d'une inscription libre ; il est à confirmer avec M. Vallet.
- Le prix indicatif est hors taxes, pour tous les clients ; les particuliers sont avertis par la mention « HT ». À confirmer avec M. Vallet.
- Les e-mails partent au nom de Vallet Location avec l'adresse d'expédition déjà utilisée pour les attestations (feature 005).
- L'agence de la réservation créée est celle du salarié qui confirme ; le retrait se fait à l'agence de rattachement de la machine (feature 001), indiquée au client.
- Le volume reste modeste : quelques dizaines de demandes par jour pour l'ensemble des agences.
- La politique de mot de passe et la limitation des tentatives de connexion sont celles déjà appliquées aux salariés.
- Les règles de confidentialité applicables aux données personnelles des comptes clients (mentions, durée de conservation) sont fournies par Vallet Location avant la mise en production.
- Cette fonctionnalité s'appuie sur les features 001 (machines, catégories, agences, clients, réservations, recherche de disponibilité), 004 (type de client), 005 (attestations et rapports de VGP) ; les features 002, 003, 006 et 007 s'appliquent aux réservations créées sans être modifiées.
