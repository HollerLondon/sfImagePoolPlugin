<?php

/**
 * PluginsfImagePoolImage form.
 *
 * @package    sfImagePoolPlugin
 * @subpackage filter
 * @author     Ben Lancaster
 * @version    SVN: $Id: PluginsfImagePoolImage.php 23810 2009-11-12 11:07:44Z Ben Lancaster $
 */
abstract class PluginsfImagePoolImageFormFilter extends BasesfImagePoolImageFormFilter
{
  public function setup()
  {
    parent::setup();
    
    $image = new sfImagePoolImage();
    
    if ($image->option('tagging'))
    {
      $query = Doctrine_Core::getTable('Tag')
                 ->createQuery('t')
                 ->where(
                   'id IN(SELECT tag_id FROM tagging WHERE taggable_model = "sfImagePoolImage")'
                 );
      
      $this->widgetSchema['tag'] = new sfWidgetFormDoctrineChoice(array(
        'model'         => 'tag',
        'multiple'      => true,
        'expanded'      => true,
        'query'         => $query,
      ));
      
      $this->validatorSchema['tag'] = new sfValidatorDoctrineChoice(array(
        'model'         => 'tag',
        'multiple'      => true,
        'required'      => false
      ));
    }
    
    $image->free(true);
  }
  
  public function addTagColumnQuery(Doctrine_Query $q, $field, $value)
  {
    $a = $q->getRootAlias();
    $q->andWhere('id IN(SELECT taggable_id FROM tagging t WHERE t.taggable_model = "sfImagePoolImage" AND t.tag_id IN (?))',implode("', '",$value));
  }
}
