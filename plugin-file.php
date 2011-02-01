<?php
/*
Plugin Name: WordPress-to-Candidate for Salesforce CRM
Plugin URI: 
Description: Easily embed a form to pages, and capture candidate resume into Salesforce CRM!
Author: Pletnev Rusalex pletnev.rusalex@gmail.com
Version: 1.0.1
Author URI: pletnev.rusalex@gmail.com
 */

if ( ! class_exists( 'SF_Candidate_Admin' ) ) {

	require_once('ov_plugin_tools.php');
	
	class SF_Candidate_Admin extends OV_Plugin_Admin {

		var $hook 		= 'salesforce-wordpress-to-candidate';
		var $filename	= 'salesforce/plugin-file.php';
		var $longname	= 'WordPress-to-Candidate for Salesforce CRM Configuration';
		var $shortname	= 'Salesforce Candidate';
		var $optionname = 'sf_candidate';
		var $homepage	= 'pletnev.rusalex@gmail.com';
		var $ozhicon	= 'salesforce-16x16.png';
		
		function SF_Candidate_Admin() {
			add_action( 'admin_menu', array(&$this, 'register_settings_page') );
			add_filter( 'plugin_action_links', array(&$this, 'add_action_link'), 10, 2 );
			add_filter( 'ozh_adminmenu_icon', array(&$this, 'add_ozh_adminmenu_icon' ) );				
			
			add_action('admin_print_scripts', array(&$this,'config_page_scripts'));
			add_action('admin_print_styles', array(&$this,'config_page_styles'));	
			add_action('admin_footer', array(&$this,'warning'));
		}

		function warning() {
			$options  = get_option($this->optionname);
			if (( !isset($options['salesforce_username']) || empty($options['salesforce_username']) ) || ( !isset($options['salesforce_pass']) || empty($options['salesforce_pass']) ) || ( !isset($options['salesforce_token']) || empty($options['salesforce_token']) ))
				echo "<div id='message' class='error'><p><strong>Your WordPress-to-Candidate  settings are not complete.</strong> You must <a href='".$this->plugin_options_url()."'>enter your Salesforce.com Username,Password and Token</a> for it to work.</p></div>";
			
		}

		function config_page() {
			if ( isset($_POST['submit']) ) {
				$options  = get_option($this->optionname);
				if (!current_user_can('manage_options')) die(__('You cannot edit the WordPress-to-Candidate options.', 'salesforce'));
				//check_admin_referer('salesforce-udpatesettings');
				
				foreach (array('usecss') as $option_name) {
					if (isset($_POST[$option_name])) {
						$options[$option_name] = true;
					} else {
						$options[$option_name] = false;
					}
				}

				$newinputs = array();
				foreach ($options['inputs'] as $id => $input) {
					foreach (array('show','required') as $option_name) {
						if (isset($_POST[$id.'_'.$option_name])) {
							$newinputs[$id][$option_name] = true;
							unset($_POST[$id.'_'.$option_name]);
						} else {
							$newinputs[$id][$option_name] = false;
						}
					}	
					foreach (array('type','label','pos') as $option_name) {
						if (isset($_POST[$id.'_'.$option_name])) {
							$newinputs[$id][$option_name] = $_POST[$id.'_'.$option_name];
							unset($_POST[$id.'_'.$option_name]);
						}
					}	
				}
				
				w2l_sksort($newinputs,'pos',true);
				$options['inputs'] = $newinputs;
								
				foreach (array('successmsg','errormsg','sferrormsg','submitbutton','salesforce_username','salesforce_pass','salesforce_token') as $option_name) {
					if (isset($_POST[$option_name])) {
						$options[$option_name] = $_POST[$option_name];
					}
				}

				update_option($this->optionname, $options);
			}
			$options  = get_option($this->optionname);

			if (!is_array($options['inputs']))
				$options = sf_candidate_default_settings();
			
			?>
			<div class="wrap">
				<a href="http://salesforce.com/"><div id="yoast-icon" style="background: url(<?php echo plugins_url('',__FILE__); ?>/salesforce-50x50.png) no-repeat;" class="icon32"><br /></div></a>
				<h2 style="line-height: 50px;"><?php echo $this->longname; ?></h2>
				<div class="postbox-container" style="width:70%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
							<?php if (!isset($_GET['tab']) || $_GET['tab'] == 'home') { ?>
							<form action="" method="post" id="salesforce-conf">
								<?php if (function_exists('wp_nonce_field')) { wp_nonce_field('salesforce-udpatesettings'); } ?>
								<input type="hidden" value="<?php echo $options['version']; ?>" name="version"/>
								<?php 
									$content = $this->textinput('successmsg',__('Success message after sending message', 'salesforce') );
									$content .= $this->textinput('errormsg',__('Error message when not all form fields are filled', 'salesforce') );
									$content .= $this->textinput('sferrormsg',__('Error message when Salesforce.com connection fails', 'salesforce') );
									$this->postbox('basicsettings',__('Basic Settings', 'salesforce'),$content); 
									
									$content = $this->textinput('salesforce_username',__('Your Salesforce.com Username','salesforce'));
									$content .= $this->textinput('salesforce_pass',__('Your Salesforce.com Password','salesforce'));
									$content .= $this->textinput('salesforce_token',__('Your Salesforce.com Token','salesforce'));
									$this->postbox('sfsettings',__('Salesforce.com Settings', 'salesforce'),$content); 

									$content = $this->textinput('submitbutton',__('Submit button text', 'salesforce') );
									$content .= $this->textinput('requiredfieldstext',__('Required fields text', 'salesforce') );
									$content .= $this->checkbox('usecss',__('Use Form CSS?', 'salesforce') );
									$content .= '<br/><small><a href="'.$this->plugin_options_url().'&amp;tab=css">'.__('Read how to copy the CSS to your own CSS file').'</a></small>';
									$this->postbox('formsettings',__('Form Settings', 'salesforce'),$content); 
																		
									$content = '<style type="text/css">th{text-align:left;}</style><table>';
									$content .= '<tr>'
									.'<th width="15%">ID</th>'
									.'<th width="10%">Show</th>'
									.'<th width="10%">Required</th>'
									.'<th width="10%">Type</th>'
									.'<th width="40%">Label</th>'
									.'<th width="10%">Position</th>'
									.'</tr>';
									$i = 1;
									foreach ($options['inputs'] as $id => $input) {
										if (empty($input['pos']))
											$input['pos'] = $i;
										$content .= '<tr>';
										$content .= '<th>'.$id.'</th>';
										$content .= '<td><input type="checkbox" name="'.$id.'_show" '.checked($input['show'],true,false).'/></td>';
										$content .= '<td><input type="checkbox" name="'.$id.'_required" '.checked($input['required'],true,false).'/></td>';
										$content .= '<td><select name="'.$id.'_type">';
										$content .= '<option '.selected($input['type'],'text',false).'>text</option>';
										$content .= '<option '.selected($input['type'],'textarea',false).'>textarea</option>';
										$content .= '</select></td>';
										$content .= '<td><input size="40" name="'.$id.'_label" type="text" value="'.$input['label'].'"/></td>';
										$content .= '<td><input size="2" name="'.$id.'_pos" type="text" value="'.$input['pos'].'"/></td>';
										$content .= '</tr>';
										$i++;
									}
									$content .= '</table>';
									$this->postbox('sffields',__('Form Fields', 'salesforce'),$content); 
								?>
								<div class="submit"><input type="submit" class="button-primary" name="submit" value="<?php _e("Save WordPress-to-Lead Settings", 'salesforce'); ?>" /></div>
							</form>
							<?php } else if ($_GET['tab'] == 'css') { ?>
							<p><a href="<?php echo $this->plugin_options_url(); ?>">&laquo; Back to config page.</a></p>
							<p>If you don't want the inline styling this plugins uses, but add the CSS for the form to your own theme's CSS, you can start by just copying the proper CSS below into your CSS file. Just copy the correct text, and then you can usually find &amp; edit your CSS file <a href="<?php echo admin_url('theme-editor.php'); ?>?file=<?php echo str_replace(WP_CONTENT_DIR,'',get_stylesheet_directory()); ?>/style.css&amp;theme=<?php echo urlencode(get_current_theme()); ?>&amp;dir=style">here</a>.</p>
							<div style="width:260px;margin:0 10px 0 0;float:left;">
								<div id="normalcss" class="postbox">
									<div class="handlediv" title="Click to toggle"><br /></div>
									<h3 class="hndle"><span>CSS for the normal form</span></h3>
									<div class="inside">
<pre>form.w2llead {
  text-align: left;
  clear: both;
}
.w2llabel, .w2linput {
  display: block;
  width: 120px;
  float: left;
}
.w2llabel.error {
  color: #f00;
}
.w2llabel {
  clear: left;
  margin: 4px 0;
}
.w2linput.text {
  width: 200px;
  height: 18px;
  margin: 4px 0;
}
.w2linput.textarea {
  clear: both;
  width: 320px;
  height: 75px;
  margin: 10px 0;
}
.w2linput.submit {
  float: none;
  margin: 10px 0 0 0;
  clear: both;
  width: 150px;
}
#salesforce {
  margin: 3px 0 0 0;
  color: #aaa;
}
#salesforce a {
  color: #999;
}</pre>
</div>
</div></div>
<div style="width:260px;float:left;">
	<div id="widgetcss" class="postbox">
		<div class="handlediv" title="Click to toggle"><br /></div>
		<h3 class="hndle"><span>CSS for the sidebar widget form</span></h3>
		<div class="inside">
<pre>.sidebar form.w2llead {
  clear: none;
  text-align: left;
}
.sidebar .w2linput, 
.sidebar .w2llabel {
  float: none;
  display: inline;
}
.sidebar .w2llabel.error {
  color: #f00;
}
.sidebar .w2llabel {
  margin: 4px 0;
}
.sidebar .w2linput.text {
  width: 160px;
  height: 18px;
  margin: 4px 0;
}
.sidebar .w2linput.textarea {
  width: 160px;
  height: 50px;
  margin: 10px 0;
}
.sidebar .w2linput.submit {
  margin: 10px 0 0 0;
}
#salesforce {
  margin: 3px 0 0 0;
  color: #aaa;
}
#salesforce a {
  color: #999;
}</pre>
</div></div></div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="postbox-container" style="width:20%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
							<?php
								$this->postbox('usesalesforce',__('How to Use This Plugin','salesforce'),__('<p>To use this form, copy the following shortcode into a post or page:</p><pre style="padding:5px 10px;margin:10px 0;background-color:lightyellow;">[sf_candidate]</pre>','salesforce'));
								$this->plugin_like(false);
								$this->plugin_support();
								// $this->news(); 
							?>
						</div>
						<br/><br/><br/>
					</div>
				</div>
			</div>
			<?php
		}

	}
	$sf_candidate = new SF_Candidate_Admin();
}

