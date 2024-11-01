var Atfs;

(function($) {
	
	"use strict";
	
	Atfs = {
		
		config: {
			pauseScroll: false
		},
		
		init: function(config) {
			$.extend(this.config, config);
			
			this.scroll();
			
			this.mobile();
			
			this.load();
		},
		
		scroll: function() {
			jQuery(window).scroll(function() {
				if (!Atfs.config.pauseScroll) {
					if (jQuery(shortcode_atts.items+":visible:last").length) {
						if (jQuery(shortcode_atts.items+":visible:last").offset().top - (jQuery(window).scrollTop()+(jQuery(window).height()-200)) < 0 ) {
							if (shortcode_atts.paging) {
								jQuery(shortcode_atts.items+".atfs-show:hidden:lt("+((shortcode_atts.page_size)-1)+")").show();
							}
							else {
								jQuery(shortcode_atts.items+".atfs-show:hidden").show();
							}
						}
					}
				}
			});
		},
		
		mobile: function() {
			jQuery('.taxonomy-filter-mobile-select').on('change', function(ev) {
				var $select = $(this);
				
				window.location = $select.val();
			});
		},
		
		load: function() {
			
			if (shortcode_atts.paging) {
				jQuery(shortcode_atts.items+":gt("+(shortcode_atts.page_size-1)+")").hide();
			}
			jQuery(shortcode_atts.items).addClass('atfs-show');
			
			jQuery(".taxonomy-filter-checkbox").on('click', function(e) {
				var $filter = [],
					$this = $(this);
					
				Atfs.config.pauseScroll = true;
				jQuery(shortcode_atts.items).removeClass('atfs-show');
				
				if ($this.val() == "*") {
					jQuery(".taxonomy-filter-checkbox").attr("checked", false);
					$this.attr("checked", true);
				}
				else {
					jQuery(".taxonomy-filter-checkbox[value='*']").attr("checked", false);
					jQuery(".taxonomy-filter-checkbox").each(function(i,e) {
						var $check = $(e);
							
						if ($check.is(":checked")) {
							$filter.push($check.val());
						}
						
					});
				}
				if (shortcode_atts.alsohide != '') {
					jQuery(shortcode_atts.alsohide).hide();
				}
				
				if ($filter.length) {
					var $el = '',
						$found;
					jQuery(shortcode_atts.items).each(function(i, el) {
						$el = $(el),
						$found = false;
						$.each($filter, function(ind,v) {
							if (!$found) {
								if ($el.hasClass(v)) {
									$el.addClass("atfs-show");
									$found = true;
								}
								else {
									$el.fadeOut();
								}
							}
						});
					});
					
					jQuery(".atfs-show").fadeIn("normal", function() {
						if (shortcode_atts.paging) {
							jQuery(shortcode_atts.items+".atfs-show:gt("+(shortcode_atts.page_size-1)+")").hide(function() {
								Atfs.config.pauseScroll = false;
							});
						}
						else {
							Atfs.config.pauseScroll = false;
						}
					});
				}
				else {
					jQuery(shortcode_atts.items).show();
					if (shortcode_atts.paging) {
						jQuery(shortcode_atts.items+":gt("+(shortcode_atts.page_size-1)+")").hide();
					}
					jQuery(shortcode_atts.items).addClass('atfs-show');
					
					Atfs.config.pauseScroll = false;
				}
				
			});
		}
	}
	
	Atfs.init();
})(jQuery);