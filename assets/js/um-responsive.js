jQuery(window).load(function() {

	um_responsive();
	um_modal_responsive();

});

jQuery(window).resize(function() {
	responsive_Modal();
	jQuery('.um-modal .um-single-image-preview.crop:visible img').cropper("destroy");
	
	um_responsive();
	um_modal_responsive();

});