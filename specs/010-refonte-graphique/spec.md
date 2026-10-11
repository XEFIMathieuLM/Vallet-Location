# Feature Specification: Refonte graphique

**Feature Branch**: `010-refonte-graphique`

**Created**: 2026-10-10

**Status**: Clarified

**Input**: User description: « Applique un web design par rapport à ce fichier, s'il faut déplace les composants, je veux un magnifique design. » Le fichier de référence est l'analyse d'un design system web de constructeur automobile, copiée dans [design-reference.md](design-reference.md). Décisions déjà prises par l'utilisateur : on reprend le langage visuel du fichier (palette noir / blanc et un seul accent jaune, géométrie carrée, typographie grasse et serrée, composants), jamais la marque ; l'identité reste « Vallet Location » ; tous les écrans sont concernés (salariés, espace client, pages de connexion, e-mails si c'est simple) ; la mise en page peut être réorganisée si c'est plus beau et reste utilisable ; aucun comportement ni aucune règle métier ne change. S'appuie sur les features 001 à 009, toutes livrées.

## Contexte

L'outil a été construit feature par feature sur l'apparence par défaut du kit de démarrage : gris neutres, coins arrondis, ombres légères, logo générique du kit et police par défaut. Il fonctionne, mais il ne ressemble à rien de reconnaissable : le salarié qui passe du planning à la facturation ne perçoit aucune hiérarchie entre l'action principale et le reste, et l'espace client ouvert aux clients en feature 009 a l'air d'un écran d'administration.

L'utilisateur a choisi une direction : un canevas blanc, une encre noire, des bandes de couleur franches plutôt que des ombres, des coins carrés, une typographie grasse et serrée, et un seul accent jaune réservé à ce qui compte le plus sur l'écran. Cette feature applique ce langage à tout l'outil, sans toucher à ce que fait l'outil : chaque écran garde ses informations, ses actions, ses règles et ses messages, seule leur présentation change (et leur disposition quand elle y gagne).

## Clarifications

### Session 2026-10-10

- Q: Quelle structure de navigation pour les écrans salariés ? → A: Une colonne latérale noire avec toutes les entrées visibles, groupées comme aujourd'hui, l'entrée courante en blanc inversé, et une barre blanche de 60 px au-dessus du contenu (maquette 1) ; repliée dans un menu sur téléphone (FR-012).
- Q: Faut-il garder un mode sombre ? → A: Oui : le choix clair / sombre / système reste dans les paramètres ; le thème clair suit la référence, le thème sombre est un thème noir / jaune cohérent (fonds `#000000` / `#111111`, texte blanc, même accent jaune à texte noir), soumis aux mêmes exigences de contraste (FR-020).
- Q: La direction du tableau de bord convient-elle ? → A: Non, la première maquette posait un problème de design ; l'utilisateur autorise à changer et à refaire complètement les composants du tableau de bord. Nouvelle composition (maquette 1 v2) : en-tête blanc, opérations du jour en quatre tuiles avec leurs listes (une seule jaune, celle des retours en retard quand il y en a), « À traiter » en bloc noir avec les compteurs à traiter mis en avant en jaune, départs des 7 prochains jours en frise par jour, état du parc avec barre de répartition, VGP à surveiller en liste (US3, FR-013).
- Q: La maquette v2 règle-t-elle le problème ? → A: Mieux, mais il manque des images. Choix : des pictogrammes de machines dessinés pour l'outil (une silhouette géométrique par famille de catégorie : nacelle, mini-pelle, compacteur, échafaudage, plus un repli neutre), utilisés dans un bandeau d'accueil illustré du tableau de bord, dans les lignes de réservation, dans la frise des départs et dans les cartes de l'espace client ; pas de photographie. La composition de la maquette 1 v3 est validée (FR-021).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Un langage visuel unique sur tout l'outil (Priority: P1)

Où qu'il soit dans l'outil, l'utilisateur retrouve les mêmes fondations : fond blanc, texte noir, une seule police grasse pour les titres et régulière pour le texte, des coins carrés (boutons très légèrement adoucis, cartes et tuiles à angle droit), aucune ombre sur les cartes, des séparateurs fins, et un seul accent jaune avec texte noir, réservé à l'action principale de l'écran. Les boutons, champs, listes déroulantes, cases, tableaux, badges, alertes, fenêtres modales et notifications suivent tous ce langage.

