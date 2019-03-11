/**
 * Functions to be used in the event handlers
 */

// ajax request handle
var currentAjaxRequest;

var resetPrivateNetworkForm = function () {
    $('#private-network-form').removeClass('active');
    $('#private-network-name').prop('disabled', true);
    $('#private-network-form .password-confirmation').slideUp('fast');
};

var resetPublicNetworkForm = function () {
    $('#public-network-form').removeClass('active');
    $('#public-network-name').prop('disabled', true);
    $('#public-network-password').prop('disabled', true);
    $('#public-network-password').attr('placeholder', '**********');
    $('#public-network-form .password-confirmation').slideUp('fast');
};

var resetSettingsForms = function () {
    resetPrivateNetworkForm();
    resetPublicNetworkForm();
};

var contactFormSetup = function () {
    var contactForm = {
        registerHandlers: function () {
            $('.form-contact').submit(function (event) {
                var $form = $(this);

                var formData = {
                    'name' : $('.form-contact input[name=name]').val(),
                    'email' : $('.form-contact input[name=email]').val(),
                    'businessSize' : $('.form-contact select[name=businessSize]').val(),
                    'g-recaptcha-response' : grecaptcha.getResponse(todyl.captcha1),
                    'csrf' : $('.form-contact input[name=csrf]').val()
                };

                $form.hide();
                grecaptcha.reset(todyl.captcha1);
                $('#contact-section .contact-loading').fadeIn('slow');

                $.ajax({
                    type        : 'POST',
                    url         : '/emailsignup',
                    data        : formData,
                    encode      : true
                })
                .done(function (data) {
                    var $messageContainer = $("#message-container");

                    var successHtml = '<div class="small-12 columns text-center success"><p class="lead"><img class="success-checkmark hide-for-small-only" src="/img/checkmark.svg" alt="success"/>Thanks - We\'ll let you know when Todyl Protection&#8482; is available!</p></div>';
                    $messageContainer.hide().html(successHtml).fadeIn('slow');

                    $('#contactBox').hide().html("<p class='lead text-center' style='margin-top: 2.5em;'>Submitted!</p>").fadeIn('slow');

                    setTimeout(function () {
                        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out');
                    }, 3000);
                })
                .fail(function (data) {
                    var $messageContainer = $('#message-container');

                    var errorHtml = "<div class='small-12 columns text-center error'><p class='lead'>" + data.responseText + "</p></div>";
                    $messageContainer.hide().html(errorHtml).fadeIn('slow');

                    $('#contact-section .contact-loading').hide();
                    $form.fadeIn('fast');

                    setTimeout(function () {
                        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out');
                    }, 3000);
                });

                event.preventDefault();
            });
        }
    }

    contactForm.registerHandlers();
}

var recaptchaCallback = function () {

    if ($('#contact-recaptcha').length) {
        todyl.captcha1 = grecaptcha.render('contact-recaptcha', {
            'sitekey' : google_pk
        });
    }

    if ($('#footer-recaptcha').length) {
        todyl.captcha2 = grecaptcha.render('footer-recaptcha', {
            'sitekey' : google_pk
        });
    }

    if ($('#signup-recaptcha').length) {
        todyl.captcha3 = grecaptcha.render('signup-recaptcha', {
            'sitekey'   : google_invisible_pk,
            'callback'  : 'signuprecaptchaCallback',
            'size'      : 'invisible',
        });
    }

};

var handleInputMaterialIE = function () {
    // handle :placeholder-shown not working on IE

    if (window.navigator.userAgent.indexOf("Trident") !== -1 || window.navigator.userAgent.indexOf("Edge") !== -1) {
        // IE or Edge

        $('.inputMaterial').each(function () {
            if ($(this).val() || $(this).is('select')) {
                $(this).siblings('label.reg').addClass('filled');
            } else {
                $(this).siblings('label.reg').removeClass('filled');
            }
        });

        $(document).on('change input', '.inputMaterial', function () {
            if ($(this).val()) {
                $(this).siblings('label.reg').addClass('filled');
            } else {
                $(this).siblings('label.reg').removeClass('filled');
            }
        });
    }
}

var adjustFooter = function () {
    // Set session-main-container div to have min-height of 100% of .off-canvas-content (100vh)
    // minus header/footer height
    var mainHeight = $('.off-canvas-content').outerHeight(true) - $('footer').outerHeight(true) - 96;

    if ($('.session-main-container').length) {
        $('.session-main-container').css('min-height', mainHeight + 'px');
    } else {
        if ($('footer').is(':visible') && Foundation.MediaQuery.current !== 'small') {
            if ($('footer').offset().top + $('footer').outerHeight(true) < $(window).height()) {
                $('.container').css('min-height', mainHeight + 'px');

                new Foundation.Equalizer($('.row[data-equalizer=container]')).applyHeight();
            } else {
                $('.container').css('min-height', '');
            }
        } else if ($('.container').length && Foundation.MediaQuery.current == 'small') {
            if ($('.jumbo-nav').length) {
                mainHeight -= $('.jumbo-nav').outerHeight(true);
            }

            $('.container').css('min-height', mainHeight + 'px');
        }
    }
}

var vcenterElement = function($element) {
    var $parent = $element.parent();

    if ($parent.outerHeight() == $element.outerHeight()) $parent = $parent.parent();

    var marginTop = ($parent.outerHeight() - $element.outerHeight()) / 2;

    $element.css({
        marginTop: marginTop + 'px',
    });

}

var scaleHomepageIntro = function () {
    $('#homepage-intro').css('height', ($(window).height() * 0.95) + 'px');
    return true;

    var imgWidth = $(window).width();
    var imgHeight = imgWidth / 1600 * 826; // scale intro height to show full image

    if (imgHeight > $(window).height()) {
        $('#homepage-intro').css('height', $(window).height() + 'px');
    } else {
        $('#homepage-intro').css('height', imgHeight + 'px');
    }
}

var introSizing = function () {

    var checkWidth = $(window).width();
    var navHeight = $('.block-nav').height();
    var linkHeight = navHeight;

    if (checkWidth > 480) {
        $('#eightyHeight').css('height', ($(window).height() - navHeight) + 'px');
    } else {
        $('#eightyHeight').css('height', ($(window).height() * 0.65) + 'px');
    }
    return true;
}




var initBannerVideoSize = function(element) {

    $(element).each(function () {
        $(this).data('height', $(this).height());
        $(this).data('width', $(this).width());
    });

    scaleBannerVideoSize(element);

}

var scaleBannerVideoSize = function(element) {

    var windowWidth = $(window).width(),
    windowHeight = $(window).height() + 5,
    videoWidth,
    videoHeight;

    $(element).each(function () {
        var videoAspectRatio = $(this).data('height')/$(this).data('width'),
            windowAspectRatio = windowHeight/windowWidth;

        if (videoAspectRatio > windowAspectRatio) {
            videoWidth = windowWidth;
            videoHeight = videoWidth * videoAspectRatio;
            $(this).css({'top' : -(videoHeight - windowHeight) / 2 + 'px', 'margin-left' : 0});
        } else {
            videoHeight = windowHeight;
            videoWidth = videoHeight / videoAspectRatio;
            $(this).css({'margin-top' : 0, 'margin-left' : -(videoWidth - windowWidth) / 2 + 'px'});
        }

        $(this).width(videoWidth).height(videoHeight);

        $('.homepage-hero-module .video-container video').addClass('fadeIn animated');
    });
}

