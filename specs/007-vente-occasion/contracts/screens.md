# Contract: écrans de la vente (layer sales)

Toutes les routes : middleware `auth`, `verified`, `can:sales.manage`, préfixe `/ventes`. Entrée « Ventes d'occasion » dans la barre latérale (`resources/views/layouts/app/sidebar.blade.php`, colle `app/`).

| Route | Nom | Composant Livewire | Contenu |
|---|---|---|---|
| `GET /ventes` | `sales.index` | `SaleList` | Liste filtrable (statut, catégorie, agence, période de mise en vente ou de remise) : machine, statut au parc, statut de vente, prix demandé, prix final, acheteur, date de remise prévue ou réelle, état de transmission ; ventes en retard mises en évidence ; total HT des ventes conclues sur le filtre. Rafraîchie sur `echo-private:sales,.sale.changed`. |
| `GET /ventes/nouvelle?machine={id}` | `sales.create` | `ListMachineForm` | Choix de la machine (recherche par référence), prix demandé, année, heures, état, commentaire. |
| `GET /ventes/{sale}` | `sales.show` | `SaleDetail` | Annonce, modification du prix et du descriptif, offres (liste + formulaire avec client existant ou nouveau), acceptation avec date de remise, changement de date de remise, levée de la réservation (motif), remise, annulation (motif), état de transmission, historique. Les boutons absents ou désactivés selon l'état sont un confort ; chaque action est contrôlée côté serveur. |
| `GET /ventes/machines/{machine}` | `sales.machine-history` | `MachineSaleHistory` | Toutes les ventes de la machine, y compris annulées, avec leurs offres et leur historique (FR-024). |

Refus : chaque exception de domaine (`RefusalException`) est affichée par le trait `DisplaysRefusals` de fleet, avec le message traduit (location en conflit avec ses dates, agence et client ; vente ouverte existante ; machine sortie ; etc.).

Liens entrants : badges de machine (E3) vers `sales.show` ; écran des transmissions (E4) vers `sales.show`. La mise en vente part de l'écran des ventes (recherche de la machine par référence) : aucun bouton n'est ajouté au parc.
