<?php
/**
 * Plugin Name: Home Schema
  * Description: Home page Schema.
 * Version: 2.0
 * Author: BluShark Digital
 */

 ob_start(); 
  //*********************************Place scehma start****************************************************/
				

				add_action('admin_init', 'Add_Metaboxes');
				function Add_Metaboxes() {	
					add_meta_box( 'prepeatable-fields', 'Place Schema', 'Add_MetaBoxDisplay', 'page', 'normal', 'default');
					 
				}

				function Add_MetaBoxDisplay() {
					  
					global $post;
					global $post_ID, $post_type;
					//Schema fields only for home page condition start
					if ( empty ( $post_ID ) or 'page' !== $post_type ){ return; }     
					
					if ( $post_ID === (int) get_option( 'page_on_front' ) ){ 
						
					$place_repeatable_fields = get_post_meta($post->ID, 'place_repeatable_fields', true);
					

					wp_nonce_field( 'hhs_repeatable_meta_box_nonce', 'hhs_repeatable_meta_box_nonce' );
					?>
					<script type="text/javascript">
					jQuery(document).ready(function( $ ){
						$( '#place-add-row' ).on('click', function() {
							var row = $( '.place-empty-row.screen-reader-text' ).clone(true);
							row.removeClass( 'place-empty-row screen-reader-text' );
							row.insertBefore( '#repeatable-fieldset-two tbody>tr:last' );
							return false;
						});
					
						$( '.premove-row' ).on('click', function() {
							$(this).parents('tr').remove();
							return false;
						});
					});
					</script>
					 <p class="meta-options hcf_field">
						<label for="place_name">Name</label>
						<input id="place_name"
							type="text"
							name="place_name"
							value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'place_name', true ) ); ?>">
					</p>
					<table id="repeatable-fieldset-two" >
					<thead>
						<tr>
							<th width="40%">Latitude</th>			
							<th width="40%">Longitude</th>
							<th width="8%"></th>
						</tr>
					</thead>
					<tbody>
					<?php
					
					if ( $place_repeatable_fields ) :
					
					foreach ( $place_repeatable_fields as $field ) {
					?>
					<tr>
						<td><input type="text" class="widefat" name="latitude[]" value="<?php if($field['latitude'] != '') echo esc_attr( $field['latitude'] ); ?>" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" title="Only allowed number"/></td>
					
					
						<td><input type="text" class="widefat" name="longitude[]" value="<?php if ($field['longitude'] != '') echo esc_attr( $field['longitude'] ); ?>" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" title="Only allowed number"/></td>
					
						<td><a class="button premove-row" href="#">Remove</a></td>
					</tr>
					<?php
					}
					else :
					// show a blank one
					?>
					<tr>
						<td><input type="text" class="widefat" name="latitude[]" title="Only allowed number" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" /></td>
					
						<td><input type="text" class="widefat" name="longitude[]" title="Only allowed number" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" /></td>
					
						<td><a class="button premove-row" href="#">Remove</a></td>
					</tr>
					<?php endif; ?>
					
					<!-- empty hidden one for jQuery -->
					<tr class="place-empty-row screen-reader-text">
						<td><input type="text" class="widefat" name="latitude[]" title="Only allowed number" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" /></td>
						<td><input type="text" class="widefat" name="longitude[]" title="Only allowed number" pattern="^[-+]?([0-9]+(\.[0-9]+)?|\.[0-9]+)$" /></td>
						  
						<td><a class="button premove-row" href="#">Remove</a></td>
					</tr>
					</tbody>
					</table>
					<?php 
						/*Single value display*/ ?>
					<p><a id="place-add-row" class="button" href="#">Add another</a></p>
					<?php
					} else {
						?>
						<script>
						jQuery('label[for=prepeatable-fields-hide]').remove();
						jQuery('#prepeatable-fields').remove();
						</script>
						<?php 
					} 
				}

				add_action('save_post', 'MetaBox_save');
				function MetaBox_save($post_id) {
					/*SIngle fields value save */
					 if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
					if ( $parent_id = wp_is_post_revision( $post_id ) ) {
						$post_id = $parent_id;
					}
					$fields = [
							   'place_name',
					];
					foreach ( $fields as $field ) {
						if ( array_key_exists( $field, $_POST ) ) {
							update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
						}
					 } 
					/*SIngle fields value save end */
					if ( ! isset( $_POST['hhs_repeatable_meta_box_nonce'] ) ||
					! wp_verify_nonce( $_POST['hhs_repeatable_meta_box_nonce'], 'hhs_repeatable_meta_box_nonce' ) )
						return;
					
					if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
						return;
					
					if (!current_user_can('edit_post', $post_id))
						return;
					
					$old = get_post_meta($post_id, 'place_repeatable_fields', true);
					$new = array();
					
					
					$Placelatitudes = $_POST['latitude'];
					$Placelongitudes = $_POST['longitude'];
					
					$count = count( $Placelatitudes );
					
					for ( $i = 0; $i < $count; $i++ ) {
						if ( $Placelatitudes[$i] != '' ) :
							$new[$i]['latitude'] = stripslashes( strip_tags( $Placelatitudes[$i] ) );
							
							
							if ( $Placelongitudes[$i] != '' )				
								$new[$i]['longitude'] = stripslashes( $Placelongitudes[$i] ); // and however you want to sanitize
						endif;
					}

					if ( !empty( $new ) && $new != $old )
						update_post_meta( $post_id, 'place_repeatable_fields', $new );
					elseif ( empty($new) && $old )
						delete_post_meta( $post_id, 'place_repeatable_fields', $old );
				} 

				//meta box value save in backend show on frontend
				function HookHeader() {
					if ( is_front_page()) {
					  //multiple value get
					$emergency_contact_meta_place = get_post_meta( get_the_ID(), 'place_repeatable_fields', true);
					
					  ?>
					  <style>
					  .schema-place{display:none}
					  </style>
					   <div  class="schema-place" itemscope itemtype="http://schema.org/Place">
										<span itemprop='name'><?php echo esc_attr( get_post_meta( get_the_ID(), 'place_name', true ) ); ?></span>
										<?php
									if ( $emergency_contact_meta_place ) {
									foreach ( $emergency_contact_meta_place as $emergency_contact_meta_place_vals ) {
									?>
									<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
									<meta itemprop="latitude" content="<?php echo esc_attr($emergency_contact_meta_place_vals['latitude']);?>" />
									<meta itemprop="longitude" content="<?php echo esc_attr($emergency_contact_meta_place_vals['longitude']);?>" />
									</div>
										<?php 
										}
									}						
									?>			
										
								   </div>	  
					<?php
					
					 }
					
				}
				add_action('wp_head','HookHeader');
				//*********************************Place scehma end****************************************************/
				
					
				//*********************************Organization scehma start****************************************************/
 add_action('admin_init', 'OrgainzationAddMetaBoxes');
	function OrgainzationAddMetaBoxes() {
		add_meta_box( 'gpminvoice-group', 'Organization Schema', 'OrgainzationMetaBoxDisplay', 'page', 'normal', 'default');
	}