// homepage - activate counter
var startCounter = function($counter) {
    var countTo = $counter.attr('data-count');

    $({ countNum: $counter.text()}).animate({
        countNum: countTo
    },
    {
        duration: 1500,
        easing:'swing',
        step: function () {
            $counter.text(numberWithCommas(Math.floor(this.countNum)));
        },
        complete: function () {
            $counter.text(numberWithCommas(this.countNum));
        }
    });
}

// homepage - detect and show service animation
var showServiceDescription = function($service_container) {
    if (! $service_container.find('.description').is(':visible')){
        // todyl defender animation
        if ($(window).scrollTop() + $(window).outerHeight() - $service_container.outerHeight() > $service_container.offset().top) {
            $service_container.find('.description').fadeIn();
        }
    }
}

// on registration step 1 - handle personal | business use
var handlePrimaryType = function () {
    if ($('#primary_use').val() == 'personal') {
        $('#todyl_office_zip').siblings('label.reg').html('Home Zip');
        $('#num_devices').siblings('label.reg').html('How many desktops or laptops do you have at home?');

        $('#todyl_company_name').val('Your Home').parent().fadeOut();
        $('#business_type').val($("#business_type option:nth-child(2)").val()).parent().fadeOut();
        $('#num_employee').val($("#num_employee option:nth-child(2)").val()).parent().fadeOut();
        $('#num_clients').val($("#num_clients option:nth-child(2)").val()).parent().fadeOut();
    } else if ($('#primary_use').val() == 'business') {
        $('#todyl_office_zip').siblings('label.reg').html('Main Office Zip');
        $('#num_devices').siblings('label.reg').html('How many desktops or laptops does your company have?');

        if ($('#todyl_company_name').val() == 'Your Home') {
            $('#todyl_company_name').val('');
        }
        $('#todyl_company_name').parent().fadeIn();
        if (! $('#business_type').find('option[selected]').length) {
            // prevent removal of populated data
            $('#business_type').val($("#business_type option:first").val()).parent().fadeIn();
            $('#num_employee').val($("#num_employee option:first").val()).parent().fadeIn();
            $('#num_clients').val($("#num_clients option:first").val()).parent().fadeIn();
        }
    }
}

// phone no format
var formatPhoneNo = function(tel) {
    if (!tel) { return ''; }

    var value = tel.toString().trim().replace(/^\+/, '');

    if (value.match(/[^0-9]/)) {
        return tel;
    }

    var country, city, number;

    switch (value.length) {
        case 1:
        case 2:
        case 3:
            city = value;
            break;

        default:
            city = value.slice(0, 3);
            number = value.slice(3);
    }

    if (number) {
        if (number.length > 3) {
            number = number.slice(0, 3) + '-' + number.slice(3, 7);
        } else {
            number = number;
        }

        return ("(" + city + ") " + number).trim();
    } else {
        return "(" + city;
    }
}

// signup package update fields according to device number
var updateDeviceNumber = function(op) {
    var num_devices = parseInt($('input[name=num_devices]').val());

    if (op == 'inc') {
        // increase device count

        if (num_devices == 25) {
            return false;
        }

        num_devices ++;
    } else if (op == 'dec') {
        // decrease device count

        if (num_devices == 1) {
            return false;
        }

        num_devices --;
    }

    $('input[name=num_devices]').val(num_devices);

    $('#num_devices').html(num_devices + (num_devices > 1 ? ' Devices' : ' Device'));

    updatePackageOptions();
}

var toggleDeviceCount = function(type) {
    var num_devices;

    if (type == 'toInput') {

        $('#counter_container').hide();

        num_devices = $('input[name=num_devices]').val();

        $('#num_devices').addClass('editable').html('<input type="text" id="edit_num_devices" value="' + num_devices + '" onkeypress="return numbersonly(event)" oninput="checkInputLength(2, this)" />' + (num_devices > 1 ? ' Devices' : ' Device'));
        $('#edit_num_devices').focus().select();

    } else if (type == 'toStatic') {

        $('#counter_container').show();

        if (! $('#edit_num_devices').length || ! parseInt($('#edit_num_devices').val()) || parseInt($('#edit_num_devices').val()) > 25) {
            num_devices = $('input[name=num_devices]').val();
        } else {
            num_devices = $('#edit_num_devices').val();
        }

        $('input[name=num_devices]').val(num_devices);

        $('#num_devices').removeClass('editable').html(num_devices + (num_devices > 1 ? ' Devices' : ' Device'));

        updatePackageOptions();

    }
}

