var FmPaymentPaymill = {
    els: {
        form:    'form',
        submit:  'form input[type=submit]',
        number:  '#jms_choose_payment_method_data_paymill_number',
        expires: '#jms_choose_payment_method_data_paymill_expires',
        holder:  '#jms_choose_payment_method_data_paymill_holder',
        code:    '#jms_choose_payment_method_data_paymill_code',
        errors:  '.payment-errors'
    },

    trans: {
        numberInvalid: 'Invalid card number',
        expiresInvalid: 'Invalid expiration date'
    },

    /**
     * @param string amount   "4900" for 49,00 EUR
     * @param string currency ISO 4217 i.e. "EUR"
     */
    init: function (amount, currency, options) {
        this.amount   = amount;
        this.currency = currency;
        this.options  = options;

        $(this.els.form).submit(Fm.bind(this.onSubmit, this));
    },

    /**
     * Called when the submit button is clicked
     */
    onSubmit: function () {
        this.enableSubmit(false);

        // var month = $(this.els.expires).val();
        // var year  = $(this.els.expires).val();
        var month = '12';
        var year  = '2013';

        if (!paymill.validateCardNumber($(this.els.number).val())) {
            this.error(this.trans.numberInvalid);
            this.enableSubmit();
            return false;
        }

        if (!paymill.validateExpiry(month, year)) {
            this.error(this.trans.expiresInvalid);
            this.enableSubmit();
            return false;
        }

        paymill.createToken({
            number:     $(this.els.number).val(),
            exp_month:  month,
            exp_year:   year,
            cvc:        $(this.els.code).val(),
            cardholder: $(this.els.holder).val(),
            amount_int: $(this.amount).val(),
            currency:   $(this.currency).val()
        }, Fm.bind(this.onResponse, this));

        return false;
    },

    /**
     * Received a response from the Paymill API.
     *
     * @param {[type]} error  [description]
     * @param {[type]} result [description]
     */
    onResponse: function (error, result) {
        if (error) {
            this.error(error.apierror);
        } else {
            this.error('');
            var form = $(this.els.form);
            form.find('input[name="jms_choose_payment_method[data_paymill][token]"]')
                .val(result.token);
            form.get(0).submit();
        }

        this.enableSubmit();
    },

    /**
     * Enable or disable the submit button
     */
    enableSubmit: function (enable) {
        if (enable === undefined || enable) {
            $(this.els.submit).removeAttr('disabled');
        } else {
            $(this.els.submit).attr('disabled', 'disabled');
        }
    },

    /**
     * Show an error message
     */
    error: function (message) {
        $(this.els.errors).text(message);
    }
};

var Fm = {
    // Create a function bound to a given object (assigning this, and arguments,
    // optionally). Borrowed from underscore.js
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