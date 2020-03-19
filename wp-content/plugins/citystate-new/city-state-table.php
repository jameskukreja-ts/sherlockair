<?php

/*

 *

 * Plugin Name: City State Contectivity (new)

 * Description: Plugin to manage City state links in the footer.
 * Patched 9/13/19 to hide extra H1s and H2s from being rendered hidden in the page source

 * Version: 3.0

 *

 */ 

	/*

	 * hooks make table at the time of activation

	 * @table name 'wp_citystate'

	*/

	register_activation_hook( __FILE__, 'citystate_install' );

	

	function citystate_install() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'citystate';

		

		$charset_collate = $wpdb->get_charset_collate();



		$sql = "CREATE TABLE IF NOT EXISTS $table_name (

			id mediumint(9) NOT NULL AUTO_INCREMENT,

			page_id int NOT NULL,

			citystate longtext,

			UNIQUE KEY id (id)

		) $charset_collate;";



		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		

	}

	



	/*

	 * Plugin Activation Show setting and linked pages setting options in wp admin

	 * register settings, register menu 

	*/

		

	add_action('admin_menu', 'register_my_custom_submenu_page');



	function register_my_custom_submenu_page() {

		add_submenu_page( 'options-general.php', 'City-State-Connectivity', 'City-State-Connectivity', 'manage_options', 'csc-options', 'csc_settings_Form' ); 

		add_action( 'admin_init', 'register_citystatesettings' );

	}





	function register_citystatesettings(){

		register_setting( 'city-state-settings-group', 'main_head' );

		register_setting( 'city-state-settings-group', 'img_path' );

		register_setting( 'city-state-settings-group', 'hov_img_path' );

		register_setting( 'city-state-settings-group', 'incl_css' );

		register_setting( 'city-state-settings-group', 'incl_cus_css' );

		//register_setting( 'city-state-settings-group', 'vs_all_pages' );

		register_setting( 'city-state-settings-group', 'count_incr','intval' );

		register_setting( 'city-state-settings-group', 'csc_post' );

		

	}

	

	/*

	 * Plugin Activation hook

	 * Register Post type, taxonomony, 

	*/

	

	add_action('init','citystate_activate');



	function citystate_activate() {

		register_post_type( 'citystate', 

							array(	

								'label' => 'City-State',

								'description' => '',

								'public' => true,

								'show_ui' => true,

								'show_in_menu' => true,

								'capability_type' => 'post',

								'hierarchical' => false,

								'rewrite' => array(

												'slug' => '',

												'with_front' => false

											),

								'query_var' => true,

								'exclude_from_search' => false,

								'supports' => array( 'title', 'editor', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'thumbnail', 'author', 'page-attributes'												),					

								'labels' => array (

												'name' => 'City-State',

												  'singular_name' => '',

												  'menu_name' => 'City-State',

												  'add_new' => 'Add City-State',

												  'add_new_item' => 'Add New City-State',

												  'edit' => 'Edit',

												  'edit_item' => 'Edit City-State',

												  'new_item' => 'New City-State',

												  'view' => 'View City-State',

												  'view_item' => 'View City-State',

												  'search_items' => 'Search City-State',

												  'not_found' => 'No City-State Found',

												  'not_found_in_trash' => 'No City-State Found in Trash',

												  'parent' => 'Parent City-State'

											)

							) 

		);



		register_taxonomy( 

			'State Heading',

			array (

				0 => 'citystate',

			),

			array( 

				'hierarchical' => false,

				'label' => 'State Heading',

				'show_ui' => true,

				'query_var' => true,

				'rewrite' => array('slug' => ''),

				'singular_label' => 'State Heading'

			)

		);

		

		wp_enqueue_script( 'jquery');

		wp_enqueue_script('thickbox');

		wp_enqueue_style('thickbox');

		wp_enqueue_script( "gform_vs_script", plugin_dir_url(__FILE__) . "/assets/js/vs-script.js", array("jquery","thickbox"), '1.0' );

		wp_register_style( 'csc-front-style', plugin_dir_url(__FILE__) . "/assets/css/csc-frontend-style.css" );



	}

	

	

	/*

	 * Hook's jquery on admin head

	*/

	

	add_action( 'wp_print_scripts',  'load_js' );



	function load_js() { 

              if( is_admin() ){

		 wp_enqueue_script( 'jquery' );

		 wp_enqueue_media();

		 wp_enqueue_script( 'csc_admin_script', plugin_dir_url(__FILE__).'/assets/js/csc_admin_script.js', array( 'jquery' ) );  

	      }

	}

	

	/*

	 * Hook's style on admin head

	*/

	

	add_action( 'admin_head',  'load_css' );

	

	function load_css() {

		wp_enqueue_style( 'csc-style', plugin_dir_url(__FILE__) . "/assets/css/csc-admin-style.css" );

	}

	

	/*

	 * Backend Form Function

	*/



	function csc_settings_Form() {

		$city_pages = get_posts(array('posts_per_page' => -1, 'post_type' => 'citystate','post_status'=>'publish','orderby'=> 'name','meta_key'=>'City Name'));

		$pages =  get_pages();

		global $wpdb;

		$table_name = $wpdb->prefix . 'citystate';

		?>

		<div class="csc-error" style="display:none;"></div>

		<div class="wrap">

		<?php screen_icon(); ?>

		<h2>City-State-Contectivity</h2>

		<ul class="subsubsubsub csc-links">

			<li class="all">

				<a class="current" href="<?= admin_url(); ?>options-general.php?page=csc-options">Linked Pages</a> |

			</li>

			<li class="publish">

				<a href="<?= admin_url(); ?>options-general.php?page=csc-options&action=settings">Other Options</a>

			</li>

		</ul>

			

		<?php if($_REQUEST['action'] === 'settings'){ ?>

		<form id="css-city-form" method="post" action="options.php">

		<?php settings_fields( 'city-state-settings-group' ); ?>

			<table class="form-table">

				<tbody>

					<tr>

						<th scope="row">

							<label>Main Heading</label>

						</th>

						<td>

							<input style="width:80%" type="text" name='main_head' id="main_head" value='<?php echo get_option('main_head'); ?>'/>

						</td>

					</tr>

					<tr>

						<th scope="row"><label>Image Path</label>

						</th>

						<td>

							<input class="upload_image_button" type="button"  value="Upload Image" style="cursor:pointer;" /><input style="width:80%" type="text" name='img_path' id="img_path" value='<?php echo get_option('img_path'); ?>'/>

						</td>

					</tr>

					<tr>

						<th scope="row">

							<label>Hover image Path</label>

						</th>

						<td>

							<input class="upload_image_button" type="button"  value="Upload Image" style="cursor:pointer;" /><input style="width:80%" type="text" name='hov_img_path' id="hov_img_path" value='<?php echo get_option('hov_img_path'); ?>'/>

						</td>

					</tr>

					<tr>

						<th scope="row">

							<label>Exclude Default CSS</label>

						</th>

						<td>

							<select name="incl_css" id="incl_css">

							  <option value="yes" <?php if(get_option('incl_css')== 'yes'){ echo "selected";} ?>>Yes</option>

							  <option value="no" <?php if(get_option('incl_css')== 'no'){ echo "selected";} ?>>No</option>

							</select> 

							<?php //echo get_option('incl_css') ?>

						</td>

					</tr>

					<tr>

						<th scope="row">

							<label>Add Custom CSS</label>

						</th>

						<td>

							<textarea id="incl_cus_css" name="incl_cus_css"  rows="4" cols="75" ><?php echo get_option('incl_cus_css'); ?></textarea>

							<input type="hidden" name="count_incr" id="count_incr" value="<?php echo get_option('count_incr'); ?>" />

						</td>

					</tr>

					<tr>

						<th scope="row">

							<label>Include Post</label>

						</th>

						<td>

							<select name="csc_post" id="csc_post">

							  <option value="no" <?php if(get_option('csc_post')== 'no'){ echo "selected";} ?>>No</option>

							  <option value="yes" <?php if(get_option('csc_post')== 'yes'){ echo "selected";} ?>>Yes</option>

							</select> 

							<?php //echo get_option('incl_css') ?>

							<p class="description">For including posts in Select page dropdown.</p>

						</td>

					</tr>

					<tr>

						<th scope="row"><?php submit_button(); ?></th>

					</tr>

				</tbody>

			</table>

		</form>

		<?php }

		

		if( $_REQUEST['action'] === '' || $_REQUEST['action'] !== 'settings' ){

		

			$results = $wpdb->get_results( 

							"

							SELECT * 

							FROM $table_name

							"

						);

						$i =1; 

			if(!empty($results)){

			foreach( $results as $result ) { 

		?>

		

		<form id="csc-settings-<?= $i; ?>" class="csc-form" method="post" action="" enctype="multipart/form-data">

			<div class="csc-row">

			<input type="hidden" name="change" value="update">

				<div class="csc-page-section">

					<h3 style="line-height:30px;"><span>Select Pages</span></h3>

					<div class="pagelistall">

						<select class="vs-slect" name="csc_pages" id="pag_list-<?= $i; ?>">

							<option value="0">--Select Page--</option>

							<?php 

							$all_pages =  get_pages();

							if( get_option('csc_post') == 'yes' ){

								$args = array(

										'posts_per_page'   => -1,

										'offset'           => 0,

										'orderby'          => 'date',

										'order'            => 'DESC',

										'post_type'        => 'post',

										'post_status'      => 'publish',

										'suppress_filters' => true 

									);

								$posts_array = get_posts( $args );

								$all_pages = array_merge( $all_pages, $posts_array );

							}

							foreach($all_pages as $page){?>

							<option  <?php if( $result->page_id == $page->ID){ echo "selected"; } ?> value="<?php echo $page->ID; ?>" ><?php echo $page->post_title; ?></option>

							<?php		}	?>

							

						</select> 

					</div>

				</div>

				<div class="csc-citystate-section">

					<div class="postbox postbox1" id="add-page-<?php echo $i; ?>">

						<h3 style="line-height:30px;"><span>City State Post</span></h3>

						<div class="inside">

							<div class="vs_posttypediv" id="vs_posttype-page-<?php echo $i; ?>" style="height:100px;overflow:auto">

								<ul>

									<?php 

																		

									asort($city_pages);

									foreach($city_pages as $page){

									?>

									<li><label class="vs-page-list"><input <?php if( @in_array( $page->ID, unserialize($result->citystate) ) ) { echo "checked";  } ?> type="checkbox" id="csc_list_<?php echo $page->ID; ?>" value="<?php echo $page->ID; ?>" name="csc_list[]" class="menu-item-checkbox"> <?php echo $page->post_title; ?></label></li>

									<?php

										}

									?>

								</ul>

							</div>

						</div>

					</div>

					<div class="btn-sec">

						<span class="csc-spinner spinner-<?= $i; ?>"></span>

						<div class="Delete">

							<a href="javascript:void(0)" class="button-primary delete-block" data-formid="<?= $i; ?>" data-pageid="<?= $result->page_id ?>">DELETE THIS BLOCK</a>

						</div>

						<div class="Save">

							<input type="submit" class="button-primary save-block" data-formid="<?= $i; ?>" name="submit" value="SAVE THIS BLOCK"/>

						</div>

					</div>

				</div>

			</div>

		</form>

		<?php $i++;

			} 

			}else{

			?>

				<div class="csc-empty"><strong>Start Linking Your Pages to  CItystates on Clicking Add New Block Button.</Strong></div>

		<?php } ?>

	<!---- end ----->

	

			<div class="last_tr"><span class="csc-spin"></span><a href="javascript:void(0)" class="button button-primary button-large add-block">ADD NEW BLOCK</a></div>

		

		</div>

	

	<?php

		}

	}



	/*

	 * Function to show service area list on frontend

	 * city_state shortcode

	*/

	  

	add_shortcode( 'city_state', 'city_state_shortcode' );

	  

	function city_state_shortcode( $atts ){

		

		$main_head = get_option('main_head');

		$img_path = get_option('img_path');

		$hov_img_path = get_option('hov_img_path');

		$incl_css = get_option('incl_css');

		$incl_cus_css = get_option('incl_cus_css');

		

		global $wpdb;

		$table_name = $wpdb->prefix . 'citystate';

		$page_id = get_the_ID();

		$results = $wpdb->get_row( 

			"

			SELECT * 

			FROM $table_name WHERE page_id = $page_id

			"

		);

		if( $results ){

		?>

	

<div id="msg-container" class="service-area">
			<div class="msg-inner container">
			<div class="msg-head"> 
				<div class="butonimg"> service areas </div> 
			</div>		
			<div class="msg-body" id="msg-body">
				<div class="msgtext">
				<?php 	
					global $wp_query;
					$post_id = $wp_query->post->ID;
					$custom_heading = get_post_meta( $post_id, 'City-state Heading', $single );
					
					if(!empty($custom_heading)){
						$main_head = $custom_heading[0];
					}
					echo $main_head 
				?> 
				</div>
				<div id="block-views-citylist-block_1">
					<div class="inner-content">
					<?php 
						$city_state = unserialize( $results->citystate );
						$myLists = get_categories('taxonomy=State Heading&orderby=name&hide_empty=1');
									
						if( count($myLists) > 0 ){
							/* list city state having state heading */
							foreach($myLists as $mylist){
								$posts = query_posts( 
											array( 
												'post_type' => 'citystate',
												'taxonomy'=>'State Heading',
												'posts_per_page' => 200,
												'meta_key' => 'City Name',
												'order' => 'ASC',
												'post__in'=>$city_state,
												'term' => $mylist->name
												)
											);
									// }
								if (have_posts()) :
									while (have_posts()) : the_post();
										$all_list[$i][heading] = $mylist->name;
										$arrcustField = get_post_custom_values("City Name", $post->ID);
										$cityname = $arrcustField[0];
										$all_list[$i][url] =get_permalink();
										if($cityname == ''){
											$all_list[$i][cityname] =ucfirst(get_the_title());
										}
										$all_list[$i][cityname] =ucfirst($cityname);//List generated
										$i++;
									endwhile;
								endif;
								wp_reset_query();
								aasort($all_list, 'cityname');
								prnt_out_put( $all_list );
								$all_list = array();
							}
							
						}else{
							/* list city state without having state heading */
							$posts = query_posts( 
										array( 
											'post_type' => 'citystate',
											'posts_per_page' => 200,
											'meta_key' => 'City Name',
											'order' => 'ASC',
											'post__in'=>$city_state
										)
									);
						
							if (have_posts()) :
								$i = 0;
								while (have_posts()) : the_post();
									$arrcustField = get_post_custom_values( "City Name", $post->ID );
									$cityname = $arrcustField[0];
									$all_list[$i][url] =get_permalink();
									if($cityname == ''){
										$all_list[$i][cityname] = ucfirst(get_the_title());
									}
									$all_list[$i][cityname] = ucfirst( $cityname );//List generated
									$i++;
								
								endwhile;
							endif;
							wp_reset_query();
							aasort( $all_list, 'cityname' );
							
							foreach( $all_list as $single )
							
							{
								$singleindexarray[]= '<li class="custom-class-'.$single[cityname].'"><a href="'.$single[url].'">'.$single[cityname].'</a></li>';
							}
							
							
							$totalcount = count($singleindexarray) ;
							$rowcount =  floor(($totalcount)/1);
							$extrarowcount = $totalcount - ($rowcount*1);
							$firstrow = 1 + $extrarowcount;
							$j=0;
							
							for($i=0;$i<$totalcount;$i++){
								if($j==0){
									echo "<ul>";
								}
								echo $singleindexarray[$i];
								if($extrarowcount>0)
									$checkrow = $rowcount;
								else
									$checkrow = $rowcount-1;										
								if($j==$checkrow){
								echo "</ul>";
									$j = -1;
									$extrarowcount--;
								}
								$j++;
							
							}
							
							if($j!=0){echo "</ul>";
							}
						}
						
						
																		
					?>
					</div>
				</div>
			</div>
		</div>
	</div>



	<?php 

		if(empty($incl_css) || $incl_css == 'yes'){

		 wp_enqueue_style( 'csc-front-style');

		}

	?>

	<?php 

		if(!empty($incl_cus_css)){

	?>

		<style>

			<?php echo $incl_cus_css; ?>

		</style>

	<?php 

		}

	?>

	<style>

		.butonimg {

			background: url("<?php echo $img_path; ?>") no-repeat scroll 0 0 transparent;

		}

		.butonimg:hover{

		 background: url("<?php echo $hov_img_path; ?>") no-repeat scroll 0 0 transparent;

		}

		.butonimghover {

			background: url("<?php echo $hov_img_path; ?>") no-repeat scroll 0 0 transparent !important;

		}

	</style>	

	<?php

		}	

	}

	/* Ajax functions start from here */

	/*

	 *  function use to save, insert and update city state into table wp_citystate

	 *  @ page_id main

	*/

	

	add_action('wp_ajax_vs_savecitystate', 'vs_savecitystate');

	

	function vs_savecitystate(){

		global $wpdb;

		$table_name = $wpdb->prefix . 'citystate';

		parse_str($_REQUEST['form'], $output);

		if( $output['csc_pages'] == 0 || empty( $output['csc_pages'] ) ){

			echo $msg = 111;

			die;

		}

		

		if($output['change'] == 'update' ){

			$page_id = $output['csc_pages'];

			$cs_array = serialize($output['csc_list']);

			$query = $wpdb->update( 

						$table_name, 

						array( 

							'page_id' => $page_id, 

							'citystate' => $cs_array 

						), 

						array( 'page_id' => $page_id ), 

						array( 

							'%d',

							'%s'

						), 

						array( '%d' ) 

			);

			if($query){

				$msg = "Successfully Updated";

			}else{

				$msg = "Please Try Again, Something is wrong";

			}

		}

		if($output['change'] == 'insert' ){

			$page_id = $output['csc_pages'];

			$cs_array = serialize( $output['csc_list'] );

			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE page_id = $page_id" );

			if($count >= 1 ){

				echo $msg = 112;

				die;

			}else{

				$query = $wpdb->insert( 

					$table_name, 

					array( 

						'page_id' => $page_id, 

						'citystate' => $cs_array 

					), 

					array( 

						'%d', 

						'%s' 

					) 

				);

				if($query){

					$last = $wpdb->insert_id;

					$id = $wpdb->get_row( "SELECT page_id FROM $table_name WHERE id = $last" );

					echo $id->page_id."-";

					$msg = "Successfully Inserted";

				}else{

					$msg = "Please Try Again, Something is wrong";

				}

			}

		}

		echo $msg;

		die();

	}

	

	/*

	 *  function use to show new block of city state and page on admin

	 *  @ form count $tr main

	*/

	

	add_action('wp_ajax_vs_action', 'vs_action_callback');



	function vs_action_callback() {

		 

		$tr = $_POST['trnum']+1;

		$pages = get_pages(); 

		$posts = get_posts( 

					array( 

						'posts_per_page' => -1,

						'post_type' => 'citystate',

						'post_status'=>'publish',

						'orderby'=> 'name',

						'meta_key'=>'City Name'

					)

				);

		if( get_option('csc_post') == 'yes' ){

			$args = array(

					'posts_per_page'   => -1,

					'offset'           => 0,

					'orderby'          => 'date',

					'order'            => 'DESC',

					'post_type'        => 'post',

					'post_status'      => 'publish',

					'suppress_filters' => true 

				);

			$posts_array = get_posts( $args );

			$pages = array_merge( $pages, $posts_array );

		}



		$whole_string = '<form id="csc-settings-'.$tr.'" class="csc-form" method="post" action="" enctype="multipart/form-data">';

		$whole_string .= '<div class="csc-row"><input type="hidden" class="csc-hidden" name="change" value="insert"><div class="csc-page-section">Select Pages<div class="pagelistall">';

		$whole_string .= '<select  class="vs-slect" name="csc_pages" id="pag_list-'.$tr.'">';

		$whole_string .= '<option value="0">--Select Page--</option>';

		

		foreach($pages as $page){

			$whole_string .= '<option  value="'.$page->ID.'" >'.$page->post_title.'</option>';

		}

		$whole_string .= '</select></div></div><div class="csc-citystate-section">';

		$whole_string .= '<div class="postbox postbox1" id="add-page-'.$tr.'">';

		$whole_string .= '<h3 style="line-height:30px;"><span>City State Post</span></h3><div class="inside">';

		$whole_string .= '<div class="vs_posttypediv" id="vs_posttype-page-'.$tr.'" style="height:100px;overflow:auto"><ul>';

		asort($posts);

		foreach($posts as $page){

		$whole_string .= '<li><label class="vs-page-list"><input type="checkbox" id="csc_list_'.$page->ID.'" value="'.$page->ID.'" name="csc_list[]" class="menu-item-checkbox">'.$page->post_title.'</label></li>';

		}

		$whole_string .= '</ul></div></div></div>';

		$whole_string .= '<div class="btn-sec"><span class="csc-spinner spinner-'.$tr.'"></span><div class="Delete" style="display:none;"><a href="javascript:void(0)" class="button-primary delete-block" data-formid="'.$tr.'" data-pageid="">DELETE THIS BLOCK</a></div><div class="Save"><input type="submit" data-formid="'.$tr.'" class="button-primary save-block" name="submit" value="SAVE THIS BLOCK"/></div>

		</div></div></div></form>';

		echo $whole_string; 

		die(); // this is required to return a proper result

	}

	

	/*

	 *  function use to delete linked block of city state on admin end

	 *  @ page id $d_id main

	*/

		

	add_action('wp_ajax_vs_action_delete', 'vs_action_delete');

	

	function vs_action_delete() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'citystate';

		$d_id = $_REQUEST['delete_id'];

		$query = $wpdb->delete( $table_name, array( 'page_id' => $d_id ), array( '%d' ) );

		if($query)

			echo 1;

		die;

	}

		

		

	function pre_print_r( $p ){

		echo "<pre>";

		print_r($p);

		echo "</pre>";

		//die;

	}

	

	

	function prnt_out_put( $all_list ) {

		

		$t = 0;

		foreach( $all_list as $single ) {

			

			$singleindexarray[]= '<li><a href="'.$single[url].'">'.$single[cityname].'</a></li>';

			if($t==0){

				echo '<div class="main-heading"><strong>'.$single[heading].'</strong></div>';

				$t++;

			}

		}

				

		$totalcount = count($singleindexarray) ;

		$rowcount =  floor(($totalcount)/5);

		$extrarowcount = $totalcount - ($rowcount*5);

		$firstrow = 5 + $extrarowcount;

		$j=0;

				

		for($i=0;$i<$totalcount;$i++){

			if($j==0){

				//echo '<div class="main-heading">'.$single[heading].'</div>';

				echo "<ul>";

			}

			echo $singleindexarray[$i];

			if($extrarowcount>0)

				$checkrow = $rowcount;

			else

				$checkrow = $rowcount-1;										

			if($j==$checkrow){

			echo "</ul>";

				$j = -1;

				$extrarowcount--;

			}

			$j++;

		}

		if($j!=0){echo "</ul>";

		}

	}

	

	function aasort (&$array, $key) {

		$sorter=array();

		$ret=array();

		reset($array);

		foreach ($array as $ii => $va) {

			$sorter[$ii]=$va[$key];

		}

		asort($sorter);

		foreach ($sorter as $ii => $va) {

			$ret[$ii]=$array[$ii];

		}

		$array=$ret;

	}

?>