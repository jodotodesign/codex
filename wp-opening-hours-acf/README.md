# ACF Öffnungszeiten & Feiertage

Ein WordPress-Plugin, das Öffnungszeiten mit Advanced Custom Fields (ACF) verwaltet und einen Shortcode bereitstellt, um im Frontend den aktuellen Status (geöffnet/geschlossen) anzuzeigen. Feiertage (inkl. Brückentage) für ein deutsches Bundesland werden berücksichtigt.

## Installation
1. Kopiere den Ordner `wp-opening-hours-acf` in den Ordner `wp-content/plugins/` deiner WordPress-Installation.
2. Stelle sicher, dass das ACF-Plugin (Pro oder Free) aktiv ist.
3. Aktiviere **ACF Öffnungszeiten & Feiertage** in WordPress.

## Einrichtung
- Im Admin-Menü erscheint ein neuer Eintrag **Öffnungszeiten**. Dort kannst du pro Wochentag beliebig viele Zeitfenster hinterlegen (Start- und Endzeit).
- Wähle unter **Bundesland** das passende Bundesland für die Feiertagsberechnung aus.
- Hinterlege optionale **Brückentage** oder **individuelle Schließtage**, die zusätzlich zu den automatisch berechneten Feiertagen gelten.

## Shortcode
```
[opening_status]
```
Optionale Attribute:
- `state` – Überschreibt das im Backend gewählte Bundesland (z. B. `state="BY"`).
- `show_schedule` – `true` zeigt eine Tabelle der Öffnungszeiten unter dem Status an.

**Beispiel:**
```
[opening_status state="NW" show_schedule="true"]
```

Der Shortcode gibt standardmäßig aus, ob aktuell geöffnet ist und wann als nächstes geöffnet wird, falls geschlossen.