/* Org Meta Box */
	function OrgainzationMetaBoxDisplay() {
			global $post;
			global $post_ID, $post_type;
					//Schema fields only for home page condition start
					if ( empty ( $post_ID ) or 'page' !== $post_type ){ return; }  
						if ( $post_ID === (int) get_option( 'page_on_front' ) ){	
							$gpminvoice_group = get_post_meta($post->ID, 'customdata_group', true);
							 wp_nonce_field( 'gpm_repeatable_meta_box_nonce', 'gpm_repeatable_meta_box_nonce' );
							?>
							<p class="meta-options hcf_field">
									<label for="org_name">Name</label>
									<input id="org_name"
										type="text"
										name="org_name"
										value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'org_name', true ) ); ?>">
								</p>
								<p class="meta-options hcf_field">
								<label for="org_fb">Facebook</label>
								<input id="org_fb"
								type="text"
								name="org_fb"
								value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'org_fb', true ) ); ?>">
								</p>
								<p class="meta-options hcf_field">
								<label for="org_twittr">Twiiter</label>
								<input id="org_twittr"
								type="text"
								name="org_twittr"
								value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'org_twittr', true ) ); ?>">
								</p>
								<p class="meta-options hcf_field">
								<label for="org_LinkedIn ">LinkedIn </label>
								<input id="org_LinkedIn"
								type="text"
								name="org_LinkedIn"
								value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'org_LinkedIn', true ) ); ?>">
								</p>
								<p class="meta-options hcf_field">
								<label for="org_youtube ">Youtube </label>
								<input id="org_youtube"
								type="text"
								name="org_youtube"
								value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'org_youtube', true ) ); ?>">
								</p>
	
										<?php } else {
			?>
		<script>
		jQuery('label[for=gpminvoice-group-hide]').remove();
		jQuery('#gpminvoice-group').remove();
		</script>
		<?php 
	} 
}
			add_action('save_post', 'OrgainzationMetaBoxSave');
			function OrgainzationMetaBoxSave($post_id) {	
				/*SIngle fields value save */
				 if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
				if ( $parent_id = wp_is_post_revision( $post_id ) ) {
					$post_id = $parent_id;
				}
				$fields = [
						   'org_name',
						   'org_fb',
						   'org_twittr',
						   'org_LinkedIn',
						   'org_youtube',
						  
				];
				foreach ( $fields as $field ) {
					if ( array_key_exists( $field, $_POST ) ) {
						update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
					}
				 } 
				/*Single fields value save end */   
			 }

			 //Organization value save in backend show on frontend
			function HeaderHookOrg() {
				if ( is_front_page()) {
				 
				  //single value get
					$org_name= esc_attr( get_post_meta( get_the_ID(), 'org_name', true ) );
					//twitter case
					$org_twitter = get_post_meta( get_the_ID(), 'org_twittr', true ) ;
					$org_Twiiter ="";
					if($org_twitter !='' && !empty($org_twitter)){
					$org_Twiiter .= '"'.$org_twitter.'"'.',';
					}
					else{
						$org_Twiiter .= '"twitter.com,"';
					}
					//facebook case
					$org_facebook= esc_attr( get_post_meta( get_the_ID(), 'org_fb', true ) ); 
					$org_Facebook ="";
					if($org_facebook !='' && !empty($org_facebook)){
					$org_Facebook .= '"'.$org_facebook.'"'.',';
					}
					else{
						$org_Facebook .= '"facebook.com,"';
					}
					//linkdin case
					$org_LinkedIn= esc_attr( get_post_meta( get_the_ID(), 'org_LinkedIn', true ) ); 
					$org_link ="";
					if($org_LinkedIn !='' && !empty($org_LinkedIn)){
					$org_link .= '"'.$org_LinkedIn.'"'.',';
					}
					else{
						$org_link .= '"linkdin.com,"';
					}
					//youtube case
					$org_youtubes= esc_attr( get_post_meta( get_the_ID(), 'org_youtube', true ) ); 
					$org_Youtube ="";
					if($org_youtubes !='' && !empty($org_youtubes)){
					$org_Youtube .= '"'.$org_youtubes.'"';
					}
					else{
						$org_Youtube .= '"youtube.com"';
					}
					
				   
				   ?>
				  <script type="application/ld+json">
				  {
					"@context": "http://www.schema.org",
					"@type" : "Organization",
					"name" : "<?php echo $org_name ?>",
					"url" : "<?php echo bloginfo('url') ?>",
					"sameAs": [<?php echo $org_Twiiter.$org_Facebook.$org_link.$org_Youtube;?>]
				  }
				  </script>	 
				<?php	
				}
			}
			add_action('wp_head','HeaderHookOrg');
