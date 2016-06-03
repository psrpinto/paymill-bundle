var Paymill = {
    els: {
        form:          '.paymill',
        submit:        '.paymill input[type=submit]',
        number:        '.paymill-number input',
        expiry:        '.paymill-expiry input',
        holder:        '.paymill-holder input',
        cvc:           '.paymill-cvc input',
        errors:        'form > .paymill-errors',
        token:         'input[name="jms_choose_payment_method[data_paymill][token]"]',
        methodPaymill: "#jms_choose_payment_method_method input[value='paymill']"
    },

    /**
     * @param string amount   "4900" for 49,00 EUR
     * @param string currency ISO 4217 string, i.e. "EUR"
     */
    init: function(options) {
        this.amount   = Number(options.amount);
        this.currency = options.currency;

        $('[data-numeric]').payment('restrictNumeric');
        $(this.els.number).payment('formatCardNumber');
        $(this.els.expiry).payment('formatCardExpiry');
        $(this.els.cvc).payment('formatCardCVC');

        // Remove previous errors when a field is changed
        $(this.els.form).find('input').keyup(function() {
            $(this).removeClass('error');
        });

        $(this.els.number).keyup(PaymillUtil.bind(this.setCardType, this));
        $(this.els.form).submit(PaymillUtil.bind(this.onSubmit, this));

        $(this.els.methodPaymill).prop('checked', 'checked');
        this.setCardType();
    },

    /**
     * Show the card type icon according to the (partial) card number.
     */
    setCardType: function() {
        var number  = $(this.els.number).val();
        var $target = $('.paymill-number');

        $target.removeClass('visa mastercard maestro amex identified');

        var cardType = $.payment.cardType(number);
        if (cardType && number.length >= 4) {
            $target.addClass(cardType).addClass('identified');
        }
    },

    /**
     * Called when the submit button is clicked
     */
    onSubmit: function() {
        if (!$(this.els.methodPaymill).prop('checked')) {
            // some other payment method was selected
            return;
        }

        $(this.els.form).find('input').removeClass('error');
        this.error('');
        this.enableSubmit(false);

        var number = $(this.els.number).val();
        var expiry = $(this.els.expiry).payment('cardExpiryVal');
        var cvc    = $(this.els.cvc).val();
        var holder = $(this.els.holder).val();

        if (!paymill.validateCardNumber(number)) {
            $(this.els.number).addClass('error');
        }

        if (!paymill.validateExpiry(expiry.month, expiry.year)) {
            $(this.els.expiry).addClass('error');
        }

        if (!paymill.validateCvc(cvc, number)) {
            $(this.els.cvc).addClass('error');
        }

        if (holder === '') {
            $(this.els.holder).addClass('error');
        }

        if ($(this.els.form).find('input.error').length) {
            this.enableSubmit();
            return false;
        }

        paymill.createToken({
            number:     number,
            exp_month:  expiry.month,
            exp_year:   expiry.year,
            cvc:        cvc,
            cardholder: holder,
            amount_int: this.amount,
            currency:   this.currency
        }, PaymillUtil.bind(this.onResponse, this));

        return false;
    },

    /**
     * Received a response from the Paymill API.
     */
    onResponse: function(error, paymillResponse) {
        if (error) {
            this.error(error.apierror);
            this.enableSubmit();
        } else {
            this.submitForm(paymillResponse);
        }
    },

    /**
     * Submit the form through Ajax
     */
    submitForm: function(paymillResponse) {
        var form = $(this.els.form);
        form.find(this.els.token).val(paymillResponse.token);

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            success: PaymillUtil.bind(function(data) {
                if (data.error) {
                    this.error(data.message);
                    this.enableSubmit();
                } else {
                    window.location.href = data.successUrl;
                }
            }, this),
            error: PaymillUtil.bind(function(data) {
                this.error('Something went wrong. Please try again and contact us if the problem persists.');
                this.enableSubmit();
            }, this)
        });
    },

    /**
     * Enable or disable the submit button
     */
    enableSubmit: function(enable) {
        if (enable === undefined || enable) {
            $(this.els.submit).removeAttr('disabled');
            if (this.submitBtnText) {
                $(this.els.submit).val(this.submitBtnText);
            }
        } else {
            this.submitBtnText = $(this.els.submit).val();
            $(this.els.submit).attr('disabled', 'disabled').val('Processing payment...');
        }
    },

    /**
     * Show an error message
     */
    error: function(message) {
        if (message == '') {
            $(this.els.errors).hide();
            return;
        }

        $ul = $(this.els.errors).find('ul');
        $ul.html('');
        $ul.append(
            '<li class="alert alert-error">' + message + '</li>'
        );
        $(this.els.errors).show();
    }
};

// Create a function bound to a given object (assigning this, and arguments,
// optionally). Borrowed from underscore.js
var PaymillUtil = {
    bind: function(func, context) {
        var args, bound;
        args = Array.prototype.slice.call(arguments, 2);
        return bound = function() {
            if (!(this instanceof bound)) return func.apply(context, args.concat(Array.prototype.slice.call(arguments)));
            ctor.prototype = func.prototype;
            var self = new ctor;
            ctor.prototype = null;
            var result = func.apply(self, args.concat(Array.prototype.slice.call(arguments)));
            if (Object(result) === result) return result;
            return self;
        };
    }
};
