jQuery(document).ready( function() {
	jQuery('.butonimg').click(function(){
		jQuery(this).parent().siblings('.msg-body').slideToggle();
			jQuery(this).toggleClass('butonimghover');
	});
	
});


