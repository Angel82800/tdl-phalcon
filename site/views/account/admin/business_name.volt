<div class="medium-4 columns">
    <p class="title">Name of Your Business</p>
</div>

<div class="medium-8 columns end">
    <div class="static-field">{{ organization.name }}</div>
    {% if ! organization.name %}
    <a class="edit text-light-grey" href="javascript:void(0);">Add Your Business Name</a>
    {% endif %}

    <div class="edit-field hide">
        <input class="dash-form" type="text" placeholder="Your Business Name" value="{{ organization.name }}" name="org_name" required maxlength="100">
        <span class="form-error">
            Please enter your business name
        </span>
    </div>
</div>
