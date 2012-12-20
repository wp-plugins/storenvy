<?php
/*
Plugin Name: Storenvy
Plugin URI: http://trepmal.com/
Description: Display your Storenvy products on your site
Author: Kailey Lampert
Version: 0.4
Author URI: http://kaileylampert.com/
*/
/*
	Copyright (C) 2011-12 Kailey Lampert

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// delete_option('storenvy');

$se_storenvy = new se_storenvy();

class se_storenvy {

		var $defaults = array(
						'type' => 'items',
						'title' => 1,
						'link' => 1,
						'pubdate' => 1,
						'description' => 1,
						'image' => 1,
						'limit' => -1,
						'start' => 0,
						'fblike' => '1',
						'tweet' => '1',
						'per_page' => '-1'
					);

		function __construct() {
			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'admin_menu' , array( &$this , 'admin_menu' ) );
			add_action( 'wp_ajax_get_store_name', array( &$this, 'get_store_name_cb' ) );
			add_action( 'admin_footer-settings_page_storenvy/storenvy', array( &$this, 'admin_footer' ) );
			add_action( 'wp_head', array( &$this , 'wp_head' ) );
			add_action( 'contextual_help', array( &$this, 'help'), 10, 3);
			add_shortcode( 'storenvy', array( &$this , 'show' ) );

		}

		function init() {

				$itemformat = '<h3><a href="[short_url]">[name]</a></h3>
<strong>$[price]</strong>

[description]

[gallery]
<hr />';
			add_option( 'storenvy', array( 'storeurl' => '' , 'customcss' => 'div.se-item { clear:both; }', 'itemformat' => $itemformat ) );

			$options = get_option( 'storenvy', false );

			// If updating and itemformat isn't there, let's add it
			if ( $options && ! isset( $options['itemformat'] ) ) {
				$options['itemformat'] = $itemformat;
				update_option( 'storenvy', $options );
			}
		}
		function admin_menu() {
			global $storenvy_admin_page;
			$storenvy_admin_page = add_options_page( 'Storenvy' , 'Storenvy' , 'administrator' , __FILE__ , array( &$this , 'page' ) );

		} // end menu()

		function page() {

				$required_keys = array_keys( array( 'storeurl', 'customcss', 'itemformat' ) );
				$userset = get_option( 'storenvy', array() );


				echo '<div class="wrap">';
				echo '<h2>'.__( 'Storenvy' , 'storenvy' ).'</h2>';

				// printer( $userset );

				if (isset($_POST['customcss'])) {
					$css = stripslashes( $_POST['customcss'] );
					$itemformat = stripslashes( $_POST['itemformat'] );
					$opts = array( 'storeurl' => $userset['storeurl'] , 'customcss' => $css, 'itemformat' => $itemformat );
					if ( $opts != $userset ) {
						// check_admin_referer( 'storenvy-save' );
						echo update_option( 'storenvy' , $opts ) ? __( '<p>Settings saved!</p>' , 'storenvy' ) : __( '<p>Settings failed to save, please try again.</p>' , 'storenvy' );
					}
				}

				if ( isset( $_POST['clear'] ) ) {
					if ( !isset( $_POST['confirm'])) {
						echo '<p>' . __( 'You must confirm for a reset' , 'storenvy' ) . '</p>';
					}
					else if (delete_option( 'storenvy' )) {
						echo '<p>' . __( 'All settings deleted' , 'storenvy' ) . '</p>';
					}
				}

				$userset = get_option( 'storenvy', array() );
				$values = wp_parse_args( $userset , $required_keys );

				?>
			<form method="post" id="save_store">
				<?php wp_nonce_field( 'storenvy-save' ); ?>

				<h3>The Important Bit</h3>
				<p>
					<label for="storeurl">
						<?php _e( 'Store URL' , 'storenvy' ); ?>
						<input type="text" name="storeurl" id="storeurl" value="<?php echo $values['storeurl']; ?>" />
					</label><br />
					<?php _e( 'For example, <em>http://yourstore.storenvy.com</em>' , 'storenvy' ); ?>
				</p>

				<p>
					<input type="hidden" name="submitted" /><input type="submit" name="submitted" value="<?php _e( 'Save' , 'storenvy' ); ?>" class="button" />
					<strong id="store_name"><?php
						if ( false !== ( $storename = $this->_get_store_name( $values['storeurl'] ) ) ) {
							echo 'Your store is: ' . $storename;
						} else {
							_e( 'Once your store url is saved, you should see your store name here' , 'storevny' );
						}
					?></strong>

				</p>
			</form>
			<?php
			##testing##

			// $products = $this->_get_store_products( $values['storeurl'] );

			// $keys = array_keys( (array) $products[0] );
			// printer( $keys );

			// $product_ids = wp_list_pluck( $products, 'id' );
			// $products = array_combine( $product_ids, $products );
			// printer( $products );
			##/testing##
			?>
			<form method="post">

				<h3>Additional Configuration</h3>

				<p>
					<label for="itemformat"><?php _e( 'Item Format' , 'storenvy' ); ?></label><br />
					<!-- <textarea name="itemformat" id="itemformat" rows="8" cols="60"><?php echo $values['itemformat']; ?></textarea> -->
					<!-- <textarea name="itemformat" id="itemformat" rows="8" cols="60">[id], [name], [description], [short_url], [on_sale], [price], [marketplace_category]</textarea> -->
				</p>
				<?php
					$content = '[id], [name], [description], [short_url], [on_sale], [price], [marketplace_category]';
					$content = $values['itemformat'];
					wp_editor( $content, 'itemformat', array(
						'media_buttons' => false,
						'tinymce' => false,
						'textarea_rows' => 10,
						'quicktags' => array(
							// 'buttons' => 'strong,em,link,del,ul,ol,li,se_id,se_name,se_description,se_price,se_short_url,se_marketplace_category'
							'buttons' => 'strong,em,link,se_id,se_name,se_description,se_price,se_short_url,se_marketplace_category'
						)
					) );
				?>

				<p>
					<label for="customcss"><?php _e( 'Customize CSS' , 'storenvy' ); ?></label><br />
					<textarea name="customcss" id="customcss" rows="8" cols="60"><?php echo $values['customcss']; ?></textarea>
				</p>

				<?php submit_button(); ?>

			</form>

				<?php
				echo '</div>';

		} // end page()

		function get_store_name_cb() {

			$url = esc_url( $_POST['url'] );
			$name = $this->_get_store_name( $url );

			if ( $name ) {
				//update option
				$opts = get_option( 'storenvy', array() );
				$opts['storeurl'] = $url;
				update_option( 'storenvy', $opts );
				die( $name );
			} else {
				die( 'Store data not found. Is that the correct URL?' );
			}
			// die('test');
		}

		function _get_store_name( $url ) {
			$jsonurl = trailingslashit( $url ) . 'store.json';

			$body = wp_remote_retrieve_body( wp_remote_get( $jsonurl ) );
			$data = json_decode( $body );
			if ( isset( $data->name ) )
				return $data->name;
			return false;
		}

		function _get_store_products( $url ) {
			$jsonurl = trailingslashit( $url ) . 'products.json';

			$body = wp_remote_retrieve_body( wp_remote_get( $jsonurl ) );
			$data = json_decode( $body );
			return $data;
			if ( isset( $data->name ) )
				return $data->name;
			return false;
		}

		function admin_footer() {
			?><script>
	jQuery(document).ready( function($) {

		$('#save_store').submit( function(ev) {
			ev.preventDefault();

			$.post( ajaxurl, {
				'action' : 'get_store_name',
				'url' : $('#storeurl').val()
			}, function(response) {

				$('#store_name').html( response );

			}, 'text' );
		});
	});
		//'[id], [name], [description], [short_url], [on_sale], [price], [marketplace_category]'
		QTags.addButton( 'se_storenvyspacer', 'Storenvy:', '');
		QTags.addButton( 'se_id', 'ID', '[id]');
		QTags.addButton( 'se_name', 'Name', '[name]');
		QTags.addButton( 'se_description', 'Description', '[description]');
		QTags.addButton( 'se_short_url', 'URL', '[short_url]');
		// QTags.addButton( 'se_on_sale', 'ID', '[id]');
		QTags.addButton( 'se_price', 'Price', '[price]');
		QTags.addButton( 'se_marketplace_category', 'Category', '[marketplace_category]');
		QTags.addButton( 'se_facebook', 'Facebook', '[facebook]');
		QTags.addButton( 'se_twitter', 'Twitter', '[twitter]');


		QTags.addButton( 'se_sample_1', 'Sample 1', '<div style="padding:20px;margin: 20px; background:whitesmoke;"><h3><a href="[short_url]">[name]</a></h3>'+"\n"+
'<strong>$[price]</strong>'+"\n"+"\n"+
'[description]'+"\n"+
'</div>');

		QTags.addButton( 'se_sample_2', 'Sample 2', '<div style="border-top:3px solid #777;padding: 20px 0 0;"><h3><a href="[short_url]">[name]</a></h3>'+"\n"+
'<strong>$[price]</strong>'+"\n"+"\n"+
'[description]'+"\n"+"\n"+
'[gallery]'+"\n"+
'</div>');

		QTags.addButton( 'se_sample_3', 'Sample 3', '<h3><a href="[short_url]">[name]</a></h3>'+"\n"+
'<div style="float:left">[photo]</div><strong>$[price]</strong>'+"\n"+"\n"+
'[description]'+"\n"+"\n"+
'<em>In [marketplace_category]</em>');

			</script><?php
		}

		function wp_head() {

			$opts = get_option( 'storenvy' );

			echo $opts['customcss'] ? "<style type='text/css'>\n{$opts['customcss']}\n</style>\n" : '';

		} // end wp_head()

 		function help( $contextual_help, $screen_id, $screen ) {
 			global $storenvy_admin_page;
			if ( $storenvy_admin_page != $screen->id ) return $contextual_help;

			$content = '<p><code>[storenvy]</code> ' .
			__( 'Add this to any page or post to show all the details for all your products' , 'storevny' )
			. '</p>';

			$content .= '<p>The following options have not yet been restored to the plugin:</p>';

			$content .= '<p><code>limit=(<em>n</em>)</code> ' .
			__( 'Display <em>n</em> items. Default = -1 (all)' , 'storevny' )
			. '</p>';

			$content .= '<p><code>start=(<em>n</em>)</code> ' .
			__( 'Start displaying after <em>n</em> items (or, skip the first <em>n</em> items). Default = 0' , 'storevny' )
			. '</p>';

			$content .= '<p><code>[storenvy image=0 pubdate=0 limit=5]</code> ' .
			__( 'Show the first 5 items. Hide images and published dates.' , 'storevny' )
			. '</p>';

			$screen->add_help_tab( array(
				'id' => 'se_shortcode',
				'title' => 'Shortcode',
				'content' => $content,
			) );

			$content = '<p><code>' . htmlentities( '<?php storenvy(); ?>' ) . '</code> ' .
			__( 'The equivalent of <code>[storenvy]</code>.' , 'storevny' )
			. '</p>';

			$content .= '<p><code>' . htmlentities( '<?php $args = array( \'image\' => 0 , \'pubdate\' => 0 , \'limit\' => 5 ); storenvy( $args ); ?>' ) . '</code> ' .
			__( 'The equivalent of <code>[storenvy image=0 pubdate=0 limit=5]</code>.' , 'storevny' )
			. '</p>';

			$content .= '<p><code>' . htmlentities( '<?php if ( function_exists( \'storenvy\' ) ) { storenvy(); } ?>' ) . '</code> ' .
			__( 'Protect yourself! Check to make sure the function exists before using it.' , 'storevny' )
			. '</p>';
			$screen->add_help_tab( array(
				'id' => 'se_template_tag',
				'title' => 'Template Tag',
				'content' => $content,
			) );

			$content = '<form method="post"><p><label><input type="submit" class="button" name="clear" value = "' . __( 'Reset' , 'storenvy' ) . '" /> ' . __( 'Check to confirm' , 'storenvy' ) . '<input type="checkbox" name="confirm" id="confirm_delete" value="true" /></label></p></form>';
			$screen->add_help_tab( array(
				'id' => 'se_reset',
				'title' => 'Reset Options',
				'content' => $content,
			) );

		}

		function show( $atts ) {

				extract( shortcode_atts( $this->defaults , $atts ) );

				$opts = get_option( 'storenvy' );

				$items = $this->fetch();

				//id, name, description, short_url, status, labels, preorder, on_sale, price, marketplace_category,
				//photos[]->photo->(original|large|marketplace|homepage|medium|small|mktpl_tiny|mktpl_small|mktpl_big_landscape|mktpl_big_square)
				//variants[]->...
				//collections[]->...

				$the_list = '';

				if ( $items ) {

					$start = $start ? $start : '0';

					$i = '0'; $s = '0'; //$i will keep track of total items, $s counts how many are displayed
					foreach($items as $k=>$item) {
						++$i;

						if ($i < $start) continue;
						++$s;

						//shorttags: [id], [name], [description], [short_url], [on_sale], [price], [marketplace_category]
						$shortags = array( 'id', 'name', 'description', 'short_url', 'on_sale', 'price', 'marketplace_category' );
						$faux_contents = $opts['itemformat'];

						foreach( $shortags as $key ) {
							// if ( 'description' == $key ) $c = nl2br( $item->$key );
							$faux_contents = str_replace("[$key]", nl2br( $item->$key ), $faux_contents );
						}

						$others = array( 'gallery', 'photo', 'facebook', 'twitter' );
						foreach( $others as $tag ) {
							switch( $tag ) {
								case 'gallery':
								case 'photo':
									$photos = $item->photos;
									$gal = '';
									foreach( $photos as $photo ) {
										$med = $photo->photo->medium;
										$lar = $photo->photo->large;
										$gal .= "<a href='$lar'><img src='$med' alt='{$item->name} medium photo' /></a>";
										if ($tag == 'photo') break;
									}
									$faux_contents = str_replace("[$tag]", $gal, $faux_contents );
								break;
								case 'facebook' :
									$widget = '<fb:like href="' . urlencode( $item->short_url ) . '" layout="button_count" show-faces=false width="55"></fb:like>';
									$faux_contents = str_replace("[$tag]", $widget, $faux_contents, $count );
									if ( $count > 0 ) $facebookjs = true;
								break;
								case 'twitter' :
									$widget = '<a href="http://twitter.com/share" class="twitter-share-button" data-text="'. get_bloginfo('name') .' | '. esc_attr( $item->name ).'" data-url="' . $item->short_url . '" data-count="horizontal" data-via="storenvy">Tweet</a>';
									$faux_contents = str_replace("[$tag]", $widget, $faux_contents, $count );
									if ( $count > 0 ) $twitterjs = true;
								break;
							}
							// if ( 'description' == $key ) $c = nl2br( $item->$key );
						}

						// $faux_contents .= '<hr />';
						$the_list .= wpautop( $faux_contents );
						continue;

						$the_link = $link ? $item->short_url : '';	//get the link
						$tmp_title = $title ? "<h3><a href='$the_link'>{$item->name}</a></h3>" : ''; //build the title with link
						$the_title = $tmp_title != '' && !$the_link ? strip_tags( $tmp_title , '<h3>' ) : $tmp_title; //if no link, strip it out

						// $the_pubdate = $pubdate ? '<p class="se-pubdate">' . $opts['pubdate_text'] . ' ' . date( $opts['timeformat'] , strtotime( $item->pubDate ) ) . '</p>' : ''; //get pubdate with prefix
						$the_pubdate = '';

						$the_description = $description ? '<p class="se-description">' . $item->description . '</p>' : ''; //get description
						$the_description = $image ? $the_description : strip_tags( $the_description , '<p>' ); //if image is false, strip it out
						$the_description = nl2br($the_description); //add line breaks

						$fblike = $fblike ? ' ' : '';
						$tweet = $tweet ? '' : '';
						$share = $fblike != '' || $tweet != '' ? '<p class="se-social">' . $tweet . $fblike . '</p>' : '';

						$the_list .= '<div class="se-item">' . $the_title . $the_pubdate . $the_description . $share . '</div>';

						/*numpages is calculated before the shortcode is parsed, so this doesn't work*/
						//if ($s % $per_page == '0') $the_list .= '</div><!--nextpage--><div class="storenvy-wrapper">';
						if ($s == $limit) break;

					}
				}

				$js = isset( $facebookjs ) ? "<div id='fb-root'></div>
