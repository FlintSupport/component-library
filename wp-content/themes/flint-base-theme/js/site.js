$(document).ready(function() {

	//MOBILE MENU FUNCTIONS
	$(document).on('click','#mobile-toggle',function(e){
		$('#mobile-toggle, #menuOpen, #menuClose, #mobile-menu, body').toggleClass('active'); var open=$('#mobile-menu').hasClass('active'); $('.mobileMenu').attr('aria-expanded',open).attr('aria-label',open?'Close menu':'Open menu');
	});
	$(document).on('click','#mobile-menu li.menu-item-has-children > a',function(e){
		e.preventDefault();
		$parent = $(this).parent('li');
		$child = $($parent).find('li');
		$parent.toggleClass('expanded').removeClass('hidden');
		$('#mobile-menu.active li').not($parent).not($child).toggleClass('hidden').removeClass('expanded');
		$('#mobile-menu').toggleClass('expanded');
		return false;
	});

	//CTA NAV FUNCTIONS
	$('#menu2 #main-menu').on('click', '.mega-sub-menu li.mega-menu-item-has-children', function(){
		$("li.mega-disable-link").removeClass('active');
		$(".mega-menu-row").removeClass('active');
		$(".mega-menu-item").removeClass('active');
		$(this).addClass('active');
		$(this).closest('.mega-menu-row').addClass('active');
		$(this).closest('.mega-toggle-on').addClass('active');
	});

	//SEARCH BUTTON
	$(document).on('click','#searchButton',function(e){
		$(this).toggleClass('active');
		$('#searchform').toggleClass('active'); var open=$('#searchform').hasClass('active'); $(this).attr('aria-expanded',open); if(open){$('#searchform').find('input[type=search]').trigger('focus');}
	});

	//SLIDERS
	if ($.fn.slick) {
		$('.testimonialSlider').slick({
			autoplay: false,
			dots: true,
			arrows: true,
			infinite: true,
			adaptiveHeight: true,
			speed: 300,
			fade: true,
			cssEase: 'ease-out',
		});
		$('.slider-single').slick({
			autoplay: false,
			dots: true,
			arrows: true,
			infinite: true,
			adaptiveHeight: true,
			speed: 300,
			fade: true,
			cssEase: 'ease-out',
			responsive: [
				{
				  breakpoint: 1000,
				  settings: {
					arrows: true,
				  }
				},
			]
		});
		$('.slider-multi').slick({
			dots: true,
			arrows: true,
			infinite: true,
			adaptiveHeight: true,
			speed: 300,
			slidesToShow: 4,
			slidesToScroll: 4,
			cssEase: 'ease-out',
			responsive: [
				{
				breakpoint: 1000,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 2,
					dots: true
				}
				},
				{
				breakpoint: 480,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1,
					dots: true
				}
				}
			]
		});
		$('.video-slider').slick({
			dots: false,
			arrows: true,
			infinite: true,
			speed: 300,
			slidesToShow: 4,
			slidesToScroll: 4,
			cssEase: 'ease-out',
			responsive: [
				{
				breakpoint: 1000,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 2
				}
				},
				{
				breakpoint: 600,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1
				}
				}
			]
		});
	}

	//VIDEO CAROUSEL FUNCTIONS
	$(".videoCarousel .video-trigger").click(function(){
		$slideid = this.id.replace('slide-','');
		var $popup=$('#popup-' + $slideid); $popup.removeAttr('hidden').addClass('active').data('trigger',this); $popup.find('.close').trigger('focus');
	});
	$(".videoCarousel .close").click(function(){
		var $popup=$(this).closest('.popup'); $popup.removeClass('active').attr('hidden',true); var trigger=$popup.data('trigger'); if(trigger){trigger.focus();}
	});


	//ON LOAD DETAILS FOR ACCORDIONS AND TABS
	window.onload = function() {
		$('.open .accordionItem:first-of-type .accordionTitle').addClass('active').css('display', 'flex');
		$('.open .accordionItem:first-of-type .accordionCopy').addClass('active').css('display', 'block');
		$('.open > .accordionTitle, .open .topTabs > .tabTitle, .open .sideTabs > .tabTitle').addClass('active');
		$('.open > .accordionTitle ~ .accordionTitle, .open .topTabs > .tabTitle ~ .tabTitle, .open .sideTabs > .tabTitle ~ .tabTitle').removeClass('active');
		$('.open .tabContent > .tabCopy').addClass('active');
		$('.open .tabContent > .tabCopy ~ .tabCopy').removeClass('active');
		$('.tabItems [role=tab]').attr({'aria-selected':'false','tabindex':'-1'}); $('.tabItems [role=tablist]').each(function(){var $first=$(this).find('[role=tab]').first();$first.attr({'aria-selected':'true','tabindex':'0'}).addClass('active');}); $('.tabItems [role=tabpanel]').attr('hidden',true); $('.tabItems [role=tabpanel].active').removeAttr('hidden'); $('.accordionTitle.active').attr('aria-expanded','true').each(function(){$('#'+$(this).attr('aria-controls')).removeAttr('hidden');});
	}

	//ACCORDION FUNCTION
	$(document).on('click', '.accordionItems .accordionTitle', function(){
		var $button=$(this), $panel=$('#'+$button.attr('aria-controls')), expanded=$button.attr('aria-expanded')==='true';
		$button.attr('aria-expanded', String(!expanded)).toggleClass('active', !expanded);
		$panel.prop('hidden', expanded).toggleClass('active', !expanded);
	});

	//TAB FUNCTION
	var tabs = function(){
		$tabid = this.id.replace('tabTitle-','');
		$tabItems = this.closest('.tabItems').id.replace('tabItems-','');
		$('.tabTitle-' + $tabItems).removeClass('active').attr({'aria-selected':'false','tabindex':'-1'});
		$(this).addClass('active').attr({'aria-selected':'true','tabindex':'0'});

		$(".tabCopy").each(function(){
		$contentid = this.id.replace('tabCopy-','');
		
		if($contentid === $tabid){
			$('.tabCopy-' + $tabItems).not('#tabCopy-' + $contentid).removeClass('active').attr('hidden',true);
			$('#tabCopy-' + $contentid).addClass('active').removeAttr('hidden');
		}
		});
	};
	$(".tabItems .topTabs .tabTitle").click(tabs);
	$(".tabItems .sideTabs .tabTitle").click(tabs);
	$(document).on('keydown', '[role=tab]', function(e){ if(!['ArrowLeft','ArrowRight','Home','End'].includes(e.key))return; e.preventDefault(); var $tabs=$(this).closest('[role=tablist]').find('[role=tab]'), i=$tabs.index(this); if(e.key==='Home')i=0; else if(e.key==='End')i=$tabs.length-1; else i=(i+(e.key==='ArrowRight'?1:-1)+$tabs.length)%$tabs.length; $tabs.eq(i).trigger('focus').trigger('click'); });
	$(document).on('keydown', '[role=dialog]', function(e){ if(e.key==='Escape')$(this).find('.close').trigger('click'); });


	//ALERT BAR COOKIE

	// If the 'hide cookie is not set we show the message
	if (!readCookie('notice')) {
		$('#alert').addClass('visible');
	} else {
		$('#alert').hide();
	}
	
	// Add the event that closes the popup and sets the cookie that tells us to
	// not show it again until one day has passed.
	$('#noticeClose').click(function() {
		$('#alert').hide();
		createCookie('notice', true, 7);
		return false;
	});
	  
	// ---
	// And some generic cookie logic
	// ---
	function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
	}
	
	function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
	}
	
	function eraseCookie(name) {
	createCookie(name,"",-1);
	}
});