// signup package step update package options based on prepaid(annual) option
var updatePackageOptions = function () {
    $.ajax({
        type        : 'POST',
        url         : '/pricing/getPricing',
        dataType    : 'json',
        data        : {
            prepay  : $('input[name=prepay]').is(':checked'),
            devices : $('input[name=num_devices]').val(),
        },
        encode      : true
    })
    .done(function (data) {
        $('.package_price[data-package=standard]').html(data.standard);
        $('.package_price[data-package=expedited]').html(data.expedited);
        $('.package_price[data-package=dedicated]').html(data.dedicated);
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// billing update summary
var updateSummary = function () {
    if ($('.summary_container').length) {
        $.ajax({
            type        : 'POST',
            url         : '/pricing/getSignupSummary',
            dataType    : 'json',
            encode      : true
        })
        .done(function (data) {
            var summaryHtml = '<p><span class="f_left">Subtotal</span><span class="f_right">' + data.subtotal + '</span></p>';
            summaryHtml += '<p><span class="f_left">Special Ongoing Offer</span><span class="f_right">' + data.discount + '</span></p>';
            if (data.prepay) {
                summaryHtml += '<p><span class="f_left">Annual Billing (Save 10%)</span><span class="f_right">' + data.prepay + '</span></p>';
            }
            if (data.promo) {
                summaryHtml += '<p><span class="f_left">Promo Code</span><span class="f_right">' + data.promo + '</span></p>';
            }
            summaryHtml += '<p class="text-bold"><span class="f_left">Total</span><span class="f_right">' + data.total + '</span></p>';

            $('#subtotal_container').html(summaryHtml);
        })
        .fail(function (data) {
            showErrorText(data.message);
        });
    }
}

// show success text
var showSuccessText = function(message, callback) {
    var $messageContainer = $('#message-container');

    var successHtml = "<div class='small-12 columns text-center success'><p class='lead'>" + message + "</p></div>";
    $messageContainer.hide().html(successHtml).fadeIn('slow');

    setTimeout(function () {
        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out', function () {
            if (callback) {
                callback();
            }
        });
    }, 3000);
}

// show ajax error text
var showErrorText = function(message, reload) {
    if (! message || message == 'undefined') {
        return;
    }

    var $messageContainer = $('#message-container');

    var errorHtml = "<div class='small-12 columns text-center error'><p class='lead'>" + message + "</p></div>";
    $messageContainer.hide().html(errorHtml).fadeIn('slow');

    setTimeout(function () {
        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out');

        if (typeof reload !== 'undefined' && reload === true) {
            window.location.reload();
        }
    }, 3000);
}

var checkDeviceInstall = function(pin) {
    (function checkDevice() {
        // check if device has been registered

        $.ajax({
            type        : 'POST',
            url         : '/dashboard/checkDevice',
            dataType    : 'json',
            data        : {
                pin     : pin,
            },
        })
        .done(function (response) {
            if (response.status == 'success') {
                // device is registered - redirect to dashboard

                // set a 2 sec delay to handle network changes for customer
                setTimeout(function () {
                    window.location.href = '/dashboard';
                }, 2000);
            } else {
                // Schedule the next request when the current one's complete
                setTimeout(checkDevice, 3000);
            }
        })
        .fail(function (response) {
            // send next request
            setTimeout(checkDevice, 3000);
            // showErrorText(response.message ? response.message : response.statusText);
        });
    })();
}

// dashboard update stats
var updateDashboardStats = function () {
    // data protected by device
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
        data        : {
            type    : 'data_protected',
        },
    })
    .done(function (response) {
        // update blocked threats chart

        if ($('.data_protected_container').find('.spinner')) {
            $('.data_protected_container .spinner').remove();
        }

        if (parseInt(response.blocked_threats_total) >= 10) {
            $('#no_chart_data').hide();
            $('#chart_blocks').show();
            chart_blocks.data.datasets = response.block_data;
            chart_blocks.update();

            $('#blocks_header').html('<h4><span class="stat_num">' + numberWithCommas(response.blocked_threats_total) + '</span> Potential Threats Blocked</h4><p>Last 7 Days</p>');
        } else {
            $('#chart_blocks').hide();
            $('#no_chart_data').show();

            $('#blocks_header').html('<h4>Data Protected by Device</h4>');
        }

        if ($('#spinner_container').is(':visible')) {
            // this is a first run

            chart_blocks.update();
            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=first-row]')).applyHeight();
    })
    .fail(function (response) {
        console.log(response);
    });

    // threat indicators
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
        data        : {
            type    : 'threat_indicators',
        },
    })
    .done(function (response) {
        // update threat indicators added chart
        $('#indicators_header').html('<h4><span class="stat_num">' + response.threat_indicators_total + '</span> Threat Indicators Added</h4><p>Last 7 Days</p>');
        chart_indicators.data.datasets = response.indicator_data;
        chart_indicators.update();

        // update new threat indicator
        $('#latest_threat')
            .empty()
            .append($('<p>')
                .text('Latest blocked by Todyl:')
            )
            .append($('<p>')
                .text(response.latestThreat.name)
            )
            .append($('<p>')
                .html('Missed by <span class="text-blue">' + (100 - response.latestThreat.percentage) + '%</span> of security products. <span data-tooltip data-allow-html="true" class="remove-underline" title="Newly discovered threats are scanned with over 50 of the most popular anti-virus and anti-malware products."><img src="/img/info.svg" height="16" width="16" alt=""></span>')
            )
        ;

        if ($('#spinner_container').is(':visible')) {
            // this is a first run

            chart_indicators.update();

            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=second-row]')).applyHeight();
    })
    .fail(function (response) {
        console.log(response);
    });

    // your devices
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
        data        : {
            type    : 'your_devices',
        },
    })
    .done(function (response) {
        if ($('.devices_container').hasClass('spinner')) {
            $('.devices_container').removeClass('spinner');
        }

        // connected devices
        if (response.connected_count) {
            $('#connected_devices').html(response.connected_count + ' of ' + response.total_devices + ' connected');
        } else {
            $('#connected_devices').html('No devices currently connected');
        }
        $('#see_all_devices').html('See all');
        //response.total_devices

        // device status
        $('.devices_container').html(response.device_status);

        if ($('#spinner_container').is(':visible')) {
            // this is a first run

            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=second-row]')).applyHeight();
    })
    .fail(function (response) {
        console.log(response);
    });

    // threat level
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
        data        : {
            type    : 'threat_level',
        },
    })
    .done(function (response) {
        // internet threat levels
        $('#level_image').attr('src', response.threat_level.image_path);
        $('#level_text').removeClass().addClass(response.threat_level.title.toLowerCase()).html(response.threat_level.title);
        $('#threat_description').html(response.threat_level.description);

        if ($('#spinner_container').is(':visible')) {
            // this is a first run

            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=first-row]')).applyHeight();
    })
    .fail(function (response) {
        console.log(response);
    });

    // bottom stats
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
        data        : {
            type    : 'stats',
        },
    })
    .done(function (response) {
        // data protected all time
        $('#stats_container .columns:nth-child(1) h2').html(response.stat_traffic);
        $('#stats_container .columns:nth-child(1) p').html(response.stat_blocks ? 'Data Protected to Date' : 'Data protected all time, all customers');

        // potential threats blocked all time
        $('#stats_container .columns:nth-child(2) h2').html(response.stat_blocks ? response.stat_blocks : response.stat_blocks_all);
        $('#stats_container .columns:nth-child(2) p').html(response.stat_blocks ? 'Potential Threats Blocked to Date' : 'Potential threats blocked all time, all customers');

        // potential threats blocked across all customers
        $('#stats_container .columns:nth-child(3) h2').html(response.stat_blocks ? response.stat_blocks_all : response.stat_malicious);
        $('#stats_container .columns:nth-child(3) p').html(response.stat_blocks ? 'Potential Threats Blocked Across All Customers' : 'Malicious files blocked all time, all customers');

        if ($('#spinner_container').is(':visible')) {
            // this is a first run

            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=third-row]')).applyHeight();
    })
    .fail(function (response) {
        console.log(response);
    });

    // $.ajax({
    //     type        : 'POST',
    //     url         : '/dashboard/getstats',
    //     dataType    : 'json',
    // })
    // .done(function (response) {
    //     // update blocked threats chart

    //     if (parseInt(response.blocked_threats_total) >= 10) {
    //         $('#no_chart_data').hide();
    //         $('#chart_blocks').show();
    //         chart_blocks.data.datasets = response.block_data;
    //         chart_blocks.update();

    //         $('#blocks_header').html('<h4><span class="stat_num">' + numberWithCommas(response.blocked_threats_total) + '</span> Potential Threats Blocked</h4><p>Last 7 Days</p>');
    //     } else {
    //         $('#chart_blocks').hide();
    //         $('#no_chart_data').show();

    //         $('#blocks_header').html('<h4>Data Protected by Device</h4>');
    //     }

    //     // update threat indicators added chart
    //     $('#indicators_header').html('<h4><span class="stat_num">' + response.threat_indicators_total + '</span> Threat Indicators Added</h4><p>Last 7 Days</p>');
    //     chart_indicators.data.datasets = response.indicator_data;
    //     chart_indicators.update();

    //     // internet threat levels
    //     $('#level_image').attr('src', response.threat_level.image_path);
    //     $('#level_text').removeClass().addClass(response.threat_level.title.toLowerCase()).html(response.threat_level.title);
    //     $('#threat_description').html(response.threat_level.description);

    //     // update new threat indicator
    //     $('#latest_threat')
    //         .empty()
    //         .append($('<p>')
    //             .text('Latest blocked by Todyl:')
    //         )
    //         .append($('<p>')
    //             .text(response.latestThreat.name)
    //         )
    //         .append($('<p>')
    //             .html('Missed by <span class="text-blue">' + (100 - response.latestThreat.percentage) + '%</span> of security products. <span data-tooltip data-allow-html="true" class="remove-underline" title="Newly discovered threats are scanned with over 50 of the most popular anti-virus and anti-malware products."><img src="/img/info.svg" height="16" width="16" alt=""></span>')
    //         )
    //     ;

    //     // connected devices
    //     if (response.connected_count) {
    //         $('#connected_devices').html(response.connected_count + ' of ' + response.total_devices + ' connected');
    //     } else {
    //         $('#connected_devices').html('No devices currently connected');
    //     }
    //     $('#see_all_devices').html('See all ' + response.total_devices);

    //     // device status
    //     $('.devices_container').html(response.device_status);

    //     // threat descriptions
    //     $('#threat_descriptions').html(response.threat_description);

    //     // data protected all time
    //     $('#stats_container .columns:nth-child(1) h2').html(response.stat_traffic);
    //     $('#stats_container .columns:nth-child(1) p').html(response.stat_blocks ? 'Data Protected to Date' : 'Data protected all time, all customers');

    //     // potential threats blocked all time
    //     $('#stats_container .columns:nth-child(2) h2').html(response.stat_blocks ? response.stat_blocks : response.stat_blocks_all);
    //     $('#stats_container .columns:nth-child(2) p').html(response.stat_blocks ? 'Potential Threats Blocked to Date' : 'Potential threats blocked all time, all customers');

    //     // potential threats blocked across all customers
    //     $('#stats_container .columns:nth-child(3) h2').html(response.stat_blocks ? response.stat_blocks_all : response.stat_malicious);
    //     $('#stats_container .columns:nth-child(3) p').html(response.stat_blocks ? 'Potential Threats Blocked Across All Customers' : 'Malicious files blocked all time, all customers');

    //     if ($('#spinner_container').is(':visible')) {
    //         // this is a first run

    //         chart_blocks.update();
    //         chart_indicators.update();

    //         easeInPanels();
    //     }

    //     // adjust equalizer heights
    //     new Foundation.Equalizer($('.row[data-equalizer=first-row]')).applyHeight();
    //     new Foundation.Equalizer($('.row[data-equalizer=second-row]')).applyHeight();
    //     new Foundation.Equalizer($('.row[data-equalizer=third-row]')).applyHeight();

    // })
    // .fail(function (response) {
    //     console.log(response);
    // });
}

