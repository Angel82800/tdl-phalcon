/**
 * Main JS for handling events
 */

window.todyl = {};
var todyl = window.todyl;

jQuery.fn.putCursorAtEnd = function () {

  return this.each(function () {

    $(this).focus()

    // If this function exists...
    if (this.setSelectionRange) {
      // ... then use it (Doesn't work in IE)

      // Double the length because Opera is inconsistent about whether a carriage return is one character or two. Sigh.
      var len = $(this).val().length * 2;

      this.setSelectionRange(len, len);

    } else {
    // ... otherwise replace the contents with itself
    // (Doesn't work in Google Chrome)

      $(this).val($(this).val());

    }

    // Scroll to the bottom, in case we're in a tall textarea
    // (Necessary for Firefox and Google Chrome)
    this.scrollTop = 999999;

  });

};

$("#private-network-change").click(function () {
    if ($('#private-network-form').hasClass('active')) {
        resetSettingsForms();
    } else {
        resetSettingsForms();
        $('#private-network-form').toggleClass('active');
        $('#private-network-name').prop('disabled', false);
        $('#private-network-name').putCursorAtEnd();
        $('#private-network-form .password-confirmation').slideDown('slow');
    }
});

$("#public-network-change").click(function () {
    if ($('#public-network-form').hasClass('active')) {
        resetSettingsForms();
    } else {
        resetSettingsForms();
        $('#public-network-form').toggleClass('active');
        $('#public-network-name').prop('disabled', false);
        $('#public-network-password').prop('disabled', false);
        $('#public-network-password').val('');
        $('#public-network-password').removeAttr('placeholder');
        $('#public-network-name').putCursorAtEnd();
        $('#public-network-form .password-confirmation').slideDown('slow');
    }
});

$('#filtered-content-form label').click(function () {
    resetSettingsForms();
});

$(':input').on('keyup', function (e) {
    if(e.keyCode === 13) {      // pressed enter
        var form = $(this).parents('form:first');

        // ignore if this is the contact form with captcha
        // TODO: Figure out issue with settings page forms
        if (form.attr('class') == 'form-contact' || form.hasClass('settings-page-form') || form.hasClass('signup-form')) {
            return;
        }

        var submit = form.find(':submit');
        submit.click();
        // submit.prop('disabled', true); // avoid double entry
        return false; // prevents some browser from double submitting
    }
});

// validation on keyup
// $(document).on('keypress', '.is-invalid-input', function () {
//     console.log($(this).val());
//     if ($(this).val()) $(this).removeClass('is-invalid-input');
// });

/* Settings page javascript */
$('.settings-page-form').submit(function () {
    // Disable submit button once form is submitted to prevent double-submit
    $(this).find('input[type=submit]').prop('disabled', true);
});