function sf_candidate_default_settings() {
	$options = array();
	$options['successmsg'] 			= 'Success!';
	$options['errormsg'] 			= 'There was an error, please fill all required fields.';
	$options['requiredfieldstext'] 	= 'These fields are required.';
	$options['salesforce_username'] 	= '';
	$options['salesforce_pass'] 	= '';
	$options['salesforce_token'] 	= '';
	//$options['sferrormsg'] 			= 'Failed to connect to Salesforce.com.';
	//$options['source'] 				= 'Lead form on '.get_bloginfo('name');
	$options['submitbutton']	 	= 'Submit';

	$options['usecss']				= true;

	$options['inputs'] = array(
		'your_name' 	=> array('type' => 'text', 'label' => 'Your name', 'show' => true, 'required' => true),
		'address' 	=> array('type' => 'text', 'label' => 'Your Address', 'show' => true, 'required' => true),
		'email' 		=> array('type' => 'text', 'label' => 'Email', 'show' => true, 'required' => true),
		'a_email' 		=> array('type' => 'text', 'label' => 'Email', 'show' => true, 'required' => false),
		'preferred_phone' 		=> array('type' => 'text', 'label' => 'Preferred Phone', 'show' => true, 'required' => true),
		'home_phone' 		=> array('type' => 'text', 'label' => 'Home Phone', 'show' => true, 'required' => true),
		'work_phone' 		=> array('type' => 'text', 'label' => 'Work Phone', 'show' => true, 'required' => false),
		'cell_phone' 		=> array('type' => 'text', 'label' => 'Cell Phone', 'show' => true, 'required' => false),
		'statement' 	=> array('type' => 'textarea', 'label' => 'Candidate Statement', 'show' => true, 'required' => false),
	);
	update_option('sf_candidate', $options);
	return $options;
}

