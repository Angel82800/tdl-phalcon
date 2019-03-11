<div class="row expanded" data-equalizer="invite-columns">
    <!-- Left main invite form -->
    <div class="medium-4 small-12 columns mobile-grey pwreset_container" data-equalizer-watch="invite-columns">
        <!-- <div class="flash-container small-12 medium-6 medium-centered end columns clearfix">
            {{ partial('partials/flash') }}
        </div> -->

        <div class="medium-12 columns clearfix">
            <div class="row">
                <div class="small-12 medium-12 columns">
                    <h3>Create Your Account</h3>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-12 columns">
                    <p>{{ org_name }} uses Todyl to protect their business from cyber threats and attacks. Simply create a password below and follow the instructions on screen to protect this device. It only takes a minute.</p>
                    <h4 class="text-light-grey">{{ user.email }}</h4>
                </div>
            </div>
        </div>

        <div class="medium-12 columns kill-padding">
            {{ form('class': 'pwreset-form', 'data-abide': true, 'novalidate': true) }}

            {{ form.renderDecorated('todyl_password', ['required': true, 'class': 'validate']) }}

            <div class="row">
                <div class="small-12 medium-5 medium-offset-7 columns">
                    {{ form.renderDecorated('submit') }}
                </div>
            </div>

            {{ form.render('todyl_email', ['value': user.email]) }}

            {{ form.render('csrf', ['value': security.getSessionToken()]) }}

            </form>
        </div>
    </div>

    <!-- Right background image section -->
    <div class="medium-8 columns session-background hide-for-small-only" data-equalizer-watch="invite-columns"></div>
</div>

<style type="text/css">
    .todyl-background {
        color: #333 !important;
        background: none !important;
    }
</style>
