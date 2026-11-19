<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Accessibility {
	private static $instance;
	const OPTION = 'flint_base_accessibility_report';

	public static function instance() {
		if ( ! self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_flint_accessibility_scan', array( $this, 'ajax_scan' ) );
	}

	public function ajax_scan() {
		check_ajax_referer( 'flint_accessibility_scan', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );

		$urls = array( home_url( '/' ) );
		$q = get_posts( array(
			'post_type'      => get_post_types( array( 'public' => true ) ),
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		) );
		foreach ( $q as $p ) {
			$u = get_permalink( $p );
			if ( $u ) $urls[] = $u;
		}
		$urls = array_values( array_unique( $urls ) );
		$issues = array();
		$scanned = 0;

		foreach ( $urls as $url ) {
			$r = wp_remote_get( $url, array( 'timeout' => 12, 'redirection' => 3 ) );
			if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) continue;
			$html = wp_remote_retrieve_body( $r );
			if ( ! $html ) continue;
			$scanned++;

			libxml_use_internal_errors( true );
			$d = new DOMDocument();
			@$d->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
			$x = new DOMXPath( $d );

			$this->add_nodes( $issues, $url, '1.1.1', 'Images must have text alternatives', $x->query( '//img[not(@alt)]' ), 'Add an alt attribute (empty alt is valid for decorative images).', $d );
			$this->add_nodes( $issues, $url, '1.3.1', 'Form controls need labels', $x->query( '//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image") and not(@aria-label) and not(@aria-labelledby) and not(@id=//label/@for)] | //select[not(@aria-label) and not(@aria-labelledby) and not(@id=//label/@for)] | //textarea[not(@aria-label) and not(@aria-labelledby) and not(@id=//label/@for)]' ), 'Associate a visible label or accessible name with each control.', $d );
			$this->add_nodes( $issues, $url, '2.4.4', 'Links need an accessible name', $x->query( '//a[not(normalize-space(string(.))) and not(@aria-label) and not(@aria-labelledby) and not(.//img[@alt and normalize-space(@alt)])]' ), 'Give each link meaningful visible text or an accessible name.', $d );
			$this->add_nodes( $issues, $url, '3.1.1', 'Page language must be identified', $x->query( '/html[not(@lang) or not(normalize-space(@lang))]' ), 'Set the html lang attribute.', $d );

			if ( ! $x->query( '//head/title[normalize-space()]' )->length ) {
				$head = $x->query( '//head' )->item( 0 );
				$this->add_issue( $issues, $url, '2.4.2', 'Page must have a title', 'Add a descriptive title element.', $head, $d, '<title>…</title>' );
			}

			$seen_ids = array();
			foreach ( $x->query( '//*[@id]' ) as $node ) {
				$id = $node->getAttribute( 'id' );
				if ( ! $id ) continue;
				if ( isset( $seen_ids[ $id ] ) ) {
					$this->add_issue( $issues, $url, '4.1.1', 'IDs should be unique', 'Remove duplicate id attributes to avoid parsing and labeling conflicts.', $node, $d );
				} else {
					$seen_ids[ $id ] = true;
				}
			}

			$h1s = $x->query( '//h1' );
			if ( $h1s->length > 1 ) {
				for ( $n = 1; $n < $h1s->length; $n++ ) {
					$this->add_issue( $issues, $url, '1.3.1', 'Multiple H1 headings detected', 'Review heading structure and keep the document hierarchy meaningful.', $h1s->item( $n ), $d );
				}
			}
		}

		$report = array( 'timestamp' => current_time( 'mysql' ), 'user_id' => get_current_user_id(), 'scanned' => $scanned, 'issues' => $issues );
		update_option( self::OPTION, $report, false );
		wp_send_json_success( array( 'scanned' => $scanned, 'issues' => $issues, 'count' => count( $issues ), 'timestamp' => current_time( 'M j, Y g:i a' ) ) );
	}

	private function add_nodes( &$issues, $url, $wcag, $title, $nodes, $fix, $document ) {
		if ( ! $nodes ) return;
		foreach ( $nodes as $node ) $this->add_issue( $issues, $url, $wcag, $title, $fix, $node, $document );
	}

	private function add_issue( &$issues, $url, $wcag, $title, $fix, $node, $document, $fallback = '' ) {
		$snippet = $fallback;
		$line = 0;
		if ( $node instanceof DOMNode ) {
			$line = method_exists( $node, 'getLineNo' ) ? (int) $node->getLineNo() : 0;
			if ( $node instanceof DOMElement ) $snippet = $document->saveHTML( $node );
			elseif ( ! $snippet ) $snippet = trim( $node->textContent );
		}
		$snippet = preg_replace( '/\s+/', ' ', trim( (string) $snippet ) );
		if ( strlen( $snippet ) > 500 ) $snippet = substr( $snippet, 0, 497 ) . '...';
		$issues[] = array( 'url' => $url, 'wcag' => $wcag, 'title' => $title, 'count' => 1, 'fix' => $fix, 'element' => $snippet, 'line' => $line );
	}

	public function render() {
		$r = get_option( self::OPTION, array() ); ?>
		<p>This built-in scan checks common, machine-detectable WCAG issues across up to 100 published URLs. It is a development aid, not a substitute for keyboard, screen-reader, contrast, and manual accessibility testing.</p>
		<p><button type="button" class="button button-primary" data-a11y-scan>Run Accessibility Scan</button> <span data-a11y-status></span></p>
		<div data-a11y-results><?php $this->render_report( $r ); ?></div>
	<?php }

	public function render_report( $r ) {
		if ( empty( $r['timestamp'] ) ) return;
		echo '<p><strong>Last scan:</strong> ' . esc_html( wp_date( 'M j, Y g:i a', strtotime( $r['timestamp'] ) ) ) . ' · ' . absint( $r['scanned'] ) . ' URLs scanned · ' . count( (array) $r['issues'] ) . ' issues</p>';
		if ( empty( $r['issues'] ) ) {
			echo '<div class="notice notice-success inline"><p>No issues were found by the automated checks.</p></div>';
			return;
		}
		$wcags = array(); $titles = array(); $pages = array();
		foreach ( (array) $r['issues'] as $i ) { $wcags[$i['wcag']]=$i['wcag']; $titles[$i['title']]=$i['title']; $pages[$i['url']]=$i['url']; }
		ksort($wcags); ksort($titles); ksort($pages);
		echo '<div class="flint-a11y-report" data-a11y-report data-per-page="25">';
		echo '<div class="flint-a11y-filters" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin:16px 0;">';
		echo '<label><span style="display:block;font-weight:600;">Search</span><input type="search" data-a11y-filter="search" placeholder="Element, fix, URL…"></label>';
		echo '<label><span style="display:block;font-weight:600;">WCAG</span><select data-a11y-filter="wcag"><option value="">All criteria</option>'; foreach($wcags as $v) echo '<option value="'.esc_attr($v).'">'.esc_html($v).'</option>'; echo '</select></label>';
		echo '<label><span style="display:block;font-weight:600;">Issue</span><select data-a11y-filter="issue"><option value="">All issues</option>'; foreach($titles as $v) echo '<option value="'.esc_attr($v).'">'.esc_html($v).'</option>'; echo '</select></label>';
		echo '<label><span style="display:block;font-weight:600;">Page</span><select data-a11y-filter="page"><option value="">All pages</option>'; foreach($pages as $v) echo '<option value="'.esc_attr($v).'">'.esc_html($v).'</option>'; echo '</select></label>';
		echo '<label><span style="display:block;font-weight:600;">Per page</span><select data-a11y-per-page><option>10</option><option selected>25</option><option>50</option><option>100</option></select></label>';
		echo '<button type="button" class="button" data-a11y-clear>Clear filters</button><span data-a11y-count aria-live="polite"></span></div>';
		echo '<table class="widefat striped"><thead><tr><th>WCAG</th><th>Issue</th><th>Page</th><th>HTML / element / text</th><th>Source line</th><th>Suggested fix</th></tr></thead><tbody>';
		foreach ( $r['issues'] as $i ) {
			$element = isset( $i['element'] ) ? $i['element'] : 'Run a new scan to capture the source element.';
			$line = ! empty( $i['line'] ) ? (string) absint( $i['line'] ) : '—';
			$search = strtolower( implode(' ', array($i['wcag'],$i['title'],$i['url'],$element,$i['fix'],$line)) );
			echo '<tr data-a11y-row data-wcag="'.esc_attr($i['wcag']).'" data-issue="'.esc_attr($i['title']).'" data-page="'.esc_attr($i['url']).'" data-search="'.esc_attr($search).'"><td>' . esc_html( $i['wcag'] ) . '</td><td>' . esc_html( $i['title'] ) . '</td><td><a target="_blank" rel="noopener" href="' . esc_url( $i['url'] ) . '">' . esc_html( $i['url'] ) . '</a></td><td><code style="white-space:normal;word-break:break-word;">' . esc_html( $element ) . '</code></td><td>' . esc_html( $line ) . '</td><td>' . esc_html( $i['fix'] ) . '</td></tr>';
		}
		echo '</tbody></table><div class="tablenav"><div class="tablenav-pages" data-a11y-pagination></div></div></div>';
	}
}
