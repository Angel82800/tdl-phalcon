<div class="row expanded">
    <div class="medium-12 large-9 columns">
        <div class="dash-block panel billing-settings">
            <h4>Billing</h4>

            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Current Card</p>
                </div>

                <div class="medium-8 columns">
                    <p id="current_card" class="float-left title">{{ stripe_customer ? stripe_customer.sources.data[0].brand ~ ' Ending in ' ~ stripe_customer.sources.data[0].last4 : '' }}</p>

                    <a class="edit float-right">Change</a>
                </div>
            </div>

            <div class="row expanded">
                <div class="small-12 columns end">
                    <div class="separator"></div>
                </div>
            </div>

            <form id="frm_billing" data-abide novalidate style="display: none">
            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Change Card</p>
                </div>

                <div class="medium-8 columns end">
                    <div>
                        <input class="dash-form" type="text" id="name_on_card" name="name_on_card" placeholder="Name on Card" required pattern="^[a-zA-Z\s]+$">
                    </div>
                    <div id="card-element"></div><span id="card-errors" class="form-error"></span>
                    <input type="hidden" name="card_token">
                </div>
            </div>

            <div class="row expanded">
                <div class="medium-12 columns end text-right">
                    <a class="cancel button hollow">Cancel</a>
                    <input type="submit" class="button" value="Update">
                </div>
            </div>

            <div class="row expanded">
                <div class="small-12 columns end">
                    <div class="separator"></div>
                </div>
            </div>
            </form>

            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Account Status</p>
                </div>

                <div class="medium-8 columns">
                    <div class="static-field float-left">{{ organization.is_active ? 'Active' : 'Inactive' }}</div>

                    {% if organization.is_active %}
                        <a class="edit_account_status float-right">Edit</a>
                    {% endif %}
                </div>
            </div>

            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Next Invoice</p>
                </div>

                <div class="medium-8 columns">
                    <div class="static-field float-left">{{ upcoming_invoice.date | formatDateWithoutYear }}</div>

                    <a class="view_full_history float-right">Full History</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row expanded">
    <div class="medium-12 large-9 columns">
        <div class="dash-block panel">
            <h4>Protection Package</h4>
            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Your Plan</p>
                </div>

                <div class="medium-8 columns">
                    <p class="float-left">Todyl Defender, {{ user_tier }}</p>

                    <a class="upgrade_protection float-right disabled" data-tooltip data-allow-html="true" title="Coming Soon">Upgrade</a>
                </div>
            </div>
            <div class="row expanded">
                <div class="medium-4 columns">
                    <p class="title">Protected Devices</p>
                </div>

                <div class="medium-8 columns">
                    <p class="float-left">{{ user.getDeviceCount() }}</p>

                    <a class="change_devices float-right disabled" data-tooltip data-allow-html="true" title="Coming Soon">Change</a>
                </div>
            </div>
        </div>
    </div>
</div>
