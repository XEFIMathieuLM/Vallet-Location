# Contrat : numéro de bon de commande dans la transmission (extension de la 003)

Point d'extension ajouté à `billing` pour que chaque élément transmis porte le numéro de bon de commande de sa réservation (FR-011 à FR-013), sans que `billing` sache où ce numéro est saisi (G8).

## Port `Functional\Billing\Contracts\PurchaseOrderNumbers`

```php
interface PurchaseOrderNumbers
{
    public function forReservation(int $reservationId): ?string;
}
```

- Implémentation par défaut, dans `billing` : `NullPurchaseOrderNumbers`, renvoie toujours `null`. Liée par `BillingServiceProvider::register()` avec `bindIf` : elle ne s'applique que si aucune autre liaison n'existe. Sans layer `accounts`, la 003 se comporte exactement comme avant.
- Implémentation de la 006, dans `accounts` : `KeyAccountPurchaseOrderNumbers`, lit `reservation_purchase_orders.number`. Liée par `AccountsServiceProvider::register()` avec `bind` : elle l'emporte quel que soit l'ordre de chargement des providers.
- Appelée une fois par ligne, au moment où `MakeBillableLine` construit la ligne (envoi automatique et export de secours). Aucune requête dans une boucle : l'export de secours précharge les numéros de toutes ses réservations par un appel groupé `forReservations(list<int>): array<int, string>` ajouté au même port.

## Ligne facturable : champ ajouté

À ajouter au tableau de [billing-gateway.md](../../003-transmission-facturation/contracts/billing-gateway.md) :

| Champ | Période de location | Dégât refacturé |
|-------|---------------------|-----------------|
| `purchase_order_number` | numéro de bon de commande de la réservation, ou vide | idem (numéro de la réservation d'origine) |

- Texte libre de 1 à 50 caractères, sans espaces en tête ni en fin. Aucun format vérifié.
- Vide (`null`) quand la réservation n'a pas de numéro : client particulier, professionnel ordinaire sans numéro, réservation sortie avant la mise en service de la 006.
- Le numéro est figé à la sortie : toutes les périodes et tous les dégâts d'une même réservation portent le même numéro.
- Aucun élément déjà `sent` ou `exported` n'est renvoyé pour y ajouter le numéro.
- Un refus du logiciel lié au numéro suit le chemin de tout refus : `BillingSoftwareRejectedException` → `failed` avec le motif.

## Export de secours : colonne ajoutée

À ajouter à [export-format.md](../../003-transmission-facturation/contracts/export-format.md) : colonne `purchase_order_number`, en **dernière** position, après `amount_excl_tax`, vide si sans objet. Ajouter la colonne à la fin évite de décaler les colonnes qu'un import comptable déjà paramétré attend.

## À obtenir du client (avec les questions de la 003)

- Le nom du champ « référence de commande client » dans le logiciel de facturation et sa longueur maximale.
- La confirmation que le logiciel applique le tarif négocié à partir de l'identifiant client (`customer_ref`) seul.

## Rebase

La 007 modifie aussi `BillableLine` et remplace la construction des lignes par des sources de transmission (`BillableSource::line()`) : le numéro est alors renseigné par chaque source de location et de dégât et la 003 introduit `Money` et déplace le formatage de ligne d'export dans `Functional\Billing\Exports\ExportLineFormatter` (`functional/billing/src/Exports/ExportLineFormatter.php`), où la colonne est ajoutée. Le champ et la colonne sont ajoutés là où ces éléments se trouvent après rebase ; le contrat ci-dessus ne change pas.
