<div class="todyl-background">
    <div class="row pad-t-2">
        {% if signupMessage is not empty %}
            <div class="small-12 medium-10 medium-push-1 end columns clearfix callout alert" data-closable>
                <span class="lead">{{signupMessage}}</span>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        {% endif %}

        <input type="hidden" id="current_step" value="{{ current_step }}" />

        <div class="small-12 medium-10 medium-push-1 end columns clearfix" style="margin-bottom: 1em">
            {% if current_step == 1 %}
            <span class="float-right pad-t-2">Already a member? {{link_to('session/login','Sign In')}}</span>
            {% endif %}
            <h2>Sign Up</h2>
        </div>

        {{ form('class': 'beta-signup-form', 'data-abide': true, 'novalidate': true) }}
            <div class="small-12 medium-push-1 medium-3 columns">
                {{ helper.renderSteps(saved_data) }}
            </div>

            <div class="medium-7 medium-push-1 end columns">
                {{ helper.renderFields(saved_data, signupErrors) }}
            </div>

        </form>
    </div>
</div>

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
