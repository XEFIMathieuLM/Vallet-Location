# Feature Specification: Photos de départ et de retour via QR code

**Feature Branch**: `002-photos-qr-code`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — « Le logiciel PC affiche un QR code que le vendeur scanne avec son téléphone pour prendre les photos de la machine, qui arrivent automatiquement dans la bonne réservation. Tant que toutes les photos ne sont pas reçues, la validation reste bloquée sur le PC. » Règles : une machine ne peut pas partir sans photos de départ ; une machine ne peut pas être clôturée au retour sans photos de retour. S'appuie sur `001-reservation-machines`.

## Contexte

L'an dernier, Vallet Location a perdu 85 000 € de réparations qu'elle n'a pas pu refacturer, faute de pouvoir prouver l'état de la machine au départ. Cette fonctionnalité impose une preuve photographique systématique, au départ comme au retour, et la rattache à la réservation sans manipulation de fichiers : le salarié prend les photos avec son téléphone et elles apparaissent directement sur le poste de l'agence.

Elle complète le cycle de réservation défini par la feature 001 (confirmée → en cours → clôturée) : la sortie et la clôture y deviennent conditionnées aux photos.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Prendre les photos de départ avec son téléphone et débloquer la sortie (Priority: P1)

Le jour du départ, le salarié ouvre la réservation sur le poste de l'agence et lance la prise de photos de départ. Le poste affiche un QR code et la liste des vues à photographier pour cette catégorie de machine. Le salarié scanne le QR code avec son téléphone, qui ouvre une page de prise de photos sans connexion ni application à installer. Il photographie chaque vue demandée ; chaque photo apparaît sur le poste au fur et à mesure. Tant qu'une vue requise n'a pas de photo, le bouton de sortie reste bloqué.

**Why this priority**: C'est la preuve d'état au départ qui permet de refacturer un dégât. Sans elle, la fonctionnalité n'a pas de valeur.

**Independent Test**: Sur une réservation confirmée d'une nacelle (vues requises : avant, arrière, gauche, droite, compteur d'heures), envoyer 4 photos depuis le téléphone : la sortie reste bloquée et le poste indique la vue manquante. Envoyer la 5ᵉ : la sortie se débloque.

**Acceptance Scenarios**:

1. **Given** une réservation confirmée dont la date de début est atteinte, **When** le salarié lance la prise de photos de départ, **Then** le poste affiche un QR code et la liste des vues requises pour la catégorie de la machine, toutes marquées « manquante ».
2. **Given** le QR code affiché, **When** le salarié le scanne avec son téléphone, **Then** le téléphone ouvre une page indiquant la machine (référence), le client et l'étape « départ », et listant les vues à photographier, sans demander de connexion.
3. **Given** la page ouverte sur le téléphone, **When** le salarié photographie la vue « avant », **Then** la photo apparaît sur le poste dans la vue « avant » en moins de 5 secondes, sans action sur le poste.
4. **Given** au moins une vue requise sans photo, **When** le salarié tente d'enregistrer la sortie, **Then** l'outil refuse et liste les vues manquantes.
5. **Given** chaque vue requise a au moins une photo, **When** le salarié enregistre la sortie, **Then** la sortie est acceptée (sous réserve des règles de la feature 001) et les photos de départ sont figées : plus aucune photo ne peut être ajoutée, remplacée ou supprimée pour le départ.

---

### User Story 2 - Prendre les photos de retour et bloquer la clôture tant qu'elles manquent (Priority: P1)

Au retour de la machine, le salarié lance la prise de photos de retour sur le poste. Le même parcours QR code → téléphone → photos en direct s'applique, avec les mêmes vues qu'au départ. La clôture de la réservation reste bloquée tant qu'une vue requise n'a pas de photo de retour.

**Why this priority**: Sans photo de retour, la photo de départ ne prouve rien : c'est la paire départ / retour qui établit le dégât.

**Independent Test**: Sur une réservation en cours, tenter d'enregistrer le retour sans photos : refus. Envoyer toutes les vues de retour : le retour s'enregistre.

