=== Campi per Moduli Italiani ===
Contributors: mociofiletto
Donate link: https://paypal.me/GiuseppeF77
Tags: italiano, contact form 7, codice fiscale, comuni italiani, firma digitale
Requires at least: 4.7
Tested up to: 5.4
Requires PHP: 5.6
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html

Plugin per creare campi utili per siti italiani, da utilizzare nei moduli prodotti con Contact Form 7.

== Description ==

Questo plugin crea dei form-tag per Contact Form 7.
In questa versione sono disponibili 4 form-tag (e corrispondenti mail-tag):

* [gcmi-comune]: crea una serie di select per la selezione di un comune italiano
* [gcmi-cf]: crea un campo per l'inserimento del codice fiscale italiano di una persona fisica
* [gcmi-stato]: crea la possibilità di selezionare uno stato
* [formsign]: crea la possibilità di firmare digitalmente le mail inviate con una coppia di chiavi attribuita ad ogni singolo form

Il plugin al momento dell'installazione scarica i dati che utilizza dal sito dell'Istat e da quello dell'Agenzia delle entrate. Questi dati sono aggiornabili dalla console di amministrazione.
Il download dei dati e l'inserimento degli stessi nel database richiede diversi minuti: pazientate durante la fase di attivazione.
La selezione dei comuni è stata creata partendo dal codice di https://wordpress.org/plugins/regione-provincia-comune/

== Dati utilizzati ==

Questo plugin utilizza dati resi disponibili dall'ISTAT e dall'Agenzia delle entrate.
In particolare, vengono acquisiti e memorizzati dati messi a disposizione a queste URL:

* https://www.istat.it/it/archivio/6789
* https://www.istat.it/it/archivio/6747
* https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI

I dati pubblicati sul sito dell'ISTAT sono coperti da licenza Creative Commons - Attribuzione (CC-by) (https://creativecommons.org/licenses/by/3.0/it/), come indicato qui: https://www.istat.it/it/note-legali
I dati prelevati dal sito internet dell'Agenzia delle entrate sono di pubblico dominio e costituiscono una banca dati pubblica resa disponibile per consentire gli adempimenti tributari e, più in generale, per consentire l'identificazione delle persone fiiche presso le pubbliche amministrazioni italiane, tramite il codice fiscale.
I dati presenti sul sito dell'Agenzia delle entrate possono essere liberamente immagazzinati nel proprio computer o stampati (https://www.agenziaentrate.gov.it/portale/web/guest/privacy). I dati sono gestiti dall'Ufficio Archivio anagrafico dell'Agenzia delle entrate.
Questo plugin utilizza i dati prelevati dal sito internet dell'Agenzia delle entrate esclusivamente al fine di effettuare un controllo di regolarità formale del codice fiscale.
Questo plugin non riporta nelle pagine esterne del sito internet su cui è utilizzato, nessun collegamento né al sito dell'Agenzia delle entrate, né al sito dell'ISTAT; in particolare non viene effettuata alcuna forma di link diretto, né di deep linking.

== Come utilizzare i form-tag ==

[gcmi-comune]
`[gcmi-comune]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare è possibile impostare l'attributo "kind" a "tutti"; "attuali","evidenza_cessati". Nel primo e nel terzo caso, con modalità differenti, vengono proposti sia i comuni attualmente esistenti, sia quelli cessati in precedenza (utile, ad esempio, per consentire la selezione del Comune di nascita). Nella modalità "attuali", è invece consentita solo la selezione dei Comuni attualmente esistenti (utile per consentire la selezione del Comune di residenza / domicilio).
Inoltre è possibile settare l'opzione "comu_details", per mostrare dopo la cascata di select un'icona che consente la visualizzazione di una tabella modale con i dettagli statistici dell'unità territoriale.
Il valore restituito dal gruppo è sempre il codice ISTAT del Comune selezionato. Il corrispondente mail-tag, converte tale valore nella denominazione del comune seguita dall'indicazione della provincia.
La cascata di select, può essere utilizzata anche all'esteno di CF7, mediante lo shortcode [comune] (opzioni analoge a quelle del form-tag per Contact Form 7.

[gcmi-cf]
`[gcmi-cf]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare è possibile impostare varie opzioni di validazione consentendo di riscontrare la corrispondenza del codice fiscale con altri campi del modulo.
Nello specifico è possibile verificare che il codice fiscale corrisponda
con lo stato estero di nascita (selezionato mediante una select [gcmi-stato]), il comune italiano di nascita (selezionato mediante una cascata di select [gcmi-comune]), il sesso (indicando il nome di un campo form che restituisca "M" o "F"), la data di nascita. Nel caso in cui per selezionare la data di nascita si utilizzino più campi, uno per il giorno, uno per il mese e uno per l'anno, è possibile riconstrare la corrispondenza del codice fiscale con questi valori.

