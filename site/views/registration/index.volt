<div class="row expanded sign-in-buffer">
    <!-- Left main signup form -->
    <div class="medium-6 large-4 small-12 columns registration-container">
        {% if signupMessage is not empty %}
            <div class="small-12 medium-12 end columns clearfix callout error" data-closable>
                <span class="lead">{{signupMessage}}</span>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        {% endif %}

        <input type="hidden" id="current_step" value="{{ current_step }}" />

        {% if current_step == 'splash' or current_step == 'thankyou' %}

            {{ partial('registration/steps/' ~ helper.getStepPage(current_step)) }}

        {% else %}
            <div class="small-12 medium-12 end columns clearfix mb-1">
                {% if current_step == 1 %}
                <h2 class="text-light inline-block text-partner-blue mr-1-5">Create Your Account</h2>
                {% endif %}
            </div>

            {{ form('class': 'signup-form', 'data-abide': true, 'novalidate': true) }}
                {{ partial('registration/steps/' ~ helper.getStepPage(current_step)) }}
            </form>
            <p class="text-grey inline-block pl-1">Already have an account? {{ link_to('session/login','Log In', 'class': 'text-blue') }}.</p>
        {% endif %}
    </div>

    <!-- Right background image section -->
    <div class="medium-6 large-8 columns session-background hide-for-small-only"></div>
</div>

{% if current_step == 1 %}
<div class="reveal" id="dlg_contact_us" data-reveal>
    <img src="/img/small-logo-only.png" alt="Todyl Logo"/>
    <p>We're working on services for larger businesses such as yours.</p>
    <p>Please call us at <span class="text-blue">844-311-6900</span> to discuss protecting your business with one of our experts.</p>
    <button class="close-button" data-close aria-label="Close modal" type="button">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
{% endif %}

<style type="text/css">
.todyl-background {
    color: #404548 !important;
    background: none !important;
}
</style>

{% if current_step == 1 %}
<script type="text/javascript">
    var google_pk = '{{ google_pk }}';
    var google_invisible_pk = '{{ google_invisible_pk }}';
</script>
{% endif %}

{% if current_step == 3 %}
<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    // Create a Stripe client
    var stripe = Stripe('{{ stripe_pk }}');
</script>
{% endif %}