**Acceptance Scenarios**:

1. **Given** une réservation en cours, **When** le salarié lance la prise de photos de retour, **Then** le poste affiche un nouveau QR code lié à l'étape « retour » et les mêmes vues que celles exigées au départ.
2. **Given** au moins une vue de retour sans photo, **When** le salarié tente d'enregistrer le retour, **Then** l'outil refuse et liste les vues manquantes.
3. **Given** toutes les vues de retour photographiées, **When** le salarié enregistre le retour (en état ou à l'atelier), **Then** la réservation est clôturée et les photos de retour sont figées.
4. **Given** un QR code de départ, **When** il est scanné alors que la réservation est en cours, **Then** le téléphone indique que le lien n'est plus valable ; aucune photo ne peut être ajoutée au départ.

---

### User Story 3 - Comparer départ et retour et signaler un dégât (Priority: P2)

Pendant ou après la prise des photos de retour, le salarié affiche côte à côte, vue par vue, la photo de départ et la photo de retour. S'il constate un dégât, il le signale en indiquant la vue concernée et un commentaire. La réservation est alors marquée « à refacturer », ce qui la fait apparaître dans une liste dédiée.

**Why this priority**: C'est ce qui transforme les photos en refacturation effective. Il dépend des US1 et US2 et peut venir juste après.

**Independent Test**: Sur une réservation dont les photos de départ et de retour sont complètes, signaler un dégât sur la vue « gauche » : la réservation apparaît dans la liste « à refacturer » avec la vue, le commentaire, l'auteur et la date.

**Acceptance Scenarios**:

1. **Given** une réservation avec photos de départ et de retour, **When** le salarié ouvre la comparaison, **Then** chaque vue affiche la photo de départ à gauche et la photo de retour à droite, avec la date et l'auteur de chaque prise.
2. **Given** la comparaison ouverte, **When** le salarié signale un dégât sur la vue « gauche » avec le commentaire « bras rayé », **Then** la réservation est marquée « à refacturer » et le dégât est enregistré avec sa vue, son commentaire, son auteur et sa date.
3. **Given** des réservations marquées « à refacturer », **When** un salarié consulte la liste des dégâts à traiter, **Then** il voit, pour chaque réservation, la machine, le client, les dégâts signalés et un accès à la comparaison photo.
4. **Given** un dégât signalé, **When** un salarié le marque « traité », **Then** il sort de la liste des dégâts à traiter ; la réservation reste consultable avec son historique.

---

### User Story 4 - Paramétrer les vues requises par catégorie de machine (Priority: P2)

Un salarié définit, pour chaque catégorie de machine, la liste des vues à photographier (par exemple, pour une nacelle : avant, arrière, gauche, droite, compteur d'heures, panier). Cette liste s'applique au départ comme au retour.

**Why this priority**: Une liste par défaut suffit pour démarrer (US1 et US2) ; l'adaptation par catégorie vient ensuite.

**Independent Test**: Ajouter la vue « godet » à la catégorie « mini-pelle » : la prise de photos de départ suivante d'une mini-pelle exige cette vue.

**Acceptance Scenarios**:

1. **Given** une catégorie sans paramétrage propre, **When** une prise de photos démarre pour une machine de cette catégorie, **Then** la liste de vues par défaut s'applique (avant, arrière, gauche, droite, compteur d'heures).
2. **Given** la catégorie « mini-pelle », **When** un salarié lui ajoute la vue « godet », **Then** les prises de photos démarrées ensuite pour une mini-pelle exigent la vue « godet ».
3. **Given** une réservation dont les photos de départ ont été prises avec une liste de vues, **When** la liste de la catégorie est modifiée avant le retour, **Then** le retour exige les vues photographiées au départ, et non la nouvelle liste.
4. **Given** une catégorie, **When** un salarié tente de lui retirer toutes ses vues, **Then** l'outil refuse : au moins une vue est requise.

---

### Edge Cases

- **Prise de photos jamais lancée** : si le salarié tente la sortie (ou le retour) sans avoir jamais affiché de QR code, l'outil refuse et liste toutes les vues requises comme manquantes.
- **QR code expiré** : un lien scanné après son délai de validité affiche « lien expiré, régénérez le QR code depuis le poste » ; le poste permet de générer un nouveau QR code, l'ancien devient invalide.
- **Plusieurs téléphones** : un même QR code scanné sur deux téléphones pendant sa validité accepte les photos des deux ; elles arrivent toutes dans la même réservation et la même étape.
- **Photo ratée** : tant que l'étape n'est pas validée (sortie ou retour non enregistré), le salarié peut supprimer une photo depuis le téléphone ou le poste, et en reprendre une ; une vue peut avoir plusieurs photos.
- **Réseau mobile faible ou coupé** : une photo dont l'envoi échoue est signalée « non envoyée » sur le téléphone avec possibilité de réessayer ; le poste ne la compte pas tant qu'elle n'est pas reçue.
- **Fichier qui n'est pas une photo ou trop volumineux** : refusé avec un message clair sur le téléphone.
- **Réservation annulée pendant la prise de photos** : le lien devient invalide ; les photos déjà reçues sont supprimées 1 an après leur réception (FR-023).
- **Retour anticipé** (feature 001) : la prise de photos de retour est possible dès que la réservation est en cours.
- **Réservation sortie avant la mise en service de cette fonctionnalité** : sa clôture n'exige pas de photos de départ (il n'y en a pas), mais exige les photos de retour.
- **Liste de vues modifiée avant la sortie** : tant que la liste d'une réservation n'est pas figée (aucun QR code lancé, aucune sortie acceptée), une modification des vues de la catégorie s'applique à cette réservation, même après une sortie refusée ; après, elle ne s'applique plus.
- **Lien partagé à un tiers** : le lien ne donne accès qu'à l'ajout de photos sur cette réservation et cette étape ; il ne montre ni coordonnées complètes du client ni autres réservations.

