<?php
// Copyright (C) 2010-2015 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>

function MakeAttachmentLabel($oOrmDoc, $sObjClass, $iObjId)
{
	$oAttachment = MetaModel::GetObject($sObjClass, $iObjId);
	$sTimeStampUpload = $oAttachment->Get('creation_date');
	$sFileFormattedSize = $oOrmDoc->GetFormattedSize();
	$sNameUploader = utils::HtmlEntities($oAttachment->Get('contact_id_friendlyname'));
	$sFilename = $oOrmDoc->GetFileName();
	$sFilenameForHtml = utils::HtmlEntities($sFilename);
	$sDictEntryCode = 'UI-emry-attachment-label';
	$sDictEntryCode .= ($sNameUploader != '') ? '-with-uploadername' : '';
	$sDictEntryCode .= ($sTimeStampUpload != '') ? '-with-timestamp' : '';
	$sAttachmentLabel = Dict::Format($sDictEntryCode, $sFilenameForHtml, $sFileFormattedSize, $sNameUploader, $sTimeStampUpload);
	return $sAttachmentLabel;
}


require_once(APPROOT.'/application/application.inc.php');

//remove require itopdesignformat at the same time as version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0
if (! defined("ITOP_DESIGN_LATEST_VERSION")) {
	require_once APPROOT.'setup/itopdesignformat.class.inc.php';
}
if (version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0) {
	require_once(APPROOT.'/application/ajaxwebpage.class.inc.php');
}
require_once(APPROOT.'/application/wizardhelper.class.inc.php');
require_once(APPROOT.'/application/ui.linkswidget.class.inc.php');
require_once(APPROOT.'/application/ui.extkeywidget.class.inc.php');

try
{
	require_once(APPROOT.'/application/startup.inc.php');
	require_once(APPROOT.'/application/user.preferences.class.inc.php');
	
	require_once(APPROOT.'/application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(false /* bMustBeAdmin */, false /* IsAllowedToPortalUsers */); // Check user rights and prompt if needed

	if (version_compare(ITOP_DESIGN_LATEST_VERSION , '3.0') < 0) {
		$oPage = new ajax_page('');
		$oPage->no_cache();
	} else {
		$oPage = new AjaxPage('');
	}

	$sOperation = utils::ReadParam('operation');

	switch($sOperation)
	{
		default:
		$aAttachments = utils::ReadParam('attachments', array(), false, 'raw_data');
		$aObjByClassAndId = array();
		foreach($aAttachments as $aData)
		{
			$sObjClass = $aData['sContainerClass'];
			if (!array_key_exists($sObjClass, $aObjByClassAndId))
			{
				$aObjByClassAndId[$sObjClass] = array();
			}
			$aObjByClassAndId[$sObjClass][$aData['sContainerId']] = null;
		}
		
		foreach($aObjByClassAndId as $sClass => $aObjById)
		{
			$oSearch = DBObjectSearch::FromOQL("SELECT $sClass WHERE id IN (".implode(',', array_keys($aObjById)).")");
			$oSet = new DBObjectSet($oSearch);
			while($oObj = $oSet->Fetch())
			{
				$aObjByClassAndId[$sClass][$oObj->GetKey()] = $oObj;
			}
		}
		
		foreach($aAttachments as $aData)
		{
			$sObjClass = $aData['sContainerClass'];
			$iObjId = $aData['sContainerId'];
			$oObj = $aObjByClassAndId[$sClass][$iObjId];
			if ($oObj !== null)
			{
				$oDoc = $oObj->Get($aData['sBlobAttCode']);
				$sFileName = $oDoc->GetFileName();
				$sIcon = utils::GetAbsoluteUrlAppRoot().AttachmentPlugIn::GetFileIcon($sFileName);
				$sAttachmentLabel = MakeAttachmentLabel($oDoc, $sObjClass, $iObjId);
				$sPreview = $oDoc->IsPreviewAvailable() ? 'true' : 'false';
				$sChecked = ($aData['checked'] == 'true') ? ' checked' : '';
				$sFileDef = $sObjClass.'::'.$iObjId.'/'.$aData['sBlobAttCode'];
				$sId = "emry-pick-$sObjClass-$iObjId";
				$sDownloadLink = utils::GetAbsoluteUrlAppRoot().'pages/ajax.document.php?operation=download_document&class='.$sObjClass.'&field='.$aData['sBlobAttCode'].'&id='.$iObjId;
				$iMaxWidth = MetaModel::GetModuleSetting('itop-attachments', 'preview_max_width', 290);
				$sPreviewNotAvailable = Dict::S('Attachments:PreviewNotAvailable');
				$sPreviewData = $sPreviewNotAvailable;
				if ($sPreview === 'true'){
					$sPreviewData = utils::HtmlEntities('<img src="'.$sDownloadLink.'" style="max-width: '.$iMaxWidth.'"/>');
				}
				$oPage->add('<div style="vertical-align:middle;"><input type="checkbox" data-fileref="'.$sFileDef.'" id="'.$sId.'" '.$sChecked.'><label class="emry-attachment" data-preview="'.$sPreview.'" for="'.$sId.'" data-tooltip-html-enabled="true" data-tooltip-content="'.$sPreviewData.'">&nbsp;<img style="vertical-align:middle;" src="'.$sIcon.'" />&nbsp;'.$sAttachmentLabel.'</label></div>');
				if(EmailReplyPlugIn::UseLegacy()) {
					$oPage->add_ready_script(
						<<<JS
	$(document).tooltip({ items: '.emry-attachment',  position: { my: 'left top', at: 'right top', using: function( position, feedback ) { $( this ).css( position ); }}, content: function() { return($(this).attr('data-tooltip-content'));}});
JS
					);
				}
				else {
					$oPage->add_ready_script(
						<<<JS
CombodoTooltip.InitTooltipFromMarkup($('[for="$sId"]'), true);
JS
					);
				}
			}				
		}		
	}
	
	$oPage->output();
}
catch (Exception $e)
{
	echo $e->GetMessage();
	IssueLog::Error($e->getMessage());
}