?>
	<style>
	.hcf_field label {
		font-size: 17px;
		margin: 0 1em;
	}
	#repeatable-fieldset-one label{
		font-size: 17px;
		margin: 1px 1.6em
	}
	input#org_name {
			width: 46em;
	}
	input.org_url {
		  width: 46em;
	}
	</style>
<?php 
//*********************************Organization scehma end****************************************************/
 //*********************************Legal scehma start****************************************************/
 add_action('admin_init', 'legal_hhs_add_meta_boxes' );
function legal_hhs_add_meta_boxes() {
    add_meta_box( 'repeatable_fields', 'Legal Schema', 'repeatable_meta_box_display', 'page', 'normal', 'default');
}


function repeatable_meta_box_display( $post ) { 
           global $post;
					global $post_ID, $post_type;
					//Schema fields only for home page condition start
					if ( empty ( $post_ID ) or 'page' !== $post_type ){ return; }     
					
					if ( $post_ID === (int) get_option( 'page_on_front' ) ){ 
			$repeatable_fields = get_post_meta($post->ID, 'repeatable_fields', true); 

			if ( empty( $repeatable_fields ) ){
				$repeatable_fields = array (                                
											'streetAddress' => '',                               
											'addressRegion' => '',                               
											'postalCode' => '',                               
											'telephone' => '',                               
											'addressLocality' => '' );
			}

			wp_nonce_field( 'hhs_repeatable_meta_box_nonce', 'hhs_repeatable_meta_box_nonce' ); 
?> 
<?php /*Single value display*/ ?>
					<div class="name_fileds_legal_schema">
					 <p class="meta-options hcf_field">
						<label for="hcf_name">Name</label>
						<input id="hcf_name"
							type="text"
							name="hcf_name"
							value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'hcf_name', true ) ); ?>">
					</p>
					<p class="meta-options hcf_field">
						<label for="hcf_price">Price</label>
						<input id="hcf_price"
							type="text"
							name="hcf_price"
							value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'hcf_price', true ) ); ?>">
					</p>
					<p class="meta-options hcf_field">
						<label for="hcf_logo_link">Logo Image link</label>
						<input id="hcf_logo_link"
							type="text"
							name="hcf_logo_link"
							value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'hcf_logo_link', true ) ); ?>">
					</p>
					</div>
