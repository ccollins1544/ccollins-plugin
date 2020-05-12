<?php
/**
 * CCOLLINS Updater Dashboard
 */
$c = new cuConfig(); 
?>
<div class="wrap ccollinsupdater">
	<?php $pageTitle = "Payout Dashboard"; include('pageTitle.php'); ?>
	<div class="cu-container-fluid">
		<div class="cu-row">
			<div id="cuRightRail" class="cu-hidden-xs cu-col-sm-push-9 cu-col-sm-3" >
				<!-- <span>RIGHT BAR</span> -->
			</div>
			<div class="cu-col-xs-12 cu-col-sm-9 cu-col-sm-pull-3">
				<form id="cuConfigForm-Options" class="cu-form-horizontal">
					<h2>Basic Options</h2>
					<?php
						$options = array( //Contents should already be HTML-escaped as needed
							array(
								'id' 		=> 'debugOn',
								'label'	=> 'Debug On?',
								'type'  => 'checkbox',
							),
							array(
								'id' 		=> 'deleteTablesOnDeact',
								'label'	=> 'Delete Database Tables when plugin is deactivated.',
								'type'  => 'checkbox',
							),
							array(
								'id' 		=> 'license_username',
								'label'	=> 'Username',
								'type'  => 'text',
							),
							array(
								'id' 		=> 'license_repo',
								'label'	=> 'Repository',
								'type'  => 'text',
							),
							array(
								'id' 		=> 'license_code',
								'label'	=> 'License Key',
								'type'  => 'password',
							),
						);

					foreach ($options as $o): ?>
					<div class="cu-form-group<?php if (isset($o['hidden']) && $o['hidden']) { echo ' hidden'; } ?>">
				  	<label for="<?php echo $o['id']; ?>" class="cu-col-sm-5 cu-control-label"><?php echo $o['label']; ?></label>
						<div class="cu-col-sm-7">
							<?php if($o['type'] == "checkbox") : ?>
								<div class="cu-checkbox">
									<input type="checkbox" id="<?php echo $o['id']; ?>" class="cuConfigElem" name="<?php echo $o['id']; ?>" value="1" <?php $c->cb($o['id']); ?> >
								</div>
						  <?php elseif($o['type'] == "text") : ?>
								<div class="cu-text">
									<input type="text" id="<?php echo $o['id']; ?>" class="cuConfigElem" name="<?php echo $o['id']; ?>" value="<?php echo $c->get($o['id']); ?>" >
								</div>
							<?php elseif($o['type'] == "password") : ?>
								<div class="cu-text">
									<input type="password" id="<?php echo $o['id']; ?>" class="cuConfigElem" name="<?php echo $o['id']; ?>" value="<?php echo $c->get($o['id']); ?>" >
								</div>
						  <?php elseif($o['type'] == "serialized") : ?>
								<div class="cu-text">
								  <textarea id="<?php echo $o['id']; ?>" class="cu-form-control" rows="7" name="<?php echo $o['id']; ?>"><?php echo $c->getSerializedString( $o['id'] ); ?></textarea>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>

					<div class="cu-form-group">
						<div class="cu-col-sm-7 cu-col-sm-offset-5">
					  	<a class="cu-btn cu-btn-primary cu-btn-callout" href="#" onclick="CUA.savePartialConfig('#cuConfigForm-Options'); return false;">Save Options</a>
					  	<div class="cuAjax24"></div>
					  	<span class="cuSavedMsg">&nbsp;Your changes have been saved!</span>
						</div>
					</div>

			  </form> <!-- #cuConfigForm-Options -->
			</div><!-- .cu-col-xs-12 -->
		</div><!-- .cu-row -->
	</div><!-- .cu-container-fluid -->
</div><!-- .ccollinsupdater -->
