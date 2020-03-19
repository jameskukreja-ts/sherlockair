jQuery(document).ready(function(){
	jQuery('.vs-slect option:not(:selected)').attr('disabled', true);
	
	var mediaUploader;

	  jQuery('.upload_image_button').click(function(e) {
		e.preventDefault();
		// If the uploader object has already been created, reopen the dialog
		  if (mediaUploader) {
		  mediaUploader.open();
		  return;
		}
		// Extend the wp.media object
		mediaUploader = wp.media.frames.file_frame = wp.media({
		  title: 'Choose Image',
		  button: {
		  text: 'Choose Image'
		}, multiple: false });

		// When a file is selected, grab the URL and set it as the text field's value
		mediaUploader.on('select', function() {
		  attachment = mediaUploader.state().get('selection').first().toJSON();
		  jQuery('#img_path').val(attachment.url);
		});
		// Open the uploader dialog
		mediaUploader.open();
	  });
	
	var trnum = jQuery('.csc-row').length;
	jQuery( '.wrap' ).on( 'click', '.add-block', function(){
		jQuery('.csc-empty').hide();
		jQuery('.csc-spin').css('display', 'inline');
		var data = {
			trnum: trnum,
			action: 'vs_action',
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('.csc-spin').css('display', '');
			jQuery('div.last_tr').before(response);
			jQuery('.add-block').hide();
			trnum = trnum + 1;
		});
	});
	jQuery( '.wrap' ).on( 'click', '.delete-block', function(){
		var id = jQuery(this).attr('data-pageid');
		var fid = jQuery(this).attr('data-formid');
		var answer = confirm('Are you sure you want to delete this?');
		if (answer){
			var data = {
				delete_id: id,
				action: 'vs_action_delete',
			};
			jQuery.post(ajaxurl, data, function(response) {
				if( response == 1 ){
					jQuery("#csc-settings-"+fid).remove();
				}
			});
		}else{
		  return false;
		}

	});
	jQuery( '.wrap' ).on( 'click', '.save-block', function() {
		var fid = jQuery(this).attr('data-formid');
		jQuery('.spinner-'+fid).css('display', 'inline');
		var data = {
			form: jQuery("#csc-settings-"+fid).serialize(),
			action: 'vs_savecitystate',
		};
		jQuery.post(ajaxurl, data, function(response) {
			
			if( response == 111 ){
				alert( "Please Select the page which is not linked yet!" );
				jQuery('.spinner-'+fid).css('display', '');
				return false;
			}
			if( response == 112 ){
				alert( "Please Select other page this page is already linked!" );
				jQuery('.spinner-'+fid).css('display', '');
				return false;
			}
			var array = response.split('-');

			for (var i = 0;i<array.length;i++){
				var number = parseFloat(array[i]);
				if(!isNaN(number)){
				var myNumber = number;
				//var mySomething = array[i - 1];
				}
			}
			jQuery('#csc-settings-'+fid).find('.csc-hidden').val('update');
			jQuery('.spinner-'+fid).css('display', '');
			jQuery('.Delete').css('display', 'inline');
			jQuery('#csc-settings-'+fid).find('.delete-block').attr('data-pageid', myNumber);
			jQuery('option:not(:selected)').attr('disabled', true);
			jQuery('.add-block').show();
		});
		return false;
	});
	
});
function crec(imgurl,formfield){
	jQuery(formfield).siblings('input').val(imgurl);
}