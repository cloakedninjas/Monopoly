var Game = {

	LOG_ERROR: 1,
	LOG_INFO: 2,
	LOG_DEBUG: 3,

	debug_mode: false,
	renderer: null,
	state: {},
	log_index: 0,
	listen_in_progress: false,

	init: function () {
		if (typeof Renderer == 'undefined') {
			this.log('No renderer is defined', this.LOG_ERROR);
		}
		this.renderer = new Renderer();
		this.getGameState();
	},

	getGameState: function() {
		this.makeRequest("get-game-state");
	},

	sendCommand: function(cmd) {
		this.makeRequest("user-command", {cmd: cmd});
	},

	makeRequest: function(request, params) {
		if (params == null) {
			params = {};
		}
		params.log_index = this.log_index;

		$.ajax({
			url : "/ajax/" + request,
			dataType : "JSON",
			data: params,
			success : function (data) {
				if (data != null) {
					if (data.success) {
						Game.log_index = data.new_log_index;
						Game.parseResponse(request, data.state);

						// begin long poll if response tells us to
						if (data.start_listen) {
							Game.beginListen();
						}
					}
				}
			},
			error: function(a,b,c) {
				console.log(c);
			}
		});
	},

	parseResponse: function(request, data) {
		if (request == 'get-game-state') {
			this.renderer.renderGameState(data);
		}
		else {
			this.renderer.renderAction(request, actions);
		}


		// append to log?
		// set game state?
	},

	beginListen: function() {
		if (this.listen_in_progress) {
			return false;
		}
		this.listen_in_progress = true;

		$.ajax({
			url : "/ajax/game-listen",
			dataType : "JSON",
			data: {timeout: 30},
			success : function (data) {
				Game.listen_in_progress = false;
				console.log(data);

				// do we listen again?
			},
			error: function(a,b,c) {
				Game.listen_in_progress = false;
			}
		});
	},

	setDebugMode: function(mode) {
		this.debug_mode = mode;
	},

	log: function(msg, severity) {
		if (typeof console == 'undefined') {
			return false;
		}
		if (severity == this.LOG_ERROR) {
			console.error(msg);
		}
		else if (severity == this.LOG_DEBUG && this.debug_mode) {
			console.info(msg);
		}
		else {
			console.log(msg);
		}
	}
};