<script type="text/javascript">
			jQuery(document).ready(function( $ ){
			jQuery( '#add-row' ).click(function() { 
			var rowCount = jQuery('#repeatable-fieldset-one').find('.single-movie-row').not(':last-child').size(); 
			var newRowCount = rowCount + 1;

			var row = jQuery( '.empty-row.screen-reader-text' ).clone(true); 

			// Loop through all inputs
			row.find('input, textarea, label').each(function(){ 

				if ( !! jQuery(this).attr('id') )
					jQuery(this).attr('id',  jQuery(this).attr('id').replace('[%s]', '[' + newRowCount + ']') );  // Replace for

				if ( !! jQuery(this).attr('name') )
					jQuery(this).attr('name',  jQuery(this).attr('name').replace('[%s]', '[' + newRowCount + ']') );  // Replace for

				if ( !! jQuery(this).attr('for') )
					jQuery(this).attr('for',  jQuery(this).attr('for').replace('[%s]', '[' + newRowCount + ']') );  // Replace for

			});

			row.removeClass( 'empty-row screen-reader-text' ).find('.movie_rank_number').val('# '+newRowCount);
			row.insertBefore( '.empty-row' ); 

			// if row count hits 10, hide the add row button 
			if ( newRowCount == 10 ) { 
			jQuery('#add-row').fadeOut(); 
			} 

			return false; 
			});
			
			jQuery('.legal_remove-row').on('click', function() {
			//alert('remove');
			jQuery(this).parents('tr').remove();
			return false;
			});
			});
</script>
<h3>Address</h3>


<table id="repeatable-fieldset-one" style="width:100%;"> 
<thead> 
<tr> 
	<th width="20%">Street Address</th>
	<th width="20%">Address Locality</th>
	<th width="20%">Address Region</th>
	<th width="20%">Postal Code</th>
	<th width="20%">Telephone</th>
</tr> 
</thead> 
<tbody> 


<?php 

// set a variable so we can append it to each row 
$i = 1; 

