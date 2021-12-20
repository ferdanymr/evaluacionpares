<?php 
print_collapsible_region_start('','instrucciones-envio',get_string('param_inst','mod_evaluacionpares'));
?>
<div class="row">
	<div class="col-12">
		<?php echo $moduleinstance->instruccion_envio;?>
	</div>
</div>
<?php
print_collapsible_region_end();
?>