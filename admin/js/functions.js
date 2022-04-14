jQuery(document).ready(function($) {

	/* hide top-links in documentation */
	jQuery(".top-link").css("display", "none");

	// Datepicker
	$('.mydatepicker').datepicker({
		numberOfMonths: 3,
		showButtonPanel: false,
		dateFormat  :  'yy-mm-dd',
		changeMonth: true,
		changeYear: true
	});

	// Enable iris colorpicker
	jQuery(document).ready(function() {
		jQuery('.racketmanager-colorpicker').iris();
	});

	$(window).scroll(function() {
		if ( $(this).scrollTop() > 800 ) {
			$('.go-top').addClass('show');
		} else {
			$('.go-top').removeClass('show');
		}
	});

	$('.go-top').on('click', function() {
		$("html, body").animate({ scrollTop: 0 }, 1000);
		return false;
	});

	// make formfield table sortable and add nice css cursor
	jQuery(".standings-table.sortable").sortable({
		axis: "y"
	});
	jQuery(".sortable").css("cursor", "move");

	// disable rank input fields
	jQuery("input.rank-input").prop('disabled', 'true');
	// Set js-active value to 1
	jQuery("#teams-filter>input.js-active").val(1);

	$('#teamPlayer1').autocomplete({
		minLength: 2,
		source: function(name, response) {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"action": "racketmanager_getPlayerDetails"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			$("#teamPlayerId1").val(ui.item.id);
			$("#affiliatedclub").val(ui.item.clubId);
			$("#captain").val(ui.item.value);
			$("#captainId").val(ui.item.playerId);
			$("#contactno").val(ui.item.contactno);
			$("#contactemail").val(ui.item.user_email);
		},
		change: function(event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#teamPlayerId1").val('');
				$("#affiliatedclub").val('');
				$("#captain").val('');
				$("#captainId").val('');
				$("#contactno").val('');
				$("#contactemail").val('');
				$("#team").val('');
			} else {
				$("#teamPlayerId1").val(ui.item.id);
				$("#captain").val(ui.item.value);
				$("#captainId").val(ui.item.playerId);
				$("#affiliatedclub").val(ui.item.clubId);
				$("#contactno").val(ui.item.contactno);
				$("#contactemail").val(ui.item.user_email);
				$team1 = $("#teamPlayer1").val();
				if ( $("#teamPlayer2").val() == '' ) {
					$team2 = $("#teamPlayer2").val();
					$team = $team1 + ' \/ ' + $team2;
				} else {
					$team = $team1;
				}
				$("#team").val($team);
			}
		}
	});
	$('#teamPlayer2').autocomplete({
		minLength: 2,
		source: function(name, response) {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"action": "racketmanager_getPlayerDetails"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			$("#teamPlayerId2").val(ui.item.id);
		},
		change: function(event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#teamPlayerId2").val('');
				$("#team").val('');
			} else {
				$("#teamPlayerId2").val(ui.item.id);
				$team1 = $("#teamPlayer1").val();
				$team2 = $("#teamPlayer2").val();
				$team = $team1 + ' \/ ' + $team2;
				$("#team").val($team);
			}
		}
	});
	$('#captain').autocomplete({
		minLength: 2,
		source: function(name, response) {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"affiliatedClub": $("#affiliatedclub").val(),
				"action": "racketmanager_getCaptainName"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			$("#captain").val(ui.item.value);
			$("#captainId").val(ui.item.id);
			$("#contactno").val(ui.item.contactno);
			$("#contactemail").val(ui.item.user_email);
		},
		change: function(event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#captain").val('');
				$("#captainId").val('');
				$("#contactno").val('');
				$("#contactemail").val('');
			} else {
				$("#captain").val(ui.item.value);
				$("#captainId").val(ui.item.id);
				$("#contactno").val(ui.item.contactno);
				$("#contactemail").val(ui.item.user_email);
			}
		}
	});
	$('#matchSecretaryName').autocomplete({
		minLength: 2,
		source: function(name, response) {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"affiliatedClub": $("#club_id").val(),
				"action": "racketmanager_getCaptainName"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			$("#matchsecretaryName").val(ui.item.value);
			$("#matchsecretary").val(ui.item.id);
			$("#matchSecretaryContactNo").val(ui.item.contactno);
			$("#matchSecretaryEmail").val(ui.item.user_email);
		},
		change: function(event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#matchsecretaryName").val('');
				$("#matchsecretary").val('');
				$("#matchSecretaryContactNo").val('');
				$("#matchSecretaryEmail").val('');
			} else {
				$("#matchsecretaryName").val(ui.item.value);
				$("#matchsecretary").val(ui.item.id);
				$("#matchSecretaryContactNo").val(ui.item.contactno);
				$("#matchSecretaryEmail").val(ui.item.user_email);
			}
		}
	});
	$('#tournamentSecretaryName').autocomplete({
		minLength: 2,
		source: function(name, response) {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"action": "racketmanager_getPlayerDetails"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			$("#tournamentSecretaryName").val(ui.item.value);
			$("#tournamentSecretary").val(ui.item.playerId);
			$("#tournamentSecretaryContactNo").val(ui.item.contactno);
			$("#tournamentSecretaryEmail").val(ui.item.user_email);
		},
		change: function(event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#tournamentSecretaryName").val('');
				$("#tournamentSecretary").val('');
				$("#tournamentSecretaryContactNo").val('');
				$("#tournamentSecretaryEmail").val('');
			} else {
				$("#tournamentSecretaryName").val(ui.item.value);
				$("#tournamentSecretary").val(ui.item.playerId);
				$("#tournamentSecretaryContactNo").val(ui.item.contactno);
				$("#tournamentSecretaryEmail").val(ui.item.user_email);
			}
		}
	});

	$("#teamPlayerFrm").submit(function( event ) {
		var $error = false;
		var $msg = '';
		if ( $("#team").val() == '' ) {
			$error = true;
			$msg += 'Team name not set\n';
		} else {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				async: false,
				data: {"name": $("#team").val(),
				"action": "racketmanager_checkTeamExists"},
				success: function(response) {
					if ( response == true ) {
						$error = true;
						$msg += 'Team already exists\n';
					}
				},
				error: function() {
					$error = true;
					$msg += 'Error with team name check\n';
				}
			});
		}
		if ( $("#teamPlayerId1").val() == '' ) {
			$error = true;
			$msg += 'Player 1 not set\n';
		}
		if ($("#teamPlayerId2").length){
			if ( $("#teamPlayerId2").val() == '' ) {
				$error = true;
				$msg += 'Player 2 not set\n';
			}
		}
		if ( $("#affiliatedclub").val() == '' ) {
			$error = true;
			$msg += 'Club not set\n';
		}
		if ($error) {
			$("#errorMsg").show();
			$("#errorMsg").text($msg);
			event.preventDefault();
		}
	});
});

