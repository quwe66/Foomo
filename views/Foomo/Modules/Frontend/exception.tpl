<div id="appContent">
	<h2>An error occured</h2>
	<? 
		// @todo: this seems to be old code
		#switch($exception->getCode()) { 
		#	case \Foomo\Modules\ModuleManagerAppModel::ERROR_CODE_MODULE_DOES_NOT_EXIST:
				echo '<p>' . $view->escape($exception->getMessage()) . '<p>';
		#		break;
		#} 
	?>
</div>
