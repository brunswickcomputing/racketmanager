jQuery(document).ready(function($) {

	/* hide top-links in documentation */
	jQuery(".top-link").css("display", "none");

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
			$("#teamPlayerId1").val(ui.item.playerId);
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
				$("#teamPlayerId1").val(ui.item.playerId);
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
				$("#teamPlayerId2").val(ui.item.playerId);
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
	
	$("#teamPlayerFrm").submit(function( event ) {
		let $error = false;
		let $msg = '';
		if ( $("#team").val() == '' ) {
			$error = true;
			$msg += 'Team name not set\n';
		} else {
			if ( $("#team_id").val() == '' ) {
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

function activaTab(tab) {
    jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
		jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function setIframeHeight(id) {
    var ifrm = document.getElementById(id);
    var doc = ifrm.contentDocument? ifrm.contentDocument:
        ifrm.contentWindow.document;
    ifrm.style.visibility = 'hidden';
    ifrm.style.height = "10px"; // reset to minimal height ...
    // IE opt. for bing/msn needs a bit added or scrollbar appears
    ifrm.style.height = getDocHeight( doc ) + 4 + "px";
    ifrm.style.visibility = 'visible';
}
function getDocHeight(doc) {
    doc = doc || document;
    // stackoverflow.com/questions/1145850/
    var body = doc.body, html = doc.documentElement;
    var height = Math.max( body.scrollHeight, body.offsetHeight,
        html.clientHeight, html.scrollHeight, html.offsetHeight );
    return height;
}
