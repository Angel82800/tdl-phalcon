<div class="panels_container">
  <div class="row expanded">
    <div class="medium-12 columns">
      <div class="panel">
        <div class="breadcrumb">
          <a href="/service/device">Add Services</a> / <a href="/service/device">Protect Additional Devices</a> / Confirmation

          <a href="/user-device" class="button text-white float-right">Go Back</a>
        </div>
      </div>
    </div>
  </div>

  <div class="row expanded">
    <div class="medium-12 columns">
      <div class="panel">
        <div class="row expanded">
          <div class="small-12 columns">
            <h4 class="text-green">Thank you. Your order was confirmed and your subscription has been updated.</h4>
          </div>
        </div>

        <div class="row expanded">
          <div class="small-12 columns">
            <p>You should receive an order confirmation within a few minutes. Any new users added to your account will also be notified by email shortly. If you do not receive this confirmation, please add no-reply@todyl.com to your safe senders list, or check your spam folder.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row expanded">
    <div class="medium-6 columns">
      <div class="panel">
        {% for order_item in order_items %}
        <div class="pda-b-1">
          <div class="row expanded">
            <div class="small-12 columns">
              <h5>{{ order_item['title'] }}</h5>
            </div>
          </div>

          {% for item in order_item['items'] %}
            <div class="row expanded">
              <div class="small-7 medium-6 columns">
                {{ item['label'] }}
              </div>

              <div class="small-5 medium-4 columns end">
                {{ item['value'] }}
              </div>
            </div>
          {% endfor %}
        </div>
        {% endfor %}

      </div>
    </div>

    <div class="medium-6 columns">
      <div class="panel">
        <div class="row expanded">
          <div class="small-12 columns">
            <h5>Billing Summary</h5>
          </div>
        </div>

        <div class="row expanded">
          <div class="small-7 medium-8 columns">
            {{ change_label }}
          </div>

          <div class="small-5 medium-4 columns end">
            {{ change_amount }}
          </div>
        </div>

        <div class="row expanded text-bold">
          <div class="small-7 medium-8 columns">
            New Subscription Total
          </div>

          <div class="small-5 medium-4 columns end">
            {{ total }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {% if env == 'dev' %}
  <div class="row expanded">
    <div class="medium-12 columns">
      <div class="panel">
        <div class="row expanded">
          <div class="small-12 columns">
            <h5 class="pt-1">Generated PINs : <span class="text-blue">{{ pins }}</span></h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  {% endif %}

</div>

<!-- Google Code for Registration Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 925480275;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "QqaVCN_5q3UQ0-qmuQM";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/925480275/?label=QqaVCN_5q3UQ0-qmuQM&amp;guid=ON&amp;script=0"/>
</div>
</noscript>
