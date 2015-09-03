<?php
/*
Plugin Name: Recherche Multi-Champs
Plugin URI: http://onliste.com/news/plugin-wordpress-recherche-multi-champs/
Description: Créer vos propres champs pour vos articles/pages et proposer une recherche basée sur ces champs à vos visiteurs.
Version: 0.3
Author: Stéphane Lion
Author URI: http://onliste.com/news/presentation-du-webmaster/
*/

include_once plugin_dir_path( __FILE__ ).'/rmc_widget.php';

function rmc_install(){
    global $wpdb;
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rmc_champs (id INT AUTO_INCREMENT PRIMARY KEY, cle VARCHAR(%d) NOT NULL, valeur TEXT, type_champs VARCHAR(3));", "255"));
	//var_dump($wpdb->show_errors()) ; exit( 0 ) ;
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rmc_options (id INT AUTO_INCREMENT PRIMARY KEY, cle VARCHAR(%d) NOT NULL, valeur TEXT);", "255"));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_articles', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_pages', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_vide', ''));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'couleur_bordure', '#DDDDDD'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'epaisseur_bordure', '1'));
}

function rmc_uninstall(){
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}rmc_options; WHERE %s=%s", "1", "1"));
}

function rmc_delete_fields(){
	global $wpdb;
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}rmc_champs WHERE %s=%s;", "1", "1"));
}

function rmc_register_rmc_widget(){
	register_widget('rmc_widget');
}

function rmc_add_admin_menu(){
    $hook = add_menu_page('Recherche Multi-Champs', 'Recherche Multi-Champs', 'manage_options', 'recherche-multi-champs', 'rmc_menu_html');
	add_action('load-'.$hook, 'rmc_process_action');
	
}

function rmc_process_action(){
    if (isset($_POST['ajouter_champs'])) {
		global $wpdb;
		$nouveau_champs = sanitize_text_field($_POST['nouveau_champs']);
        $nouveau_champs = str_replace(" ","_", $nouveau_champs);
        $type_champs = sanitize_text_field($_POST['type_champs']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs WHERE cle = %s", $nouveau_champs));
        if (is_null($row)) {
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_champs (cle, type_champs) VALUES (%s, %s)", ucfirst($nouveau_champs), $type_champs));
        }
    }
	if ((isset($_POST['supprimer_champs'])) && (isset($_POST['champs']))) {
		global $wpdb;
        $champs = $_POST['champs'];
		if (is_array($champs)){
			$inQuery = implode(',', array_fill(0, count($champs), '%d'));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}rmc_champs WHERE id IN ($inQuery)", $champs));
		}
    }
	if (isset($_POST['enregistrer_options'])) {
		global $wpdb;
        if (isset($_POST['afficher_champs_articles'])){$afficher_champs_articles = substr(sanitize_text_field($_POST['afficher_champs_articles'][0]),0,3);}else{$afficher_champs_articles = "";}
        if (isset($_POST['afficher_champs_pages'])){$afficher_champs_pages = substr(sanitize_text_field($_POST['afficher_champs_pages'][0]),0,3);}else{$afficher_champs_pages = "";}
        if (isset($_POST['afficher_champs_vide'])){$afficher_champs_vide = substr(sanitize_text_field($_POST['afficher_champs_vide'][0]),0,3);}else{$afficher_champs_vide = "";}
        $couleur_bordure = substr(sanitize_text_field($_POST['couleur_bordure']),0,7);
        $epaisseur_bordure = intval(sanitize_text_field($_POST['epaisseur_bordure']));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_articles'", $afficher_champs_articles));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_pages'", $afficher_champs_pages));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_vide'", $afficher_champs_vide));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'couleur_bordure'", $couleur_bordure));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'epaisseur_bordure'", $epaisseur_bordure));
    }
}

