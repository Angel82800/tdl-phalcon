{{ form('class': 'billing-form', 'data-abide': true, 'novalidate': true) }}
<div class="dash-page panels_container billing-container">
    <div class="row expanded" data-equalizer="billing">
        {% if thankyou is empty %}

        <div class="small-12 large-8 columns" data-equalizer-watch="billing">
            <div class="row">
                <div class="panel">
                    <div class="small-12 large-12 columns">
                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('caption_promo') }}

                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('promo_section') }}
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="panel">
                    <div class="small-12 large-12 columns end">
                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('caption_payment') }}

                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('separator_grey') }}
                    </div>

                    <div class="small-12 large-12 columns end">
                        {{ form.renderField('spacer') }}

                        {{ form.renderField('todyl_first_name') }}

                        {{ form.renderField('todyl_last_name') }}

                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('card_element') }}

                        {{ form.renderField('payment_info') }}

                        {{ form.renderField('spacer_sm') }}

                        {{ form.renderField('payment_badges') }}

                        {{ form.renderField('card_token') }}

                        {{ form.renderField('spacer') }}

                        {{ form.renderField('terms') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="small-12 large-4 columns end" data-equalizer-watch="billing">
            <div id="billing_summary_container">
                <div id="billing_summary" class="panel">
                    {{ form.renderField('spacer_sm') }}

                    {{ form.renderField('caption_summary') }}

                    <div class="medium-12 columns">
                        <div class="summary_ftu text-mid-grey">
                            <p class="charge_text">After your free trial, you will be charged {{ plan_price }} per device per month.</p>
                            <p>You can cancel your trial any time before {{ trial_end }} for free.</p>
                        </div>
                    </div>

                    {{ form.renderField('spacer_sm') }}

                    {{ form.renderField('Complete Your Order') }}

                </div>

                {{ form.renderField('spacer') }}

                <p class="mt-1 text-mid-grey">Upon clicking Complete Your Order, you will be prompted to install Todyl Defender and your free 15-day trial will begin.</p>

            </div>
        </div>

        {% endif %}

    </div>

    {{ form.renderField('user_platform') }}

</div>
</form>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    // Create a Stripe client
    var stripe = Stripe('{{ stripe_pk }}');
</script>
