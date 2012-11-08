// (c) Combodo SARL 2012

var aEmailReplyFiles = {};


function EmailReplyAddFile(sCaseLogAttCode, sContainerClass, sContainerId, sBlobAttCode, sFileName)
{
	var sFileDef = sContainerClass+'::'+sContainerId+'/'+sBlobAttCode;
	var sId = 'emry_file_'+sCaseLogAttCode+'_'+sContainerClass+'_'+sContainerId+'_'+sBlobAttCode;
	var sForm = '<input type="hidden" name="emry_files_'+sCaseLogAttCode+'[]" id="'+sId+'" value="'+sFileDef+'"/>';
	$('#emry_form_extension').append(sForm);

	if (!(sCaseLogAttCode in aEmailReplyFiles))
	{
		aEmailReplyFiles[sCaseLogAttCode] = {};
		aEmailReplyFiles[sCaseLogAttCode+'_file_count'] = 0;
	}
	aEmailReplyFiles[sCaseLogAttCode][sFileDef] = { sCaseLogAttCode: sCaseLogAttCode, sContainerClass: sContainerClass, sContainerId: sContainerId, sBlobAttCode: sBlobAttCode, sFileName: sFileName };
	aEmailReplyFiles[sCaseLogAttCode+'_file_count']++;
	EmailReplyUpdateFileCount(sCaseLogAttCode);
}


function EmailReplyRemoveFile(sCaseLogAttCode, sContainerClass, sContainerId, sBlobAttCode)
{
	var sFileDef = sContainerClass+'::'+sContainerId+'/'+sBlobAttCode;
	var sId = 'emry_file_'+sCaseLogAttCode+'_'+sContainerClass+'_'+sContainerId+'_'+sBlobAttCode;
	$('#'+sId).remove();

	delete aEmailReplyFiles[sCaseLogAttCode][sFileDef];
	aEmailReplyFiles[sCaseLogAttCode+'_file_count']--;
	EmailReplyUpdateFileCount(sCaseLogAttCode);
}


function EmailReplyUpdateFileCount(sCaseLogAttCode)
{
	var iCount  = aEmailReplyFiles[sCaseLogAttCode+'_file_count'];
	$('#emry_file_count_'+sCaseLogAttCode).html(iCount);
	var sHtml = '';
	var index = 0;
	for(var index in aEmailReplyFiles[sCaseLogAttCode])
	{
		sHtml += aEmailReplyFiles[sCaseLogAttCode][index].sFileName+"<br>\n";
		index++;
	}
	if (sHtml == '')
	{
		sHtml = Dict.S('UI-emry-noattachment');
	}
	var api = $('#emry_file_count_'+sCaseLogAttCode).qtip("api");
	api.destroy();
	$('#emry_file_count_'+sCaseLogAttCode).qtip({ content: sHtml, show: 'mouseover', hide: 'unfocus', position: { corner: { target: 'topRight', tooltip: 'bottomLeft'}}, style: { name: 'dark', tip: 'bottomLeft' } });
}