<div class="panels_container service_container">
  {% if session.get('is_ftu') %}
  <div class="ftu_notification pt-2 text-center">
    <div class="medium-6 medium-centered">
      <img src="/img/dashboard/devices-ftu.png" alt="Intro Image"/>
      <h6 class="medium-10 medium-centered text-mid-grey pt-1">
        Let's start by protecting you and your devices. Choose the number of devices you'd like to protect below.
      </h6>
    </div>
  </div>
  {% endif %}

  <div class="row expanded">
    <div class="large-8 small-12 columns purchase_container">
    <form id="frm_service" method="post" data-abide novalidate>
      <div class="panel">
        <div class="row expanded">
          <div class="small-12 columns">
            <h4>Your Todyl Protection</h4>
            <!-- <p>Easily add protection for devices and users below.</p> -->

            <div class="separator"></div>
          </div>
        </div>

        <div class="current_users">
          <div class="row expanded">
            <p class="sub_label small-6 medium-8 columns">Users Email Address</p>
            <p class="sub_label small-6 medium-4 columns">Devices to Protect</p>
          </div>

          {% for user in current_users %}
          <div class="row expanded user_row">
            <div class="medium-8 columns">
              <div class="userdevice_info">
                {% if user['is_active'] %}
                  {% if user['user_name'] is not ' ' %}
                    <span class="main_info">{{ user['user_name'] }}</span> <span class="sub_info">{{ user['user_email'] }}</span>
                  {% else %}
                    <span class="main_info">{{ user['user_email'] }}</span>
                  {% endif %}
                {% else %}
                <span class="main_info">{{ user['user_email'] }}</span> <span class="sub_info">Hasn't connected yet.</span>&nbsp;&nbsp;
                <a href="javascript:void(0);" class="resend_invite" data-id="{{ user['user_id'] }}">Resend Invite</a>
                <!-- <a href="javascript:void(0);" class="cancel_invite" data-id="{{ user['user_id'] }}">Cancel Invite</a> -->
                {% endif %}
              </div>

              <div class="alert_info">
                Setting the number of devices to zero will also remove this user from your account.
              </div>
            </div>

            <div class="medium-4 columns">
              <div class="userdevice_counter">
                <a class="minus hidden">-</a>
                <input type="text" name="current_devices[{{ user['user_id'] }}]" value="{{ user['device_count'] }}" data-original="{{ user['device_count'] }}" data-less-than="100" data-validator="{{ user['user_id'] == identity['GUID'] ? 'less_than_2' : 'less_than' }}" onkeypress="return numbersonly(event)" oninput="checkInputLength(2, this)" required />
                <a class="plus">+</a>
              </div>
            </div>
          </div>
          {% endfor %}

          {% if ! identity['is_beta'] and ! session.get('is_ftu') and email_verified %}
          <div class="medium-12 columns">
            <div class="separator"></div>
          </div>
          {% endif %}
        </div>

        <div class="new_users"></div>

        <div class="row expanded">
          {% if ! identity['is_beta'] and ! session.get('is_ftu') and email_verified %}
            <p class="sub_label small-12 columns">or <a class="add_user" href="javascript:void(0);">Add New Users</a></p>
          {% endif %}
        </div>

      </div>

      {% if ! session.get('is_ftu') and ! email_verified %}
      <p class="mt-2 medium-12 columns text-mid-grey">
        Before you can invite additional users, you need to verify your email address.<br />
        <span>Please check your email or you can <a id="btn_resend_verification" class="change_parent">resend this verification</a></span>.
      </p>
      {% endif %}
    </form>
    </div>

    <div class="small-12 large-4 columns summary_container">
      <div class="panel">
        <div class="row expanded">
          <h4 class="small-12 columns">Summary</h4>

          <div id="summary_info" class="small-12 columns">
            <div class="no-summary">
              <p><i class="i-add-device"></i></p>
              <p>You haven't added additional protection yet.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="summary_description"></div>
    </div>
  </div>
</div>