foreach ( $repeatable_fields as $field ) { ?>

<tr class="single-movie-row ui-state-default"> 

 

    <td> 
    <!-- streetAddress field --> 
<input type="text" name="_legals[<?php echo $i;?>][streetAddress]" id="_legals[<?php echo $i;?>][streetAddress]" class="title_tinymce_editor" value="<?php if($field['streetAddress'] != ''){ echo  esc_attr($field['streetAddress']);}?>">
    </td> 

    <td>
    <input type="text" id="_legals[<?php echo $i;?>][addressLocality]" name="_legals[<?php echo $i;?>][addressLocality]" class="movie_description_editor_hidden" value="<?php if($field['addressLocality'] != '') { echo esc_attr( $field['addressLocality'] ); }?>">
    </td> 
	<td>
    <input type="text" id="_legals[<?php echo $i;?>][addressRegion]" name="_legals[<?php echo $i;?>][addressRegion]" class="movie_description_editor_hidden" value="<?php if($field['addressRegion'] != '') { echo esc_attr( $field['addressRegion'] ); }?>" >
    </td> 
	<td>
    <input type="text" id="_legals[<?php echo $i;?>][postalCode]" name="_legals[<?php echo $i;?>][postalCode]" class="movie_description_editor_hidden" value="<?php if($field['postalCode'] != '') { echo esc_attr( $field['postalCode'] ); }?>" pattern="[A-Za-z0-9]+">
    </td> 
	<td>
    <input type="text" id="_legals[<?php echo $i;?>][telephone]" name="_legals[<?php echo $i;?>][telephone]" class="movie_description_editor_hidden" value="<?php if($field['telephone'] != '') { echo esc_attr( $field['telephone'] ); }?>" pattern="^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$">
    </td> 

    <td>
    <a class=" legal_remove-row" href="#"><img src="<?php echo plugin_dir_url( __FILE__ );?>download.png" height="15px"></a>
    </td> 

</tr> 
<?php $i++; } ?>

<!-- empty hidden one for jQuery --> 
<tr class="empty-row screen-reader-text single-movie-row"> 



    <td> 
    <!-- streetAddress field --> 
    <input type="text" name="_legals[%s][streetAddress]" id="_legals[%s][streetAddress]" class="title_tinymce_editor">
    <!-- drop down or checkbox's with release formats --> 
    </td> 

    <td>
    <input type="text" id="_legals[%s][addressLocality]" name="_legals[%s][addressLocality]" class="movie_description_editor_hidden">
    </td> 
	<td>
    <input type="text" id="_legals[%s][addressRegion]" name="_legals[%s][addressRegion]" class="movie_description_editor_hidden">
    </td> 
	<td>
    <input type="text" id="_legals[%s][postalCode]" name="_legals[%s][postalCode]" class="movie_description_editor_hidden" pattern="[A-Za-z0-9]+">
    </td>
	<td>
    <input type="text" id="_legals[%s][telephone]" name="_legals[%s][telephone]" class="movie_description_editor_hidden" pattern="^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$">
    </td> 
	


    <td>
    <a class=" legal_remove-row" href="#"><img src="<?php echo plugin_dir_url( __FILE__ );?>download.png" height="15px"></a>
    </td> 

</tr> 

</tbody> 
</table> 

<p id="add-row-p-holder"><a id="add-row" class="btn btn-small btn-success" href="#">Insert Another Row</a></p> 
<?php 
} else {
						////print '<p><b>This is the another page!</b></p>';
						?>
						<script>
						jQuery('label[for=legal-repeatable-fields-hide]').remove();
						jQuery('#legal-repeatable-fields').remove();
						</script>
						<?php 
					}
}

 add_action('save_post', 'hhs_repeatable_meta_box_save'); 
