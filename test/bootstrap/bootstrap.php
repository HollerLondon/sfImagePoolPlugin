<?php

include dirname(__FILE__).'/sfPluginTestBootstrap.class.php';

//$symfony_dir = dirname(__FILE__).'/../../../../lib/vendor/symfony/lib';
$symfony_dir = '/mnt/sites/redbullreporter2010/phase2/lib/vendor/symfony/lib';

$bootstrap = new sfPluginTestBootstrap($symfony_dir);
$bootstrap->bootstrap();

$configuration = $bootstrap->getConfiguration();
$context = $bootstrap->getContext();