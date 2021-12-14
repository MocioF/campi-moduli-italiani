=== Campi Moduli Italiani ===
Contributors: mociofiletto
Donate link: https://paypal.me/GiuseppeF77
Tags: italiano, contact form 7, codice fiscale, comuni italiani, firma digitale
Requires at least: 4.7
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html

Plugin per creare campi utili per siti italiani, da utilizzare nei moduli prodotti con Contact Form 7 e WPForms.

== Description ==
Questo plugin crea dei form-tag per Contact Form 7 e dei campi per WPForms.

= Contact Form 7 =
In questa versione sono disponibili 4 form-tag (e corrispondenti mail-tag):
* [comune]: crea una serie di select per la selezione di un comune italiano
* [cf]: crea un campo per l'inserimento del codice fiscale italiano di una persona fisica
* [stato]: crea la possibilità di selezionare uno stato
* [formsign]: crea la possibilità di firmare digitalmente le mail inviate con una chiave privata attribuita ad ogni singolo form

= WPForms =
Sono disponibili 2 tipi di campi
* Selezione a cascata di un comune italiano (restituisce il codice comune ISTAT come valore)
* Un campo per selezionare uno stato (restituisce il codice paese ISTAT come valore)

== Dati utilizzati ==
Il plugin al momento dell'attivazione scarica i dati che utilizza dal sito web dell'Istat e da quello dell'Agenzia delle entrate. Questi dati sono aggiornabili dalla console di amministrazione.
Il download dei dati e l'inserimento degli stessi nel database richiede diversi minuti: pazientate durante la fase di attivazione.
La selezione dei comuni è stata creata partendo dal codice di https://wordpress.org/plugins/regione-provincia-comune/

Questo plugin utilizza dati resi disponibili dall'ISTAT e dall'Agenzia delle entrate.
In particolare, vengono acquisiti e memorizzati dati messi a disposizione a queste URL:

* https://www.istat.it/it/archivio/6789
* https://www.istat.it/it/archivio/6747
* https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI

I dati pubblicati sul sito dell'ISTAT sono coperti da licenza Creative Commons - Attribuzione (CC-by) (https://creativecommons.org/licenses/by/3.0/it/), come indicato qui: https://www.istat.it/it/note-legali
I dati prelevati dal sito web dell'Agenzia delle entrate sono di pubblico dominio e costituiscono una banca dati pubblica resa disponibile per consentire gli adempimenti tributari e, più in generale, per consentire l'identificazione delle persone fisiche presso le pubbliche amministrazioni italiane, tramite il codice fiscale.
I dati presenti sul sito dell'Agenzia delle entrate possono essere liberamente immagazzinati nel proprio computer o stampati (https://www.agenziaentrate.gov.it/portale/web/guest/privacy). I dati sono gestiti dall'Ufficio Archivio anagrafico dell'Agenzia delle entrate.
Questo plugin utilizza i dati prelevati dal sito internet dell'Agenzia delle entrate esclusivamente al fine di effettuare un controllo di regolarità formale del codice fiscale.
Questo plugin non riporta nelle pagine esterne del sito internet su cui è utilizzato, nessun collegamento né al sito dell'Agenzia delle entrate, né al sito dell'ISTAT; in particolare non viene effettuata alcuna forma di link diretto, né di deep linking.

== Come utilizzare i form-tag in Contact Form 7 ==
[comune]
`[comune]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare è possibile impostare l'attributo "kind" a "tutti"; "attuali","evidenza_cessati". Nel primo e nel terzo caso, con modalità differenti, vengono proposti sia i comuni attualmente esistenti, sia quelli cessati in precedenza (utile, ad esempio, per consentire la selezione del Comune di nascita). Nella modalità "attuali", è invece consentita solo la selezione dei comuni attualmente esistenti (utile per consentire la selezione del Comune di residenza / domicilio).
Inoltre è possibile settare l'opzione "comu_details", per mostrare dopo la cascata di select un'icona che consente la visualizzazione di una tabella modale con i dettagli statistici dell'unità territoriale.
Il valore restituito dal gruppo è sempre il codice ISTAT del comune selezionato. Il corrispondente mail-tag, converte tale valore nella denominazione del comune seguita dall'indicazione della targa automobilistica della provincia.
Dalla versione 1.1.1 vengono creati anche dei campi hidden popolati con le stringhe corrispondenti alla denominazione di regione, provincia e comune selezionati, utili per essere utilizzanti in plugin che catturano direttamente i dati trasmessi dal form (come "Send PDF for Contact Form 7")
La cascata di select, può essere utilizzata anche all'esterno di CF7, mediante lo shortcode [comune] (opzioni analoge a quelle del form-tag per Contact Form 7).

[cf]
`[cf]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare è possibile impostare varie opzioni di validazione consentendo di riscontrare la corrispondenza del codice fiscale con altri campi del modulo.
Nello specifico è possibile verificare che il codice fiscale corrisponda con lo stato estero di nascita (selezionato mediante una select [stato]), il comune italiano di nascita (selezionato mediante una cascata di select [comune]), il sesso (indicando il nome di un campo form che restituisca "M" o "F"), la data di nascita. Nel caso in cui per selezionare la data di nascita si utilizzino più campi, uno per il giorno, uno per il mese e uno per l'anno, è possibile riscontrare la corrispondenza del codice fiscale con questi valori.

