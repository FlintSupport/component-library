<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Settings {
	private static $instance;
	const OPTION = 'flint_base_settings';

	public static function instance() {
		if ( ! self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	public static function defaults() {
		return array(
			'header_layout' => 'header-1',
			'footer_layout' => 'footer-1',
			'excerpt_length' => 15,
			'features' => array(
				'remove_emojis' => 1,
				'svg_uploads' => 1,
				'gravity_forms_button' => 1,
				'slick' => 1,
				'jquery_cdn' => 1,
				'image_alt_defaults' => 1,
				'wysiwyg_elements' => 1,
				'wysiwyg_tables' => 1,
				'hide_comments_menu' => 1,
				'remove_block_styles' => 1,
				'remove_global_styles' => 1,
				'remove_classic_theme_styles' => 1,
				'remove_dashicons_frontend' => 1,
				'remove_wp_embed' => 1,
				'remove_oembed_discovery' => 1,
				'remove_head_meta' => 1,
				'remove_rest_link' => 1,
				'remove_shortlink' => 1,
				'remove_jquery_migrate' => 0,
			),
			'enabled_blocks' => array(), // Legacy 2.2.x global list; retained for migration.
			'blocks_configured' => 0,
			'block_rules' => array(),
			'custom_post_types' => array(
				array( 'key'=>'team','plural'=>'Team Members','singular'=>'Team Member','slug'=>'team','icon'=>'dashicons-businessperson','public'=>1,'archive'=>0,'editor'=>0,'thumbnail'=>0 ),
			),
			'custom_taxonomies' => array(
				array( 'key'=>'team-category','plural'=>'Team Categories','singular'=>'Team Category','slug'=>'team-category','object_types'=>'team','hierarchical'=>1,'public'=>0 ),
				array( 'key'=>'location','plural'=>'Locations','singular'=>'Location','slug'=>'location','object_types'=>'team','hierarchical'=>1,'public'=>1 ),
			),
			'wysiwyg_elements' => array(
				array('label'=>'Button','html'=>'<a class="button primary" href="#">Button text</a>'),
				array('label'=>'Checklist','html'=>'<ul class="checklist"><li>Your list item here</li></ul>'),
				array('label'=>'Multi-Column List','html'=>'<ul style="columns: 2"><li>Your list item here</li></ul>'),
				array('label'=>'In-Content Anchor','html'=>'<hr class="anchor" id="anchor-id">'),
			),
			'editor_capabilities' => array( 'gravity_forms'=>1, 'view_plugins'=>1, 'update_plugins'=>1, 'manage_plugins'=>1, 'manage_users'=>1, 'appearance_customize'=>0, 'appearance_menus'=>1 ),
			'scripts' => array(),
			'editor_colors' => array(
				array( 'name' => 'Black', 'slug' => 'black', 'color' => '#000000' ),
				array( 'name' => 'White', 'slug' => 'white', 'color' => '#ffffff' ),
			),
		);
	}

	public static function activate() {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, self::defaults() );
		}
		flush_rewrite_rules();
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'update_option_' . self::OPTION, array( $this, 'settings_updated' ), 10, 2 );
	}

	public function get_settings() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		// One-time migration from 2.0.x base toggles into normal editable rows.
		if ( isset( $saved['base_post_types'] ) || isset( $saved['base_taxonomies'] ) ) {
			$pts = (array) ( $saved['custom_post_types'] ?? array() );
			$tax = (array) ( $saved['custom_taxonomies'] ?? array() );
			if ( ! empty( $saved['base_post_types']['team'] ) ) $pts[] = array( 'key'=>'team','plural'=>'Team Members','singular'=>'Team Member','slug'=>'team','icon'=>'dashicons-businessperson','public'=>1,'archive'=>0,'editor'=>0,'thumbnail'=>0 );
			if ( ! empty( $saved['base_taxonomies']['team-category'] ) ) $tax[] = array( 'key'=>'team-category','plural'=>'Team Categories','singular'=>'Team Category','slug'=>'team-category','object_types'=>'team','hierarchical'=>1,'public'=>0 );
			if ( ! empty( $saved['base_taxonomies']['location'] ) ) $tax[] = array( 'key'=>'location','plural'=>'Locations','singular'=>'Location','slug'=>'location','object_types'=>'team','hierarchical'=>1,'public'=>1 );
			$saved['custom_post_types'] = $this->sanitize_post_types( $pts ); $saved['custom_taxonomies'] = $this->sanitize_taxonomies( $tax );
			unset( $saved['base_post_types'], $saved['base_taxonomies'] ); update_option( self::OPTION, $saved );
		}
		$defaults = self::defaults();
		$settings = wp_parse_args( $saved, $defaults );
		// Merge nested toggle groups so new default-on features/capabilities are
		// enabled on existing Flint installs without overwriting saved choices.
		$settings['features'] = wp_parse_args( (array) ( $saved['features'] ?? array() ), $defaults['features'] );
		$settings['editor_capabilities'] = wp_parse_args( (array) ( $saved['editor_capabilities'] ?? array() ), $defaults['editor_capabilities'] );
		// Migrate the former single plugin update toggle into the new split permissions.
		if ( isset( $saved['editor_capabilities']['plugin_updates'] ) ) {
			$legacy = empty( $saved['editor_capabilities']['plugin_updates'] ) ? 0 : 1;
			foreach ( array( 'view_plugins', 'update_plugins', 'manage_plugins' ) as $key ) {
				if ( ! array_key_exists( $key, (array) $saved['editor_capabilities'] ) ) { $settings['editor_capabilities'][ $key ] = $legacy; }
			}
		}
		return $settings;
	}

	public function register_setting() {
		register_setting( 'flint_base', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	public function admin_menu() {
		add_menu_page( 'Theme Settings', 'Theme Settings', 'manage_options', 'flint-starting-setup', array( $this, 'render_page' ), 'dashicons-admin-generic', 2.1 );
	}

	public function admin_assets( $hook ) {
		if ( 'toplevel_page_flint-starting-setup' !== $hook ) { return; }
		wp_enqueue_style( 'flint-base-admin', FLINT_BASE_URL . 'assets/css/admin.css', array(), FLINT_BASE_VERSION );
		wp_enqueue_script( 'flint-base-admin', FLINT_BASE_URL . 'assets/js/admin.js', array(), FLINT_BASE_VERSION, true );
		wp_localize_script( 'flint-base-admin', 'flintBaseAdmin', array( 'a11yNonce'=>wp_create_nonce('flint_accessibility_scan'), 'securityNonce'=>wp_create_nonce('flint_security_scan') ) );
	}

	public function settings_updated() {
		flush_rewrite_rules();
	}

	public function sanitize( $input ) {
		$defaults = self::defaults();
		$out = $defaults;
		$input = is_array( $input ) ? $input : array();

		$out['header_layout'] = sanitize_key( $input['header_layout'] ?? $defaults['header_layout'] );
		$out['footer_layout'] = sanitize_key( $input['footer_layout'] ?? $defaults['footer_layout'] );
		$out['excerpt_length'] = max( 1, min( 200, absint( $input['excerpt_length'] ?? 15 ) ) );

		$out['features'] = array();
		$built_in_features = array( 'svg_uploads', 'gravity_forms_button', 'jquery_cdn', 'remove_emojis', 'remove_dashicons_frontend' );
		foreach ( array_keys( $defaults['features'] ) as $feature ) {
			$out['features'][ $feature ] = in_array( $feature, $built_in_features, true ) ? 1 : ( empty( $input['features'][ $feature ] ) ? 0 : 1 );
		}

		$out['blocks_configured'] = 1;
		$out['enabled_blocks'] = array(); // No longer written; per-post-type rules live below.
		$out['block_rules'] = array();
		foreach ( (array) ( $input['block_rules'] ?? array() ) as $post_type => $names ) {
			$post_type = sanitize_key( $post_type );
			if ( ! $post_type ) { continue; }
			$out['block_rules'][ $post_type ] = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) $names ) ) ) );
		}


		$out['custom_post_types'] = $this->sanitize_post_types( $input['custom_post_types'] ?? array() );
		$out['custom_taxonomies'] = $this->sanitize_taxonomies( $input['custom_taxonomies'] ?? array() );
		$out['editor_colors'] = $this->sanitize_colors( $input['editor_colors'] ?? array() );
		$out['wysiwyg_elements'] = $this->sanitize_wysiwyg_elements( $input['wysiwyg_elements'] ?? array() );
		$out['editor_capabilities'] = array();
		foreach ( array( 'gravity_forms','view_plugins','update_plugins','manage_plugins','manage_users','appearance_customize','appearance_menus' ) as $cap ) { $out['editor_capabilities'][$cap] = empty($input['editor_capabilities'][$cap]) ? 0 : 1; }
		$out['scripts'] = $this->sanitize_scripts( $input['scripts'] ?? array() );
		return $out;
	}

	private function sanitize_post_types( $rows ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$key = sanitize_key( $row['key'] ?? '' );
			if ( ! $key || in_array( $key, get_post_types( array( '_builtin' => true ) ), true ) || isset( $out[ $key ] ) ) { continue; }
			$out[ $key ] = array(
				'key' => $key,
				'plural' => sanitize_text_field( $row['plural'] ?? ucfirst( $key ) ),
				'singular' => sanitize_text_field( $row['singular'] ?? ucfirst( $key ) ),
				'slug' => sanitize_title( $row['slug'] ?? $key ),
				'icon' => sanitize_html_class( $row['icon'] ?? 'dashicons-admin-post' ),
				'public' => empty( $row['public'] ) ? 0 : 1,
				'archive' => empty( $row['archive'] ) ? 0 : 1,
				'editor' => empty( $row['editor'] ) ? 0 : 1,
				'thumbnail' => empty( $row['thumbnail'] ) ? 0 : 1,
			);
		}
		return array_values( $out );
	}

	private function sanitize_taxonomies( $rows ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$key = sanitize_key( $row['key'] ?? '' );
			if ( ! $key || in_array( $key, get_taxonomies( array( '_builtin' => true ) ), true ) || isset( $out[ $key ] ) ) { continue; }
			$objects = array_values( array_filter( array_map( 'sanitize_key', preg_split( '/[\s,]+/', $row['object_types'] ?? '' ) ) ) );
			if ( ! $objects ) { continue; }
			$out[ $key ] = array(
				'key' => $key,
				'plural' => sanitize_text_field( $row['plural'] ?? ucfirst( $key ) ),
				'singular' => sanitize_text_field( $row['singular'] ?? ucfirst( $key ) ),
				'slug' => sanitize_title( $row['slug'] ?? $key ),
				'object_types' => implode( ',', $objects ),
				'hierarchical' => empty( $row['hierarchical'] ) ? 0 : 1,
				'public' => empty( $row['public'] ) ? 0 : 1,
			);
		}
		return array_values( $out );
	}

	private function sanitize_wysiwyg_elements( $rows ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$label = sanitize_text_field( $row['label'] ?? '' );
			$html = wp_kses_post( $row['html'] ?? '' );
			if ( $label && $html ) { $out[] = array( 'label' => $label, 'html' => $html ); }
		}
		return $out;
	}

	private function sanitize_dashboard_roles( $rows ) {
		$out = array();
		$allowed = array( 'comments','plugins','plugin_editor','theme_editor','tools','appearance','users','settings','posts','media','pages' );
		foreach ( (array) $rows as $role => $items ) {
			$role = sanitize_key( $role );
			$out[$role] = array_values( array_intersect( $allowed, array_map( 'sanitize_key', (array) $items ) ) );
		}
		return $out;
	}

	private function sanitize_scripts( $rows ) {
		$out=array(); $allowed=array('ga4','gtm','maps','script_url');
		foreach((array)$rows as $row){ $type=sanitize_key($row['type']??''); $value=trim(sanitize_text_field($row['value']??'')); if(!in_array($type,$allowed,true)||!$value)continue; $out[]=array('type'=>$type,'value'=>$value); }
		return $out;
	}

	private function sanitize_colors( $rows ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$name = sanitize_text_field( $row['name'] ?? '' );
			$color = sanitize_hex_color( $row['color'] ?? '' );
			if ( ! $name || ! $color ) { continue; }
			$slug = sanitize_title( $row['slug'] ?? $name );
			$out[] = array( 'name' => $name, 'slug' => $slug ?: 'color-' . count( $out ), 'color' => $color );
		}
		return $out ?: self::defaults()['editor_colors'];
	}

	private function layout_registry() {
		return apply_filters( 'flint_base_layout_registry', array( 'headers' => array(), 'footers' => array() ) );
	}

	private function known_blocks() {
		$blocks = array();
		if ( class_exists( 'WP_Block_Type_Registry' ) ) {
			foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $obj ) {
				$blocks[ $name ] = $obj->title ?: $name;
			}
		}
		$blocks = apply_filters( 'flint_base_known_custom_blocks', $blocks );
		ksort( $blocks );
		return $blocks;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$s = $this->get_settings();
		$layouts = $this->layout_registry();
		$blocks = $this->known_blocks();
		$groups = array( 'Flint / Custom' => array(), 'Core' => array(), 'Plugin / Other' => array() );
		foreach ( $blocks as $name => $label ) {
			if ( str_starts_with( $name, 'acf/' ) ) { $groups['Flint / Custom'][ $name ] = $label; }
			elseif ( str_starts_with( $name, 'core/' ) ) { $groups['Core'][ $name ] = $label; }
			else { $groups['Plugin / Other'][ $name ] = $label; }
		}
		// Migrate the old global block selection visually until the settings are saved.
		$legacy_enabled = $s['blocks_configured'] ? (array) $s['enabled_blocks'] : array_keys( $groups['Flint / Custom'] );
		$block_rules = (array) ( $s['block_rules'] ?? array() );
		// Block rules are intentionally limited to Posts, Pages, and Flint-managed
		// custom post types whose Editor option is enabled in Content Types.
		$allowed_block_types = array( 'post', 'page' );
		foreach ( (array) $s['custom_post_types'] as $row ) { if ( ! empty( $row['editor'] ) && ! empty( $row['key'] ) ) $allowed_block_types[] = sanitize_key( $row['key'] ); }
		$post_types = array();
		foreach ( array_unique( $allowed_block_types ) as $pt ) { $obj=get_post_type_object($pt); if($obj && post_type_supports($pt,'editor')) $post_types[$pt]=$obj; }
		$registered_blocks = class_exists( 'WP_Block_Type_Registry' ) ? array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ) : array();
		?>
		<div class="wrap flint-setup">
			<h1>Theme Settings</h1>
			<p class="description">Configure the stable site foundation here. Markup and visual changes stay in the theme; behavior and reusable site setup stay in this plugin.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'flint_base' ); ?>
				<nav class="nav-tab-wrapper flint-tabs">
					<a href="#layouts" class="nav-tab nav-tab-active">Layouts</a><a href="#blocks" class="nav-tab">Blocks</a><a href="#content" class="nav-tab">Content Types</a><a href="#editor" class="nav-tab">Editor Settings</a><a href="#dashboard" class="nav-tab">Client Account Permissions</a><a href="#scripts" class="nav-tab">Scripts</a><a href="#accessibility" class="nav-tab">Accessibility</a><a href="#security" class="nav-tab">Security</a><a href="#features" class="nav-tab">Features</a><a href="#launch" class="nav-tab">Launch Checklist</a>
				</nav>

				<section id="layouts" class="flint-panel active"><h2>Header & Footer</h2><p>Choosing a layout automatically registers only the menu locations that layout uses.</p>
					<div class="flint-grid two">
						<label><strong>Header template</strong><select name="<?php echo esc_attr( self::OPTION ); ?>[header_layout]">
						<?php foreach ( (array) $layouts['headers'] as $key => $layout ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['header_layout'], $key ); ?>><?php echo esc_html( $layout['label'] ); ?></option><?php endforeach; ?>
						</select></label>
						<label><strong>Footer template</strong><select name="<?php echo esc_attr( self::OPTION ); ?>[footer_layout]">
						<?php foreach ( (array) $layouts['footers'] as $key => $layout ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['footer_layout'], $key ); ?>><?php echo esc_html( $layout['label'] ); ?></option><?php endforeach; ?>
						</select></label>
					</div>
					<label class="flint-field"><strong>Excerpt length</strong><input type="number" min="1" max="200" name="<?php echo esc_attr( self::OPTION ); ?>[excerpt_length]" value="<?php echo esc_attr( $s['excerpt_length'] ); ?>"> <span>words</span></label>
				</section>

				<section id="blocks" class="flint-panel"><h2>Gutenberg Blocks by Post Type</h2><p>Choose a content type, then select the blocks its editors can use. Pages, Posts, and every custom post type can have a different block library.</p>
					<div class="flint-post-type-tabs" role="tablist">
					<?php $pt_i = 0; foreach ( $post_types as $post_type => $post_type_obj ) : ?>
						<button type="button" class="button <?php echo 0 === $pt_i ? 'button-primary' : ''; ?>" data-block-post-type="<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $post_type_obj->labels->name ?: $post_type ); ?></button>
					<?php $pt_i++; endforeach; ?>
					</div>
					<?php $pt_i = 0; foreach ( $post_types as $post_type => $post_type_obj ) :
						$enabled = array_key_exists( $post_type, $block_rules ) ? (array) $block_rules[ $post_type ] : $legacy_enabled; ?>
					<div class="flint-post-type-blocks" data-block-post-type-panel="<?php echo esc_attr( $post_type ); ?>" <?php echo 0 === $pt_i ? '' : 'hidden'; ?>>
						<h3><?php echo esc_html( $post_type_obj->labels->name ?: $post_type ); ?></h3>
						<p class="description">Applies only when editing <strong><?php echo esc_html( strtolower( $post_type_obj->labels->name ?: $post_type ) ); ?></strong>.</p>
						<div class="flint-block-actions"><button type="button" class="button" data-block-action="all">Enable all</button> <button type="button" class="button" data-block-action="none">Disable all</button></div>
						<div class="flint-grid three">
						<?php foreach ( $groups as $group => $items ) : ?><div class="flint-card flint-block-group"><h3><?php echo esc_html( $group ); ?></h3><p class="flint-group-actions"><button type="button" class="button-link" data-group-block-action="all">Select all</button> / <button type="button" class="button-link" data-group-block-action="none">Deselect all</button></p><?php if ( ! $items ) : ?><p class="description">No blocks detected.</p><?php endif; ?><?php foreach ( $items as $name => $label ) : ?>
						<label class="flint-check"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[block_rules][<?php echo esc_attr( $post_type ); ?>][]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $enabled, true ) ); ?>> <span><?php echo esc_html( $label ); ?><code><?php echo esc_html( $name ); ?></code><?php $is_enabled = in_array( $name, $enabled, true ); $is_registered = in_array( $name, $registered_blocks, true ); ?><small class="flint-block-status"><?php echo esc_html( $is_enabled ? ( $is_registered ? 'Enabled + Registered' : 'Enabled + Not Registered' ) : 'Disabled' ); ?></small></span></label>
						<?php endforeach; ?></div><?php endforeach; ?>
						</div>
					</div>
					<?php $pt_i++; endforeach; ?>
				</section>

				<section id="content" class="flint-panel"><h2>Post Types & Taxonomies</h2>
					<p>The starter content types are ordinary editable rows. Change or remove them just like anything you add later.</p>
					<?php $this->render_post_type_table( $s['custom_post_types'] ); ?>
					<?php $this->render_taxonomy_table( $s['custom_taxonomies'] ); ?>
				</section>

				<section id="editor" class="flint-panel"><h2>Editor Settings</h2><h3>Color Swatches</h3><p>These colors are shared by Gutenberg and TinyMCE/ACF WYSIWYG editors.</p><?php $this->render_color_table( $s['editor_colors'] ); ?><hr><h3>Custom WYSIWYG Elements</h3><p>When the <strong>Custom WYSIWYG elements</strong> feature is enabled, these reusable snippets appear under an <strong>Add Elements</strong> toolbar button.</p><?php $this->render_wysiwyg_table( $s['wysiwyg_elements'] ); ?></section>

				<section id="dashboard" class="flint-panel"><h2>Client Account Permissions</h2><p>Choose which additional WordPress capabilities are available to the base Editor role used for client accounts.</p><div class="flint-grid two"><?php $caps=array('gravity_forms'=>array('Add Gravity Forms','Create/edit Gravity Forms and work with entries.'),'view_plugins'=>array('View plugins','Allows Editors to access the Plugins screen. WordPress uses the same core capability for viewing and activating/deactivating plugins.'),'update_plugins'=>array('Allow plugin updates','Allows Editors to run available plugin updates.'),'manage_plugins'=>array('Allow Editors to add/delete plugins','Allows Editors to install and delete plugins.'),'manage_users'=>array('Allow Editors to add and edit users','Allows Editors to list, create, edit, and promote users within WordPress capability safeguards.'),'appearance_customize'=>array('Appearance: Customize','Allows Editors to access Appearance → Customize.'),'appearance_menus'=>array('Appearance: Menus','Allows Editors to access Appearance → Menus.')); foreach($caps as $key=>$copy): ?><label class="flint-card flint-feature"><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[editor_capabilities][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($s['editor_capabilities'][$key])); ?>><span><strong><?php echo esc_html($copy[0]); ?></strong><small><?php echo esc_html($copy[1]); ?></small></span></label><?php endforeach; ?></div></section>

				<section id="scripts" class="flint-panel"><h2>Scripts & API Integrations</h2><p>Add common integrations without editing theme files. Choose the integration and enter only its ID, API key, or script URL.</p><?php $this->render_scripts_table($s['scripts']); ?></section>

				<section id="accessibility" class="flint-panel"><h2>Accessibility Report</h2><?php Flint_Base_Accessibility::instance()->render(); ?></section>

				<section id="security" class="flint-panel"><h2>Security Report</h2><?php Flint_Base_Security::instance()->render(); ?></section>

				<section id="features" class="flint-panel">
					<h2>Base Features</h2>
					<p>Feature controls are grouped by purpose so related site behavior and performance settings can be reviewed together.</p>
					<?php
					$feature_labels = array(
						'slick' => array( 'Enable Slick Slider', 'Loads the shared Slick 1.8.1 library from this plugin.' ),
						'image_alt_defaults' => array( 'Default image title/alt from filename', 'Normalizes image filenames into attachment title and alt text on upload.' ),
						'wysiwyg_elements' => array( 'Custom WYSIWYG elements', 'Adds the existing Flint Button, Checklist, multi-column list, and anchor controls to TinyMCE.' ),
						'wysiwyg_tables' => array( 'TinyMCE table support', 'Loads the existing table plugin for classic/ACF WYSIWYG editors.' ),
						'hide_comments_menu' => array( 'Hide Comments in dashboard menu', 'Removes the Comments item from the WordPress admin sidebar. Comments remain stored and can be restored by disabling this feature.' ),
						'remove_block_styles' => array( 'Remove Gutenberg block library CSS', 'Stops wp-block-library and wp-block-library-theme on the front end. Leave off if front-end content uses core Gutenberg blocks that depend on these styles.' ),
						'remove_global_styles' => array( 'Remove WordPress global styles', 'Removes global-styles and classic-theme-styles inline CSS generated by WordPress. Enable only when the FLINT theme supplies all required presentation.' ),
						'remove_classic_theme_styles' => array( 'Remove classic theme styles', 'Dequeues the separate classic-theme-styles handle when WordPress registers it.' ),
						'remove_wp_embed' => array( 'Remove WordPress embed script', 'Stops wp-embed.js from loading on the front end. Embedded third-party iframe content added directly is unaffected.' ),
						'remove_oembed_discovery' => array( 'Remove oEmbed discovery output', 'Removes oEmbed discovery links and the front-end oEmbed host JavaScript used for embedding this site elsewhere.' ),
						'remove_head_meta' => array( 'Remove generator, RSD and WLW head links', 'Removes legacy WordPress generator, Really Simple Discovery and Windows Live Writer metadata from wp_head.' ),
						'remove_rest_link' => array( 'Remove REST API discovery link', 'Removes the REST API discovery link from page head/output. This does not disable the REST API itself.' ),
						'remove_shortlink' => array( 'Remove WordPress shortlink output', 'Removes the shortlink tag from wp_head and HTTP headers.' ),
						'remove_jquery_migrate' => array( 'Remove jQuery Migrate on the front end', 'Removes jquery-migrate from WordPress jQuery dependencies for logged-out visitors. Leave off until legacy theme/plugin JavaScript has been tested.' ),
					);
					$feature_groups = array(
						'Content & Editor Tools' => array(
							'description' => 'Controls used while building and managing site content.',
							'features' => array( 'image_alt_defaults', 'wysiwyg_elements', 'wysiwyg_tables' ),
						),
						'Front-End Libraries' => array(
							'description' => 'Shared JavaScript libraries and compatibility options loaded by the public site.',
							'features' => array( 'slick', 'remove_jquery_migrate' ),
						),
						'CSS & Asset Performance' => array(
							'description' => 'Optional asset cleanup that can reduce front-end CSS and icon payloads. Test carefully when enabling the Gutenberg-related options.',
							'features' => array( 'remove_block_styles', 'remove_global_styles', 'remove_classic_theme_styles' ),
						),
						'WordPress Head & Embed Cleanup' => array(
							'description' => 'Removes optional WordPress discovery, embed, metadata, and shortlink output from public pages.',
							'features' => array( 'remove_wp_embed', 'remove_oembed_discovery', 'remove_head_meta', 'remove_rest_link', 'remove_shortlink' ),
						),
						'WordPress Admin Cleanup' => array(
							'description' => 'Controls that simplify the WordPress dashboard without deleting site data.',
							'features' => array( 'hide_comments_menu' ),
						),
					);
					foreach ( $feature_groups as $group_title => $group ) : ?>
						<div class="flint-feature-category">
							<div class="flint-feature-category-header">
								<h3><?php echo esc_html( $group_title ); ?></h3>
								<p><?php echo esc_html( $group['description'] ); ?></p>
							</div>
							<div class="flint-grid two flint-feature-category-items">
								<?php foreach ( $group['features'] as $key ) : $copy = $feature_labels[ $key ]; ?>
									<label class="flint-card flint-feature"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[features][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s['features'][ $key ] ) ); ?>><span><strong><?php echo esc_html( $copy[0] ); ?></strong><small><?php echo esc_html( $copy[1] ); ?></small></span></label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</section>

				<section id="launch" class="flint-panel"><h2>Launch Checklist</h2><?php Flint_Base_Launch::instance()->render(); ?></section>
				<?php submit_button( 'Save Starting Setup' ); ?>
			</form>
		</div>
		<?php
	}

	private function render_post_type_table( $rows ) { ?>
		<div class="flint-repeat"><div class="flint-repeat-head"><h3>Post Types</h3><button type="button" class="button" data-add-row="post-type">Add post type</button></div><div data-rows="post-type">
		<?php foreach ( (array) $rows as $i => $row ) { $this->post_type_row( $i, $row ); } ?>
		</div><template data-template="post-type"><?php $this->post_type_row( '__INDEX__', array() ); ?></template></div><?php
	}
	private function post_type_row( $i, $r ) { $n = self::OPTION . '[custom_post_types][' . $i . ']'; ?>
		<div class="flint-row"><div class="flint-row-fields"><label>Key<input name="<?php echo esc_attr($n); ?>[key]" value="<?php echo esc_attr($r['key'] ?? ''); ?>" placeholder="event"></label><label>Plural label<input name="<?php echo esc_attr($n); ?>[plural]" value="<?php echo esc_attr($r['plural'] ?? ''); ?>" placeholder="Events"></label><label>Singular label<input name="<?php echo esc_attr($n); ?>[singular]" value="<?php echo esc_attr($r['singular'] ?? ''); ?>" placeholder="Event"></label><label>Rewrite slug<input name="<?php echo esc_attr($n); ?>[slug]" value="<?php echo esc_attr($r['slug'] ?? ''); ?>" placeholder="events"></label><label>Dashicon<input name="<?php echo esc_attr($n); ?>[icon]" value="<?php echo esc_attr($r['icon'] ?? 'dashicons-admin-post'); ?>"></label></div><div class="flint-row-options"><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[public]" value="1" <?php checked(!isset($r['public']) || !empty($r['public'])); ?>> Public</label><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[archive]" value="1" <?php checked(!empty($r['archive'])); ?>> Archive</label><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[editor]" value="1" <?php checked(!empty($r['editor'])); ?>> Editor</label><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[thumbnail]" value="1" <?php checked(!empty($r['thumbnail'])); ?>> Featured image</label><button type="button" class="button-link-delete" data-remove-row>Remove</button></div></div><?php
	}
	private function render_taxonomy_table( $rows ) { ?>
		<div class="flint-repeat"><div class="flint-repeat-head"><h3>Taxonomies</h3><button type="button" class="button" data-add-row="taxonomy">Add taxonomy</button></div><div data-rows="taxonomy"><?php foreach ( (array) $rows as $i => $row ) { $this->taxonomy_row( $i, $row ); } ?></div><template data-template="taxonomy"><?php $this->taxonomy_row( '__INDEX__', array() ); ?></template></div><?php
	}
	private function taxonomy_row( $i, $r ) { $n = self::OPTION . '[custom_taxonomies][' . $i . ']'; ?>
		<div class="flint-row"><div class="flint-row-fields"><label>Key<input name="<?php echo esc_attr($n); ?>[key]" value="<?php echo esc_attr($r['key'] ?? ''); ?>" placeholder="region"></label><label>Plural label<input name="<?php echo esc_attr($n); ?>[plural]" value="<?php echo esc_attr($r['plural'] ?? ''); ?>" placeholder="Regions"></label><label>Singular label<input name="<?php echo esc_attr($n); ?>[singular]" value="<?php echo esc_attr($r['singular'] ?? ''); ?>" placeholder="Region"></label><label>Rewrite slug<input name="<?php echo esc_attr($n); ?>[slug]" value="<?php echo esc_attr($r['slug'] ?? ''); ?>" placeholder="region"></label><label>Object types<input name="<?php echo esc_attr($n); ?>[object_types]" value="<?php echo esc_attr($r['object_types'] ?? 'post'); ?>" placeholder="post,event"></label></div><div class="flint-row-options"><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[hierarchical]" value="1" <?php checked(!isset($r['hierarchical']) || !empty($r['hierarchical'])); ?>> Hierarchical</label><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[public]" value="1" <?php checked(!isset($r['public']) || !empty($r['public'])); ?>> Public</label><button type="button" class="button-link-delete" data-remove-row>Remove</button></div></div><?php
	}
	private function render_wysiwyg_table( $rows ) { ?>
		<div class="flint-repeat"><div class="flint-repeat-head"><h3>Toolbar Elements</h3><button type="button" class="button" data-add-row="wysiwyg">Add element</button></div><div data-rows="wysiwyg"><?php foreach ( (array) $rows as $i => $row ) { $this->wysiwyg_row( $i, $row ); } ?></div><template data-template="wysiwyg"><?php $this->wysiwyg_row( '__INDEX__', array() ); ?></template></div><?php
	}
	private function wysiwyg_row( $i, $r ) { $n = self::OPTION . '[wysiwyg_elements][' . $i . ']'; ?>
		<div class="flint-row"><div class="flint-row-fields"><label>Toolbar label<input name="<?php echo esc_attr($n); ?>[label]" value="<?php echo esc_attr($r['label'] ?? ''); ?>" placeholder="Callout"></label><label class="flint-wide">HTML inserted<textarea rows="4" name="<?php echo esc_attr($n); ?>[html]" placeholder="&lt;div class=&quot;callout&quot;&gt;Callout text&lt;/div&gt;"><?php echo esc_textarea($r['html'] ?? ''); ?></textarea></label></div><div class="flint-row-options"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div></div><?php
	}
	private function render_scripts_table( $rows ) { ?>
		<div class="flint-repeat"><div class="flint-repeat-head"><h3>Configured integrations</h3><button type="button" class="button" data-add-row="script">Add integration</button></div><div data-rows="script"><?php foreach((array)$rows as $i=>$r)$this->script_row($i,$r); ?></div><template data-template="script"><?php $this->script_row('__INDEX__',array()); ?></template></div><?php
	}
	private function script_row($i,$r){ $type=$r['type']??'ga4'; ?>
		<div class="flint-row"><select name="<?php echo esc_attr(self::OPTION); ?>[scripts][<?php echo esc_attr($i); ?>][type]"><option value="ga4" <?php selected($type,'ga4'); ?>>Google Analytics 4</option><option value="gtm" <?php selected($type,'gtm'); ?>>Google Tag Manager</option><option value="maps" <?php selected($type,'maps'); ?>>Google Maps API Key</option><option value="script_url" <?php selected($type,'script_url'); ?>>External Script URL</option></select><input type="text" class="regular-text" placeholder="G-XXXX, GTM-XXXX, API key, or URL" name="<?php echo esc_attr(self::OPTION); ?>[scripts][<?php echo esc_attr($i); ?>][value]" value="<?php echo esc_attr($r['value']??''); ?>"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div><?php
	}

	private function render_dashboard_roles( $settings ) {
		$roles = wp_roles()->roles;
		$areas = array( 'comments'=>'Comments','plugins'=>'Plugins','plugin_editor'=>'Plugin File Editor','theme_editor'=>'Theme File Editor','tools'=>'Tools','appearance'=>'Appearance','users'=>'Users','settings'=>'Settings','posts'=>'Posts','media'=>'Media','pages'=>'Pages' );
		echo '<div class="flint-grid two">';
		foreach ( $roles as $role => $data ) { echo '<div class="flint-card"><h3>'.esc_html($data['name']).'</h3>'; foreach($areas as $key=>$label){ $n=self::OPTION.'[dashboard_roles]['.$role.'][]'; echo '<label class="flint-check"><input type="checkbox" name="'.esc_attr($n).'" value="'.esc_attr($key).'" '.checked(in_array($key,(array)($settings[$role]??array()),true),true,false).'> Hide '.esc_html($label).'</label>'; } echo '</div>'; }
		echo '</div>';
	}

	private function render_color_table( $rows ) { ?>
		<div class="flint-repeat"><div class="flint-repeat-head"><h3>Palette</h3><button type="button" class="button" data-add-row="color">Add color</button></div><div data-rows="color"><?php foreach ( (array) $rows as $i => $row ) { $this->color_row( $i, $row ); } ?></div><template data-template="color"><?php $this->color_row( '__INDEX__', array() ); ?></template></div><?php
	}
	private function color_row( $i, $r ) { $n = self::OPTION . '[editor_colors][' . $i . ']'; ?>
		<div class="flint-row color-row"><div class="flint-row-fields"><label>Name<input name="<?php echo esc_attr($n); ?>[name]" value="<?php echo esc_attr($r['name'] ?? ''); ?>" placeholder="Brand Blue"></label><label>Slug<input name="<?php echo esc_attr($n); ?>[slug]" value="<?php echo esc_attr($r['slug'] ?? ''); ?>" placeholder="brand-blue"></label><label>Hex<input class="flint-color-input" name="<?php echo esc_attr($n); ?>[color]" value="<?php echo esc_attr($r['color'] ?? '#000000'); ?>" placeholder="#000000"></label></div><div class="flint-row-options"><span class="flint-swatch" style="background:<?php echo esc_attr($r['color'] ?? '#000000'); ?>"></span><button type="button" class="button-link-delete" data-remove-row>Remove</button></div></div><?php
	}
}
