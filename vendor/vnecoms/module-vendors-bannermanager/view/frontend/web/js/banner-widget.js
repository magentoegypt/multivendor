define([
    'jquery',
    'swipe',
    'underscore',
    'domReady!'
], function ($, Swiper, _) {
    'use strict';

    return function (config, element) {

        var options = {
            effect: config.effect,
            template: config.template,
            hidePagination: parseInt(config.hidePagination),
            pagination_type: config.pagination_type,
            speed: parseInt(config.speed),
            autoplay: false
        };

        if (!options.hidePagination) {
            var swipe_config = {
                speed: options.speed,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: {
                    el: '.swiper-pagination',
                    type: options.pagination_type,
                    clickable: true,
                }
            };
        } else {
            var swipe_config = {
                speed: options.speed,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                }
            };
        }

        if (options.pagination_type == 'custom') {

        }

        if (config.autoplay) {
            _.extend(swipe_config, {
                autoplay: {
                    delay: parseInt(config.autoplay),
                    disableOnInteraction: true,
                }
            });
        }

        if (config.effect == 'fade') {
            _.extend(swipe_config, {
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                }
            });
        }

        if (config.effect == 'cube') {
            _.extend(swipe_config, {
                effect: 'cube',
                grabCursor: true,
                cubeEffect: {
                    shadow: true,
                    slideShadows: true,
                    shadowOffset: 20,
                    shadowScale: 0.94,
                }
            });
        }

        if (config.effect == 'coverflow') {
            _.extend(swipe_config, {
                effect: 'coverflow',
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 'auto',
                coverflowEffect: {
                    rotate: 50,
                    stretch: 0,
                    depth: 100,
                    modifier: 1,
                    slideShadows : true,
                }
            });
        }

        if (config.effect == 'flip') {
            _.extend(swipe_config, {
                effect: 'flip',
                grabCursor: true,
            });
        }

        switch (options.template) {

            case 'default.phtml':
                 _.extend(swipe_config, {
                     autoHeight: false, //enable auto height
                 });
            break;

            case 'responsive.phtml':

                _.extend(swipe_config, {
                    autoHeight: true, //enable auto height
                    breakpoints: {
                        1220: {
                            spaceBetween: 50
                        },
                        1024: {
                            spaceBetween: 40
                        },
                        960: {
                            spaceBetween: 35
                        },
                        768: {
                            spaceBetween: 30
                        },
                        640: {
                            spaceBetween: 20
                        },
                        320: {
                            spaceBetween: 10
                        }
                    }
                });

            break;

            case 'rtl.phtml':
                _.extend(swipe_config, {
                    autoHeight: false, //enable auto height
                });
            break;


        }

        //console.log(swipe_config);

        var swipe = new Swiper(element, swipe_config);
        //swipe.onResize();
        //console.log(swipe_config);
        //console.log(swipe);
    };


});