**Why this priority**: C'est la demande. Une fois les fondations en place, tous les écrans changent d'apparence d'un coup, y compris ceux qui ne seront pas retouchés un par un.

**Independent Test**: Ouvrir trois écrans de domaines différents (réservations, facturation, cautions) : même police, mêmes boutons, mêmes champs, mêmes badges ; sur chacun, au plus une action en jaune, et c'est l'action principale de l'écran.

**Acceptance Scenarios**:

1. **Given** n'importe quel écran salarié ou client, **When** il s'affiche, **Then** les titres sont dans la police de titrage en gras serré, le texte courant dans la même famille en poids régulier, sur fond blanc et en encre noire.
2. **Given** un écran avec une action principale (enregistrer, créer, confirmer, envoyer la demande, se connecter), **When** il s'affiche, **Then** cette action est un bouton jaune à texte noir, et c'est le seul bouton jaune de l'écran ; les autres actions sont des boutons noirs ou à contour.
3. **Given** un champ de saisie, **When** l'utilisateur y place le curseur au clavier ou à la souris, **Then** un indicateur de focus visible et contrasté apparaît.
4. **Given** une carte, une tuile ou un tableau, **When** il s'affiche, **Then** ses angles sont droits et il n'a pas d'ombre ; sa séparation du fond vient d'un trait fin ou d'un changement de couleur de fond.
5. **Given** un statut (réservation, machine, caution, transmission, VGP, vente, demande), **When** il s'affiche en badge, **Then** le badge est une pastille arrondie dont la couleur suit la palette sémantique désaturée (erreur, alerte, succès, info, neutre), avec un texte lisible.
6. **Given** un texte, **When** il s'affiche, **Then** il n'est jamais écrit en jaune sur fond blanc, et tout texte respecte un contraste AA avec son fond.

---

### User Story 2 - Une navigation salariés claire et à l'identité Vallet Location (Priority: P1)

Le salarié retrouve toutes les entrées de navigation qu'il a aujourd'hui (tableau de bord, dégâts, vues photos, disponibilités, réservations, planning, facturation, cautions, ventes, VGP, grands comptes, demandes en ligne avec leur compteur, machines, salariés), regroupées par domaine, avec l'entrée courante clairement marquée, dans une structure de navigation redessinée. Un logotype typographique « Vallet Location » remplace le logo du kit de démarrage. Son menu personnel (paramètres, déconnexion) reste accessible. Sur téléphone, la navigation se replie dans un menu.

**Why this priority**: La navigation est présente sur tous les écrans salariés : c'est elle qui donne l'identité de l'outil, et c'est elle qui ne doit perdre aucune entrée.

**Independent Test**: Se connecter avec un salarié qui a toutes les permissions : chaque entrée de navigation existante est présente et mène au même écran qu'avant ; l'entrée courante est distinguée ; un salarié sans la permission d'un domaine ne voit pas ses entrées. Sur un téléphone, ouvrir le menu, choisir « Planning » : le planning s'ouvre.

**Acceptance Scenarios**:

1. **Given** un salarié connecté, **When** un écran salarié s'affiche, **Then** la navigation présente le logotype « Vallet Location », les entrées auxquelles il a droit groupées par domaine, et son menu personnel.
2. **Given** un salarié sur l'écran des réservations, **When** il regarde la navigation, **Then** l'entrée « Réservations » est marquée comme courante autrement que par la seule couleur (graisse, fond inversé ou marqueur).
3. **Given** des demandes en ligne en attente, **When** la navigation s'affiche, **Then** le compteur des demandes en attente reste visible à côté de l'entrée correspondante.
4. **Given** un salarié sans permission sur la facturation, **When** la navigation s'affiche, **Then** les entrées de facturation n'apparaissent pas (comme aujourd'hui).
5. **Given** un écran de 375 px de large, **When** le salarié ouvre le menu, **Then** toutes ses entrées sont accessibles et l'écran ne défile pas horizontalement.
6. **Given** n'importe quel écran, **When** il s'affiche, **Then** aucun logo, nom, police ou image d'une marque tierce n'apparaît.

---

### User Story 3 - Un tableau de bord en tuiles et en bandes (Priority: P2)

Le tableau de bord de la feature 008 garde exactement ses sections, ses chiffres, ses liens et son filtre d'agence, mais ses composants sont refaits (maquette 1 v3) : le choix de l'agence dans la barre de 60 px ; un bandeau d'accueil en deux blocs (à gauche, le titre d'accueil et la phrase qui résume les opérations du jour ; à droite, un bloc noir illustré de pictogrammes de machines) ; les quatre listes d'opérations du jour (départs, retours, retours en retard, conflits) en tuiles à angle droit qui montrent leur nombre en grand et leur liste ; les compteurs « à traiter » dans un bloc noir, ceux qui demandent une action mis en avant en jaune ; les départs des 7 prochains jours en frise, une colonne par jour ; l'état du parc en chiffres avec une barre de répartition ; les VGP à surveiller en liste. Une seule tuile au plus est jaune : celle des retours en retard quand il y en a.

