(function($) {

	$(function() {

		var Realogy = {
			
			totalProperties : 0, 
			
			totalAgents : 0, 
			
			countProperties : 0, 
			
			countAgents : 0, 
			
			saved_properties : [], 
			
			saved_agents : [],

			_log : function( message ) {
				
				$('.js-log').append( '<p>' + message + '</p>' );
				
				$(".js-log").scrollTop( $(".js-log")[0].scrollHeight );

			},

			_error : function( message ) {
				
				$('.js-log').append( '<p style="color:#f00">' + message + '</p>' );
				
				$(".js-log").scrollTop( $(".js-log")[0].scrollHeight );

			},

			_progress : function( percent ) {

				var total = $('.js-progress-bar').width();
				var current = $('.js-progress').width();

				var current_percent = current / total * 100;

				var new_width = ( current_percent + percent ) / 100 * total;

				$('.js-progress').width( new_width );

			},

			get_properties : function( callback ) {

				Realogy._log( 'Getting properties' );

				$.ajax({
					url : sircr_realogy.ajax_url,
					data : {
						action : 'sircr_realogy_get_properties'
					},
					success : function( result ) {

						Realogy._log( 'Got ' + result.length + ' properties back' );
						Realogy._progress( 5 );

						callback( result );

					}
				});

			},

			get_agents : function( callback ) {

				Realogy._log( 'Getting Agents' );

				$.ajax({
					url : sircr_realogy.ajax_url,
					data : {
						action : 'sircr_realogy_get_agents'
					},
					success : function( result ) {
						
						Realogy._log( 'Got ' + result.length + ' agents back' );
						Realogy._progress( 5 );

						callback( result );

					}
				});

			},

			save_property : function( id, progress, callback ) {

				return $.ajax({
					url : sircr_realogy.ajax_url,
					data : {
						action : 'sircr_realogy_save_property',
						id: id
					},
					success : function( result ) {

						Realogy.countProperties++;
						
						if(result.name)
							Realogy._log( Realogy.countProperties + '/' + Realogy.totalProperties + ' Property Saved: ' + result.name );
						else
							Realogy._error( Realogy.countProperties + '/' + Realogy.totalProperties + ' Property Not Found: ' + id );
						
						Realogy._progress( progress );

						callback( result );

					}
				});

			},

			save_agent : function( id, progress, callback ) {

				return $.ajax({
					url : sircr_realogy.ajax_url,
					data : {
						action : 'sircr_realogy_save_agent',
						id: id
					},
					success : function( result ) {

						Realogy.countAgents++;
						
						if(result.name)
							Realogy._log( Realogy.countAgents + '/' + Realogy.totalAgents + ' Agent Saved: ' + result.name );
						else
							Realogy._error( Realogy.countAgents + '/' + Realogy.totalAgents + ' Agent Not Found: ' + id );

						Realogy._progress( progress );

						callback( result );

					}
				});

			},

			done_refetching : function() {
				
				Realogy._log('All Done!');
			
				Realogy._progress(100);

			},

			disable_old_properties : function( properties ) {

				Realogy._log('Disabling old Properties');
				
				return $.ajax({
					url : sircr_realogy.ajax_url,
					type: 'post',
					data : {
						action : 'sircr_realogy_disable_old_properties',
						properties: properties
					},
					success : function( result ) {

						Realogy._progress(5);

					}
				});

			},

			disable_old_agents : function( agents ) {
				
				Realogy._log('Disabling old Agents');
				
				return $.ajax({
					url : sircr_realogy.ajax_url,
					type: 'post',
					data : {
						action : 'sircr_realogy_disable_old_agents',
						agents: agents
					},
					success : function( result ) {
						
						Realogy._progress(5);

					}
				});

			},

			done_updating_agents : function() {

				var async_request = [];
				
				async_request.push( Realogy.disable_old_properties( Realogy.saved_properties ) );
				async_request.push( Realogy.disable_old_agents( Realogy.saved_agents ) );
				
				$.when.apply(null, async_request).done( Realogy.done_refetching );

			},

			done_updating_properties : function() {

				Realogy.get_agents( function ( agents ) {
					
					var async_request = [];
					// Figure out how much to moove the progress bar per agent saved
					var progress = 40 / agents.length;
					
					Realogy.totalAgents = agents.length;
					
					for( var i in agents ) {
						async_request.push( Realogy.save_agent( agents[i].entityId, progress, function( ageids ) {
							if(ageids.es)
								Realogy.saved_agents.push( ageids.es );
							if(ageids.en)
								Realogy.saved_agents.push( ageids.en );
						}));
					}

					$.when.apply(null, async_request).done( Realogy.done_updating_agents );

				});
			},

			start : function() {
				Realogy.get_properties( function( properties ) {

					var async_request = [];
					// Figure out how much to moove the progress bar per propety saved
					var progress = 40 / properties.length;
					
					Realogy.totalProperties = properties.length;
					
					for( var i in properties ) {
						async_request.push( Realogy.save_property( properties[i].entityId, progress, function( propids ) {
							if(propids.es)
								Realogy.saved_properties.push( propids.es );
							if(propids.en)
								Realogy.saved_properties.push( propids.en );
						}));
					}

					$.when.apply(null, async_request).done( Realogy.done_updating_properties );

				});
			}

		};

		$('.js-refetch').on( 'click', function() {

			$('.js-progress-bar').show();
			$('.js-log').show();
			$('.js-refetch').hide();

			Realogy._log( 'Updating Properties' );

			Realogy.start();

			return false;

		});

	});

})(window.jQuery);