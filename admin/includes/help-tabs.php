<?php

class GCMI_Help_Tabs {

	private $screen;

	public function __construct( WP_Screen $screen ) {
		$this->screen = $screen;
	}

	public function set_help_tabs( $type ) {
		switch ( $type ) {
			case 'gcmi':
				$this->screen->add_help_tab(
					array(
						'id'      => 'gcmi_overview',
						'title'   => __( 'Overview', 'campi-moduli-italiani' ),
						'content' => $this->content( 'gcmi_overview' ),
					)
				);

				$this->screen->add_help_tab(
					array(
						'id'      => 'gcmi_update_tables',
						'title'   => __( 'Update tables', 'campi-moduli-italiani' ),
						'content' => $this->content( 'update_tables_overview' ),
					)
				);

				$this->sidebar();

				return;
		}
	}

	private function content( $name ) {
		$content                  = array();
		$content['gcmi_overview'] = '<p>' . sprintf(
		/* translators: %1$s: Contact Form 7, plugin page link; %2$s: link to the page where ISTAT publishes used data; %3$s: link to the page where Agenzia delle entrate publishes used data */
			esc_html__( '"Campi Moduli Italiani" creates shortcodes and, if %1$s is activated, form-tags, useful into Italian forms. The first module written is used to select an Italian municipality. Optionally it can show details of selected municipality. The data used are retrivied from %2$s and from %3$s.', 'campi-moduli-italiani' ),
			'<a href="https://contactform7.com" target="_blank">Contact Form 7</a>',
			'<a href="https://www.istat.it/it/archivio/6789" target="_blank">https://www.istat.it/it/archivio/6789</a>',
			'<a href="https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI" target="_blank">https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI</a>'
		) . '</p>';

		$content['update_tables_overview'] = '<p>' . sprintf(
			/* translators: %1$s: link to ISTAT website; %2$s: link to the page where ISTAT publishes used data */
			esc_html__( 'On this screen, you can update tables by direct data download from %1$s and %2$s. For details about downloaded data, visit %3$s.', 'campi-moduli-italiani' ),
			'<a href="https://www.istat.it" target="_blank">https://www.istat.it</a>',
			'<a href="https://www.agenziaentrate.gov.it" target="_blank">https://www.agenziaentrate.gov.it</a>',
			'<a href="https://www.istat.it/it/archivio/6789" target="_blank">https://www.istat.it/it/archivio/6789</a>'
		) . '</p>';
		$content['update_tables_overview'] .= '<p>' . esc_html__( 'Check the update dates of your data and the update dates of the online files, pick tables to update, select the "Update tables" bulk action and click on "Apply".', 'campi-moduli-italiani' ) . '</p>';

		if ( ! empty( $content[ $name ] ) ) {
			return $content[ $name ];
		}
	}

	public function sidebar() {
		$content  = '<p><strong>' . __( 'For more information:', 'campi-moduli-italiani' ) . '</strong></p>';
		$content .= sprintf( '<p><a href="%s" target="_blank">', 'https://wordpress.org/plugins/campi-moduli-italiani/' ) . __( 'Plugin page', 'campi-moduli-italiani' ) . '</a></p>';
		$this->screen->set_help_sidebar( $content );
	}
}