## Clarifications

### Session 2026-10-10

- Q: À quel moment la liste des vues d'une réservation est-elle figée, si la sortie est tentée sans QR code ou si la réservation est sortie avant la mise en service ? → A: Au premier de ces moments : lancement d'une prise de photos, ou sortie ou retour acceptés. Une sortie ou un retour refusés ne figent rien, le contrôle se faisant dans la transaction annulée par le refus (FR-003, révisé après branchement sur la 001).
- Q: Combien de temps garder les QR codes (sessions de prise de photos) expirés ou révoqués ? → A: 30 jours, puis suppression automatique s'ils n'ont aucune photo ; ceux qui portent des photos suivent la durée de vie de ces photos (FR-023, audit de conformité Xefi).
- Q: Deux vues d'une même catégorie dont les noms ne diffèrent que par la casse sont-elles un doublon ? → A: Oui, la comparaison ignore les majuscules et les espaces en début et fin (FR-001).

## Requirements *(mandatory)*

### Functional Requirements

**Vues requises**

- **FR-001**: Le système DOIT permettre de définir, pour chaque catégorie de machine, une liste ordonnée de vues à photographier (nom de la vue) ; au moins une vue est requise par catégorie ; deux vues d'une même catégorie ne peuvent pas porter le même nom, sans tenir compte des majuscules ni des espaces en début et fin (« Gauche » et « gauche » sont un doublon).
- **FR-002**: Le système DOIT appliquer une liste de vues par défaut (avant, arrière, gauche, droite, compteur d'heures) à toute catégorie sans paramétrage propre.
- **FR-003**: Le système DOIT figer la liste des vues requises d'une réservation au premier de ces moments : le lancement d'une prise de photos (départ, ou retour pour une réservation sortie avant la mise en service, FR-018), ou une sortie ou un retour acceptés ; une sortie ou un retour refusés ne figent rien (les vues manquantes listées sont celles qui seraient figées à cet instant) ; une fois figée, la liste ne change plus et le retour exige exactement ces vues.

**QR code et lien temporaire**

- **FR-004**: Le système DOIT, à la demande d'un salarié connecté sur le poste, générer un QR code portant un lien lié à une seule réservation et une seule étape (départ ou retour).
- **FR-005**: Le lien DOIT être valable 30 minutes au plus et devenir invalide dès que l'étape est validée, que la réservation est annulée, ou qu'un nouveau QR code est généré pour la même réservation et la même étape.
- **FR-006**: Le lien DOIT ouvrir une page de prise de photos dans le navigateur du téléphone sans connexion ni installation d'application.
- **FR-007**: La page ouverte par le lien DOIT afficher uniquement la référence de la machine, le nom du client, l'étape et les vues à photographier avec leur état (manquante / reçue) ; elle ne DOIT donner accès à aucune autre donnée.
- **FR-008**: Le lien DOIT être impossible à deviner à partir d'un autre lien ou d'un identifiant de réservation.
- **FR-009**: Le système DOIT permettre la prise de photos de départ seulement pour une réservation confirmée dont la date de début est atteinte, et la prise de photos de retour seulement pour une réservation en cours.

**Photos**

- **FR-010**: Le système DOIT permettre de rattacher une ou plusieurs photos à chaque vue requise, depuis l'appareil photo du téléphone.
- **FR-011**: Le système DOIT refuser tout fichier qui n'est pas une image ou qui dépasse la taille maximale autorisée, avec un message explicite.
- **FR-012**: Chaque photo reçue DOIT apparaître sur le poste qui affiche la réservation en moins de 5 secondes, sans action de l'utilisateur.
- **FR-013**: Le système DOIT enregistrer pour chaque photo : la réservation, l'étape, la vue, la date et l'heure de réception, et le salarié qui a généré le QR code.
- **FR-014**: Tant que l'étape n'est pas validée, le système DOIT permettre de supprimer une photo et d'en ajouter une autre ; après validation, les photos de l'étape DOIVENT être non modifiables.
- **FR-015**: Le téléphone DOIT signaler toute photo dont l'envoi a échoué et permettre de la renvoyer.

**Blocages**

- **FR-016**: Le système DOIT refuser l'enregistrement d'une sortie tant qu'une vue requise n'a pas au moins une photo de départ, et lister les vues manquantes, y compris quand aucune prise de photos n'a jamais été lancée pour la réservation.
- **FR-017**: Le système DOIT refuser l'enregistrement d'un retour (clôture) tant qu'une vue requise n'a pas au moins une photo de retour, et lister les vues manquantes, y compris quand aucune prise de photos de retour n'a jamais été lancée.
- **FR-018**: Pour une réservation sortie avant la mise en service de cette fonctionnalité, le système DOIT exiger les photos de retour selon la liste de vues de sa catégorie, sans exiger de photos de départ.

**Comparaison et dégâts**

- **FR-019**: Le système DOIT présenter, pour une réservation, chaque vue avec ses photos de départ et de retour côte à côte, avec la date et l'auteur de chaque prise.
- **FR-020**: Le système DOIT permettre de signaler un ou plusieurs dégâts sur une réservation dont les photos de retour ont été prises, chacun portant la vue concernée, un commentaire obligatoire, l'auteur et la date.
- **FR-021**: Une réservation portant au moins un dégât non traité DOIT être marquée « à refacturer » et apparaître dans une liste des dégâts à traiter, consultable depuis toutes les agences.
- **FR-022**: Le système DOIT permettre de marquer un dégât « traité » ; une réservation dont tous les dégâts sont traités n'est plus marquée « à refacturer ». L'issue finale d'un dégât (refacturé, ou non refacturé avec motif) est définie par la feature 003 (FR-016 de la 003) ; « Marquer traité » reste l'action par défaut tant que la 003 n'est pas installée, et disparaît de tous les écrans dès qu'elle l'est.

**Rétention et traçabilité**

- **FR-023**: Le système DOIT supprimer automatiquement les photos d'une réservation 1 an après sa clôture, sauf si elle porte un dégât non traité ; dans ce cas, la suppression intervient 1 an après le traitement du dernier dégât. Les photos d'une réservation annulée sont supprimées 1 an après leur réception. Les autorisations de prise de photos (QR codes) expirées ou révoquées depuis plus de 30 jours et auxquelles aucune photo n'est rattachée sont supprimées automatiquement ; l'historique de la réservation (FR-024) en garde la trace.
- **FR-024**: Le système DOIT conserver dans l'historique de la réservation la génération de chaque QR code, la réception et la suppression de chaque photo, le signalement et le traitement de chaque dégât (auteur, date).

### Key Entities

- **Vue requise** : un angle ou un détail à photographier (ex. « avant », « compteur d'heures ») ; appartient à la liste d'une catégorie de machine, avec un ordre d'affichage.
- **Session de prise de photos** : l'autorisation temporaire matérialisée par le QR code ; liée à une réservation et une étape (départ ou retour), avec une date d'expiration, un état (active, expirée, ou révoquée parce que remplacée par un nouveau QR code, parce que l'étape est validée ou parce que la réservation est annulée) et le salarié qui l'a générée.
- **Photo** : une image rattachée à une réservation, une étape et une vue ; date de réception, session d'origine.
- **Dégât** : un constat sur une réservation ; vue concernée, commentaire, auteur, date, état (à traiter, traité) et auteur / date du traitement.
- **Réservation** (feature 001) : enrichie de la liste de vues figée au départ et de l'indicateur « à refacturer ».
- **Catégorie de machine** (feature 001) : enrichie de sa liste de vues requises.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 % des sorties enregistrées après la mise en service ont une photo pour chaque vue requise.
- **SC-002**: 100 % des clôtures enregistrées après la mise en service ont une photo de retour pour chaque vue requise.
- **SC-003**: Un salarié prend l'ensemble des photos de départ d'une machine (5 vues) en moins de 3 minutes, du lancement sur le poste au déblocage de la sortie.
- **SC-004**: Une photo prise sur le téléphone apparaît sur le poste en moins de 5 secondes dans des conditions de réseau mobile normales.
- **SC-005**: 100 % des dégâts signalés sont retrouvables dans la liste des dégâts à traiter avec la paire de photos départ / retour correspondante.
- **SC-006**: Sur les 12 mois suivant la mise en service, le montant des réparations non refacturées baisse d'au moins 80 % par rapport aux 85 000 € de l'année précédente.

## Out of Scope

- Chiffrage du dégât, issue « refacturé / non refacturé » et transmission à la facturation (feature 003, `specs/003-transmission-facturation/`).
- Caution des particuliers.
- Envoi de l'attestation VGP par e-mail.
- Application mobile native à installer.
- Détection automatique des dégâts par analyse d'image.
- Envoi des photos au client.
- Droits différenciés par rôle (tous les salariés ont les mêmes droits, comme dans la feature 001).

## Assumptions

- Le salarié qui fait la sortie ou le retour dispose d'un smartphone avec appareil photo, navigateur récent et accès internet mobile (ou au wifi de l'agence).
- Le téléphone n'a pas besoin d'être connecté au compte du salarié : l'autorisation vient du QR code généré par un salarié connecté sur le poste, qui est enregistré comme auteur des photos.
- La validité de 30 minutes couvre une prise de photos normale ; au-delà, le salarié régénère un QR code.
- Une taille maximale de 15 Mo par photo couvre les photos de smartphone actuelles.
- La liste de vues par défaut (avant, arrière, gauche, droite, compteur d'heures) convient à la majorité des catégories ; elle est ajustable par catégorie.
- La durée de conservation de 1 an après clôture est une proposition à confirmer avec M. Vallet.
- Cette fonctionnalité s'appuie sur la feature 001 (machines, catégories, réservations, sortie, retour, historique, mise à jour en temps réel entre postes).
