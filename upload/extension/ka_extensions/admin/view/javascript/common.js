/*
    $Project$
    $Author$
    
    $Version$ ($Revision$)
    
    ka_extensions - our global object
    
*/
var ka_extensions = new function () {

	this.labels = {
		'txt_info'   : 'Info',
		'txt_success': 'Success',
		'txt_warning': 'Warning',
		'txt_error'  : 'Error'
	};

	this.resetForm = (id) => {
		$('#' + id + ' input, #' + id + ' select').val('');
		location = $('#' + id).attr('action');
	}

    this.showMessage =(text, type) => {
	
    	var labels = this.labels;
    
		var style = 'info';
		var title = labels['txt_info'];
		var icon  = 'fa-info-circle';

		if (type) {
			if (type == 'D' || type == 'E') {
				style = 'danger';
				title = labels['txt_error'];
				icon  = 'fa-exclamation-circle';
			} else if (type == 'S') {
				style = 'success';
				title = labels['txt_success'];
				icon  = 'fa-check-circle';
				
			} else if (type == 'W') {
				style = 'warning';
				title = labels['txt_warning'];
				icon  = 'fa-exclamation-triangle';
			}
		}
		
		var str = `
	    	<div class="alert alert-${style} alert-dismissible fade show" role="alert">
	    		<i class="fas ${icon}"></i>
	    		${text}
	    		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	    	</div>`;
	    	
		$('#alert').prepend(str);

	    window.setTimeout(function() {
	        $('.alert-dismissible').fadeTo(1000, 0, function() {
	            $(this).remove();
	        });
	    }, 5000);

	}	

	this.initPopovers = () => {
		$('[data-bs-toggle=\'popover\']').each(function(idx, el) {
			return new bootstrap.Popover(el, {
			  container: 'body', 
			    html: true,
			    trigger: "click hover focus",
			    delay: {show:300, hide:1500}
			})	
		});
	}
};