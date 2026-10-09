# Contrat : fichier d'export de secours

Format provisoire, en attendant le format d'import du logiciel de facturation du client (B9). Seule la mise en forme d'une ligne changera ; le choix des éléments exportés et leur passage à `exported` restent identiques.

## Fichier

- CSV, UTF-8 avec BOM (ouverture correcte dans Excel), séparateur `;`, une ligne d'en-tête.
- Nom : `export-facturation-AAAAMMJJ-HHMMSS.csv` (heure de Paris).
- Une ligne par transmission, triées par date de création.

## Colonnes

Mêmes champs que la ligne facturable de [billing-gateway.md](billing-gateway.md), dans cet ordre :

`idempotency_key;type;customer_ref;reservation_ref;machine_reference;machine_category;home_agency;booking_agency;period_start;period_end;period_kind;days;damage_view;damage_comment;label;amount_excl_tax`

- Les champs sans objet sont vides.
- `amount_excl_tax` est en euros avec une virgule décimale (ex. `450,00`), pour une saisie ou un import comptable directs.
- Une transmission sans référence client a `customer_ref` vide : la comptabilité la complète à l'import.

## Contenu

Toutes les transmissions `pending` ou `failed` au moment de l'export, prises sous verrou. Les transmissions `sent` ou déjà `exported` n'y figurent jamais (FR-023).
