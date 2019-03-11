<div class="dash-page panels_container account-container">
    <div class="row expanded">
        <div class="small-12 large-9 columns">
            <div class="panel inactive_account">
                <h4 class="kill-margin">Your Subscription is Currently Suspended</h4>

                <p class="pt-1">You can contact us if you have any questions or concerns.</p>

                <div class="pt-1 row expanded">
                    <div class="small-12 medium-4 medium-offset-8 columns">
                        <a id="btn_trigger_reactivate" class="button btn-wide">Reactivate Subscription</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dash-page panels_container dialog_panels">
    <div class="row expanded">
        <div class="medium-12 large-9 columns">
            <div id="panel_reactivate_billing" class="dash-block panel">
                <form id="frm_reactivate_billing" data-abide novalidate>

                <h4>Billing</h4>

                {{ form.renderField('promo_section') }}

                {% if is_card_active %}
                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Current Card</p>
                    </div>

                    <div class="medium-8 columns">
                        <p id="current_card" class="title">{{ stripe_customer.sources.data[0].brand ~ ' Ending in ' ~ stripe_customer.sources.data[0].last4 }}</p>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="small-12 columns end">
                        <div class="separator"></div>
                    </div>
                </div>
                {% endif %}

                <div {{ is_card_active ? ' id="update_card"' : ' id="new_card"' }} class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">{{ is_card_active ? 'Change Card' : 'Add a Card' }}</p>
                    </div>

                    <div class="medium-8 columns end">
                        <div class="form-group">
                            <input class="dash-form" type="text" id="name_on_card" name="name_on_card" placeholder="Name on Card" required pattern="^[a-zA-Z\s]+$">
                            <i class="bar"></i>
                        </div>
                        <div id="card-element"></div><span id="card-errors" class="form-error"></span>
                        <input type="hidden" name="card_token">
                    </div>
                </div>

                <div class="row">
                    <div class="small-12 medium-12 columns text-right">
                        <a class="cancel button btn-link">Go Back</a>

                        {% if is_card_active %}
                            <span class="button btn-link">|</span>
                            <a href="javascript:void(0);" class="btn_change_card button btn-link">Use A Different Card</a>
                        {% endif %}

                        <a id="btn_reactivate_account" class="button">{{ is_card_active ? 'Continue to use this Card' : 'Add Card' }}</a>
                    </div>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    // Create a Stripe client
    var stripe = Stripe('{{ stripe_pk }}');
</script>
