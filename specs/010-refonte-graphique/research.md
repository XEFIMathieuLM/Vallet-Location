# Research: Refonte graphique

## R1 — Police : Inter Tight servie par l'application

- **Decision** : remplacer `bunny('Instrument Sans', [400, 500, 600])` par `bunny('Inter Tight', { weights: [400, 600, 700, 800] })` dans `vite.config.js`. `--font-sans` vaut `'Inter Tight', ui-sans-serif, system-ui, sans-serif`.
  - Titres en 700, avec `line-height: 0.95` pour les tailles display et heading.
  - Le logotype seul utilise le 800.
- **Rationale** : la référence cite Inter Tight comme premier substitut libre. Le module `fonts` de `laravel-vite-plugin` télécharge les fichiers au build et les sert depuis `/build/assets` (`@fonts` dans `partials/head`), déjà en place : aucune requête vers un tiers à l'exécution, aucun package nouveau.
- **Alternatives** :
  - Manrope : plus rond, moins proche du caractère semi-condensé.
  - Google Fonts en `<link>` : requête vers un tiers à chaque page.
  - `@fontsource/inter-tight` : nouveau package npm, inutile ici.

## R2 — Palette : accent Flux et gammes Tailwind redéfinies

- **Decision** : dans `resources/css/vallet/tokens.css` (`@theme`) :
  - **Accent** : `--color-accent: #ffed00`, `--color-accent-foreground: #000` (texte sur le jaune), `--color-accent-content: #000`. Flux utilise `accent-content` pour le texte et les liens « accentués » : il doit rester noir, jamais jaune. Ajout de `--color-accent-pressed: #e6d200`.
  - **Neutres** : la gamme `zinc` est remplacée par les gris de la référence : 50 `#f7f7f7`, 100 `#f2f2f2`, 200 `#e6e6e6`, 300 `#c4c4c4`, 400 `#8a8a8a`, 500 `#666666`, 600 `#4d4d4d`, 700 `#333333`, 800 `#222222`, 900 `#111111`, 950 `#0a0a0a`. `neutral` pointe sur les mêmes valeurs.
  - **Sémantiques** : les gammes `red`, `green`, `amber`, `blue` sont générées en OKLCH à partir de la teinte et de la saturation des couleurs de la référence (erreur `#be6464`, succès `#8dc572`, alerte `#f0ad4e`, info `#337ab7`). La saturation est réduite aux extrémités, la luminosité suit l'échelle de Tailwind, et les crans 600 à 900 sont assombris pour le texte. Valeurs dans [contracts/design-tokens.md](contracts/design-tokens.md).
  - **Alias** : `yellow` et `orange` pointent sur `amber`, pour qu'aucun statut ni alerte « warning » (Flux utilise `yellow` pour les callouts d'avertissement) ne ressemble à l'accent. `lime` pointe sur `green`, `sky` sur `blue`.
- **Rationale** :
  - Les statuts (`MachineStatus::color()` et 8 autres enums, `CustomerBadge`) renvoient des noms de couleur Flux (`red`, `green`, `amber`, `blue`, `zinc`).
  - Les badges, callouts et textes d'erreur de Flux utilisent ces gammes.
  - Redéfinir les gammes change tout l'outil en un seul endroit, sans toucher aux enums ni aux vues.
