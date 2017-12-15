$ = jQuery.noConflict();
function LoanExpresSlider(config) {
    this.config = config;
    this.slided = false;
    this.initialize = function() {
        var loanSlider = document.getElementById('loan-slider');
        var that = this;
        noUiSlider.create(loanSlider, {
            start: that.config.start,
            step: that.config.step,
            connect: [true, false],
            range: {
                'min': [that.config.min],
                'max': [that.config.max]
            }
        });
        loanSlider.noUiSlider.on('slide', function() {
            that.slided = true;
            var val = loanSlider.noUiSlider.get();
            $('#howmuch-num').text(that.formatCurrency(val));
        });

        $('.loan-indicators li').click(function() {
            var stepId = $(this).data('target');
        });
    };
    this.formatCurrency = function(amount, n, x) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
        return '$' + parseInt(amount).toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
    };
    this.val = function() {
        var loanSlider = document.getElementById('loan-slider');
        return loanSlider.noUiSlider.get();
    };
    this.isValid = function() {
        $('#select-amount-error').hide();
        if (!this.slided) {
            $('#select-amount-error').show();
        }
        return this.slided;
    }
}

function LoanExpress() {
    this.valid = false;
    this.loanSlider;
    this.data;
    this.lenders = [];
    this.initialize = function(config) {
        this.loanSlider = new LoanExpresSlider({'max': 1000000000, 'min': 5000, 'step': 5000, 'start': 500000000});
        this.loanSlider.initialize();
        this.data = new FormData();
    };
    this.stepLoanSlider = function(e) {
        if (this.loanSlider.isValid()) {
            this.data.append('loan_amount', this.loanSlider.val());
            this.nextStep(e);
        }
    };
    this.stepTimeOfBusinessOperating = function(e) {
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.data.append('time_of_business_operating', $(e).data('val'));
        this.nextStep(e);
    };
    this.stepLoanAverageRevenue = function(e) {
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.data.append('loan_average_revenue', $(e).data('val'));
        this.nextStep(e);
    };
    this.stepLoanOffers = function(e) {
        var loanOffersFrm = $("#loan-offers-frm");
        var term_condition_ok = $('[name="term_condition"]').is(":checked");
        loanOffersFrm.validate();
        if (loanOffersFrm.valid() && term_condition_ok) {
            this.data.append('loan_customer_name', $('input[name="name"]').val());
            this.data.append('loan_customer_email', $('input[name="email"]').val());
            this.data.append('loan_customer_phone', $('input[name="phone"]').val());
            this.data.append('loan_customer_business', $('input[name="business"]').val());
            this.nextStep(e);
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
            var birth_day = $('input[name="birth_day"]').val();
            var birth_month = $('input[name="birth_month"]').val();
            var birth_year = $('input[name="birth_year"]').val();
            this.data.append('loan_dob', birth_day + '/' + birth_month + '/' + birth_year);
            this.nextStep(e);
        }
    };
    this.selectProduct = function(e){
        $(e).toggleClass('active');
    };
    this.stepLoanProducts = function(e){
        var active = $('.loan-products .active');
        that = this;
        if(active.length > 0){
            var products = [];
            active.each(function(index){
                products.push($(this).data('val'));
            });
            this.data.append('loan_products', products);
            this.nextStep(e);
        }else{
            alert('Please select the product below');
        }
    };
    this.stepLoanTerm = function(e){
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.data.append('loan_terms', $(e).data('val'));
        this.nextStep(e);
    };
    this.stepLoanIndustry = function(e){
        var industryFrm = $("#industry-frm");
        industryFrm.validate({
            messages: {},
            errorPlacement: function(error, element) {
                $('#industry_name_error_text').show();
            }
        });
        if (industryFrm.valid()) {
            this.data.append('loan_industry', $('[name="industry_ID"]').val());
            this.nextStep(e);
        }
    };
    this.stepLoanDrivingLicenseNumber = function(e){
        this.data.append('loan_driving_license_number', $('[name="driver_license"]').val());
        this.nextStep(e);
    };
    this.verifyAbn = function(e){
        var q = $('[name="abn_num"]').val();
        $('.abn-loader').show();
        if(q.length){
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data:{action:'get_abn_info',q:q},
                success: function(resp) {
                     $('.abn-loader').hide();
                    if(!resp.errno){
                        $('.abn-search-link, #abn_num_error_text').hide();
                        $('.abn-legal-name').html(resp.bussiness.name);
                        $('.abn-reg-date').html(resp.bussiness.effectiveFrom);
                        $('[name="abn_num"]').data('valid',true);
                        $('.abn-info').show();
                    }else{
                        $('#abn_num_error_text, .abn-search-link').show();
                        $('.abn-info').hide();
                        $('[name="abn_num"]').data('valid',false);
                    }
                }
            });
        }
    };
    this.stepAbn = function(e, skip){
        if(!skip){
            var abn_num_valid = $('[name="abn_num"]').data('valid');
            if(abn_num_valid){
                this.data.append('loan_abn', $('[name="abn_num"]').val());
            }
        }
        this.nextStep(e);
        this.loadRelevantLender();
    };
    this.selectLender = function(e){
        $(e).toggleClass('active');
    };
    this.stepLenders = function(e){
        var active = $('.loan-lenders .active');
        that = this;
        if(active.length > 0){
            var lenders = [];
            active.each(function(index){
                lenders.push($(this).data('id'));
            });
            this.data.append('loan_lenders', lenders);
        }else{
            alert('Please select the lenders below');
        }
    };
    this.loadRelevantLender = function(){
        $('.lender-loader').show();
        var lender_amount = this.data.get('loan_amount');
        var lender_term = this.data.get('loan_terms');
        var lender_products = this.data.get('loan_products');
        $.ajax({
            type: 'POST',
            url:ajaxurl,
            data:{action:'search_lender',lender_amount:lender_amount, lender_term: lender_term, lender_products:lender_products },
            success: function(resp) {
                $('.lender-loader').hide();
                if(!resp.errno && resp.count){
                    $('.lender-main').html(resp.html);
                    $('.loan-lenders .process-btn').show();
                }else{
                    
                }
            }
        });
    };
    this.nextStep = function(e) {
        var currentStep = $(e).closest('.step');
        var nextStep = currentStep.next('.step');
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

                }
            });
            if (indicator) {
                $('.loan-indicators li').removeClass('active');
                $('.loan-indicators li[data-step="' + stepId + '"]').addClass('active');
            }
        }
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
        var liCollection = $('.loan-indicators li');
        if (liCollection.index(li) == 0) {
            $('.loan-indicators').hide();
        }
        $('.loan-indicators li').removeClass('active');
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
                    $('.loan-indicators li[data-step="#' + step.attr('id') + '"]').addClass('active');
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

}

var loanExpress;
$(document).ready(function() {
    if ($('.cloanexpress').length > 0) {
        loanExpress = new LoanExpress();
        loanExpress.initialize();
    }
});

