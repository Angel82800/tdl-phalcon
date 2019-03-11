{{ load_partial(identity['role'], 'ftu') }}

<div class="panels_container userdevices_container">
	<div class="row expanded">
		<div class="small-12 columns">
      {% if not load_user %}

      <div class="panel clearfix">
				<div class="small-12 medium-5 columns kill-padding">
					<h4 class="kill-margin">{{ title }}</h4>
				</div>

				<div class="small-12 medium-7 columns medium-text-right kill-padding breadcrumb_nav">
					{{ load_partial(identity['role'], 'breadcrumb_nav') }}
				</div>
      </div>

      {% else %}

      <div class="panel blue-bg clearfix">
        <div class="small-12 medium-8 columns">
          <h4 class="kill-margin">Viewing All Devices for {{ load_user.firstName or load_user.lastName ? load_user.firstName ~ ' ' ~ load_user.lastName : load_user.email }}</h4>
        </div>

        <div class="small-12 medium-4 columns text-right">
          <a class="button kill-margin" href="/user-device">Back to All Users</a>
        </div>
      </div>

      {% endif %}
		</div>
	</div>

	<div class="row expanded" data-equalizer="{{ type }}_panel">
		<ul id="{{ type }}_list" class="clearfix"></ul>
	</div>

	<!-- {{ load_partial(identity['role'], 'additional-card') }} -->
</div>

{% if type is 'device' %}

<div class="dash-page panels_container dialog_panels">
  <div class="row expanded">
    <div class="medium-12 columns">

      <div id="panel_rename_device" class="dash-block panel">
        <h4>Rename <span class="action_device_name"></span></h4>

        <div class="row expanded">
          <div class="medium-12 columns">
						<input class="dash-form" type="text" id="new_device_name" name="new_device_name" placeholder="New Device Name" required>
					</div>
				</div>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-3 medium-offset-6 columns">
						<a class="cancel button hollow btn-wide">Cancel</a>
					</div>

					<div class="small-12 medium-3 columns">
						<a id="btn_confirm_rename_device" class="button btn-wide">Rename Device</a>
					</div>
				</div>
			</div>

      <div id="panel_reinstall_defender" class="dash-block panel">
        <h4>Are you sure you want to reinstall <span class="action_device_name"></span>?</h4>

        <div class="row expanded">
          <div class="medium-12 columns">
						<p class="text-mid-grey">If you choose to reinstall Todyl Defender on this device you will be issued a new PIN number for this device on the next page.</p>
					</div>
				</div>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-3 medium-offset-6 columns">
						<a class="cancel button hollow btn-wide">Cancel</a>
					</div>

					<div class="small-12 medium-3 columns">
						<a id="btn_confirm_reinstall_defender" class="button btn-wide">Yes, I Want to Reinstall Defender</a>
					</div>
				</div>
			</div>

      <div id="panel_cancel_invite" class="medium-8 small-12 columns dash-block panel">
        <h4>Are you sure you want to cancel this invitation?</h4>

        <div class="row expanded">
          <div class="medium-12 columns">
            <p class="text-mid-grey">Cancelling <span class="action_user_email"></span>'s invite will also remove the <span class="action_user_devicecnt"></span> you allocated to their account.</p>
          </div>
        </div>

        <div class="pt-1 row expanded">
          <div class="small-12 medium-3 medium-offset-5 columns">
            <a class="cancel button hollow btn-wide">Go Back</a>
          </div>

          <div class="small-12 medium-4 columns">
            <a id="btn_confirm_cancel_invite" class="button btn-wide">Yes, Cancel This Invite</a>
          </div>
        </div>
      </div>

      <div id="panel_remove_slots" class="medium-8 small-12 columns dash-block panel">
        <h4>How many devices do you want to remove?</h4>

        <div class="row expanded other_user">
          <div class="medium-12 columns">
            <p class="text-mid-grey"><span class="action_user_email"></span> has <span class="action_user_unused_devicecnt"></span> assigned to them. Select how many to remove :</p>
            <p>
              <select id="remove_slot_cnt"></select>
            </p>
          </div>
        </div>

        <div class="pt-1 row expanded">
          <div class="small-12 medium-3 medium-offset-5 columns">
            <a class="cancel button hollow btn-wide">Cancel</a>
          </div>

          <div class="small-12 medium-4 columns">
            <a id="btn_confirm_remove_slots" class="button btn-wide">Yes, Remove <span id="remove_slot_cnt_indicator"></span></a>
          </div>
        </div>
      </div>

      <div id="panel_remove_device" class="medium-8 small-12 columns dash-block panel">
        <h4>Are you sure you want to remove <span class="action_device_name"></span>?</h4>

        <div class="row expanded other_user">
          <div class="medium-12 columns">
            <p class="text-mid-grey">This device is owned by <span class="action_device_username"></span>. If you remove this device, it will no longer be protected by Todyl.</p>
          </div>
        </div>

        <div class="pt-1 row expanded">
          <div class="small-12 medium-3 medium-offset-5 columns">
            <a class="cancel button hollow btn-wide">Cancel</a>
          </div>

          <div class="small-12 medium-4 columns">
            <a id="btn_confirm_remove_device" class="button btn-wide">Yes, Remove This Device</a>
          </div>
        </div>
      </div>

      {{ load_partial(identity['role'], 'summary') }}

		</div>

		<input type="hidden" id="action_device_id" />
    <input type="hidden" id="action_user_id" />
	</div>
</div>

<script type="text/javascript">

{% if not load_user %}
	var userdevice_func = 'loadDeviceList()';
{% else %}
  var userdevice_func = 'loadDeviceList(\'{{ load_user.GUID }}\')';
{% endif %}

</script>

{% elseif type is 'user' %}

<div class="dash-page panels_container dialog_panels">
  <div class="row expanded">
    <div class="medium-12 columns" data-equalizer="summary">

      <div class="medium-8 small-12 columns" data-equalizer-watch="summary">
        <div id="panel_remove_user" class="dash-block panel">
          <h4>Are you sure you want to remove <span class="action_user_email"></span>?</h4>

          <div class="row expanded">
            <div class="medium-12 columns">
              <p class="text-mid-grey">This will remove protection for <span class="action_user_email"></span> and their <span class="action_user_devicecnt"></span>. Your subscription will automatically reflect the removal of <span class="action_user_devicecnt"></span> in the next billing cycle.</p>
            </div>
          </div>

          <div class="pt-1 row expanded">
            <div class="small-12 medium-3 medium-offset-5 columns">
              <a class="cancel button hollow btn-wide">Cancel</a>
            </div>

            <div class="small-12 medium-4 columns">
              <a id="btn_confirm_remove_user" class="button btn-wide">Remove User</a>
            </div>
          </div>
        </div>
      </div>

      {{ load_partial(identity['role'], 'summary') }}

    </div>

    <input type="hidden" id="action_user_id" />
  </div>
</div>

<script type="text/javascript">

	var userdevice_func = 'loadUserList()';

</script>

{% endif %}

