<div class="cu-row">
	<div class="cu-col-xs-12">
		<div class="cu-page-title">
			<div class="ccollinsupdater-lock-icon ccollinsupdater-icon32"></div><h2 id="cuHeading"><?php echo $pageTitle; ?></h2>
		</div>
		<?php 
			if(!empty(get_option('ccollinsupdater_version',false))){
		    echo "<h4 style='margin: 0px;'>Version: <strong>".get_option('ccollinsupdater_version',false)."</strong></h4>";
		    
		    if(get_option('ccollinsupdaterActivated',false) != 1){
		    	" <strong>ccollinsupdater Not Activated!</strong>";
		    }
		  }

		  if(!empty(get_option('ccollinsupdater_plugin_act_error',false))){
		    echo "<strong>ccollinsupdater Activation Error: ".get_option('ccollinsupdater_plugin_act_error',false)."</strong>";
			}
		?>
	</div>
	<div class="wp-header-end"></div>
</div>