var Game = {

	state: {},
	me: 0,

	debug_opts: {
		show_redraw: false
	},

	start: function(params) {

		// request game state from server
		$.ajax({
			url : "/ajax/start-game",
			dataType : "JSON",
			success : function (data) {
				Game.handleGameState(data);
				Game.play();
			}
		});
	},

	handleGameState: function(data) {
		this.state = data;
		Game.toPrettyState();
	},

	play: function() {
		if (this.state.turn == this.me) {
			this.startTurn();
		}
		else {
			this.showBusy();
		}
	},

	startTurn: function() {
		console.log("It is your turn");
	},

	showBusy: function() {
		console.log("Player " + this.state.players.name + " is taking their turn");
	},

	rollDice: function() {
		$.ajax({
			url : "/ajax/roll-dice",
			dataType : "JSON",
			success : function (data) {
				Game.handleRollDice(data);
			}
		});
	},

	handleRollDice: function(data) {
		this.state = data.state;
		var position = this.getMe().position;

		console.log("You rolled a " + data.roll);
		console.log("You landed on " + Board.names[position]);

		this.movePiece(this.me, data.position);
		this.toPrettyState();
	},

	movePiece: function(player, position) {
		// animate piece
		//this.state.players[this.player].position = position;
	},

	drawCard: function(type) {
	},

	getMe: function() {
		return this.state.players[this.me];
	},

	buyProperty: function() {
		var position = this.getMe().position;

		if (Board.costs[position] > this.me.money) {
			console.log("You don't have enough money to buy " + Board.names[position]);
		}
		else {
			$.ajax({
				url : "/ajax/buy-property",
				dataType : "JSON",
				success : function (data) {
					if (data.success) {
						console.log("Your have bought " + Board.names[position]);
					}
				}
			});
		}
	},

	endTurn: function() {
		$.ajax({
			url : "/ajax/end-turn",
			dataType : "JSON",
			success : function (data) {
				// maybe start polling?
				Game.state = data;
				console.log("Your turn is over");
				Game.toPrettyState();
			}
		});
	},

	toPrettyState: function() {
		var table = $("table.info");

		for(var i = 0; i < this.state.players.length; i++) {

			var name = this.state.players[i].name;

			if (i == this.state.turn) {
				name = "<b>" + name + "</b>";
			}
			table.find(".player_name_" + i).html(name);


			var pos = this.state.players[i].position;

			table.find(".p" + i + "_on").text(Board.names[pos]);
		}

	}


};