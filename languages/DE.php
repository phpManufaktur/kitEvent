<?php

/**
 * kitEvent
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

define('event_cfg_cal_prev_month',								'&laquo;');
define('event_cfg_cal_next_month',								'&raquo;');
define('event_cfg_currency',											'%s €');
define('event_cfg_date_separator',								'.');
define('event_cfg_date_str',											'd.m.Y');
define('event_cfg_datetime_str',									'd.m.Y H:i');
define('event_cfg_day_names',											"Sonntag, Montag, Dienstag, Mittwoch, Donnerstag, Freitag, Samstag");
define('event_cfg_decimal_separator',             ',');
define('event_cfg_month_names',										"Januar,Februar,März,April,Mai,Juni,Juli,August,September,Oktober,November,Dezember");
define('event_cfg_thousand_separator',						'.');
define('event_cfg_time_long_str',									'H:i:s');
define('event_cfg_time_str',											'H:i');
define('event_cfg_time_zone',											'Europe/Berlin');
define('event_cfg_title',													'Herr,Frau');

define('event_btn_abort',													'Abbruch');
define('event_btn_ok',														'Übernehmen');

define('event_desc_cfg_exec',											'Legen Sie fest, ob der EventCalendar ausgeführt wird oder nicht (1=JA, 0=NEIN)');

define('event_error_cal_dayofweek_def_invalid',		'<p>Für den Wochentag mit der Nummer <b>%d</b> wurde kein gültiger Tagesname gefunden!</p>');
define('event_error_cal_month_def_invalid',				'<p>Für den Monat <b>%d</b> wurde kein gültiger Monatsname gefunden!</p>');
define('event_error_cfg_id',											'<p>Der Konfigurationsdatensatz mit der <b>ID %05d</b> konnte nicht ausgelesen werden!</p>');
define('event_error_cfg_name',										'<p>Zu dem Bezeichner <b>%s</b> wurde kein Konfigurationsdatensatz gefunden!</p>');
define('event_error_evt_invalid',									'<p>Es wurde ein ungültiges Event angefordert.</p>');
define('event_error_evt_params_missing',					'<p>Es wurden nicht alle erforderlichen Parameter übergeben!</p>');
define('event_error_evt_unspecified',							'<p>Die Ansicht <b>%s</b> ist nicht spezifiert und kann deshalb nicht angezeigt werden!</p>');
define('event_error_group_invalid',								'<p>Die Gruppe <b>%s</b> wurde nicht gefunden, prüfen Sie die Parameter!</p>'); 
define('event_error_id_invalid',									'<p>Der Datensatz mit der <b>ID %03d</b> wurde nicht gefunden!</p>');
define('event_error_must_fields_missing',					'<p>Fataler Fehler: die Pflichtfelder für das Formular sind nicht definiert!</p>');
define('event_error_preset_not_exists',						'<p>Das Presetverzeichnis <b>%s</b> existiert nicht, die erforderlichen Templates können nicht geladen werden!</p>');
define('event_error_send_email',									'<p>Die E-Mail an <b>%s</b> konnte nicht versendet werden!</p>');
define('event_error_template_error',							'<p>Fehler bei der Ausführung des Template <b>%s</b>:</p><p>%s</p>');

define('event_header_edit_event',									'Event bearbeiten oder erstellen');
define('event_header_edit_group',									'Gruppe bearbeiten oder erstellen');
define('event_header_event_list',									'Übersicht über die aktuellen Events');
define('event_header_messages_list',							'Mitteilungen und Anmeldungen');
define('event_header_suggest_event',							'Daten eines Event übernehmen');

define('event_intro_edit_event',									'<p>Mit diesem Dialog können Sie ein neues Event (Ereignis) anlegen oder ein bestehendes bearbeiten.</p>');
define('event_intro_edit_group',							 		'<p>Mit diesem Dialog können Sie eine neue Gruppe für ein Event (Ereignis) anlegen oder eine bestehende Gruppe bearbeiten.</p><p>Die <i>Bezeichner</i> für die Gruppen sollten möglichst aus einem einzelnen Wort bestehen und keine Leerzeichen, Sonderzeichen enthalten. Sie verwenden den Bezeichner als Parameter beim Aufruf des Droplets [[kit_event]].</p>');
define('event_intro_event_list',									'<p>In dieser Liste sehen Sie alle aktuell anstehenden Veranstaltungen.</p>');
define('event_intro_suggest_event',								'<p>Sie können Daten eines früheren Event für das neue Event übernehmen. Wählen Sie dazu das passende Event aus.</p>');

define('event_hint_click_for_detail',							'Anklicken, um mehr über die Veranstaltungen an diesem Tag zu erfahren!');
define('event_hint_previous_month',								'Vorheriger Monat');
define('event_hint_next_month',										'Nächster Monat');

define('event_label_cfg_exec',										'EventCalendar ausführen');
define('event_label_date',												'Datum');
define('event_label_deadline',										'Anmeldeschluß');
define('event_label_declared',										'Angemeldet');
define('event_label_email',												'E-Mail');
define('event_label_event',												'Veranstaltung');
define('event_label_event_costs',									'Kosten pro Teilnehmer (<i>-1 = Kostenfrei</i>)');
define('event_label_event_date_from',							'Datum: Beginn des Event');
define('event_label_event_date_to',								'Datum: Ende des Event');
define('event_label_event_group',									'Event Gruppe'); 
define('event_label_event_link',									'Ergänzender Link');
define('event_label_event_location',							'Veranstaltungsort');
define('event_label_event_title',									'Schlagzeile für das Event');
define('event_label_event_time_start',						'Uhrzeit: Beginn des Event');
define('event_label_event_time_end',							'Uhrzeit: Ende des Event');
define('event_label_free_field_nr',								'Freies Feld %d');
define('event_label_group_description',						'Beschreibung der Gruppe');
define('event_label_group_name',									'Bezeichner für die Gruppe');
define('event_label_group_select',								'Gruppe auswählen');
define('event_label_long_description',						'Langbeschreibung (Optional)');
define('event_label_message',											'Mitteilung');
define('event_label_participants_max',						'Anzahl Teilnehmer (<i>-1 = unbegrenzt</i>)');
define('event_label_participants_total',					'Gemeldete Teilnehmer');
define('event_label_phone',												'Telefon');
define('event_label_publish_from',								'Event veröffentlichen ab');
define('event_label_publish_to',									'Event veröffentlichen bis');
define('event_label_select_event',								'Event auswählen');
define('event_label_short_description',						'Kurzbeschreibung (Pflicht)');
define('event_label_show_all',										'alle Events anzeigen');
define('event_label_status',											'Status');

define('event_msg_captcha_invalid',								'<p>Der übermittelte Wert stimmt nicht mit dem Captcha überein.</p>');
define('event_msg_date_from_to_invalid',					'<p>Prüfen Sie die Datumsangaben, das Enddatum für das Event liegt vor dem Beginn des Events!</p>');
define('event_msg_date_from_in_past',							'<p>Der Beginn des Events liegt in der Vergangenheit, prüfen Sie das Datum!</p>');
define('event_msg_date_invalid',									'<p>Die Datumsangabe <b>%s</b> für das Feld <b>%s</b> ist ungültig und konnte nicht gelesen werden! Geben Sie das Datum in der Form <i>dd.mm.YYYY</i> an!</p>');
define('event_msg_deadline_invalid',							'<p>Das Datum des Anmeldeschluß liegt nach dem Event, bitte prüfen Sie das Datum!</p>');
define('event_msg_event_inserted',								'<p>Das Event <b>%03d</b> wurde erfolgreich angelegt!</p>');
define('event_msg_event_take_suggestion',					'<p>Es wurden Daten aus dem Event mit der <b>ID %03d</b> übernommen!</p>');
define('event_msg_event_title_missing',						'<p>Bitte geben Sie eine Schlagzeile für das Event an!</p>');
define('event_msg_event_updated',									'<p>Das Event <b>%03d</b> wurde erfolgreich aktualisiert!</p>');
define('event_msg_group_already_exists',					'<p>Es existiert bereits eine Gruppe mit dem Bezeichner <b>%s</b>, bitte wählen Sie einen anderen Bezeichner!</p>');
define('event_msg_group_created',									'<p>Die Gruppe mit der <b>ID %03d</b> wurde hinzufügt.</p>');
define('event_msg_group_name_empty',							'<p>Der Gruppen Bezeichner darf nicht leer sein!</p>');
define('event_msg_group_updated',									'<p>Die Gruppe mit der <b>ID %03d</b> wurde aktualisiert!');
define('event_msg_invalid_email',									'<p>Die E-Mail Adresse <b>%s</b> ist nicht gültig, bitte prüfen Sie Ihre Eingabe.</p>');
define('event_msg_must_city',											'<p>Bitte geben Sie die <b>Stadt</b> an.</p>');
define('event_msg_must_first_name',								'<p>Bitte geben Sie Ihren <b>Vornamen</b> an.</p>');
define('event_msg_must_last_name',								'<p>Bitte geben Sie Ihren <b>Nachnamen</b> an.</p>');
define('event_msg_must_phone',										'<p>Bitte geben Sie eine <b>Telefonnummer</b> an.</p>');
define('event_msg_must_street',										'<p>Bitte geben Sie <b>Straße und Hausnummer</b> an.</p>');
define('event_msg_must_terms_and_conditions',			'<p>Bitte bestätigen Sie, dass Sie unsere <b>Geschäftsbedingungen</b> akzeptieren.</p>');
define('event_msg_must_zip',											'<p>Bitte geben Sie die <b>Postleitzahl</b> an.</p>');
define('event_msg_no_event_at_date',							'<p>Für den <b>%s</b> sind leider keine Veranstaltungen eingetragen!</p>');
define('event_msg_publish_from_check',						'<p>Bitte prüfen Sie das Startdatum der Veröffentlichung!</p>');
define('event_msg_publish_from_invalid',					'<p>Das Veröffentlichungsdatum liegt nach dem Event! Prüfen Sie die Datumsangaben!</p>');
define('event_msg_publish_to_check',							'<p>Bitte prüfen Sie das Enddatum der Veröffentlichung!</p>');
define('event_msg_publish_to_invalid',						'<p>Das Ende der Veröffentlichung liegt vor dem Event! Prüfen Sie die Datumsangaben!</p>');
define('event_msg_short_description_empty',				'<p>Die Kurzbeschreibung zu dem Event darf nicht leer sein! Bitte fügen Sie eine Kurzbeschreibung ein.</p>');
define('event_msg_show_all_events',								'<p>Es werden alle Events angezeigt, die nicht gelöscht wurden!</p>');
define('event_msg_time_invalid',									'<p>Die Zeitangabe <b>%s</b> für das Feld <b>%s</b> ist ungültig und konnte nicht gelesen werden! Geben Sie die Uhrzeit in der Form <i>HH:mm</i> an!</p>');

define('event_status_active',											'Aktiv');
define('event_status_deleted',										'Gelöscht');
define('event_status_locked',											'Gesperrt');

define('event_tab_about',													'?');
define('event_tab_edit',													'Event bearbeiten');
define('event_tab_group',													'Gruppen');
define('event_tab_list',													'Aktuelle Events');
define('event_tab_messages',											'Mitteilungen');

define('event_text_create_new_group',							'- neue Gruppe erstellen -');
define('event_text_back',													'Zurück...');
define('event_text_yes',													'JA');
define('event_text_no',														'NEIN');
define('event_text_no_group',											'- keine Gruppe -');
define('event_text_none',													'- keine -');
define('event_text_fully_booked',									'<b>- ausgebucht -</b>');
define('event_text_participants_free',						'- noch Plätze frei -');
define('event_text_participants_unlimited',				'- offen -');
define('event_text_select_no_event',							'- keine Daten übernehmen -');
define('event_text_confirmed',										'Bestätigt');
define('event_text_not_confirmed',								'Nicht bestätigt');

define('event_th_id',															'ID');
define('event_th_date',														'Datum');
define('event_th_date_time',											'Datum/Zeit');
define('event_th_date_from',											'Datum von');
define('event_th_date_to',												'Datum bis');
define('event_th_declared',												'TLN');
define('event_th_email',													'E-Mail');
define('event_th_event',													'Veranstaltung');
define('event_th_group',													'Gruppe');
define('event_th_message',												'Mitteilung');
define('event_th_name',														'Name');
define('event_th_participants_max',								'Tln. max.');
define('event_th_participants_total',							'Tln. total');
define('event_th_deadline',												'Meldeschluß');
define('event_th_title',													'Schlagzeile');

?>