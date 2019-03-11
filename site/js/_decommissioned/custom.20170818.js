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

/* Settings page javascript */
$('.settings-page-form').submit(function () {
    // Disable submit button once form is submitted to prevent double-submit
    $(this).find('input[type=submit]').prop('disabled', true);
});

function contactFormSetup () {
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

$(document).ready(function () {
    // Foundation custom options
    Foundation.Orbit.defaults.timerDelay = 8000;
    Foundation.Abide.defaults.validators['positive_integer'] = function($el, required, parent) {
        if (!required) return true;

        return (parseInt($el.val()) > 0);
    };

    $(document).foundation();

    handleInputMaterialIE();

    adjustFooter();

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
    $('.group.error input,select').on('keyup keypress blur change', function(e) {
        // e.type is the type of event fired
        $(this).siblings('.error_text').fadeOut();
        $(this).parent().removeClass('error');
    });

    // password security check
    $('.group input#todyl_password:not(.no_hint)')
        .on('keydown keyup keypress change input', function() {
            var $self = $(this);

            if (! $self.siblings('.hint').length) {
                $('<div class="hint">Must Contain : <span id="pw_val_length">At least 7 Characters</span><span id="pw_val_number">A Number</span><span id="pw_val_symbol">A Symbol (?, !, *, &)</span></div>').appendTo($self.parent()).fadeIn();
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
        .on('blur focusout', function() {
            if (! $(this).parent().find('.hint span:not(.match)').length) {
                $(this).parent().find('.hint').fadeOut();
            }
        });

    if ($('.registration-container').length) {
        // Sign up

        // trim all text inputs on change
        $('.signup-form').on('blur change', 'input[type=text]', function() {
            if ($.trim($(this).val()) != $(this).val()) {
                // value has white spaces - remove and trigger change again to revalidate
                $(this).val($.trim($(this).val()));
                $(this).trigger('change');
            }
        });

        $('#todyl_phone_no').on('keyup', function() {
            var telno = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(formatPhoneNo(telno));
        });

        // package select buttons
        $(document).on('click', '.package_select_btn:not(.selected)', function() {
            $('.package_select_btn.selected').html('Select').removeClass('selected').siblings('input[type=radio][name=package]').prop('checked', false);
            $(this).html('Selected!').addClass('selected').siblings('input[type=radio][name=package]').prop('checked', true);
        });

        if ($('.stage_1').length) {
            // step 1 - account page

            if (Foundation.MediaQuery.current == 'small') {
                // change number to #
                $('#num_devices').siblings('label.reg').html('# of desktops or laptops to protect');
            }
        }

        if ($('.stage_2').length) {
            // step 2 - packages page

            // load drift
            drift.load('han4vxm4ebps');

            updatePackageOptions();

            $('input[name=prepay]').on('change', function() {
                updatePackageOptions();
            });
        }

        if ($('.stage_3').length) {
            // step 3 - review page

            updateSummary();

            $('#terms-yes').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).closest('.highlight').removeClass('highlight').addClass('unhighlight');
                    $('.signup-form input:submit').attr('disabled', false);
                } else {
                    $(this).closest('.unhighlight').addClass('highlight').removeClass('unhighlight');
                    $('.signup-form input:submit').attr('disabled', true);
                }
            });
            $('#terms-yes').trigger('change');

            $('#promo_code').on('keyup change blur input', function() {
                if ($(this).val()) {
                    $('#apply_promo').removeClass('btn-link').prop('disabled', false);
                } else {
                    $('#apply_promo').addClass('btn-link').prop('disabled', true);
                }
            })

            $('#apply_promo').on('click', function(e) {
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

            $('#remove_promo').on('click', function(e) {
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

                    updateSummary();
                })
                .fail(function (response) {
                    $('#promo_code').prop('readonly', true);
                    $btn.html('Remove').prop('disabled', false);
                });
            });

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
            card.addEventListener('change', function(event) {
                var $displayError = $('#card-errors');

                if (event.error) {
                    $displayError.html(event.error.message).fadeIn();
                } else {
                    $displayError.html('').fadeOut();
                }
            });

            $('#change_card').on('click', function() {
                $(this).closest('.step_label').slideUp();
                $('#card-element').closest('.step_label.hide').removeClass('hide');
            });
        }

        // sign up form submit handler - perform per-step actions here
        $('.signup-form').submit(function() {
            if (! validateForm($(this))) return false;

            var current_step = $('#current_step').val();

            switch (current_step) {
                case '1':
                    // step 1 - Organization

                    if ($('#signup-recaptcha').data('valid') !== 'yes') {
                        // grecaptcha.reset(todyl.captcha3);
                        // return false;
                        grecaptcha.execute(todyl.captcha3);
                        return false;
                    }

                    // prevent form submit if hint is not all met
                    if ($(this).find('.hint span:not(.match)').length) {
                        $(this).find('.hint').fadeTo(100, 0.1).fadeTo(200, 1.0);

                        return false;
                    }

                    return true;
                case '2':
                    // step 2 - Packages

                    // check if a package plan has been selected
                    if (! $('input[name=package]:checked').length) {
                        showErrorText('Please choose a package to continue.');
                        return false;
                    }

                    return true;

                case '3':
                    // step 3 - Review

                    // check if terms checkbox is checked
                    if (! $('#terms-yes').is(':checked')) {
                        return false;
                    }

                    var $form = $(this);
                    var $submitBtn = $form.find('input:submit');

                    if ($('#card-element').is(':visible')) {
                        $submitBtn.val('Processing...').prop('disabled', true);

                        stripe.createToken(card, { name: $('#name_on_card').val() }).then(function(result) {
                            fbq('track', 'AddPaymentInfo');

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

                    // tracking
                    // fbq('track', 'CompleteRegistration');
                    // fbq('track', 'Purchase');

                    return false;

                default:
                    break;
            }

            // if no interruptions, submit the form
            return true;
        });

        if ($('.splash').length) {
            // this is a splash screen - redirect after 5 seconds
            setTimeout(function() {
                window.location.href = $('.splash').attr('next');
            }, $('.splash').attr('delay'));
        }
    }

    if ($('.signin-form').length) {
        $('.signin-form input').on('keyup change blur input', function() {
            var is_valid = true;

            $('.signin-form input[type=text],input[type=password]').each(function() {
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

    $(document).on('invalid.zf.abide', function(ev, elem) {
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

        $('#primary_use').on('change', function() {
            handlePrimaryType();
        });

        // trim all text inputs on change
        $('.beta-signup-form').on('blur change', 'input[type=text]', function() {
            if ($.trim($(this).val()) != $(this).val()) {
                // value has white spaces - remove and trigger change again to revalidate
                $(this).val($.trim($(this).val()));
                $(this).trigger('change');
            }
        });

        $('#todyl_phone_no').on('keyup', function() {
            var telno = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(formatPhoneNo(telno));
        });

        $('#promo_code').on('keyup change blur input', function() {
            if ($(this).val()) {
                $('#apply_promo').removeClass('btn-link').prop('disabled', false);
            } else {
                $('#apply_promo').addClass('btn-link').prop('disabled', true);
            }
        });

        $('#apply_promo').on('click', function(e) {
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
                if (data.status === 'success' && $('#promo_code').val().substr(0, 4).toLowerCase() === 'beta') {
                    $btn.hide();

                    $('#promo_code').prop('readonly', true);
                    $('#promo_description').html('Verified').removeClass('fail').addClass('success').fadeIn();
                } else {
                    $btn.html('Apply').prop('disabled', false);
                    $('#promo_description').html('Sorry, we cannot find that Beta code. Please try again.').removeClass('success').addClass('fail').fadeIn();
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

        if ($('#promo_code').val()) {
            // beta promo code present
            $('#apply_promo').trigger('click');
        }

        $('.beta-signup-form').submit(function() {
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
        $('.pwreset-form').submit(function() {
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
        $('.v-center').addClass('margin-t-20 margin-b-20');
        $('.v-center .pad-t-1').removeClass('pad-t-1');
    }

    // toggle password show/hide
    $('.toggle_pwd').on('click', function() {
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
    $('.billing_options .tc_option input[type=radio]').on('change', function() {
        $(this).parents('.tc_option:first').addClass('active').siblings('.tc_option').removeClass('active');

        // if ($(this).val() == 'annual') {
        //     $('input[name=get_early_access]').prop('checked', true).prop('disabled', true);
        // } else if ($(this).val() == 'monthly') {
        //     $('input[name=get_early_access]').prop('checked', false).prop('disabled', false);
        // }
    });

    $(window).scroll(function(){
        if ($(this).scrollTop() > 200) {
            $('#homepage-nav').fadeIn(500);
        } else {
            $('#homepage-nav').fadeOut(500);
        }
    });

    //countup for homepage stats, this one is kind of junky, i'd prefer something smoother
    $('.counter').each(function() {


      var $this = $(this),
          countTo = $this.attr('data-count');
      
      $({ countNum: $this.text()}).animate({
        countNum: countTo
      },

      {

        duration: 3000,
        easing:'swing',
        step: function() {
          $this.text(Math.floor(this.countNum));
        },
        complete: function() {
          $this.text(this.countNum);
          //alert('finished');
        }

      });  
    });


    // homepage
    if ($('#homepage-services').length) {
        if (Foundation.MediaQuery.atLeast('large')) {

            scaleHomepageIntro();

            $(window).on('resize', function () {
                scaleHomepageIntro();
            });
        } else {

            $('.product_img').each(function() {
                $(this).css('background-image', 'url(' + $(this).children('img').attr('src') + ')');
                $(this).children('img').hide();
            });

            scaleHomepageIntro();

            $(window).scroll(function() {
                showServiceDescription($('#service_defender'));
                showServiceDescription($('#service_shield'));
            });

            $('.mobile-nav-menu').on('click', 'a', function() {
                // auto-close nav menu when clicking menu item
                $(".off-canvas .close-button").click();
            });
        }

    }

    // download page -> would like to add some error handling if copy fails.
    if ($('.download_container').length && $('.download_container').is(':visible')) {
        // if os url is not set, redirect to proper page
        detectOS();

        $('#copy_pin').on('click', function() {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val($('#pin_no').html()).select();
            document.execCommand('copy');
                document.getElementById('copy-confirm').classList.add('copied');
                    var temp2 = setInterval( function(){
                    document.getElementById('copy-confirm').classList.remove( 'copied' );
                    clearInterval(temp2);
                    }, 1000 );
            $temp.remove();
        });

        (function checkDevice() {
            // check if device has been registered
            $.ajax({
                type        : 'POST',
                url         : '/dashboard/checkDevice',
                dataType    : 'json',
                data        : {
                    pin     : $('#pin_no').html(),
                },
            })
            .done(function (response) {
                if (response.status == 'success') {
                    // device is registered - redirect to dashboard
                    window.location.href = '/dashboard';
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

    //-- dashboard pages --//

    // dashboard page
    if ($('.dashboard-container').length) {
        // internet threat level management
        $('#btn_edit_threat_level').on('click', function() {
            $('#dlg_manage_threat_level').foundation('open');
        });

        $('#dlg_manage_threat_level')
            .on('change', 'input[name=threat_level]', function() {
                $('textarea[name=threat_level_description]').val(threat_level_descriptions[$(this).val()]);
            })
            .on('click', '#btn_confirm_topic', function() {
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
                        callback: function(label, index, labels) {
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
                custom: function(tooltip) {
                    if (! tooltip) return;
                    // disable displaying the color box
                    tooltip.displayColors = false;
                },
                callbacks: {
                    label: function(tooltipItem, data) {
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
        setInterval(function() {
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
            .on('keyup', '.phone_no', function() {
                var telno = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(formatPhoneNo(telno));
            })
            .on('click', '.edit', function() {
                // remove all current expanded fields

                resetAccountFields($('.account-settings'));

                // reset password fields
                $('.account-settings .pw_field').removeClass('hide');
                $('#frm_password').addClass('hide');

                // convert static container to edit

                var $container = $('.account-settings .row.expanded:not(.pw_field)');

                $container.find('.edit-field').each(function() {
                    $(this).removeClass('hide').find('input').val($(this).siblings('.static-field').html().trim());
                });
                $container.find('.static-field').addClass('hide');

                $(this).addClass('hide');
                $('.account-settings').find('.edit_action').removeClass('hide');
            })
            // change password
            .on('click', '.change_pw', function() {
                resetAccountFields($('.account-settings'));

                $('.account-settings .pw_field').addClass('hide');
                $('#frm_password').removeClass('hide');
            })
            .on('click', '.cancel', function() {
                resetAccountFields($('.account-settings'));

                // reset password fields
                $('.account-settings .pw_field').removeClass('hide');
                $('#frm_password').addClass('hide');
            })
            .on('click', '.save', function(e) {
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
                            $form.find('.static-field').each(function() {
                                $(this).html($(this).siblings('.edit-field').children('input').val());
                            });
                        }

                        resetAccountFields($('.account-settings'));

                        // reset password fields
                        $('.account-settings .pw_field').removeClass('hide');
                        $('#frm_password').addClass('hide');

                        showSuccessText(response.message);
                    }
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        // billing settings
        if ($('.billing-settings').length) {

            $('.billing-settings')
                .on('click', '.edit', function() {
                    $('#frm_billing').slideDown();
                })
                .on('click', '.cancel', function() {
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
            card.addEventListener('change', function(event) {
                var $displayError = $('#card-errors');

                if (event.error) {
                    $displayError.html(event.error.message).fadeIn();
                } else {
                    $displayError.html('').fadeOut();
                }
            });

            $('#frm_billing').on('submit', function() {
                if (! validateForm($(this)) || $('#card-errors').is(':visible')) return false;

                var $form = $(this);
                var $submitBtn = $form.find(':submit');

                $submitBtn.val('Processing...').prop('disabled', true);

                stripe.createToken(card, { name: $('#name_on_card').val() }).then(function(result) {
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
                                showSuccessText('Your payment information was updated successfully');

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
            .on('click', '.edit_account_status', function() {
                $('.account-container').fadeOut(300, function() {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_suspend_1').fadeIn();
                });
            })
            .on('click', '.edit_email_settings', function() {
                $('.account-container').fadeOut(300, function() {
                    $('html, body').animate({ scrollTop: 0 }, 300);

                    $('.dialog_panels').show();
                    $('#panel_email_settings').fadeIn();
                });
            })
        ;

        $('.dialog_panels')
            .on('click', '#btn_suspend_1', function() {
                $('#panel_suspend_1').fadeOut(300, function() {
                    $('#panel_suspend_2').fadeIn();
                });
            })
            .on('click', '#btn_suspend_2', function() {
                // confirm suspension

                if (! $('#suspension_current_pw').val().trim()) {
                    return false;
                }

                $.ajax({
                    type        : 'POST',
                    url         : '/account/manage',
                    dataType    : 'json',
                    data        : {
                        type        : 'suspend',
                        current_pw  : $('#suspension_current_pw').val(),
                    }
                })
                .done(function (response) {
                    if (response.status == 'success') {
                        $('#panel_suspend_1').fadeOut(300, function() {
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
            .on('click', '#btn_update_email_settings', function() {
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

                        $('#panel_suspend_1').fadeOut(300, function() {
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
            .on('click', '.cancel', function() {
                $('#panel_suspend_1').fadeOut(300, function() {
                    $('.dialog_panels').hide();
                    $('.account-container').fadeIn();
                });
            })
        ;

    }

    // device page
    if ($('.devices-container').length) {
        $('.toggle_expand').on('click', function() {
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

        $('#btn_management').on('click', function() {
            // deactivate device

            $.ajax({
                type        : 'POST',
                url         : '/device/manage',
                dataType    : 'json',
                data        : {
                    action      : $(this).data('action'),
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

        $('#btn_renamedevice').on('click', function() {
            // rename device

            if (! $('#new_device_name').val().trim()) return;

            $.ajax({
                type        : 'POST',
                url         : '/device/manage',
                dataType    : 'json',
                data        : {
                    action      : 'rename',
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

    // shield page
    if ($('.shield-container').length) {
        // network settings - open edit
        $('.network-settings')
            .on('click', '.edit', function() {
                // remove all current expanded fields

                resetNetworkSettings();

                // convert static container to edit

                var $container = $('.network-settings .row.expanded');

                $container.find('.edit-field').removeClass('hide');

                $('.secure_wireless_name input').val($('.secure_wireless_name .static-field').html().trim());
                $('.secure_wireless_password input').val($('.secure_wireless_password input').data('original'));

                $container.find('.static-field').addClass('hide');

                $container.find('.edit').removeClass('edit').addClass('cancel').html('Cancel');
            })
            .on('click', '.cancel', function() {
                resetNetworkSettings();
            })
            .on('click', '.edit-field button', function(e) {
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

                    setTimeout(function() {
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

                            resetNetworkSettings();
                        }
                    }, 1000);
                })
                .fail(function (response) {
                    showErrorText(response.message ? response.message : response.statusText);
                });
            })
        ;

        $('.toggle_password').on('click', function() {
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

    // support page
    if ($('.support-container').length) {
        $('#btn_add_topic').on('click', function() {
            $('#frm_edit_topic')[0].reset();
            $('#frm_edit_topic').foundation('resetForm');

            $('#dlg_edit_topic h4').html('Create New Guide or FAQ Topic');
            $('#frm_edit_topic input[name=action]').val('add_topic');
            $('#frm_edit_topic input[name=icon]').attr('required', true);
            $('#btn_confirm_topic').html('Add');

            $('#dlg_edit_topic').foundation('open');
        });

        $('.btn_edit_topic').on('click', function() {
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

        $('#btn_confirm_topic').on('click', function() {
            $('#frm_edit_topic').foundation('validateForm');
        });

        $('#frm_edit_topic').on('formvalid.zf.abide', function(ev, frm) {
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

        $('.btn_hide_topic').on('click', function() {
            $('#dlg_manage_topic #manage_topic_action').val('hide_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Hiding of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to hide <b>' + $(this).data('topic-name') + '</b>?');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Hide This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('.btn_show_topic').on('click', function() {
            $('#dlg_manage_topic #manage_topic_action').val('show_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Unhiding of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to show <b>' + $(this).data('topic-name') + '</b>?');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Show This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('.btn_delete_topic').on('click', function() {
            $('#dlg_manage_topic #manage_topic_action').val('delete_topic');
            $('#dlg_manage_topic #manage_topic_id').val($(this).data('topic-id'));

            $('#dlg_manage_topic h4').html('Confirm Deletion of Topic');
            $('#dlg_manage_topic .desc').html('Are you sure you want to delete <b>' + $(this).data('topic-name') + '</b>? You will not be able to bring this content back.');
            $('#dlg_manage_topic #btn_manage_topic').html('Yes, Delete This Topic');

            $('#dlg_manage_topic').foundation('open');
        });

        $('#btn_manage_topic').on('click', function() {
            manageSupport($('#manage_topic_action').val(), $('#manage_topic_id').val());
        });

        // article management

        $('.btn_hide_article').on('click', function() {
            $('#dlg_manage_article #manage_article_action').val('hide_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Hiding of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to hide <b>' + $(this).data('article-name') + '</b>?');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Hide This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('.btn_show_article').on('click', function() {
            $('#dlg_manage_article #manage_article_action').val('show_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Unhiding of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to show <b>' + $(this).data('article-name') + '</b>?');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Show This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('.btn_delete_article').on('click', function() {
            $('#dlg_manage_article #manage_article_action').val('delete_article');
            $('#dlg_manage_article #manage_article_id').val($(this).data('article-id'));

            $('#dlg_manage_article h4').html('Confirm Deletion of article');
            $('#dlg_manage_article .desc').html('Are you sure you want to delete <b>' + $(this).data('article-name') + '</b>? You will not be able to bring this content back.');
            $('#dlg_manage_article #btn_manage_article').html('Yes, Delete This article');

            $('#dlg_manage_article').foundation('open');
        });

        $('#btn_manage_article').on('click', function() {
            manageSupport($('#manage_article_action').val(), $('#manage_article_id').val());
        });

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

    }

    // activity page
    if ($('.activity-container').length) {
        // spinner
        $('#spinner_container').show();

        $.ajax({
            type        : 'POST',
            url         : '/activity/manage',
            dataType    : 'json',
            data        : {
                type    : 'list',
            },
        })
        .done(function (response) {
            if (response.status != 'success') {
                console.log(response);
                showErrorText('Sorry, there was an error while loading your activity history.');

                return;
            }

            $('#activity_stats .columns:nth-child(1) .stat-value').html(response.hours_protected);
            $('#activity_stats .columns:nth-child(2) .stat-value').html(response.blocked_threats);
            $('#activity_stats .columns:nth-child(3) .stat-value').html(response.connected_text);

            $('#activity_list tbody').html(response.activity_list);

            easeInPanels();
        })
        .fail(function (data) {
            showErrorText(data.message);
        });

        $('#btn_pass_ftu').on('click', function() {
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
        });
    }

    // magic download page
    if ($('.magicdownload_container').length) {
        // if os url is not set, redirect to proper page
        detectOS();

        $('#copy_pin').on('click', function() {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val($('#pin_no').html()).select();
            document.execCommand('copy');
                document.getElementById('copy-confirm').classList.add('copied');
                    var temp2 = setInterval( function(){
                    document.getElementById('copy-confirm').classList.remove( 'copied' );
                    clearInterval(temp2);
                    }, 1000 );
            $temp.remove();
        });
    }

});

function handleInputMaterialIE() {
    // handle :placeholder-shown not working on IE

    if (window.navigator.userAgent.indexOf("Trident") !== -1 || window.navigator.userAgent.indexOf("Edge") !== -1) {
        // IE or Edge

        $('.inputMaterial').each(function() {
            if ($(this).val() || $(this).is('select')) {
                $(this).siblings('label.reg').addClass('filled');
            } else {
                $(this).siblings('label.reg').removeClass('filled');
            }
        });

        $(document).on('change input', '.inputMaterial', function() {
            if ($(this).val()) {
                $(this).siblings('label.reg').addClass('filled');
            } else {
                $(this).siblings('label.reg').removeClass('filled');
            }
        });
    }
}

function adjustFooter() {
    // Set session-main-container div to have min-height of 100% of .off-canvas-content (100vh)
    // minus header/footer height
    var mainHeight = $('.off-canvas-content').outerHeight(true) - $('footer').outerHeight(true) - 72;
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
            if ($('.top-bar').length) {
                mainHeight -= $('.top-bar').outerHeight(true);
            }

            $('.container').css('min-height', mainHeight + 'px');
        }
    }
}

function vcenterElement($element) {
    var $parent = $element.parent();

    if ($parent.outerHeight() == $element.outerHeight()) $parent = $parent.parent();

    var marginTop = ($parent.outerHeight() - $element.outerHeight()) / 2;

    $element.css({
        marginTop: marginTop + 'px',
    });

}

function scaleHomepageIntro() {
    $('#homepage-intro').css('height', ($(window).height() * 0.9) + 'px');
    return true;

    var imgWidth = $(window).width();
    var imgHeight = imgWidth / 1600 * 826; // scale intro height to show full image

    if (imgHeight > $(window).height()) {
        $('#homepage-intro').css('height', $(window).height() + 'px');
    } else {
        $('#homepage-intro').css('height', imgHeight + 'px');
    }

}

function initBannerVideoSize(element) {

    $(element).each(function () {
        $(this).data('height', $(this).height());
        $(this).data('width', $(this).width());
    });

    scaleBannerVideoSize(element);

}

function scaleBannerVideoSize(element) {

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

// homepage - detect and show service animation
function showServiceDescription($service_container) {
    if (! $service_container.find('.description').is(':visible')){
        // todyl defender animation
        if ($(window).scrollTop() + $(window).outerHeight() - $service_container.outerHeight() > $service_container.offset().top) {
            $service_container.find('.description').fadeIn();
        }
    }
}

// on registration step 1 - handle personal | business use
function handlePrimaryType() {
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
function formatPhoneNo(tel) {
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

// signup package step update package options based on prepaid(annual) option
function updatePackageOptions() {
    $.ajax({
        type        : 'POST',
        url         : '/pricing/getPricing',
        dataType    : 'json',
        data        : {
            prepay: $('input[name=prepay]').is(':checked'),
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

// signup review step update subtotal section
function updateSummary() {
    $.ajax({
        type        : 'POST',
        url         : '/pricing/getSummary',
        dataType    : 'json',
        encode      : true
    })
    .done(function (data) {
        var summaryHtml = '<p><span class="f_left">Subtotal</span><span class="f_right">' + data.subtotal + '</span></p>';
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

// show success text
function showSuccessText(message, callback) {
    var $messageContainer = $('#message-container');

    var successHtml = "<div class='small-12 columns text-center success'><p class='lead'>" + message + "</p></div>";
    $messageContainer.hide().html(successHtml).fadeIn('slow');

    setTimeout(function () {
        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out', function() {
            if (callback) {
                callback();
            }
        });
    }, 3000);
}

// show ajax error text
function showErrorText(message) {
    if (! message || message == 'undefined') {
        return;
    }

    var $messageContainer = $('#message-container');

    var errorHtml = "<div class='small-12 columns text-center error'><p class='lead'>" + message + "</p></div>";
    $messageContainer.hide().html(errorHtml).fadeIn('slow');

    setTimeout(function () {
        MotionUI.animateOut($('#message-container'), 'slide-out-up short-delay ease-out');
    }, 3000);
}

// dashboard update stats
function updateDashboardStats() {
    $.ajax({
        type        : 'POST',
        url         : '/dashboard/getstats',
        dataType    : 'json',
    })
    .done(function (response) {
        // update blocked threats chart

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

        // update threat indicators added chart
        $('#indicators_header').html('<h4><span class="stat_num">' + response.threat_indicators_total + '</span> Threat Indicators Added</h4><p>Last 7 Days</p>');
        chart_indicators.data.datasets = response.indicator_data;
        chart_indicators.update();

        // internet threat levels
        $('#level_image').attr('src', response.threat_level.image_path);
        $('#level_text').removeClass().addClass(response.threat_level.title.toLowerCase()).html(response.threat_level.title);
        $('#threat_description').html(response.threat_level.description);

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

        // connected devices
        if (response.connected_count) {
            $('#connected_devices').html(response.connected_count + ' of ' + response.total_devices + ' connected');
        } else {
            $('#connected_devices').html('No devices currently connected');
        }
        $('#see_all_devices').html('See all ' + response.total_devices);

        // device status
        $('.devices_container').html(response.device_status);

        // threat descriptions
        $('#threat_descriptions').html(response.threat_description);

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

            chart_blocks.update();
            chart_indicators.update();

            easeInPanels();
        }

        // adjust equalizer heights
        new Foundation.Equalizer($('.row[data-equalizer=first-row]')).applyHeight();
        new Foundation.Equalizer($('.row[data-equalizer=second-row]')).applyHeight();
        new Foundation.Equalizer($('.row[data-equalizer=third-row]')).applyHeight();

    })
    .fail(function (response) {
        console.log(response);
    });
}

function easeInPanels() {
    $('#spinner_container').fadeOut(500, function() {
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
    // panels.each(function() {
    //     $(this).animate({
    //         'opacity': 1,
    //         'margin-top': '0px'
    //     }, 500);
    // });
}

// abide validate form
function validateForm($form) {
    $form.foundation('validateForm',$form);

    if ($form.find('.form-error.is-visible').length || $form.find('.is-invalid-label').length || $form.find('.is-invalid-input').length) {
        return false;
    }

    return true;
}

// download products
function startDownload() {
    var os = getUrlVars()['os'];

    // this is unnecessary if redirect works properly
    if (os === undefined) os = getPlatform();

    window.location.href = '/download/download?os=' + os;
}

// start magic download
function startMagicDownload() {
    var os = getUrlVars()['os'];

    // this is unnecessary if redirect works properly
    if (os === undefined) os = getPlatform();

    window.location.href = '?os=' + os + '&dl=1';
}

// on download page enter, detect OS and redirect
function detectOS() {
    var os = getUrlVars()['os'];

    if (os === undefined) {
        os = getPlatform();
        window.location.href = '?os=' + os;
    }
}

// get user OS
function getPlatform() {
    var osName = 'win';

    if (navigator.appVersion.indexOf('Win') != -1) osName = 'win';
    if (navigator.appVersion.indexOf('Mac') != -1) osName = 'mac';

    return osName;
}

// Read a page's GET URL variables and return them as an associative array.
function getUrlVars() {
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

// sign up page - check if user has clicked google recaptcha
function signuprecaptchaCallback(token) {
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

//-- dashboard functions --//

// account page

function resetAccountFields($container) {
    var $expanded = $container.find('.row.expanded');

    $expanded.find('.edit-field').addClass('hide');
    $expanded.find('.static-field').removeClass('hide');

    $expanded.find('.edit').removeClass('hide');
    $container.find('.edit_action').addClass('hide');
}

// shield page

function resetNetworkSettings() {
    var $expanded = $('.network-settings').find('.row.expanded');

    $expanded.find('.edit-field').addClass('hide');
    $expanded.find('.static-field').removeClass('hide');

    $expanded.find('.cancel').removeClass('cancel').addClass('edit').html('Edit');
}

function numbersonly(myfield, e)
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
    if ((key==null) || (key==0) || (key==8) || (key==9) || (key==13) || (key==27) )
        return true;

    // numbers
    else if ((("0123456789").indexOf(keychar) > -1))
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

function numberWithCommas(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
