<?php
/*
Plugin Name: Storenvy
Plugin URI: http://trepmal.com/
Description: DEV - Display your Storenvy products on your site
Author: Kailey Lampert
Version: 0.1
Author URI: http://kaileylampert.com/
*/
/*
    Copyright (C) 2010  Kailey Lampert

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

$se_storenvy = new se_storenvy();

class se_storenvy {

        function se_storenvy() {

                add_action( 'admin_menu' , array( &$this , 'menu' ) );
                add_shortcode( 'storenvy', array( &$this , 'show' ) );
                add_action( 'wp_head', array( &$this , 'customcss' ) );
				add_action( 'contextual_help', array( &$this, 'help'), 10, 3);

        }// end se_storenvy()

        function menu() {

                add_submenu_page( 'options-general.php' , 'Storenvy' , 'Storenvy' , 'administrator' , __FILE__ , array( &$this , 'page' ) );

        }// end menu()

        function customcss() {
        
        	$opts = get_option( 'storenvy' );
        	
        	echo $opts[ 'customcss' ] ? '<style type="text/css">' . "\n" . $opts[ 'customcss' ] . "\n" . '</style>' . "\n" : '';
        
        }// end customcss()

        function defaults() {

			return array( 'type' => 'items' , 'title' => 1 , 'link' => 1 , 'pubdate' => 1 , 'description' => 1 , 'image' => 1  , 'limit' => -1  , 'start' => 0 , 'fblike' => '1'  , 'tweet' => '1'  , 'per_page' => '-1' );

        }// end defaults()
        
        function show($atts) {
        
				extract(shortcode_atts( $this->defaults() , $atts));
	
	        	$opts = get_option( 'storenvy' );

				$items = $this->fetch();
				$the_list = '';

				if ($items) {

					$start = $start ? $start : '0';
					
					$i = '0'; $s = '0'; //$i will keep track of total items, $s counts how many are displayed
					foreach($items as $k=>$item) {
						++$i;
					
						if ($i < $start) continue;
						++$s;

						$the_link = $link ? $item->link : '';	//get the link
						$tmp_title = $title ? '<h3><a href="' . $the_link . '">' . $item->title . '</a></h3>' : ''; //build the title with link
						$the_title = $tmp_title != '' && !$the_link ? strip_tags( $tmp_title , '<h3>' ) : $tmp_title; //if no link, strip it out

						$the_pubdate = $pubdate ? '<p class="se-pubdate">' . $opts['pubdate_text'] . ' ' . date( $opts['timeformat'] , strtotime( $item->pubDate ) ) . '</p>' : ''; //get pubdate with prefix

						$the_description = $description ? '<p class="se-description">' . $item->description . '</p>' : ''; //get description
						$the_description = $image ? $the_description : strip_tags( $the_description , '<p>' ); //if image is false, strip it out
						$the_description = nl2br($the_description); //add line breaks

						$fblike = $fblike ? '<fb:like href="' . urlencode( $the_link ) . '" layout="button_count" show-faces=false width="55"></fb:like> ' : '';
						$tweet = $tweet ? '<a href="http://twitter.com/share" class="twitter-share-button" data-url="' . $the_link . '" data-count="horizontal" data-via="storenvy">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>' : '';
						$share = $fblike != '' || $tweet != '' ? '<p class="se-social">' . $tweet . $fblike . '</p>' : '';

						$the_list .= '<div class="se-item">' . $the_title . $the_pubdate . $the_description . $share . '</div>';

						/*numpages is calculated before the shortcode is parsed, so this doesn't work*/
						//if ($s % $per_page == '0') $the_list .= '</div><!--nextpage--><div class="storenvy-wrapper">';
						if ($s == $limit) break;

					}
				}
				
				$js = $fblike != '' ? "<div id='fb-root'></div>
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
</script>
" : '';
				
				return $js.'<div class="se-wrapper">' . $the_list . '</div>';

        }// end show()
        
        function fetch( $args = array() ) { 

				$args = wp_parse_args( $args , $this->defaults() );

        		extract( $args );

        		$opts = get_option( 'storenvy' );
        		$url = $opts[ 'storeurl' ] . '/products.rss';
				$url = str_replace('//products.rss','/products.rss',$url);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec($ch);
				curl_close($ch);
				
				if (empty($data)) { return false; } // if nothing found in rss feed, do nothing
				
				$xml = simplexml_load_string($data);
				
				$storeinfo = $xml->channel;
				$items = $xml->channel->item;

					 if ($type == 'items')		return $items;
				else if ($type == 'storeinfo')  return $storeinfo;
				else if ($type == 'url')  		return $url;
				else 							return $items;
        
        }// end fetch()
        
        function page() {
        		
				$defaultvalues = array( 'storeurl' => '' , 'customcss' => '' , 'timeformat' => 'm/d/y' , 'pubdate_text' => 'Added on: ' );
				$userset = get_option( 'storenvy' ) ? get_option( 'storenvy' ) : array();
				
				
                echo '<div class="wrap">';
                echo '<h2>'.__( 'Storenvy' , 'storenvy' ).'</h2>';

        		if (isset($_POST['submitted'])) {
        			$url = esc_url( $_POST[ 'storeurl' ] );
        			$css = stripslashes( $_POST[ 'customcss' ] );
        			$timeformat = esc_attr( $_POST[ 'timeformat' ] );
        			$pubdate_text = esc_attr( $_POST[ 'pubdate_text' ] );
        			$opts = array( 'storeurl' => $url , 'customcss' => $css , 'timeformat' => $timeformat , 'pubdate_text' => $pubdate_text );
        			if ( $opts != $userset ) {
						check_admin_referer( 'storenvy-save' );
	        			echo update_option( 'storenvy' , $opts ) ? __( '<p>Settings saved!</p>' , 'storenvy' ) : __( '<p>Settings failed to save, please try again.</p>' , 'storenvy' );
	        		}
        		}

				if ( isset( $_POST[ 'clear' ] ) ) {
					if ( !isset( $_POST['confirm'])) {
						echo '<p>' . __( 'You must confirm for a reset' , 'storenvy' ) . '</p>';
					}
					else if (delete_option( 'storenvy' )) {
						echo '<p>' . __( 'All settings deleted' , 'storenvy' ) . '</p>';
					}
				}

				$userset = get_option( 'storenvy' ) ? get_option( 'storenvy' ) : array();
				$values = wp_parse_args( $userset , $defaultvalues );

                ?>
			<form method="post">
				<?php wp_nonce_field( 'storenvy-save' ); ?>

				<h3>The Important Bit</h3>
				<p>
					<label for="storeurl">
						<?php _e( 'Store URL' , 'storenvy' ); ?>
						<input type="text" name="storeurl" id="storeurl" value="<?php echo $values[ 'storeurl' ]; ?>" />
					</label><br />
					<?php _e( 'For example, <em>http://yourstore.storenvy.com</em>' , 'storenvy' ); ?>
				</p>

				<p>
					<input type="hidden" name="submitted" /><input type="submit" name="submitted" value="<?php _e( 'Save' , 'storenvy' ); ?>" class="save" />
					<?php
	                	echo ($storeinfo = $this->fetch(array('type'=>'storeinfo'))) ? 
	                	__( 'It looks like your store name is' , 'storenvy' ) . ' <strong>'.$storeinfo->title.'</strong>' :
	                	__( 'Once your store url is saved, you should see your store name here' , 'storevny' );
					?>

				</p>

				<h3>Additional Configuration</h3>
				<p>
					<label for="timeformat">
						<?php _e( 'Published date format' , 'storenvy' ); ?>
						<input type="text" name="timeformat" id="timeformat" value="<?php echo $values[ 'timeformat' ]; ?>" />
					</label><br />
					<?php _e( 'PHP format, <a href="http://www.php.net/manual/en/function.date.php">http://www.php.net/manual/en/function.date.php</a>' , 'storenvy' ); ?>
				</p>

				<p>
					<label for="pubdate_text">
						<?php _e( 'Published date text' , 'storenvy' ); ?>
						<input type="text" name="pubdate_text" id="pubdate_text" value="<?php echo $values[ 'pubdate_text' ]; ?>" />
					</label><br />
					<?php _e( 'For example, <em>Added on: </em> 06/22/10' , 'storenvy' ); ?>
				</p>

				<p>
					<label for="customcss" style="float:left;">
						<?php _e( 'Customize CSS' , 'storenvy' ); ?><br />
						<textarea name="customcss" id="customcss" rows="8" cols="60" style="float:left;"><?php echo $values[ 'customcss' ]; ?></textarea>
					</label><pre style="margin-top:1em;float:left;padding:1px;"><?php include('starter.css'); ?></pre>
				</p>

				<p style="clear:both;">
					<input type="submit" name="submitted" value="<?php _e( 'Save' , 'storenvy' ); ?>" class="save" />
				</p>

			</form>

			<p>See the "help" area (upper-right) for more info</p>
			<p>This plugin is still in "Beta" - so if something doesn't work please let me know (trepmal (at) gmail (dot) com) before leaving a poor rating, thanks!</p>

                <?php
                echo '</div>';

        }// end page()

		function help( $contextual_help, $screen_id, $screen ) {
			if ('settings_page_storenvy/storenvy' == $screen->id ) {
				$help = '<h3>' . __( 'The Shortcode' , 'storenvy' ) . '</h3>';
                
                $help .= '<p><code>[storenvy]</code> ' . 
                __( 'Add this to any page or post to show all the details for all your products' , 'storevny' )
                . '</p>';
                
                $help .= '<h4>' . __( 'Attributes' , 'storenvy' ) . '</h4>';

                $help .= '<p><code>title=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show title, false (0) to hide title. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>link=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to add link to title, false (0) to remove link. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>pubdate=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show published date, false (0) to hide published date. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>description=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show description, false (0) to hide description. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>image=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show image, false (0) to hide image. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>limit=(<em>n</em>)</code> ' . 
                __( 'Display <em>n</em> items. Default = -1 (all)' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>start=(<em>n</em>)</code> ' . 
                __( 'Start displaying after <em>n</em> items (or, skip the first <em>n</em> items). Default = 0' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>fblike=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show Facebook Like button, false (0) to hide Facebook Like button. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>tweet=(0/<strong>1</strong>)</code> ' . 
                __( 'True (1) to show share on Twitter link, false (0) to hide share on Twitter link. Default = 1' , 'storevny' )
                . '</p>';
                
                $help .= '<h4>' . __( 'Usage' , 'storenvy' ) . '</h4>';

                $help .= '<p><code>[storenvy image=0 pubdate=0 limit=5]</code> ' . 
                __( 'Show the first 5 items. Hide images and published dates.' , 'storevny' )
                . '</p>';
                
                $help .= '<h3>' . __( 'The Template Tag' , 'storenvy' ) . '</h3>';

                $help .= '<p><code>' . htmlentities( '<?php $help .= storenvy( ); ?>' ) . '</code> ' . 
                __( 'The equivalent of <code>[storenvy]</code>.' , 'storevny' )
                . '</p>';
                
                $help .= '<p><code>' . htmlentities( '<?php $args = array( \'image\' => 0 , \'pubdate\' => 0 , \'limit\' => 5 ); $help .= storenvy( $args ); ?>' ) . '</code> ' . 
                __( 'The equivalent of <code>[storenvy image=0 pubdate=0 limit=5]</code>.' , 'storevny' )
                . '</p>';

                $help .= '<p><code>' . htmlentities( '<?php if ( function_exists( \'storenvy\' ) ) { $help .= storenvy( ); } ?>' ) . '</code> ' . 
                __( 'For safety, check to make sure the function exists before using it.' , 'storevny' )
                . '</p>';
				$help . '<form method="post"><p><input type="submit" name="clear" value = "' . __( 'Reset' , 'storenvy' ) . '" /> <label for="confirm_delete">' . __( 'Check to confirm' , 'storenvy' ) . '<input type="checkbox" name="confirm" id="confirm_delete" value="true" /></label></p></form>';
			return $help;
			}
		}

}//end class

/* the template tag function */
function storenvy( $args = array() ) {
	global $se_storenvy;
	return $se_storenvy->show( $args );
}
?>