[stato]
`[stato]` dispone di un gestore nell'area di creazione dei form CF7 che consente di impostare le varie opzioni.
In particolare, è possibile impostare la selezione dei soli stati attualmente esistenti (opzione "only_current") ed è possibile impostare l'opzione "use_continent" per avere i valori della select suddivisi per continente. Il campo restituisce sempre il codice ISTAT dello Stato estero (codice 100 per l'Italia). Il codice ISTAT è il tipo di dato atteso da [cf], per il riscontro del codice fiscale.

[formsign]
`[formsign]` _NON_ dispone di un gestore nell'area di creazione dei form CF7.
Per utilizzarlo è sufficiente inserire nel proprio modulo il tag seguito dal nome del campo: ad esempio [formsign firmadigitale]. Questo tag, creerà nel modulo un campo hidden con attributo name="firmadigitale" e nessun valore.
Per utilizzare il codice è anche necessario inserire nella mail o nelle mail che il form invia il campo [firmadigitale] (si consiglia al termine della mail).
In questo modo in coda alla mail verrà inserita una sequenza di due righe contenenti:
un hash md5 dei dati trasmessi con il modulo (non del contenuto dei files eventualmente allegati)
una firma digitale dell'hash.
La firma viene apposta mediante la generazione di una coppia di chiavi RSA, attribuita a ciascun form.
Mediante il riscontro dell'hash e della firma, sarà possibile verificare che le mail siano state effettivamente spedite dal form e che i dati trasmessi dall'utente corrispondano a quanto registato.
Per agevolare il riscontro dei dati, è preferibile utilizzare "Flamingo" per l'archiviazione dei messaggi inviati. Infatti, nella schermata di admin di Flamingo viene creato uno specifico box che consente il riscontro dell'hash e della firma digitale inseriti nella mail.
Il sistema è utile nel caso in cui mediante il form si preveda di ricevere domande di iscrizione o candidature etc.. ed evita contestazioni in merito ai dati che i candidati pretendono di aver inviato e quanto registrato dal sistema in Flamingo.

