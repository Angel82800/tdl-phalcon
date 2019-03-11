<!DOCTYPE html>
<html lang="en">
    <head>
        {{ get_title() }}
        {{ get_keywords() }}
        {{ get_description() }}
        {{ get_robots() }}

        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2b80bb">

        <link rel='icon' href='/img/favicon.png?' type='image/png' />
        {% block head %}
            <link rel="stylesheet" href="/css/base.css?1519798184478244423">
        {% endblock %}

        {{ partial("partials/google/analytics") }}
        {{ partial("partials/facebook/pixel") }}

        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-M5Z7N47');</script>
        <!-- End Google Tag Manager -->


    </head>
    <body>

        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M5Z7N47"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->

        <div>
            {{ partial("partials/messageContainer") }}
            {{ partial("partials/offCanvas") }}

            <div class="off-canvas-content" data-off-canvas-content data-transition="overlap">
                <!-- original content goes in this container -->
                
                    {{ flash.output() }}
                    {% block content %}
                        {{ content() }}
                    {% endblock %}
                
                <!-- close wrapper, no more content after this -->
            </div>
        </div>

        <script type="text/javascript" src="/js/min/scripts_footer.min.js?1519798184478244423"></script>

        {% if router.getControllerName() in ['registration', 'beta'] %}
        <script src="https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit"></script>
        {% endif %}

        {{ partial("partials/adroll/pixel") }}
        {{ partial("partials/hubspot/pixel") }}
        {{ partial("partials/google/adwords") }}

    </body>
</html>
