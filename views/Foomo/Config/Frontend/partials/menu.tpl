<? /* @var $view Foomo\MVC\View */ ?>
<nav id="menuSub">
	<ul>
		<li><?= $view->partial('menuButton', array('url' => 'default', 'name' => 'Overview' ), 'Foomo\Frontend') ?></li>
		<li><?= $view->partial('menuButton', array('url' => 'newConfEditor', 'name' => 'Create a new configuration' ), 'Foomo\Frontend') ?></li>
	</ul>
</nav>
