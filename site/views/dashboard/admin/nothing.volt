<i class="i-todyl"></i>
<h2 class="text-blue">Welcome to Todyl Protection</h2>
<p class="text-mid-grey pt-1">
  {% if session.get('is_ftu') %}
    Try Todyl Protection for {{ trial_days }} Days Free, On Us.
  {% else %}
    Get started by adding the users you protect.<br />
    Setting up Todyl Protection for each user only takes a few moments.
  {% endif %}
</p>
<p class="pt-1">
  {% if session.get('is_ftu') %}
    <a class="button" href="/service/device">Start Your Free Trial</a>
  {% else %}
    <a class="button" href="/service/device">Add Users or Devices</a>
  {% endif %}
</p>
