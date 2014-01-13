<?php
require_once realpath(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(7, new lime_output_color());

$adapter_options = sfConfig::get('app_sf_image_pool_cache');

// Test will fail if no config options
// Rackspace do not have testing credentials available for the API so this test requires Rackspace account details
if (isset($adapter_options['options']) && !empty($adapter_options['options']))
{
  // Create an image
  $filename = time().'test.png';
  
  $image = new sfImagePoolImage();
  $image->original_filename = 'test.png';
  $image->filename = $filename;
  $image->mime_type = 'image/png';
  $image->save();
  
  $t->isa_ok($image, 'sfImagePoolImage', 'Image created');
  
  $thumbnail_options = array('width'=>300,'height'=>300,'scale'=>false);
  
  // Create cache
  $cache = sfImagePoolCache::getInstance($image, $adapter_options, $thumbnail_options);
  $t->isa_ok($cache, 'sfImagePoolRackspaceCloudFilesCache', 'Cache class created');
  
  $container = $cache->getContainer();
  $t->is($container->name, $adapter_options['options']['container'], 'Container created or already exists');

  // commit original
  copy($_test_dir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'test.png', $cache->getDestination($filename));
    
  // don't redirect - but commit file
  $cache->commitOriginal($filename, false);
  
  // copy test file in place
  copy($_test_dir.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'test.png', $cache->getDestination());
  
  $url = $cache->commit(false);
  
  $imageCrop = sfImagePoolCropTable::getInstance()->findCrop($image, $thumbnail_options['width'], $thumbnail_options['height'], !$thumbnail_options['scale'], $cache::CROP_IDENTIFIER);
    
  $t->isa_ok($imageCrop, 'sfImagePoolCrop', 'Crop created');
  
  $objectName = $cache->getCloudName();
  $object = $container->DataObject($objectName);
  
  $t->isa_ok($object, 'OpenCloud\ObjectStore\DataObject', 'Image created on Rackspace cloud');

  $image->delete();
  
  $image = sfImagePoolImageTable::getInstance()->findOneByFilename($filename);
  
  $t->is($image, false, 'Image deleted from database');
  
  try 
  {
    $object2 = $container->DataObject($objectName);
    $t->fail('Object not deleted from cloud');
  }
  catch (\OpenCloud\Common\Exceptions\ObjFetchError $e)
  {
    $t->pass('Object deleted from cloud');
  }
}
else
{
  $t->fail('Please ensure you have set up your Rackspace cache options - use the rackspace:initialise task to accomplish this or see the README');
  $t->skip('Skipping tests', 6);
}