var loadDeviceList = function(user_id) {
    if (typeof user_id === 'undefined' || user_id === null) user_id = '';

    $.ajax({
        type        : 'POST',
        url         : '/user-device/manageDevice',
        dataType    : 'json',
        data        : {
            type    : 'list',
            user_id : user_id,
        },
    })
    .done(function (response) {
        var device_list_html = '';

        var grid_class = '';
        if (user_id || $('.view-switcher.active').data('type') == 'grid') {
            grid_class = 'small-12 medium-6 large-3';
        } else {
            grid_class = 'small-12 medium-6 large-3';
        }

        $.each(response.devices, function(index, device) {
            if (! $('#additional_card_container').length &&
                index == response.devices.length - 1 &&
                ! response.invitations.length &&
                ! parseInt(response.available_slots) &&
                ! response.user_slots.length) {
                // user view && last device card
                device_list_html += '<div class="' + grid_class + ' end columns">';
            } else {
                device_list_html += '<div class="' + grid_class + ' columns">';
            }

            device_list_html += '<li class="panel" data-type="device" data-equalizer-watch="device_panel">';

            //--- device info ---

            //-- action
            device_list_html += '<div class="panel_action">';
            device_list_html += '<a class="toggle_settings" href="javascript:void(0);"><i class="i-menu"></i></a>';
            device_list_html += '</div>';

            //-- info
            device_list_html += '<div class="panel_info">';

            // icon
            device_list_html += '<i class="i-' + device.device_type + ' device_icon"></i>';

            // device name
            device_list_html += '<h4>' + (device.pin_used == '1' ? device.user_device_name : 'Not Installed') + '</h4>';

            // connected status
            var status_html = '';
            if (! device.datetime_disconnected && device.datetime_connected) {
                // connected
                status_html += '<div class="connection_status connected" data-tooltip data-allow-html="true" title="' + device.tooltip + '"><i class="i-defender"></i></div>';
                status_html += '<p>' + device.user_email + '<br />Connected</p>';
            } else {
                // disconnected
                status_html += '<div class="connection_status" data-tooltip data-allow-html="true" title="' + device.tooltip + '"><i class="i-defender"></i></div>';

                status_html += '<p>' + device.user_email + '<br />Not Connected</p>';
            }
            device_list_html += status_html;

            device_list_html += '</div>';

            //--- device config menu ---

            device_list_html += '<div class="panel_menu">';

            device_list_html += '<input class="input_info_holder" type="hidden"\
                                    data-device_id="' + device.device_id + '"\
                                    data-device_name="' + device.user_device_name + '"\
                                    data-device_user="' + device.user_email + '"\
                                    data-is_own_device="' + device.is_own_device + '">\
            ';

            device_list_html += '<h4>' + device.user_device_name + '</h4>';

            device_list_html += '<p><a href="javascript:void(0);" class="action" data-action="rename_device">Rename This Device</a></p>';
            device_list_html += '<p><a href="javascript:void(0);" class="action" data-action="reinstall_defender">Reinstall on This Device</a></p>';
            // device_list_html += '<p><a href="javascript:void(0);" class="action" data-action="user_details">User Details</a></p>';
            device_list_html += '<p><a href="javascript:void(0);" class="action danger" data-action="remove_device">Remove This Device</a></p>';

            device_list_html += '</div>';

            device_list_html += '</li>';

            device_list_html += '</div>';
        });

        // unanswered invites
        $.each(response.invitations, function(index, invitation) {
            if (! $('#additional_card_container').length &&
                index == response.invitations.length - 1 &&
                ! parseInt(response.available_slots) &&
                ! response.user_slots.length) {
                // user view && last device card
                device_list_html += '<div class="' + grid_class + ' end columns">';
            } else {
                device_list_html += '<div class="' + grid_class + ' columns">';
            }

            device_list_html += '<li class="panel" data-type="user" data-equalizer-watch="device_panel">';

            device_list_html += '<input class="input_info_holder" type="hidden"\
                                    data-user_id="' + invitation.user_id + '"\
                                    data-user_name="' + invitation.user_email + '"\
                                    data-user_email="' + invitation.user_email + '"\
                                    data-user_devicecnt="' + invitation.device_count + '">';

            device_list_html += '<div class="panel_info_2">';

            device_list_html += '<i class="i-email device_icon inactive"></i>';

            device_list_html += '<h4>Invited</h4>';

            device_list_html += '<div class="connection_status"><i class="i-defender"></i></div>';

            device_list_html += '<p>' + invitation.user_email + ' has not signed up.</p>';

            device_list_html += '<p><a href="javascript:void(0);" class="action" data-action="cancel_invite">Cancel Invite</a>&nbsp;|&nbsp;';

            device_list_html += '<a href="javascript:void(0);" class="resend_invite" data-id="' + invitation.user_id + '">Resend Invite</a></p>';

            device_list_html += '</div>';

            device_list_html += '</li>';

            device_list_html += '</div>';
        });

        // available slots
        if (response.available_slots) {
            // stack available devices
            if (! $('#additional_card_container').length &&
                ! response.user_slots.length) {
                device_list_html += '<div class="' + grid_class + ' end columns">';
            } else {
                device_list_html += '<div class="' + grid_class + ' columns">';
            }

            device_list_html += '<li class="panel" data-type="user" data-equalizer-watch="device_panel">';

            device_list_html += '<input class="input_info_holder" type="hidden"\
                                    data-user_id="' + response.user_id + '"\
                                    data-user_email="' + response.user_email + '"\
                                    data-user_devicecnt="' + response.available_slots + '">';

            //-- action
            device_list_html += '<div class="panel_action">';
            device_list_html += '<a class="toggle_settings" href="javascript:void(0);"><i class="i-menu"></i></a>';
            device_list_html += '</div>';

            //-- info
            device_list_html += '<div class="panel_info">';

            device_list_html += '<div class="icon-text"><i class="i-laptop device_icon inactive"></i><span>' + response.available_slots + '</span></div>';

            device_list_html += '<h4 class="text-mid-grey">' + (user_id ? (response.available_slots == 1 ? 'Device' : 'Devices') + ' Available' : 'Your Available Devices') + '</h4>';

            device_list_html += '<div class="connection_status"><i class="i-defender"></i></div>';

            if (response.is_own) {
                // user's own slot card
                device_list_html += '<p><a href="javascript:void(0);" class="device_instruction">Email me an install link</a> or<br />';

                device_list_html += '<a href="javascript:void(0);" class="device_install">Install on this device</a></p>';
            } else {
                // org user's slot card
                device_list_html += '<p><a href="/service/device">Edit</a>&nbsp;|&nbsp;';

                device_list_html += '<a href="javascript:void(0);" class="device_instruction" data-id="' + user_id + '">Send Reminder</a></p>';
            }

            // device_list_html += '<a href="javascript:void(0);" class="device_install">Install on this device</a></p>';

            device_list_html += '</div>';

            //--- device config menu ---

            device_list_html += '<div class="panel_menu">';

            device_list_html += '<h4>Actions</h4>';

            device_list_html += '<p><a href="javascript:void(0);" class="action danger" data-action="remove_slots">Remove Available Devices</a></p>';

            device_list_html += '</div>';

            device_list_html += '</li>';

            device_list_html += '</div>';
        }
        // for (var i = 0; i < parseInt(response.available_slots); i ++) {
        //     if (! $('#additional_card_container').length && i == parseInt(response.available_slots) - 1) {
        //         device_list_html += '<div class="' + grid_class + ' end columns">';
        //     } else {
        //         device_list_html += '<div class="' + grid_class + ' columns">';
        //     }

        //     device_list_html += '<li class="panel" data-type="device" data-equalizer-watch="device_panel">';

        //     device_list_html += '<i class="i-unknown-device device_icon inactive"></i>';

        //     device_list_html += '<h4 class="text-mid-grey">A device for you...</h4>';

        //     device_list_html += '<div class="connection_status"><i class="i-defender"></i></div>';

        //     device_list_html += '<p><a href="javascript:void(0);" class="device_instruction">Send me instructions</a> or<br />';

        //     device_list_html += '<a href="javascript:void(0);" class="device_install">Install on this device</a></p>';

        //     device_list_html += '</li>';

        //     device_list_html += '</div>';
        // }

        // user slots
        for (var i = 0; i < response.user_slots.length; i ++) {
            // stack available devices
            if (! $('#additional_card_container').length && i == response.user_slots.length - 1) {
                device_list_html += '<div class="' + grid_class + ' end columns">';
            } else {
                device_list_html += '<div class="' + grid_class + ' columns">';
            }

            device_list_html += '<li class="panel" data-type="user" data-equalizer-watch="device_panel">';

            device_list_html += '<input class="input_info_holder" type="hidden"\
                                    data-user_id="' + response.user_slots[i].user_id + '"\
                                    data-user_email="' + response.user_slots[i].user_email + '"\
                                    data-user_devicecnt="' + response.user_slots[i].device_count + '">';

            //-- action
            device_list_html += '<div class="panel_action">';
            device_list_html += '<a class="toggle_settings" href="javascript:void(0);"><i class="i-menu"></i></a>';
            device_list_html += '</div>';

            //-- info
            device_list_html += '<div class="panel_info">';

            device_list_html += '<div class="icon-text"><i class="i-laptop device_icon inactive"></i><span>' + response.user_slots[i].device_count + '</span></div>';

            device_list_html += '<h4 class="text-mid-grey">Available ' + (response.user_slots[i].device_count == 1 ? 'Device' : 'Devices') + '</h4>';

            device_list_html += '<div class="connection_status"><i class="i-defender"></i></div>';

            device_list_html += '<p>' + response.user_slots[i].user_email + ' has devices available</p>';

            device_list_html += '<p><a href="javascript:void(0);" class="device_instruction" data-id="' + response.user_slots[i].user_id + '">Send Reminder</a></p>';

            // device_list_html += '<a href="javascript:void(0);" class="device_install">Install on this device</a></p>';

            device_list_html += '</div>';

            //--- device config menu ---

            device_list_html += '<div class="panel_menu">';

            device_list_html += '<h4>Actions</h4>';

            device_list_html += '<p><a href="javascript:void(0);" class="action danger" data-action="remove_slots">Remove Available Devices</a></p>';

            device_list_html += '</div>';

            device_list_html += '</li>';

            device_list_html += '</div>';
        }

        if ($('#additional_card_container').length) {
            // additional protection panel
            device_list_html += '<div class="' + grid_class + ' columns end">';

            device_list_html += $('#additional_card_container').html();

            device_list_html += '</div>';
        }

        $('#device_list').html(device_list_html);

        new Foundation.Equalizer($('.row[data-equalizer=device_panel]')).applyHeight();
        new Foundation.Equalizer($('.row[data-equalizer=container]')).applyHeight();

        if ($('#spinner_container').is(':visible')) {
            $('#spinner_container').hide();
        }
    })
    .fail(function (response) {
        console.log(response);
    });
}