**Why this priority**: C'est le premier écran vu chaque matin par 85 salariés, et l'écran où la direction visuelle a le plus d'effet ; il vient après les fondations et la navigation dont il dépend.

**Independent Test**: Afficher le tableau de bord avec des départs, des retours en retard et des éléments à traiter : chaque section de la feature 008 est présente avec les mêmes chiffres et les mêmes liens qu'avant ; changer d'agence met à jour les mêmes sections qu'avant.

**Acceptance Scenarios**:

1. **Given** le tableau de bord, **When** il s'affiche, **Then** il présente les mêmes sections, compteurs, listes et liens que la feature 008, composés en tuiles à angle droit (claires, sombres, et au plus une jaune).
2. **Given** des retours en retard, **When** le tableau de bord s'affiche, **Then** la tuile des retours en retard est la tuile jaune et montre leur nombre et leur liste ; sans retour en retard, aucune tuile n'est jaune.
3. **Given** des départs dans les 7 prochains jours, **When** le tableau de bord s'affiche, **Then** ils apparaissent en frise, une colonne par jour, chaque départ menant à sa réservation ; sur téléphone, les jours s'empilent.
4. **Given** des compteurs « à traiter », **When** ils s'affichent, **Then** chaque compteur est un grand chiffre lisible dans une tuile qui mène au même écran qu'avant.
5. **Given** le choix d'une autre agence, **When** il est fait, **Then** les sections se mettent à jour comme avant.
6. **Given** un écran de 375 px, **When** le tableau de bord s'affiche, **Then** les tuiles s'empilent sur une colonne sans défilement horizontal.

---

### User Story 4 - Un espace client à l'allure d'un site public (Priority: P2)

Le client de la feature 009 arrive sur un espace qui ressemble à un site de loueur et non à un écran d'administration : barre de navigation blanche avec le logotype, bande d'en-tête noire avec un titre accrocheur et le formulaire de recherche, résultats présentés en cartes de machines à angle droit (référence, catégorie, agence, prix indicatif, action « Demander »), ses demandes et réservations en listes nettes, et un pied de page noir. Les pages de connexion, d'inscription et de mot de passe de l'espace client suivent le même style.

**Why this priority**: L'espace client est la vitrine de Vallet Location auprès de ses clients ; il vient après les fondations communes.

**Independent Test**: Se connecter comme client, chercher une catégorie disponible : la bande noire, les cartes et le pied de page s'affichent ; envoyer une demande, l'annuler, ouvrir ses réservations : tout fonctionne comme en feature 009.

**Acceptance Scenarios**:

1. **Given** un client connecté, **When** il ouvre son espace, **Then** il voit une barre de navigation blanche avec le logotype et ses entrées (rechercher, mes demandes, mes réservations, compte), une bande d'en-tête noire et un pied de page noir.
2. **Given** une recherche avec des machines disponibles, **When** les résultats s'affichent, **Then** chaque machine est une carte à angle droit avec les mêmes informations et la même action qu'en feature 009 ; le prix reste présenté comme indicatif.
3. **Given** une recherche sans résultat ou un formulaire incomplet, **When** l'écran s'affiche, **Then** les mêmes messages qu'en feature 009 apparaissent, dans le nouveau style.
4. **Given** les pages de connexion, d'inscription, de mot de passe oublié et de confirmation d'adresse, **When** elles s'affichent, **Then** elles suivent le même langage visuel et portent le logotype et la mention « Espace client ».
5. **Given** un écran de 375 px, **When** n'importe quelle page de l'espace client s'affiche, **Then** elle ne défile pas horizontalement.

