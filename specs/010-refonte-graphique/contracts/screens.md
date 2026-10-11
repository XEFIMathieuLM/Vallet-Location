# Contrat : écrans

Pour chaque écran : la mise en page cible, l'action jaune (au plus une visible hors modale) et ce qui change. Les actions qui ne sont plus jaunes passent en `filled` (noir) ou `outline`. Les actions destructrices restent `danger`. Les textes, actions, filtres et sections ne changent pas (FR-015, FR-017).

## Salariés

| Écran | Mise en page | Action jaune | Changements notables |
|---|---|---|---|
| Layout salariés | Colonne noire, barre de 60 px, en-tête mobile | — | R4 |
| Tableau de bord | Maquette 1 v3 ; sélecteur d'agence téléporté dans la barre de 60 px | aucune (le jaune est la tuile des retours en retard et les compteurs à traiter) | R6 ; `DayOperations::daySummary`, `upcomingDays` |
| Disponibilités | En-tête, filtres à bordure basse, résultats en tableau ; pictogramme de la catégorie dans la colonne référence | « Réserver » ne devient jaune que s'il est unique ; une action par ligne → `filled` | |
| Réservations (liste) | En-tête, filtres en pastilles quand ce sont des statuts, tableau | — | |
| Nouvelle réservation | Formulaire à bordure basse sur une colonne, récapitulatif machine avec pictogramme | « Confirmer la réservation » | |
| Détail de réservation | En-tête (référence en display, statut en badge), bloc d'informations en grille à filets, puis chaque section d'extension en bloc séparé par un bord haut 2 px noir, dans le même ordre | transition du statut courant (« Sortie » ou « Retour ») | Les actions des sections (caution, facturation des dégâts, remise de l'attestation, bon de commande, qualification) passent en `filled` hors modale ; leurs boutons de confirmation dans les modales restent jaunes. |
| Photos (comparaison, panneau) | Grille de photos à angle droit, légendes en surtitre | « Signaler le dégât » (modale) | |
| Planning | Filtres à bordure basse, légende, grille à filets, bord haut 2 px noir, défilement horizontal dans sa zone | — | `PlanningCellKind::cssClasses()` ; correction du défilement de page à 375 px (542 px aujourd'hui) |
| Dégâts | Liste à filets, actions par dégât | — | « Marquer résolu » par dégât → `filled` |
| Vues photos (liste, édition) | Liste, formulaire | « Ajouter » (édition) | |
| Transmissions, exports, relevé | Tableaux, statuts en badges | « Créer un export » (exports) | « Confirmer » dans la modale reste jaune |
| Cautions en attente, montants | Tableau ; formulaire des montants | « Enregistrer » (montants) | |
| Ventes (liste, nouvelle, détail, historique) | Tableau ; détail en blocs | « Nouvelle vente » (liste) ; « Mettre en vente » (nouvelle) ; détail : « Remettre au client » | « Accepter » par offre → `filled` ; enregistrement d'offre et formulaires de modale restent jaunes |
| Rapports VGP (machines, détail), attestations | Tableaux ; détail avec dépôt de rapport | « Déposer » (détail) | |
| Grands comptes, bons de commande manquants | Tableaux | — | |
| Demandes en ligne | Tableau des demandes ; filtre d'agence | — | « Confirmer » par ligne → `filled` ; dans la modale de confirmation, jaune |
| Prix indicatifs | Tableau éditable | « Enregistrer » s'il est unique | |
| Machines (liste, nouvelle, modification, import) | Tableau avec pictogramme ; formulaires | « Nouvelle machine » (liste) ; « Enregistrer » ; « Importer » | |
| Salariés | Formulaire de création et tableau | « Créer » | |
| Paramètres (profil, sécurité, apparence) | Sous-navigation en pastilles | « Enregistrer » (profil) ; « Modifier le mot de passe » (sécurité) | Les autres boutons de la page sécurité passent en `filled` hors modale |
| Prise de photos au téléphone | En-tête noir, boutons de 48 px | action de capture | |

## Espace client

| Écran | Mise en page | Action jaune |
|---|---|---|
| Layout | Barre blanche de 60 px, `main` pleine largeur, pied de page noir | — |
| Connexion, inscription, mot de passe oublié, réinitialisation, confirmation | Deux panneaux (R7, R8) | bouton d'envoi du formulaire |
| Recherche | Bandeau noir avec les filtres, nombre de résultats, cartes avec pictogramme | aucune hors modale (« Demander » en `filled` par carte) ; « Envoyer la demande » dans la modale |
| Mes demandes | Bandeau noir, liste à filets, statuts en badges | — (« Annuler » reste `danger` / `outline`) |
| Mes réservations | Bandeau noir, liste, téléchargement de l'attestation | — |
| Compte | Bandeau noir, deux formulaires | « Enregistrer » (profil) ; « Modifier le mot de passe » en `filled` |

## Autres

| Écran | Mise en page | Action jaune |
|---|---|---|
| Accueil `/` | Barre de 60 px, bande noire, deux tuiles (salariés, client), pied de page noir | — (les deux tuiles sont des liens de même poids) |
| Connexion salariés et pages de mot de passe / double authentification | Deux panneaux | bouton d'envoi |
| E-mails clients | Thème `vallet` | bouton d'action de l'e-mail |
