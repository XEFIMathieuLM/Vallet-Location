# Contract: ligne de vente transmise au logiciel de facturation

Envoyée par le `BillingGateway` existant (003), une seule fois par vente, clé d'idempotence = UUID de la transmission.

| Champ (`BillableLine::toArray()`) | Valeur pour une vente |
|---|---|
| idempotency_key | UUID de la transmission |
| type | `used_machine_sale` |
| customer_ref | identifiant de l'acheteur dans le logiciel de facturation (`CustomerBillingAccount`), `null` si inconnu → échec « client inconnu » |
| reservation_ref | `null` |
| source_ref | `SALE-{id}` |
| machine_reference | référence de la machine |
| machine_category | catégorie de la machine |
| home_agency | agence de rattachement de la machine |
| booking_agency | agence du salarié qui a mis en vente |
| sale_date | date de remise (`YYYY-MM-DD`) |
| label | « Vente machine d'occasion {référence} » |
| amount_excl_tax_cents | prix final hors taxes en centimes |
| period_* / days / damage_* | `null` |

Export de secours : même ligne, montant formaté en euros par `ExportLineFormatter` (colonne `amount_excl_tax`), colonnes `source_ref` et `sale_date` ajoutées à l'en-tête.

Faux logiciel (`FakeBillingGateway`) : accepte ou refuse selon le mode existant ; refuse avec « client inconnu » quand `customer_ref` est `null`, comme pour une location.