---

### User Story 5 - Des écrans métier conformes au nouveau langage (Priority: P2)

Chaque écran métier (disponibilités, réservations et leur détail avec photos, planning, dégâts, vues photos, facturation, cautions, ventes, VGP, grands comptes, bons de commande, demandes en ligne, prix indicatifs, machines, salariés, paramètres) applique les fondations : titre de page en gras serré, filtres et sous-navigation en pastilles quand ils existent, tableaux à séparateurs fins, sections du détail de réservation lisibles en blocs, états vides et alertes dans le nouveau style. Le planning garde sa grille, ses légendes et ses liens ; ses cases d'état utilisent la palette sémantique et ne portent plus de jaune d'accent.

**Why this priority**: Les fondations couvrent la majorité des composants ; ce passage traite ce qu'elles ne peuvent pas couvrir seules (couleurs codées en dur, mises en page propres à un écran).

**Independent Test**: Parcourir la liste des écrans de la section « Écrans concernés » à 1440 px et à 375 px : chacun suit le langage visuel, garde ses informations et ses actions, et aucun ne défile horizontalement à 375 px (hors grilles larges dans leur propre zone défilante).

**Acceptance Scenarios**:

1. **Given** le planning, **When** il s'affiche, **Then** chaque état de case (libre, réservée, atelier, en panne, VGP non valide) reste distinguable par sa couleur et par sa lettre, avec une légende, et aucune case n'utilise le jaune d'accent.
2. **Given** le planning sur un écran de 375 px, **When** il s'affiche, **Then** la grille défile horizontalement dans sa propre zone, la page elle-même ne défile pas horizontalement.
3. **Given** le détail d'une réservation, **When** il s'affiche, **Then** toutes ses sections (informations, photos, facturation, caution, bon de commande, attestation VGP, origine en ligne) sont présentes, dans le même ordre qu'avant, sous forme de blocs séparés par des traits fins ou des bandes.
4. **Given** un écran avec un refus métier, une alerte ou une confirmation, **When** le message s'affiche, **Then** son texte est inchangé et sa présentation suit la palette sémantique.
5. **Given** un écran salarié vide (aucun résultat), **When** il s'affiche, **Then** l'état vide garde son texte et ses actions, dans le nouveau style.

---

### User Story 6 - Pages de connexion salariés et paramètres (Priority: P3)

Les pages de connexion, de mot de passe oublié, de réinitialisation, de double authentification et de confirmation du mot de passe des salariés, ainsi que leurs paramètres (profil, sécurité, apparence), suivent le langage visuel avec le logotype « Vallet Location ».

**Why this priority**: Pages peu fréquentes mais premières vues ; elles bénéficient surtout des fondations.

**Independent Test**: Se déconnecter, afficher la connexion, se connecter, ouvrir les paramètres : même langage, mêmes champs, même comportement.

**Acceptance Scenarios**:

1. **Given** la page de connexion des salariés, **When** elle s'affiche, **Then** elle porte le logotype « Vallet Location », ses champs à bordure basse et un bouton « Se connecter » jaune.
2. **Given** une erreur de connexion, **When** elle s'affiche, **Then** le message est inchangé et présenté dans la couleur d'erreur du langage visuel.

---

### User Story 7 - E-mails aux couleurs de Vallet Location (Priority: P3)

