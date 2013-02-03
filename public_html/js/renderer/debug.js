function Renderer() {
    this.name = 'debug';
	Game.setDebugMode(true);

    this.renderAction = function(cmd, params) {
		console.info(cmd, params);
        //switch on cmd
    };

    this.renderGameState = function(state) {
		this.log('Received game state');
		this.log(state);

		// Who's turn is it
		$('table.info td.player_turn').text('');
		$('table.info td.turn_' + state.player_turn).html('&bull;');

		// Player cash / goojf
		for (var i = 0; i < state.players.length; i++) {
			$('table.info td.money_' + i).text(state.players[i].cash);
			$('table.info td.goojf_' + i).text(state.players[i].goojf_count);
		}






    };

	this.log = function(msg) {
		Game.log(msg, Game.LOG_DEBUG);
	}
};