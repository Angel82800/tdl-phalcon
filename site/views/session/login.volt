<div class="row expanded">
    <!-- Left main signin form -->
    <div class="small-12 medium-6 large-4 columns signin_container">
        <div class="flash-container pb-2 small-12 medium-12 medium-centered end columns clearfix">
            {{ partial('partials/flash') }}
        </div>

        <div class="small-12 medium-12 end columns clearfix mb-1">
            <h2 class="text-light text-partner-blue inline-block">Log In</h2>
        </div>

        {% if loginMessage is not empty %}
        <div class="callout alert" data-closable>
            <span class="lead">{{loginMessage}}</span>
            <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        {% endif %}

        {{ form('class': 'signin-form', 'data-abide': true, 'novalidate': true) }}

        {{ form.renderField('todyl_email') }}
        {{ form.renderField('todyl_password') }}

        <div class="row expanded">
            <div class="small-12 columns kill-padding">
                {{ form.renderField('Log In') }}
            </div>
        </div>

        {{ form.renderCsrf() }}

        </form>
        <div class="row expanded">
            <div class="small-12 columns">
                
                <p class="text-grey">
                    {{ link_to('session/forgotPassword', 'Forgot Your Password?', 'class': 'text-blue') }}&nbsp;&nbsp;|&nbsp;&nbsp;Don’t have an account? {{ link_to('signup','Sign Up') }}.
                </p>
            </div>
        </div>
    </div>

    <!-- Right background image section -->
    <div class="medium-6 large-8 columns session-background hide-for-small-only"></div>
</div>
