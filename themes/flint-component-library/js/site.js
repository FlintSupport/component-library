$(document).ready(function() {
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

	//SEARCH BUTTON
	$(document).on('click','#searchButton',function(e){
		$(this).toggleClass('active');
		$('#searchform').toggleClass('active');
	});

	//SLIDERS
	$('.testimonialSlider').slick({
		autoplay: false,
		dots: false,
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
		arrows: false,
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
		dots: false,
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
		$('.open .accordionItem:first-of-type > div').addClass('active').css('display', 'block');
		$('.open > .accordionTitle, .open .topTabs > .tabTitle, .open .sideTabs > .tabTitle').addClass('active');
		$('.open > .accordionTitle ~ .accordionTitle, .open .topTabs > .tabTitle ~ .tabTitle, .open .sideTabs > .tabTitle ~ .tabTitle').removeClass('active');
		$('.open > .accordionCopy').css('display', 'block').addClass('active');
		$('.open > .accordionCopy ~ .accordionCopy').css('display', 'none');
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
});