var loadUserList = function () {
    $.ajax({
        type        : 'POST',
        url         : '/user-device/manageUser',
        dataType    : 'json',
        data        : {
            type    : 'list',
        },
    })
    .done(function (response) {
        var user_list_html = '';

        var grid_class = '';
        if ($('.view-switcher.active').data('type') == 'grid') {
            grid_class = 'small-12 medium-6 large-3';
        } else {
            grid_class = 'small-12 medium-6 large-3';
        }

        $.each(response.users, function(index, user) {
            if (! $('#additional_card_container').length && index == response.users.length - 1) {
                user_list_html += '<div class="' + grid_class + ' end columns">';
            } else {
                user_list_html += '<div class="' + grid_class + ' columns">';
            }

            user_list_html += '<li class="panel" data-type="user" data-equalizer-watch="user_panel">';

            //--- user info ---

            //-- action
            user_list_html += '<div class="panel_action">';
            user_list_html += '<a class="toggle_settings" href="javascript:void(0);"><i class="i-menu"></i></a>';
            user_list_html += '</div>';

            //-- info
            user_list_html += '<div class="panel_info">';

            if (user.is_active == '1') {
                // user initials
                user_list_html += '<div class="user_initials">' + user.initials + '</div>';

                // user name
                user_list_html += '<h4>' + (user.user_name == ' ' ? user.user_email : user.user_name) + '</h4>';

                user_list_html += '<div class="connection_status connected"><i class="i-defender"></i></div>';

                user_list_html += '<p data-tooltip data-allow-html="true" title="' + user.device_count + (user.device_count == 1 ? ' Device' : ' Devices') + '">' + user.device_count + '</p>';
            } else {
                // user initials
                user_list_html += '<div class="user_initials inactive">' + user.initials + '</div>';

                user_list_html += '<p class="text-mid-grey">' + user.user_email + ' has not signed up.<br />';

                // user_list_html += '<a href="javascript:void(0);" class="cancel_invite" data-id="' + user.user_id + '">Cancel Invite</a>&nbsp;|&nbsp;';

                user_list_html += '<a href="javascript:void(0);" class="resend_invite" data-id="' + user.user_id + '">Resend Invite</a>';

                user_list_html += '</p>';
            }

            user_list_html += '</div>';

            //--- user config menu ---

            user_list_html += '<div class="panel_menu">';

            user_list_html += '<input class="input_info_holder" type="hidden"\
                                    data-user_id="' + user.user_id + '"\
                                    data-user_name="' + user.user_name + '"\
                                    data-user_email="' + user.user_email + '"\
                                    data-user_devicecnt="' + user.device_count + '">';

            user_list_html += '<h4>' + (user.user_name.trim() ? user.user_name : user.user_email) + '</h4>';

            // user_list_html += '<p><a href="javascript:void(0);" class="action" data-action="user_details">User Details</a></p>';
            // user_list_html += '<p><a href="/service/device">Manage Device Count</a></p>';
            // user_list_html += '<p><a href="/service">Add Additional Services</a></p>';

            if (user.is_active == '1') {
                user_list_html += '<p><a href="/user-device?user=' + user.user_id + '">Manage Devices</a></p>';
            }

            if (user.is_own == '0') {
                user_list_html += '<p><a href="javascript:void(0);" class="action danger" data-action="remove_user">Remove This User</a></p>';
            }

            user_list_html += '</div>';

            user_list_html += '</li>';

            user_list_html += '</div>';
        });

        if ($('#additional_card_container').length) {
            // additional protection panel

            user_list_html += '<div class="' + grid_class + ' columns end">';

            user_list_html += $('#additional_card_container').html();

            user_list_html += '</div>';
        }

        $('#user_list').html(user_list_html);

        new Foundation.Equalizer($('.row[data-equalizer=user_panel]')).applyHeight();
        new Foundation.Equalizer($('.row[data-equalizer=container]')).applyHeight();

        if ($('#spinner_container').is(':visible')) {
            $('#spinner_container').hide();
        }
    })
    .fail(function (response) {
        console.log(response);
    });
}

