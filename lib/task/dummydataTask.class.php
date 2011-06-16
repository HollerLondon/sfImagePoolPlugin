<?php
class dummydataTask extends sfBaseTask
{
  /**
   * 
   */
  private $model_ids = array();
  
  /**
   * 
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('model', sfCommandArgument::REQUIRED, 'Model type to associate dummy images with'),
      new sfCommandArgument('num_rows', sfCommandArgument::REQUIRED, 'Number of dummy images to generate'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'imagepool';
    $this->name             = 'dummy-data';
    $this->briefDescription = 'Generate some dummy image and image-lookup data';
  }

  /**
   * 
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->arguments = $arguments;
    
    if(!class_exists($arguments['model']))
    {
      throw new Exception(sprintf('Class "%s" does not exist', $arguments['model']));
    }
    
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
    
    $this->getRandomModelId($arguments['model']);
    $this->generateImages($arguments['num_rows']);
    $this->randomlyAssign();
  }

  /**
   * Return a random id from
   */
  public function getRandomModelId($class_name)
  {
    if(!$this->model_ids)
    {
      $this->model_ids = Doctrine_Core::getTable($class_name)->createQuery()
        ->select('id')
        ->execute(null, Doctrine_Core::HYDRATE_SINGLE_SCALAR);
    }
    
    $id = $this->model_ids[array_rand($this->model_ids)];
    
    if(!$id)
    {
      throw new Exception(sprintf('You need some %s data in your database first', $this->arguments['model']));
    }   
    
    return $id;
  }
  
  /**
   * 
   */
  public function generateImages($nb)
  {
    for($i = 0; $i < $nb; $i++)
    {
      $data = array(
        'original_filename' => mt_rand() . '.jpg',
        'filename'          => md5(microtime()) . '.jpg',
        'is_published'      => rand(0, 1),
        'mime_type'         => 'image/jpg', 
      );
      
      $image = new sfImagePoolImage();
      $image->fromArray($data);
      $image->save();
      
      unset($image, $data);
    }
  }
}
