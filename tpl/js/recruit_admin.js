/**
 * @file   modules/recruit/js/recruit_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  recruit admin javascript
 **/

function completeDeleteRecruit(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispRecruitAdminList').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

jQuery(function($){
	    $('.tWord').click(function(){
			var location_srl = $(this).attr('data');

	        $('.inputText').val($(this).html());
			$('.inputText').attr('data', location_srl);
			$('.cgBtnAdd').css('display','none');
            $('.cgBtnModify').css('display','inline');
			$('.cgBtnDelete').css('display','inline');
	    });

	    this.addItem = function()
	    {
			if($('.inputText').val() == ""){
				alert('Location can not be empty.');
			}else{
				$('form.locationList').submit();
			}
	    }

        this.ModifyItem = function(){
			var location_srl = $('.inputText').attr('data');
			var location_srl_input = $('input[name=location_srl]');
			location_srl_input.val(location_srl);
            
			if($('.inputText').val() == ""){
				alert('Location can not be empty.');
			}else{
				$('form.locationList').submit();
			}
        }

        this.deleteItem = function()
        {
			if(confirm('Are you sure to delete this location?')){
				var location_srl = $('.inputText').attr('data');
				var location_srl_input = $('input[name=location_srl]');
				location_srl_input.val(location_srl);
				var type_input  =  $('input[name=type]');
				type_input.val('delete');
				$('.locationList').submit();

			}else{
				return false;
			}
        }
	});