<div class="panels_container review-container">
    <div class="row expanded">
        <div class="small-12 columns">
            <div class="panel">
                <h4>{{ incident.alert.short_alert_summary }}</h4>
                <h5 class="text-light-grey">{{ incident.datetime_created }} | {{ incident.alert.agent.user.email }}</h5>

                <div class="separator"></div>

                <label><b>Class Type:</b> {{ incident.alert.classification.class_type }}</label>
                <label><b>Description:</b> {{ incident.alert.classification.description }}</label>
                <label><b>Alert Action:</b> {{ incident.alert.action.description }}</label>
                <label><b>Host Name:</b> {{ incident.alert.hostname }}</label>
                <label><b>RAW:</b> {{ raw_alert }}</label>

                <div class="separator"></div>

                <div class="row expanded">
                    <div class="medium-7 columns kill-padding">
                        <form class="form-inline" id="frm_assign_incident" data-abide novalidate>
                            <input type="hidden" name="incident_id" value="{{ incident.pk_id }}" />

                            <div class="row">
                                <div class="small-3 columns kill-padding">
                                    <label for="assign_to" class="inline pt-half text-blue">Assign To</label>
                                </div>

                                <div class="small-9 columns kill-padding form-group">
                                    <select id="assign_to" name="assign_to" required>
                                    {% for assignee in assignees %}
                                        {% if assignee.pk_id == identity['id'] %}
                                        <option value="{{ assignee.pk_id }}" selected>Myself</option>
                                        {% else %}
                                        <option value="{{ assignee.pk_id }}">{{ assignee.getName() }}</option>
                                        {% endif %}
                                    {% endfor %}
                                    </select>
                                    <i class="bar"></i>
                                </div>
                            </div>

                            <div class="row">
                                <div class="small-3 columns kill-padding">
                                    <label for="classification" class="inline pt-half text-blue">Classification</label>
                                </div>

                                <div class="small-9 columns kill-padding form-group">
                                    <select id="classification" name="classification" required>
                                        <option disabled="disabled" value="">Select One</option>
                                    {% for classification in classifications %}
                                        <option value="{{ classification.pk_id }}">{{ classification.classification|capitalize }}</option>
                                    {% endfor %}
                                    </select>
                                    <i class="bar"></i>
                                    <span class="form-error">Please choose an incident classification.</span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="small-3 columns kill-padding">
                                    <label for="mark_as" class="inline pt-half text-blue">Mark As</label>
                                </div>

                                <div class="small-9 columns kill-padding form-group">
                                    <select id="mark_as" name="mark_as" required>
                                        <option disabled="disabled" value="">Select One</option>
                                    {% for state in states %}
                                        <option value="{{ state.pk_id }}"{{ state.state == 'open' ? ' selected' : '' }}>{{ state.state|capitalize }}</option>
                                    {% endfor %}
                                    </select>
                                    <i class="bar"></i>
                                    <span class="form-error">Please the state of the incident.</span>
                                </div>
                            </div>

                            <div id="incident_instructions" class="row">
                                <label class="mb-1" for="instructions"><span class="text-blue">Instructions</span> <span class="text-light-grey">(Shown to Users)</span></label>

                                <div class="form-group">
                                    <input type="text" id="instructions" name="instructions" placeholder="What to do to resolve this incident." />
                                    <i class="bar"></i>
                                    <span class="form-error">Please enter incident instructions.</span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="small-12 medium-4 columns kill-padding">
                                    <a href="javascript:void(0);" id="btn_save_incident" class="button btn-wide">Save</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="medium-5 columns">
                        <h6 class="f_right text-light-grey">Logged in as: {{ identity['email'] }}</h6>
                    </div>
                </div>

                <div class="separator"></div>

                <div class="row expanded">
                    <div class="medium-7 columns end kill-padding">
                        <h5><span class="text-blue">Comments</span> <span class="text-light-grey">(Internal Only)</span></h5>

                        {% for comment in comments %}
                        <div class="row expanded mt-2">
                            <div class="medium-8 columns kill-padding">{{ comment.user.getName() }}</div>
                            <div class="medium-4 columns kill-padding">
                                <p class="f_right text-light-grey kill-margin">{{ comment.datetime_created }}</p>
                            </div>
                        </div>
                        <p class="text-mid-grey">{{ comment.comment }}</p>
                        {% endfor %}

                        <form id="frm_comment_incident" class="form-inline mt-2" data-abide novalidate>
                            <input type="hidden" name="incident_id" value="{{ incident.pk_id }}" />

                            <div class="row expanded">
                                <div class="medium-9 columns kill-padding form-group">
                                    <input type="text" id="comment" name="comment" placeholder=" " required />
                                    <label class="control-label">Add Comment</label>
                                    <i class="bar"></i>
                                    <span class="form-error">Please enter comment for the incident.</span>
                                </div>

                                <div class="medium-3 columns">
                                    <a href="javascript:void(0);" id="btn_save_comment" class="button btn-wide">Send</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
