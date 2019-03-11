<div class="flex-center-vertically full-height-container">
  <div class="full-height-content text-center">
    <div class="large-6 medium-8 medium-centered">
      {% if status is empty %}

        <a class="button mt-1" href="/dashboard">Go to Dashboard</a>

      {% elseif status is 'duplicate' %}

        <h5>Your email is already verified.</h5>

        <a class="button mt-1" href="/dashboard">Go to Dashboard</a>

      {% elseif status is 'verified' %}

        <i class="i-email"></i>

        <h5>Your email has been verified successfully.</h5>

        <a class="button mt-1" href="/dashboard">Go to Dashboard</a>

      {% endif %}
    </div>
  </div>
</div>
