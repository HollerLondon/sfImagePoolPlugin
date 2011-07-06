<?php
require_once realpath(dirname(__FILE__).'/../../../../../test/bootstrap/unit.php');

//Load in project configuration and enable database
$this->configuration = ProjectConfiguration::getApplicationConfiguration('backend', 'test', true);
new sfDatabaseManager($this->configuration);

$t = new lime_test(3);

$adapter_options = sfConfig::get('app_sf_image_pool_cache');
// Make it a test container which we'll be clearing at the end
$adapter_options['adapter_options']['container'] .= ' Test';

$image = new sfImagePoolImage();
$image->original_filename = 'test.png';
$t->isa_ok($image, 'sfImagePoolImage', 'Image created');

$cacheAdapter = new sfImagePoolRackCloudCache($image, $adapter_options, array());
$t->isa_ok($cacheAdapter, 'sfImagePoolRackCloudCache', 'Cache class created');

$container = $cacheAdapter->getContainer();
$t->is($container->name, $adapter_options['adapter_options']['container'], 'Container created or already exists');

