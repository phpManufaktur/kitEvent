<?php

/**
 * kitEvent
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/kitEvent
 * @copyright 2011 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
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
    '- no contacts available in this distribution list -'
      => '- in diesem KIT Verteiler befinden sich keine Kontakte -',
    '- no distribution -'
      => '- kein Verteiler -',
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
    'Additional link'
      => 'Ergänzender Link',
    '<p>All events are shown!</p>'
      => '<p>Es werden alle Events angezeigt, die nicht gelöscht wurden!</p>',
    'Area, Category'
      => 'Region, Kategorie',

    'Basic settings'
      => 'Grundeinstellungen',

    'Content'
      => 'Inhalt',
    'Copy also date and time of the event'
      => 'auch Datum und Uhrzeit der ausgewählten Veranstaltung übernehmen',
    'Copy Event'
      => 'Veranstaltungsdaten übernehmen',
    'Costs'
      => 'Kosten, pro Teilnehmer',
    'Create files'
      => 'Dateien erzeugen',
    'Create new event'
      => 'Eine neue Veranstaltung erstellen',
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
      => 'Datum, von',
    'Date, Time and Participants'
      => 'Datum, Uhrzeit und Teilnehmer',
    'Date to'
      => 'Datum, bis',
    'Deadline'
      => 'Anmeldeschluß',
    'Declared'
      => 'Angemeldet',
    'Delete'
        => 'Löschen',
    'DELETED'
      => 'Gelöscht',
    'Description'
      => 'Beschreibung',
    'Description, long'
      => 'Beschreibung, lang',
    'Description, short'
      => 'Beschreibung, kurz',
    'Description of the event'
      => 'Beschreibung der Veranstaltung',
    'Determine a distribution group for the event locations in KeepInTouch. This will enable you to select the locations of your event from a list if you are creating or editing an event.'
      => 'Erstellen Sie in KeepInTouch eine Verteilerliste für die Veranstaltungsort und ordnen Sie den Verteiler dieser Gruppe zu. Sie können dann beim Erstellen und Bearbeiten die Veranstaltungsorte direkt aus einer Liste auswählen.',
    'Determine a distribution group for the organizer in KeepInTouch. This will enable you to select the organizer from a list if you are creating or editing an event.'
      => 'Erstellen Sie in KeepInTouch eine Verteilerliste für die Veranstalter und ordnen Sie den Verteiler dieser Veranstaltungsgruppe zu. Dies ermöglicht es Ihnen beim Erstellen oder Bearbeiten einer Veranstaltung (Event, Konzert ...) den Veranstalter direkt aus einer Liste auszuwählen.',
    'Determine a distribution group for the participants in KeepInTouch. If you do so, all participants of events in this group will be assigned to this group - it will be easy to contact this group or send a newsletter.'
      => 'Erstellen Sie in KeepInTouch eine Verteilerliste für die Teilnehmer der Veranstaltungen aus dieser Gruppe. Wenn Sie einen Verteiler festlegen, werden die Teilnehmer automatisch dieser Liste zugeordnet - das Versenden von Informationen, Newslettern etc. wird dadurch sehr einfach.',
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
    'End'
        => 'Ende',
    'Error: cannot create the directory {{ directory }}!'
      => '<p>Das Verzeichnis {{ directory }} konnte nicht angelegt werden!</p>',
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
      => '<b>Schlagzeile</b> für die Veranstaltung',
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

    'General'
      => 'Allgemein',
    'Go back ...'
      => 'Zurück',
    'Group'
      => 'Gruppe',
    'Groups'
      => 'Veranstaltungsgruppen',
    'Group name'
      => 'Bezeichner für die Gruppe',

    'If you give this free field a name it will be activated'
      => 'Geben Sie diesem freien Datenfeld eine Bezeichnung um es zu aktivieren. Freie Datenfelder können HTML Code enthalten und werden in die Suchfunktion eingebunden.',
    'If you have defined a pattern in the <a href="{{ link }}">group definition</a>, kitEvent will create a permanet link at the first save of this event.'
      => 'Wenn Sie in der <a href="{{ link }}">Gruppe für die Veranstaltung</a> ein Muster für die Erzeugung eines permanenten Link angegeben haben, wird dieser <b>beim ersten Speichern</b> des Event automatisch angelegt.',
    'If you want also copy all the dates and times of the previous event please check this box.'
      => 'Wenn Sie auch die Datums- und Zeitinformationen der früheren Veranstaltung übernehmen möchten, klicken Sie bitte die Checkbox an.',
    'In status <b>locked</b> the event will not published in the frontend and status <b>delete</b> finally remove the event.'
      => 'Im Status <b>Gesperrt</b> wird die Veranstaltung nicht veröffentlicht und ist nicht sichtbar. Der Status <b>Gelöscht</b> entfernt die Veranstaltung unwiederruflich.',

    'KIT Distribution'
      => 'KIT Verteiler',
    'KIT Distribution, Organizer'
      => 'KIT Verteiler, Veranstalter',
    'KIT Distribution, Participant'
      => 'KIT Verteiler, Teilnehmer',

    'Lady'
        => 'Frau',
    'List of the active events'
      => 'Liste der aktuellen Veranstaltungen',
    'Location'
      => 'Veranstaltungsort',
    'Location, alias'
      => 'Veranstaltungsort, Alias',
    'LOCKED'
      => 'Gesperrt',
    'Long Description'
      => 'Ausführliche Beschreibung',
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

    'Order details'
      => 'Details zu der Anmeldung',
    'Orders and messages'
      => 'Anmeldungen und Mitteilungen',
    'Organizer'
      => 'Veranstalter',
    'Organizer and Event Location'
      => 'Veranstalter und Veranstaltungsort',

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
      => 'Permanenter Link',
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
    '<p>Please help to improve open source software and report this problem to the <b><a href="{{ support }}" target="_blank">Addons Support Group</a></b> of the <b><a href="{{ phpmanufaktur }}" target="_blank">phpManufaktur</a></b>.</p>'
      => 'Bitte helfen Sie mit, diese Open Source Software zu verbessern und melden Sie das aufgetretene Problem in der <strong><a href="{{ support }}" target="_blank">Addons Support Group</a></strong> dem Team der <strong><a href="{{ phpmanufaktur }}" target="_blank">phpManufaktur</a></strong>.',
    '<p>Please insert a event title!</p>'
      => '<p>Bitte geben Sie eine Schlagzeile für das Event an!</p>',
    '<p>Please select the event group to which the new event will be added to.</p>'
      => '<p>Bitte wählen Sie die Veranstaltungsgruppe aus, der die neue Veranstaltung hinzugefügt werden soll.</p>',
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
      => 'Veröffentlichen, ab',
    'Publish to'
      => 'Veröffentlichen, bis',

    'QR-Code Size'
      => 'QR-Code Größe',

    'Register'
        => 'Anmelden',

    'Select'
      => 'Auswählen',
    'Select event group'
      => 'Veranstaltungsgruppe auswählen',
    'Set the error correction level from 0 (low) to 3 (high), default is 2'
      => 'Legen Sie den Wert für die Fehlerkorrektur von 0 (niedrig) bis 3 (hoch) fest, Vorgabe ist 2',
    'Set to YES to activate HTML usage for this field'
      => 'Mit JA aktivieren Sie die HTML Ausgabe für dieses Feld',
    'Short Description'
      => 'Kurzbeschreibung',
    'Show all events'
      => 'Alle Veranstaltungen anzeigen',
    'Sign up'
        => 'Anmelden',
    'Specify the start and end date of the event, they can be identical.'
      => 'Geben Sie das Start- und Enddatum für die Veranstaltung an.',
    'Start'
        => 'Beginn',
    'Status'
      => 'Status',
    'TAB_ABOUT'
      => '?',
    'TAB_EDIT'
      => 'Veranstaltung',
    'TAB_LIST'
      => 'Aktuelle Veranstaltungen',
    'TAB_MESSAGES'
      => 'Anmeldungen',
    '<p>The CAPTCHA is invalid!</p>'
      => '<p>Der übermittelte Wert stimmt nicht mit dem Captcha überein.</p>',
    'The content of the QR-Code ()1=Perma Link, 2=iCal information), default is 2'
      => 'Inhalt, der im QR Code gespeichert wird (1=permaLink, 2=iCal Information), Vorgabe ist 1',
    '<p>The date {{ date }} for the field {{ field }} is invalid! Please type in the date in the format <i>mm-dd-YYYY</i>.</p>'
      => '<p>Die Datumsangabe <b>{{ date }}</b> für das Feld <b>{{ field }}</b> ist ungültig und konnte nicht gelesen werden! Geben Sie das Datum in der Form <i>dd.mm.YYYY</i> an!</p>',
    '<p>The deadline is invalid, please check the date!</p>'
      => '<p>Das Datum des Anmeldeschluß liegt nach dem Event, bitte prüfen Sie das Datum!</p>',
    'The event group contains settings for the permanent link to the event, QR-Codes, Distribution list for the publisher and the participants. <a href="{{ link }}">Define as many event groups you need</a>.'
      => 'Die <a href="{{ link }}">Veranstaltungsgruppe</a> enthält u.a. Einstellungen für die permanenten Links, QR-Codes, Verteilerlisten für die Veranstalter und Teilnehmer.',
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
    'The <i>Long Description</i> is <a href="{{ link }}">not active</a>.'
      => 'Die <i>Ausführliche Beschreibung</i> ist <a href="{{ link }}">nicht aktiviert</a>.',
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
    'The <i>Short Description</i> is <a href="{{ link }}">not active</a>.'
      => 'Die <i>Kurzbeschreibung</i> ist <a href="{{ link }}">nicht aktiviert</a>.',
    'The size of the created QR-Code from 1 to 40, default is 3'
      => 'Die Größe des erzeugten QR-Code von 1 bis 40, Vorgabe ist 3',
    '<p>There are no events for {{ date }}!</p>'
      => '<p>Für den <b>{{ date }}</b> sind leider keine Veranstaltungen eingetragen!</p>',
    '<p>There is no permaLink defined!</p>'
      => '<p>Es ist kein permaLink definiert!</p>',
    '<p>This dialog shows you additional informations to the registration.</p><p>Click at the KIT ID to switch to the contact details or click at the event title to switch to the event details.</p>'
      => '<p>Dieser Dialog zeigt Ihnen zusätzliche Informationen zu der Anmeldung.</p><p>Klicken Sie auf die KIT ID um auf die Detailseite des Kontaktes zu wechseln oder klicken Sie auf den Titel der Veranstaltung um auf die Informationsseite der Veranstaltung zu wechseln.</p>',
    '<p>This event was taken from the previous event with the ID {{ id }}</p>'
      => '<p>Es wurden Daten aus dem Event mit der <b>ID {{ id }}</b> übernommen!</p>',
    'This list shows you all active events'
      => '<p>In dieser Liste sehen Sie alle aktuell anstehenden Veranstaltungen.</p>',
    '<p>This list shows you all registrations for your events.</p>'
      => '<p>Diese Liste zeigt Ihnen alle Anmeldungen sowie Anfragen zu Ihren Veranstaltungen.</p>',
    'Time end'
      => 'Uhrzeit, bis',
    'Time start'
      => 'Uhrzeit, von',
    'Title'
      => 'Schlagzeile',
    'To copy data from a previous event to the new event please the event to copy from. Informations about date and time will not copied.'
      => 'Um Daten von einer früheren Veranstaltung in die neue Veranstaltung zu übernehmen, wählen Sie bitte die gewünschte Veranstaltung aus. Datums- und Zeitinformationen werden nicht übernommen.',
    '<p>To create a permaLink for this event, you must select a valid event group!</p>'
      => '<p>Damit für dieses Event ein permaLink angelegt werden kann, muss dieses Event einer Gruppe zugeordnet sein und in der Gruppendefinition eine Zielseite für die permaLinks definiert sein!</p>',
    'Use Perma Links'
      => 'Perma Links verwenden',
    '<p>Use this dialog to create or edit a group.</p><p>The name of the group should be a single word, it will be used as parameter for the droplet [[kit_event]]</p>'
      => '<p>Mit diesem Dialog können Sie eine neue Gruppe für Veranstaltungen (Event, Konzert etc.) anlegen oder eine bestehende Gruppe bearbeiten.</p><p>Die <i>Bezeichner</i> für die Gruppen sollten möglichst aus einem einzelnen Wort bestehen und keine Leerzeichen, Sonderzeichen enthalten. Sie verwenden den Bezeichner als Parameter beim Aufruf des Droplets [[kit_event]].</p>',
    'Vacancies'
        => 'Freie Plätze',
    'With this dialog you can create a new event or edit an existing event.'
      => '<p>Mit diesem Dialog können Sie ein neues Event (Ereignis) anlegen oder ein bestehendes bearbeiten.</p>',
    'You can activate up to <b>{{ free }}</b> <a href="{{ link }}">additional <i>free fields</i></a>.'
      => 'Sie können zusätzlich bis zu <b>{{ free }}</b> <a href="{{ link }}">Freie Datenfelder</a> aktivieren.',
    'You can copy the data from a previous event to a new event, just select an event from the list.'
      => '<p>Sie können die Daten einer früheren Veranstaltung für die Anlage einer neuen Veranstaltung übernehmen. Wählen Sie dazu einfach die passende Veranstaltung aus.</p><p>Wenn Sie eine neue Veranstaltung ohne Vorbelegung erstellen möchten, klicken Sie bitte einfach auf <kbd>OK</kbd>.</p>'
    );
