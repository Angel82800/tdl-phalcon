<div class="panels_container setting-container">
    <div class="row expanded site-settings">
        <form id="frm_site_settings" data-abide novalidate>
        <div class="medium-12 columns dash-page">
            <div class="dash-block panel">
                <h4 class="kill-margin">Service Plans</h4>

                <h5>Device Protection</h5>

                {% for tier in 1..3 %}
                <div class="row expanded">
                    <div class="small-12 medium-4 columns">
                        <p class="title">Tier {{ tier }} Threshold</p>
                    </div>

                    <div class="small-12 medium-5 columns device_plan">
                        <div class="static-field">{{ settings['device_threshold_' ~ tier] | default('N/A') }}</div>

                        <div class="edit-field hide">
                            <input class="dash-form" type="number" placeholder="Enter Threshold for Device Tier {{ tier }}" name="device_threshold_{{ tier }}" value="{{ settings['device_threshold_' ~ tier] | default('0') }}" data-original="{{ settings['device_threshold_' ~ tier] | default('N/A') }}" data-field="value" required>
                            <div class="field-info" data-info="Updating site settings..."></div>
                        </div>
                    </div>

                    <div class="small-12 medium-3 columns action">
                        <a class="edit" href="javascript:void(0);">Edit</a>
                        <a class="save" href="javascript:void(0);">Save Changes</a>
                    </div>
                </div>

                <div class="row expanded">
                    <div class="small-12 medium-4 columns">
                        <p class="title">Tier {{ tier }} Plan</p>
                    </div>

                    <div class="small-12 medium-5 columns device_plan">
                        <div class="static-field">{{ settings['device_plan_' ~ tier] | default('N/A') }}</div>

                        <div class="edit-field hide">
                            <select name="device_plan_{{ tier }}">
                            {% for plan in plans %}
                                <option{{ settings['device_plan_' ~ tier] is not empty and plan.id is settings['device_plan_' ~ tier] ? ' selected' : '' }} value="{{ plan.id }}">{{ plan.id }}</option>
                            {% endfor %}
                            </select>
                            <!-- <input class="dash-form" type="text" placeholder="Enter Signup Plan" name="device" value="{{ settings['device'] | default('N/A') }}" data-original="{{ settings['device'] | default('N/A') }}" data-field="value" required> -->
                            <div class="field-info" data-info="Updating site settings..."></div>
                        </div>
                    </div>

                    <div class="small-12 medium-3 columns action">
                        <a class="edit" href="javascript:void(0);">Edit</a>
                        <a class="save" href="javascript:void(0);">Save Changes</a>
                    </div>
                </div>
                {% endfor %}

                <!-- <div class="row expanded">
                    <div class="small-12 medium-4 columns">
                        <p class="title">Current Pricing Plan</p>
                    </div>

                    <div class="small-12 medium-5 columns device_plan">
                        <div class="static-field">{{ settings['device'] | default('N/A') }}</div>

                        <div class="edit-field hide">
                            <select name="device">
                            {% for plan in plans %}
                                <option{{ settings['device'] is not empty and plan.id is settings['device'] ? ' selected' : '' }} value="{{ plan.id }}">{{ plan.id }}</option>
                            {% endfor %}
                            </select>
                            <div class="field-info" data-info="Updating site settings..."></div>
                        </div>
                    </div>

                    <div class="small-12 medium-3 columns action">
                        <a class="edit" href="javascript:void(0);">Edit</a>
                        <a class="save" href="javascript:void(0);">Save Changes</a>
                    </div>
                </div> -->
            </div>
        </div>
        </form>
    </div>
</div>
