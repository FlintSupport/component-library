<?php
defined( 'ABSPATH' ) || exit;

final class Flint_Base_Security {
	private static $instance;
	const OPTION = 'flint_base_security_report';

	public static function instance() { if ( ! self::$instance ) self::$instance = new self(); return self::$instance; }
	private function __construct() { add_action( 'wp_ajax_flint_security_scan', array( $this, 'ajax_scan' ) ); }

	public function ajax_scan() {
		check_ajax_referer( 'flint_security_scan', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
		$issues = array(); $scanned = 0;
		$urls = array( home_url( '/' ) );
		foreach ( get_posts( array( 'post_type'=>get_post_types(array('public'=>true)), 'post_status'=>'publish', 'posts_per_page'=>100, 'orderby'=>'menu_order date', 'order'=>'ASC' ) ) as $p ) { $u=get_permalink($p); if($u)$urls[]=$u; }
		$urls=array_values(array_unique($urls));
		foreach($urls as $url){
			$r=wp_remote_get($url,array('timeout'=>12,'redirection'=>3)); if(is_wp_error($r)||200!==wp_remote_retrieve_response_code($r))continue;
			$html=wp_remote_retrieve_body($r); if(!$html)continue; $scanned++;
			$this->scan_page($issues,$url,$html,$r);
		}
		$this->scan_configuration($issues);
		$this->scan_code($issues, get_stylesheet_directory(), 'Active theme');
		$this->scan_code($issues, FLINT_BASE_DIR, 'FLINT Theme Builder');
		$this->scan_public_endpoints($issues);
		$report=array('timestamp'=>current_time('mysql'),'user_id'=>get_current_user_id(),'scanned'=>$scanned,'issues'=>$issues);
		update_option(self::OPTION,$report,false);
		wp_send_json_success(array('scanned'=>$scanned,'count'=>count($issues),'timestamp'=>current_time('M j, Y g:i a')));
	}

	private function scan_page(&$issues,$url,$html,$response){
		libxml_use_internal_errors(true); $d=new DOMDocument(); @$d->loadHTML('<?xml encoding="utf-8" ?>'.$html); $x=new DOMXPath($d);
		foreach($x->query('//input') as $node){
			$name=strtolower($node->getAttribute('name').' '.$node->getAttribute('id').' '.$node->getAttribute('placeholder'));
			$type=strtolower($node->getAttribute('type') ?: 'text');
			if(preg_match('/\b(pass(word)?|pwd|passwd)\b/',$name) && 'password'!==$type) $this->add($issues,'High','Password field exposed as plain text','A field appears to collect a password but is not type="password".',$url,$node->getLineNo(),$this->safe_node($d,$node),'Use <input type="password"> and an appropriate autocomplete value.');
			if('password'===$type){
				$form=$node; while($form && !($form instanceof DOMElement && 'form'===strtolower($form->nodeName)))$form=$form->parentNode;
				if($form instanceof DOMElement && 'get'===strtolower($form->getAttribute('method') ?: 'get')) $this->add($issues,'High','Password form uses GET','Passwords submitted with GET can be exposed in URLs, history, logs, and referrers.',$url,$node->getLineNo(),$this->safe_node($d,$node),'Submit password forms with method="post".');
				if('https'!==wp_parse_url($url,PHP_URL_SCHEME)) $this->add($issues,'High','Password field served without HTTPS','A password field is present on a non-HTTPS page.',$url,$node->getLineNo(),$this->safe_node($d,$node),'Serve authentication and password forms exclusively over HTTPS.');
				if(!$node->hasAttribute('autocomplete')) $this->add($issues,'Low','Password field missing autocomplete purpose','Password managers benefit from an explicit current-password or new-password autocomplete token.',$url,$node->getLineNo(),$this->safe_node($d,$node),'Set autocomplete="current-password" or "new-password" as appropriate.');
			}
		}
		// Flag credential-like literals in rendered source, but never store the discovered value.
		$lines=preg_split('/\R/',$html);
		foreach($lines as $n=>$line){ if(preg_match('/(?:password|passwd|pwd|secret|api[_-]?key|access[_-]?token)\s*[=:]\s*["\'][^"\']{4,}["\']/i',$line)) $this->add($issues,'High','Possible secret in rendered source','A credential-like value appears to be embedded in public page source.',$url,$n+1,$this->redact($line),'Remove secrets from browser-delivered HTML/JavaScript and rotate any exposed credential.'); }
		$h=wp_remote_retrieve_headers($response); $headers=array_change_key_case((array)$h,CASE_LOWER);
		if(empty($headers['content-security-policy'])) $this->add($issues,'Medium','Content-Security-Policy header not detected','No Content-Security-Policy response header was detected.',$url,0,'HTTP response headers','Consider a tested Content-Security-Policy appropriate to the site.');
		if(empty($headers['x-content-type-options'])) $this->add($issues,'Low','X-Content-Type-Options header not detected','The nosniff response header was not detected.',$url,0,'HTTP response headers','Send X-Content-Type-Options: nosniff.');
		if(empty($headers['referrer-policy'])) $this->add($issues,'Low','Referrer-Policy header not detected','No Referrer-Policy response header was detected.',$url,0,'HTTP response headers','Set an appropriate Referrer-Policy, commonly strict-origin-when-cross-origin.');
		if('https'===wp_parse_url($url,PHP_URL_SCHEME) && empty($headers['strict-transport-security'])) $this->add($issues,'Medium','HSTS header not detected','HTTPS is in use but Strict-Transport-Security was not detected.',$url,0,'HTTP response headers','Consider HSTS after confirming all required subdomains/resources support HTTPS.');
		if(empty($headers['x-frame-options']) && (empty($headers['content-security-policy']) || false===stripos((string)$headers['content-security-policy'],'frame-ancestors'))) $this->add($issues,'Medium','Clickjacking protection not detected','Neither X-Frame-Options nor a CSP frame-ancestors directive was detected.',$url,0,'HTTP response headers','Set X-Frame-Options or CSP frame-ancestors according to embedding requirements.');
	}

	private function scan_configuration(&$issues){
		if(defined('WP_DEBUG') && WP_DEBUG && (!defined('WP_DEBUG_DISPLAY') || WP_DEBUG_DISPLAY)) $this->add($issues,'Medium','Debug display may be enabled','WordPress debugging appears configured to display errors, which can disclose paths or implementation details.','WordPress configuration',0,'WP_DEBUG / WP_DEBUG_DISPLAY','Disable public debug display on production; log errors privately instead.');
		if(!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) $this->add($issues,'Medium','WordPress file editor is enabled','Theme/plugin source may be editable from wp-admin by sufficiently privileged accounts.','WordPress configuration',0,'DISALLOW_FILE_EDIT','Set DISALLOW_FILE_EDIT to true in wp-config.php on production.');
	}

	private function scan_code(&$issues,$root,$label){
		if(!$root||!is_dir($root))return; $root=realpath($root); $count=0;
		$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
		foreach($it as $file){ if($count>=1500)break; if(!$file->isFile()||!preg_match('/\.(php|js|json|env|ini)$/i',$file->getFilename()))continue; if($file->getSize()>2*1024*1024)continue; $count++;
			$lines=@file($file->getPathname()); if(!$lines)continue;
			foreach($lines as $n=>$line){
				if(preg_match('/(?:password|passwd|pwd|secret|api[_-]?key|access[_-]?token|client[_-]?secret)\s*[=:>]\s*["\'][^"\']{6,}["\']/i',$line) && !preg_match('/(?:example|placeholder|your[_ -]|dummy|test)/i',$line)){
					$rel=ltrim(str_replace($root,'',$file->getPathname()),DIRECTORY_SEPARATOR); $this->add($issues,'High','Possible hard-coded secret in source','A credential-like literal was found in local source. The value is intentionally redacted in this report.',$label.' / '.$rel,$n+1,$this->redact($line),'Move secrets to protected configuration/environment storage and rotate any credential that may have been exposed.');
				}
			}
		}
	}

	private function scan_public_endpoints(&$issues){
		$r=wp_remote_get(rest_url('wp/v2/users'),array('timeout'=>10,'redirection'=>2));
		if(!is_wp_error($r) && 200===wp_remote_retrieve_response_code($r)) $this->add($issues,'High','REST API user list is publicly accessible','The public WordPress users endpoint returned HTTP 200.','/wp-json/wp/v2/users',0,'REST API endpoint','Restrict unauthenticated access to WordPress user REST endpoints.');
		$uploads=wp_upload_dir(); if(!empty($uploads['baseurl'])){ $r=wp_remote_get(trailingslashit($uploads['baseurl']),array('timeout'=>10)); if(!is_wp_error($r)&&200===wp_remote_retrieve_response_code($r)&&preg_match('/<title>Index of|Index of \/wp-content\/uploads/i',wp_remote_retrieve_body($r))) $this->add($issues,'Medium','Directory listing appears enabled','The uploads directory appears to return a browsable file index.',$uploads['baseurl'],0,'Directory index','Disable directory indexes at the web-server level.'); }
	}

	private function safe_node($d,$node){ $s=$d->saveHTML($node); return $this->redact($s); }
	private function redact($text){ $text=preg_replace('/((?:password|passwd|pwd|secret|api[_-]?key|access[_-]?token|client[_-]?secret)\s*[=:>]\s*["\'])[^"\']+(["\'])/i','$1[REDACTED]$2',(string)$text); $text=preg_replace('/\s+/',' ',trim($text)); return strlen($text)>500?substr($text,0,497).'...':$text; }
	private function add(&$issues,$severity,$title,$detail,$location,$line,$evidence,$fix){ $issues[]=array('severity'=>$severity,'title'=>$title,'detail'=>$detail,'location'=>$location,'line'=>(int)$line,'evidence'=>$evidence,'fix'=>$fix); }

	public function render(){ $r=get_option(self::OPTION,array()); ?>
		<p>This scanner checks machine-detectable security concerns in published pages, response headers, WordPress configuration, public endpoints, and local theme/Theme Builder source. It is a development aid, not a penetration test or guarantee that a site is secure.</p>
		<p><strong>Secret safety:</strong> detected credential-like values are redacted before the report is stored or displayed.</p>
		<p><button type="button" class="button button-primary" data-security-scan>Run Security Scan</button> <span data-security-status aria-live="polite"></span></p>
		<?php $this->render_report($r); ?>
	<?php }
	private function render_report($r){
		if(empty($r['timestamp']))return; $issues=(array)($r['issues']??array());
		echo '<p><strong>Last scan:</strong> '.esc_html(wp_date('M j, Y g:i a',strtotime($r['timestamp']))).' · '.absint($r['scanned']).' URLs scanned · '.count($issues).' warnings</p>';
		if(!$issues){echo '<div class="notice notice-success inline"><p>No warnings were found by these automated checks.</p></div>';return;}
		$sev=array();$titles=array(); foreach($issues as $i){$sev[$i['severity']]=$i['severity'];$titles[$i['title']]=$i['title'];}
		echo '<div class="flint-a11y-report" data-security-report><div class="flint-a11y-filters" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin:16px 0;">';
		echo '<label><span style="display:block;font-weight:600;">Search</span><input type="search" data-security-filter="search" placeholder="Issue, file, URL…"></label><label><span style="display:block;font-weight:600;">Severity</span><select data-security-filter="severity"><option value="">All severities</option><option>High</option><option>Medium</option><option>Low</option></select></label><label><span style="display:block;font-weight:600;">Issue</span><select data-security-filter="issue"><option value="">All issues</option>'; foreach($titles as $v)echo '<option value="'.esc_attr($v).'">'.esc_html($v).'</option>'; echo '</select></label><label><span style="display:block;font-weight:600;">Per page</span><select data-security-per-page><option>10</option><option selected>25</option><option>50</option><option>100</option></select></label><button type="button" class="button" data-security-clear>Clear filters</button><span data-security-count aria-live="polite"></span></div>';
		echo '<table class="widefat striped"><thead><tr><th>Severity</th><th>Issue</th><th>Location</th><th>Evidence</th><th>Source line</th><th>Recommendation</th></tr></thead><tbody>';
		foreach($issues as $i){$line=!empty($i['line'])?(string)absint($i['line']):'—';$search=strtolower(implode(' ',array_values($i)));echo '<tr data-security-row data-severity="'.esc_attr($i['severity']).'" data-issue="'.esc_attr($i['title']).'" data-search="'.esc_attr($search).'"><td><strong>'.esc_html($i['severity']).'</strong></td><td><strong>'.esc_html($i['title']).'</strong><br><small>'.esc_html($i['detail']).'</small></td><td>'.esc_html($i['location']).'</td><td><code style="white-space:normal;word-break:break-word;">'.esc_html($i['evidence']).'</code></td><td>'.esc_html($line).'</td><td>'.esc_html($i['fix']).'</td></tr>';}
		echo '</tbody></table><div class="tablenav"><div class="tablenav-pages" data-security-pagination></div></div></div>';
	}
}