var easeInPanels = function () {
    $('#spinner_container').fadeOut(500, function () {
        $('.ajax-hidden').animate({
            'opacity': 1
        }, 500);

        var panels = $('.ajax-panels').find('.panel');

        var i = 0;

        (function easeInPanel() {
            $(panels[i]).animate({
                'opacity': 1,
                'margin-top': '0px'
            }, 500);

            i ++;
            if (i < panels.length) {
                setTimeout(easeInPanel, 100);
            }
        })();
    });
    // panels.each(function () {
    //     $(this).animate({
    //         'opacity': 1,
    //         'margin-top': '0px'
    //     }, 500);
    // });
}

// abide validate form
var validateForm = function($form) {
    $form.foundation('validateForm', $form);

    if ($form.find('.form-error.is-visible').length || $form.find('.is-invalid-label').length || $form.find('.is-invalid-input').length) {
        return false;
    }

    return true;
}

// download products
var startDownload = function () {
    var os = getUrlVars()['os'];

    // this is unnecessary if redirect works properly
    if (os === undefined) os = getPlatform();

    window.location.href = '/download/download?os=' + os;
}

// start magic download
var startMagicDownload = function () {
    var os = getUrlVars()['os'];

    // this is unnecessary if redirect works properly
    if (os === undefined) os = getPlatform();

    window.location.href = '?os=' + os + '&dl=1';
}

// on download page enter, check OS and redirect
var checkOS = function () {
    var os = getUrlVars()['os'];

    if (os === undefined) {
        os = getPlatform();
        window.location.href = '?os=' + os;
    }
}

// get user OS
var getPlatform = function () {
    var osName = 'win';

    if (navigator.appVersion.indexOf('Win') != -1) osName = 'win';
    if (navigator.appVersion.indexOf('Mac') != -1) osName = 'mac';

    return osName;
}

