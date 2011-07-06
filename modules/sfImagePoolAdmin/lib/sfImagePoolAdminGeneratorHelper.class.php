<?php

/**
 * sfImagePoolAdmin module helper.
 *
 * @package    symfony
 * @subpackage sfImagePoolPlugin
 * @author     Your name here
 * @version    SVN: $Id: helper.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sfImagePoolAdminGeneratorHelper extends BaseSfImagePoolAdminGeneratorHelper
{
    public function linkToEdit($object, $params)
    {
        return link_to(__($params['label'], array(), 'sf_admin'), $this->getUrlForAction('edit'), $object, array('class' => 'edit'));
    }

    public function linkToDelete($object, $params)
    {
        if($object->isNew())
        {
          return '';
        }
        
        return link_to(__($params['label'], array(), 'sf_admin'), $this->getUrlForAction('delete'), $object, array('class' => 'delete', 'method' => 'delete', 'confirm' => !empty($params['confirm']) ? __($params['confirm'], array(), 'sf_admin') : $params['confirm']));
    }
}
