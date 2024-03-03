# Documentation


## Setup und lokale Entwicklung

Die Installation der benötigten Pakete erfolgt über npm:

	$ npm install

Auf dem System muss [Grunt CLI](https://gruntjs.com/getting-started#installing-the-cli) vorhanden sein. Dies kann mittels `grunt --version` geprüft werden. Falls es fehlt, kann es mittels npm global installiert werden:

	$ npm install -g grunt-cli

Danach kann der Build-Workflow gestartet werden Dev+Debugging:

	$ grunt

Für die Erstellung der Distributiion
        
	$ grunt --production
	
Es werden dabei folgende Aktionen durchgeführt:

1. __CSS-Dateien__  
Suche im Addon-Verzeichnis sowie in allen Plugin-Verzeichnissen nach den Dateien `assets_src/less/be.less` sowie `assets_src/less/fe.less`, kompiliere sie zu CSS-Dateien und lege sie im Ordner `assets/css` als `be.css`, bzw. `fe.css` ab.

2. __JS-Dateien__  
Suche im Addon-Verzeichnis sowie in allen Plugin-Verzeichnissen nach Dateien in den Ordnern `assets_src/js/be/` sowie `assets_src/js/fe/`, kompiliere sie zu einzelnen JS-Dateien, minimiere sie und lege sie im Ordner `assets/js` als `be.js` bzw. `fe.js` ab.

3. __Synchronisiere Verzeichnisse__  
Synchronisiere nun die `assets/`-Ordner im Addon- sowie in allen PluginVerzeichnissen mit den entsprechenden `assets/`-Ordnern im Hauptordner `/assets/addons/bloecks`.

4. __Watch__  
Beobachte nun die `assets` und `assets_src` Ordner und führe bei Bedarf die entsprechenden Aktionen aus.


## Release erstellen

Um die Version des AddOns und der PlugIns zu erhöhen, kann das version.sh-Script verwendet werden:

	$ ./version.sh

Jetzt nicht vergessen, die `CHANGELOG.md` zu aktualisieren und einen Git-Tag für den finalen Stand zu vergeben.

Das Release für den __REDAXO-Installer__ wird mit dem zip.sh-Script erstellt. Es führt einen Build für Production durch (`grunt --production`) und legt danach innerhalb des `/redaxo/src/addons`-Verzeichnisses eine ZIP-Datei ab:

	$ ./zip.sh


-----


## Aufbau der Klassen

Jedes Plugin sollte die Klassen `bloecks_PLUGINNAME` (für alle Basisfunktionen) und `bloecks_PLUGINNAME_backend` (für alle Funktionen im Backend) beinhalten. Dabei erweitert `bloecks_PLUGINNAME` immer die `bloecks`-Klasse, und `bloecks_PLUGINNAME_backend` entsprechend die `bloecks_backend`-Klasse.

### 1. `bloecks_abstract`

Diese Klasse dient als Basis für allen anderen Klassen und beinhaltet Funktionen zur Vereinfachung des Zugriffs auf das Addon und seine Plugins.

Als Klassenvariablen sind `bleocks_abstract::$addon_name` und `bleocks_abstract::$plugin_name` definiert, die von den jeweiligen Klassen mit den nötigen Werten belegt werden - die Hauptklasse `bloecks` definiert hier bspw. den Wert `bloecks` als `bleocks_abstract::$addon_name`-Wert. Das Plugin `bloecks_status` liefert `status` als `bleocks_abstract::$plugin_name`-Wert.

Diese Variablen werden bspw. bei der Erstellung des Permission-Strings verwendet (`bleocks_abstract::$addon_name[bleocks_abstract::$plugin_name]`).

* __`bloecks_abstract::package()`__  
Liefert das AddOn-Objekt, bzw. das Plugin-Objekt der aktuellen Klasse zurück, jenachdem von wo aus die Funktion aufgerufen wurde. Wird diese Funktion beispielsweise von bloecks_backend::package() aufgerufen, liefert sie das `rex_addon::get('bloecks')`-Object zurück. Wird sie vom Plugin `bloecks_status` aufgerufen, liefert sie das `rex_addon::get('bloecks')->getPlugin('status')` zurück.

* __`bloecks_abstract::addon()`__  
Liefert das Addon-Objekt zurück.

* __`bloecks_abstract::plugin()`__  
Liefert das jeweilige Plugin-Object zurück (sofern die Funktion von einer Plugin-Klasse aufgerufen wurde)

* __`bloecks_abstract::getValueOfSlice($slice_id, $key, $default = null)`__  
Liefert den Wert `$key` des Slices mit der ID `$slice_id` zurück, bzw. den Wert `$default` sofern `$key` nicht existiert.

### 2. `bloecks`

Die Basisklasse des Addons - initialisiert im Backend die Backendfunktionen und fügt zusätzlich zum EP `SLICE_SHOW` einen `SLICE_SHOW_BLOECKS_FE` ExtensionPoint hinzu, der von allen Plugins genutzt wird um die Ausgabe eines Slices anzupassen.

### 3. `bloecks_backend`

Beinhaltet die Backendfunktionen des Plugins:

* __`bloecks_backend::init()`__  
Registriert die nötigen Permissions via rex_perm (bspw. bloecks[] oder bloecks[pluginname]),
lädt auf der `content/edit`-Seite die nötigen CSS und JS-Dateien.

* __`bloecks_backend::getPermName()`__  
Liefert den Namen der Permisson des Addons bzw. des Plugins, jenachdem von wo aus die Funktion aufgerufen wurde.

* __`bloecks_backend::addPerm()`__  
Registriert die AddOn- bzw. PlugIn-Permissions.

* __`bloecks_backend::hasModulePerm(rex_user $user, $module_id)`__  
Liefert zurück, ob `$user` das Recht hat, die Funktionen des Addons bzw. des Plugins zu nutzen - beachtet
dazu auch `$user->getComplexPerm('modules')`.

* __`bloecks_backend::showSlice()`__  
Hängt sich beim EP `SLICE_SHOW` ein und erstellt einen neuen ExtensionPoint namens `SLICE_SHOW_BLOECKS_BE`
der in allen Plugins zur Anzeige des Blocks im Backend genutzt werden soll.