// Read a page's GET URL variables and return them as an associative array.
var getUrlVars = function () {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

// copy pin
var copyPin = function () {
    var $temp = $('<input>');
    $('body').append($temp);
    $temp.val($('#pin_no').html()).select();
    document.execCommand('copy');
    document.getElementById('copy-confirm').classList.add('copied');

    var temp2 = setInterval(function () {
        document.getElementById('copy-confirm').classList.remove( 'copied' );
        clearInterval(temp2);
    }, 1000);

    $temp.remove();
}

// sign up page - check if user has clicked google recaptcha
var signuprecaptchaCallback = function(token) {
    if ($('#num_devices').length && parseInt($('#num_devices').val()) > 25) {
        // employee count is more than 25 - show Contact Us modal without going through

        $('#signup-recaptcha').data('valid', 'no');
        $('#dlg_contact_us').foundation('open');

        grecaptcha.reset(todyl.captcha3);
    } else {
        $('#signup-recaptcha').data('valid', 'yes');
        if ($('.signup-form').length) {
            $('.signup-form').submit();
        } else if ($('.beta-signup-form').length) {
            $('.beta-signup-form').submit();
        }
    }
}

// activity page

function loadActivityStats() {
    $.ajax({
        type        : 'POST',
        url         : '/activity/manage',
        dataType    : 'json',
        data        : {
            type    : 'stats',
        },
    })
    .done(function (response) {
        if (response.status != 'success') {
            console.log(response);
            showErrorText('Sorry, there was an error while loading your activity stats.');

            return;
        }

        $('#activity_stats .columns:nth-child(1) .stat-value').html(response.hours_protected);
        $('#activity_stats .columns:nth-child(2) .stat-value').html(response.blocked_threats);
        $('#activity_stats .columns:nth-child(3) .stat-value').html(response.connected_text);
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

function loadActivityList(period) {
    // spinner
    $('#spinner_container').show();

    $.ajax({
        type        : 'POST',
        url         : '/activity/manage',
        dataType    : 'json',
        data        : {
            type    : 'list',
            period  : $('#filter_activity_period').val(),
        },
    })
    .done(function (response) {
        if (response.status != 'success') {
            console.log(response);
            showErrorText('Sorry, there was an error while loading your activity history.');

            return;
        }

        $('#activity_list tbody').html(response.activity_list);

        easeInPanels();
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// review alerts page
function loadIncidentList(show_resolved) {
    if (typeof show_resolved === 'undefined' || show_resolved === null) show_resolved = false;

    // spinner
    $('#spinner_container').show();

    $.ajax({
        type        : 'POST',
        url         : '/review/manage',
        dataType    : 'json',
        data        : {
            type            : 'list',
            show_resolved   : show_resolved,
        },
    })
    .done(function (response) {
        if (response.status != 'success') {
            console.log(response);
            showErrorText('Sorry, there was an error while loading your activity history.');

            return;
        }

        $('#incident_list tbody').html(response.incident_list);

        var incident_cnt = response.incident_count;

        $('#incident_cnt').html(incident_cnt + (incident_cnt == 1 ? ' Incident' : ' Incidents') + ' to Review');

        easeInPanels();
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// support page

function manageSupport(action, item_id) {
    $.ajax({
        type        : 'POST',
        url         : '/support/manage',
        dataType    : 'json',
        data        : {
            action  : action,
            id      : item_id,
        },
    })
    .done(function (response) {
        if (response.status == 'success') {
            window.location.reload();
        } else {
            showErrorText(response.message);
        }
    })
    .fail(function (response) {
        showErrorText(response.message ? response.message : response.statusText);
    });
}

/**
 * JavaScript Client Detection
 */
var detectOS = function () {
    var unknown = '-';

    // browser
    var nVer = navigator.appVersion;
    var nAgt = navigator.userAgent;

    // system
    var os = unknown;
    var clientStrings = [
        {s:'Windows 10', r:/(Windows 10.0|Windows NT 10.0)/},
        {s:'Windows 8.1', r:/(Windows 8.1|Windows NT 6.3)/},
        {s:'Windows 8', r:/(Windows 8|Windows NT 6.2)/},
        {s:'Windows 7', r:/(Windows 7|Windows NT 6.1)/},
        {s:'Windows Vista', r:/Windows NT 6.0/},
        {s:'Windows Server 2003', r:/Windows NT 5.2/},
        {s:'Windows XP', r:/(Windows NT 5.1|Windows XP)/},
        {s:'Windows 2000', r:/(Windows NT 5.0|Windows 2000)/},
        {s:'Windows ME', r:/(Win 9x 4.90|Windows ME)/},
        {s:'Windows 98', r:/(Windows 98|Win98)/},
        {s:'Windows 95', r:/(Windows 95|Win95|Windows_95)/},
        {s:'Windows NT 4.0', r:/(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/},
        {s:'Windows CE', r:/Windows CE/},
        {s:'Windows 3.11', r:/Win16/},
        {s:'Android', r:/Android/},
        {s:'Open BSD', r:/OpenBSD/},
        {s:'Sun OS', r:/SunOS/},
        {s:'Linux', r:/(Linux|X11)/},
        {s:'iOS', r:/(iPhone|iPad|iPod)/},
        {s:'Mac OS X', r:/Mac OS X/},
        {s:'Mac OS', r:/(MacPPC|MacIntel|Mac_PowerPC|Macintosh)/},
        {s:'QNX', r:/QNX/},
        {s:'UNIX', r:/UNIX/},
        {s:'BeOS', r:/BeOS/},
        {s:'OS/2', r:/OS\/2/},
        {s:'Search Bot', r:/(nuhk|Googlebot|Yammybot|Openbot|Slurp|MSNBot|Ask Jeeves\/Teoma|ia_archiver)/}
    ];
    for (var id in clientStrings) {
        var cs = clientStrings[id];
        if (cs.r.test(nAgt)) {
            os = cs.s;
            break;
        }
    }

    var osVersion = unknown;

    if (/Windows/.test(os)) {
        osVersion = /Windows (.*)/.exec(os)[1];
        os = 'Windows';
    }

    switch (os) {
        case 'Mac OS X':
            osVersion = /Mac OS X (10[\.\_\d]+)/.exec(nAgt)[1];
            break;

        case 'Android':
            osVersion = /Android ([\.\_\d]+)/.exec(nAgt)[1];
            break;

        case 'iOS':
            osVersion = /OS (\d+)_(\d+)_?(\d+)?/.exec(nVer);
            osVersion = osVersion[1] + '.' + osVersion[2] + '.' + (osVersion[3] | 0);
            break;
    }

    // mobile version
    var mobile = /Mobile|mini|Fennec|Android|iP(ad|od|hone)/.test(nVer);

    var client_os = {
        mobile: mobile,
        os: os,
        osVersion: osVersion,
    };

    return client_os;
}

//-- dashboard functions --//

// account page

var resetAccountFields = function($container) {
    var $expanded = $container.find('.row.expanded');

    $expanded.find('.edit-field').addClass('hide');
    $expanded.find('.static-field').removeClass('hide');

    $expanded.find('.edit').removeClass('hide');
    $container.find('.edit_action').addClass('hide');
}

var reactivateAccount = function () {
    $.ajax({
        type        : 'POST',
        url         : '/account/manage',
        dataType    : 'json',
        data        : $('#frm_reactivate_billing').serialize() + '&type=reactivate',
    })
    .done(function (response) {
        if (response.status == 'success') {
            window.location.href = '/dashboard';
        } else {
            showErrorText(response.message);
        }
    })
    .fail(function (response) {
        showErrorText(response.message ? response.message : response.statusText);
    });
}

// settings (shield & site)

var resetOpenFields = function ($container) {
    var $expanded = $container.find('.row.expanded');

    $expanded.find('.edit-field').addClass('hide');
    $expanded.find('.static-field').removeClass('hide');

    $expanded.find('.save').hide();
    $expanded.find('.cancel').removeClass('cancel').addClass('edit').html('Edit');
}

//--- added services page

// added services user device counter
var updateUserdeviceCounter = function($container, op) {
    if (typeof op === 'undefined' || op === null) op = false;

    var $input = $container.children('input');
    var num_devices = parseInt($input.val());

    var lower_limit = 1;
    if ($input.data('original')) {
        lower_limit = $input.data('original');
    }
    // if ($container.hasClass('removable')) {
    //     lower_limit = 0;
    // }

    if (op == 'inc') {
        // increase device count

        // if (num_devices == 25) {
        //     return false;
        // }

        num_devices ++;
    } else if (op == 'dec') {
        // decrease device count

        if (num_devices == lower_limit) {
            return false;
        }

        num_devices --;
    }

    if (num_devices == lower_limit) {
        // the number has reached the lower limit
        $container.children('.minus').addClass('hidden');

        if ($container.hasClass('removable')) {
            $container.parents('.user_row').find('.alert_info').show();
        }
    } else {
        $container.children('.minus').removeClass('hidden');

        if ($container.hasClass('removable') && $container.parents('.user_row').find('.alert_info').is(':visible')) {
            $container.parents('.user_row').find('.alert_info').hide();
        }
    }

    $input.val(num_devices);

    // updateServiceSummary();
}

// added users & devices summary
var loadUserDeviceSummary = function(action, type) {
    // if (typeof param === 'undefined' || param === null) {
    var id = $('#action_' + type + '_id').val();

    $.ajax({
        type        : 'POST',
        url         : '/user-device/manage',
        dataType    : 'json',
        data        : {
            type    : 'getSummary',
            action  : action,
            entry   : {
                type    : type,
                id      : id,
            },
        },
        encode      : true
    })
    .done(function (data) {
        if (data.status != 'success') {
            showErrorText(data.message);
            return false;
        }

        var summary_html = '';

        if (data.coupon) {
            summary_html += '<p class="clearfix couponDesc"><span class="f_left">Coupon</span><span class="f_right">' + data.coupon + '</span></p>';
        }

        summary_html += '<p class="clearfix"><span class="f_left">Current Plan</span><span class="f_right text-mid-grey">' + data.current + '</span></p>';

        summary_html += '<p class="clearfix"><span class="f_left">Change</span><span class="f_right">' + data.change + '</span></p>';

        if (data.discount) {
            summary_html += '<p class="clearfix"><span class="f_left">Discount</span><span class="f_right">' + data.discount + '</span></p>';
        }

        summary_html += '<p class="clearfix separator"></p>';

        summary_html += '<p class="text-bold clearfix"><span class="f_left">New Plan</span><span class="f_right">' + data.total + '</span></p>';

        if (action != 'remove') {
            $('.summary_description').html('Changes made to your subscription plan will be reflected starting ' + data.next_cycle);
        }

        $('#summary_info').html(summary_html);

        // $('#panel_summary').fadeIn();
        new Foundation.Equalizer($('[data-equalizer=summary]')).applyHeight();
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// added services update summary based on selection
var updateServiceSummary = function () {
    currentAjaxRequest = $.ajax({
        type        : 'POST',
        url         : '/service/manageDevice',
        dataType    : 'json',
        data        : $('#frm_service').serialize() + '&type=getSummary',
        encode      : true,
        beforeSend  : function () {
            if(currentAjaxRequest != null) {
                currentAjaxRequest.abort();
            }
        }
    })
    .done(function (data) {
        if (data.status != 'success') {
            showErrorText(data.message);
            return false;
        }

        var summary_html = '';

        if (data.is_ftu) {
            summary_html = '<div class=\"text-mid-grey\">';

            if (data.is_free) {
                summary_html += '<p>With this coupon code, you won\'t be charged until ' + data.coupon_end + '.</p>';
            } else {
                summary_html += '<p>After your free trial, you will be charged ' + data.plan_price + ' per device per month.</p>';
            }

            summary_html += '<p>You can cancel your trial any time before ' + data.trial_end + ' for free.</p>\
                <p>If you have a promo code, you can enter it on the following screen.</p>\
                <p><a class="button btn-wide" href="/service/billing">Next</a></p>\
            </div>';

            $('#summary_info').html(summary_html);

            Foundation.reInit('abide');
            assignServiceAbide();
        } else if (! data.changed) {
            if (! data.updated) {
                // no changes
                summary_html = "<div class=\"no-summary\">\
                    <p><i class=\"i-add-device\"></i></p>\
                    <p>You haven't added additional protection yet.</p>\
                </div>";
            } else {
                summary_html = "<div class=\"no-summary\">\
                    <a id=\"confirm_order\" class=\"button btn-wide\">Update</a>\
                </div>";
            }

            $('.summary_description').html('');
        } else {
            // var summaryHtml = '<p><span class="f_left">Subtotal</span><span class="f_right">' + data.subtotal + '</span></p>';
            // summaryHtml += '<p><span class="f_left">Special Ongoing Offer</span><span class="f_right">' + data.discount + '</span></p>';
            // if (data.prepay) {
            //     summaryHtml += '<p><span class="f_left">Annual Billing (Save 10%)</span><span class="f_right">' + data.prepay + '</span></p>';
            // }
            // if (data.promo) {
            //     summaryHtml += '<p><span class="f_left">Promo Code</span><span class="f_right">' + data.promo + '</span></p>';
            // }
            // summaryHtml += '<p class="text-bold"><span class="f_left">Total</span><span class="f_right">' + data.total + '</span></p>';

            if (data.coupon) {
                summary_html += '<p class="clearfix couponDesc"><span class="f_left">Coupon</span><span class="f_right">' + data.coupon + '</span></p>';
            }

            summary_html += '<p class="clearfix"><span class="f_left">Current Plan</span><span class="f_right text-mid-grey">' + data.current + '</span></p>';

            summary_html += '<p class="clearfix"><span class="f_left">Change</span><span class="f_right">' + data.change + '</span></p>';

            if (data.discount) {
                summary_html += '<p class="clearfix"><span class="f_left">Discount</span><span class="f_right">' + data.discount + '</span></p>';
            }

            summary_html += '<p class="clearfix separator"></p>';

            summary_html += '<p class="text-bold clearfix"><span class="f_left">New Plan</span><span class="f_right">' + data.total + '</span></p>';

            summary_html += '<a id="confirm_order" class="button">Complete Order</a>';

            $('.summary_description').html('Monthly price includes proration.<br />Upon clicking complete order your monthly bill will be updated immediately to reflect these changes, and email notifications will be sent to any added users.');
        }
        $('#summary_info').html(summary_html);

        Foundation.reInit('abide');
        assignServiceAbide();
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// resend user invitation

var resendUserInvitation = function($btn) {
    $btn.addClass('disabled').html('Processing...');

    $.ajax({
        type        : 'POST',
        url         : '/service/manageDevice',
        dataType    : 'json',
        data        : {
            type    : 'resendInvite',
            user_id : $btn.data('id'),
        },
    })
    .done(function (response) {
        if (response.status == 'success') {
            $btn.html('Invitation has been sent');
        } else {
            showErrorText(response.message);
        }
    })
    .fail(function (response) {
        showErrorText(response.message ? response.message : response.statusText);
    });
}

// cancel user invitation

var cancelUserInvitation = function($btn) {
    $btn.addClass('disabled').html('Processing...');

    $.ajax({
        type        : 'POST',
        url         : '/service/manageDevice',
        dataType    : 'json',
        data        : {
            type    : 'cancelInvite',
            user_id : $btn.data('id'),
        },
    })
    .done(function (response) {
        if (response.status == 'success') {
            $btn.siblings('.resend_invite').fadeOut();
            $btn.html('Invitation has been cancelled');
        } else {
            showErrorText(response.message);
        }
    })
    .fail(function (response) {
        showErrorText(response.message ? response.message : response.statusText);
    });
}

/**
 * install device (send instructions or install here)
 * @param  DOM      $btn            - button triggering the action
 * @param  mixed    direct_install  - whether to install directly,
 *                                    if GUID is passed, send instruction to that user
 */
var installDevice = function($btn, direct_install) {
    var $container = $btn.parent();
    $container.html('Processing...');

    var user_id = 0;
    if (typeof direct_install === 'undefined' || direct_install === null) {
        direct_install = 1;
    } else if (direct_install !== false && direct_install !== true) {
        // install for a user
        user_id = direct_install;
        direct_install = 0;
    } else {
        // install for self
        direct_install = direct_install ? 1 : 0;
    }

    // process order
    $.ajax({
        type        : 'POST',
        url         : '/user-device/manageDevice',
        dataType    : 'json',
        data        : {
            type            : 'install',
            user_id         : user_id,
            direct_install  : direct_install,
        },
        encode      : true
    })
    .done(function (data) {
        if (data.status != 'success') {
            showErrorText(data.message, true);

            return false;
        }

        if (direct_install) {
            window.location.href = '/dnld/' + data.GUID + '?os=' + getPlatform();
        } else {
            showSuccessText(data.message);
            $container.html('Instruction sent');
        }
    })
    .fail(function (data) {
        showErrorText(data.message);
    });
}

// services form submit handler
var assignServiceAbide = function () {
    $('#frm_service').on('formvalid.zf.abide', function(ev, frm) {
        $('#spinner_container').show();

        // process order
        $.ajax({
            type        : 'POST',
            url         : '/service/manageDevice',
            dataType    : 'json',
            data        : $('#frm_service').serialize() + '&type=processOrder',
            encode      : true
        })
        .done(function (data) {
            if (data.status != 'success') {
                showErrorText(data.message, true);

                return false;
            }

            window.location.href = '/service';
        })
        .fail(function (data) {
            showErrorText(data.message);
        });
    });
}

//--- global functions ---

var numbersonly = function(e)
{
    var key;
    var keychar;

    if (window.event)
        key = window.event.keyCode;
    else if (e)
        key = e.which;
    else
        return true;

    keychar = String.fromCharCode(key);

    // control keys
    if ((key == null) || (key == 0) || (key == 8) || (key == 9) || (key == 13) || (key == 27))
        return true;

    // numbers
    else if ((('0123456789').indexOf(keychar) > -1))
        return true;

    // only one decimal point
    // else if ((keychar == "."))
    // {
    //     if (myfield.value.indexOf(keychar) > -1)
    //         return false;
    // }
    else
        return false;
}

var checkInputLength = function(len, element)
{
    var fieldLength = element.value.length;

    if (fieldLength <= len) {
        return true;
    } else {
        var str = element.value;
        str = str.substring(0, str.length - 1);
        element.value = str;
    }
}

var numberWithCommas = function(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

var validateEmail = function(input_email) {
  var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

  if (filter.test(input_email))
    return true;
  else
    return false;
}​

// get today date string in US format
var getCurrentDate = function () {
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth() + 1; // January is 0!
    var yyyy = today.getFullYear();

    if (dd < 10) {
        dd = '0' + dd;
    }

    if (mm < 10) {
        mm = '0' + mm;
    }

    today = mm + '-' + dd + '-' + yyyy;

    return today;
}
