
jQuery(function(){
	
	var request = {
		trees : [], 
		links : [], 
		lists : [] 
	};
	
	jQuery('.bugzillalink').each(function( index, ele ){
		request.links.push( jQuery(ele).data() );
	});

	jQuery('.bugzillatree').each(function( index, ele ){
		request.trees.push( jQuery(ele).data() );
	});

	jQuery('.bugzillalist').each(function( index, ele ){
		request.lists.push( jQuery(ele).data() );
	});
	
	if ( request.links.length + request.trees.length + request.lists.length > 0 ) {
		fetchData( [{links:request.links}, {lists:request.lists}, {trees:request.trees}] );
	}
	
	
	function fetchData( requests ) {
		var req = jQuery.extend( {call:'plugin_bugzillaint_fetch'}, requests.shift() );
		jQuery.post( DOKU_BASE + 'lib/exe/ajax.php', req )
		.done( handleData )
		.done(function(){
			if ( requests.length > 0 ) fetchData( requests );
		})
		.fail( handleError );
	}
	
	function handleError( a ) {
		if ( a.readyState == 0 ) return;
		var msg = a.status + " " + a.statusText + "\n\n" + a.responseText;
		alert( msg );
	}
	
	function handleData( data ) {
		if ( data.lists && data.lists.length > 0 ) {
			jQuery('.bugzillalist').each(function( index, ele ){
				var master = jQuery(ele).removeClass('loading');
				var bugs = data.lists[index];
				var groupBy = master.data('group_by');
				
				master.toggleClass('empty', bugs.length == 0 );
				
				for (var i=0, group=null; i < bugs.length; i++) {
					
					if ( groupBy && group != bugs[i][groupBy] ) {
						group = bugs[i][groupBy];
						jQuery('<li class="group-headline"></li>')
							.toggleClass('group-headline-first', i==0)
							.append( ' – ' + group + ' – ' )
							.appendTo( master.find('ul') );
					}
					
					jQuery('<li></li>')
						.append( renderBug( master, bugs[i] ) )
						.appendTo( master.find('ul') );
				}
			});
		}
		if ( data.links && jQuery.isEmptyObject(data.links) == false ) {
			jQuery('.bugzillalink').each(function( index, ele ){
				var master = jQuery(ele).removeClass('loading');
				var bug = data.links[ master.data('id') ];
				master.find('a.bzref').replaceWith( renderBug( master, bug ) );
			});
		}
		if ( data.trees && jQuery.isEmptyObject(data.trees) == false ) {
			jQuery('.bugzillatree').each(function( index, ele ){
				var master = jQuery(ele).removeClass('loading');
				var rootBug = data.trees[ master.data('id') ];
				master.find('a.bzref').replaceWith( renderBug( master, rootBug ) );
				renderTree( rootBug.depends_on, master.find('ul'), master, master.data('depth') );
				master.toggleClass('empty', master.find('a.bzref').length <= 1 );
			});
		}
	}
	
	
	function renderBug( master, bug ) {
		
		// bzref
		var a = jQuery('<a></a>')
			.addClass('bzref')
			.attr('href', master.attr('bugzilla_baseurl') + '/show_bug.cgi?id=' + bug.id);
		if ( bug.error ) {
			a.addClass( 'bz-error' );
			a.text( bug.id + ": " + bug.error );
		} else {
			if ( bug.resolution ) a.addClass( 'bz-resolved' );
			a.text( bug.id + ": " + bug.summary );
		}
		
		// bzextra
		var extra = renderExtras( master, bug );
		
		return ( extra.children().length > 0 ) ? a.add( extra ) : a;
	}
	
	
	function renderExtras( master, bug ) {
		var extras = master.data('extras').split(' ').join('').split(',');
		
		// add properties with warnings to extras if they are not set
		var warnings = getBugWarnings( master, bug );
		for ( var property in warnings ) {
			if ( extras.indexOf( property ) == -1 ) {
				extras.unshift( property );
			}
		}
		
		// render items
		var bzextra = jQuery('<small class="bzextra"></span></small>');
		jQuery.each( extras, function(index,val){
			renderExtra( val, master, bzextra, bug );
		});
		
		return bzextra;
	}
	
	
	function renderExtra( extra, master, parent, bug ) {

		var groupBy = master.data('group_by');
		var e;
		
		if ( extra == 'assigned_to' && groupBy != 'assigned_to' ) {
			e = jQuery('<a></a>');
			e.attr('href', master.attr('bugzilla_baseurl') + '/buglist.cgi?bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&emailassigned_to1=1&emailtype1=substring&query_format=advanced&email1=' + encodeURIComponent(bug.assigned_to) );
			e.attr('title', 'assigned to ' + bug.assigned_to);
			e.append( bug.assigned_to );
		}

		if ( extra == 'classification' && groupBy != 'classification' ) {
			e = jQuery('<a></a>');
			e.attr('href', master.attr('bugzilla_baseurl') + '/buglist.cgi?bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&query_format=advanced&classification=' + encodeURIComponent(bug.classification) );
			e.attr('title', 'Classification: ' + bug.classification);
			e.append( bug.classification );
		}

		if ( extra == 'product' && groupBy != 'product' ) {
			e = jQuery('<a></a>');
			e.attr('href', master.attr('bugzilla_baseurl') + '/describecomponents.cgi?product=' + encodeURIComponent(bug.product) );
			e.attr('title', 'Product: ' + bug.product);
			e.append( bug.product );
		}

		if ( extra == 'component' && groupBy != 'component' ) {
			e = jQuery('<a></a>');
			e.attr('href', master.attr('bugzilla_baseurl') + '/buglist.cgi?resolution=---&component=' + encodeURIComponent(bug.component) + (bug.product?'&product='+encodeURIComponent(bug.product):'') );
			e.attr('title', 'Component: ' + bug.component);
			e.append( bug.product );
		}

		if ( extra == 'lastchange' && groupBy != 'last_change_time' && bug.last_change_time ) {
			e = jQuery('<span title ></span>');
			e.attr('title', 'last changed on ' + bug.last_change_time);
			e.append( "MOD " + bug.last_change_time );
		}

		if ( extra == 'time' && bug.actual_time && bug.actual_time > 0 ) {
			e = jQuery('<span></span>');
			e.attr('title', 'actual time taken ' + bug.actual_time);
			e.append( "" + bug.actual_time + "h" );
		}

		if ( extra == 'deadline' && bug.deadline ) {
			e = jQuery('<span></span>');
			e.attr('title', 'deadline: ' + bug.deadline);
			e.append( 'DL ' + bug.deadline );
		}

		if ( extra == 'priority' && groupBy != 'priority' ) {
			e = jQuery('<span></span>');
			e.attr('title', 'priority: ' + bug.priority);
			e.append( bug.priority );
		}

		if ( extra == 'severity' && groupBy != 'severity' ) {
			e = jQuery('<span></span>');
			e.attr('title', 'severity: ' + bug.severity);
			e.append( bug.severity );
		}

		if ( extra == 'version' && groupBy != 'version' && bug.version && bug.version != 'unspecified' ) {
			e = jQuery('<span></span>');
			e.append( bug.version );
		}

		if ( extra == 'status' && groupBy != 'status' ) {
			e = jQuery('<span></span>');
			if ( bug.resolution ) {
				e.attr('title', 'status: ' + bug.status + ' / ' + bug.resolution );
				e.addClass('bz-label-' + master.attr( 'color_resolved_' + bug.resolution.toLowerCase() ) );
				e.append(bug.resolution);
			} else {
				e.addClass('bz-label-' + master.attr( 'color_' + bug.status.toLowerCase() ) );
				e.append( bug.status );
			}
		}

		if ( extra == 'dependencies' ) {
			e = jQuery('<a></a>')
				.attr('href', master.attr('bugzilla_baseurl') + '/showdependencytree.cgi?hide_resolved=0&id=' + bug.id);
			if (bug.depends_on_resolved && bug.depends_on_resolved.length < bug.depends_on.length) {
				e.append(
					master.attr('extra_depends_on').split('{0}').join(bug.depends_on.length - bug.depends_on_resolved.length)
				);
			}
			if (bug.blocks_resolved && bug.blocks_resolved.length < bug.blocks.length) {
				if (e.text().length > 0) {
					e.append(', ');
				}
				e.append(
					master.attr('extra_blocks').split('{0}').join(bug.blocks.length - bug.blocks_resolved.length)
				);
			}
		}
		
		if ( e && e.text().length > 0 ) {
			
			// check if warning exists
			var warnings = getBugWarnings( master, bug );
			if ( warnings.hasOwnProperty(extra) ) {
				e.addClass('bz-label-' + warnings[extra].color );
			}
			
			// add extra to parent
			parent.append( e );
		}
		
	}
	


	function renderTree( bugs, parent, master, depth ) {
		
		// exit if depth is extended
		if ( depth-- < 1 ) return;
		
		// created sorted array of bugs
		var l = [];
		for (var k in bugs) {
			l.push( bugs[k] );
		}
		l.sort(function(a,b){
			if ( parseInt(a.id) < parseInt(b.id) ) return -1; 
			if ( parseInt(a.id) < parseInt(b.id) ) return 1;
			return 0;
		});
		
		for (var i=0; i < l.length; i++) {
			var bug = l[i];
			
			if ( master.data('hideresolved') && bug.status == "RESOLVED" ) continue;
			
			var li = jQuery('<li></li>')
				.append( renderBug( master, bug ) )
				.appendTo( parent );
			
			if ( jQuery.isEmptyObject( bug.depends_on ) == false ) {
				var ul = jQuery('<ul></ul>').appendTo( li );
				renderTree( bug.depends_on, ul, master, depth );
			}
			
		}
	}
	

	function getBugWarnings( master, bug ) {
		
		if ( bug.hasOwnProperty('warnings') ) {
			return bug.warnings;
		}
		
		bug.warnings = {};
		
		if ( !bug.resolution && bug.severity ) {
			var red = master.attr('severity_threshold_red');
			var orange = master.attr('severity_threshold_orange');
			
			var s = ['blocker', 'major', 'normal', 'minor', 'enhancement'];
			if ( s.indexOf(bug.severity) <= s.indexOf(red) ) {
				bug.warnings.severity = { color:'red', property:'severity' };
			} else if ( s.indexOf(bug.severity) <= s.indexOf(orange) ) {
				bug.warnings.severity = { color:'orange', property:'severity' };
			}
		}
		
		if ( !bug.resolution && bug.priority ) {
			var red = master.attr('priority_threshold_red');
			var orange = master.attr('priority_threshold_orange');

			var p = parseInt(bug.priority.split('P')[1]);
			var r = parseInt(red.split('P')[1]);
			var o = parseInt(orange.split('P')[1]);
			if ( p <= r ) {
				bug.warnings.priority = { color:'red', property:'priority' };
			} else if ( p <= o ) {
				bug.warnings.priority = { color:'orange', property:'priority' };
			}
		}
		
		if ( !bug.resolution && bug.deadline ) {
			var red = master.attr('deadline_threshold_days_red');
			var orange = master.attr('deadline_threshold_days_orange');

			var p = bug.deadline.split('-');
			var deadlineTime = new Date(p[0], p[1]-1, p[2], 0, 0, 0, 0).getTime();
			var nowTime = new Date().getTime();
			if ( nowTime > deadlineTime - (red*24*60*60*1000) ) {
				bug.warnings.deadline = { color:'red', property:'deadline' };
			} else if ( nowTime > deadlineTime - (orange*24*60*60*1000) ) {
				bug.warnings.deadline = { color:'orange', property:'deadline' };
			}
		}
		
		return bug.warnings;
	}
	
	
});
