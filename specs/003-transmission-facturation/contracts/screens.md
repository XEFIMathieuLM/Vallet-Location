# Contrat : écrans

Tous les écrans exigent la permission `billing.manage`. Exception : les actions d'un dégât s'affichent dans les écrans de la 002, dont l'accès reste régi par `damages.manage` ; les actions elles-mêmes (« Refacturer », « Ne pas refacturer ») exigent en plus `billing.manage`, sinon elles ne sont ni affichées ni acceptées par le serveur. Textes en français par fichiers de traduction.

| Écran | Emplacement | Actions | Refus affichés | Exigences |
|-------|-------------|---------|----------------|-----------|
| Section « Facturation » du détail de réservation | section enregistrée dans le détail de `booking` | voir les périodes (dates, jours, type), les dégâts chiffrés ou non refacturés, l'état et la date de chaque transmission ; relancer une transmission en échec | — | FR-017, US1 |
| Actions d'un dégât | registre `DamageActions` d'`inspection`, rendu sur « Dégâts à traiter » et « Comparaison » (remplace « Marquer traité » sur les deux) | « Refacturer » : saisir montant HT et libellé, valider ; « Ne pas refacturer » : saisir un motif, valider | montant vide, nul ou négatif ; libellé vide ; motif vide ; dégât déjà réglé | US3, FR-012 à FR-016 |
| Transmissions à traiter | `/facturation/transmissions` | lister les transmissions `failed` et les `pending` depuis plus de 24 h (réservation, client, type, date, motif) ; renseigner la référence client ; relancer ; ouvrir la réservation | relance d'une transmission déjà transmise ou exportée | US2, FR-009, FR-010 |
| Exports de secours | `/facturation/exports` | produire un export de tout ce qui est en attente ou en échec ; télécharger un export passé (date, auteur, nombre de lignes) | aucun élément à exporter | US2, FR-022, FR-023 |
| Relevé | `/facturation/releve` | filtrer par agence et par période ; voir le nombre de locations transmises, le total des dégâts refacturés, les dégâts non refacturés et leurs motifs, les dégâts à traiter et leur ancienneté (en évidence au-delà de 7 jours) | — | US4, FR-018, FR-019 |
| Alerte | bandeau dans le layout de `app/`, visible sur tous les écrans | indiquer le nombre de transmissions à traiter ; lien vers l'écran des transmissions | — | FR-011 |

## Menu

Une entrée « Facturation » avec trois sous-entrées : Transmissions, Exports, Relevé.
