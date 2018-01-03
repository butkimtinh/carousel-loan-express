$ = jQuery.noConflict();
function LoanExpresSlider(config) {
    this.config = config;
    this.slided = false;
    this.loanSlider;
    this.initialize = function() {
        this.loanSlider = document.getElementById('loan-slider');
        var that = this;
        noUiSlider.create(this.loanSlider, {
            start: that.config.start,
            step: that.config.step,
            connect: [true, false],
            range: {
                'min': [that.config.min],
                'max': [that.config.max]
            }
        });
        this.loanSlider.noUiSlider.on('slide', function() {
            that.updatePrice();
        });
        this.loanSlider.noUiSlider.on('set', function() {
            that.updatePrice();
        });
    };
    this.updatePrice = function() {
        this.slided = true;
        var val = this.loanSlider.noUiSlider.get();
        $('#howmuch-num').text(this.formatCurrency(val));
    };
    this.formatCurrency = function(amount, n, x) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
        return '$' + parseInt(amount).toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
    };
    this.val = function(num) {
        if (!num) {
            return this.loanSlider.noUiSlider.get();
        } else {
            this.loanSlider.noUiSlider.set(num);
            return num;
        }

    };
    this.isValid = function() {
        $('#select-amount-error').hide();
        if (!this.slided) {
            $('#select-amount-error').show();
        }
        return this.slided;
    }
}
function LoanExpress(config) {
    this.valid = false;
    this.config = config;
    this.loanSlider;
    this.container;
    this.stepValid = [];
    this.initialize = function() {
        this.container = $('.cloanexpress');
        this.container.data(this.config.data);
        this.initStep();
        this.loanSlider = new LoanExpresSlider({'max': 1000000, 'min': 5000, 'step': 5000, 'start': 500000});
        this.loanSlider.initialize();

        var loan_amount = this.container.data('loan_amount');
        if (loan_amount) {
            this.loanSlider.val(loan_amount);
        }
        $.cookie('_cletoken', cletoken);
        $.cookie('appId', appId);
        if (this.config.stepValid) {
            this.stepValid = this.config.stepValid.split(',');
            $(this.config.stepValid).data('valid', true);
            this.step(document.getElementById(this.config.step));
        }
    };

    this.stepLoanSlider = function(e) {
        if (this.loanSlider.isValid()) {
            this.container.data('loan_amount', this.loanSlider.val());
            this.nextStep(e);
        }
    };

    this.stepTimeOfBusinessOperating = function(e) {
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.container.data('time_of_business_operating', $(e).data('val'));
        this.nextStep(e);
    };

    this.stepLoanAverageRevenue = function(e) {
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.container.data('loan_average_revenue', $(e).data('val'));
        this.nextStep(e);
    };
    this.fieldOnChange = function(e) {
        if ($(e).valid() == true) {
            $(e).closest('.wpcf7-form-control-wrap').find('.fa').css({display:'block'});
        } else {
            $(e).closest('.wpcf7-form-control-wrap').find('.fa').css({display:'none'});;
        }
    };
    this.stepLoanOffers = function(e) {
        var loanOffersFrm = $("#loan-offers-frm");
        var that = this;
        var term_condition_ok = $('[name="term_condition"]').is(":checked");
        var nameEle = $('.loan-offers input[name="name');
        var emailEle = $('.loan-offers input[name="email');
        var phoneEle = $('.loan-offers input[name="phone');
        var businessEle = $('.loan-offers input[name="business');
        if (loanOffersFrm.valid() && term_condition_ok) {
            var name = nameEle.val();
            var email = emailEle.val();
            var phone = phoneEle.val();
            that.showLoader();
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {action: 'cloanexpress_save', user_name: name, user_email: email, user_phone: phone, appId:appId},
                success: function(resp) {
                    that.hideLoader();
                    if (!resp.errno) {
                        that.container.data('loan_author_id', resp.author_id);
                        that.container.data('loan_customer_name', name);
                        that.container.data('loan_customer_email', email);
                        that.container.data('loan_customer_phone', phone);
                        that.container.data('loan_customer_business', businessEle.val());
                        if(resp.errno == 3){
                            appId = resp.appId;
                            UID = resp.UID;
                        }
                        that.nextStep(e);
                    } else {
                        alert('Sorry we cant process your data.');
                    }
                }
            });

        }
        if (loanOffersFrm.valid() && !term_condition_ok) {
            alert('Please tick the checkbox below to go to next step');
        }
    };
    this.stepDob = function(e) {
        var dobFrm = $("#dob-frm");
        dobFrm.validate({
            messages: {},
            errorPlacement: function(error, element) {
                $('#birth_date_error_text').show();
            }
        });
        if (dobFrm.valid()) {
            $('#birth_date_error_text').hide();
            var birth_day = $('[name="birth_day"]').val();
            var birth_month = $('[name="birth_month"]').val();
            var birth_year = $('[name="birth_year"]').val();
            this.container.data('loan_dob', birth_day + '/' + birth_month + '/' + birth_year);
            this.nextStep(e);
        }
    };
    this.selectProduct = function(e) {
        $(e).toggleClass('active');
    };
    this.stepLoanProducts = function(e) {
        var active = $('.loan-products .active');
        that = this;
        if (active.length > 0) {
            that.container.data('loan_products_length', active.length);
            active.each(function(index) {
                that.container.data('loan_products[' + index + ']', $(this).data('val'));
            });
            this.nextStep(e);
        } else {
            alert('Please select the product below');
        }
    };
    this.stepLoanTerm = function(e) {
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.container.data('loan_terms', $(e).data('val'));
        this.nextStep(e);
    };
    this.stepLoanIndustry = function(e) {
        var loan_driving_license_number = this.container.data('loan_driving_license_number');
        if (loan_driving_license_number) {
            $('.loan-driving-license-number [name="driver_license"]').val(loan_driving_license_number);
        }
        var industryFrm = $("#industry-frm");
        industryFrm.validate({
            messages: {},
            errorPlacement: function(error, element) {
                $('#industry_name_error_text').show();
            }
        });
        if (industryFrm.valid()) {
            this.container.data('loan_industry', $('[name="industry_ID"]').val());
            this.nextStep(e);
        }
    };
    this.stepLoanDrivingLicenseNumber = function(e) {
        this.container.data('loan_driving_license_number', $('[name="driver_license"]').val());
        this.nextStep(e);
    };
    this.verifyAbn = function(e) {
        var q = $('[name="abn_num"]').val();
        $('.abn-loader').show();
        if (q.length) {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {action: 'get_abn_info', q: q},
                success: function(resp) {
                    $('.abn-loader').hide();
                    if (!resp.errno) {
                        $('.abn-search-link, #abn_num_error_text').hide();
                        $('.abn-legal-name').html(resp.bussiness.name);
                        $('.abn-reg-date').html(resp.bussiness.effectiveFrom);
                        $('.abn-state-name').html(resp.bussiness.stateName);
                        $('.loan-abn .loan-form-field .wpcf7-form-control-wrap .fa').show();
                        $('[name="abn_num"]').data('valid', true);
                        $('.abn-info').show();
                    } else {
                        $('.loan-abn .loan-form-field .wpcf7-form-control-wrap .fa').hide();
                        $('#abn_num_error_text, .abn-search-link').show();
                        $('.abn-info').hide();
                        $('[name="abn_num"]').data('valid', false);
                    }
                }
            });
        }
    };
    this.stepAbn = function(e, skip) {
        if (!skip) {
            var abn_num_valid = $('[name="abn_num"]').data('valid');
            if (abn_num_valid) {
                this.container.data('loan_abn', $('[name="abn_num"]').val());
                this.nextStep(e);
            }
            var abn_num = $('[name="abn_num"]').val();
            if (!abn_num.length) {
                alert('Please enter number abn to check');
            }
        } else {
            this.nextStep(e);
        }
        //this.loadRelevantLender();
    };
    this.selectLender = function(e) {
        $(e).toggleClass('active');
    };
    this.stepLenders = function(e) {
        var active = $('.loan-lenders .active');
        that = this;
        if (active.length > 0) {
            that.container.data('loan_lenders_length', active.length);
            active.each(function(index) {
                that.container.data('loan_lenders[' + index + ']', $(this).data('id'));
            });
            $('.modal').show();
        } else {
            alert('Please select the lenders below');
        }
    };
    this.loadRelevantLender = function() {
        $('.lender-loader').show();
        var lender_amount = this.container.data('loan_amount');
        var lender_term = this.container.data('loan_terms');
        var lender_products = this.container.data('loan_products');
        var that = this;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {action: 'search_lender', lender_amount: lender_amount, lender_term: lender_term, lender_products: lender_products},
            success: function(resp) {
                $('.lender-loader').hide();
                if (!resp.errno && resp.count) {
                    $('.lender-main').html(resp.html);
                    $('.loan-lenders .process-btn').show();
                    $('.loan-lenders .fail-btn').hide();
                    var loan_lenders_length = that.container.data('loan_lenders_length');
                    if (loan_lenders_length) {
                        for (var i = 0; i < loan_lenders_length; i++) {
                            var id = this.container.data('loan_lenders[' + i + ']');
                            $('.loan-lenders [data-id="' + id + '"]').addClass('active');
                        }
                    }
                } else {
                    $('.lender-main').html(resp.msg);
                    $('.loan-lenders .fail-btn').show();
                    $('.loan-lenders .process-btn').hide();
                }
            }
        });
    };
    this.stepAgree = function(e) {
        var disclaimer_check_ok = $('[name="disclaimer-check"]').is(":checked");
        if (disclaimer_check_ok) {
            $('.modal').hide();
            this.nextStep(e);
        } else {
            alert('Please tick the checkbox below to go to next step');
        }
    };
    this.stepFinish = function(e) {
        $('.additional-loader').show();
        var that = this;
        this.container.data('business_phone_number', $('[name="business_phone_number"]').val());
        this.container.data('best_time_to_reach', $('[name="best_time_to_reach"]').val());
        this.container.data('action', 'create_application');
        this.container.data('cletoken', cletoken);

        var loanadditionalfrm = $('#loan-additional-frm');
        if(loanadditionalfrm.valid()){
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: this.container.data(),
                success: function(resp) {
                    $('.additional-loader').hide();
                    if (!resp.errno) {
                        $.removeCookie('_cletoken');
                        $.removeCookie(cletoken);
                        that.nextStep(e, false);
                    } else {
                        alert(resp.msg);
                    }
                }
            });
        }
    };
    this.nextStep = function(e, sync = true) {
        var currentStep = $(e).closest('.step');
        var nextStep = currentStep.next('.step');
        var that = this;
        that.stepValid.push('#' + currentStep.attr('id'));
        if (nextStep.length) {
            var width = currentStep.outerWidth(true);
            var stepId = '#' + nextStep.attr('id');
            var indicator = nextStep.data('indicator');
            currentStep.data('valid', true);
            currentStep.animate({left: '-' + width});
            nextStep.animate({left: '0px'}, {
                'done': function() {
                    if (indicator) {
                        $('.loan-indicators').css({display: 'flex'});
                    } else {
                        $('.loan-indicators').css({display: 'none'});
                    }
                    if (sync) {
                        that.saveStep(nextStep.attr('id'));
                    }
                }
            });
            $.cookie('current_step', stepId);
            if (indicator) {
                $('.loan-indicators li').removeClass('active');
                $('.loan-indicators li[data-step="' + stepId + '"]').addClass('active');
            }
    }
    };
    this.saveStep = function(stepId, status ='proccessing') {
        if(appId == ''){
            return this;
        }
        var data = {
            action: 'save_step',
            data: this.container.data(),
            appId: appId,
            user_id: UID,
            status: status
        };
        var that = this;
        that.showLoader();
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function(resp) {
                that.hideLoader();
                if (!resp.errno) {
                    $.cookie(cletoken, cledata);
                }
            }
        });
    };
    this.prevStep = function(e) {
        var currentStep = $(e).closest('.step');
        var prevStep = currentStep.prev('.step');
        if (prevStep.length) {
            prevStep.animate({left: '0px'});
        }
    };
    this.step = function(e) {
        var li = $(e);
        var stepId = li.data('step');
        var stepTarget = $(stepId);
        var indicator = stepTarget.data('indicator');
        $('.loan-indicators li').removeClass('active');
        if (!indicator) {
            $('.loan-indicators').hide();
        } else {
            $('.loan-indicators').show();
        }
        $('.step').each(function() {
            var step = $(this);
            var valid = step.data('valid');
            var id = '#' + step.attr('id');
            if (id == stepId && valid == true) {
                if (step.filter(":animated").length) {
                    step.stop();
                }
                if (step.css('left') != '0px') {
                    step.animate({left: '0px'});
                }
                li.addClass('active');
                return false;
            } else {
                if (valid == false) {
                    if (indicator) {
                        $('.loan-indicators li[data-step="#' + stepId + '"]').addClass('active');
                    }

                    if (step.filter(":animated").length) {
                        step.stop();
                    }
                    if (step.css('left') != '0px') {
                        step.animate({left: '0px'});
                    }
                    return false;
                } else {
                    if (step.filter(":animated").length) {
                        step.stop();
                    }
                    if (step.css('left') == '0px') {
                        var width = step.outerWidth(true);
                        step.animate({left: '-' + width});
                    }
                }
            }
        });
    };
    this.initStep = function() {
        var time_of_business_operating = this.container.data('time_of_business_operating');
        if (time_of_business_operating) {
            $('.loan-time-of-business-operating step-buttons').removeClass('active');
            $('.loan-time-of-business-operating [data-val="' + time_of_business_operating + '"]').addClass('active');
        }
        var loan_average_revenue = this.container.data('loan_average_revenue');
        if (loan_average_revenue) {
            $('.loan-monthly-revenue step-buttons').removeClass('active');
            $('.loan-monthly-revenue [data-val="' + loan_average_revenue + '"]').addClass('active');
        }
        var loan_customer_name = this.container.data('loan_customer_name');
        if (loan_customer_name) {
            $('.loan-offers input[name="name"]').val(loan_customer_name);
        }

        var loan_customer_email = this.container.data('loan_customer_email');
        if (loan_customer_email) {
            $('.loan-offers input[name="email"]').val(loan_customer_email);
        }

        var loan_customer_phone = this.container.data('loan_customer_phone');
        if (loan_customer_phone) {
            $('.loan-offers input[name="phone"]').val(loan_customer_phone);
        }

        var loan_customer_business = this.container.data('loan_customer_business');
        if (loan_customer_business) {
            $('.loan-offers input[name="business"]').val(loan_customer_business);
        }

        var loan_dob = this.container.data('loan_dob');
        if (loan_dob) {
            var ex = loan_dob.split("/");
            var birth_day = ex[0], birth_month = ex[1], birth_year = ex[2];
            $('.loan-dob [name="birth_day"]').val(birth_day);
            $('.loan-dob [name="birth_month"]').val(birth_month);
            $('.loan-dob [name="birth_year"]').val(birth_year);
        }

        var loan_products_length = this.container.data('loan_products_length');
        if (loan_products_length) {
            $('.loan-products .step-buttons').removeClass('active');
            for (var i = 0; i < loan_products_length; i++) {
                var productId = this.container.data('loan_products[' + i + ']');
                $('.loan-products [data-val="' + productId + '"]').addClass('active');
            }
        }

        var loan_terms = this.container.data('loan_terms');
        if (loan_terms) {
            $('.loan-terms .step-buttons').removeClass('active');
            $('.loan-terms [data-val="' + loan_terms + '"]').addClass('active');
        }

        var loan_industry = this.container.data('loan_industry');
        if (loan_industry) {
            $('.loan-industry [name="industry_ID"]').val(loan_industry);
        }

        var loan_abn = this.container.data('loan_abn');
        if (loan_abn) {
            $('.loan-abn [name="abn_num"]').val(loan_abn);
        }

        var business_phone_number = this.container.data('business_phone_number');
        if (business_phone_number) {
            $('.loan-additional [name="business_phone_number"]').val(business_phone_number);
        }
        var best_time_to_reach = this.container.data('best_time_to_reach');
        if (best_time_to_reach) {
            $('.loan-additional [name="best_time_to_reach"]').val(best_time_to_reach);
        }
    };
    this.showLoader = function() {
        $('.loan-loader').show();
    };
    this.hideLoader = function() {
        $('.loan-loader').hide();
    };
}