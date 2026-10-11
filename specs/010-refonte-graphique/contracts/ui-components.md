# Contrat : composants d'interface

Les composants existants gardent leur nom et leurs props. Les nouveaux composants n'ont que des props de présentation. Aucun composant ne porte de logique métier.

## Composants Flux

| Composant | Contrat après refonte | Fichier |
|---|---|---|
| `flux:button` | Variantes `primary` (jaune, texte noir, au plus une visible par écran hors modale), `filled` (noir, texte blanc), `outline` (défaut, contour noir), `ghost` / `subtle`, `danger` (`red-600`, texte blanc). Rayon 2 px, sans ombre, 44 px sur téléphone. Attribut `data-variant` rendu. Props inchangées. | `resources/views/flux/button/index.blade.php` (publié) |
| `flux:input`, `flux:select`, `flux:textarea` | Bordure basse seule, rayon 0, 48 px (`base`) ; focus : bordure basse 2 px et contour visible. | `components.css` |
| `flux:card` | Rayon 0, sans ombre, filet `zinc-100` ; `dark:` fond `zinc-900`. | `components.css` |
| `flux:callout` | Rayon 0, filet gauche 4 px de la couleur de la variante. | `components.css` |
| `flux:badge` | Pastille ; couleurs des gammes redéfinies. | `components.css` + `tokens.css` |
| `flux:table` | Bord haut 2 px noir, colonnes en surtitre, lignes à filets. | `components.css` |
| `flux:modal`, `flux:dropdown`, `flux:menu`, `flux:toast`, `flux:tooltip` | Rayon 0, ombre légère autorisée (éléments flottants). | `components.css` |
| `flux:sidebar.item` | Dans la colonne noire : texte blanc à 80 %, survol `zinc-900`, entrée courante en fond blanc, texte noir et 700. | `components.css` |
| `flux:navbar.item` | Entrée courante soulignée 2 px noir ; 14,4 px, 700. | `components.css` |

## Composants Blade d'`app/`

| Composant | Props | Rendu |
|---|---|---|
| `x-app-logo` | `sidebar` (bool, existant), `inverse` (bool, défaut `false`), `href` | Logotype « VALLET » (800, 22 px, interligne 1) et pavé « LOCATION » (9 px, 700, +0,2 em) ; noir sur clair, blanc sur sombre avec `inverse`. `aria-label` = nom de l'application. |
| `x-app-logo-icon` | attributs | Monogramme carré « V » (SVG), favicon et menus repliés. |
| `x-page-heading` | `title` (existant), `eyebrow` (facultatif), slot `actions` (existant) | `h1` en `.display-md` (`.display-lg` à partir de 1024 px), surtitre facultatif au-dessus, actions à droite ; passage à la ligne sur téléphone. |
| `x-section-heading` | `title`, `level` (existants) | Niveau 2 : `.heading-lg` ; 3 : `.heading-md` ; 4 : `.heading-sm`. 700, interligne 0,95. |
| `x-empty-state` | `heading`, `description`, slot `actions` (existants) | Bloc `zinc-50` à angle droit, sans pointillés, titre `.heading-sm`. |
| `x-tile` | `variant` : `light` \| `soft` \| `dark` \| `accent` (défaut `soft`), `href` (facultatif), attributs | Tuile à angle droit, padding 24 px (32 px à partir de 1024 px). `accent` : jaune à texte noir, au plus une par zone. `dark` : noir à texte blanc. Rendue en `<a>` si `href` est donné, avec `wire:navigate` transmis par les attributs. |
| `x-top-bar` | `title` (facultatif), slot `start` | Barre de 60 px, fond blanc (`dark:` noir), filet bas, titre de la page et date du jour (Europe/Paris). À droite, une zone vide `#top-bar-actions` reçoit le contenu qu'un écran y téléporte (`@teleport`), par exemple le sélecteur d'agence du tableau de bord. |

## Composants des layers

| Composant | Props | Rendu |
|---|---|---|
| `x-fleet::pictogram` | `category` (nom de catégorie, string), attributs (taille et couleur par classes) | SVG de `MachineFamily::fromCategoryName($category)`, `fill="currentColor"`, `aria-hidden="true"`, `focusable="false"`. |
| `x-portal::hero` | `title`, `eyebrow` (défaut : « Espace client »), slot | Bande noire pleine largeur, conteneur 1440 px, titre `.display-xl`, slot sous le titre (filtres de recherche). |

## Vérification

- `tests/Concerns/InspectsPrimaryActions::assertAtMostOnePrimaryAction(string $html)` compte les `[data-flux-button][data-variant="primary"]` qui ne sont pas descendants d'un `ui-modal`.
- `x-fleet::pictogram` ne rend jamais de texte : la référence et la catégorie restent affichées à côté.
