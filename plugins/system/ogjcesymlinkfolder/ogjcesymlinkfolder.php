<?php

/**
 * @package     JCE
 * @subpackage  System.wf_filesystem_events
 *
 * @copyright   Copyright (C) 2016 Ryan Demmer. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('JPATH_BASE') or die;

/**
 * @package     JCE
 * @subpackage  System.wf_filesystem_events
 * @since       2.6
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin as JPlugin;

require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_jce' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'constants.php';

class PlgSystemOGJCESymLinkFolder extends JPlugin {
	protected $app;
	protected $_params_dirbase;
	protected $_params_dirlink;
	
	
	function __construct(&$subject, $config) {
	    parent::__construct($subject, $config);
	    $this->app=Factory::getApplication();
	    JLoader::register('WFUtility', WF_EDITOR_CLASSES . '/utility.php');
	    JLoader::register('WFFileSystem', WF_EDITOR_CLASSES . '/extensions/filesystem.php');
	    $this->_params_dirlink = $this->params->def('directory_link', '');
	    $dirsource = $this->params->def('directories_base', '');
	    $this->_params_dirbase = explode("\r\n", $dirsource);
	    
	}
	
	/**
	 * onWfFileSystemGetRootDir
	 * @param   string  $path  The relative root directory path, eg: "images".
	 * @return  void
	 */
    public function onWfFileSystemGetRootDir(&$path) {
        $filesystem = WFFileSystem::getInstance();
        foreach($this->_params_dirbase as $dirbase){
            $filesystem = WFFileSystem::getInstance();
            $filesystem->processPath($dirbase);
            if($path != $dirbase){
                continue;
            }
            $this->_traitedir($path);
            break;
        }
    }
    
    private function _traitedir($path){
        $paths  = $this->app->getUserState('ogjcesymlink',array());
        if(in_array($path,$paths)){ // déjà traité
            return;
        }
        $paths[]=$path;
        $this->app->setUserState('ogjcesymlink',$paths);
        $dir = WFUtility::makePath(JPATH_SITE, $path);
        
        $Tmp= $this->_params_dirlink;
        $position = strrpos($Tmp,DIRECTORY_SEPARATOR);
        if($position === false ){
            $position = strrpos($Tmp,'/');
        }
        if($position === false){
            $position = strrpos($Tmp,'\\');
        }
        if($position === false){
            return;
        }
        $nom = substr($Tmp,$position+1);
        if($nom == ''){
            return;
        }
        $dir = WFUtility::makePath($dir, $nom);
        // on vérifie si le lien n'existe pas déjà
        
        if(is_dir($dir) || is_file($dir)){
           return;
        }
        $lien = WFUtility::makePath(JPATH_SITE, $this->_params_dirlink);
        if(!symlink($lien,$dir)){
            $this->app->enqueueMessage(JText::_("PLG_OGJCESYMLINKFOLDER_ERROR_LINK_CREATE"),'WARNING');
        }
        $this->app->enqueueMessage(JText::sprintf("PLG_OGJCESYMLINKFOLDER_LINK_CREATED",$nom),'WARNING');
        return;
    }
}
