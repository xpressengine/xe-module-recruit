/**
 * @file   modules/recruit/js/recruit.js
 * @author NHN (developers@xpressengine.com)
 * @brief  recruit   javascript
 **/


function completeDocumentInserted(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var category_srl = ret_obj['category_srl'];

    var url;
    if(!document_srl)
    {
        url = current_url.setQuery('mid',mid).setQuery('act','');
    }
    else
    {
        url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','');
    }
    if(category_srl) url = url.setQuery('category',category_srl);
    location.href = url;
}

function completeDeleteDocument(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var page = ret_obj['page'];

    var url = current_url.setQuery('mid',mid).setQuery('act','').setQuery('document_srl','');
    if(page) url = url.setQuery('page',page);

    location.href = url;
}

function completeInsertComment(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var comment_srl = ret_obj['comment_srl'];

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','dispRecruitJobdetail');
    if(comment_srl) url = url.setQuery('rnd',comment_srl)+"#comment_"+comment_srl;

    location.href = url;
}

function completeDeleteComment(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var mid = ret_obj['mid'];
    var document_srl = ret_obj['document_srl'];
    var page = ret_obj['page'];

    var url = current_url.setQuery('mid',mid).setQuery('document_srl',document_srl).setQuery('act','dispRecruitJobdetail');
    if(page) url = url.setQuery('page',page);

    location.href = url;
}

function completeSearch(ret_obj, response_tags, params, fo_obj) {
    fo_obj.submit();
}

function doChangeCategory() {
    var category_srl = jQuery('#recruit_category option:selected').val();
    location.href = decodeURI(current_url).setQuery('category_srl',category_srl).setQuery('page', '');
}

function doDeleteGroup(divName)
{
    var dSrl = [];
    var cartBoxes = jQuery('.cartCheckBox');
    for(var i in cartBoxes)
    {
        if(!cartBoxes[i].checked) continue;
        dSrl[dSrl.length] = cartBoxes[i].value;
    }
    if(!dSrl.length){
		alert('No jobs are selected.') 
		return;
	}
    dSrl = dSrl.join(',');

    divName = divName || 'delete_job';
    showDelete(dSrl, divName);
}

function doDeleteGroupApplicant(divName)
{
    var dSrl = [];
    var cartBoxes = jQuery('.cartCheckBox');
    for(var i in cartBoxes)
    {
        if(!cartBoxes[i].checked) continue;
        dSrl[dSrl.length] = cartBoxes[i].value;
    }
    if(!dSrl.length){
		alert('No applicants are selected.') 
		return;
	}
    dSrl = dSrl.join(',');

    divName = divName || 'delete_applicant';
    showDeleteApplicant(dSrl, divName, true);
}

function addDetail(ret_obj, response_tags)
{
    var error = ret_obj['error'];
    var content = ret_obj['content'];
    var srl = ret_obj['srl'];

    if(ret_obj['message']!='success') alert(ret_obj['message']);

    jQuery('.detailRow').css('display','none');
    var jobTd = jQuery(".detail_" + srl);
    jobTd.children().html(content);
    jobTd.css('display','');
}

function getDetail(srl,act)
{
    act = act || 'procRecruitGetDetail';
    var params = {};
    params['srl'] = srl;
    exec_xml('recruit', act, params, addDetail,['error','message','srl','content']);
}

function showDelete(srl, divName)
{
    divName = 'delete_job';
    jQuery('#'+divName).find('div.deleteForm').css('display','block');
    jQuery('#' + divName).find('#deleteJobSrl').val(srl);
}

function showDeleteApplicant(comment_srl, divName, group)
{
	if(group == false){
		jQuery('#'+divName).find('h3#delete_group_applicants').hide();
		jQuery('#'+divName).find('h3#delete_single_applicant').show();
	}
	if(group == true){
		jQuery('#'+divName).find('h3#delete_single_applicant').hide();
		jQuery('#'+divName).find('h3#delete_group_applicants').show();
	}
	divName = 'delete_applicant';
    jQuery('#'+divName).css('display','block');
    jQuery('#' + divName).find('#deleteCommentFormSrl').val(comment_srl);
}

function cancelDelete()
{
    jQuery('.deleteForm').css('display','none');
    jQuery('.deleteApplicantForm').css('display','none');
}