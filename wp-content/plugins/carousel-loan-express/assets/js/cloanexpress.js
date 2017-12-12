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
        
        $('.loan-indicators li').click(function(){
            var stepId = $(this).data('target');
        });
    };
    this.formatCurrency = function(amount, n, x) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
        return '$' + parseInt(amount).toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
    };
    this.isValid = function(){
        $('#select-amount-error').hide();
        if(!this.slided){
            $('#select-amount-error').show();
        }
        return this.slided;
    }
}

function LoanExpress() { 
    this.valid = false;
    this.loanSlider;
    this.initialize = function(config){
        this.loanSlider = new LoanExpresSlider({'max': 1000000000, 'min': 5000, 'step': 5000, 'start': 500000000});
        this.loanSlider.initialize();
    };
    this.stepLoanSlider = function(e){
        if(this.loanSlider.isValid()){
            this.nextStep(e);
        }
    };
    this.stepLoanTerms = function(e){
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.nextStep(e);
    };
    this.stepLoanAverageRevenue = function(e){
        $(e).closest('.step').find('.step-buttons').removeClass('active');
        $(e).addClass('active');
        this.nextStep(e);
    };
    this.stepLoanType = function(e){
        
    };
    this.nextStep = function(e){
        var currentStep = $(e).closest('.step');
        var nextStep = currentStep.next('.step');
        if(nextStep.length){
            var width = currentStep.outerWidth(true);
            var stepId = '#'+ nextStep.attr('id');
            currentStep.data('valid', true);
            currentStep.animate({left: '-' + width});
            nextStep.animate({left: '0px'}, {
                'done': function(){
                    $('.loan-indicators').css({display:'flex'});
                }
            });
            $('.loan-indicators li').removeClass('active');
            $('.loan-indicators li[data-step="'+ stepId +'"]').addClass('active');
        }
    };
    this.prevStep = function(e){
        var currentStep = $(e).closest('.step');
        var prevStep = currentStep.prev('.step');
        if(prevStep.length){
            prevStep.animate({left: '0px'});
        }
    };
    this.step = function(e){
        var li = $(e);
        var stepId = li.data('step');
        var liCollection = $('.loan-indicators li');
        if(liCollection.index(li) == 0){
            $('.loan-indicators').hide();
        }
        $('.loan-indicators li').removeClass('active');
        $('.step').each(function(){
            var step = $(this);
            var valid = step.data('valid');
            var id = '#' + step.attr('id');
            if(id == stepId && valid == true){
                if(step.filter(":animated").length){
                    step.stop();
                }
                if(step.css('left') != '0px'){
                    step.animate({left: '0px'});
                }
                li.addClass('active');
                return false; 
            }else{
                if(valid == false){
                    $('.loan-indicators li[data-step="#'+ step.attr('id') +'"]').addClass('active');
                    if(step.filter(":animated").length){
                        step.stop();
                    }
                    if(step.css('left') != '0px'){
                        step.animate({left: '0px'});
                    }
                    return false; 
                }else{
                    if(step.filter(":animated").length){
                        step.stop();
                    }
                    if(step.css('left') == '0px'){
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
    if($('.cloanexpress').length > 0){
        loanExpress = new LoanExpress();
        loanExpress.initialize();
    }
});

