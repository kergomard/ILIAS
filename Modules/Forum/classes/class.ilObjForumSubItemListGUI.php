<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once './Services/Object/classes/class.ilSubItemListGUI.php';

/** 
* Show forum threads
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
*
* @ingroup ModulesForum
*/
class ilObjForumSubItemListGUI extends ilSubItemListGUI
{
	/**
	 * get html 
	 * @return
	 */
	public function getHTML()
	{
		global $lng;
		
		foreach($this->getSubItemIds(true) as $sub_item)
		{
			if(is_object($this->getHighlighter()) and strlen($this->getHighlighter()->getContent($this->getObjId(),$sub_item)))
			{
				$this->tpl->setCurrentBlock('sea_fragment');
				$this->tpl->setVariable('TXT_FRAGMENT',$this->getHighlighter()->getContent($this->getObjId(),$sub_item));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock('subitem');
			$this->tpl->setVariable('SUBITEM_TYPE',$lng->txt('thread'));
			$this->tpl->setVariable('SEPERATOR',':');
			
			$this->getItemListGUI()->setChildId($sub_item);
			$this->tpl->setVariable('LINK',$this->getItemListGUI()->getCommandLink('thread'));
			$this->tpl->setVariable('TARGET',$this->getItemListGUI()->getCommandFrame(''));
			include_once './Modules/Forum/classes/class.ilObjForum.php';
			$this->tpl->setVariable('TITLE',ilObjForum::_lookupThreadSubject($sub_item));			
			$this->tpl->parseCurrentBlock();
		}
		
		$this->showDetailsLink();
		
		return $this->tpl->get();	 
	}
}
?>