function hhs_repeatable_meta_box_save($post_id) {
	
/*SIngle fields value save */
					 if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
					if ( $parent_id = wp_is_post_revision( $post_id ) ) {
						$post_id = $parent_id;
					}
					$fields = [
							   'hcf_name',
							   'hcf_price',
							   'hcf_logo_link',
					];
					foreach ( $fields as $field ) {
						if ( array_key_exists( $field, $_POST ) ) {
							update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
						}
					 } 
					/*SIngle fields value save end */	

			if ( ! isset( $_POST['hhs_repeatable_meta_box_nonce'] ) || 
			!wp_verify_nonce( $_POST['hhs_repeatable_meta_box_nonce'], 'hhs_repeatable_meta_box_nonce' ) ) 
			return; 



			if (!current_user_can('edit_post', $post_id)) 
			return; 

			$legalvalueInarray = array(); 

			if  ( isset ( $_POST['_legals'] ) && is_array( $_POST['_legals'] ) ) :

			foreach ( $_POST['_legals'] as $i => $legalAddress_save ){

			// skip the hidden "to copy" div
			if( $i == '%s' ){ 
				continue;
			}

			

			$legalvalueInarray[] = array(         
				'streetAddress' => isset( $legalAddress_save['streetAddress'] ) ? sanitize_text_field( $legalAddress_save['streetAddress'] ) : null, 'addressRegion' => isset( $legalAddress_save['addressRegion'] ) ? sanitize_text_field( $legalAddress_save['addressRegion'] ) : null,'addressLocality' => isset( $legalAddress_save['addressLocality'] ) ? sanitize_text_field( $legalAddress_save['addressLocality'] ) : null,
				'postalCode' => isset( $legalAddress_save['postalCode'] ) ? sanitize_text_field( $legalAddress_save['postalCode'] ) : null,
				'telephone' => isset( $legalAddress_save['telephone'] ) ? sanitize_text_field( $legalAddress_save['telephone'] ) : null,
				);
			}

			endif;
			// save movie data 
			if ( ! empty( $legalvalueInarray ) ) { 
			update_post_meta( $post_id, 'repeatable_fields', $legalvalueInarray ); 
			} else
			delete_post_meta( $post_id, 'repeatable_fields' ); 
			} 
			
			//meta box value save in backend show on frontend
				function LegalHookHeader() {
					if ( is_front_page()) { 
						//multiple value get
					$emergency_contact_meta_legal = get_post_meta( get_the_ID(), 'legal_repeatable_fields', true);
					  ?>
					  <style>.schema-hide{display: none;}.schema-address{display:none}</style>
					  <div itemscope itemtype="https://schema.org/LegalService">
						<div class="schema-hide">
										<span itemprop="name"><?php echo get_post_meta( get_the_ID(), 'hcf_name', true ); ?></span>
										<p itemprop="priceRange" ><?php echo get_post_meta( get_the_ID(), 'hcf_price', true ); ?></p> 
										<img src="<?php echo get_post_meta( get_the_ID(), 'hcf_logo_link', true ); ?>" itemprop="image" alt="<?php echo bloginfo('name');?>"/>
									</div>
									<?php if ( $emergency_contact_meta_legal ) {
									foreach ( $emergency_contact_meta_legal as $emergency_contact_meta_legal_vals ) { 
									//echo '<pre>'; print_r($emergency_contact_meta_legal);
									?>
									  <div itemprop="address" class="schema-address" itemscope="" itemtype="https://schema.org/PostalAddress">
												<ul>
													<li>
														<span itemprop="streetAddress">
														<?php echo $emergency_contact_meta_legal_vals['streetAddress']; ?>
														 </span>
													</li>
													<li>
														<span itemprop="addressLocality">
														<?php echo $emergency_contact_meta_legal_vals['addressLocality']; ?>
														 </span>
													</li>
													<li>
														<span itemprop="addressRegion">
														<?php echo $emergency_contact_meta_legal_vals['addressRegion']; ?>
														 </span>
													</li>
													<li>
														<span itemprop="postalCode">
														<?php echo $emergency_contact_meta_legal_vals['postalCode']; ?>
														 </span>
													</li>
													<li>
														<span itemprop="telephone">
														<?php echo $emergency_contact_meta_legal_vals['telephone']; ?>
														 </span>
													</li>
										</ul>
										</div>	
									<?php } } ?>
									 
					  </div>
					 
					<?php

					 }
					
				}
				add_action('wp_head','LegalHookHeader');
 //*********************************Legal scehma end****************************************************/

			ob_clean();
			