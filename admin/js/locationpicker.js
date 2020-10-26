jQuery(document).ready(function($){
	$("#club-location-picker").locationpicker({
		location: {
			latitude: Number($('#latitude').val()),
			longitude: Number($('#longitude').val())
		},
		radius: 0,
		inputBinding: {
	        latitudeInput: $('#latitude'),
	        longitudeInput: $('#longitude'),
	        locationNameInput: $('#address')
	    },
	    enableAutocomplete: true
	});
});
