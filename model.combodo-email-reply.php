<?php
// Copyright (C) 2012 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


/**
 * Module combodo-email-reply
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */
/**
 * To trigger notifications when a ticket is updated from the portal
 */
class TriggerOnLogUpdate extends TriggerOnObject
{
	public static function Init()
	{
		$aParams = array
		(
			"category" => "core/cmdb",
			"key_type" => "autoincrement",
			"name_attcode" => "description",
			"state_attcode" => "",
			"reconc_keys" => array('description'),
			"db_table" => "priv_trigger_onlogupdate",
			"db_key_field" => "id",
			"db_finalclass_field" => "",
			"display_template" => "",
		);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();

		// Display lists
		MetaModel::Init_SetZListItems('details', array('description', 'target_class', 'action_list')); // Attributes to be displayed for the complete details
		MetaModel::Init_SetZListItems('list', array('finalclass', 'target_class', 'description')); // Attributes to be displayed for a list
		// Search criteria
	}
}
// Add class definitions here

// Add menus creation here

// Declare a class that implements iBackgroundProcess (will be called by the CRON)
// Extend the class AsyncTask to create a queue of asynchronous tasks (process by the CRON)
// Declare a class that implements iApplicationUIExtension (to tune object display and edition form)
// Declare a class that implements iApplicationObjectExtension (to tune object read/write rules)

class CombodoEmailReplyPlugIn implements iApplicationUIExtension, iApplicationObjectExtension
{
	public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
	{
		if ($bEditMode && self::IsTargetObject($oObject) && !$oObject->IsNew())
		{
			$sAttCode = MetaModel::GetModuleSetting('combodo-email-reply', 'target_caselog', 'ticket_log');
			$oPage->add_ready_script("$('#field_2_$sAttCode div.caselog_input_header').append('<input id=\"email_reply_trigger\" type=\"checkbox\" checked name=\"email_reply_trigger\" value=\"yes\">&nbsp;<img src=\"../images/mail.png\">');");
		}
	}

	public function OnDisplayRelations($oObject, WebPage $oPage, $bEditMode = false)
	{
	}

	public function OnFormSubmit($oObject, $sFormPrefix = '')
	{
		if (self::IsTargetObject($oObject))
		{
			$sAttCode = MetaModel::GetModuleSetting('combodo-email-reply', 'target_caselog', 'ticket_log');
			$sOperation = utils::ReadPostedParam('email_reply_trigger', null);
			if ($sOperation == 'yes')
			{
				// Trigger ?
				//
				$aClasses = MetaModel::EnumParentClasses(get_class($oObject), ENUM_PARENT_CLASSES_ALL);
				$sClassList = implode(", ", CMDBSource::Quote($aClasses));
				$oSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT TriggerOnLogUpdate AS t WHERE t.target_class IN ($sClassList)"));
				while ($oTrigger = $oSet->Fetch())
				{
					$oTrigger->DoActivate($oObject->ToArgs('this'));
				}
			}
		}
	}
	
	public function OnFormCancel($sTempId)
	{
	}

	public function EnumUsedAttributes($oObject)
	{
		return array();
	}

	public function GetIcon($oObject)
	{
		return '';
	}

	public function GetHilightClass($oObject)
	{
		// Possible return values are:
		// HILIGHT_CLASS_CRITICAL, HILIGHT_CLASS_WARNING, HILIGHT_CLASS_OK, HILIGHT_CLASS_NONE	
		return HILIGHT_CLASS_NONE;
	}

	public function EnumAllowedActions(DBObjectSet $oSet)
	{
		// No action
		return array();
    }

	public function OnIsModified($oObject)
	{
		return false;
	}

	public function OnCheckToWrite($oObject)
	{
		return array();
	}

	public function OnCheckToDelete($oObject)
	{
		return array();
	}

	public function OnDBUpdate($oObject, $oChange = null)
	{
	}
	
	public function OnDBInsert($oObject, $oChange = null)
	{
	}
	
	public function OnDBDelete($oObject, $oChange = null)
	{	
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	// Plug-ins specific functions
	//
	///////////////////////////////////////////////////////////////////////////////////////////////////////
	
	protected function IsTargetObject($oObject)
	{
		$sAllowedClass = MetaModel::GetModuleSetting('combodo-email-reply', 'target_class', 'Ticket');
		return ($oObject instanceof $sAllowedClass);
	}
}
?>
