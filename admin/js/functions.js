jQuery(document).ready(function ($) {
	$('[data-bs-toggle="popover"]').popover({
		placement: "left",
		content: showPlayerClubs,
		html: true
	});
	/* hide top-links in documentation */
	jQuery(".top-link").css("display", "none");

	// Enable iris colorpicker
	jQuery(document).ready(function () {
		jQuery('.racketmanager-colorpicker').iris();
	});

	$(window).scroll(function () {
		if ($(this).scrollTop() > 800) {
			$('.go-top').addClass('show');
		} else {
			$('.go-top').removeClass('show');
		}
	});

	$('.go-top').on('click', function () {
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
		source: function (request, response) {
			response(get_player_details(request.term));
		},
		select: function (event, ui) {
			$("#teamPlayerId1").val(ui.item.playerId);
			$("#affiliatedclub").val(ui.item.club_id);
			$("#captain").val(ui.item.value);
			$("#captainId").val(ui.item.playerId);
			$("#contactno").val(ui.item.contactno);
			$("#contactemail").val(ui.item.user_email);
		},
		change: function (event, ui) {
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
				$("#teamPlayerId1").val(ui.item.playerId);
				$("#captain").val(ui.item.value);
				$("#captainId").val(ui.item.playerId);
				$("#affiliatedclub").val(ui.item.club_id);
				$("#contactno").val(ui.item.contactno);
				$("#contactemail").val(ui.item.user_email);
				let $team1 = $("#teamPlayer1").val();
				let $team = '';
				if ($("#teamPlayer2").val() == '') {
					let $team2 = $("#teamPlayer2").val();
					$team = $team1 + ' / ' + $team2;
				} else {
					$team = $team1;
				}
				$("#team").val($team);
			}
		}
	});
	$('#teamPlayer2').autocomplete({
		minLength: 2,
		source: function (request, response) {
			response(get_player_details(request.term));
		},
		select: function (event, ui) {
			$("#teamPlayerId2").val(ui.item.id);
		},
		change: function (event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#teamPlayerId2").val('');
				$("#team").val('');
			} else {
				$("#teamPlayerId2").val(ui.item.playerId);
				let $team1 = $("#teamPlayer1").val();
				let $team2 = $("#teamPlayer2").val();
				let $team = $team1 + ' / ' + $team2;
				$("#team").val($team);
			}
		}
	});
	$('#captain').autocomplete({
		minLength: 2,
		source: function (request, response) {
			club = $("#affiliatedclub").val();
			response(get_player_details(request.term, club));
		},
		select: function (event, ui) {
			$("#captain").val(ui.item.value);
			$("#captainId").val(ui.item.playerId);
			$("#contactno").val(ui.item.contactno);
			$("#contactemail").val(ui.item.user_email);
		},
		change: function (event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#captain").val('');
				$("#captainId").val('');
				$("#contactno").val('');
				$("#contactemail").val('');
			} else {
				$("#captain").val(ui.item.value);
				$("#captainId").val(ui.item.playerId);
				$("#contactno").val(ui.item.contactno);
				$("#contactemail").val(ui.item.user_email);
			}
		}
	});
	$('#match_secretary_name').autocomplete({
		minLength: 2,
		source: function (request, response) {
			club = $("#club_id").val();
			response(get_player_details(request.term, club));
		},
		select: function (event, ui) {
			$("#match_secretary_name").val(ui.item.value);
			$("#match_secretary").val(ui.item.playerId);
			$("#match_secretary_contact_no").val(ui.item.contactno);
			$("#match_secretary_email").val(ui.item.user_email);
		},
		change: function (event, ui) {
			if (ui.item === null) {
				$(this).val('');
				$("#match_secretary_name").val('');
				$("#match_secretary").val('');
				$("#match_secretary_contact_no").val('');
				$("#match_secretary_email").val('');
			} else {
				$("#match_secretary_name").val(ui.item.value);
				$("#match_secretary").val(ui.item.playerId);
				$("#match_secretary_contact_no").val(ui.item.contactno);
				$("#match_secretary_email").val(ui.item.user_email);
			}
		}
	});

	$("#teamPlayerFrm").submit(function (event) {
		let $error = false;
		let $msg = '';
		if ($("#team").val() == '') {
			$error = true;
			$msg += 'Team name not set\n';
		} else if ($("#team_id").val() == '') {
			$.ajax({
				type: 'POST',
				datatype: 'json',
				url: ajaxurl,
				async: false,
				data: {
					"name": $("#team").val(),
					"action": "racketmanager_check_team_exists",
					"security": ajax_var.ajax_nonce,
				},
				success: function (response) {
					if (response.data) {
						$error = true;
						$msg += 'Team already exists\n';
					}
				},
				error: function (data) {
					$error = true;
					$msg += 'Error with team name check\n';
				}
			});
		}
		if ($("#teamPlayerId1").val() == '') {
			$error = true;
			$msg += 'Player 1 not set\n';
		}
		if ($("#teamPlayerId2").length) {
			if ($("#teamPlayerId2").val() == '') {
				$error = true;
				$msg += 'Player 2 not set\n';
			}
		}
		if ($("#affiliatedclub").val() == '') {
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

tb_init('a.thickbox, area.thickbox, input.thickbox');

function activaTab(tab) {
	jQuery('.navbar-nav button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function setIframeHeight(id) {
	let ifrm = document.getElementById(id);
	let doc = ifrm.contentDocument ? ifrm.contentDocument : ifrm.contentWindow.document;
	ifrm.style.visibility = 'hidden';
	ifrm.style.height = "10px"; // reset to minimal height ...
	// IE opt. for bing/msn needs a bit added or scrollbar appears
	ifrm.style.height = getDocHeight(doc) + 4 + "px";
	ifrm.style.visibility = 'visible';
}
function getDocHeight(doc) {
	doc = doc || document;
	// stackoverflow.com/questions/1145850/
	let body = doc.body, html = doc.documentElement;
	let height = Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
	return height;
}
function showPlayerClubs(link) {
	let id = link.id;
	let split_id = id.split('_');
	let player = split_id[1];
	let msg = '';
	jQuery.ajax({
		url: ajax_var.url,
		datatype: 'json',
		type: "POST",
		async: false,
		data: {
			'player': player,
			"action": "racketmanager_get_player_clubs",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = '';
			message = 'Player not linked to clubs';
			if (response.data) {
				if (response.data.length > 0) {
					message = '';
					let clubs = response.data;
					for (let club of clubs) {
						message += club.name + '<br />';
					}
				}
			}
			msg = message;
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data[0];
				for (let errorMsg of response.responseJSON.data[1]) {
					message += '<br />' + errorMsg;
				}
				msg = message;
			} else {
				msg = response.statusText;
			}
		}
	});
	return msg;
}
function get_player_details(name, club = null) {
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajaxurl,
		async: false,
		data: {
			"name": name,
			"affiliatedClub": club,
			"security": ajax_var.ajax_nonce,
			"action": "racketmanager_get_player_details",
		},
		success: function (data) {
			response = (JSON.parse(data.data));
		},
		error: function (response) {
			alert("Ajax error on getting leagues");
		}
	});
	return response;
}
