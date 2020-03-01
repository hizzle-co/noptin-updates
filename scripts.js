(function ($) {

	// Attach the tooltips
	$(document).ready(function(){

        // Activate license.
		$('.noptin-updates-activate-license').on('click', function(e){
            e.preventDefault();

            var data = {
                'product_id' : $(this).data('product-id'),
                'product_name' : $(this).data('product-name'),
                '_wpnonce' : noptinUpdates.nonce
            }

            //Init sweetalert
		    Swal.fire({
			    titleText: 'Enter Your License',
			    showCancelButton: true,
			    confirmButtonColor: '#3085d6',
			    cancelButtonColor: '#d33',
			    confirmButtonText: 'Activate',
			    showLoaderOnConfirm: true,
			    showCloseButton: true,
			    input: 'text',
                inputPlaceholder: 'Enter your license key',
                footer: 'Activating ' + data.product_name,
                allowOutsideClick: function() { return !Swal.isLoading() },

                inputValidator: function( value ) {
                    if (!value) {
                        return 'Specify a license key'
                    }
                },
                
                //Fired when the user clicks on the confirm button.
			    preConfirm(license_key) {
                    data.license_key = license_key

                    jQuery.post(noptinUpdates.activate_url, data )

                          .done(function (data) {

                                Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Your license has been activated',
                                    showConfirmButton: false,
                                    timer: 1500
                                })
                              window.location = window.location
                          })

                          .fail(function (jqXHR) {
                               var footer = jqXHR.statusText

                               if ( jqXHR.responseJSON && jqXHR.responseJSON.message ) {
                                    footer = jqXHR.responseJSON.message
                               }

                            Swal.fire({
                                icon: 'error',
                                title: 'Error activating your license',
                                footer: footer,
                                showCloseButton: true,
                                confirmButtonText: 'Close',
                                confirmButtonColor: '#9e9e9e',
                                showConfirmButton: false,
                            })
    
                        })

                    //Return a promise that never resolves
                    return jQuery.Deferred()
                }
            })
        })

        // Deactivate license.
		$('.noptin-updates-deactivate-license').on('click', function(e){
            e.preventDefault();

            var data = {
                'product_id' : $(this).data('product-id'),
                '_wpnonce' : noptinUpdates.nonce
            }

            //Init sweetalert
		    Swal.fire({
                icon: 'warning',
			    titleText: 'Deactivate License',
			    showCancelButton: true,
			    confirmButtonColor: '#3085d6',
			    cancelButtonColor: '#d33',
			    confirmButtonText: 'Deactivate',
			    showLoaderOnConfirm: true,
			    showCloseButton: true,
                footer: 'You will nolonger receive product updates on this website',
                allowOutsideClick: function() { return !Swal.isLoading() },
                
                //Fired when the user clicks on the confirm button.
			    preConfirm() {

                    jQuery.post(noptinUpdates.deactivate_url, data )

                          .done(function (data) {

                                Swal.fire({
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Your license has been deactivated',
                                    showConfirmButton: false,
                                    timer: 1500
                                })
                              window.location = window.location
                          })

                          .fail(function (jqXHR) {
                               var footer = jqXHR.statusText

                               if ( jqXHR.responseJSON && jqXHR.responseJSON.message ) {
                                    footer = jqXHR.responseJSON.message
                               }

                            Swal.fire({
                                icon: 'error',
                                title: 'Error deactivating your license',
                                footer: footer,
                                showCloseButton: true,
                                confirmButtonText: 'Close',
                                confirmButtonColor: '#9e9e9e',
                                showConfirmButton: false,
                            })
    
                        })

                    //Return a promise that never resolves
                    return jQuery.Deferred()
                }
            })
        })

	});


})(jQuery);
