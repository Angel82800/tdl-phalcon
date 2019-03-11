<div class="thankyou row pt-2">
	<div class="medium-12 columns">
    {% if from == 'registration' %}

		<div class="pt-2 pb-3 text-center thin-divider">
			<h4 class="thin-blue">Welcome to your free trial!</h4>

			<p>You should receive an email from no-reply@todyl.com with your receipt.<br />If you do not receive an email within a few minutes, please check your spam or junk folder.</p>
		</div>

		<div class="pt-3 text-center">
			<a class="button" href="/dashboard">Get Started</a>
		</div>

    {% elseif from == 'billing' %}
    <div class="dash-page panels_container billing-container">
      <div class="row expanded">
        <div class="medium-12 columns">
          <div class="row">
            <div class="panel">
              <h2 class="text-green">Thank you. Your order was confirmed.</h2>

              <p class="mt-1">You should receive an order confirmation within a few minutes. If you do not receive this confirmation, please add no-reply@todyl.com to your safe senders list, or check your spam folder.</p>

              <p class="mt-1">
                <b>Your First Invoice:</b> {{ final_price }} - {{ trial_end }}
              </p>

              <a href="/dashboard" class="button">Install Todyl Defender</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    {% else %}

    <div class="pt-2 pb-3 text-center">
      <h4 class="thin-blue">Thank You!</h4>
    </div>

    {% endif %}
    <div>
	</div>
</div>
</div>