Les e-mails envoyés aux clients (confirmation d'adresse, réinitialisation du mot de passe, demande confirmée, refusée ou expirée, attestation VGP) utilisent le même langage : logotype texte « Vallet Location », fond blanc, encre noire, bouton d'action jaune à texte noir et coins carrés, pied de page sobre. Leur contenu ne change pas.

**Why this priority**: Utile pour la cohérence, mais secondaire, et retenu seulement s'il reste simple.

**Independent Test**: Déclencher l'envoi d'une demande confirmée et d'une attestation VGP en environnement de test : les deux e-mails ont le nouveau style et le même contenu qu'avant.

**Acceptance Scenarios**:

1. **Given** un e-mail envoyé à un client, **When** il est ouvert, **Then** il porte le logotype texte « Vallet Location », un bouton d'action jaune à texte noir, et le même texte qu'avant.

---

### Edge Cases

- Un écran a deux actions d'égale importance (confirmer / refuser une demande en ligne) : une seule est jaune (la plus fréquente) ou aucune, l'autre est noire ou à contour ; une action destructrice (supprimer, refuser, annuler) n'est jamais jaune et garde sa couleur d'erreur.
- Une fenêtre modale s'ouvre au-dessus d'un écran qui a déjà un bouton jaune : dans la modale, l'action principale de la modale peut être jaune, puisqu'elle est seule au premier plan.
- Un libellé long (raison sociale, nom de catégorie) dans une carte ou une tuile étroite : il passe à la ligne ou est tronqué avec le texte complet accessible, sans déborder.
- Un titre de page long sur téléphone avec l'interligne serré du titrage : les lignes ne se chevauchent pas.
- La police de titrage ne se charge pas (réseau coupé) : l'outil reste lisible avec une police de secours de la même famille de forme.
- Un salarié a choisi le mode sombre dans ses paramètres avant la refonte : il retrouve le thème sombre noir / jaune (FR-020) ; la colonne latérale noire se distingue du contenu par un séparateur et une surface `#111111`.
- Un statut sans couleur sémantique évidente (brouillon, annulée) : badge neutre noir sur gris clair.
- Les grilles et tableaux larges (planning, transmissions, liste des machines) sur téléphone : défilement horizontal dans leur propre zone uniquement.
- La page de prise de photos au téléphone (lien envoyé par QR code, feature 002) : elle suit le langage visuel et reste utilisable d'une main sur téléphone.
- Les fichiers produits pour l'extérieur (exports de facturation, relevés, attestations PDF) ne font pas partie des écrans et ne changent pas.

## Requirements *(mandatory)*

### Functional Requirements

**Fondations**

- **FR-001**: L'outil MUST utiliser une palette unique : canevas blanc `#ffffff`, encre noire `#000000`, surfaces claire `#f7f7f7` et sombre `#000000` / `#111111`, séparateurs fins `#f2f2f2`, gris de texte secondaire de la référence (`#222222`, `#333333`, `#666666`) ; `#8a8a8a` et `#c4c4c4` sont réservés aux textes d'exemple et aux éléments désactivés.
- **FR-002**: L'outil MUST n'avoir qu'un accent, le jaune `#ffed00` (`#e6d200` à l'état pressé), toujours avec un texte noir, réservé : à l'action principale d'un écran ou d'une fenêtre modale (au plus une par écran visible), aux badges de nouveauté ou de compteur à traiter, et à au plus une tuile d'accent par zone d'écran. Le jaune n'est jamais une couleur de texte ni une couleur de statut.
- **FR-003**: Les statuts, alertes et messages MUST utiliser la palette sémantique désaturée de la référence (erreur `#be6464`, alerte `#f0ad4e`, succès `#8dc572`, info `#337ab7`) ou un neutre, en variantes claires ou foncées choisies pour que tout texte atteigne un contraste AA avec son fond.
- **FR-004**: L'outil MUST utiliser une seule famille typographique libre de droits proche de la référence (Inter Tight), servie par l'application elle-même, en poids 700 pour les titres avec un interligne de 0,95 et 400 pour le texte courant, selon l'échelle de la référence (titres de page, de section, de carte ; texte ; légendes ; boutons).
- **FR-005**: La géométrie MUST suivre la référence : boutons à coins de 2 px ; cartes, tuiles, tableaux, champs et bandes à angle droit ; forme de pastille réservée aux filtres, à la sous-navigation et aux badges ; aucune ombre sur les cartes et tuiles (une ombre légère reste permise pour les éléments flottants : menus déroulants, fenêtres modales, notifications).
- **FR-006**: Les composants MUST suivre la référence : bouton principal jaune, secondaire noir, tertiaire à contour noir (et leurs équivalents sur fond noir) ; champs de saisie sans bordure haute ni latérale, à bordure basse noire, renforcée au focus ; tableaux à séparateurs fins ; badges en pastille ; alertes en blocs à angle droit.
- **FR-007**: Tout élément interactif MUST avoir un indicateur de focus clavier visible, d'un contraste d'au moins 3:1 avec son fond, y compris sur les fonds noirs et jaunes.
- **FR-008**: Tout texte MUST atteindre un contraste AA avec son fond (4,5:1, 3:1 pour les grands titres) ; le jaune n'est jamais utilisé pour du texte sur fond blanc.
- **FR-009**: Les cibles tactiles MUST mesurer au moins 44 px de côté sur téléphone pour les boutons et les entrées de navigation.