## Code
Vuoi controllare il codice?  [https://github.com/MocioF/campi-moduli-italiani](https://github.com/MocioF/campi-moduli-italiani)

== Installazione ==

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
3. Copia tutta la cartella `campi-moduli-italiani` nella directory `/wp-content/plugins/`
4. Attiva il plugin dal pannello di amministrazione di WordPress.
NOTA: l'attivazione richiede diversi minuti, perché vengono scaricate le tabelle di dati aggiornati dai siti ufficiali (Istat e Agenzia delle entrate e poi i dati vengono importati nel database)

== Frequently Asked Questions ==

= Come prelevare valori predefiniti dal contesto ? =

Dalla versione 1.2, [comune], [stato] e [cf] supportano il metodo standard di Contact Form 7 per ottenere valori dal contesto.
Inoltre, tutti supportano valori predefiniti nel tag.
Cerca qui per maggiori informazioni: https://contactform7.com/getting-default-values-from-the-context/
[comune] utilizza javascript per essere riempito con il valore predefinito o contestuale.

= Come posso segnalare un bug? =
Puoi inviare una richiesta nel nostro repository Github:
[https://github.com/MocioF/campi-moduli-italiani](https://github.com/MocioF/campi-moduli-italiani)

== Screenshots ==

1. Immagine dei form-tag [stato] e [comune] in un form
2. Immagine del form-tag [cf] in un form
3. Immagine del blocco "firma digitale" inserito in calce ad una email mediante il form-tag [formsign]
4. Immagine del meta-box di verifica dei codici hash e firma digitale in Flamingo
5. Immagine della schermata di admin, da cui è possibile effettuare l'aggiornamento dei dati

== Changelog ==
= 2.0.5 =
* Aggiunto il certificato della nuoca CA di istat.it per cUrl. Corregge: https://wordpress.org/support/topic/attivazione-vietata-forbidden/

= 2.0.3 =
* Correzioni di bug minori [#1](https://github.com/MocioF/campi-moduli-italiani/issues/1).

= 2.0.2 =
* Utilizza la data di aggiornamento del file remoto di comuni_attuali per codici_catastali

= 2.0.1 =
* Correzioni di bug minori

= 2.0.0 =
* aggiunto un campo per selezionare un Comune a WPForms
* rimossa la definizione di una variabile in global scope
* aggiunto l'uso di gruppi di opzioni nella selezione del paese 

= 1.3.0 =
* prima integrazione con WPForms

= 1.2.2 =
* modificata tabella _comuni_variazioni (l'ISTAT ha cambiato il formato del file)
* modificata tabella _comuni_soppressi (l'ISTAT ha cambiato il formato del file)
* aggiornato jquery-ui-dialog.css alla versione utilizzata in WP 5.6
* aggiunte classi standard wpcf7 a [comune] (wpcf7-select), [stato] (wpcf7-select) e [cf] (wpcf7-text)
* modificato il comportamento dell'opzione "use_label_element" in [comune]: se non è settata, non verrà visualizzata alcuna stringa prima delle select
* modificati i precedenti valori dei primi elementi utilizzati come label nelle selezioni di [comune]
* aggiunta l'opzione per utilizzare un primo valore come etichetta nella select di [stato] (Seleziona un paese) 
* modificato il nome della classe "gcmi_wrap" in "gcmi-wrap"
* per [comune] è ora possibile impostare classi personalizzate sia per il tag span contenitore, sia per le select
*
* [comune] shortcode (non per CF7):
* modificato il nome della classe "gcmi_comune" in "gcmi-comune"
* aggiunta l'opzione "use_label_element"; predefinito a true
* rimossi gli elementi <p> e <div> dal codice html generato

= 1.2.1 =
* Bug fix: corretto [stato] che non sostitutiva il mail-tag con il nome paese

= 1.2.0 =
* Aggiunto supporto per i valori di default dal contesto in [comune], [cf] e [stato]. Utilizzata la sintassi standard di Contact Form 7. Leggi: https://contactform7.com/getting-default-values-from-the-context/
* Correzioni di bug minori

= 1.1.3 =
* Correzioni di bug minori

= 1.1.2 =
* Sistemato il charset per https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv (set di dati "current_communes", tabella _gcmi_comuni_attuali). Aggiorna la tabella dalla console di amministrazione se alcuni nomi hanno caratteri non corrispondenti
* Corretti errori minori in class-gcmi-comune.php

= 1.1.1 =
* Aggiunti dei campi hidden che contengono la denominazione di comune, provincia e regione selezionati per poter essere utilizzati all'interno di plugin che creano dei PDF
* Impostati set_time_limit(360) nella routine di attivazione
* Aggiunto readme.txt in inglese

= 1.1.0 =
* Modificato controllo firma mail: l'ID del form viene determinato direttamente dai dati di Flamingo e non è più inserito nel corpo della mail
* Inseriti link alle review e alla pagina di supporto nella pagina dei plugins
* Modificate routine di importazione database "comuni attuali", a seguito di modifica nei file ISTAT da giugno 2020
* Modificato sistema di rilevazione aggiornamento file remoti

= 1.0.3 =
* Bug fix: errore nel calcolo dell'hash in modules/formsign/wpcf7-formsign-formtag.php

= 1.0.2 =
* Aggiornamenti di alcune stringhe della traduzione.
* Bug fix (addslashes prima di calcolare hash di verifica)

= 1.0.1 =
* Aggiornato il text domain allo slug assegnato da wordpress.

= 1.0.0 =
* Prima versione del plugin.

== Upgrade Notice ==
= 2.0.0 =
Integrato anche con WPForms

= 1.1.0 =
L'ISTAT ha modificato il formato del suo database.
Dopo questo aggiornamento è necessario aggiornare la tabella relativa ai comuni attuali [comuni_attuali].
È consigliato anche aggiornare le tabelle relativa ai comuni soppressi [comuni_soppressi] e alle variazioni [comuni_variazioni]

= 1.0.0 =
Prima installazione

