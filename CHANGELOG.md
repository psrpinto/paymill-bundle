# Changelog

## 0.3.0 (2015-06-14)

* **[BC break]** The `Resources/assets` directory has been renamed `Resources/public`, as per Symfony's coding standards. If you're overriding `Resources/views/init.html.twig` (through the `memeoirs_paymill.initialize_template` configuration) but still use the javascript assets provided by the bundle, you need to change the way you reference those assets:

        {# before #}
        {% javascripts
          '@MemeoirsPaymillBundle/Resources/assets/js/jquery.payment.js'
          '@MemeoirsPaymillBundle/Resources/assets/js/CreditCardForm.js'
        %}
          <script src="{{ asset_url }}"></script>
        {% endjavascripts %}

        {# after #}
        <script src="{{ asset('bundles/memeoirspaymill/js/jquery.payment.js') }}"></script>
        <script src="{{ asset('bundles/memeoirspaymill/js/CreditCardForm.js') }}"></script>

    In addition, if you're referencing `paymill.css`:

        {# before #}
        {% stylesheets '@MemeoirsPaymillBundle/Resources/assets/css/paymill.css' %}
          <link rel="stylesheet" type="text/css" href="{{ asset_url }}" />
        {% endstylesheets %}

        {# after #}
        <link rel="stylesheet" type="text/css" href="{{ asset('bundles/memeoirspaymill/css/paymill.css') }}">
