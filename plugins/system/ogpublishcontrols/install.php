<?php

/**
 * @version		$Id: script.php 19 2019-04-12 19:29:57Z RoL $
 * @package		OGPublishcontrols
 * @subpackage	Component
 * @copyright	Copyright (C) 2010-today Roland Leicher. All rights reserved.
 * @author		Roland Leicher
 * @link		http://ordi-genie.com
 * @license		http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Installer\Adapter\ComponentAdapter as JInstallerComponent;
/**
 * Script file of Gskititres component
 */
class plgSystemOgpublishcontrolsInstallerScript
{
	/**
	 * method to install the component
	 *
	 * @param	JInstallerComponent	$parent	The class calling this method
	 *
	 * @return void
	 */
	function install($parent) 
	{
	    $this->PluginActivation("update", $parent);
	    return true;
	}

	/**
	 * method to update the component
	 *
	 * @param	JInstallerComponent	$parent	The class calling this method
	 *
	 * @return void
	 */
	function update($parent) 
	{
	    $this->PluginActivation("update", $parent);
	    return true;
	}

	/**
	 * method to run before an install/update/discover_install method
	 *
	 * @param	JInstallerComponent	$parent	The class calling this method
	 * @param 	string				$type  	The type of change (install, update or discover_install)
	 *
	 * @return void
	 */
	function preflight($type, $parent) 
	{
	    define('OGPC_MINIMUM_PHP', '5.6.0');
	    
	    if (version_compare(PHP_VERSION, OGPC_MINIMUM_PHP, '<'))
	    {
	        JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_OGPUBLISHCONTROLS_PHP_VERSION_WARNING', PHP_VERSION),'WARNING');
	    }
	    
	    // Joomla! broke the update call, so we have to create a workaround check.
	    $db = JFactory::getDbo();
	    $db->setQuery("SELECT extension_id, enabled FROM #__extensions WHERE element = 'ogpublishcontrols'");
		$result = $db->loadObject();
		if($result){
			$this->extension_id = $result->extension_id;
			$this->enabled = $result->enabled;
			$this->newinstall = false;
		}else{
			$this->extension_id = false;
			$this->enabled = false;
			$this->newinstall = true;
		}
	    if (!$this->enabled) {
	        if (version_compare(JVERSION, '3.9.0', '<')  && $type !="uninstall") {
	            JFactory::getApplication()->enqueueMessage('Attention! Votre version de Joomla est trop ancienne! <br/>Faite rapidement une mise à jour vers une version 3.9.0 minimum avant d installer ogpublishcontrols. Ce plugin ne pourra pas fonctionner correctement avec votre version de Joomla.','WARNING');
	            return false;
	        }
	        return;
	    } else {
	        if (version_compare(JVERSION, '3.9.0', '<')  && $type !="uninstall") {
	            JFactory::getApplication()->enqueueMessage('Cette version du plugin ogpublishcontrols nécessite une version de Joomla 3.9.0 et suivantes.<br/>Ce plugin ne pourra pas fonctionner correctement. Faite une mùise à jour de Joomla avant d\'installer cette mise à jour du plugin.','WARNING');
	            return false;
	        }
	        return;
	    }
	}

	/*
	 * enable the plugins
	 */
	
	public function PluginActivation($type, $parent)
	{
		if($this->newinstall){
		    $db = JFactory::getDbo();
			$db->setQuery("SELECT extension_id, enabled FROM #__extensions WHERE element = 'ogpublishcontrols'");
			$result = $db->loadObject();
			if($result){
				$this->extension_id = $result->extension_id;
				$this->enabled = $result->enabled;
				$this->installok = true;
			}else{
				$this->extension_id = false;
				$this->enabled = false;
				$this->installok = false;
			}

		}
	    // CSS Styling:
	    ?>
		<style type="text/css">
			.adminform tr th:first-child {display:none;}
			table.adminform tr td {padding:15px;}
			div.gski_install {background-color:#f4f4f4;border:1px solid #ccc; border-radius:5px; padding:10px;}
			.installed {clear:both;display:inline-block;}
			.installed ul { width:350px;padding-left:0px;border: 1px solid #ccc;border-radius: 5px;}
			.installed ul li:first-child {border-top-left-radius: 5px;border-top-right-radius: 5px;}
			.installed ul li:last-child {border-bottom-left-radius: 5px;border-bottom-right-radius: 5px;}
			.installed ul li {padding:8px;list-style-type:none;}
			.installed ul li:nth-child(odd) {background-color: #fff;}
			.installed ul li:nth-child(even) {background-color: #D6D6D6;}
			.proceed {display:inline-block; vertical-align:top;}
			div.proceed ul {text-align:center;list-style-type:none;}
			div.proceed ul li {padding:5px;background-color:#fff;border:1px solid #ccc;margin-bottom:10px;border-radius:5px;}
		</style>
		<?php
		// End of CSS Styling
		if (!$this->newinstall) { 
			$inst_text = JText::sprintf('PLG_OGPUBLISHCONTROLS_INST_VERSION_UPRG',$parent->manifest->version); 
		} 
		else {  
			$inst_text = JText::sprintf('PLG_OGPUBLISHCONTROLS_INST_VERSION',$parent->manifest->version);
		}

		echo "<div class='gski_install'>
				<div class='version'><h2>". $inst_text ."</h2></div>
				<div class='installed'>
					<ul>
						<li>ogpublishcontrols</li>
					</ul>
				</div>

				<div class='proceed'>
					<ul>
						<li><a href='index.php?option=com_plugins&view=plugins&filter[search]=id:".$this->extension_id ."' alt='Configuration du plugin OGPublishControls'><br/> Configuration</a><br/></li>
					</ul>
				</div>
				<div class='proceed'>".JText::sprintf('PLG_OGPUBLISHCONTROLS_INST_VERSION_INFO')."
				</div>";



	}
	
}