if(typeof Racketmanager == "undefined") {
	var Racketmanager = new Object();
}

tb_init('a.thickbox, area.thickbox, input.thickbox');

Racketmanager.checkAll = function(form) {
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
			if(form.elements[i].checked == true)
			form.elements[i].checked = false;
			else
			form.elements[i].checked = true;
		}
	}
}

//Racketmanager.checkPointRule = function( forwin, forwin_overtime, fordraw, forloss, forloss_overtime ) {
Racketmanager.checkPointRule = function( rule ) {
	//	var rule = document.getElementById('point_rule').value;

	// manual rule selected
	if ( rule == 'user' ) {
		new_element_contents = "";
		new_element_contents += "<input type='text' name='forwin' id='forwin' value=" + forwin + " size='2' />";
		new_element_contents += "<input type='text' name='forwin_overtime' id='forwin_overtime' value=" + forwin_overtime + " size='2' />";
		new_element_contents += "<input type='text' name='fordraw' id='fordraw' value=" + fordraw + " size='2' />";
		new_element_contents += "<input type='text' name='forloss' id='forloss' value=" + forloss + " size='2' />";
		new_element_contents += "<input type='text' name='forloss_overtime' id='forloss_overtime' value=" + forloss_overtime + " size='2' />";
		new_element_contents += "&#160;<span class='setting-description'>" + RacketManagerAjaxL10n.manualPointRuleDescription + "</span>";
		new_element_id = "point_rule_manual_content";
		new_element = document.createElement('div');
		new_element.id = new_element_id;

		document.getElementById("point_rule_manual").appendChild(new_element);
		document.getElementById(new_element_id).innerHTML = new_element_contents;
	} else {
		element_count = document.getElementById("point_rule_manual").childNodes.length;
		if(element_count > 0) {
			target_element = document.getElementById("point_rule_manual_content");
			document.getElementById("point_rule_manual").removeChild(target_element);
		}

	}

	return false;
}

Racketmanager.insertPlayer = function(id, target) {
	tb_remove();
	var player = document.getElementById(id).value;
	document.getElementById(target).value = player;
}

Racketmanager.removeField = function(id, parent_id) {
	element_count = document.getElementById(parent_id).childNodes.length;
	if(element_count > 1) {
		target_element = document.getElementById(id);
		document.getElementById(parent_id).removeChild(target_element);
	}
	return false;
}

Racketmanager.reInit = function() {
	tb_init('a.thickbox, area.thickbox, input.thickbox');
}
function activaTab(tab) {
    jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
		jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
