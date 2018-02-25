(function($){
	
	/*! FF WP Rest API Ajax Loader v1.0 */
	$.fn.ff_wp_restapi_ajax_loader = function(options){
		
		var instance = this,
			$this = $(this);
			
		var settings = $.extend({
			
			post_type: $this.data('post_type'),
			per_page: $this.data('per_page'),
			page: $this.data('page'),
			base_url: $this.data('base_url'),
			
			template: $($this.data('template')).html(),
			loading_html: $this.find('.loading'),
			load_more_button: $this.find('.load-more-button'),
			load_more_button_container: $this.find('.load-more-button').parent(),
			no_more_results_html: $this.find('.no-more-results'),
			results_container: $this.find('.results-container'),
			
			display_method: 'append', // 'append' or 'replace'
			load_on_init: true,
			
			append_delay: 0,
			fade_speed: 400,
			slide_down_speed: 400,
			height_animation_speed: 250,
			
			on_complete: null,
			on_append_complete: null,
			on_init: null,
		}, options );
		
		
		var $results_container = settings.results_container,
			 $loading = settings.loading_html,
			 $load_more = settings.load_more_button_container,
			 $load_more_btn = settings.load_more_button,
			 $no_more_results = settings.no_more_results_html;
			 
		function init(){
			
			$this.addClass('ff-restapi-ajax-loader-init');
			
			$loading.hide();
			
			// Load posts on init
			if( settings.load_on_init ) loadMore();
			
			// On load more button click
			settings.load_more_button.click(function(e){
				e.preventDefault();
				loadMore();
			});
			
			// On init callback
			if( typeof settings.on_init === 'function' ) settings.on_init();
		}
		
		init();
		
		function loadMore(){
			
			displayLoading('show');
			
			var base_url = settings.base_url.replace(/\/$/, ''); // remove trailing slash
			var query_url = base_url +'/wp-json/wp/v2/'+ settings.post_type +'?_embed';
			
			if( settings.per_page ) {
				// posts per page
				query_url += '&per_page='+ settings.per_page;
			}
			
			if( settings.page ) {
				// pagination
				query_url += '&page='+ settings.page;
			}
			
			console.log(query_url);
			
			$.ajax({
				type: 'GET',
				url: query_url,
				dataType: 'json',
				success: function(data){
					
					console.log('Data', data); // debug point 2
					
					displayLoading('hide');
					
					$this.data('page', settings.page++); // Update pagination
					
					if( data.length ) {
						// Have posts
						if( settings.display_method === 'append' ) {
							// Append content
							showItems(data);
							
						} else {
							// Replace content
							$results_container.html('');
							showItems(data);
						}
					}
					
					if( data.length < settings.per_page ) {
						// No more posts
						$load_more.fadeOut(100);
						$no_more_results.fadeIn(100);
						updateResultContainerHeight();
					}
					
				},
				error: function(request, status, error){
					console.log('status : ', status);
					console.log('error: : ', error);
					displayLoading('hide');
					$load_more.fadeOut(100);
					$no_more_results.fadeIn(100);
				}
			}); // $.ajax */
			
		} // loadMore()
		
		function showItems(data){
			var nItems = data.length;
			$.each(data, function(index){
				var $post = $(this)[0];
				var item = Mustache.render(settings.template, $post);
				appendItems(item, index, nItems);
			});
		}
		
		function displayLoading(state){
			if( state === 'show' ) {
				$loading.fadeIn(100);
				$load_more.fadeOut(100);
			} else {
				$loading.fadeOut(100);
				$load_more.fadeIn(100);
			}
		}
		
		function updateResultContainerHeight(){
			var origH = $results_container.height();
			$results_container.height('auto');
			var newH = $results_container.height();
			if( newH < origH ) {
				$results_container.height(origH);
				if( settings.slide_down_speed === 0 ) {
					$results_container.height('auto');
				} else {
					$results_container.animate({'height': newH}, settings.height_animation_speed, function(){
						$results_container.height('auto');
					});
				}
			}
		}
		
		function appendItems(item, index, nItems){
			var $item = $(item),
				isLastItem = false;
				
			if( index + 1 === nItems ) {
				isLastItem = true;
			}
			
			$item.appendTo($results_container);
			
			// On append item callback
			if (typeof settings.on_append_complete === 'function') {
				settings.on_append_complete();
			}
			
			var origH = $results_container.height();
			$results_container.height('auto');
			var newH = $results_container.height();
			if( newH < origH ) {
				$results_container.height(origH);
			}
			
			$item
				.hide()
				.css({'opacity': 0})
				.slideDown(settings.slide_down_speed)
				.delay(index*settings.append_delay)
				.animate({'opacity': 1}, settings.fade_speed, function(){
					if(isLastItem) {
						updateResultContainerHeight();
						// On load complete callback
						if (typeof settings.on_complete === 'function') {
							settings.on_complete();
						}
					}
				});
		}
		return this;
	}
	
	// Initalize instances
	$('.ff-restapi-ajax-loader').each(function(){
		$(this).ff_wp_restapi_ajax_loader();
	});
	
})(jQuery)