**Identité**

- **FR-010**: L'outil MUST afficher un logotype typographique « Vallet Location » dessiné dans ce langage (lettres capitales grasses, sans pictogramme emprunté), en version sur fond clair et sur fond sombre, partout où apparaît aujourd'hui le logo du kit de démarrage (navigation salariés, espace client, pages de connexion, e-mails), ainsi qu'une icône d'onglet du navigateur dérivée de ce logotype.
- **FR-011**: Aucun écran ni e-mail MUST NOT contenir le nom, le logo, la police propriétaire, l'imagerie ou un élément distinctif de la marque dont la référence est extraite, ni rien qui fasse passer l'outil pour un de ses produits.

**Structure**

- **FR-012**: La navigation des salariés MUST conserver toutes les entrées existantes, leurs destinations, leurs conditions d'affichage par permission et le compteur des demandes en ligne en attente, regroupées par domaine comme aujourd'hui, dans une colonne latérale noire, avec l'entrée courante en blanc inversé (marquée autrement que par la seule couleur) et une barre blanche de 60 px au-dessus du contenu ; sur téléphone, la colonne se replie dans un menu.
- **FR-013**: Le tableau de bord MUST garder les sections, compteurs, listes, liens, filtre d'agence et rafraîchissement de la feature 008, recomposés en tuiles et en bandes (US3).
- **FR-014**: L'espace client MUST présenter une barre de navigation blanche de 60 px avec le logotype, une bande d'en-tête noire, les résultats de recherche en cartes de machines et un pied de page noir, en gardant les informations, actions et messages de la feature 009 (US4).
- **FR-015**: Les écrans métier MUST garder leurs informations, actions, filtres, ordre des sections et messages ; leur mise en page peut être réorganisée (filtres en pastilles, blocs, bandes) sans retirer ni ajouter d'action.
- **FR-021**: Les machines MUST être illustrées par des pictogrammes dessinés pour l'outil, dans le style géométrique du langage (silhouettes pleines, angles francs), un par famille de catégorie (nacelle, mini-pelle, compacteur, échafaudage) et un pictogramme neutre pour toute autre catégorie, en version noire sur fond clair et blanche sur fond noir ; ils sont décoratifs (ignorés par les lecteurs d'écran) et ne remplacent jamais le texte (référence, catégorie).
- **FR-016**: Le planning MUST garder sa grille, ses lettres d'état, sa légende et ses liens, avec des couleurs d'état tirées de la palette sémantique et un état « réservée » en noir, sans jaune d'accent.

**Contraintes**

