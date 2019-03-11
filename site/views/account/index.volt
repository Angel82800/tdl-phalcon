<div class="dash-page panels_container account-container">
    <div class="row expanded">
        <div class="medium-12 large-9 columns">
            <div class="dash-block panel account-settings">
                <h4>Account</h4>

                <form id="frm_account" data-abide novalidate>
                <div class="row expanded">
                    {{ load_partial(identity['role'], 'business_name') }}
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Your First Name</p>
                    </div>

                    <div class="medium-8 columns end">
                        <div class="static-field">{{ user.firstName }}</div>
                        {% if ! user.firstName %}
                        <a class="edit text-light-grey" href="javascript:void(0);">Click to add First Name</a>
                        {% endif %}

                        <div class="edit-field hide">
                            <input class="dash-form" type="text" placeholder="Your First Name" value="{{ user.firstName }}" name="firstName" required pattern="^[a-zA-Z\s]+$">
                            <span class="form-error">
                                Please enter only letters in this field
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Your Last Name</p>
                    </div>

                    <div class="medium-8 columns end">
                        <div class="static-field">{{ user.lastName }}</div>
                        {% if ! user.lastName %}
                        <a class="edit text-light-grey" href="javascript:void(0);">Click to add Last Name</a>
                        {% endif %}

                        <div class="edit-field hide">
                            <input class="dash-form" type="text" placeholder="Your Last Name" value="{{ user.lastName }}" name="lastName" required pattern="^[a-zA-Z\s]+$">
                            <span class="form-error">
                                Please enter only letters in this field
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Your Email Address</p>
                    </div>

                    <div class="medium-8 columns end">
                        <div class="static-field">{{ user.email }}</div>

                        <div class="edit-field hide">
                            <input class="dash-form" type="text" placeholder="Your Email Address" value="{{ user.email }}" name="email" required pattern="email">
                            <span class="form-error">
                                Please enter your email
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Your Phone Number</p>
                    </div>

                    <div class="medium-8 columns">
                        <div class="static-field phone_no{{ user.primaryPhone ? ' float-left' : '' }}">{{ user.primaryPhone }}</div>
                        {% if ! user.primaryPhone %}
                        <a class="edit text-light-grey" href="javascript:void(0);">Click to add Phone</a>
                        {% endif %}

                        <div class="edit-field hide">
                            <input class="dash-form phone_no" type="text" placeholder="Your Phone Number" value="{{ user.primaryPhone }}" name="primaryPhone" required pattern="^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$">
                        </div>

                        <a class="edit float-right">Edit</a>
                    </div>
                </div>

                <div class="row expanded edit_action hide">
                    <div class="medium-12 column end text-right">
                        <a class="cancel button hollow">Cancel</a>
                        <a class="save button" data-form="account">Save Changes</a>
                    </div>
                </div>
                </form>

                <div class="row expanded pw_field">
                    <div class="medium-4 columns">
                        <p class="title">Password</p>
                    </div>

                    <div class="medium-8 columns end">
                        <a class="change_pw">Change My Password</a>
                    </div>
                </div>

                <form id="frm_password" class="hide" data-abide novalidate>
                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Your Current Password</p>
                    </div>
                    <div class="medium-8 columns group">
                        <input class="dash-form" type="password" placeholder="Current Password" name="current_password" required>
                        <a class="toggle_pwd">Show</a>
                        <span class="form-error">
                            Please enter your current password
                        </span>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">New Password</p>
                    </div>
                    <div class="medium-8 columns group">
                        <input id="todyl_password" class="dash-form" type="password" placeholder="New Password" name="new_password" required>
                        <a class="toggle_pwd">Show</a>
                        <span class="form-error">
                            Please enter your new password
                        </span>
                    </div>
                </div>

                <div class="row expanded edit_pw_action">
                    <div class="medium-12 column end text-right">
                        <a class="cancel button hollow">Cancel</a>
                        <a class="save button" data-form="password">Save Changes</a>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

    {% if ! user.is_beta %}

    {{ load_partial(identity['role'], 'billing') }}

    {% endif %}

    <div class="row expanded">
        <div class="medium-12 large-9 columns">
            <div class="dash-block panel account-settings">
                <h4>Advanced View</h4>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <p class="title">Turning on advanced view will change your dashboard and a few other pages, and may open up more advanced options in some cases.</p>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Advanced View</p>
                    </div>

                    <div class="medium-8 columns">
                        <div class="switch small">
                            <input class="switch-input" id="advanced_view" type="checkbox" name="advanced_view"{{ user.is_professional ? ' checked' : '' }}>
                            <label class="switch-paddle" for="advanced_view">
                                <span class="switch-active" aria-hidden="true">On</span>
                                <span class="switch-inactive" aria-hidden="true">Off</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row expanded">
        <div class="medium-12 large-9 columns">
            <div class="dash-block panel">
                <h4>Email Settings</h4>

                <div class="row expanded">
                    <div class="medium-4 columns">
                        <p class="title">Frequency</p>
                    </div>

                    <div class="medium-8 columns">
                        <p id="stat_email_setting" class="float-left">{{ email_setting == 'critical' ? 'Low' : 'Weekly' }}</p>

                        <a class="edit_email_settings float-right">Edit</a>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <p class="title">Todyl will send you emails related directly to your account, and updates on critical security issues, but we'll keep other communications to a minimum.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dash-page panels_container dialog_panels">
    <div class="row expanded">
        <div class="medium-12 large-9 columns">
            <div id="panel_suspend_1" class="dash-block panel">
                <h4>Account Status</h4>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <h5>Suspend my Subscription</h5>

                        <p>We'd hate to see you go, if there is anything we can do, <a href="/support">let us know</a>.<br />If you suspend your subscription some of your information will still be available should you reactivate your account, however Todyl Protection will no longer be available.</p>
                    </div>
                </div>

                <div class="pt-1 row expanded">
                    <div class="small-12 medium-3 medium-offset-5 columns">
                        <a class="cancel button hollow btn-wide">Cancel</a>
                    </div>

                    <div class="small-12 medium-4 columns">
                        <a id="btn_suspend_1" class="button btn-wide">Suspend My Account</a>
                    </div>
                </div>
            </div>

            <div id="panel_suspend_2" class="dash-block panel">
                <h4>Account Status</h4>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <h5>Enter Your Password to Confirm Suspension of Your Subscription</h5>
                    </div>

                    <div class="medium-12 columns form-group">
                        <input id="suspension_current_pw" class="dash-form" type="password" placeholder="Your Password" required>
                        <i class="bar"></i>
                        <a class="toggle_pwd">Show</a>
                        <span class="form-error">
                            Please enter your current password
                        </span>
                    </div>
                </div>

                <div class="pt-1 row expanded">
                    <div class="small-12 medium-3 medium-offset-5 columns">
                        <a class="cancel button hollow btn-wide">Cancel</a>
                    </div>

                    <div class="small-12 medium-4 columns">
                        <a id="btn_suspend_2" class="button btn-wide">Complete Process</a>
                    </div>
                </div>
            </div>

            <div id="panel_email_settings" class="dash-block panel">
                <h4>Email Settings</h4>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <p>Your security is our top priority. Our emails are designed to keep you, and your business safe.<br />Your information is <b>never</b> shared with third parties.</p>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-12 columns">
                        <div class="ts_option">
                            <input type="radio" id="email_setting_critical" name="email_setting" value="critical" {{ email_setting == 'critical' ? 'checked' : '' }} />

                            <label for="email_setting_critical" class="radio-label">Critical Emails Only</label>

                            <div class="radio-description">Todyl will send you emails related directly to your account, and updates on critical security issues.</div>
                        </div>

                        <div class="ts_option">
                            <input type="radio" id="email_setting_all" name="email_setting" value="all" {{ email_setting == 'all' ? 'checked' : '' }} />

                            <label for="email_setting_all" class="radio-label">All ~1 Per Week</label>

                            <div class="radio-description">Todyl will send you emails related directly to your account, and updates on critical security issues, you'll also receive a weekly update on your account activity.</div>
                        </div>
                    </div>
                </div>

                <div class="pt-1 row expanded">
                    <div class="small-12 medium-3 medium-offset-6 columns">
                        <a class="cancel button hollow btn-wide">Go Back</a>
                    </div>

                    <div class="small-12 medium-3 columns">
                        <a id="btn_update_email_settings" class="button btn-wide">Update</a>
                    </div>
                </div>
            </div>

            <div id="panel_invoice_history" class="dash-block panel">
                <div class="row expanded">
                    <div class="small-8 columns">
                        <h4 style="padding: 0 0">Invoice History</h4>
                    </div>

                    <div class="small-4 columns">
                        <a class="float-right cancel button">Go Back</a>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="medium-8 columns">
                        <b>Upcoming: {{ upcoming_invoice.date | formatDateWithoutYear }}</b>
                    </div>
                    <div class="medium-4 columns">
                        <b class="float-right">{{ upcoming_invoice.amount_due | formatStripeCurrency }}</b>
                    </div>
                </div>

                <div class="row expanded" style="padding: 0 0">
                    <div class="small-12 columns end">
                        <div class="separator"></div>
                    </div>
                </div>

                {% for invoice in invoice_history %}
                <div class="row expanded">
                    <div class="medium-8 columns">
                        {{ invoice.date | formatDateWithoutYear }}
                    </div>
                    <div class="medium-4 columns">
                        <b class="float-right">{{ invoice.total | formatStripeCurrency }}</b>
                    </div>
                </div>

                <div class="row expanded" style="padding: 0 0">
                    <div class="small-12 columns end">
                        <div class="separator"></div>
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    // Create a Stripe client
    var stripe = Stripe('{{ stripe_pk }}');
</script>
