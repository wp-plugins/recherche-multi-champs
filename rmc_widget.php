<?php
class rmc_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('rmc_widget', 'Recherche Multi-Champs', array('description' => 'Recherche via les champs personnalisés'));
    }
    
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
		echo $args['before_title'];
		echo apply_filters('widget_title', $instance['title']);
		echo $args['after_title'];
		?>
		<form action="" method="post">
			<input type='hidden' name='search_rmc' value='1'>
		
				<?php
				global $wpdb;
				$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
				foreach ($resultats as $cv) {
					$cle = str_replace('\\','',$cv->cle);
					$vals = array_unique($this->get_meta_values($cle));
					if (sizeof($vals) > 0){
						echo '<label for="'."rmc_".$cv->id.'">'.str_replace("_"," ",$cle).' :</label>';
						echo '<select id="'."rmc_".$cv->id.'" name="'."rmc_".$cv->id.'" style="float:right;width:120px;margin-top:0px;"><option value="(rmc_tous)">Tous</option>';
						
						if ($cv->type_champs == "NUM"){
							sort($vals, SORT_NUMERIC);
						}else{
							sort($vals);
						}
						
						foreach ( $vals as $val ){
							$selected = "";
							if ((isset($_POST["rmc_".$cv->id]))&&(str_replace('\\','',$_POST["rmc_".$cv->id])) == $val){
								$selected = "selected";
							}
							$val2 = $val;
							if ($val2 == ""){
								$val2 = "(vide)";
							}
							echo "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";
						}
						echo '</select>';
						if ($cv->type_champs == "NUM"){
							$selected_sup = "";
							$selected_inf = "";
							if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "SUP")){
								$selected_sup = "selected";
							}
							if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "INF")){
								$selected_inf = "selected";
							}
							echo '<select name="'."rmc_".$cv->id.'_compare" style="float:right;margin-top:0px;"><option value="EGA">=</option><option value="SUP" '.$selected_sup.'>>=</option><option value="INF" '.$selected_inf.'><=</option></select>';
						}
						echo '<br style="clear:both">';
					}
				}
				?>
				<br>
				<input type="submit" value="Lancer la recherche" style="float:right"/><br style="clear:both">
		
		</form>
		<?php
		echo $args['after_widget'];
    }
	

	public function form($instance)
	{
		$title = isset($instance['title']) ? $instance['title'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo  $title; ?>" />
		</p>
		<?php
	}
	
	public function get_meta_values( $key = '', $status = 'publish' ) {

		global $wpdb;
		if( empty( $key ) )
			return;

		$r = $wpdb->get_col( $wpdb->prepare( "
			SELECT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s' 
			AND p.post_status = '%s' 
			AND (p.post_type = 'post' OR p.post_type = 'page')
		", $key, $status ) );

		return $r;
	}
}
?>