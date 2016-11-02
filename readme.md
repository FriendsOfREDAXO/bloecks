blÖcks 1.1-alpha
=======================

Das AddOn dient als Basis für weitere Plugins und soll als Ersatz für das SliceUI-Addon dienen. Für
den Endanwender bietet es keine weiteren Funktionen, erst die Plugins geben den Redakteuren weitere
Möglichkeiten um Slices/Module/Blöcke zu bearbeiten.

## Aufbau der Klassen

Jedes Plugin sollte die Klassen ```bloecks_PLUGINNAME``` (für alle Basisfunktionen) und ```bloecks_PLUGINNAME_backend```
(für alle Funktionen im Backend) beinhalten. Dabei erweitert ```bloecks_PLUGINNAME``` immer die ```bloecks```-Klasse,
und ```bloecks_PLUGINNAME_backend``` entsprechend die ```bloecks_backend```-Klasse.

### bloecks_abstract

Diese Klasse dient als Basis für allen anderen Klassen und beinhaltet Funktionen zur Vereinfachung des Zugriffs
auf das Addon und seine Plugins.

Als Klassenvariablen sind ```bleocks_abstract::$addon_name``` und ```bleocks_abstract::$plugin_name``` definiert,
die von den jeweiligen Klassen mit den nötigen Werten belegt werden - die Hauptklasse ```bloecks``` definiert hier
bspw. den Wert ```bloecks``` als ```bleocks_abstract::$addon_name```-Wert. Das Plugin ```bloecks_status``` liefert
```status``` als ```bleocks_abstract::$plugin_name```-Wert.

Diese Variablen werden bspw. bei der Erstellung des Permission-Strings verwendet (```bleocks_abstract::$addon_name[bleocks_abstract::$plugin_name]```).

#### bloecks_abstract::package()
Liefert das AddOn-Objekt, bzw. das Plugin-Objekt der aktuellen Klasse zurück, jenachdem von wo aus die Funktion
aufgerufen wurde. Wird diese Funktion beispielsweise von bloecks_backend::package() aufgerufen, liefert sie
das ```rex_addon::get('bloecks')```-Object zurück. Wird sie vom Plugin ```bloecks_status``` aufgerufen, liefert
sie das ```rex_addon::get('bloecks')->getPlugin('status')``` zurück.

#### bloecks_abstract::addon()
Liefert das Addon-Objekt zurück.

#### bloecks_abstract::plugin()
Liefert das jeweilige Plugin-Object zurück (sofern die Funktion von einer Plugin-Klasse aufgerufen wurde)

#### bloecks_abstract::getValueOfSlice($slice_id, $key, $default = null)
Liefert den Wert ```$key``` des Slices mit der ID ```$slice_id``` zurück, bzw. den Wert ```$default``` sofern
```$key``` nicht existiert.

### bloecks

Die Basisklasse des Addons - initialisiert im Backend die Backendfunktionen und fügt zusätzlich zum EP
```SLICE_SHOW``` einen ```SLICE_SHOW_BLOECKS_FE``` ExtensionPoint hinzu, der von allen Plugins genutzt wird
um die Ausgabe eines Slices anzupassen.

### bloecks_backend

Beinhaltet die Backendfunktionen des Plugins

#### bloecks_backend::init()

Registriert die nötigen Permissions via rex_perm (bspw. bloecks[] oder bloecks[pluginname]),
lädt auf der ```content/edit```-Seite die nötigen CSS und JS-Dateien.

### bloecks_backend::getPermName()

Liefert den Namen der Permisson des Addons bzw. des Plugins, jenachdem von wo aus die Funktion aufgerufen wurde.

### bloecks_backend::addPerm()

Registriert die AddOn- bzw. PlugIn-Permissions.

### bloecks_backend::hasModulePerm(rex_user $user, $module_id)

Liefert zurück, ob ```$user``` das Recht hat, die Funktionen des Addons bzw. des Plugins zu nutzen - beachtet
dazu auch ```$user->getComplexPerm('modules')```.

### bloecks_backend::showSlice()

Hängt sich beim EP ```SLICE_SHOW``` ein und erstellt einen neuen ExtensionPoint namens ```SLICE_SHOW_BLOECKS_BE```
der in allen Plugins zur Anzeige des Blocks im Backend genutzt werden soll.

## Erstellung der Assets

Für die Erstellung der Assets gibt es ein Grunt-Setup im Hauptverzeichnis des Addons. Via ```npm install``` werden alle
nötigen node.js-Abhängigkeiten installiert. Via ```grunt``` werden folgende Aktionen durchgeführt.

### 1. CSS-Dateien
Suche im Addon-Verzeichnis sowie in allen Plugin-Verzeichnissen nach den Dateien ```assets_src/less/be.less``` sowie ```assets_src/less/fe.less```, kompiliere sie zu CSS-Dateien und lege sie im Ordner ```assets/css`` als ```be.css```,
bzw. ```fe.css``` ab.

### 2. JS-Dateien
Suche im Addon-Verzeichnis sowie in allen Plugin-Verzeichnissen nach Dateien in den Ordnern ```assets_src/js/be/``` sowie ```assets_src/js/fe/```, kompiliere sie zu einzelnen JS-Dateien, minimiere sie und lege sie im Ordner ```assets/js`` als ```be.js```,
bzw. ```fe.js``` ab.

### 3. Synchronisiere Verzeichnisse
Synchronisiere nun die ```assets/```-Ordner im Addon- sowie in allen PluginVerzeichnissen mit den entsprechenden
```assets/```-Ordnern im Hauptordner ```/assets/addons/bloecks```.

### 4. WATCH
Beobachte nun die ```assets``` und ```assets_src``` Ordner und führe bei Bedarf die entsprechenden Aktionen aus.

## Erstelle Installer-Datei
Dazu kann das zip.sh Script verwendet werden - es erstellt innerhalb des ```/redaxo/src/addons```-Verzeichnisses eine
ZIP-Datei über das Kommando

    zip -r ../bloecks.zip . -x "*node_modules*" -x "*.git*" -x "rsync*" -x "Gruntfile*" -x "package.json" -x ".*" -x "*assets_src*" -x "zip.sh"
