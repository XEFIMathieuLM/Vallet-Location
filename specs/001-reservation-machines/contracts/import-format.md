# Contrat : fichier d'import du parc

Format accepté : CSV (séparateur `;`, UTF-8) ou XLSX. Première ligne = en-têtes.

| Colonne | Obligatoire | Format | Exemple |
|---------|-------------|--------|---------|
| `reference` | oui | texte, unique dans le fichier et dans le parc | `NAC-0042` |
| `categorie` | oui | nom d'une catégorie existante | `Nacelle` |
| `agence` | oui | nom d'une agence existante | `Annecy` |
| `soumise_vgp` | non | `oui` / `non` (défaut : selon la catégorie) | `oui` |
| `echeance_vgp` | non | `JJ/MM/AAAA` | `30/11/2026` |

## Règles

- Chaque ligne est validée individuellement ; une ligne invalide n'empêche pas l'import des autres.
- Motifs de rejet : référence vide, référence déjà présente dans le parc, référence en double dans le fichier (toutes les occurrences après la première), catégorie inconnue, agence inconnue, date illisible.
- Le rapport final donne le nombre de machines créées et la liste des lignes rejetées (numéro de ligne, référence, motif).
- L'import ne modifie jamais une machine existante.
