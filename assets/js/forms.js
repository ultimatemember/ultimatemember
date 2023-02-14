if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.forms ) !== 'object' ) {
	UM.forms = {};
}

UM.forms = {
	honeypot: function () {
		// flush fields using honeypot security
		jQuery('input[name="<?php echo esc_js( UM()->honeypot ); ?>"]').val('');
	}
};

jQuery( window ).on( 'load', function() {
	UM.forms.honeypot();
});

jQuery( document ).ready( function($) {
	$('.jb-select-media').each( function() {
		var $button = $(this);
		var $action = $button.data('action');
		var $filelist = $button.parents('.jb-uploader-dropzone');
		var $button_wrapper = $button.parents('.jb-select-media-button-wrapper');
		var $errorlist = $filelist.siblings( '.jb-uploader-errorlist' );
		var extensions = 'jpg,jpeg,gif,png,bmp,ico,tiff';

		var uploader_args = {
			browse_button: $button.get( 0 ), // you can pass in id...
			drop_element: $filelist.get( 0 ), // ... or DOM Element itself
			container: $button_wrapper.get( 0 ), // ... or DOM Element itself
			url: wp.ajax.settings.url + '?action=' + $action + '&nonce=' + jb_front_data.nonce,
			chunk_size: '1024kb',
			max_retries: 1,
			multipart: true,
			multi_selection: false,
			filters: {
				max_file_size: '10mb',
				mime_types: [
					{ title: wp.i18n.__( 'Image files', 'jobboardwp' ), extensions: extensions },
				],
				prevent_duplicates: true,
				max_file_count: 1
			},
			init: {
				Error: function ( up, err ) {
					$errorlist.html( '<p>' + wp.i18n.__( 'Error!', 'jobboardwp' ) + ' ' + err.message + '</p>' );
				},
				FileFiltered: function ( up, file ) {
					$errorlist.empty();

					if ( ! up.getOption( 'multi_selection' ) ) {
						$filelist.find( '.jb-uploader-file' ).each( function ( u, item ) {
							up.removeFile( item.id );
						} );
					}
				},
				FilesAdded: function ( up, files ) {
					up.start();
				},
				FilesRemoved: function ( up, files ) {
					$.each( files, function ( i, file ) {
						$( '#' + file.id ).remove();
					} );

					if ( ! $filelist.find( '.jb-uploader-file' ).length ) {
						$errorlist.empty();
					}
				},
				FileUploaded: function ( up, file, result ) {
					if ( result.status === 200 && result.response ) {

						var response = JSON.parse( result.response );

						if ( ! response ) {
							$errorlist.append( '<p>' + wp.i18n.__( 'Error! Wrong file upload server response.', 'ultimate-member' ) + '</p>' );
						} else if ( response.info && response.OK === 0 ) {
							console.error( response.info );
						} else if ( response.data ) {

							$button.parents('.jb-uploader').addClass( 'jb-uploaded' );
							$button.parents('.jb-uploader').siblings('.jb-uploaded-wrapper').addClass( 'jb-uploaded' ).removeClass('jb-waiting-change');
							$button.parents('.jb-uploader').siblings('.jb-uploaded-wrapper').find('img').attr( 'src', response.data[0].url );
							wp.hooks.doAction( 'jb_job_uploader_after_success_upload', $button, response );

							$button.parents('.jb-uploader').siblings('.jb-media-value').val( response.data[0].name_saved );
							$button.parents('.jb-uploader').siblings('.jb-media-value-hash').val( response.data[0].hash );
						}

					} else {
						// translators: %s is the error status code.
						console.error( wp.i18n.__( 'File was not loaded, Status Code %s', 'ultimate-member' ), [ result.status ] );
					}
				},
				PostInit: function ( up ) {
					$filelist.find( '.jb-uploader-file' ).remove();
				},
				UploadProgress: function ( up, file ) {
					$( '#' + file.id ).find( 'b' ).html( '<span>' + file.percent + '%</span>' );
				},
				UploadComplete: function ( up, files ) {
				}
			}
		};
		uploader_args = wp.hooks.applyFilters( 'jb_job_uploader_filters_attrs', uploader_args, $button );

		jb_media_uploader = new plupload.Uploader( uploader_args );
		jb_media_uploader.init();
	});
});
