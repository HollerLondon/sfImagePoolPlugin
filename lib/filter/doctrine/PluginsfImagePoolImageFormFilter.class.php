<?php

/**
 * PluginsfImagePoolImage form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage filter
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormFilterPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginsfImagePoolImageFormFilter extends BasesfImagePoolImageFormFilter
{
  public function setup()
  {
    parent::setup();
    
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
  
  public function addTagColumnQuery(Doctrine_Query $q, $field, $value)
  {
    $a = $q->getRootAlias();
    $q->andWhere('id IN(SELECT taggable_id FROM tagging t WHERE t.taggable_model = "sfImagePoolImage" AND t.tag_id IN (?))',implode("', '",$value));
  }
}
