<div class="todyl-background">
    <div class="row pwreset_container v-center">
        <div class="flash-container small-12 medium-6 medium-centered end columns clearfix">
            {{ partial('partials/flash') }}
        </div>

        <div class="small-12 medium-6 medium-centered end columns clearfix">
            <div class="small-12 columns">
                <h2 class="text-light">Reset Your Password</h2>
            </div>
        </div>

        <div class="small-12 medium-6 medium-centered columns">
            {{ form('class': 'pwreset-form', 'data-abide': true, 'novalidate': true) }}

            {{ form.renderDecorated('todyl_email', ['required': true, 'class': 'validate', 'abide': ['pattern': 'email', 'error': 'Please enter correct email']]) }}

            <div class="row">
                <div class="small-6 columns text-right pt-half">
                    <a href="/session/login">Back to Log In</a>
                </div>
                <div class="small-6 columns">
                    {{ form.renderDecorated('send', [ 'class': 'button btn-wide' ]) }}
                </div>
            </div>

            {{ form.render('csrf', ['value': security.getSessionToken()]) }}

            </form>
        </div>

    </div>
</div>
