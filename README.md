# fpp-plugin-AmbientWeather

FPP plugin that shows live temperature and wind speed from an Ambient Weather
station on a Pixel Overlay Model (LED matrix).

## Install

In FPP: **Content Setup → Plugin Manager**, paste this into the
"Retrieve Plugin Info" box, then click Install:

```
https://raw.githubusercontent.com/AtlJim965/fpp-plugin-AmbientWeather/main/pluginInfo.json
```

## Setup

1. **Content Setup → Ambient Weather Display**
2. Enter your Ambient Weather **API Key** and **Application Key**
   (from https://ambientweather.net/account).
3. Pick your overlay model, message, font, color and position.
4. Click **Save & Show Now** to test.

## Use in playlists / scheduler

Add a **Run Script** command → `AmbientWeather.sh`, with optional arguments:

| Argument | What it does |
|---|---|
| *(none)* | Update the matrix once |
| `--loop 60` | Keep updating every 60 seconds |
| `--off` | Clear the text and turn the overlay off |
| `--text "Your text {temp} {wind}"` | Different wording for this entry |

## Christmas border

Turn on **Show border** on the settings page for a striped border on all four
sides (Christmas, Red & Green, Candy Cane, Winter, Gold & Silver, Rainbow or
your own colors). The message is then drawn in a pixel font sized to fit inside
the border; use `|` in the message for a new line, e.g.
`The weather right now is:|{temp}  {wind}`. Tick **Chase** to make the stripes
move around the edge while running with `--loop`.

Settings are stored in `/home/fpp/media/config/plugin.fpp-plugin-AmbientWeather.json`.
