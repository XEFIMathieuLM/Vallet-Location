# Contrat : jetons de design

Fichier : `resources/css/vallet/tokens.css`, dans un bloc `@theme` importé par `resources/css/app.css`. Toute valeur de couleur utilisée par une vue passe par ces jetons ; aucune vue n'écrit de code hexadécimal, sauf les pictogrammes SVG (`currentColor`).

## Accent

```css
--color-accent: #ffed00;
--color-accent-pressed: #e6d200;
--color-accent-foreground: #000000;
--color-accent-content: #000000;   /* .dark : #ffffff */
```

## Neutres (`zinc`, alias `neutral`)

| 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 | 950 |
|---|---|---|---|---|---|---|---|---|---|---|
| `#f7f7f7` | `#f2f2f2` | `#e6e6e6` | `#c4c4c4` | `#8a8a8a` | `#666666` | `#4d4d4d` | `#333333` | `#222222` | `#111111` | `#0a0a0a` |

## Sémantiques

Générées en OKLCH à partir de la teinte de la référence. Luminosité par cran : 50 .971, 100 .936, 200 .885, 300 .808, 400 .704, 500 .637, 600 .545, 700 .475, 800 .42, 900 .375, 950 .258. Saturation : celle de la référence × (.15, .3, .5, .8, 1, 1, .95, .85, .7, .6, .45).

| Gamme (référence) | 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 | 950 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `red` (`#be6464`) | `#fff1f0` | `#ffe1e0` | `#fdcbc9` | `#f6a9a6` | `#de8180` | `#c86d6c` | `#a75354` | `#8b4344` | `#73393a` | `#613131` | `#391818` |
| `green` (`#8dc572`) | `#f0f9ec` | `#dff0d7` | `#c7e3ba` | `#a3d08e` | `#7ab15f` | `#669c4b` | `#4e7f33` | `#3e6927` | `#365824` | `#2e4a1f` | `#162a0c` |
| `amber` (`#f0ad4e`) | `#fef4e7` | `#fbe6cd` | `#f5d3a8` | `#ebb56f` | `#d2912c` | `#bc7c03` | `#9c6100` | `#824f00` | `#6c4300` | `#5b3900` | `#351e00` |
| `blue` (`#337ab7`) | `#ecf7ff` | `#d8edff` | `#bbddff` | `#8fc6fb` | `#5fa5e5` | `#4a90cf` | `#3274ae` | `#266092` | `#235078` | `#1f4465` | `#0b253c` |

Alias : `yellow` et `orange` → `amber` ; `lime` et `emerald` → `green` ; `sky` et `indigo` → `blue` ; `rose` → `red`.

## Couples vérifiés par `DesignTokensContrastTest`

| Texte | Fond | Seuil |
|---|---|---|
| `#000` | `--color-accent` | 4,5 |
| `#000` | `#ffffff`, `zinc-50` | 4,5 |
| `zinc-500` | `#ffffff`, `zinc-50` | 4,5 |
| `#ffffff` à 72 % | `#000`, `zinc-900` | 4,5 |
| `#ffffff` à 55 % | `#000` | 4,5 |
| `{red,green,amber,blue}-700` | `{…}-400` à 20 % sur blanc | 4,5 |
| `{red,green,amber,blue}-200` | `{…}-400` à 40 % sur `zinc-900` | 4,5 |
| `{red,green,amber,blue}-600` | `#ffffff` | 4,5 |
| `#ffffff` | `red-600` (bouton danger) | 4,5 |
| `#000` | `amber-300`, `red-500` (cases du planning) | 4,5 |
| `#000` / `#fff` | contours de focus sur blanc / noir / jaune | 3 |
| `--color-accent` | `#ffffff` | doit être **< 3** (garde-fou : rappelle que le jaune n'est pas une couleur de texte) |

## Typographie

| Utilitaire | Taille | Graisse | Interligne |
|---|---|---|---|
| `.display-xl` | 56 px (40 px sous 640 px) | 700 | 0,95 |
| `.display-lg` | 40 px (32 px sous 640 px) | 700 | 0,95 |
| `.display-md` | 32 px | 700 | 0,95 |
| `.heading-lg` | 24 px | 700 | 0,95 |
| `.heading-md` | 20 px | 700 | 0,95 |
| `.heading-sm` | 18 px | 700 | 1 |
| `.eyebrow` | 11 px, capitales, +0,08 em | 700 | 1,45 |
| corps | 16 px / 14 px | 400 | 1,4 / 1,57 |
| boutons | 14,4 px, +0,01 em | 700 | 1 |