function rmc_menu_html(){
	echo "<br><br><i><font color=red>IMPORTANT : Pour visualiser les champs lors de la création de vos articles ou pages, pensez à afficher les \"champs personnalisés\" dans \"Options de l'écran\" (En haut de la page lorsque vous rédigez votre article ou votre page)</font></i>";
    echo "<br><br><b>Pour bien démarrer :</b><br> - Créez les champs sur cette page<br> - Activez le widget<br> - Remplissez les champs dans vos articles / pages<br> - Insérez le shortcode [rmc_shortcode] à l'endroit où vous voulez afficher les champs<br> - Insérez le shortcode [rmc_search_shortcode] à l'endroit où vous voulez inclure le formulaire de recherche";
	echo '<h1>'.get_admin_page_title().'</h1>';
    echo '<hr><p><b>Liste des champs: </b><form method="post" action=""><input type="hidden" name="supprimer_champs" value="1"/>';
	global $wpdb;
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle"));
	foreach ($resultats as $cv) {
		echo "<div style='display:inline-block;border:rgb(218,218,218) 1px solid;padding:5px;margin:5px'><input type='checkbox' title='Supprimer' name='champs[]' value='".$cv->id."'> ".str_replace('\\','',str_replace("_", " ", $cv->cle))." (".substr($cv->type_champs,0,1).")</div>" ;
	}
	echo '</p>';
	submit_button("Supprimer le(s) champs sélectionné(s)");
	echo '</form>';
	?>
    <hr><form method="post" action="">
		<input type="hidden" name="ajouter_champs" value="1"/>
		<table>
		<tr><td><label><b>Créer un champs: </b></label></td>
		<td><input type="text" name="nouveau_champs" value=""/></td></tr>
		<tr><td><label><b>Type: </b></label></td>
		<td><select name="type_champs"><option value='TEX'>Texte</option><option value='NUM'>Nombre</option></select></td></tr>
		</table>
		<br><b>Note:</b> Choisir le type "Nombre" pour permettre à vos visiteurs de faire une recherche >=, <= ou = à un nombre. <br>
		<i>Par exemple: Pour afficher tout les articles dont le champs 'prix' est '<=' à la valeur '50€'.</i>
		<?php submit_button("Ajouter"); ?>
    </form><hr>
	<?php
		$options = array();
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv) {
			$options[$cv->cle] = $cv->valeur;
		}
	?>
	<b>Options: </b><br><br>
	<form method="post" action="">
		<input type="hidden" name="enregistrer_options" value="1"/>
		<table>
		<tr><td><input type='checkbox' name='afficher_champs_articles[]' <?php if ($options['afficher_champs_articles']){echo "checked";} ?>> Afficher les champs dans les articles </td><td> </td></tr>
		<tr><td><input type='checkbox' name='afficher_champs_pages[]' <?php if ($options['afficher_champs_pages']){echo "checked";} ?>> Afficher les champs dans les pages </td><td> </td></tr>
		<tr><td><input type='checkbox' name='afficher_champs_vide[]' <?php if ($options['afficher_champs_vide']){echo "checked";} ?>> Afficher les champs vides </td><td> </td></tr>
		<tr><td>Couleur des bordures </td><td> <input type='text' name='couleur_bordure' value='<?php echo $options['couleur_bordure'] ?>'></td></tr>
		<tr><td>Epaisseur des bordures </td><td> <input type='text' name='epaisseur_bordure' value='<?php echo $options['epaisseur_bordure'] ?>'></td></tr>
		</table>
		<?php submit_button("Enregistrer"); ?>
    <hr></form>
    <?php
}


function rmc_insert_post($post_id) {
  if ((get_post_type($post_id) == 'post') || (get_post_type($post_id) == 'page')) {
	global $wpdb;
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
	foreach ($resultats as $cv) {
		add_post_meta($post_id, $cv->cle, '', true);
	}
  }
  return true;
}

function rmc_insert_post2(){
	rmc_insert_post(get_the_ID());
}


