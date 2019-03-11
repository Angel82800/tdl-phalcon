<div class="todyl-background">
    <div class="row pwreset_container v-center">
        <div class="flash-container small-12 medium-6 medium-centered end columns clearfix">
            {{ partial('partials/flash') }}
        </div>

        <div class="small-12 medium-6 medium-centered end columns clearfix">
            <div class="row">
                <div class="small-12 medium-12 columns">
                    <h2>Create a New Password</h2>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-12 columns">
                    <p>Create a new password for {{ user.email }}</p>
                </div>
            </div>
        </div>

        <div class="small-12 medium-6 medium-centered columns">
            {{ form('class': 'pwreset-form', 'data-abide': true, 'novalidate': true) }}

            {{ form.renderDecorated('todyl_password', ['required': true, 'class': 'validate']) }}

            <div class="row">
                <div class="small-12 medium-5 columns">
                    {{ form.renderDecorated('submit') }}
                </div>
                <div class="small-12 medium-5 columns end">
                    <a class="button hollow" href="/session/login">Back to Log In</a>
                </div>
            </div>

            {{ form.render('todyl_email', ['value': user.email]) }}
            {{ form.render('resetpass_time', ['value': user.token_time]) }}

            {{ form.render('csrf', ['value': security.getSessionToken()]) }}

            </form>
        </div>

    </div>
</div>

<style type="text/css">
    .todyl-background {
        color: #333 !important;
        background: none !important;
    }
</style>