- **Contrastes calculés** (WCAG 2.1, formule de luminance relative) :

  | Couple | Ratio |
  |---|---|
  | texte 700 sur badge 400 / 20 % (clair) | 5,44 à 5,85 |
  | texte 200 sur badge 400 / 40 % sur `#111111` (sombre) | 6,29 à 6,37 |
  | texte 600 sur blanc (messages d'erreur `text-red-600`) | 4,76 à 5,24 |
  | noir sur `#ffed00` | 17,4 |
  | `#666666` sur blanc / sur `#f7f7f7` | 5,74 / 5,36 |
  | blanc à 72 % sur noir | 10,6 |
  | blanc à 55 % sur noir (surtitres de la colonne) | 6,25 |
  | jaune sur blanc | **1,21 : interdit pour du texte** |

  `#8a8a8a` (3,45) est réservé aux textes d'exemple et aux éléments désactivés, comme dans la référence. Un test Unit recalcule ces ratios à partir de `tokens.css` (R13).
- **Alternatives** :
  - Modifier chaque `color()` d'enum : 9 enums dans 7 layers, et Flux n'accepte que ses noms de couleur.
  - Publier le composant badge : ne couvre ni les callouts ni les textes.

## R3 — Habillage des composants Flux : CSS d'abord, un seul composant publié

- **Decision** : `resources/css/vallet/components.css` cible les attributs stables de Flux. Le bouton est le seul composant publié (`php artisan flux:publish button`), dans `resources/views/flux/button/index.blade.php`.

  | Cible | Habillage |
  |---|---|
  | `[data-flux-control]` (input, select natif, textarea) | bordures haute et latérales à 0, bordure basse 1 px noire (blanche en sombre), rayon 0, sans ombre ; 48 px de haut en `base`. Focus : bordure basse 2 px plus un anneau de focus visible (R9). |
  | `[data-flux-card]` | rayon 0, sans ombre, bordure 1 px `zinc-100` |
  | `[data-flux-callout]` | rayon 0, filet gauche 4 px de la couleur sémantique |
  | `[data-flux-modal] dialog`, `[data-flux-menu]`, `ui-dropdown` | rayon 0, ombre légère autorisée (éléments flottants, FR-005) |
  | `[data-flux-table]`, `[data-flux-column]` | colonnes en surtitre (11 px, 700, capitales), lignes séparées par des filets `zinc-100`, bord haut 2 px noir |
  | `[data-flux-badge]` | pastille (`rounded-full`), 600 |
  | `[data-flux-heading]` | 700, `line-height: 0.95` |
  | `ui-radio[data-flux-radio-cards]`, `[data-flux-checkbox]` | rayon 0 / 2 px |
  | `[data-flux-tooltip]`, `[data-flux-toast]` | rayon 0 |

  **Bouton publié** :

  | Variante | Rendu |
  |---|---|
  | `primary` | jaune, texte noir, `#e6d200` au survol et à l'appui |
  | `filled` | noir, texte blanc (« secondaire noir » de la référence) |
  | `outline` (défaut) | fond blanc, contour 1 px noir |
  | `ghost` / `subtle` | sans fond, soulignement au survol |
  | `danger` | `red-600`, texte blanc (5,24:1) |

  Toutes les variantes : rayon 2 px, sans ombre, 700, `letter-spacing: 0.01em`. Hauteur 44 px sur téléphone (`max-sm:h-11`), 40 px au-dessus. L'attribut `data-variant="{{ $variant }}"` est ajouté. Les options que l'application n'utilise pas (prop `color`, groupes de boutons) sont retirées de la copie publiée, qui passe sous 200 lignes.
- **Rationale** : les attributs `data-flux-*` sont l'API stable de Flux pour le style. Le bouton n'expose pas sa variante, et ses classes de couleur sont calculées en PHP.
- **Alternatives** :
  - Publier tous les composants : une trentaine de fichiers à maintenir à chaque mise à jour de Flux.
  - Cibler les classes générées : fragile.

## R4 — Navigation salariés : colonne noire et barre de 60 px

- **Decision** : `layouts/app/sidebar.blade.php` garde `flux:sidebar` et toutes ses entrées, avec les mêmes `@can` et le même ordre.
  - **Colonne** :
    - `class="dark bg-black"` : la classe `dark` locale fait prendre aux éléments Flux de la colonne leur rendu sombre (texte blanc), même en thème clair, sans réécrire les items.
    - L'entrée courante passe en blanc inversé (fond blanc, texte noir, 700) via `components.css` sur `[data-flux-sidebar-item][data-current]`.
    - Les titres de groupe sont en surtitre (11 px, 700, capitales, blanc à 55 %).
    - Le logotype est en version inversée, le menu personnel en bas (inchangé, `data-test="sidebar-menu-button"`).
  - **Barre** : le composant `x-top-bar` (60 px, fond blanc, filet bas) affiche le titre de la page (`$title`, déjà transmis au layout) et la date du jour en heure de Paris.
  - **Mobile** : la barre porte le bouton du menu, le logotype et le profil. La colonne s'ouvre en tiroir comme aujourd'hui (`collapsible="mobile"`). Cibles de 44 px.
- **Rationale** : choix de l'utilisateur (clarification). Toutes les entrées restent à un clic, et l'entrée courante est marquée par la graisse et l'inversion, pas seulement par la couleur.
- **Alternatives** : barre supérieure avec menus par domaine (maquette 2), écartée par l'utilisateur.

## R5 — Pictogrammes de machines : `MachineFamily` dans `fleet`

- **Decision** :
  - Enum `Functional\Fleet\Enums\MachineFamily` (`Nacelle`, `MiniExcavator`, `Compactor`, `Scaffolding`, `Neutral`) avec `fromCategoryName(string $categoryName): self`, qui reconnaît par mots-clés normalisés (minuscules, sans accents) : `nacelle`, `mini-pelle` / `minipelle` / `pelle`, `compacteur` / `rouleau` / `plaque`, `echafaudage`. Repli : `Neutral`.
  - Composant anonyme `<x-fleet::pictogram :category="$categoryName" class="…" />`, qui inclut le SVG de la famille (`fleet::pictograms.<famille>`) en `fill="currentColor"`, `aria-hidden="true"` et `focusable="false"`.
  - Les SVG sont ceux validés dans `maquettes/illustrations/`, plus un pictogramme neutre (caisse à outils géométrique).
- **Rationale** :
  - Toutes les vues qui montrent une machine (`app/` tableau de bord, `booking`, `portal`, `sales`, `certification`) dépendent déjà de `fleet`.
  - Les catégories sont des données saisies : la correspondance par nom, avec un repli, couvre les catégories futures sans migration (spec, Assumptions).
  - `currentColor` donne les versions noire et blanche sans dupliquer les fichiers.
- **Alternatives** :
  - Colonne `family` sur `machine_categories` : migration et écran de saisie, hors périmètre (aucune donnée ne change).
  - Images PNG : flou sur écran haute densité, deux couleurs à produire.

## R6 — Tableau de bord : composants refaits (maquette 1 v3)

- **Decision** : les cinq composants Livewire existants gardent leurs propriétés, leurs requêtes, `wire:poll.60s` et leurs `wire:key`. Seules leurs vues sont refaites, plus deux propriétés calculées de présentation dans `DayOperations`.
  - **`Dashboard`** : le sélecteur d'agence reste dans le composant (`wire:model.live` inchangé), mais il s'affiche dans la barre de 60 px grâce à `@teleport('#top-bar-actions')` de Livewire, qui garde les liaisons du composant sur le contenu déplacé. Il est en pastille.
    - Un slot de layout ne convient pas : son contenu sort de la racine du composant et perd `wire:model`.
  - **`DayOperations`** :
    - Bandeau d'accueil : bloc clair avec le titre d'accueil et le résumé du jour, bloc noir illustré.
    - Quatre tuiles `x-tile` (`accent` pour les retours en retard quand il y en a, `soft` sinon) avec le total en display et la liste. Lignes avec pictogramme, référence, client, dates et note en badge.
    - Frise des 7 prochains jours, une colonne par jour, jours vides compris.
    - Deux nouvelles variables de vue, construites dans `render()` comme les sections existantes, sans requête de plus :
      - `daySummary` : les totaux des départs, retours et retards, déjà calculés par `DashboardSection` ;
      - `upcomingDays` : la liste ordonnée des dates de la plage de la requête existante (du lendemain à `today + config('dashboard.upcoming_departure_days')`), chacune avec ses réservations prises dans `upcomingDepartures->items`.
    - Le lien « Voir tout » reste affiché quand `hasMore()` (liste limitée à `dashboard.section_limit`).
  - **`PendingWork`** : bloc noir ; un compteur mis en avant (`isHighlighted()`) affiche son nombre dans un carré jaune à texte noir. `data-counter` et `data-highlighted` sont conservés (tests de la 008).
  - **`FleetStatus`** : barre de répartition (largeurs proportionnelles aux comptes) et chiffres avec pastille de couleur du statut.
  - **`VgpWatch`** : liste à filets, badges inchangés.
- **Rationale** : clarification « tableau de bord v3 », composants refaits avec l'autorisation de l'utilisateur, sections et liens de la 008 conservés (FR-013).
- **Alternatives** :
  - Réutiliser les cartes Flux : c'était le rendu jugé insuffisant.
  - Un composant `Hero` séparé : il refairait les requêtes de `DayOperations`.

## R7 — Espace client : barre blanche, bandeau noir, cartes, pied de page noir

- **Decision** :
  - **`portal::layouts.portal`** :
    - barre de 60 px blanche (logotype, `flux:navbar` avec soulignement 2 px noir de l'entrée courante, profil) ;
    - `<main>` pleine largeur ;
    - pied de page noir : logotype inversé, entrées de l'espace, mention de copyright.
  - **Composant `x-portal::hero`** : bande noire pleine largeur, conteneur de 1440 px au plus, surtitre « Espace client », titre display et slot. Utilisé par les pages Recherche (avec les filtres en champs à bordure basse blanche), Mes demandes, Mes réservations et Compte.
  - **Cartes de résultats** : zone `soft` en 16:9 avec le pictogramme de la catégorie, référence en heading-lg, catégorie et agence, prix indicatif, bouton `filled` noir « Demander » pleine largeur. L'action principale jaune de l'écran est celle de la modale « Envoyer la demande ».
  - **`portal::layouts.guest`** : mêmes deux panneaux que la connexion salariés (R8), avec la mention « Espace client ».
  - Thème clair forcé : `class="dark"` retiré et `@fluxAppearance` non inclus. `partials/head` reçoit `$hasAppearanceChoice` (vrai par défaut).
- **Rationale** : FR-014 et maquette 3 ; les cartes multiples ne peuvent pas être jaunes (FR-002).
- **Alternatives** : garder `flux:main container` avec un en-tête dans la page, ce qui ne permet pas de bande pleine largeur.

## R8 — Connexion, paramètres, accueil `/`, page téléphone

- **Decision** :
  - **`layouts/auth/simple`** : deux panneaux. À gauche (≥ 1024 px), panneau noir avec le logotype inversé, le titre « Vallet Location » en display et un pictogramme de nacelle. À droite, formulaire sur blanc, 400 px au plus. Sur téléphone, bande noire de 120 px avec le logotype au-dessus du formulaire. Pages de connexion, mot de passe, double authentification et confirmation inchangées hormis les composants partagés (`x-auth-header`).
  - **Paramètres** : la sous-navigation (`pages/settings/layout`) passe en pastilles ; le choix d'apparence reste.
  - **Accueil `/`** (`welcome.blade.php`, page du kit avec ses liens et son logo) : refaite en page Vallet Location.
    - Barre de 60 px avec le logotype.
    - Bande noire avec le titre display et deux tuiles : « Espace salariés » vers `login`, ou vers `dashboard` si l'utilisateur est connecté, et « Espace client » vers `portal.login`.
    - Pied de page noir.
    - La route et son nom restent.
  - **`inspection::layouts.phone`** : en-tête noir avec le logotype inversé, contenu sur blanc, boutons de 48 px.
- **Rationale** : FR-010, FR-011 (le kit affiche son propre logo sur `/`), US6.

## R9 — Accessibilité : focus, contraste, cibles

- **Decision** :
  - **Focus** : `:focus-visible { outline: 2px solid currentColor; outline-offset: 2px }` pour tous les éléments interactifs. Le contour suit la couleur du texte : noir sur blanc ou sur jaune, blanc sur noir, donc toujours ≥ 3:1. Les champs ajoutent leur bordure basse 2 px.
  - **Jaune** : jamais en couleur de texte (aucune classe `text-accent` hors du logotype) ; vérifié par une recherche dans le code au moment de l'audit final.
  - **Cibles** : boutons et entrées de navigation de 44 px au moins sous 640 px.
  - **Pictogrammes** décoratifs (`aria-hidden`), jamais seuls porteurs d'information.
  - **États** : vides, chargement (`x-loading-hint`) et erreurs gardent leur texte (règles `design:screen-states`) ; seul leur rendu change.
- **Écart avec les skills Xefi `design:*`** (la référence de l'utilisateur prime sur le visuel) :
  - rayons de 0 et 2 px au lieu des rayons arrondis Xefi ;
  - champs à bordure basse au lieu de champs encadrés ;
  - un seul accent jaune.

  L'accessibilité (contraste AA, focus visible, cibles de 44 px, états d'écran) suit les skills Xefi sans écart.

## R10 — E-mails : thème Markdown `vallet`

- **Decision** :
  - `config/mail.php` : ajout de `'markdown' => ['theme' => 'vallet', 'paths' => [resource_path('views/vendor/mail')]]`.
  - `resources/views/vendor/mail/html/themes/vallet.css` : fond blanc, encre noire, police système (les clients de messagerie ne chargent pas les polices), bouton jaune à texte noir, rayon 0, tableaux à filets.
  - `resources/views/vendor/mail/html/header.blade.php` : logotype texte « VALLET LOCATION ».
  - Les autres composants Markdown (`button`, `table`, `footer`…) restent ceux du framework.
- **Rationale** : US7 « si c'est simple ». Deux fichiers et une clé de configuration, aucun Mailable modifié.
- **Alternatives** : publier tous les composants `laravel-mail`, une vingtaine de fichiers sans gain.

## R11 — Thème sombre noir / jaune

- **Decision** :
  - `resources/css/vallet/dark.css` redéfinit, sous `.dark` : `--color-accent` (inchangé, jaune), `--color-accent-foreground` (noir), `--color-accent-content` (blanc).
  - Fonds `#000` (page) et `#111` (surfaces), séparateurs `white/16`, texte blanc et blanc à 72 %. Les champs ont leur bordure basse blanche.
  - Les vues gardent leurs variantes `dark:`, remappées dans `components.css` quand elles visent `zinc-800` / `zinc-900` : le corps passe de `dark:bg-zinc-800` à `dark:bg-black` dans les layouts.
  - Espace client et e-mails forcés en clair (R7, R10).
- **Rationale** : choix de l'utilisateur (clarification). Flux gère déjà la préférence (`@fluxAppearance`, paramètres > Apparence).

## R12 — Textes ajoutés (FR-017)

Textes nouveaux, en français dans les fichiers de traduction, tous issus de données déjà calculées :

| Clé | Texte |
|---|---|
| `dashboard.welcome.title` | « Bonjour, voici votre journée. » |
| `dashboard.welcome.summary` | « :departures départ(s), :returns retour(s) et :late retour(s) en retard aujourd'hui. » (avec `trans_choice`) |
| `dashboard.upcoming.no_departure` | « — » (jour sans départ, avec un texte pour lecteur d'écran « Aucun départ ») |
| `dashboard.fleet.total` | « :count machines » |
| `portal::navigation.footer.copyright` | « © :year Vallet Location » |
| `portal::search.results_count` | « :count machine(s) disponible(s) » |
| `welcome.*` | accueil `/` : titre, deux tuiles, accroche |

Les libellés existants ne changent pas. La date de la barre de 60 px n'est pas un texte : elle est formatée par `isoFormat('dddd D MMMM YYYY')` en locale `fr`.

## R13 — Vérifications automatisées

- **Decision** :
  - **`tests/Concerns/InspectsPrimaryActions`** : analyse le HTML rendu (`DOMDocument` + XPath) et compte les `[data-flux-button][data-variant="primary"]` hors `ui-modal`. Utilisé par un test Feature sur chaque écran de référence (SC-004).
  - **`tests/Unit/Design/DesignTokensContrastTest`** : lit `tokens.css`, recalcule les ratios des couples listés dans [contracts/design-tokens.md](contracts/design-tokens.md) et échoue sous 4,5:1 (texte) ou 3:1 (grands titres, contours de focus).
  - **`tests/Feature/Design/BrandingTest`** : rend les écrans de référence, les layouts et les e-mails, vérifie le logotype et l'absence de « Renault », « NouvelR » et « Laravel » (SC-007, FR-011).
  - **Captures** : script Playwright hors projet (Chrome installé, `playwright-core` dans un dossier temporaire), décrit dans [quickstart.md](quickstart.md). Il produit `captures/apres/*.png` et signale tout défilement horizontal à 375 px (SC-001, SC-002).
- **Rationale** : tout ce qui se vérifie sur le HTML rendu a un test, et le reste a une vérification reproductible (Complexity Tracking).