$(document).ready(function () {
    // Foundation custom options
    Foundation.Orbit.defaults.timerDelay = 8000;
    Foundation.Abide.defaults.validators['positive_integer'] = function ($el, required, parent) {
        if (!required) return true;

        return (parseInt($el.val()) > 0);
    };

    $(document).foundation();

    Foundation.Abide.defaults.validators['less_than'] = function ($el, required, parent) {
        if (! required) return true;

        // var limit = $('#' + $el.attr('data-less-than')).val(),
        var limit = $el.data('less-than'),
        value = $el.val();

        return /^\+?(0|[1-9]\d*)$/.test(value) && 0 < parseInt(value) && parseInt(value) < parseInt(limit);
    };

    Foundation.Abide.defaults.validators['less_than_2'] = function ($el, required, parent) {
        if (! required) return true;

        // var limit = $('#' + $el.attr('data-less-than')).val(),
        var limit = $el.data('less-than'),
        value = $el.val();

        return /^\+?(0|[1-9]\d*)$/.test(value) && 0 <= parseInt(value) && parseInt(value) < parseInt(limit);
    };

    handleInputMaterialIE();

    adjustFooter();

    // prevent disabled link clicks
    $(document).on('click', 'a.disabled', function (e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    // Fix z-index sticky nav menu bug
    $('#todyl-nav-menu').on(
        'show.zf.dropdownmenu', function () {
            var dropdown = $(this).find('.is-dropdown-submenu');
            dropdown.fadeIn('slow');
    });

    $('#todyl-nav-menu').on(
        'hide.zf.dropdownmenu', function () {
            var dropdown = $(this).find('.is-dropdown-submenu');
            dropdown.css('display', 'block');
            dropdown.fadeOut('fast');
    });

    $('.feature-chart .columns.block span').each(function () {
        var divHeight = $(this).parent('div').height();
        if ($(this).height() < divHeight / 2) {
            $(this).css('lineHeight', divHeight  + 'px');
        }
    });

    var featureChartTitle = $('.feature-chart h6');
    featureChartTitle.css('lineHeight', $(featureChartTitle).parent('div').height() + 'px');

    // Registration Steps
    $('.form-group.error input,select').on('keyup keypress blur change', function (e) {
        // e.type is the type of event fired
        $(this).siblings('.error_text').fadeOut();
        $(this).parent().removeClass('error');
    });

    // password security check
    $('.form-group input#todyl_password:not(.no_hint)')
        .on('keydown keyup keypress change input', function () {
            var $self = $(this);

            if (! $self.siblings('.hint').length) {
                $('<div class="hint">Password Must Contain : <span id="pw_val_length">At least 7 Characters</span><span id="pw_val_number">A Number</span><span id="pw_val_symbol">A Symbol (?, !, *, &)</span></div>').appendTo($self.parent()).fadeIn();
            } else if (! $self.siblings('.hint').is(':visible')) {
                $self.siblings('.hint').fadeIn();
            }

            var password = $self.val();

            // length check
            if (password.length >= 7) {
                $('#pw_val_length').addClass('match');
            } else {
                $('#pw_val_length').removeClass('match');
            }

            var exp_number = new RegExp('[0-9]+');
            var exp_symbol = new RegExp('[!?^&*$]+');

            // number check
            exp_number.test(password) ? $('#pw_val_number').addClass('match')  : $('#pw_val_number').removeClass('match');

            // symbol check
            exp_symbol.test(password) ? $('#pw_val_symbol').addClass('match')  : $('#pw_val_symbol').removeClass('match');

        })
        .on('blur focusout', function () {
            if (! $(this).parent().find('.hint span:not(.match)').length) {
                $(this).parent().find('.hint').fadeOut();
            }
        });

    if ($('.registration-container').length) {
        // Sign up

        if (Foundation.MediaQuery.atLeast('medium')) {
            $('.registration-container').addClass('vertical-center');
        }

        // trim all text inputs on change
        $('.signup-form').on('blur change', 'input[type=text]', function () {
            if ($.trim($(this).val()) != $(this).val()) {
                // value has white spaces - remove and trigger change again to revalidate
                $(this).val($.trim($(this).val()));
                $(this).trigger('change');
            }
        });

        $('#todyl_phone_no').on('keyup', function () {
            var telno = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(formatPhoneNo(telno));
        });

        // package select buttons
        $(document).on('click', '.package_select_btn:not(.selected)', function () {
            $('.package_select_btn.selected').html('Select').removeClass('selected').siblings('input[type=radio][name=package]').prop('checked', false);
            $(this).html('Selected!').addClass('selected').siblings('input[type=radio][name=package]').prop('checked', true);
        });

        if ($('.stage_1').length) {
            // step 1 - account page

        }

        if ($('.stage_2').length) {
            // step 2 - about you page

            $('.signup-form').on('keypress', 'input[type=text]', function () {
                const $empty_inputs = $('input:required').filter(function () { return this.value == ""; });

                if (! $empty_inputs.length) {
                    // enable submit button if the required inputs are all filled in
                    $('.signup-form :submit').removeClass('disabled');
                } else {
                    // disable submit button if the required inputs are not filled in
                    $('.signup-form :submit').addClass('disabled');
                }
            });

        }

        // sign up form submit handler - perform per-step actions here
        $('.signup-form').submit(function () {
            if (! validateForm($(this))) return false;

            var current_step = $('#current_step').val();

            switch (current_step) {
                case '1':
                    // step 1 - Account

                    var $btn = $(this).find(':submit');

                    $btn.val('Processing...').prop('disabled', true);

                    if ($('#signup-recaptcha').data('valid') !== 'yes') {
                        grecaptcha.execute(todyl.captcha3);

                        $btn.val('Next').prop('disabled', false);

                        return false;
                    }

                    // prevent form submit if hint is not all met
                    if ($(this).find('.hint span:not(.match)').length) {
                        $(this).find('.hint').fadeTo(100, 0.1).fadeTo(200, 1.0);

                        $btn.val('Next').prop('disabled', false);

                        return false;
                    }

                    return true;
                case '2':
                    // step 2 - About You

                    return true;

                default:
                    break;
            }

            // if no interruptions, submit the form
            return true;
        });

        if ($('.splash').length) {

            // trying this a different way fbq('track', 'Lead');
            // splash after registration first step
            // Here we need to check if they have at least windows 7, and mac osx 10.10.

            var info = detectOS(), requirements_met = false;

            if (info.mobile) {
                // mobile
                $('.os_mobile').removeClass('hide');
            } else {
                // desktop
                $('.os_desktop').removeClass('hide');

                var min_requirement = 'Windows 7 or Mac OS X 10.10';
                if (info.os == 'Windows') {
                    // At least Windows 7
                    if ($.inArray(info.osVersion, [ 7, 8, 8.1, 10 ])) {
                        requirements_met = true;
                    } else {
                        min_requirement = 'Windows 7';
                    }
                } else if (info.os == 'Mac OS X') {
                    // At least Mac OS X 10.10
                    var osVersion = info.osVersion.replace('_', '.');

                    if (parseFloat(osVersion) >= 10.1) {
                        requirements_met = true;
                    } else {
                        min_requirement = 'Mac OS X 10.10';
                    }
                }
            }

            // TODO - remove this in production
            // requirements_met = true;

            setTimeout(function () {
                if (requirements_met) {
                    window.location.href = $('.splash').attr('next');
                } else {
                    $('#detected_os').html(info.os + (info.osVersion == '-' ? '' : ' ' + info.osVersion));
                    $('#os_min_requirement').html(min_requirement);

                    $('.splash_content').fadeOut(300, function () {
                        $('#requirements_notification').fadeIn();
                    });
                }
            }, $('.splash').attr('delay'));
        }
    }


    // billing
    if ($('.billing-container').length) {
        var $billingForm = $('.billing-form');

        updateSummary();

        if ($('#billing_summary_container').length) {
            $('#billing_summary_container').stickybits({ stickyBitStickyOffset: 30 });
        }

        $('#terms-yes').on('change', function () {
            if ($(this).is(':checked')) {
                $(this).closest('.highlight').removeClass('highlight').addClass('unhighlight');
                $billingForm.find('input:submit').attr('disabled', false);
            } else {
                $(this).closest('.unhighlight').addClass('highlight').removeClass('unhighlight');
                $billingForm.find('input:submit').attr('disabled', true);
            }
        });
        $('#terms-yes').trigger('change');

        $('#promo_code').on('keyup change blur input', function () {
            if ($(this).val()) {
                $('#apply_promo').removeClass('btn-link').prop('disabled', false);
            } else {
                $('#apply_promo').addClass('btn-link').prop('disabled', true);
            }
        })

        $('#apply_promo').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);

            $btn.html('Checking...').prop('disabled', true);
            $.ajax({
                type        : 'POST',
                url         : '/pricing/applyCoupon',
                dataType    : 'json',
                data        : {
                    promo_code: $('#promo_code').val(),
                },
            })
            .done(function (data) {
                if (data.status === 'success') {
                    $btn.hide();

                    $('#promo_code').prop('readonly', true);
                    $('#remove_promo').html('Remove').prop('disabled', false).fadeIn();
                    $('#promo_description').html(data.message).removeClass('fail').addClass('success').fadeIn();

                    if (data.billing_date) {
                        $('.charge_text').html('With this coupon, you won\'t be charged until ' + data.billing_date + '.');
                    } else {
                        $('.charge_text').html('After your free trial, you will be charged ' + data.price + ' per month.');
                    }

                    // if ($('.monthly_fee').length) {
                    //     $('.monthly_fee').html(data.price);
                    // }

                    updateSummary();
                } else {
                    $btn.html('Apply').prop('disabled', false);
                    $('#promo_description').html(data.message).removeClass('success').addClass('fail').fadeIn();
                }
            })
            .fail(function (response) {
                if (response.status == 500) {
                    showErrorText('Promo code is not valid');
                }

                $('#promo_code').prop('readonly', false);
                $btn.html('Apply').prop('disabled', false);
            });
        });

        $('#remove_promo').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);

            $btn.html('Removing...').prop('disabled', true);
            $.ajax({
                type        : 'POST',
                url         : '/pricing/removeCoupon',
                dataType    : 'json',
            })
            .done(function (data) {
                $btn.hide();
                $('#promo_code').val('').prop('readonly', false);
                $('#apply_promo').addClass('btn-link').html('Apply').prop('disabled', true).fadeIn();

                $('#promo_description').html('').removeClass('success fail').fadeOut();
                $('#coupon_reflection').fadeOut();

                if (data.billing_date) {
                    $('.charge_text').html('With this coupon, you won\'t be charged until ' + data.billing_date + '.');
                } else {
                    $('.charge_text').html('After your free trial, you will be charged ' + data.price + ' per device per month.');
                }

                // if ($('.monthly_fee').length) {
                //     $('.monthly_fee').html(data.price);
                // }

                updateSummary();
            })
            .fail(function (response) {
                $('#promo_code').prop('readonly', true);
                $btn.html('Remove').prop('disabled', false);
            });
        });

        if ($('#promo_code').val()) {
            // promo code present
            $('#apply_promo').trigger('click');
        }

        // Create an instance of Elements
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        // (Note that this demo uses a wider set of styles than the guide below.)
        var style = {
          base: {
            color: '#0a0a0a',
            lineHeight: '25px',
            fontFamily: '"Oxygen", Helvetica, Roboto, Arial, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
              color: '#999'
            },
          },
          invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
          }
        };

        // Create an instance of the card Element
        var card = elements.create('card', {
            style: style,
            classes: {
                base: 'card-number',
                focus: 'is-focused',
                empty: 'is-empty',
            },
        });

        // Add an instance of the card Element into the `card-element` <div>
        card.mount('#card-element');

        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function (event) {
            var $displayError = $('#card-errors');

            if (event.error) {
                $displayError.html(event.error.message).fadeIn();
            } else {
                $displayError.html('').fadeOut();
            }
        });

        $('#change_card').on('click', function () {
            $(this).closest('.step_label').slideUp();
            $('#card-element').closest('.step_label.hide').removeClass('hide');
        });

        // billing form submit handler
        $('.billing-form').submit(function () {
            if (! validateForm($(this))) return false;

            // check if terms checkbox is checked
            if (! $('#terms-yes').is(':checked')) {
                return false;
            }

            var $form = $(this);
            var $submitBtn = $form.find('input:submit');

            if ($('#card-element').is(':visible')) {
                $submitBtn.val('Processing...').prop('disabled', true);

                stripe.createToken(card, { name: $('#name_on_card').val() }).then(function (result) {

                    if (result.error) {
                        // Inform the user if there was an error
                        $('#card-errors').html(result.error.message).fadeIn();

                        $submitBtn.val('Complete Your Order').prop('disabled', false);
                    } else {
                        // success - save token value and submit

                        $('#card_token').val(result.token.id);

                        $('#user_platform').val(getPlatform());

                        // submit form
                        $form.unbind('submit').submit();

                    }
                });

            } else {
                $('#card_token').val('');
                return true;
            }

            return false;

        });

    }

    if ($('.signin-form').length) {
        if (Foundation.MediaQuery.atLeast('medium')) {
            $('.signin_container').addClass('vertical-center');
        }

        $('.signin-form input').on('keyup change blur input', function () {
            var is_valid = true;

            $('.signin-form input[type=text],input[type=password]').each(function () {
                if (! $(this).val()) {
                    is_valid = false;
                    return false;
                }
            });

            if (is_valid) {
                $('.signin-form input[type=submit]').prop('disabled', false);
            } else {
                $('.signin-form input[type=submit]').prop('disabled', true);
            }
        });
    }

    $(document).on('invalid.zf.abide', function (ev, elem) {
        if (elem.siblings('.form-error').length) {
            var $error_container = elem.siblings('.form-error');

            if ($error_container.data('force-msg')) {
                // force messages to override others
                $error_container.html($error_container.data('force-msg'));
            } else if (! elem.val().trim()) {
                // required check (foundation checks for pattern by default)
                $error_container.html('This field is required');
            } else if ($error_container.data('msg')) {
                // error message set in base form
                $error_container.html($error_container.data('msg'));
            }
        }
    });

    if ($('.beta-signup-form').length) {
        $('#user_platform').val(getPlatform());

        $('#primary_use').val('business');
        handlePrimaryType();

        $('#primary_use').on('change', function () {
            handlePrimaryType();
        });

        // trim all text inputs on change
        $('.beta-signup-form').on('blur change', 'input[type=text]', function () {
            if ($.trim($(this).val()) != $(this).val()) {
                // value has white spaces - remove and trigger change again to revalidate
                $(this).val($.trim($(this).val()));
                $(this).trigger('change');
            }
        });

        $('#todyl_phone_no').on('keyup', function () {
            var telno = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(formatPhoneNo(telno));
        });

        $('.beta-signup-form').submit(function () {
            if (! validateForm($(this))) return false;

            // beta promo code check
            if ($('#promo_code').val().substr(0, 4).toLowerCase() !== 'beta' || $('#apply_promo').is(':visible') || ! $('#promo_code').prop('readonly')) {
                $('#promo_description').html('Please enter your Beta code').removeClass('success').addClass('fail').fadeIn();
                return false;
            }

            // prevent form submit if hint is not all met
            if ($(this).find('.hint span:not(.match)').length) {
                $(this).find('.hint').fadeTo(100, 0.1).fadeTo(200, 1.0);

                return false;
            }

            // captcha check
            if ($('#signup-recaptcha').data('valid') !== 'yes') {
                grecaptcha.execute(todyl.captcha3);
                return false;
            }

            return true;
        });
    }

    if ($('.pwreset-form').length) {
        $('.pwreset-form').submit(function () {
            if (! validateForm($(this))) return false;

            // prevent form submit if hint is not all met
            if ($(this).find('.hint span:not(.match)').length) {
                $(this).find('.hint').fadeTo(100, 0.1).fadeTo(200, 1.0);

                return false;
            }

            return true;
        });
    }

    // vcenter element
    if (Foundation.MediaQuery.current !== 'small') {
        vcenterElement($('.v-center'));
    } else {
        $('.v-center').addClass('mt-2 mb-2');
        $('.v-center .pt-1').removeClass('pt-1');
    }

    // toggle password show/hide
    $('.toggle_pwd').on('click', function () {
        var field = $(this).siblings('input');
        var type = field.attr('type');

        if (type == 'text') {
            field.attr('type', 'password');
            $(this).html('Show');
        } else {
            field.attr('type', 'text');
            $(this).html('Hide');
        }
    });

    // Billing options styling
    $('.billing_options .tc_option input[type=radio]').on('change', function () {
        $(this).parents('.tc_option:first').addClass('active').siblings('.tc_option').removeClass('active');

        // if ($(this).val() == 'annual') {
        //     $('input[name=get_early_access]').prop('checked', true).prop('disabled', true);
        // } else if ($(this).val() == 'monthly') {
        //     $('input[name=get_early_access]').prop('checked', false).prop('disabled', false);
        // }
    });

    $(".scroll").click(function (event){
        event.preventDefault();
        $('html,body').animate({scrollTop:$(this.hash).offset().top-96}, 500);
    });

    //--- scrolled nav ---
    $(window).scroll(function () {
        var window_top_position = $(this).scrollTop();

        if (window_top_position >= 200 && ! $('.logo').hasClass('dark')) {
            $('.navbar').addClass('scrolledNav');
            $('.logo').addClass('dark');
        } else if (window_top_position < 200 && $('.logo').hasClass('dark')) {
            $('.navbar').removeClass('scrolledNav');
            $('.logo').removeClass('dark');
        }
    });

    if ($('#homepage-intro').length) {
        // new homepage

        scaleHomepageIntro();

        // scroll depth tracking - per percentage 25%, 50%, 75%
        jQuery.scrollDepth();

        $(window).on('resize', function () {
            scaleHomepageIntro();
        });

        $(window).scroll(function () {
            var window_height = $(this).height();
            var window_top_position = $(this).scrollTop();
            var window_bottom_position = window_top_position + window_height;

            // start counter when it's above four fifth of window height
            var window_half_position = window_top_position + window_height / 5 * 4;

            // counter
            $.each($('.counter:not(.counted)'), function () {
                if ($(this).offset().top <= window_half_position) {
                    startCounter($(this));
                    $(this).addClass('counted');
                }
            });

            // trigger product animations
            var elementClasses = [
                '.productDefenderBG',
                '.productShieldBG',
                '.productServiceBG',
                '.partnerSuperiorProtection',
                '.partnerSimpleAffordable',
                '.partnerMoreTime',
                '.partnerStrengthenTeam',
            ];

            elementClasses.forEach(function (elementClass) {
                if ($(elementClass).length && $(elementClass).offset().top <= window_bottom_position) {
                    $(elementClass).addClass('start');
                }
            });
        });
    }

    // dynamic heights for new homepage

    introSizing();

    $(window).on('resize', function () {
        introSizing();
        Foundation.reInit('equalizer');
    });

    // Modal Videos (Instructional, Installation, About, Etc) //

    (function () {

    var video_path = "";

    $(".video_about").click(function () {
        video_path = "https://www.youtube.com/embed/NoEezyL6iS8?rel=0&amp;controls=0&amp;showinfo=0";
    })

    if (navigator.userAgent.indexOf('Mac OS X') != -1) {
        $(".video_install").click(function () {
            video_path = "https://www.youtube.com/embed/smMgHH4dGUM?rel=0&amp;controls=0&amp;showinfo=0";
        })
    } else {
        $(".video_install").click(function () {
            video_path = "https://www.youtube.com/embed/qAhGycWM6is?rel=0&amp;controls=0&amp;showinfo=0";
        })
    }

  var pop_up_video = $('#pop_up_video'),
    pop_up_video_iframe = $('#pop_up_video_iframe'),
    close_pop_up_video_id = $('#pop_up_video_bg'),
    open_pop_up_video_class = $('.open_pop_up_video');

    // Pop-up Video

    var close_pop_up_video = function (event){
        event.preventDefault();
        pop_up_video_iframe.attr('src', '');
        pop_up_video.css('display', 'none');
    };
    close_pop_up_video_id.on('click', close_pop_up_video);


    var open_pop_up_video = function (event){
        event.preventDefault();
        pop_up_video_iframe.attr('src', video_path);
        pop_up_video.css('display', 'block');
    };

    open_pop_up_video_class.on('click', open_pop_up_video);

}());


    if ($('#homepage-services').length) {
        if (Foundation.MediaQuery.atLeast('large')) {

            scaleHomepageIntro();

            $(window).on('resize', function () {
                scaleHomepageIntro();
            });
        } else {

            $('.product_img').each(function () {
                $(this).css('background-image', 'url(' + $(this).children('img').attr('src') + ')');
                $(this).children('img').hide();
            });

            scaleHomepageIntro();

            $(window).scroll(function () {
                showServiceDescription($('#service_defender'));
                showServiceDescription($('#service_shield'));
            });

            $('.mobile-nav-menu').on('click', 'a', function () {
                // auto-close nav menu when clicking menu item
                $(".off-canvas .close-button").click();
            });
        }

    }

    // download page -> would like to add some error handling if copy fails.
    if ($('.download_container').length && $('.download_container').is(':visible')) {
        // if os url is not set, redirect to proper page
        checkOS();

        $('#copy_pin').parent().on('click', copyPin);
        // $('#copy_pin').on('click', copyPin);

        checkDeviceInstall($('#pin_no').html());
    }

    //-- dashboard pages --//

    // dashboard page
    if ($('.dashboard-container').length) {
        // internet threat level management
        $('#btn_edit_threat_level').on('click', function () {
            $('#dlg_manage_threat_level').foundation('open');
        });

        $('#dlg_manage_threat_level')
            .on('change', 'input[name=threat_level]', function () {
                $('textarea[name=threat_level_description]').val(threat_level_descriptions[$(this).val()]);
            })
            .on('click', '#btn_confirm_topic', function () {
                $.ajax({
                    type        : 'POST',
                    url         : '/dashboard/manage',
                    dataType    : 'json',
                    data        : {
                        type            : 'threat_level',
                        threat_level    : $('input[name=threat_level]:checked').val(),
                        description     : $('textarea[name=threat_level_description]').val(),
                    },
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        $('#dlg_manage_threat_level').foundation('close');
                        showSuccessText(response.message);
                        updateDashboardStats();
                    } else {
                        showErrorText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message);
                });
            })
        ;

        $('input[name=threat_level]:checked').change();

        var days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        var days_short = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        var goBackDays = 7;

        var today = new Date();
        var daysSorted = [];
        var daysSorted_short = [];

        for(var i = 0; i < goBackDays; i++) {
            var newDate = new Date(today.setDate(today.getDate() - 1));
            daysSorted.push(days[newDate.getDay()]);
            daysSorted_short.push(days_short[newDate.getDay()]);
        }
        daysSorted[0] = 'Today';

        //--- start potential threats blocked chart ---
        var ctx_blocks = document.getElementById('chart_blocks').getContext('2d');

        var data_blocks = {
            labels: daysSorted.reverse(),
        };

        // chart options to be used in common
        var chart_options = {
            responsive: false,
            animation: false,
            legend: {
                display: false,
            },
            scales : {
                xAxes: [{
                    gridLines: {
                        display: false,
                        zeroLineColor: "transparent",
                    },
                    ticks: {
                        fontColor: '#BCC3C5',
                        fontSize: 14,
                    },
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        fontColor: '#BCC3C5',
                        fontSize: 14,
                        callback: function (label, index, labels) {
                            return numberWithCommas(label);
                        },
                    },
                    gridLines: {
                        color: '#BCC3C5',
                    },
                }],
            },
            tooltips: {
                xPadding: 12,
                yPadding: 10,
                cornerRadius: 3,
                custom: function (tooltip) {
                    if (! tooltip) return;
                    // disable displaying the color box
                    tooltip.displayColors = false;
                },
                callbacks: {
                    label: function (tooltipItem, data) {
                        var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || 'Value';
                        return datasetLabel + ': ' + numberWithCommas(tooltipItem.yLabel);
                    },
                }
            },
        };

        chart_blocks = new Chart(ctx_blocks, {
            type: 'line',
            height: 250,
            data: data_blocks,
            options: chart_options,
        });

        $('#chart_blocks').hide();

        //--- end potential threats blocked chart ---

        //--- start threat indicators added chart ---
        var ctx_indicators = document.getElementById('chart_indicators').getContext('2d');

        var data_indicators = {
            labels: daysSorted_short.reverse(),
        };

        chart_indicators = new Chart(ctx_indicators, {
            type: 'line',
            height: 250,
            data: data_indicators,
            options: chart_options,
        });

        //--- end threat indicators added chart ---

        // spinner
        $('#spinner_container').show();

        updateDashboardStats();

        // update stats every 30 seconds
        setInterval(function () {
            updateDashboardStats();
        }, 30 * 1000);

        // adjust equalizer heights on window resize
        $(window).on('resize', function () {
            new Foundation.Equalizer($('.row[data-equalizer=first-row]')).applyHeight();
            new Foundation.Equalizer($('.row[data-equalizer=second-row]')).applyHeight();
        });
    }

    // account page
    if ($('.account-container').length) {
        $('.phone_no').html(formatPhoneNo($('.phone_no').html()));

        // account settings
        $('.account-settings')
            .on('keyup', '.phone_no', function () {
                var telno = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(formatPhoneNo(telno));
            })
            .on('click', '.edit', function () {
                // remove all current expanded fields

                resetAccountFields($('.account-settings'));

                // reset password fields
                $('.account-settings .pw_field').removeClass('hide');
                $('#frm_password').addClass('hide');

                // convert static container to edit

                var $container = $('.account-settings .row.expanded:not(.pw_field)');

                $container.find('.edit-field').each(function () {
                    $(this).removeClass('hide').find('input').val($(this).siblings('.static-field').html().trim());
                });
                $container.find('.static-field').addClass('hide');

                $('.account-settings .edit').addClass('hide');
                $('.account-settings').find('.edit_action').removeClass('hide');
            })
            // change password
            .on('click', '.change_pw', function () {
                resetAccountFields($('.account-settings'));

                $('.account-settings .pw_field').addClass('hide');
                $('#frm_password').removeClass('hide');
            })
            .on('click', '.cancel', function () {
                resetAccountFields($('.account-settings'));

                // reset password fields
                $('.account-settings .pw_field').removeClass('hide');
                $('#frm_password').addClass('hide');
            })
            .on('click', '.save', function (e) {
                e.preventDefault();

                var $btn = $(this);

                var type = $btn.data('form');
                var $form = $('#frm_' + type);

                if (! validateForm($form)) {
                    return false;
                }

                if (type == 'password') {
                    if ($form.find('.hint span:not(.match)').length) {
                        $form.find('.hint').fadeTo(100, 0.1).fadeTo(200, 1.0);

                        return false;
                    }
                }

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/account/manage',
                    dataType    : 'json',
                    data        : $form.serialize() + '&type=' + type
                })
                .done(function (response) {
                    $btn.html('Save Changes').removeClass('disabled');

                    if (response.status == 'success') {
                        if (type == 'account') {
                            $form.find('.static-field').each(function () {
                                $(this).html($(this).siblings('.edit-field').children('input').val());
                            });
                        }

                        resetAccountFields($('.account-settings'));

                        // reset password fields
                        $('.account-settings .pw_field').removeClass('hide');
                        $('#frm_password').addClass('hide');

                        // remove light grey edit buttons
                        $('.account-settings .edit.text-light-grey').remove();

                        showSuccessText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
            // advanced view switch
            .on('change', '#advanced_view', function () {
                const $switch = $(this);
                const isChecked = $switch.is(':checked');

                $switch.addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/account/manage',
                    dataType    : 'json',
                    data        : {
                        type    : 'advanced_view',
                        value   : isChecked,
                    },
                })
                .done(function (response) {
                    $switch.removeClass('disabled');

                    if (response.status == 'success') {
                        showSuccessText(response.message);
                    }
                })
                .fail(function (response) {
                    $switch.removeClass('disabled');
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        // billing settings
        if ($('#frm_billing').length) {

            $('.billing-settings')
                .on('click', '.edit', function () {
                    $('#frm_billing').slideDown();
                })
                .on('click', '.cancel', function () {
                    $('#frm_billing').slideUp();
                })
            ;

            // billing information - stripe initialization

            // Create an instance of Elements
            var elements = stripe.elements();

            var style = {
              base: {
                color: '#0a0a0a',
                lineHeight: '20px',
                fontFamily: '"Oxygen", Helvetica, Roboto, Arial, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                  color: '#999'
                },
              },
              invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
              }
            };

            // Create an instance of the card Element
            var card = elements.create('card', {
                style: style,
                classes: {
                    base: 'card-number',
                    focus: 'is-focused',
                    empty: 'is-empty',
                },
            });

            // Add an instance of the card Element into the `card-element` <div>
            card.mount('#card-element');

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                var $displayError = $('#card-errors');

                if (event.error) {
                    $displayError.html(event.error.message).fadeIn();
                } else {
                    $displayError.html('').fadeOut();
                }
            });

            $('#frm_billing').on('submit', function () {
                if (! validateForm($(this)) || $('#card-errors').is(':visible')) return false;

                var $form = $(this);
                var $submitBtn = $form.find(':submit');

                $submitBtn.val('Processing...').prop('disabled', true);

                stripe.createToken(card, { name: $('#name_on_card').val() }).then(function (result) {
                    if (result.error) {
                        // Inform the user if there was an error
                        $('#card-errors').html(result.error.message).fadeIn();

                        $submitBtn.val('Update').prop('disabled', false);
                    } else {
                        // success - save token value and submit

                        $('input[name=card_token]').val(result.token.id);

                        // save information
                        $.ajax({
                            type        : 'POST',
                            url         : '/account/manage',
                            dataType    : 'json',
                            data        : $('#frm_billing').serialize() + '&type=billing',
                        })
                        .done(function (response) {
                            if (response.status == 'success') {
                                // successfully updated card
                                showSuccessText(response.message);

                                $('#current_card').html(response.card_info);
                                $('#frm_billing').slideUp();

                                $submitBtn.val('Update').prop('disabled', false);
                            } else {
                                showErrorText(response.message);
                            }
                        })
                        .fail(function (response) {
                            showErrorText(response.message ? response.message : response.statusText);
                        });
                    }
                });

                return false;
            });
        }

        $('.account-container')
            .on('click', '.edit_account_status', function () {
                $('.account-container').fadeOut(300, function () {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_suspend_1').fadeIn();
                });
            })
            .on('click', '.edit_email_settings', function () {
                $('.account-container').fadeOut(300, function () {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_email_settings').fadeIn();
                });
            })
            .on('click', '.view_full_history', function () {
                // view full stripe history
                $('.account-container').fadeOut(300, function () {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_invoice_history').fadeIn();
                });
            })

            // reactivate account
            .on('click', '#btn_trigger_reactivate', function () {
                $('.account-container').fadeOut(300, function () {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_reactivate_billing').fadeIn();
                });
            })
        ;

        if ($('#panel_reactivate_billing').length) {
            // reactivate page

            $('#promo_code').on('keyup change blur input', function () {
                if ($(this).val()) {
                    $('#apply_promo').prop('disabled', false);
                } else {
                    $('#apply_promo').prop('disabled', true);
                }
            })

            $('#apply_promo').on('click', function (e) {
                e.preventDefault();

                var $btn = $(this);

                $btn.html('Checking...').prop('disabled', true);
                $.ajax({
                    type        : 'POST',
                    url         : '/pricing/applyCoupon',
                    dataType    : 'json',
                    data        : {
                        promo_code: $('#promo_code').val(),
                    },
                })
                .done(function (data) {
                    if (data.status === 'success') {
                        $btn.hide();

                        $('#promo_code').prop('readonly', true);
                        $('#remove_promo').html('Remove').prop('disabled', false).fadeIn();
                        $('#promo_description').html(data.message).removeClass('fail').addClass('success').fadeIn();

                        if (data.billing_date) {
                            $('.charge_text').html('With this coupon, you won\'t be charged until ' + data.billing_date + '.');
                        } else {
                            $('.charge_text').html('After your free trial, you will be charged ' + data.price + ' per month.');
                        }

                        // if ($('.monthly_fee').length) {
                        //     $('.monthly_fee').html(data.price);
                        // }

                        updateSummary();
                    } else {
                        $btn.html('Apply').prop('disabled', false);
                        $('#promo_description').html(data.message).removeClass('success').addClass('fail').fadeIn();
                    }
                })
                .fail(function (response) {
                    if (response.status == 500) {
                        showErrorText('Promo code is not valid');
                    }

                    $('#promo_code').prop('readonly', false);
                    $btn.html('Apply').prop('disabled', false);
                });
            });

            $('#remove_promo').on('click', function (e) {
                e.preventDefault();

                var $btn = $(this);

                $btn.html('Removing...').prop('disabled', true);
                $.ajax({
                    type        : 'POST',
                    url         : '/pricing/removeCoupon',
                    dataType    : 'json',
                })
                .done(function (data) {
                    $btn.hide();
                    $('#promo_code').val('').prop('readonly', false);
                    $('#apply_promo').addClass('btn-link').html('Apply').prop('disabled', true).fadeIn();

                    $('#promo_description').html('').removeClass('success fail').fadeOut();
                    $('#coupon_reflection').fadeOut();

                    if (data.billing_date) {
                        $('.charge_text').html('With this coupon, you won\'t be charged until ' + data.billing_date + '.');
                    } else {
                        $('.charge_text').html('After your free trial, you will be charged ' + data.price + ' per device per month.');
                    }

                    // if ($('.monthly_fee').length) {
                    //     $('.monthly_fee').html(data.price);
                    // }

                    updateSummary();
                })
                .fail(function (response) {
                    $('#promo_code').prop('readonly', true);
                    $btn.html('Remove').prop('disabled', false);
                });
            });

            if ($('#promo_code').val()) {
                // promo code present
                $('#apply_promo').trigger('click');
            }

        }

        $('.dialog_panels')
            .on('click', '.cancel', function () {
                $('.dialog_panels .panel').fadeOut(300, function () {
                    $('.dialog_panels').hide();
                    $('.account-container').fadeIn();
                });
            })
            .on('click', '#btn_suspend_1', function () {
                $('#panel_suspend_1').fadeOut(300, function () {
                    $('#panel_suspend_2').fadeIn();
                });
            })
            .on('click', '#btn_suspend_2', function () {
                // confirm suspension

                var $btn = $(this);

                if (! $('#suspension_current_pw').val().trim()) {
                    return false;
                }

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/account/manage',
                    dataType    : 'json',
                    data        : {
                        type        : 'checkpw',
                        current_pw  : $('#suspension_current_pw').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        $('#panel_suspend_1').fadeOut(300, function () {
                            window.location.href = response.redirectUrl;
                        });
                    } else {
                        showErrorText(response.message);
                        $btn.html('Complete Process').removeClass('disabled');
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
            .on('click', '#btn_update_email_settings', function () {
                // update email settings

                $.ajax({
                    type        : 'POST',
                    url         : '/account/manage',
                    dataType    : 'json',
                    data        : {
                        type        : 'email_settings',
                        setting     : $('input[name=email_setting]:checked').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        $('#stat_email_setting').html(response.frequency);

                        $('#panel_suspend_1').fadeOut(300, function () {
                            $('.dialog_panels').hide();
                            $('.account-container').fadeIn();

                            showSuccessText(response.message);
                        });
                    } else {
                        showErrorText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        if ($('.splash').length) {
            // suspension in progress

            $.ajax({
                type        : 'POST',
                url         : '/account/manage',
                dataType    : 'json',
                data        : {
                    type    : 'suspend',
                }
            })
            .done(function (response) {
                if (response.status == 'success') {
                    window.location.href = '/session/login';
                } else {
                    $('.splash_content').fadeOut(300, function () {
                        $('#failure_notification').fadeIn();
                    });
                }
            })
            .fail(function (response) {
                showErrorText(response.message ? response.message : response.statusText);
            });
        }

        if ($('.inactive_account').length) {
            // billing information - stripe initialization

            // Create an instance of Elements
            var elements = stripe.elements();

            var style = {
              base: {
                color: '#0a0a0a',
                lineHeight: '20px',
                fontFamily: '"Oxygen", Helvetica, Roboto, Arial, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                  color: '#999'
                },
              },
              invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
              }
            };

            // Create an instance of the card Element
            var card = elements.create('card', {
                style: style,
                classes: {
                    base: 'card-number',
                    focus: 'is-focused',
                    empty: 'is-empty',
                },
            });

            // Add an instance of the card Element into the `card-element` <div>
            card.mount('#card-element');

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                var $displayError = $('#card-errors');

                if (event.error) {
                    $displayError.html(event.error.message).fadeIn();
                } else {
                    $displayError.html('').fadeOut();
                }
            });

            $('.btn_change_card').on('click', function () {
                if (! $('#update_card').is(':visible')) {
                    // update card
                    $('#update_card').slideDown();
                    $('#btn_reactivate_account').html('Update Card');
                    $(this).html('Use Existing Card');
                } else {
                    // use this card
                    $('#update_card').slideUp();
                    $('#btn_reactivate_account').html('Continue to use this Card');
                    $(this).html('Use A Different Card');
                }
            });

            $('#btn_reactivate_account').on('click', function () {
                $('#frm_reactivate_billing').submit();
            });

            $('#frm_reactivate_billing').on('submit', function () {
                var $form = $('#frm_reactivate_billing');
                var $submitBtn = $('#btn_reactivate_account');

                $submitBtn.html('Processing...').addClass('disabled');

                if (! $('#name_on_card').val()) {
                    // stripe form is not filled - continue with no card

                    reactivateAccount();
                } else if (validateForm($form) && ! $('#card-errors').is(':visible')) {
                    // stripe form is filled

                    stripe.createToken(card, { name: $('#name_on_card').val() }).then(function (result) {
                        if (result.error) {
                            // Inform the user if there was an error

                            $('#card-errors').html(result.error.message).fadeIn();

                            $submitBtn.html('Update').removeClass('disabled');
                        } else {
                            // success - save token value and submit

                            $('input[name=card_token]').val(result.token.id);

                            reactivateAccount();
                        }
                    });
                }

                return false;
            });

        }

    }

    // device page
    if ($('.devices-container').length) {
        $('.toggle_expand').on('click', function () {
            // expand/shrink device info

            var $container = $(this).closest('.device_container');

            if ($container.hasClass('expanded')) {
                // shrink
                $container.children('.row:nth-child(2)').slideUp();
                $(this).children('i').removeClass('i-minus').addClass('i-plus');
            } else {
                // expand
                $container.children('.row:nth-child(2)').slideDown();
                $(this).children('i').removeClass('i-plus').addClass('i-minus');
            }

            $container.toggleClass('expanded');
        });

        $('#btn_management').on('click', function () {
            // deactivate device

            $.ajax({
                type        : 'POST',
                url         : '/device/manage',
                dataType    : 'json',
                data        : {
                    type        : $(this).data('type'),
                    device      : $(this).data('device'),
                },
            })
            .done(function (response) {
                if (response.status == 'success') {
                    // successfully updated device - redirect to devices page
                    window.location.href = '/device';
                } else {
                    showErrorText(response.message);
                }
            })
            .fail(function (response) {
                showErrorText(response.message ? response.message : response.statusText);
            });
        });

        $('#btn_renamedevice').on('click', function () {
            // rename device

            if (! $('#new_device_name').val().trim()) return;

            $.ajax({
                type        : 'POST',
                url         : '/device/manage',
                dataType    : 'json',
                data        : {
                    type        : 'rename',
                    device      : $(this).data('device'),
                    new_name    : $('#new_device_name').val(),
                },
            })
            .done(function (response) {
                if (response.status == 'success') {
                    // successfully renamed device - redirect to devices page
                    window.location.href = '/device';
                } else {
                    showErrorText(response.message);
                }
            })
            .fail(function (response) {
                showErrorText(response.message ? response.message : response.statusText);
            });
        });

    }

    // user & devices page
    if ($('.userdevices_container').length) {
        // show spinner
        $('#spinner_container').show();

        eval(userdevice_func);

        $('.userdevices_container')
            // .on('click', '.view-switcher', function () {
            //     var type = $(this).data('type');
            //     var devices = $('ul#device_list');
            //     var classNames = $(this).attr('class').split(' ');

            //     if ($(this).hasClass('active')) {
            //         // the view is already active
            //         return false;
            //     } else {
            //         // switch view

            //         if (type == 'grid') {
            //             $(this).addClass('active');
            //             $('.view-switcher[data-type=list]').removeClass('active');

            //             // remove the list class and change to grid
            //             devices.removeClass('list');
            //             devices.addClass('grid');

            //         } else if (type == 'list') {
            //             $(this).addClass('active');
            //             $('.view-switcher[data-type=grid]').removeClass('active');

            //             // remove the grid view and change to list
            //             devices.removeClass('grid')
            //             devices.addClass('list');
            //         }
            //     }
            // })

            // need additional service panel
            .on('click', '.panel_add_device', function () {
                window.location.href = '/service/device';
            })

            // resend invitation
            .on('click', '.resend_invite:not(.disabled)', function () {
                var $btn = $(this);

                resendUserInvitation($btn);
            })

            // cancel invitation
            // .on('click', '.cancel_invite:not(.disabled)', function () {
            //     var $btn = $(this);

            //     cancelUserInvitation($btn);
            // })

            // send me instructions
            .on('click', '.device_instruction:not(.disabled)', function () {
                var $btn = $(this);

                if ($(this).data('id')) {
                    installDevice($btn, $(this).data('id'));
                } else {
                    installDevice($btn, false);
                }
            })

            // install on this device
            .on('click', '.device_install:not(.disabled)', function () {
                var $btn = $(this);

                installDevice($btn);
            })

            // toggle panel settings
            .on('click', '.toggle_settings, .panel_info', function (e) {
                // proceed if hyperlink
                if ($(e.target).is('a')) return;

                var current_panel = $(this).closest('.panel');

                var $toggle_btn = $(this);
                if (! $(this).hasClass('toggle_settings')) {
                    $toggle_btn = current_panel.find('.toggle_settings');
                }

                if (current_panel.hasClass('flipped')) {
                    // current panel is flipped - just revert back
                    current_panel.removeClass('flipped');
                    $toggle_btn.children('i').removeClass('i-close').addClass('i-menu');
                } else {
                    // current panel is not flipped - revert all panels
                    $(this).closest('ul').find('li.panel').removeClass('flipped');
                    current_panel.addClass('flipped');
                    $('.toggle_settings i').removeClass('i-close').addClass('i-menu');
                    $toggle_btn.children('i').addClass('i-close').removeClass('i-menu');
                }
            })
        ;

        $('.userdevices_container')
            .on('click', '.action', function () {
                var action = $(this).data('action');
                var $panel = $(this).parents('.panel');

                var type = $panel.data('type');
                var $info = $panel.find('.input_info_holder');

                $('.userdevices_container').fadeOut(300, function () {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();

                    if (type == 'user') {
                        $('#action_user_id').val($info.data('user_id'));
                        $('.action_user_name').html($info.data('user_name'));
                        $('.action_user_email').html($info.data('user_email'));
                        $('.action_user_devicecnt').html($info.data('user_devicecnt') + ($info.data('user_devicecnt') == 1 ? ' device' : ' devices'));
                        $('.action_user_unused_devicecnt').html($info.data('user_devicecnt') + ($info.data('user_devicecnt') == 1 ? ' unused device' : ' unused devices'));
                    } else if (type == 'device') {
                        $('#action_device_id').val($info.data('device_id'));
                        $('.action_device_name').html($info.data('device_name'));
                        $('.action_device_username').html($info.data('device_user'));
                    }

                    //-- device actions
                    if (action == 'remove_device') {
                        // load remove device panel & summary

                        if ($info.data('is_own_device') == '1') {
                            $('.other_user').hide();
                        } else {
                            $('.other_user').show();
                        }

                        $('#panel_remove_device').fadeIn();
                        $('#panel_summary').fadeIn();

                        loadUserDeviceSummary('remove', 'device');
                    } else if (action == 'reinstall_defender') {
                        $('#panel_reinstall_defender').fadeIn();
                    } else if (action == 'rename_device') {
                        $('#panel_rename_device').fadeIn();

                    //-- user actions
                    } else if (action == 'view_user_devices') {
                        // view user devices

                        // show spinner
                        $('#spinner_container').show();

                        $('.userdevice-elements').hide();
                        $('.userview-elements').fadeIn();
                        $('.dialog_panels').hide();
                        $('.userdevices_container').show();

                        loadDeviceList($('#action_user_id').val());
                    } else if (action == 'remove_slots') {
                        // load remove slots panel

                        $('#panel_remove_slots').fadeIn();
                        $('#panel_summary').fadeIn();

                        var slot_options = '';
                        for (var i = 1; i <= $info.data('user_devicecnt'); i ++) {
                            slot_options += '<option value="' + i + '">' + i + '</option>';
                        }

                        $('#remove_slot_cnt').html(slot_options);

                        $('#remove_slot_cnt_indicator').html($('#remove_slot_cnt').val() + ($('#remove_slot_cnt').val() == 1 ? ' Device' : ' Devices'));

                        loadUserDeviceSummary('remove_slots', $('#remove_slot_cnt').val());
                    } else if (action == 'remove_user') {
                        // load remove user panel & summary

                        $('#panel_remove_user').fadeIn();
                        $('#panel_summary').fadeIn();

                        loadUserDeviceSummary('remove', 'user');
                    } else if (action == 'cancel_invite') {
                        // cancel invitation

                        $('#panel_cancel_invite').fadeIn();
                        // $('#panel_summary').fadeIn();

                        loadUserDeviceSummary('remove', 'user');
                    } else {
                        $('.dialog_panels').hide();
                        $('.userdevices_container').fadeIn();
                    }
                });
            })
        ;

        $('.dialog_panels')
            .on('click', '.cancel', function () {
                $('.dialog_panels .panel').fadeOut(300, function () {
                    $('.dialog_panels').hide();
                    $('.userdevices_container').fadeIn();
                });
            })

            // load summary for remove slots count
            .on('change', '#remove_slot_cnt', function () {
                $('#remove_slot_cnt_indicator').html($('#remove_slot_cnt').val() + ($('#remove_slot_cnt').val() == 1 ? ' Device' : ' Devices'));

                loadUserDeviceSummary('remove_slots', $(this).val());
            })

            //-- device actions

            .on('click', '#btn_confirm_rename_device', function () {
                // confirm rename device

                var $btn = $(this);

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/user-device/manageDevice',
                    dataType    : 'json',
                    data        : {
                        type        : 'rename',
                        device_id   : $('#action_device_id').val(),
                        device_name : $('#new_device_name').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        $('#spinner_container').show();

                        $('.dialog_panels').hide();
                        $('.dialog_panels .panel').hide();
                        $('.userdevices_container').fadeIn();

                        showSuccessText(response.message);

                        eval(userdevice_func);
                    } else {
                        showErrorText(response.message);
                    }

                    $btn.html('Rename Device').removeClass('disabled');
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            .on('click', '#btn_confirm_reinstall_defender', function () {
                // confirm reinstall defender

                var $btn = $(this);

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/user-device/manageDevice',
                    dataType    : 'json',
                    data        : {
                        type        : 'reinstall',
                        device_id   : $('#action_device_id').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        window.location.href = '/dnld/' + response.GUID;
                    } else {
                        $btn.html('Reinstall Defender').removeClass('disabled');
                        showErrorText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            .on('click', '#btn_confirm_remove_device', function () {
                // confirm remove device

                var $btn = $(this);

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/user-device/manageDevice',
                    dataType    : 'json',
                    data        : {
                        type        : 'remove',
                        device_id   : $('#action_device_id').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        window.location.href = '/user-device/summary';
                    } else {
                        showErrorText(response.message);
                    }

                    $btn.html('Remove Device').removeClass('disabled');
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            .on('click', '#btn_confirm_remove_slots', function () {
                // confirm remove slots

                var $btn = $(this);

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/user-device/manageUser',
                    dataType    : 'json',
                    data        : {
                        type        : 'remove_slots',
                        user_id     : $('#action_user_id').val(),
                        slot_cnt    : $('#remove_slot_cnt').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        window.location.href = '/user-device/summary';
                    } else {
                        showErrorText(response.message);
                    }

                    $btn.html('Remove Device').removeClass('disabled');
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            //-- user actions
            .on('click', '#btn_confirm_remove_user, #btn_confirm_cancel_invite', function () {
                // confirm remove user

                var $btn = $(this);

                $btn.html('Processing...').addClass('disabled');
                $.ajax({
                    type        : 'POST',
                    url         : '/user-device/manageUser',
                    dataType    : 'json',
                    data        : {
                        type        : 'remove',
                        user_id     : $('#action_user_id').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        window.location.href = '/user-device/summary';
                    } else {
                        showErrorText(response.message);
                    }

                    $btn.html('Remove Device').removeClass('disabled');
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        $('#btn_pass_ftu').on('click', function () {
            $.ajax({
                type        : 'POST',
                url         : '/user-device/ftu',
                dataType    : 'json',
                data        : {
                    type    : 'pass',
                },
            })
            .done(function (response) {
                if (response.status != 'success') {
                    showErrorText(response.error);
                    return;
                }

                $('.ftu_notification').slideUp();
            })
            .fail(function (data) {
                showErrorText(data.message);
            });
        });
    }

    // shield page
    if ($('.shield-container').length) {
        // network settings - open edit
        $('.network-settings')
            .on('click', '.edit', function () {
                // remove all current expanded fields

                resetOpenFields($('.network-settings'));

                // convert static container to edit

                var $container = $('.network-settings .row.expanded');

                $container.find('.edit-field').removeClass('hide');

                $('.secure_wireless_name input').val($('.secure_wireless_name .static-field').html().trim());
                $('.secure_wireless_password input').val($('.secure_wireless_password input').data('original'));

                $container.find('.static-field').addClass('hide');

                $container.find('.edit').removeClass('edit').addClass('cancel').html('Cancel');
            })
            .on('click', '.cancel', function () {
                resetOpenFields($('.network-settings'));
            })
            .on('click', '.edit-field button', function (e) {
                e.preventDefault();

                // update field
                var $input = $(this).siblings('input');
                var $info = $(this).siblings('.field-info');

                if ($input.hasClass('is-invalid-input')) {
                    // validation failed
                    $info.html('Please input correct value').fadeIn();
                    return;
                }

                // show the loading message
                $info.html($info.data('info')).fadeIn();

                $.ajax({
                    type        : 'POST',
                    url         : '/shield/manage',
                    dataType    : 'json',
                    data        : {
                        type        : 'network',
                        name        : $('.secure_wireless_name input').val(),
                        password    : $('.secure_wireless_password input').val(),
                    }
                })
                .done(function (response) {
                    $info.html(response.message);

                    setTimeout(function () {
                        $info.html('').fadeOut();

                        if (response.status == 'success') {
                            $('.secure_wireless_name .static-field').html(response.name);
                            $('.secure_wireless_name input').data('original', response.password);
                            $('.secure_wireless_password input').data('original', response.password);
                            $('.secure_wireless_password .static-field label').data('original', response.password);

                            if ($('.password_container').hasClass('password_hidden')) {
                                $('.secure_wireless_password .static-field label').html('********');
                            } else {
                                $('.secure_wireless_password .static-field label').html(response.password);
                            }

                            resetOpenFields($('.network-settings'));
                        }
                    }, 1000);
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        $('.toggle_password').on('click', function () {
            var static_password = $(this).siblings('label');

            if ($('.password_container').hasClass('password_hidden')) {
                // password is hidden - show password
                static_password.html(static_password.data('original'));
                $('.password_container').removeClass('password_hidden').addClass('password_shown');
                $(this).find('i').removeClass('i-invisible').addClass('i-visible');

                $('.secure_wireless_password input').attr('type', 'text');
            } else {
                // password is shown - hide password
                static_password.html('********');
                $('.password_container').removeClass('password_shown').addClass('password_hidden');
                $(this).find('i').removeClass('i-visible').addClass('i-invisible');

                $('.secure_wireless_password input').attr('type', 'password');
            }
        });

    }

    // setting page
    if ($('.setting-container').length) {
        $('.site-settings')
            .on('click', '.edit', function () {
                // remove all current expanded fields

                resetOpenFields($('.site-settings'));

                // convert static container to edit

                var $container = $(this).closest('.row.expanded');

                $container.find('.edit-field').removeClass('hide');

                $container.find('input').each(function () {
                    $(this).val($(this).data('original'));
                });

                $container.find('.static-field').addClass('hide');

                $container.find('.edit').removeClass('edit').addClass('cancel').html('Cancel');
                $container.find('.save').show();
            })
            .on('click', '.cancel', function () {
                resetOpenFields($('.site-settings'));
            })
            .on('click', '.save', function (e) {
                e.preventDefault();

                var $container = $(this).closest('.row.expanded').find('.edit-field');

                // update field
                var $input = $container.find('input').length ? $container.find('input') : $container.find('select');
                var $info = $container.find('.field-info');

                if (! $input.is(':valid')) {
                    // validation failed
                    $info.html('Please input correct value').fadeIn();
                    return;
                }

                // show the loading message
                $info.html($info.data('info')).fadeIn();

                $.ajax({
                    type        : 'POST',
                    url         : '/setting/manage',
                    dataType    : 'json',
                    data        : {
                        key     : $input.attr('name'),
                        value   : $input.val(),
                    },
                })
                .done(function (response) {
                    $info.html(response.message);

                    setTimeout(function () {
                        $info.html('').fadeOut();

                        if (response.status == 'success') {
                            $container.siblings('.static-field').html(response.value);
                            $input.data('original', response.value).val(response.value);

                            resetOpenFields($('.site-settings'));
                        }
                    }, 1000);
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        $('.toggle_password').on('click', function () {
            var static_password = $(this).siblings('label');

            if ($('.password_container').hasClass('password_hidden')) {
                // password is hidden - show password
                static_password.html(static_password.data('original'));
                $('.password_container').removeClass('password_hidden').addClass('password_shown');
                $(this).find('i').removeClass('i-invisible').addClass('i-visible');

                $('.secure_wireless_password input').attr('type', 'text');
            } else {
                // password is shown - hide password
                static_password.html('********');
                $('.password_container').removeClass('password_shown').addClass('password_hidden');
                $(this).find('i').removeClass('i-visible').addClass('i-invisible');

                $('.secure_wireless_password input').attr('type', 'password');
            }
        });

    }

    // added services page
    if ($('.service_container').length) {
        updateServiceSummary();

        $('.service_container')
            // resend verification email
            .on('click', '#btn_resend_verification', function () {
                var $btn = $(this);
                var $parent = $btn.parent();

                if ($btn.hasClass('change_parent')) {
                    $parent.html('Processing...');
                } else {
                    $btn.addClass('disabled').html('Processing...');
                }

                $.ajax({
                    type        : 'POST',
                    dataType    : 'json',
                    url         : '/common/verifyUserEmail',
                })
                .done(function (response) {
                    if (response.status == 'success') {

                        if ($btn.hasClass('change_parent')) {
                            $parent.html('Verification Email sent');
                        } else {
                            $btn.html('Email sent');
                        }

                        showSuccessText(response.message);
                    } else {
                        if ($btn.hasClass('change_parent')) {
                            $parent.html('<a id="btn_resend_verification" class="change_parent">Resend email</a>');
                        } else {
                            $btn.removeClass('disabled').html('Re-Send Verification Email');
                        }

                        showErrorText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            //--- device counter
            // minus
            .on('click', '.userdevice_counter .minus', function () {
                var $input = $(this).parent().children('input[type=text]');

                updateUserdeviceCounter($(this).parent(), 'dec');
                $input.change();
            })
            // plus
            .on('click', '.userdevice_counter .plus', function () {
                var $input = $(this).parent().children('input[type=text]');

                updateUserdeviceCounter($(this).parent(), 'inc');
                $input.change();
            })
            // manual input
            .on('change', '.userdevice_counter input[type=text]', function () {
                updateUserdeviceCounter($(this).parent());
                // updateServiceSummary();
            })

            .on('valid.zf.abide', '.userdevice_counter input[type=text]', function (ev, frm) {
                updateServiceSummary();
            })

            //--- devices page
            // validate new user emails
            .on('change', 'input[name="new_users[email][]"]', function () {
                if (! validateEmail($(this).val())) return false;

                var $input = $(this);

                $.ajax({
                    type        : 'POST',
                    url         : '/service/manageDevice',
                    dataType    : 'json',
                    data        : {
                        type    : 'validateEmail',
                        email   : $input.val(),
                    },
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        var $container = $input.parents('.userdevice_info');
                        if (response.result == 'available') {
                            $container.children('input').removeClass('invalid');
                            $container.siblings('.alert_info').hide();
                        } else if (response.result == 'deleted') {
                            $container.siblings('.alert_info').html('Note: This will re-add a user that you\'ve previously removed from the organization.').show();
                        } else {
                            $container.children('input').addClass('invalid');
                            $container.siblings('.alert_info').html('We were unable to invite this user. Please contact Todyl support for more details.').show();
                        }
                    } else {
                        showErrorText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })

            // resend invitation
            .on('click', '.resend_invite:not(.disabled)', function () {
                var $btn = $(this);

                resendUserInvitation($btn);
            })

            // cancel invitation
            .on('click', '.cancel_invite:not(.disabled)', function () {
                var $btn = $(this);

                cancelUserInvitation($btn);
            })

            // add user
            .on('click', '.add_user', function () {
                if (! $('.new_users .user_row').length) {
                    // creating a first new user - change link text
                    $(this).parent().html($(this).html('Add Another User'));

                    $('.new_users').html(
                        "<div class=\"row expanded\">\
                            <p class=\"sub_label small-12 columns\">Add New Users</p>\
                        </div>"
                    );
                }

                var new_user_row = "<div class=\"row expanded user_row\">\
                    <div class=\"medium-8 columns\">\
                        <div class=\"userdevice_info\">\
                            <input type=\"text\" name=\"new_users[email][]\" placeholder=\"Enter Their Email Address\" pattern=\"email\" required />\
                        </div>\
                        <div class=\"alert_info\"></div>\
                    </div>\
                    <div class=\"medium-4 columns\">\
                        <div class=\"userdevice_counter\">\
                            <a class=\"minus hidden\">-</a>\
                            <input type=\"text\" name=\"new_users[device][]\" value=\"1\" onkeypress=\"return numbersonly(event)\" oninput=\"checkInputLength(2, this)\" required />\
                            <a class=\"plus\">+</a>\
                        </div>\
                        <a class=\"remove_user\"><i class=\"i-close\"></i></a>\
                    </div>\
                </div>";

                $('.new_users').append($(new_user_row));

                updateServiceSummary();
            })

            // remove new user entry
            .on('click', '.remove_user', function () {
                $(this).closest('.user_row').remove();

                if (! $('.new_users .user_row').length) {
                    // all users have been removed, revert the add user text
                    $('.new_users').empty();
                    $('.new_users').next().html('<p class="sub_label small-12 columns">or <a class="add_user" href="javascript:void(0);">Add New Users</a></p>');
                }

                updateServiceSummary();
            })

            // confirm order
            .on('click', '#confirm_order', function () {
                if (! $('#frm_service input.invalid').length) {
                    $('#frm_service').foundation('validateForm');
                }
            })
        ;
    }

    // support page
    if ($('.support-container').length) {
        $('#btn_add_topic').on('click', function () {
            $('#frm_edit_topic')[0].reset();
            $('#frm_edit_topic').foundation('resetForm');

            $('#dlg_edit_topic h4').html('Create New Guide or FAQ Topic');
            $('#frm_edit_topic input[name=action]').val('add_topic');
            $('#frm_edit_topic input[name=icon]').attr('required', true);
            $('#btn_confirm_topic').html('Add');

            $('#dlg_edit_topic').foundation('open');
        });

        $('.btn_edit_topic').on('click', function () {
            $('#frm_edit_topic')[0].reset();
            $('#frm_edit_topic').foundation('resetForm');

            $('#dlg_edit_topic h4').html('Edit Topic - ' + $(this).data('topic-name'));
            $('#frm_edit_topic input[name=action]').val('edit_topic');
            $('#frm_edit_topic input[name=id]').val($(this).data('topic-id'));
            $('#frm_edit_topic input[name=name]').val($(this).data('topic-name'));
            $('#frm_edit_topic input[name=icon]').attr('required', false);
            $('#btn_confirm_topic').html('Edit');

            $('#dlg_edit_topic').foundation('open');
        });

        $('#btn_confirm_topic').on('click', function () {
            $('#frm_edit_topic').foundation('validateForm');
        });

        $('#frm_edit_topic').on('formvalid.zf.abide', function (ev, frm) {
            // add topic
            $.ajax({
                type        : 'POST',
                url         : '/support/manage',
                dataType    : 'json',
                data        : new FormData(this),
                processData : false,
                contentType : false,
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
        });

        // topic management
        $('.btn_hide_topic').on('click', function () {
            $('#dlg_manage_topic #manage_topic_action').val('hide_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Hiding of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to hide <b>' + $(this).data('topic-name') + '</b>?');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Hide This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('.btn_show_topic').on('click', function () {
            $('#dlg_manage_topic #manage_topic_action').val('show_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Unhiding of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to show <b>' + $(this).data('topic-name') + '</b>?');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Show This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('.btn_delete_topic').on('click', function () {
            $('#dlg_manage_topic #manage_topic_action').val('delete_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Deletion of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to delete <b>' + $(this).data('topic-name') + '</b>? You will not be able to bring this content back.');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Delete This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('#btn_manage_topic').on('click', function () {
            manageSupport($('#manage_topic_action').val(), $('#manage_topic_id').val());
        });

        // article management

        $('.btn_hide_article').on('click', function () {
            $('#dlg_manage_article #manage_article_action').val('hide_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Hiding of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to hide <b>' + $(this).data('article-name') + '</b>?');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Hide This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('.btn_show_article').on('click', function () {
            $('#dlg_manage_article #manage_article_action').val('show_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Unhiding of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to show <b>' + $(this).data('article-name') + '</b>?');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Show This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('.btn_delete_article').on('click', function () {
            $('#dlg_manage_article #manage_article_action').val('delete_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Deletion of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to delete <b>' + $(this).data('article-name') + '</b>? You will not be able to bring this content back.');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Delete This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('#btn_manage_article').on('click', function () {
            manageSupport($('#manage_article_action').val(), $('#manage_article_id').val());
        });

    }

    // activity page
    if ($('.activity-container').length) {
        loadActivityStats();
        loadActivityList();

        $('.ftu_notification')
            .on('click', '#btn_pass_ftu', function () {
                $.ajax({
                    type        : 'POST',
                    url         : '/activity/manage',
                    dataType    : 'json',
                    data        : {
                        type    : 'pass_ftu',
                    },
                })
                .done(function (response) {
                    if (response.status != 'success') {
                        showErrorText(response.error);
                        return;
                    }

                    $('.ftu_notification').slideUp();
                })
                .fail(function (data) {
                    showErrorText(data.message);
                });
            })
        ;

        $('.activity-container')
            .on('change', '#filter_activity_period', function () {
                loadActivityList();
            })
        ;
    }

    // review alerts page
    if ($('.review-container').length) {
        if ($('#incident_list').length) {
            loadIncidentList();
        }

        $('.review-container')
            .on('change', '#show_resolved', function () {
                loadIncidentList($(this).is(':checked'));
            })

            .on('change', '#classification', function () {
                if ($(this).val() == '2') {
                    // show instructions only for <True positive>
                    $('#incident_instructions').slideDown();
                } else {
                    $('#incident_instructions').slideUp();
                }
            })

            .on('click', '#btn_save_incident', function () {
                if (! validateForm($('#frm_assign_incident'))) return false;

                var $btn = $('#btn_save_incident');

                $btn.html('Processing...').prop('disabled', true);
                $.ajax({
                    type        : 'POST',
                    url         : '/review/manage',
                    dataType    : 'json',
                    data        : $('#frm_assign_incident').serialize() + '&type=saveIncident',
                })
                .done(function (data) {
                    if (data.status === 'success') {
                        window.location.href = '/review';
                    } else {
                        console.log(data);
                        $btn.html('Save').prop('disabled', false);
                    }
                })
                .fail(function (response) {
                    console.log(response);
                    showErrorText('An error occurred while updating the incident instructions');
                    $btn.html('Save').prop('disabled', false);
                });
            })

            .on('click', '#btn_save_comment', function () {
                if (! validateForm($('#frm_comment_incident'))) return false;

                var $btn = $('#btn_save_comment');

                $btn.html('Processing...').prop('disabled', true);
                $.ajax({
                    type        : 'POST',
                    url         : '/review/manage',
                    dataType    : 'json',
                    data        : $('#frm_comment_incident').serialize() + '&type=saveComment',
                })
                .done(function (data) {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        console.log(data);
                        $btn.html('Send').prop('disabled', false);
                    }
                })
                .fail(function (response) {
                    console.log(response);
                    showErrorText('An error occurred while commenting on the incident');
                    $btn.html('Send').prop('disabled', false);
                });
            })

            .on('click', '#incident_list tr', function () {
                window.location.href = $(this).data('link');
            })
        ;
    }

    // magic download page
    if ($('.magicdownload_container').length) {
        // if os url is not set, redirect to proper page
        checkOS();

        $('#copy_pin').parent().on('click', copyPin);

        checkDeviceInstall($('#pin_no').html());
    }

});


/*==========Adwords Conversion Tracking==========*/

//Phone Leads
function gtag_report_phoneLead(url) {
  var callback = function () {
    if (typeof(url) != 'undefined') {
      window.location = url;
    }
  };
  gtag('event', 'conversion', {
      'send_to': 'AW-925480275/sQKgCJj2ln4Q0-qmuQM',
      'event_callback': callback
  });
  return false;
}

function gtag_report_signUpLead(url) {
  var callback = function () {
    if (typeof(url) != 'undefined') {
      window.location = url;
    }
  };
  gtag('event', 'conversion', {
      'send_to': 'AW-925480275/v747CP3yln4Q0-qmuQM',
      'event_callback': callback
  });
  return false;
}

//Complete Sign Up
if ($('.thankyou').length) {
  gtag('event', 'conversion', {
      'send_to': 'AW-925480275/wNAXCPrykn4Q0-qmuQM',
      'transaction_id': ''
  });
}
