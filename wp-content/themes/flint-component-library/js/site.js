$(document).ready(function() {

	//HEADER FOOTER SWITCHER
	$("#header2, #header3, #footer2, #footer3, #footer4").hide();
	$(document).on('change','#switcher #header',function(e){
		$header = $(this).val();
		$('header').hide();
		$('#' + $header).show();
	});
	$(document).on('change','#switcher #footer',function(e){
		$footer = $(this).val();
		$('footer').hide();
		$('#' + $footer).show();
	});

	//MOBILE MENU FUNCTIONS
	$(document).on('click','#mobile-toggle',function(e){
		$('#mobile-toggle, #menuOpen, #menuClose, #mobile-menu, body').toggleClass('active');
	});
	$(document).on('click','#mobile-menu li.menu-item-has-children a',function(e){
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
		$('#searchform').toggleClass('active');
	});

	//SLIDERS
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

	//VIDEO CAROUSEL FUNCTIONS
	$(".videoCarousel .slide").click(function(){
		$slideid = this.id.replace('slide-','');
		$('#popup-' + $slideid).addClass('active');
	});
	$(".videoCarousel .close").click(function(){
		$('.popup').removeClass('active');
	});


	//ON LOAD DETAILS FOR ACCORDIONS AND TABS
	window.onload = function() {
		$('.open .accordionItem:first-of-type .accordionTitle').addClass('active').css('display', 'flex');
		$('.open .accordionItem:first-of-type .accordionCopy').addClass('active').css('display', 'block');
		$('.open > .accordionTitle, .open .topTabs > .tabTitle, .open .sideTabs > .tabTitle').addClass('active');
		$('.open > .accordionTitle ~ .accordionTitle, .open .topTabs > .tabTitle ~ .tabTitle, .open .sideTabs > .tabTitle ~ .tabTitle').removeClass('active');
		$('.open .tabContent > .tabCopy').addClass('active');
		$('.open .tabContent > .tabCopy ~ .tabCopy').removeClass('active');
	}

	//ACCORDION FUNCTION
	$(".accordionItems .accordionTitle").click(function(){
		$tabid = this.id.replace('accordionTitle-','');
		$accordionid = this.closest('.accordionItems').id.replace('accordionItems-','');

		if (!$(this).hasClass('active')) {
			$('.accordionTitle-' + $accordionid).removeClass('active');
			$(this).addClass('active');

			$(".accordionCopy").each(function(){
				$contentid = this.id.replace('accordionCopy-','');
			
				if($contentid === $tabid){
					$('.accordionCopy-' + $accordionid).not("#accordionCopy" + $contentid).fadeOut(300).hide().removeClass('active');
					$('#accordionCopy-' + $contentid).not('.active').fadeIn(300).show().addClass('active');
				}
			});
		}
		else if ($(this).hasClass('active')) {
			$('.accordionTitle-' + $accordionid).removeClass('active');
			$('.accordionCopy').fadeOut(300).hide().removeClass('active');
		}
	});

	//TAB FUNCTION
	var tabs = function(){
		$tabid = this.id.replace('tabTitle-','');
		$tabItems = this.closest('.tabItems').id.replace('tabItems-','');
		$('.tabTitle-' + $tabItems).removeClass('active');
		$(this).addClass('active');

		$(".tabCopy").each(function(){
		$contentid = this.id.replace('tabCopy-','');
		
		if($contentid === $tabid){
			$('.tabCopy-' + $tabItems).not("#tabCopy" + $contentid).removeClass('active');
			$('#tabCopy-' + $contentid).addClass('active');
		}
		});
	};
	$(".tabItems .topTabs .tabTitle").click(tabs);
	$(".tabItems .sideTabs .tabTitle").hover(tabs);


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