[gcmi-stato]
`[gcmi-stato]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare, è possibile impostare la selezione dei soli stati attualmente esistenti (opzione "only_current") ed è possibile impostare l'opzione "use_continent" per avere i valori della select suddivisi per continente. Il campo restituisce sempre il codice ISTAT dello Stato estero (codice 100 per l'Italia). Il codice ISTAT è il tipo di dato atteso da [gcmi-cf], per il riscontro del codice fiscale.

[formsign]
`[formsign]` _NON_ dispone di un gestore nell'area di creazione dei form CF7.
Per utilizzarlo è sufficiente inserire nel proprio modulo il tag seguito dal nome del campo: ad esempio [formsign firmadigitale]. Questo tag, creerà nel modulo un campo hidden con attributo name="firmadigitale" e nessun valore.
Per utilizzare il codice è anche necessario inserire nella mail o nelle mail che il form invia il campo [firmadigitale] (si consiglia al termine della mail).
In questo modo in coda alla mail verrà inserita una sequenza di tre righe contenenti:
l'ID del FORM di invio
un hash md5 dei dati trasmessi con il modulo (non del contenuto dei files eventualmente allegati)
una firma digitale dell'hash.
La firma viene apposta mediante la generazione di una coppia di chiavi RSA, attribuita a ciascun form.
Mediante il riscontro dell'hash e della firma, sarà possibile verificare che le mail siano state effettivamente spedite dal form e che i dati trasmessi dall'utente corrispondano a quanto registato.
Per agevolare il riscontro dei dati, è preferibile utilizzare "Flamingo" per l'archiviazione dei messaggi inviati. Infatti, nella schermata di admin di Flamengo viene creato uno specifico box che consente il riscontro dell'hash e della firma digitale inseriti nella mail.
Il sistema è utile nel caso in cui mediante il form si preveda di ricevere domande o candidature etc.. ed evita contestazioni in merito ai dati che i candidati pretendono di aver inviato e quanto registrato dal sistema in Flamingo.

== Installation ==

= Installazione automatica =

1. Pannello di amministrazione plugin e opzione `aggiungi nuovo`.
2. Ricerca nella casella di testo `campi moduli italiani`.
3. Posizionati sulla descrizione di questo plugin e seleziona installa.
4. Attiva il plugin dal pannello di amministrazione di WordPress.
NOTA: l'attivazione richiede diversi minuti, perché vengono scaricate le tabelle di dati aggiornati dai siti ufficiali (Istat e Agenzia delle entrate e poi i dati vengono importati nel database)

= Installazione manuale file ZIP =

1. Scarica il file .ZIP da questa schermata.
2. Seleziona opzione aggiungi plugin dal pannello di amministrazione.
3. Seleziona opzione in alto `upload` e seleziona il file che hai scaricato.
4. Conferma installazione e attivazione plugin dal pannello di amministrazione.
NOTA: l'attivazione richiede diversi minuti, perché vengono scaricate le tabelle di dati aggiornati dai siti ufficiali (Istat e Agenzia delle entrate e poi i dati vengono importati nel database)

= Installazione manuale FTP =

1. Scarica il file .ZIP da questa schermata e decomprimi.
2. Accedi in FTP alla tua cartella presente sul server web.
3. Copia tutta la cartella `gcmi` nella directory `/wp-content/plugins/`
4. Attiva il plugin dal pannello di amministrazione di WordPress.
NOTA: l'attivazione richiede diversi minuti, perché vengono scaricate le tabelle di dati aggiornati dai siti ufficiali (Istat e Agenzia delle entrate e poi i dati vengono importati nel database)

== Frequently Asked Questions ==

= Dove sono le FAQ? =

Ancora non ci sono. :)

== Screenshots ==

1. Immagine dei form-tag [gcmi-stato] e [gcmi-comune] in un form
2. Immagine del form-tag [gcmi-cf] in un form
3. Immagine del blocco "firma digitale" inserito in calce ad una mail mediante il form-tag [formsign]
4. Immagine del meta-box di verifica dei codici hash e firma digitale in Flamingo
5. Immagine della schermata di admin, da cui è possibile effettuare l'aggiornamento dei dati

== Changelog ==

= 1.0.3
* Bug fix: error in hash calculation on modules/formsign/wpcf7-formsign-formtag.php

= 1.0.2 =
* Aggiornamenti di alcune stringhe della traduzione.
* Bug fix (addslashes prima di calcolare hash di verifica)

= 1.0.1 =
* Aggiornato il text domain allo slug assegnato da wordpress.

= 1.0.0 =
* Primo rilascio del plugin.

== Upgrade Notice ==

= 1.0.0 =
Prima installazione

