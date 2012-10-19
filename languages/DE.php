<?php

/**
 * kitEvent
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

if ('á' != "\xc3\xa1") {
  // important: language files must be saved as UTF-8 (without BOM)
  trigger_error('The language file <b>'.basename(__FILE__).'</b> is damaged, it must be saved <b>UTF-8</b> encoded!', E_USER_ERROR);
}

$LANG = array(
    '- create a new group -'
      => '- neue Gruppe erstellen -',
    '- do not use data from a previous event -'
      => '- keine Daten einer früheren Veranstaltung übernehmen -',
    '- no group -'
      => '- keine Gruppe -',
    '- out of stock -'
      => '- ausverkauft -',
    '- places available -'
      => '- freie Plätze -',
    '- select the redirect page -'
      => '- Zielseite auswählen -',
    '- unlimited -'
      => '- unbegrenzt -',
    'ACTIVE'
      => 'Aktiv',
    '<p>All events are shown!</p>'
      => '<p>Es werden alle Events angezeigt, die nicht gelöscht wurden!</p>',
    'Content'
      => 'Inhalt',
    'Copy also date and time of the event'
      => 'auch Datum und Uhrzeit der ausgewählten Veranstaltung übernehmen',
    'Copy Event'
      => 'Kerndaten übernehmen von',
    'Copy event data'
      => 'Daten einer Veranstaltung übernehmen',
    'Costs'
      => 'Kosten pro Teilnehmer (<i>-1 = Kostenfrei</i>)',
    'Create files'
      => 'Dateien erzeugen',
    'Create or edit event'
      => 'Veranstaltung erstellen oder bearbeiten',
    'Create or edit group'
      => 'Gruppe erstellen oder bearbeiten',
    'Create QR-Codes'
      => 'QR-Codes erstellen',
    'Date'
      => 'Datum',
    'Date/time'
      => 'Datum/Zeit',
    'Date from'
      => 'Datum: Beginn des Event',
    'Date to'
      => 'Datum: Ende des Event',
    'Deadline'
      => 'Anmeldeschluß',
    'Declared'
      => 'Angemeldet',
    'DELETED'
      => 'Gelöscht',
    'Description'
      => 'Beschreibung',
    'Description, long'
      => 'Beschreibung, lang',
    'Description, short'
      => 'Beschreibung, kurz',
    'Determine at which page the droplet [[kit_event]] is placed. This information is important for the automatic creation of the permaLinks'
      => 'Legen Sie die Seite fest, auf der das Droplet [[kit_event]] für diese Gruppe verwendet wird. Diese Festlegung ist wichtig, damit automatisch permaLinks erzeugt werden können.',
    'Determine if kitEvent should create iCal files'
      => 'Legen Sie fest, ob kitEvent iCal Dateien anlegen soll oder nicht',
    'Determine if kitEvent should use permanent links'
      => 'Legen Sie fest, ob kitEvent permanente Links verwenden soll oder nicht.',
    'Determine if kitEvent should create QR-Codes'
      => 'Legen Sie fest, ob kitEvent QR-Codes erstellen soll oder nicht.',
    'Determine if to use the long description'
      => 'Legen Sie fest, ob kitEvent die Langbeschreibung bei den Veranstaltungen verwenden soll.',
    'Determine if to use the short description'
      => 'Legen Sie fest, ob kitEvent die Kurzbeschreibung bei den Veranstaltungen verwenden soll.',
    'Directory'
      => 'Verzeichnis',
    'Error: cannot create the file {{ file }}!'
      => '<p>Die Datei <b>{{ file }}</b> konnte nicht geschrieben werden!</p>',
    'Error: cannot send the email to {{ email }}!'
      => '<p>Die E-Mail an <b>{{ email }}</b> konnte nicht versendet werden!</p>',
    'Error: No entry for the day of the week {{ day_of_week }}!'
      => '<p>Für den Wochentag mit der Nummer <b>{{ day_of_week }}</b> wurde kein gültiger Tagesname gefunden!</p>',
    'Error: No entry for the month number {{ month }}!'
      => '<p>Für den Monat <b>{{ month }}</b> wurde kein gültiger Monatsname gefunden!</p>',
    'Error: The event view <b>{{ view }}</b> is not specified!'
      => '<p>Die Ansicht <b>{{ view }}</b> ist nicht spezifiert und kann deshalb nicht angezeigt werden!</p>',
    'Error: The group {{ group }} does not exists, please check the params!'
      => '<p>Die Gruppe <b>{{ group }}</b> wurde nicht gefunden, prüfen Sie die Parameter!</p>',
    'Error: The id {{ id }} is invalid!'
      => '<p>Der Datensatz mit der <b>ID {{ id }}</b> ist ungültig!</p>',
    'Error: The must fields for the form are not defined!'
      => '<p>Fataler Fehler: die Pflichtfelder für das Formular sind nicht definiert!</p>',
    'Error: This event is invalid!'
      => '<p>Es wurde ein ungültiges Event angefordert.</p>',
    'Error correction'
      => 'Fehlerkorrektur',
    'Event'
      => 'Veranstaltung',
    'Event End'
      => 'Ende',
    'Event group'
      => 'Veranstaltungsgruppe',
    'Event link'
      => 'Ergänzender Link',
    'Event Start'
      => 'Beginn',
    'Event title'
      => 'Schlagzeile für die Veranstaltung',
    'Field 1 uses HTML'
      => 'Feld 1 verwendet HTML',
    'Field 2 uses HTML'
      => 'Feld 2 verwendet HTML',
    'Field 3 uses HTML'
      => 'Feld 3 verwendet HTML',
    'Field 4 uses HTML'
      => 'Feld 4 verwendet HTML',
    'Field 5 uses HTML'
      => 'Feld 5 verwendet HTML',
    'Free Fields'
      => 'Freie Datenfelder',
    'go back ...'
      => 'Zurück',
    'Group'
      => 'Gruppe',
    'Group name'
      => 'Bezeichner für die Gruppe',
    'If you give this free field a name it will be activated'
      => 'Geben Sie diesem freien Datenfeld eine Bezeichnung um es zu aktivieren. Freie Datenfelder können HTML Code enthalten und werden in die Suchfunktion eingebunden.',
    'If you have defined a pattern in the group definition, kitEvent will create a permanet link at the first save of this event.'
      => 'Wenn Sie in der Gruppendefinition ein Muster für die Erzeugung eines permanenten Link angegeben haben, wird dieser <b>beim ersten Speichern</b> dieses Event automatisch erzeugt.',
    'Lady'
        => 'Frau',
    'List of the active events'
      => 'Liste der aktuellen Veranstaltungen',
    'Location'
      => 'Veranstaltungsort',
    'LOCKED'
      => 'Gesperrt',
    'Long description'
      => 'Ausführliche Beschreibung (optional)',
    'Margin'
      => 'Randabstand',
    'Margin of the QR-Code, default is 2'
      => 'Randabstand des QR Code in Pixel, Vorgabe ist 2',
    'Message'
      => 'Mitteilung',
    'Mister'
        => 'Herr',
    'Name of Field 1'
      => 'Bezeichnung Feld 1',
    'Name of Field 2'
      => 'Bezeichnung Feld 2',
    'Name of Field 3'
      => 'Bezeichnung Feld 3',
    'Name of Field 4'
      => 'Bezeichnung Feld 4',
    'Name of Field 5'
      => 'Bezeichnung Feld 5',
    'Orders and messages'
      => 'Anmeldungen und Mitteilungen',
    'Page with details'
      => 'Detailseite',
    'Part. max.'
      => 'Tln. max.',
    'Part. total'
      => 'Tln. total',
    'Participants, max.'
      => 'Teilnehmer, max.',
    'Participants, total'
      => 'Teilnehmer, gesamt',
    'Pattern for the automatic generation of permanent links. Possible placeholders are <b>{&#x0024;ID}, {&#x0024;NAME}, {&#x0024;YEAR}, {&#x0024;MONTH}, {&#x0024;DAY}</b> and <b>{&#x0024;EXT}</b>.<br />The assigned permaLink is relative to the page directory, must star6t with a slash / and is closed by the file extension.<br />Sample: <b>/termine/{&#x0024;NAME}-{&#x0024;YEAR}{&#x0024;MONTH}{&#x0024;DAY}{&#x0024;EXT}</b>'
      => 'Muster für die automatische Erzeugung von permanenten Links (permaLinks). Möglich sind die Platzhalter <b>{&#x0024;ID}, {&#x0024;NAME}, {&#x0024;YEAR}, {&#x0024;MONTH}, {&#x0024;DAY}</b> und <b>{&#x0024;EXT}</b>.<br />Der angegebene permaLink bezieht sich relativ auf das Seitenverzeichnis, beginnt mit einem Slash / und endet mit der Dateiendung.<br />Beispiel: <b>/termine/{&#x0024;NAME}-{&#x0024;YEAR}{&#x0024;MONTH}{&#x0024;DAY}{&#x0024;EXT}</b>',
    'Perma Link'
      => 'Perma Link',
    'Permanent Link Pattern'
      => 'permaLink Muster',
    'Phone'
      => 'Telefon',
    '<p>Please accept our data privacy!</p>'
      => '<p>Bitte bestätigen Sie, dass Sie unsere <b>Datenschutzerklärung</b> akzeptieren.</p>',
    '<p>Please accept our terms and conditions!</p>'
      => '<p>Bitte bestätigen Sie, dass Sie unsere <b>Geschäftsbedingungen</b> akzeptieren.</p>',
    '<p>Please check the both dates from and to!</p>'
      => '<p>Prüfen Sie die Datumsangaben, das Enddatum für das Event liegt vor dem Beginn des Events!</p>',
    '<p>Please check the publishing date!</p>'
      => '<p>Bitte prüfen Sie das Veröffentlichungsdatum!</p>',
    'Please click to get more informations about the events of this day!'
      => 'Anklicken, um mehr über die Veranstaltungen an diesem Tag zu erfahren!',
    '<p>Please insert a event title!</p>'
      => '<p>Bitte geben Sie eine Schlagzeile für das Event an!</p>',
    '<p>Please type in the city!</p>'
      => '<p>Bitte geben Sie die <b>Stadt</b> an.</p>',
    '<p>Please type in the short description!</p>'
      => '<p>Die Kurzbeschreibung zu dem Event darf nicht leer sein! Bitte fügen Sie eine Kurzbeschreibung ein.</p>',
    '<p>Please type in your first name!</p>'
      => '<p>Bitte geben Sie Ihren <b>Vornamen</b> an.</p>',
    '<p>Please type in your phone number!</p>'
      => '<p>Bitte geben Sie eine <b>Telefonnummer</b> an.</p>',
    '<p>Please type in your last name!</p>'
      => '<p>Bitte geben Sie Ihren Vornamen an.</p>',
    '<p>Please type in your street!</p>'
      => '<p>Bitte geben Sie <b>Straße und Hausnummer</b> an.</p>',
    '<p>Please type in your ZIP code!</p>'
      => '<p>Bitte geben Sie Ihre Postleitzahl an!</p>',
    'Publish from'
      => 'Veröffentlichen ab',
    'Publish to'
      => 'Veröffentlichen bis',
    'QR-Code Size'
      => 'QR-Code Größe',
    'Select'
      => 'Auswählen',
    'Set the error correction level from 0 (low) to 3 (high), default is 2'
      => 'Legen Sie den Wert für die Fehlerkorrektur von 0 (niedrig) bis 3 (hoch) fest, Vorgabe ist 2',
    'Set to YES to activate HTML usage for this field'
      => 'Mit JA aktivieren Sie die HTML Ausgabe für dieses Feld',
    'Short Description'
      => 'Kurzbeschreibung (Pflicht)',
    'Show all events'
      => 'Alle Veranstaltungen anzeigen',
    'Status'
      => 'Status',
    'TAB_ABOUT'
      => '?',
    'TAB_CONFIG'
      => 'Einstellungen',
    'TAB_EDIT'
      => 'Bearbeiten',
    'TAB_GROUP'
      => 'Gruppen',
    'TAB_LIST'
      => 'Liste',
    'TAB_MESSAGES'
      => 'Mitteilungen',
    '<p>The CAPTCHA is invalid!</p>'
      => '<p>Der übermittelte Wert stimmt nicht mit dem Captcha überein.</p>',
    'The content of the QR-Code ()1=Perma Link, 2=iCal information), default is 2'
      => 'Inhalt, der im QR Code gespeichert wird (1=permaLink, 2=iCal Information), Vorgabe ist 1',
    '<p>The date {{ date }} for the field {{ field }} is invalid! Please type in the date in the format <i>mm-dd-YYYY</i>.</p>'
      => '<p>Die Datumsangabe <b>{{ date }}</b> für das Feld <b>{{ field }}</b> ist ungültig und konnte nicht gelesen werden! Geben Sie das Datum in der Form <i>dd.mm.YYYY</i> an!</p>',
    '<p>The deadline is invalid, please check the date!</p>'
      => '<p>Das Datum des Anmeldeschluß liegt nach dem Event, bitte prüfen Sie das Datum!</p>',
    '<p>The event group must be named!</p>'
      => '<p>Der Gruppen Bezeichner darf nicht leer sein!</p>',
    '<p>The event group with the ID {{ id }} was successfull created.</p>'
      => '<p>Die Gruppe mit der <b>ID {{ id }}</b> wurde hinzufügt.</p>',
    '<p>The event group with the ID {{ id }} was successfull updated</p>'
      => '<p>Die Gruppe mit der <b>ID {{ id }}</b> wurde aktualisiert!',
    '<p>The event group with the name {{ name }} already exists!</p>'
      => '<p>Es existiert bereits eine Gruppe mit dem Bezeichner <b>{{ name }}</b>, bitte wählen Sie einen anderen Bezeichner!</p>',
    '<p>The event with the {{ id }} was successfull created.</p>'
      => '<p>Die Veranstaltung <b>{{ id }}</b> wurde erfolgreich angelegt!</p>',
    '<p>The event with the ID {{ id }} was successfull updated.</p>'
      => '<p>Das Event <b>{{ id }}</b> wurde erfolgreich aktualisiert!</p>',
    '<p>The iCal file does not exists!</p>'
      => '<p>Es ist keine iCal Datei definiert!</p>',
    'The /MEDIA directory for <b>iCal</b> <i>*.ics</i> files.'
      => 'Das /MEDIA Verzeichnis für <b>iCal</b> <i>*.ics</i> Dateien',
    'The /MEDIA directory for QR Code files'
      => 'Das /MEDIA Verzeichnis für QR Code Dateien',
    'The message with the ID {{ id }} was successfull deleted.'
      => '<p>Die Mitteilung mit der ID {{ id }} wurde gelöscht.</p><p>Bitte vergessen Sie nicht, den eventuell frei gewordenen Platz bei den Events zu berücksichtigen.</p>',
    '<p>The permaLink {{ link }} was created!</p>'
      => '<p>Der permaLink <b>{{ link }}</b> wurde angelegt.</p>',
    '<p>The permaLink {{ link }} was deleted!</p>'
      => '<p>Der permaLink <b>{{ link }}</b> wurde gelöscht.</p>',
    'The QR-Code contains a link to this event'
      => 'Dieser QR Code enthält einen permanenten Link auf diesen Termin',
    'The QR-Code contains iCal informations'
      => 'Dieser QR Code enthält die Daten des Termin im iCal Format.',
    'The size of the created QR-Code from 1 to 40, default is 3'
      => 'Die Größe des erzeugten QR-Code von 1 bis 40, Vorgabe ist 3',
    '<p>There are no events for {{ date }}!</p>'
      => '<p>Für den <b>{{ date }}</b> sind leider keine Veranstaltungen eingetragen!</p>',
    '<p>There is no permaLink defined!</p>'
      => '<p>Es ist kein permaLink definiert!</p>',
    '<p>This event was taken from the previous event with the ID {{ id }}</p>'
      => '<p>Es wurden Daten aus dem Event mit der <b>ID {{ id }}</b> übernommen!</p>',
    'This list shows you all active events'
      => '<p>In dieser Liste sehen Sie alle aktuell anstehenden Veranstaltungen.</p>',
    'Time end'
      => 'Uhrzeit: Ende',
    'Time start'
      => 'Uhrzeit: Start',
    'Title'
      => 'Schlagzeile',
    '<p>To create a permaLink for this event, you must select a valid event group!</p>'
      => '<p>Damit für dieses Event ein permaLink angelegt werden kann, muss dieses Event einer Gruppe zugeordnet sein und in der Gruppendefinition eine Zielseite für die permaLinks definiert sein!</p>',
    'Use Perma Links'
      => 'Perma Links verwenden',
    '<p>Use this dialog to create or edit a group.</p><p>The name of the group should be a single word, it will be used as parameter for the droplet [[kit_event]]</p>'
      => '<p>Mit diesem Dialog können Sie eine neue Gruppe für ein Event (Ereignis) anlegen oder eine bestehende Gruppe bearbeiten.</p><p>Die <i>Bezeichner</i> für die Gruppen sollten möglichst aus einem einzelnen Wort bestehen und keine Leerzeichen, Sonderzeichen enthalten. Sie verwenden den Bezeichner als Parameter beim Aufruf des Droplets [[kit_event]].</p>',
    'With this dialog you can create a new event or edit an existing event.'
      => '<p>Mit diesem Dialog können Sie ein neues Event (Ereignis) anlegen oder ein bestehendes bearbeiten.</p>',
    'You can copy the data from a previous event to a new event, just select an event from the list.'
      => '<p>Sie können Daten eines früheren Event für das neue Event übernehmen. Wählen Sie dazu das passende Event aus.</p>'
    );