function sf_candidate_form($options, $is_sidebar = false, $content = '') {
	if (!empty($content))
		$content = wpautop('<strong>'.$content.'</strong>');
	if ($options['usecss'] && !$is_sidebar) {
		$content .= '<style type="text/css">
		form.w2llead{text-align:left;clear:both;}
		.w2llabel, .w2linput {display:block;float:left;}
		.w2llabel.error {color:#f00;}
		.w2llabel {clear:left;margin:4px 0;width:50%;}
		.w2linput.text{width:50%;height:18px;margin:4px 0;}
		.w2linput.textarea {clear:both;width:100%;height:75px;margin:10px 0;}
		.w2linput.submit {float:none;margin:10px 0 0 0;clear:both;}
		#salesforce{margin:3px 0 0 0;color:#aaa;}
		#salesforce a{color:#999;}
		</style>';
	} else if ($is_sidebar && $options['usecss']) {
		$content .= '<style type="text/css">
		.sidebar form.w2llead{clear:none;text-align:left;}
		.sidebar .w2linput, #sidebar .w2llabel{float:none; display:inline;}
		.sidebar .w2llabel.error {color:#f00;}
		.sidebar .w2llabel {margin:4px 0;float:none;display:inline;}
		.sidebar .w2linput.text{width:95%;height:18px;margin:4px 0;}
		.sidebar .w2linput.textarea {width:95%;height:50px;margin:10px 0;}
		.sidebar .w2linput.submit {margin:10px 0 0 0;}
		#salesforce{margin:3px 0 0 0;color:#aaa;}
		#salesforce a{color:#999;}
		</style>';
	}
	$sidebar = '';
	if ($is_sidebar)
		$sidebar = ' sidebar';
	$content .= "\n".'<form class="w2llead'.$sidebar.'" method="post">'."\n";
	$content .= "<input type=hidden name=job_c value=".$_GET['job_c'].">";
	foreach ($options['inputs'] as $id => $input) {
		if (!$input['show'])
			continue;
		$val 	= '';
		if (isset($_POST[$id]))
			$val	= esc_attr(strip_tags(stripslashes($_POST[$id])));

		$error 	= ' ';
		if ($input['error']) 
			$error 	= ' error ';
			
		$content .= "\t".'<label class="w2llabel'.$error.$input['type'].'" for="sf_'.$id.'">'.esc_html(stripslashes($input['label'])).':';
		if ($input['required'])
			$content .= ' *';
		$content .= '</label>'."\n";
		if ($input['type'] == 'text') {			
			$content .= "\t".'<input value="'.$val.'" id="sf_'.$id.'" class="w2linput text" name="'.$id.'" type="text"/><br/>'."\n\n";
		} else if ($input['type'] == 'textarea') {
			$content .= "\t".'<br/>'."\n\t".'<textarea id="sf_'.$id.'" class="w2linput textarea" name="'.$id.'">'.$val.'</textarea><br/>'."\n\n";
		} 
	}

	if(1==1) {
			$content .= <<<EOD
<label class="w2llabel text">Saw our Ad:</label>
<br><select name="referer" class="w2linput text">
	<option value="">Select...</option>
	<option value="6FigureJobs">6FigureJobs</option>
	<option value="America's Job Bank">America's Job Bank</option>
	<option value="Career Builder">Career Builder</option>
	<option value="Career Journal (WSJ)">Career Journal (WSJ)</option>
	<option value="Craig's List">Craig's List</option>
	<option value="eFinancial Careers">eFinancial Careers</option>
	<option value="ExecutivesOnly">ExecutivesOnly</option>
	<option value="Jobdango">Jobdango</option>
	<option value="Jobmag">Jobmag</option>
	<option value="Linkedin">Linkedin</option>
	<option value="Monster">Monster</option>
	<option value="NIRI">NIRI</option>
	<option value="OSU">OSU</option>
	<option value="Other">Other</option>
	<option value="Our website">Our website</option>
	<option value="PSU">PSU</option>
	<option value="Refered">Refered</option>
	<option value="Right Express">Right Express</option>
	<option value="RiteSite">RiteSite</option>
	<option value="Seeking Alpha">Seeking Alpha</option>
	<option value="The Appraisal Institute">The Appraisal Institute</option>
	<option value="The Ladders">The Ladders</option>
	<option value="The Oregonian">The Oregonian</option>
	<option value="Yahoo! Hotjobs">Yahoo! Hotjobs</option>
</select>
EOD;
	}
	$submit = stripslashes($options['submitbutton']);
	if (empty($submit))
		$submit = "Submit";
	$content .= "\t".'<input type="submit" name="w2lsubmit" class="w2linput submit" value="'.esc_attr($submit).'"/>'."\n";
	$content .= '</form>'."\n";

	$reqtext = stripslashes($options['requiredfieldstext']);
	if (!empty($reqtext))
		$content .= '<p id="requiredfieldsmsg"><sup>*</sup>'.esc_html($reqtext).'</p>';
	//$content .= '<div id="salesforce"><small>Powered by <a href="http://www.salesforce.com/">Salesforce CRM</a></small></div>';
	return $content;
}

function submit_sf_candidate_form($post, $options) {
	global $wp_version;
	if (!isset($options['org_id']) || empty($options['org_id']))
		return false;

	$post['oid'] 			= $options['org_id'];
	$post['lead_source']	= $options['source'];
	$post['debug']			= 0;

	// Set SSL verify to false because of server issues.
	$args = array( 	
		'body' 		=> $post,
		'headers' 	=> array(
			'user-agent' => 'WordPress-to-Candidate for Salesforce plugin - WordPress/'.$wp_version.'; '.get_bloginfo('url'),
		),
		'sslverify'	=> false,  
	);
	
	$result = wp_remote_post('https://www.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8', $args);

	if ($result['headers']['is-processed'] == "true")
		return true;
	else 
		return false;
}

function sf_candidate_preview($post,$options) {
	if ($options['usecss'] && !$is_sidebar) {
		$content .= '<style type="text/css">
		form.w2llead{text-align:left;clear:both;}
		.w2llabel, .w2linput {display:block;float:left;}
		.w2llabel.error {color:#f00;}
		.w2llabel {clear:left;margin:4px 0;width:50%;}
		.w2linput.text{width:50%;height:18px;margin:4px 0;}
		.w2linput.textarea {clear:both;width:100%;height:75px;margin:10px 0;}
		.w2linput.submit {float:none;margin:10px 0 0 0;clear:both;}
		#salesforce{margin:3px 0 0 0;color:#aaa;}
		#salesforce a{color:#999;}
		</style>';
	} else if ($is_sidebar && $options['usecss']) {
		$content .= '<style type="text/css">
		.sidebar form.w2llead{clear:none;text-align:left;}
		.sidebar .w2linput, #sidebar .w2llabel{float:none; display:inline;}
		.sidebar .w2llabel.error {color:#f00;}
		.sidebar .w2llabel {margin:4px 0;float:none;display:inline;}
		.sidebar .w2linput.text{width:95%;height:18px;margin:4px 0;}
		.sidebar .w2linput.textarea {width:95%;height:50px;margin:10px 0;}
		.sidebar .w2linput.submit {margin:10px 0 0 0;}
		#salesforce{margin:3px 0 0 0;color:#aaa;}
		#salesforce a{color:#999;}
		</style>';
	}

	$content .= "\n".'<form class="w2llead'.$sidebar.'" method="post"  enctype="multipart/form-data">'."\n";
	foreach ($post as $id => $input) {
			if(!$options['inputs'][$id]['show']) continue;
			$content .= "\t".'<label class="w2llabel'.'" for="sf_'.$id.'">'.esc_html(stripslashes($options['inputs'][$id]['label'])).':';
			$content .= '</label>'."\n";
			$content .= "\t".'<input value="'.$input.'" id="sf_'.$id.'" class="w2linput text" name="'.$id.'" type="hidden"/><p>'.$input."</p>";
	}
			$content .= "\t".'<label class="w2llabel'.'" for="sf_'.$post['referer'].'">Saw our Ad:';
			$content .= '</label>'."\n";
			$content .= "\t".'<input value="'.$post['referer'].'" id="sf_referer" class="w2linput text" name="referer" type="hidden"/><p>'.$post['referer']."</p>";
	$content .= '<input type=file name="form_attachment_2" class="form-required"><br><p>(MS Word .doc, Adobe PDF .pdf or Rich Text format .rtf; maximum 150Kb)</p>';
	$content .= '<input type=file name="form_attachment_1" class="form-required"><br><p>(MS Word .doc, Adobe PDF .pdf or Rich Text format .rtf; maximum 150Kb)</p>';
	$content .= '<input type=hidden name=job_c value='.$_GET['job_c'].'>';
	$submit = stripslashes($options['submitbutton']);
	if (empty($submit))
		$submit = "Submit";
	$content .= "\t".'<input type="submit" name="w2lsubmitpreview" class="w2linput submit" value="'.esc_attr($submit).'"/>'."\n";
	$content .= '</form>'."\n";
	return $content;
}

function sf_candidate_form_shortcode($is_sidebar = false) {
	$options = get_option("sf_candidate");
	if (!is_array($options))
			sf_candidate_default_settings();

	if (isset($_POST['w2lsubmit'])) {
		$error = false;
		$post = array();
		foreach ($options['inputs'] as $id => $input) {
			if ($input['required'] && empty($_POST[$id])) {
				$options['inputs'][$id]['error'] = true;
				$error = true;
			} else if ($id == 'email' && $input['required'] && !is_email($_POST[$id]) ) {
				$error = true;
				$emailerror = true;
			} else {
				$post[$id] = trim(strip_tags(stripslashes($_POST[$id])));
			}
		}
		$post['referer'] = trim(strip_tags(stripslashes($_POST['referer'])));
		if (!$error) {
			//$result = submit_sf_candidate_form($post, $options);
				$result = sf_candidate_preview($post, $options);
			if (!$result)
				$content = '<strong>'.esc_html(stripslashes($options['sferrormsg'])).'</strong>';			
			else
				$content = '<strong>'.esc_html(stripslashes($options['successmsg'])).'</strong>';
				$content = $result;
		} else {
			$errormsg = esc_html( stripslashes($options['errormsg']) ) ;
			if ($emailerror)
				$errormsg .= '<br/>The email address you entered is not a valid email address.';
			$content = sf_candidate_form($options, $is_sidebar, $errormsg);
		}
	} elseif(isset($_POST['w2lsubmitpreview'])) {
			require_once('ws_partner_api_connections.inc.php');
			require_once('applicant_ws_globals.inc.php');
			$applicantCollection['Candidate_Status__c'] = 'Candidate Received';  // hardcoded here for convenience.
			try	{
					$sObject = new SObject();  
					$sObject->fields = $applicantCollection;
					$sObject->type = 'SFDC_Candidate__c'; 
					$rid = $mySforceConnection->create(array($sObject));
					$idForAttachment = $rid -> id;

			} catch (Exception $tryerr) {
					echo("exception caught: " . $tryerr -> getMessage() . "<br>");
			}
			try	{
					$sObject1 = new SObject();  
					$sObject1->type = 'Attachment';
					// load the tmp doc
					$handle = fopen($_FILES['form_attachment_1']['tmp_name'],'rb');
					$file_content = fread($handle,filesize($_FILES['form_attachment_1']['tmp_name']));
					fclose($handle);
					$resumefile =  chunk_split(base64_encode($file_content));
					$resumefilename = 'Resume: ' . $applicantCollection['Name'] . ', ' . date('m-d-y') . '.' . end(explode('.',$_FILES['form_attachment_1']['name']));
					$sObject1->fields = array(
							'Body' => $resumefile,
							'Name' => $resumefilename, 
							'IsPrivate' => 'false',
							'ParentId' => $idForAttachment
					); 

					$rid1 = $mySforceConnection->create(array($sObject1));

			} catch (Exception $tryerr1) {
					echo("exception caught: " . $tryerr1 -> getMessage() . "<br>");
			}
			try	{
					$sObject2 = new SObject();  
					$sObject2->type = 'Attachment';
					// load the tmp doc
					$handle = fopen($_FILES['form_attachment_2']['tmp_name'],'rb');
					$file_content = fread($handle,filesize($_FILES['form_attachment_2']['tmp_name']));
					fclose($handle);
					$coverletterfile =  chunk_split(base64_encode($file_content));
					$coverletterfilename = 'Cover Letter: ' . $applicantCollection['Name'] . ', ' . date('m-d-y') . '.' . end(explode('.',$_FILES['form_attachment_2']['name']));
					$sObject2->fields = array(
							'Body' => $coverletterfile,
							'Name' => $coverletterfilename, 
							'IsPrivate' => 'false',
							'ParentId' => $idForAttachment
					); 

					$rid2 = $mySforceConnection->create(array($sObject2));

			} catch (Exception $tryerr2) {
					echo("exception caught: " . $tryerr2 -> getMessage() . "<br>");
			}
				$content = '<strong>'.esc_html(stripslashes($options['successmsg'])).'</strong>';
	}

	else {
		$content = sf_candidate_form($options, $is_sidebar);
	}
	return $content;
}

add_shortcode('sf_candidate', 'sf_candidate_form_shortcode');	
