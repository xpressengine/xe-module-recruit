/**
 * @file   modules/recruit/skins/xe_recruit_default/js/skin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  recruit default skin javascript
 **/

jQuery(function($){
	$('a.btn_applicant').click(function(){
		$('div.feedbackList').toggle(500);
	});

	$('a.view_applicant_detail').click(function(){
		var comment_srl = $(this).attr('data');
		var id = 'tr#' + comment_srl;
		if(($(id).css("display") == 'none')){
			$(id).show(300);
			$(id).prev('tr').find('td').css('border-style','none');
		}else{
			$(id).hide(300);
			$(id).prev('tr').find('td').css('border-style','solid');
		}
	});


});