# Data Model: Refonte graphique

Aucune table, colonne, migration ni donnée ne change. Ce document décrit les seuls « modèles » de la feature : les jetons de design, la famille de machine qui choisit un pictogramme, et la correspondance des statuts avec les couleurs.

## Jetons de design (CSS, `resources/css/vallet/tokens.css`)

| Groupe | Jetons | Source |
|---|---|---|
| Police | `--font-sans` : Inter Tight | R1 |
| Accent | `--color-accent` `#ffed00`, `--color-accent-pressed` `#e6d200`, `--color-accent-foreground` `#000`, `--color-accent-content` `#000` (clair) / `#fff` (sombre) | R2, R11 |
| Neutres | gamme `zinc` (et alias `neutral`) de 50 à 950 | R2 |
| Sémantiques | gammes `red`, `green`, `amber`, `blue` ; alias `yellow`, `orange` vers `amber`, `lime` vers `green`, `sky` vers `blue` | R2 |
| Rayons | `--radius-xs` 2 px (boutons), cartes / champs / tuiles 0, `rounded-full` pour pastilles et badges | FR-005 |
| Échelle typographique | utilitaires `.display-xl` (56 / 40 px sur téléphone), `.display-lg` (40), `.display-md` (32), `.heading-lg` (24), `.heading-md` (20), `.heading-sm` (18), `.eyebrow` (11 px, 700, capitales, +0,08 em) | référence, Typography |

Valeurs exactes et couples de contraste : [contracts/design-tokens.md](contracts/design-tokens.md).

## MachineFamily (enum, layer `fleet`)

| Cas | Valeur | Reconnu dans le nom de catégorie (minuscules, sans accents) | Pictogramme |
|---|---|---|---|
| `Nacelle` | `nacelle` | `nacelle` | `fleet::pictograms.nacelle` |
| `MiniExcavator` | `mini_excavator` | `mini-pelle`, `minipelle`, `pelle` | `fleet::pictograms.mini-pelle` |
| `Compactor` | `compactor` | `compacteur`, `rouleau`, `plaque vibrante` | `fleet::pictograms.compacteur` |
| `Scaffolding` | `scaffolding` | `echafaudage` | `fleet::pictograms.echafaudage` |
| `Neutral` | `neutral` | tout autre nom | `fleet::pictograms.neutral` |

- `MachineFamily::fromCategoryName(string $categoryName): self` : premier cas reconnu dans l'ordre du tableau, `Neutral` sinon.
- `MachineFamily::pictogramView(): string` : nom de la vue du pictogramme.
- Pure : aucune donnée persistée, testée en Unit.

## Statuts et couleurs (inchangés côté code)

Les enums gardent leurs `color()`. Seule la gamme de couleurs derrière chaque nom change (R2).

| Nom renvoyé | Rendu après refonte |
|---|---|
| `green` | succès désaturé (`#8dc572` en base) |
| `amber`, `yellow` | alerte (`#f0ad4e` en base), jamais l'accent |
| `red` | erreur (`#be6464` en base) |
| `blue` | info (`#337ab7` en base) |
| `zinc` | neutre noir sur gris clair |

## PlanningCellKind (enum, layer `booking`) : `cssClasses()` modifié

| Cas | Avant | Après |
|---|---|---|
| `Free` | `bg-transparent` | `bg-transparent` |
| `Reserved` | `bg-blue-600 text-white` | `bg-black text-white dark:bg-white dark:text-black` |
| `Workshop` | `bg-amber-300 text-zinc-900` | `bg-amber-300 text-black` (`#ebb56f`, 10,8:1) |
| `OutOfOrder` | `bg-red-600 text-white` | `bg-red-500 text-black` (5,2:1) |
| `VgpInvalid` | `bg-zinc-300 text-zinc-900 dark:…` | `bg-zinc-200 text-zinc-800 dark:bg-zinc-600 dark:text-white` |

La lettre de chaque case et la légende restent : l'état ne repose jamais sur la seule couleur.
