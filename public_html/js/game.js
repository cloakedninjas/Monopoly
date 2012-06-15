var Game = {

	state: {},
	me: 0,
	logIndex: 0,
	requestListen: false,

	init: function() {
		return;
		// setup the starting state
		$.ajax({
			url : "/ajax/" + url,
			dataType : "JSON",
			data: params,
			success : function (data) {

			}
		});
	},

	sendCommand: function(cmd) {
		this.makeRequest("user-command", {cmd: cmd});
	},

	makeRequest: function(url, params) {
		if (params != null) {
			params.log_index = this.logIndex;
		}

		$.ajax({
			url : "/ajax/" + url,
			dataType : "JSON",
			data: params,
			success : function (data) {
				//console.log(data);

				if (data != null) {
					if (data.success) {
						Game.logIndex += data.log.length;
						Game.parseResponse(data.log);

						// begin long poll if response tells us to
						if (data.start_listen) {
							Game.beginListen();
						}
					}
				}
			},
			error: function(a,b,c) {
				$("#debug").html(c);
			}
		});
	},

	parseResponse: function(log) {
		for(var i = 0; i < log.length; i++) {
			var cmd = log[i].cmd;

			if (log[i].params != null) {
				var params = log[i].params.split(",");
			}

			switch (cmd) {
				case "game_start":
					break;

				case "player_to_start":
					break;

				case "roll_dice":
					$(".dice1").text(params[1]);
					$(".dice2").text(params[2]);
					break;

				case "player_landed_on":
					$(".p" + params[0] + "_on").text(Board.names[params[1]]);
					break;
			}

			$("#debug").append("<p>" + cmd + "</p>");
		}
	},

	beginListen: function() {
		if (this.requestListen) {
			// already listening
			return false;
		}
		this.requestListen = true;

		$.ajax({
			url : "/ajax/game-listen",
			dataType : "JSON",
			data: {timeout: 30},
			success : function (data) {
				Game.requestListen = false;
				console.log(data);

				// do we listen again?
			},
			error: function(a,b,c) {
				Game.requestListen = false;
			}
		});
	}


};