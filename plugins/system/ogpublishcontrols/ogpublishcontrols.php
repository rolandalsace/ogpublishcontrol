<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.og_controls
 *
 * @Copyright	2017 - today Roland Leicher. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories as JCategories;
use Joomla\CMS\Plugin\CMSPlugin as JPlugin;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Form\Form as JForm;

/**
 *
 */
class PlgSystemOGPublishControls extends JPlugin
{

	protected $autoloadLanguage = true;
	protected $_com_content_create_categories = true;
	protected $_com_content_publish_in_nodes = true;
	protected $app;
	
	function __construct(&$subject, $config) {
		if (file_exists(__DIR__ . '/defines.php'))
		{
			include_once __DIR__ . '/defines.php';
		}
		
		$this->app = JFactory::getApplication();
		parent::__construct($subject, $config);
		
		$this->_com_content_publish_in_nodes = true;
		$this->_com_content_create_categories = true;
		$ugroups = JFactory::getUser()->getAuthorisedGroups();
		if ($this->params->def('check_category_last_son', 0)){
		    $authgroups = $this->params->def('usergroups_publish_in_categories_nodes_from_com_content', array());
		    $this->_com_content_publish_in_nodes = false;
		    if(count(array_intersect($ugroups, $authgroups)) > 0){
		        $this->_com_content_publish_in_nodes = true;
		    }
		}
		if ($this->params->def('check_com_content_create_categories', 0)){
		    $authgroups = $this->params->def('usergroups_create_categories_from_com_content', array());
		    $this->_com_content_create_categories = false;
		    if(count(array_intersect($ugroups, $authgroups)) > 0){
		        $this->_com_content_create_categories = true;
		    }
		    
		}
		
		// define language
		$this->loadLanguage();
		
	}
	
	public function onContentBeforeSave($context, $infos, $isNew)
	{
	    if(($context != 'com_content.article' && $context != 'com_content.form' && $context != 'com_categories.category')){
			return true;
		}
		if($context == 'com_categories.category'){
		    if(!$infos->id && !$this->_com_content_create_categories){ // creation verfier si droits
		        $infos->setError(JText::_('PLG_OGPUBLISHCONTROLS_CATEGORY_NOT_CREATE_AUTORISED'));
		        $this->app->enqueueMessage(JText::_('PLG_OGPUBLISHCONTROLS_CATEGORY_NOT_CREATE_AUTORISED'),'ERROR');
		        return false;
		    }
		    return true;
		}
		$tabErreurs = array();
		if( ! property_exists($infos, 'catid') || ! $infos->catid ){ // la catégorie n'est pas définie, peut arriver si l'on refuse la création de la catégorie
            $tabErreurs[] = JText::_('PLG_OGPUBLISHCONTROLS_SELECT_CATEGORY_IN_LIST');
		}
		if (!$this->_com_content_publish_in_nodes){
			$model_categories = JCategories::getInstance('Content');
			$root = $model_categories->get($infos->catid);
			$categories = $root->getChildren();
			if(count($categories)>1){
				$tabErreurs[] = JText::_('PLG_OGPUBLISHCONTROLS_CATEGORY_NOT_LAST_SON');
			}
		}
		
		if ($this->params->def('check_lg_title_and_text', 0)){
		    $lg = strlen(trim(strip_tags($infos->introtext))) + strlen(trim(strip_tags($infos->fulltext)));
    		if(strlen($infos->title) > $lg){
    		    $tabErreurs[] = JText::_('PLG_OGPUBLISHCONTROLS_CONTENT_NO_ENOUGH_TEXT');
    		}
		}
		if(count($tabErreurs) > 0){
		    $msg = '<ul>';
		    foreach ($tabErreurs as $erreur) {
		        $msg .= "<li>$erreur</li>";
		    }
		    $msg .= '</ul>';
		    $infos->setError($msg);
		    //$this->app->enqueueMessage($msg,'ERROR');
		    return false;
		}
		return true;
	}

	public function onAfterInitialise()
	{
	   
	    if($this->app->isClient('administrator')){
	        // si l'url est du type 
	        $input = JFactory::getApplication()->input;
	        $option = $input->getCmd('option');
	        $extension = $input->getCmd('extension');
	        if($option == 'com_categories' && $extension == 'com_content' && !$this->_com_content_create_categories){ // creation verfier si droits
	            $this->app->enqueueMessage(JText::_('PLG_OGPUBLISHCONTROLS_ACCES_CONTENT_ADMIN_CATEGORIES_UNAUTHORISED'),'ERROR');
	            $referer = '';
	            if(array_key_exists('HTTP_REFERER', $_SERVER)){
	               $referer = $_SERVER['HTTP_REFERER'];
	               $i1 = strpos($referer,'categor');
	               if($i1 !== false){
	                   $referer = ''; // si l'on vient d'une page catégorie pour eviter la boucle
	               }
	               //$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
	               
	            }
	            if($referer == ''){
	               $referer = "/administrator/index.php";
	            }
	            $this->app->redirect(JRoute::_($referer));
	            /*
	            $controller = JControllerLegacy::getInstance("com_content");
	            $controller->setRedirect($url,$msg,$type);
	            $controller->redirect();
	            */
	            return false;
	        }
	        return true;
	    }
	    return true;
	}
	/**
	 * After Route Event.
	 * Verify if current page is not excluded from cache.
	 *
	 * @return   void
	 *
	 * @since   3.9.0
	 */
	public function onAfterRoute()
	{
	    if ($this->app->isClient('administrator') || $this->app->get('offline', '0') || count($this->app->getMessageQueue()))
	    {
	        return;
	    }
	    
	   return;
	}
	
	public function onContentPrepareForm(JForm $form, $data)
	{
	    
	    $context = $form->getName();
	    
	    if($this->app->isClient('site') && $context == 'com_content.article' ){
	        // changer l'attribut du champ 'catid' allowAdd = false 
	        $form->setFieldAttribute('catid','allowAdd','false');
	        // ne fonctionne pas en 3.9.5 à cause d'un bug dans /administrator/component/com_categories/models/fields/categoryedit.php
	        // qui teste un simpleXMLElement comme un booléen
	        // je l'ai signalé dans le tracker voir https://issues.joomla.org/tracker/joomla-cms/24566
	        // il est donc possible de créer une catégorie dans le formulaire de creation des articles en front, 
	        // mais la création sera refusée avant création de la catégorie suite au controle en onContentBeforeSave
	    }
	    return true;
	}

}