function rmc_shortcode($atts){
	$post_type = get_post_type();
	global $wpdb;
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options WHERE cle = %s", 'afficher_champs_articles'));
	$afficher_champs_articles = $row->valeur;
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options WHERE cle = %s", 'afficher_champs_pages'));
	$afficher_champs_pages = $row->valeur;
	$result = "";
	if ((($post_type == "post") && ($afficher_champs_articles == "on")) || (($post_type == "page") && ($afficher_champs_pages == "on"))){
		$custom_fields = get_post_custom();
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv){
			if ( isset($custom_fields[$cv->cle][0]) ) {
				$result .= '<div><b>'.str_replace("_", " ", $cv->cle).':</b> '.$custom_fields[$cv->cle][0].'</div>';
			}
		}
	}
	return $result;
}
function rmc_search_shortcode($atts){		?>		<form action="" method="post">			<input type='hidden' name='search_rmc' value='1'>						<?php				global $wpdb;				$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;				foreach ($resultats as $cv) {					$cle = str_replace('\\','',$cv->cle);					$vals = array_unique(get_meta_values($cle));					if (sizeof($vals) > 0){						echo '<label for="'."rmc_".$cv->id.'">'.str_replace("_"," ",$cle).' :</label>';						echo '<select id="'."rmc_".$cv->id.'" name="'."rmc_".$cv->id.'" style="float:right;width:120px;margin-top:0px;"><option value="(rmc_tous)">Tous</option>';												if ($cv->type_champs == "NUM"){							sort($vals, SORT_NUMERIC);						}else{							sort($vals);						}												foreach ( $vals as $val ){							$selected = "";							if ((isset($_POST["rmc_".$cv->id]))&&(str_replace('\\','',$_POST["rmc_".$cv->id])) == $val){								$selected = "selected";							}							$val2 = $val;							if ($val2 == ""){								$val2 = "(vide)";							}							echo "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";						}						echo '</select>';						if ($cv->type_champs == "NUM"){							$selected_sup = "";							$selected_inf = "";							if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "SUP")){								$selected_sup = "selected";							}							if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "INF")){								$selected_inf = "selected";							}							echo '<select name="'."rmc_".$cv->id.'_compare" style="float:right;margin-top:0px;"><option value="EGA">=</option><option value="SUP" '.$selected_sup.'>>=</option><option value="INF" '.$selected_inf.'><=</option></select>';						}						echo '<br style="clear:both">';					}				}				?>				<br>				<input type="submit" value="Lancer la recherche" style="float:right"/><br style="clear:both">				</form>	<?php	}
function rmc_results_before_content( $content ) {
	$custom_content = "";
	if (isset($_POST['search_rmc'])){
		
			$pageaff = 1; if ((isset($_POST['pageaff']))&&($_POST['pageaff'] != "")){$pageaff = intval(sanitize_text_field($_POST['pageaff']));}
			
			$posts_per_page = get_option('posts_per_page');
			
			$args = array('orderby' => 'post_type',
				'order' => 'ASC',
				'posts_per_page' => $posts_per_page,
				'offset' => ($pageaff-1) * $posts_per_page,
				'meta_query' => array(
					'relation'		=> 'AND'
				),
				'post_type' => array( 'post', 'page' )
			);
			
			global $wpdb;
			$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
			$debug = "";
			foreach ($resultats as $cv) {
				if (isset($_POST["rmc_".$cv->id])){
					$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
					if ($rmc_cv_id != ""){
						if ($rmc_cv_id != "(rmc_tous)"){
							$type = "CHAR";
							if ($cv->type_champs == "NUM"){
								$type = "NUMERIC";
							}
							$compare = '=';
							if (isset($_POST["rmc_".$cv->id."_compare"])){
								$rmc_cv_id_compare = sanitize_text_field($_POST["rmc_".$cv->id."_compare"]);
								if ($rmc_cv_id_compare == "SUP"){
									$compare = ">=";
								}
								if ($rmc_cv_id_compare == "INF"){
									$compare = "<=";
								}
							}
							$args["meta_query"][] = array('key' => str_replace('\\','',$cv->cle),'value' => str_replace('\\','',$rmc_cv_id),'compare' => $compare, 'type' => $type);
						}
					}
				}
			}	
			$the_query = new WP_Query( $args );
			query_posts( '$args' );
			
			
			$result = 0;
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options WHERE cle = %s", 'couleur_bordure'));
			$couleur_bordure = $row->valeur;
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options WHERE cle = %s", 'epaisseur_bordure'));
			$epaisseur_bordure = $row->valeur;
			$list = $debug.'<div style="margin: 20px 0 20px 0;border: '.$epaisseur_bordure.'px solid '.$couleur_bordure.';padding:10px"><div class="content-headline"><h1 class="entry-headline"><span class="entry-headline-text">Résultat de la recherche</span></h1></div>';
			while ( $the_query->have_posts() ) : $the_query->the_post();
				$result++;
				$custom_fields = get_post_custom();
				$new_content = strip_tags(strip_shortcodes(get_the_content()));
				$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id($the_query->post->ID), 'medium');
				$list .= '	<hr style="width:100%;border: '.$epaisseur_bordure.'px solid '.$couleur_bordure.';clear:both">
							<article class="grid-entry" style="float: left;margin: 0 20px 20px 0;width: 100%;">
								<a style="float:left;margin-right:10px;" href="' . get_permalink() . '"><img src="'.$feat_image[0].'"></a>
								<span>
									<a href="'. get_permalink() . '"><b>' . ucfirst(get_the_title()) . '</b></a> - 
									<span>'.substr($new_content,0,400).'...</span>
								</span>
							</article>';
			endwhile;
			if ($result == 0){
				$list .= "Aucun résultat pour cette recherche, merci de réessayer avec moins de critères.";
			}
			$list .= "<br style='clear:both;'></div>";
			if ($pageaff > 1){
				$list .= "<div style='float:left'><form action='' method='post'>";
				$list .= "<input type='hidden' name='search_rmc' value='1'><input type='hidden' name='pageaff' value='".($pageaff - 1)."'>";
				foreach ($resultats as $cv) {
					if (isset($_POST["rmc_".$cv->id])){
						$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
						if ($rmc_cv_id != ""){
							if ($rmc_cv_id != "(rmc_tous)"){
								$list .= "<input type='hidden' name='"."rmc_".$cv->id."' value='".esc_attr($rmc_cv_id)."'>";
							}
						}
					}
				}
				$list .= "<input type='submit' value='Page précédente'></form></div>";
			}
			if ($result == $posts_per_page){
				$list .= "<div style='float:right'><form action='' method='post'>";
				$list .= "<input type='hidden' name='search_rmc' value='1'><input type='hidden' name='pageaff' value='".($pageaff + 1)."'>";
				foreach ($resultats as $cv) {
					if (isset($_POST["rmc_".$cv->id])){
						$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
						if ($rmc_cv_id != ""){
							if ($rmc_cv_id != "(rmc_tous)"){
								$list .= "<input type='hidden' name='"."rmc_".$cv->id."' value='".esc_attr($rmc_cv_id)."'>";
							}
						}
					}
				}
				$list .= "<input type='submit' value='Page suivante'></form></div>";
			}
			$list .= "<br style='clear:both;'>";
			$custom_content .= $list;
			wp_reset_query();

		return $custom_content;
	}
    return $content;
}
function get_meta_values( $key = '', $status = 'publish' ) {	global $wpdb;	if( empty( $key ) )		return;	$r = $wpdb->get_col( $wpdb->prepare( "		SELECT pm.meta_value FROM {$wpdb->postmeta} pm		LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id		WHERE pm.meta_key = '%s' 		AND p.post_status = '%s' 		AND (p.post_type = 'post' OR p.post_type = 'page')	", $key, $status ) );	return $r;}
add_filter( 'the_content', 'rmc_results_before_content' );
add_action('wp_insert_post', 'rmc_insert_post');
add_action('edit_form_after_editor', 'rmc_insert_post2');
register_activation_hook(__FILE__, 'rmc_install');
register_deactivation_hook(__FILE__, 'rmc_uninstall');
register_uninstall_hook(__FILE__, 'rmc_delete_fields');
add_action('widgets_init', 'rmc_register_rmc_widget');
add_action('admin_menu', 'rmc_add_admin_menu');
add_shortcode('rmc_shortcode', 'rmc_shortcode');add_shortcode('rmc_search_shortcode', 'rmc_search_shortcode');
?>