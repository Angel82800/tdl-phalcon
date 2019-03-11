<div class="panels_container devices-container">
	<div class="row expanded">
		<div class="small-12 columns">
			<div class="panel">
			{% if type == 'deactivate' %}
				<h4 class="pt-1">You are about to deactivate protection for {{ device.user_device_name }}.</h4>

				<p class="pt-1">{{ device.user_device_name }} will no longer be protected.</p>
				<p>You can reactivate this device at any time from the devices tab.</p>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-2 columns">
	                    <a class="button hollow deactive btn-wide" href="/device">Cancel</a>
	                </div>

					<div class="small-12 medium-2 columns end">
                    	<a class="button btn-wide" href="javascript:void(0);" id="btn_management" data-type="{{ type }}" data-device="{{ device.pk_id }}">Deactivate Protection</a>
                    </div>
				</div>
			{% elseif type == 'reactivate' %}
				<h4 class="pt-1">You are about to reactivate protection for {{ device.user_device_name }}.</h4>

				<p class="pt-1">Protection will be resumed on {{ device.user_device_name }}.</p>
				<p>You can deactivate this device at any time from the devices tab.</p>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-2 columns">
	                    <a class="button hollow deactive btn-wide" href="/device">Cancel</a>
	                </div>

					<div class="small-12 medium-2 columns end">
                    	<a class="button btn-wide" href="javascript:void(0);" id="btn_management" data-type="{{ type }}" data-device="{{ device.pk_id }}">Reactivate Protection</a>
                    </div>
				</div>
			{% elseif type == 'forget' %}
				<h4 class="pt-1">Are you really going to forget {{ device.user_device_name }}?</h4>

				<p class="pt-1">You won't be able to reactivate a forgotten device. Would you like to continue?</p>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-2 columns">
	                    <a class="button hollow deactive btn-wide" href="/device">Cancel</a>
	                </div>

					<div class="small-12 medium-2 columns end">
                    	<a class="button btn-wide" href="javascript:void(0);" id="btn_management" data-type="{{ type }}" data-device="{{ device.pk_id }}">Yes, forget {{ device.user_device_name }}</a>
                    </div>
				</div>
			{% elseif type == 'new' %}
				<h4 class="pt-1">An activation email was sent to {{ email }}</h4>
				<p class="pt-1">Click the activation link in the email from the device you wish to protect to get started.</p>

				{% if env == 'dev' %}
				<h4 class="pt-1">The generated pin is <span class="text-blue">{{ pin }}</span></h4>
				{% endif %}

				<div class="pt-1 row expanded">
					<div class="small-12 medium-2 columns">
                    	<a class="button btn-wide" href="/device">Got it</a>
                    </div>
				</div>
			{% elseif type == 'rename' %}
				<div class="row expanded">
					<div class="medium-12 columns">
						<h4 class="pt-1">Rename {{ device.user_device_name }}</h4>
					</div>
				</div>

				<div class="pt-1 dash-page row expanded">
					<div class="small-12 medium-6 columns">
						<input class="dash-form" type="text" id="new_device_name" name="new_device_name" placeholder="New Device Name" required>
					</div>
				</div>

				<div class="pt-1 row expanded">
					<div class="small-12 medium-2 columns">
	                    <a class="button hollow deactive btn-wide" href="/device">Cancel</a>
	                </div>

					<div class="small-12 medium-2 columns end">
                    	<a id="btn_renamedevice" data-device={{ device.pk_id }} class="button btn-wide" href="javascript:void(0);">Rename Device</a>
                    </div>
				</div>
			{% endif %}
			</div>
		</div>
	</div>
</div>