<script>
  window.fbAsyncInit = function() {
	FB.init({appId: '105417462850115', status: true, cookie: true,
			 xfbml: true});
  };
  (function() {
	var e = document.createElement('script'); e.async = true;
	e.src = document.location.protocol +
	  '//connect.facebook.net/en_US/all.js';
	document.getElementById('fb-root').appendChild(e);
  }());
</script>" : '';

				$js .= isset( $twitterjs ) ? '<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>' : '';

				return "$js<div class='se-wrapper'>{$the_list}</div>";

		} // end show()

		function fetch( $args = array() ) {

				$args = wp_parse_args( $args , $this->defaults );

				extract( $args );

				$opts = get_option( 'storenvy' );
				if ( empty( $opts['storeurl'] ) ) return;
				$url = trailingslashit( $opts['storeurl'] ) . 'products.json';

				$page = wp_remote_get( $url );
				$data = $page['body'];

				if (empty($data)) { return false; } // if nothing found in rss feed, do nothing

				$xml = json_decode( $data );
				// echo '<hr />';
				// printer( $xml );

				return $xml;

				$storeinfo = $xml->channel;
				$items = $xml->channel->item;

					 if ($type == 'items')		return $items;
				else if ($type == 'storeinfo')  return $storeinfo;
				else if ($type == 'url')  		return $url;
				else 							return $items;

		} // end fetch()

} //end class

/* the template tag function */
function storenvy( $args = array() ) {
	global $se_storenvy;
	return $se_storenvy->show( $args );
}


if ( ! function_exists( 'printer') ) {
	function printer( $input ) {
		echo '<pre>' . print_r( $input, true ) . '</pre>';
	}
}