- **FR-017**: La refonte MUST NOT changer aucun comportement, aucune règle métier, aucune permission, aucun texte existant ni aucune donnée : seules la présentation et la disposition changent. Les seuls textes ajoutés sont ceux que demande la nouvelle mise en page (accueil et résumé du jour du tableau de bord, pied de page de l'espace client, nombre de résultats), en français dans les fichiers de traduction, et ils ne présentent que des informations déjà calculées.
- **FR-018**: Chaque écran MUST s'afficher sans défilement horizontal de la page sur un écran de 375 px ; les grilles et tableaux larges défilent dans leur propre zone.
- **FR-019**: Les e-mails envoyés aux clients MUST reprendre le langage visuel (logotype texte, bouton jaune à texte noir, coins carrés) sans changer leur contenu.
- **FR-020**: L'outil MUST garder le choix d'apparence clair / sombre / système des paramètres : le thème clair suit la référence ; le thème sombre est noir / jaune (fonds `#000000` / `#111111`, surfaces `#1a1a1a`, texte blanc, séparateurs blancs à faible opacité, même accent jaune à texte noir) et respecte les mêmes exigences de contraste, de focus et d'usage du jaune (FR-002, FR-007, FR-008). L'espace client et les e-mails restent en thème clair.

### Écrans concernés

Salariés : connexion et pages de mot de passe / double authentification, tableau de bord, dégâts, vues photos (liste, édition), disponibilités, réservations (liste, création, détail avec ses sections photos, facturation, caution, bon de commande, attestation, origine), comparaison des photos, planning, transmissions, exports de secours, relevé, cautions en attente, montants de caution, ventes (liste, création, détail, historique d'une machine), rapports VGP (machines, détail), attestations à traiter, grands comptes, bons de commande manquants, demandes en ligne, prix indicatifs, machines (liste, création, modification, import), salariés, paramètres (profil, sécurité, apparence), page de prise de photos au téléphone.

Espace client : connexion, inscription, mot de passe oublié, réinitialisation, confirmation d'adresse, recherche, mes demandes, mes réservations, compte.

E-mails : confirmation d'adresse, réinitialisation, demande confirmée, refusée, expirée, attestation VGP.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 % des écrans listés dans « Écrans concernés » suivent le langage visuel, vérifié par une capture avant / après de chacun des écrans de référence (connexion, tableau de bord, planning, disponibilités, détail de réservation, transmissions, cautions, ventes, VGP, grands comptes, demandes en ligne, espace client : accueil, recherche, demandes, réservations) en largeur bureau et téléphone, en thème clair, plus le tableau de bord, le planning et le détail de réservation en thème sombre.
- **SC-002**: 0 écran listé ne défile horizontalement à 375 px de large (aujourd'hui : le planning et les trois pages de l'espace client défilent).
- **SC-003**: 100 % des couples texte / fond définis par le langage visuel atteignent le contraste AA, et 0 texte jaune sur fond blanc.
- **SC-004**: Sur chaque écran de référence, au plus un bouton jaune visible à la fois.
- **SC-005**: 100 % des tests existants passent ; seules des assertions portant uniquement sur l'apparence ont pu être adaptées, et chacune est listée.
- **SC-006**: 100 % des entrées de navigation existantes restent accessibles depuis chaque écran salarié, sur bureau comme sur téléphone, avec les mêmes conditions de permission.
- **SC-007**: 0 occurrence du nom, du logo, de la police ou de l'imagerie de la marque de la référence dans l'outil livré.
- **SC-008**: Un salarié habitué retrouve chacune de ses tâches quotidiennes (chercher une disponibilité, créer une réservation, sortir et rentrer une machine, traiter une transmission) sans aide, au premier essai, après la refonte.

## Assumptions

- La référence est une analyse d'un site marketing ; elle ne décrit ni tableaux de données, ni fenêtres modales, ni états d'erreur de formulaire. Ces composants sont dérivés de ses principes (angle droit, traits fins, noir / blanc, accent rare), et les écarts sont documentés dans le plan.
- Les bonnes pratiques d'accessibilité, d'états d'écran et de rédaction de l'interface de Xefi restent la référence pour ces sujets ; en cas de conflit purement visuel (tailles, rayons, couleurs), la référence de l'utilisateur prime et l'écart est documenté dans le plan.
- Il n'y a pas de photographie de machines dans l'outil : les emplacements « photo » de la référence (bandeaux, cartes) reçoivent les pictogrammes de FR-021 ; aucune image de stock n'est ajoutée.
- Une catégorie est rattachée à une famille de pictogramme d'après son nom (« Nacelle 12 m » → nacelle) ; une catégorie créée plus tard sans famille reconnue reçoit le pictogramme neutre.
- Le dégradé atmosphérique de la référence, propre à une gamme de véhicules, n'est pas repris.
- Les textes de l'interface ne changent pas ; une nouvelle accroche de l'espace client ou de la navigation, si la nouvelle mise en page en demande une, est ajoutée en français dans les fichiers de traduction et relue par l'utilisateur.
- Les documents produits (PDF, exports) sont hors périmètre.

## Out of Scope

- Toute modification de comportement, de règle métier, de permission ou de donnée.
- Les documents générés (exports de facturation, relevés, attestations PDF).
- L'ajout de photographies ou d'animations décoratives (les seules images ajoutées sont les pictogrammes de FR-021).
- Une charte graphique imprimée ou un site vitrine hors de l'outil.
