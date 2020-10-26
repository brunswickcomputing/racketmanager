jQuery(function($) {

       var clubCarousel = function(){
            if ( $().owlCarouselFork ) {
                $("#clubs").owlCarouselFork({
                                          navigation : false,
                                          pagination: true,
                                          responsive: true,
                                          items: 3,
                                          itemsDesktopSmall: [1400,3],
                                          itemsTablet:[970,2],
                                          itemsTabletSmall: [600,1],
                                          itemsMobile: [360,1],
                                          touchDrag: true,
                                          mouseDrag: true,
                                          autoHeight: false,
                                          autoPlay: false,
                                          }); // end owlCarousel
            } // end if
       };
 
       var club2Carousel = function(){
            if ( $().owlCarouselFork ) {
                $("#clubs.roll-club").owlCarouselFork({
                                           navigation : false,
                                           pagination: true,
                                           responsive: true,
                                           items: 1,
                                           itemsDesktop: [3000,1],
                                           itemsDesktopSmall: [1400,1],
                                           itemsTablet:[970,1],
                                           itemsTabletSmall: [600,1],
                                           itemsMobile: [360,1],
                                           touchDrag: true,
                                           mouseDrag: true,
                                           autoHeight: true,
                                           autoPlay: $('.roll-club').data('autoplay')
                                           });
            }
       };
       
       $(document).ready(function() {
//                         clubCarousel();
                         club2Carousel();
                         });
});
